<?php
session_start();

// ===== DATABASE CONNECTION =====
$db_host = 'localhost';
$db_name = 'learnflow_db';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ===== AUTH GUARD =====
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'instructor') {
    header('Location: learnflow-login.php');
    exit;
}

// ===== SESSION DATA =====
// Load instructor info from DB using session user_id
$instructor_user_id = (int)($_SESSION['user_id'] ?? 0);
if (!$instructor_user_id) {
    header('Location: learnflow-login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.email, up.first_name, up.last_name, up.display_name,
           up.avatar_url, ip.employee_id, ip.designation, d.name AS dept_name
    FROM users u
    JOIN user_profiles up ON up.user_id = u.id
    LEFT JOIN instructor_profiles ip ON ip.user_id = u.id
    LEFT JOIN departments d ON d.id = ip.department_id
    WHERE u.id = ?
");
$stmt->execute([$instructor_user_id]);
$instructor_row = $stmt->fetch();

$instructor_name  = $instructor_row ? ($instructor_row['display_name'] ?? 'Catherine Santos') : ($_SESSION['user'] ?? 'Catherine Santos');
$instructor_email = $instructor_row ? $instructor_row['email'] : ($_SESSION['email'] ?? 'santos_cath@plpasig.edu.ph');
$instructor_dept  = $instructor_row ? ($instructor_row['dept_name'] ?? 'College of Computer Studies') : ($_SESSION['dept'] ?? 'College of Computer Studies');
$instructor_designation = $instructor_row['designation'] ?? 'Instructor I';
$instructor_photo_url   = $instructor_row ? ($instructor_row['avatar_url'] ?? null) : null;

// ===== LOAD COURSES & SECTIONS FROM DB =====
$stmt = $pdo->prepare("
    SELECT cs.id AS section_id, cs.section_code, cs.room, cs.schedule, cs.max_students, cs.status AS section_status,
           c.id AS course_id, c.code AS course_code, c.title AS course_title, c.units, c.status AS course_status,
           at.label AS term_label,
           (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = cs.id AND e.status = 'enrolled') AS enrolled_count,
           (SELECT COUNT(*) FROM assignments a WHERE a.section_id = cs.id AND a.status = 'published') AS assignment_count,
           (SELECT COUNT(*) FROM quizzes q WHERE q.section_id = cs.id AND q.status = 'published') AS quiz_count
    FROM course_sections cs
    JOIN courses c ON c.id = cs.course_id
    JOIN academic_terms at ON at.id = cs.term_id
    WHERE cs.instructor_id = ?
    ORDER BY cs.id
");
$stmt->execute([$instructor_user_id]);
$db_sections = $stmt->fetchAll();
$name_parts = explode(' ', $instructor_name);
$initials = '';
foreach ($name_parts as $p) {
    if (isset($p[0]) && ctype_alpha($p[0])) $initials .= strtoupper($p[0]);
}
$initials = substr($initials, 0, 2);
$theme = $_COOKIE['theme'] ?? 'dark';

// ===== LOAD CUSTOM THEME FROM DB =====
$db_theme = null;
try {
    $th_stmt = $pdo->query("SELECT * FROM theme_settings WHERE id=1 LIMIT 1");
    $db_theme = $th_stmt ? $th_stmt->fetch() : null;
} catch(Exception $e) { $db_theme = null; }

// ===== BUILD JS-READY COURSE DATA FROM DB =====
$js_courses       = [];
$js_inst_data     = [];
$js_archived_data = [];
$course_emojis    = ['💻','🌐','🧮','🎯','📡','🔬','🧪','📐','🛠️','📊','🤖','🎲'];
$emoji_map        = ['IT 106' => '💻', 'IT 301' => '🌐', 'IT 201' => '🧮', 'IT 411' => '🎯'];
$color_map        = ['IT 106' => '#CC3A72', 'IT 301' => '#4AAEE8', 'IT 201' => '#E09010', 'IT 411' => '#a82860'];

foreach ($db_sections as $idx => $sec) {
    $code  = $sec['course_code'];
    $title = $sec['course_title'];
    $emoji = $emoji_map[$code] ?? $course_emojis[$idx % count($course_emojis)];
    $color = $color_map[$code] ?? '#CC3A72';
    $label = $title . ' (' . $code . ')';
    $isArchived = ($sec['course_status'] === 'archived');

    if (!$isArchived) {
        $js_courses[] = ['code' => $code, 'name' => $title, 'label' => $label];
    }

    $enrolled = (int)$sec['enrolled_count'];
    $acts = (int)$sec['assignment_count'] + (int)$sec['quiz_count'];
    $prog = min(100, $acts > 0 ? ($acts * 20) : 0);

    $row = [
        'id'      => (int)$sec['section_id'],
        'name'    => $title,
        'code'    => $code,
        'section' => $sec['section_code'],
        'students'=> $enrolled,
        'prog'    => $prog,
        'emoji'   => $emoji,
        'color'   => $color,
    ];

    if ($isArchived) {
        $js_archived_data[] = $row;
    } else {
        $js_inst_data[] = $row;
    }
}

$js_courses_json      = json_encode($js_courses,       JSON_UNESCAPED_UNICODE);
$js_inst_data_json    = json_encode($js_inst_data,     JSON_UNESCAPED_UNICODE);
$js_archived_data_json= json_encode($js_archived_data, JSON_UNESCAPED_UNICODE);

// ===== AJAX: UPLOAD PHOTO =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_photo') {
    header('Content-Type: application/json');
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit;
    }
    $file = $_FILES['photo'];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, WebP, or GIF allowed.']);
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB.']);
        exit;
    }
    $uploadDir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'avatar_' . $instructor_user_id . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
        exit;
    }
    $avatarUrl = 'uploads/avatars/' . $filename;
    try {
        $pdo->prepare("UPDATE user_profiles SET avatar_url=? WHERE user_id=?")->execute([$avatarUrl, $instructor_user_id]);
        $pdo->prepare("INSERT INTO media_files (uploader_id, original_name, stored_name, mime_type, file_size_kb, file_path, is_public) VALUES (?,?,?,?,?,?,1)")
            ->execute([$instructor_user_id, $file['name'], $filename, $file['type'], (int)ceil($file['size']/1024), $avatarUrl]);
        echo json_encode(['success' => true, 'avatar_url' => $avatarUrl, 'message' => 'Photo updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE PROFILE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    header('Content-Type: application/json');
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $dept        = trim($_POST['department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    if (!$fullName || !$email) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    $parts     = explode(' ', $fullName, 2);
    $firstName = $parts[0];
    $lastName  = $parts[1] ?? '';
    try {
        $pdo->prepare("UPDATE user_profiles SET first_name=?, last_name=? WHERE user_id=?")->execute([$firstName, $lastName, $instructor_user_id]);
        $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email, $instructor_user_id]);
        if ($designation) {
            $pdo->prepare("UPDATE instructor_profiles SET designation=? WHERE user_id=?")->execute([$designation, $instructor_user_id]);
        }
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!', 'display_name' => trim($firstName . ' ' . $lastName)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: POST MODULE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'post_module') {
    header('Content-Type: application/json');
    $section_id  = (int)($_POST['section_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (!$title || !$section_id) {
        echo json_encode(['success' => false, 'message' => 'Title and section are required.']);
        exit;
    }
    try {
        $stmt_ord = $pdo->prepare("SELECT COALESCE(MAX(sort_order),0)+1 AS next_ord FROM modules WHERE section_id=?");
        $stmt_ord->execute([$section_id]);
        $next_ord = (int)($stmt_ord->fetch()['next_ord'] ?? 1);
        $stmt_ins = $pdo->prepare("INSERT INTO modules (section_id, title, description, sort_order, is_published, published_at) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt_ins->execute([$section_id, $title, $description, $next_ord]);
        $module_id = (int)$pdo->lastInsertId();
        // Handle file upload
        if (!empty($_FILES['module_file']) && $_FILES['module_file']['error'] === UPLOAD_ERR_OK) {
            $mf       = $_FILES['module_file'];
            $modDir   = __DIR__ . '/uploads/modules/';
            if (!is_dir($modDir)) mkdir($modDir, 0755, true);
            $mExt      = strtolower(pathinfo($mf['name'], PATHINFO_EXTENSION));
            $mFilename = 'mod_' . $section_id . '_' . $module_id . '_' . time() . '.' . $mExt;
            $mDest     = $modDir . $mFilename;
            if (move_uploaded_file($mf['tmp_name'], $mDest)) {
                $mUrl = 'uploads/modules/' . $mFilename;
                $pdo->prepare("INSERT INTO media_files (uploader_id, original_name, stored_name, mime_type, file_size_kb, file_path, is_public) VALUES (?,?,?,?,?,?,1)")
                    ->execute([$instructor_user_id, $mf['name'], $mFilename, $mf['type'], (int)ceil($mf['size']/1024), $mUrl]);
                $mime = $mf['type'];
                $lType = 'link';
                if (strpos($mime,'video')!==false) $lType='video';
                elseif (strpos($mime,'audio')!==false) $lType='audio';
                elseif ($mime==='application/pdf'||strpos($mime,'presentation')!==false) $lType='slide';
                elseif (strpos($mime,'text')!==false||strpos($mime,'word')!==false) $lType='reading';
                $pdo->prepare("INSERT INTO lessons (module_id, title, lesson_type, resource_url, sort_order, is_published) VALUES (?,?,?,?,1,1)")
                    ->execute([$module_id, $mf['name'], $lType, $mUrl]);
            }
        }
        echo json_encode(['success' => true, 'module_id' => $module_id, 'message' => 'Module posted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: GET MODULES =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_modules') {
    header('Content-Type: application/json');
    $section_id = (int)($_GET['section_id'] ?? 0);
    try {
        $stmt_mods = $pdo->prepare("
            SELECT m.id, m.title, m.description, m.sort_order, m.is_published, m.published_at,
                   m.created_at, COUNT(l.id) AS lesson_count
            FROM modules m
            LEFT JOIN lessons l ON l.module_id = m.id
            WHERE m.section_id = ?
            GROUP BY m.id
            ORDER BY m.sort_order ASC
        ");
        $stmt_mods->execute([$section_id]);
        $mods = $stmt_mods->fetchAll();
        foreach ($mods as &$mod) {
            $stmt_files = $pdo->prepare("SELECT id, title, lesson_type, resource_url FROM lessons WHERE module_id=? AND resource_url IS NOT NULL ORDER BY sort_order ASC");
            $stmt_files->execute([$mod['id']]);
            $mod['files'] = $stmt_files->fetchAll();
        }
        echo json_encode(['success' => true, 'modules' => $mods]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'modules' => []]);
    }
    exit;
}

// ===== AJAX: GET COURSE STATS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_course_stats') {
    header('Content-Type: application/json');
    $section_id = (int)($_GET['section_id'] ?? 0);
    try {
        $stmt_s = $pdo->prepare("
            SELECT
              (SELECT COUNT(*) FROM enrollments  WHERE section_id=? AND status='enrolled') AS students,
              (SELECT COUNT(*) FROM announcements WHERE scope='section' AND scope_id=? AND author_id=?) AS announcements,
              (SELECT COUNT(*) FROM modules       WHERE section_id=? AND is_published=1) AS modules,
              (SELECT COUNT(*) FROM assignments   WHERE section_id=? AND status='published') AS assignments,
              (SELECT COUNT(*) FROM quizzes       WHERE section_id=? AND status='published') AS quizzes
        ");
        $stmt_s->execute([$section_id, $section_id, $instructor_user_id, $section_id, $section_id, $section_id]);
        $stats = $stmt_s->fetch();
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'stats' => []]);
    }
    exit;
}

// ===== AJAX: POST ANNOUNCEMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'post_announcement') {
    header('Content-Type: application/json');
    $section_id = (int)($_POST['section_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $body       = trim($_POST['body'] ?? '');
    $pinned     = (int)(!empty($_POST['pinned']));
    if (!$title || !$body || !$section_id) {
        echo json_encode(['success' => false, 'message' => 'Title, body and section are required.']); exit;
    }
    try {
        $pdo->prepare("INSERT INTO announcements (author_id, scope, scope_id, title, body, is_pinned, published_at) VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$instructor_user_id, 'section', $section_id, $title, $body, $pinned]);
        $ann_id = (int)$pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $ann_id, 'message' => 'Announcement posted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE ANNOUNCEMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_announcement') {
    header('Content-Type: application/json');
    $ann_id = (int)($_POST['ann_id'] ?? 0);
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $pinned = (int)($_POST['pinned'] ?? 0);
    if (!$ann_id || !$title || !$body) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
    try {
        $pdo->prepare("UPDATE announcements SET title=?, body=?, is_pinned=? WHERE id=? AND author_id=?")
            ->execute([$title, $body, $pinned, $ann_id, $instructor_user_id]);
        echo json_encode(['success' => true, 'message' => 'Announcement updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: DELETE ANNOUNCEMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_announcement') {
    header('Content-Type: application/json');
    $ann_id = (int)($_POST['ann_id'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM announcements WHERE id=? AND author_id=?")->execute([$ann_id, $instructor_user_id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: ARCHIVE COURSE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archive_course') {
    header('Content-Type: application/json');
    $section_id = (int)($_POST['section_id'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');
    if (!$section_id) { echo json_encode(['success' => false, 'message' => 'Missing section ID']); exit; }
    try {
        $stmt_chk = $pdo->prepare("SELECT cs.id, c.id AS course_id FROM course_sections cs JOIN courses c ON c.id=cs.course_id WHERE cs.id=? AND cs.instructor_id=?");
        $stmt_chk->execute([$section_id, $instructor_user_id]);
        $chk = $stmt_chk->fetch();
        if (!$chk) { echo json_encode(['success' => false, 'message' => 'Section not found or access denied']); exit; }
        $pdo->prepare("UPDATE course_sections SET status='archived' WHERE id=?")->execute([$section_id]);
        $pdo->prepare("UPDATE courses SET status='archived', archived_at=NOW(), archived_by=?, archive_reason=? WHERE id=?")
            ->execute([$instructor_user_id, $reason ?: 'Archived by instructor', $chk['course_id']]);
        $pdo->prepare("INSERT INTO course_archives (course_id, section_id, action, performed_by, reason) VALUES (?,?,'archived',?,?)")
            ->execute([$chk['course_id'], $section_id, $instructor_user_id, $reason ?: 'Archived by instructor']);
        echo json_encode(['success' => true, 'message' => 'Course archived successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UNARCHIVE COURSE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unarchive_course') {
    header('Content-Type: application/json');
    $section_id = (int)($_POST['section_id'] ?? 0);
    if (!$section_id) { echo json_encode(['success' => false, 'message' => 'Missing section ID']); exit; }
    try {
        $stmt_chk = $pdo->prepare("SELECT cs.id, c.id AS course_id FROM course_sections cs JOIN courses c ON c.id=cs.course_id WHERE cs.id=? AND cs.instructor_id=?");
        $stmt_chk->execute([$section_id, $instructor_user_id]);
        $chk = $stmt_chk->fetch();
        if (!$chk) { echo json_encode(['success' => false, 'message' => 'Section not found or access denied']); exit; }
        $pdo->prepare("UPDATE course_sections SET status='open' WHERE id=?")->execute([$section_id]);
        $pdo->prepare("UPDATE courses SET status='published', archived_at=NULL, archived_by=NULL, archive_reason=NULL WHERE id=?")
            ->execute([$chk['course_id']]);
        $pdo->prepare("INSERT INTO course_archives (course_id, section_id, action, performed_by, reason) VALUES (?,?,'restored',?,NULL)")
            ->execute([$chk['course_id'], $section_id, $instructor_user_id]);
        echo json_encode(['success' => true, 'message' => 'Course restored successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: SAVE GRADE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_grade') {
    header('Content-Type: application/json');
    $sub_id   = (int)($_POST['submission_id'] ?? 0);
    $score    = (float)($_POST['score'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    if (!$sub_id) { echo json_encode(['success' => false, 'message' => 'Missing submission ID']); exit; }
    try {
        $pdo->prepare("UPDATE submissions SET score=?, feedback=?, status='graded', graded_by=?, graded_at=NOW() WHERE id=?")
            ->execute([$score, $feedback, $instructor_user_id, $sub_id]);
        echo json_encode(['success' => true, 'message' => 'Grade saved!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE MODULE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_module') {
    header('Content-Type: application/json');
    $module_id   = (int)($_POST['module_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (!$module_id || !$title) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
    try {
        // Verify instructor owns this module via section enrollment
        $stmt_chk = $pdo->prepare("SELECT m.id FROM modules m JOIN sections s ON s.id=m.section_id WHERE m.id=? AND s.instructor_id=?");
        $stmt_chk->execute([$module_id, $instructor_user_id]);
        if (!$stmt_chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
        $pdo->prepare("UPDATE modules SET title=?, description=? WHERE id=?")
            ->execute([$title, $description, $module_id]);
        echo json_encode(['success' => true, 'message' => 'Module updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: DELETE MODULE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_module') {
    header('Content-Type: application/json');
    $module_id = (int)($_POST['module_id'] ?? 0);
    if (!$module_id) { echo json_encode(['success' => false, 'message' => 'Missing module_id']); exit; }
    try {
        $stmt_chk = $pdo->prepare("SELECT m.id FROM modules m JOIN sections s ON s.id=m.section_id WHERE m.id=? AND s.instructor_id=?");
        $stmt_chk->execute([$module_id, $instructor_user_id]);
        if (!$stmt_chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
        $pdo->prepare("DELETE FROM lessons WHERE module_id=?")->execute([$module_id]);
        $pdo->prepare("DELETE FROM modules WHERE id=?")->execute([$module_id]);
        echo json_encode(['success' => true, 'message' => 'Module deleted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE DISCUSSION REPLY =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_reply') {
    header('Content-Type: application/json');
    $reply_id = (int)($_POST['reply_id'] ?? 0);
    $body     = trim($_POST['body'] ?? '');
    if (!$reply_id || !$body) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
    try {
        $pdo->prepare("UPDATE forum_replies SET body=? WHERE id=? AND author_id=?")
            ->execute([$body, $reply_id, $instructor_user_id]);
        echo json_encode(['success' => true, 'message' => 'Reply updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: DELETE DISCUSSION REPLY =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_reply') {
    header('Content-Type: application/json');
    $reply_id = (int)($_POST['reply_id'] ?? 0);
    if (!$reply_id) { echo json_encode(['success' => false, 'message' => 'Missing reply_id']); exit; }
    try {
        $pdo->prepare("DELETE FROM forum_replies WHERE id=? AND author_id=?")->execute([$reply_id, $instructor_user_id]);
        echo json_encode(['success' => true, 'message' => 'Reply deleted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE DISCUSSION POST (thread body) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_discussion') {
    header('Content-Type: application/json');
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $title     = trim($_POST['title'] ?? '');
    $body      = trim($_POST['body'] ?? '');
    if (!$thread_id || !$title || !$body) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
    try {
        $pdo->prepare("UPDATE forum_threads SET title=?, body=? WHERE id=? AND author_id=?")
            ->execute([$title, $body, $thread_id, $instructor_user_id]);
        echo json_encode(['success' => true, 'message' => 'Discussion updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: POST REPLY TO FORUM THREAD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'post_reply') {
    header('Content-Type: application/json');
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $body      = trim($_POST['body'] ?? '');
    if (!$thread_id || !$body) { echo json_encode(['success' => false, 'message' => 'Missing fields']); exit; }
    try {
        $pdo->prepare("INSERT INTO forum_replies (thread_id, author_id, body) VALUES (?,?,?)")
            ->execute([$thread_id, $instructor_user_id, $body]);
        $reply_id = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE forum_threads SET view_count=view_count+1 WHERE id=?")->execute([$thread_id]);
        echo json_encode(['success' => true, 'reply_id' => $reply_id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: CREATE DISCUSSION THREAD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_discussion') {
    header('Content-Type: application/json');
    $section_id = (int)($_POST['section_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $body       = trim($_POST['body'] ?? '');
    if (!$section_id || !$title || !$body) {
        echo json_encode(['success' => false, 'message' => 'All fields required']); exit;
    }
    try {
        $stmt_f = $pdo->prepare("SELECT id FROM forums WHERE section_id=? LIMIT 1");
        $stmt_f->execute([$section_id]);
        $forum = $stmt_f->fetch();
        if (!$forum) {
            $pdo->prepare("INSERT INTO forums (section_id, title, description) VALUES (?,?,?)")
                ->execute([$section_id, 'General Discussion', 'Course discussion forum']);
            $forum_id = (int)$pdo->lastInsertId();
        } else {
            $forum_id = (int)$forum['id'];
        }
        $pdo->prepare("INSERT INTO forum_threads (forum_id, author_id, title, body) VALUES (?,?,?,?)")
            ->execute([$forum_id, $instructor_user_id, $title, $body]);
        $thread_id = (int)$pdo->lastInsertId();
        echo json_encode(['success' => true, 'thread_id' => $thread_id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: PUBLISH ASSIGNMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'publish_assignment') {
    header('Content-Type: application/json');
    $section_id   = (int)($_POST['section_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $due_date     = trim($_POST['due_date'] ?? '');
    $max_score    = (int)($_POST['max_score'] ?? 100);
    $status       = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    if (!$section_id || !$title) {
        echo json_encode(['success' => false, 'message' => 'Title and section required']); exit;
    }
    try {
        $pdo->prepare("INSERT INTO assignments (section_id, title, instructions, max_score, due_date, allow_late, status) VALUES (?,?,?,?,?,1,?)")
            ->execute([$section_id, $title, $instructions, $max_score, $due_date ?: null, $status]);
        $asg_id = (int)$pdo->lastInsertId();
        if ($status === 'published') {
            $stmt_enr = $pdo->prepare("SELECT student_id FROM enrollments WHERE section_id=? AND status='enrolled'");
            $stmt_enr->execute([$section_id]);
            foreach ($stmt_enr->fetchAll() as $e) {
                $pdo->prepare("INSERT INTO notifications (recipient_id, notification_type, title, message) VALUES (?,?,?,?)")
                    ->execute([$e['student_id'], 'new_assignment', 'New Assignment: ' . $title, 'A new assignment has been posted in your course.']);
            }
        }
        echo json_encode(['success' => true, 'assignment_id' => $asg_id, 'message' => 'Assignment ' . $status . '!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE ASSIGNMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_assignment') {
    header('Content-Type: application/json');
    $asg_id           = (int)($_POST['assignment_id'] ?? 0);
    $title            = trim($_POST['title'] ?? '');
    $instructions     = trim($_POST['instructions'] ?? '');
    $submission_type  = trim($_POST['submission_type'] ?? 'file');
    $due_date         = trim($_POST['due_date'] ?? '');
    $max_score        = (int)($_POST['max_score'] ?? 100);
    if (!$asg_id || !$title) {
        echo json_encode(['success' => false, 'message' => 'Title is required']); exit;
    }
    try {
        $stmt_chk = $pdo->prepare("SELECT a.id, a.section_id FROM assignments a JOIN course_sections cs ON cs.id=a.section_id WHERE a.id=? AND cs.instructor_id=?");
        $stmt_chk->execute([$asg_id, $instructor_user_id]);
        $asg_data = $stmt_chk->fetch();
        if (!$asg_data) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
        
        $pdo->prepare("UPDATE assignments SET title=?, instructions=?, max_score=?, due_date=? WHERE id=?")
            ->execute([$title, $instructions, $max_score, $due_date ?: null, $asg_id]);
        
        // Handle file attachments if provided
        if (!empty($_FILES['attachments'])) {
            $uploadDir = __DIR__ . '/uploads/assignments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $files = $_FILES['attachments'];
            $numFiles = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < $numFiles; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $origName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
                $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $filename = 'asg_' . $asg_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                $destPath = $uploadDir . $filename;
                if (move_uploaded_file($tmpName, $destPath)) {
                    $filePath = 'uploads/assignments/' . $filename;
                    $pdo->prepare("INSERT INTO media_files (uploader_id, original_name, stored_name, mime_type, file_size_kb, file_path, is_public) VALUES (?,?,?,?,?,?,1)")
                        ->execute([$instructor_user_id, $origName, $filename, $fileType, (int)ceil($fileSize/1024), $filePath]);
                }
            }
        }
        echo json_encode(['success' => true, 'message' => 'Assignment updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: DELETE ASSIGNMENT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_assignment') {
    header('Content-Type: application/json');
    $asg_id = (int)($_POST['assignment_id'] ?? 0);
    if (!$asg_id) { echo json_encode(['success' => false, 'message' => 'Missing assignment_id']); exit; }
    try {
        $stmt_chk = $pdo->prepare("SELECT a.id FROM assignments a JOIN course_sections cs ON cs.id=a.section_id WHERE a.id=? AND cs.instructor_id=?");
        $stmt_chk->execute([$asg_id, $instructor_user_id]);
        if (!$stmt_chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
        $pdo->prepare("DELETE FROM submissions WHERE assignment_id=?")->execute([$asg_id]);
        $pdo->prepare("DELETE FROM assignments WHERE id=?")->execute([$asg_id]);
        echo json_encode(['success' => true, 'message' => 'Assignment deleted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: SAVE NEW QUIZ WITH QUESTIONS =====
// ===== AJAX: GET QUIZZES FOR INSTRUCTOR =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_quizzes') {
    header('Content-Type: application/json');
    $section_id = (int)($_GET['section_id'] ?? 0);
    
    try {
        if ($section_id) {
            // Get quizzes for specific section
            $stmt = $pdo->prepare("
                SELECT q.id, q.title, q.time_limit_min, q.max_score, q.status, q.created_at,
                       q.section_id, '' as section_code, '' as course_title,
                       COUNT(DISTINCT qq.id) as question_count,
                       COUNT(DISTINCT CASE WHEN qa.status IN ('submitted','graded') THEN qa.student_id END) as attempt_count,
                       ROUND(AVG(CASE WHEN qa.status IN ('submitted','graded') THEN qa.score END),0) as avg_score,
                       COUNT(DISTINCT e.student_id) as enrolled_count
                FROM quizzes q
                LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id
                LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id
                LEFT JOIN enrollments e ON e.section_id = q.section_id AND e.status='enrolled'
                WHERE q.section_id = ?
                GROUP BY q.id, q.title, q.time_limit_min, q.max_score, q.status, q.created_at, q.section_id
                ORDER BY q.created_at DESC
            ");
            $stmt->execute([$section_id]);
        } else {
            // Get all quizzes for instructor
            $stmt = $pdo->prepare("
                SELECT q.id, q.title, q.time_limit_min, q.max_score, q.status, q.created_at,
                       q.section_id,
                       cs.section_code, c.title as course_title,
                       COUNT(DISTINCT qq.id) as question_count,
                       COUNT(DISTINCT CASE WHEN qa.status IN ('submitted','graded') THEN qa.student_id END) as attempt_count,
                       ROUND(AVG(CASE WHEN qa.status IN ('submitted','graded') THEN qa.score END),0) as avg_score,
                       COUNT(DISTINCT e.student_id) as enrolled_count
                FROM quizzes q
                JOIN course_sections cs ON cs.id = q.section_id
                JOIN courses c ON c.id = cs.course_id
                LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id
                LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id
                LEFT JOIN enrollments e ON e.section_id = q.section_id AND e.status='enrolled'
                WHERE cs.instructor_id = ?
                GROUP BY q.id, q.title, q.time_limit_min, q.max_score, q.status, q.created_at, q.section_id, cs.section_code, c.title
                ORDER BY q.created_at DESC
            ");
            $stmt->execute([$instructor_user_id]);
        }
        
        $quizzes = $stmt->fetchAll();
        echo json_encode(['success' => true, 'quizzes' => $quizzes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'quizzes' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: GET QUIZ DETAILS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_quiz_detail') {
    header('Content-Type: application/json');
    $quiz_id = (int)($_GET['quiz_id'] ?? 0);
    
    if (!$quiz_id) {
        echo json_encode(['success' => false, 'error' => 'Missing quiz_id']); exit;
    }
    
    try {
        // Get quiz
        $stmt_q = $pdo->prepare("
            SELECT q.* FROM quizzes q
            JOIN course_sections cs ON cs.id = q.section_id
            WHERE q.id = ? AND cs.instructor_id = ?
        ");
        $stmt_q->execute([$quiz_id, $instructor_user_id]);
        $quiz = $stmt_q->fetch();
        
        if (!$quiz) {
            echo json_encode(['success' => false, 'error' => 'Not found or not authorized']); exit;
        }
        
        // Get questions
        $stmt_qq = $pdo->prepare("
            SELECT qq.id, qq.question_text, qq.question_type, qq.points, qq.order_num, qq.choices, qq.correct_answer
            FROM quiz_questions qq
            WHERE qq.quiz_id = ?
            ORDER BY qq.order_num ASC
        ");
        $stmt_qq->execute([$quiz_id]);
        $questions = $stmt_qq->fetchAll();
        
        // Decode choices JSON for each question
        foreach ($questions as &$q) {
            $q['options'] = json_decode($q['choices'] ?? '[]', true) ?: [];
        }
        
        $quiz['questions'] = $questions;
        echo json_encode(['success' => true, 'quiz' => $quiz]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: UPDATE QUIZ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_quiz') {
    header('Content-Type: application/json');
    $quiz_id    = (int)($_POST['quiz_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $time_raw   = trim($_POST['time_limit'] ?? '');
    $status     = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $time_limit = (int)preg_replace('/[^0-9]/', '', $time_raw) ?: null;
    if (!$quiz_id || !$title) { echo json_encode(['success' => false, 'message' => 'Title required']); exit; }
    try {
        $stmt_chk = $pdo->prepare("SELECT q.id FROM quizzes q JOIN course_sections cs ON cs.id=q.section_id WHERE q.id=? AND cs.instructor_id=?");
        $stmt_chk->execute([$quiz_id, $instructor_user_id]);
        if (!$stmt_chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
        $pdo->prepare("UPDATE quizzes SET title=?, time_limit_min=?, status=? WHERE id=?")
            ->execute([$title, $time_limit, $status, $quiz_id]);
        echo json_encode(['success' => true, 'message' => 'Quiz updated!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: DELETE QUIZ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_quiz') {
    header('Content-Type: application/json');
    $quiz_id = (int)($_POST['quiz_id'] ?? 0);
    if (!$quiz_id) { echo json_encode(['success' => false, 'message' => 'Missing quiz_id']); exit; }
    try {
        $stmt_chk = $pdo->prepare("SELECT q.id FROM quizzes q JOIN course_sections cs ON cs.id=q.section_id WHERE q.id=? AND cs.instructor_id=?");
        $stmt_chk->execute([$quiz_id, $instructor_user_id]);
        if (!$stmt_chk->fetch()) { echo json_encode(['success' => false, 'message' => 'Not authorized']); exit; }
        $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id=?")->execute([$quiz_id]);
        $pdo->prepare("DELETE FROM quiz_attempts WHERE quiz_id=?")->execute([$quiz_id]);
        $pdo->prepare("DELETE FROM quizzes WHERE id=?")->execute([$quiz_id]);
        echo json_encode(['success' => true, 'message' => 'Quiz deleted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: DELETE DISCUSSION THREAD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_discussion') {
    header('Content-Type: application/json');
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    if (!$thread_id) { echo json_encode(['success' => false, 'message' => 'Missing thread_id']); exit; }
    try {
        // Only allow deleting threads the instructor authored
        $stmt_own = $pdo->prepare("SELECT id FROM forum_threads WHERE id=? AND author_id=?");
        $stmt_own->execute([$thread_id, $instructor_user_id]);
        if (!$stmt_own->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own discussions.']); exit;
        }
        $pdo->prepare("DELETE FROM forum_replies WHERE thread_id=?")->execute([$thread_id]);
        $pdo->prepare("DELETE FROM forum_threads WHERE id=?")->execute([$thread_id]);
        echo json_encode(['success' => true, 'message' => 'Discussion deleted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: CREATE COURSE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_course') {
    header('Content-Type: application/json');
    $code    = trim($_POST['code']    ?? '');
    $title   = trim($_POST['title']   ?? '');
    $section = trim($_POST['section'] ?? 'A');
    $term_id = (int)($_POST['term_id'] ?? 2);
    if (!$code || !$title) {
        echo json_encode(['success'=>false,'message'=>'Course code and title are required']); exit;
    }
    try {
        // Check for duplicate code
        $chk = $pdo->prepare("SELECT id FROM courses WHERE code=? LIMIT 1");
        $chk->execute([$code]);
        if ($chk->fetch()) {
            echo json_encode(['success'=>false,'message'=>"Course code \"$code\" already exists."]); exit;
        }
        $pdo->prepare("INSERT INTO courses (code, title, status, created_by) VALUES (?,?,'published',?)")
            ->execute([$code, $title, $instructor_user_id]);
        $course_id   = (int)$pdo->lastInsertId();
        $section_code = $code . '-' . strtoupper($section);
        $pdo->prepare("INSERT INTO course_sections (course_id, instructor_id, section_code, term_id, status) VALUES (?,?,?,?,'open')")
            ->execute([$course_id, $instructor_user_id, $section_code, $term_id]);
        $section_id = (int)$pdo->lastInsertId();
        echo json_encode(['success'=>true,'course_id'=>$course_id,'section_id'=>$section_id,'message'=>"Course \"$title\" created!"]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ===== AJAX: SAVE / PUBLISH QUIZ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_quiz') {
    header('Content-Type: application/json');
    $title          = trim($_POST['title']          ?? '');
    $section_id     = (int)($_POST['section_id']    ?? 0);
    $time_raw       = trim($_POST['time_limit']     ?? '');
    $status         = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $questions_json = $_POST['questions']           ?? '[]';
    $description    = trim($_POST['description']    ?? '');
    $due_date       = trim($_POST['due_date']        ?? '');
    $max_score      = (int)($_POST['max_score']      ?? 100);
    // parse time limit string like "30 minutes" → int
    $time_limit = (int)preg_replace('/[^0-9]/','',$time_raw) ?: null;
    if (!$title || !$section_id) {
        echo json_encode(['success'=>false,'message'=>'Quiz title and course are required']); exit;
    }
    try {
        $pdo->prepare("INSERT INTO quizzes (section_id, title, description, due_date, time_limit_min, max_score, status) VALUES (?,?,?,?,?,?,?)")
            ->execute([$section_id, $title, $description ?: null, $due_date ?: null, $time_limit, $max_score, $status]);
        $quiz_id = (int)$pdo->lastInsertId();
        // Save questions
        $questions = json_decode($questions_json, true) ?: [];
        foreach ($questions as $i => $q) {
            $q_type  = $q['type'] === 'tf' ? 'true_false' : ($q['type'] === 'id' ? 'short_answer' : 'multiple_choice');
            $q_text  = trim($q['text'] ?? '');
            if (!$q_text) continue;
            // For MC: options array, correct = index int
            // For TF: answer is 'true'/'false' string → store as '0' (True) or '1' (False)
            // For ID (short_answer): answer is the expected text
            if ($q_type === 'multiple_choice') {
                $q_options = json_encode($q['options'] ?? []);
                $q_correct = (string)(int)($q['correct'] ?? 0);
            } elseif ($q_type === 'true_false') {
                $q_options = json_encode(['True', 'False']);
                $tf_ans    = strtolower($q['answer'] ?? 'true');
                $q_correct = ($tf_ans === 'true') ? '0' : '1'; // 0=True, 1=False
            } else {
                $q_options = json_encode([]);
                $q_correct = trim($q['answer'] ?? '');
            }
            $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_type, question_text, choices, correct_answer, points, sort_order, order_num) VALUES (?,?,?,?,?,1,?,?)")
                ->execute([$quiz_id, $q_type, $q_text, $q_options, $q_correct, $i + 1, $i + 1]);
        }
        // Notify students if published
        if ($status === 'published') {
            $stmt_enr = $pdo->prepare("SELECT student_id FROM enrollments WHERE section_id=? AND status='enrolled'");
            $stmt_enr->execute([$section_id]);
            foreach ($stmt_enr->fetchAll() as $e) {
                $pdo->prepare("INSERT INTO notifications (recipient_id, notification_type, title, message) VALUES (?,?,?,?)")
                    ->execute([$e['student_id'], 'quiz_available', 'New Quiz: '.$title, 'A new quiz has been posted in your course.']);
            }
        }
        echo json_encode(['success'=>true,'quiz_id'=>$quiz_id,'message'=>'Quiz '.$status.'!']);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ===== DASHBOARD STATS FROM DB =====
try {
    // Active courses (published, instructor owns the section) and total unique enrolled students
    $stmt_dash = $pdo->prepare("
        SELECT
            COUNT(DISTINCT cs.id) AS active_courses,
            COALESCE(SUM(CASE WHEN e.status='enrolled' THEN 1 ELSE 0 END), 0) AS total_students
        FROM course_sections cs
        JOIN courses c ON c.id = cs.course_id AND c.status = 'published'
        LEFT JOIN enrollments e ON e.section_id = cs.id
        WHERE cs.instructor_id = ?
    ");
    $stmt_dash->execute([$instructor_user_id]);
    $dash_row = $stmt_dash->fetch();
    $dash_active_courses = (int)($dash_row['active_courses'] ?? count($db_sections));
    $dash_total_students = (int)($dash_row['total_students'] ?? 0);

    // Archived courses count (courses created by instructor that are archived)
    $stmt_archived = $pdo->prepare("
        SELECT COUNT(*) AS archived_count
        FROM courses c
        WHERE c.created_by = ? AND c.status = 'archived'
    ");
    $stmt_archived->execute([$instructor_user_id]);
    $dash_archived_courses = (int)($stmt_archived->fetchColumn() ?? 0);

    // Active discussions — forum threads in the instructor's sections
    $stmt_disc = $pdo->prepare("
        SELECT COUNT(DISTINCT ft.id) AS active_discussions
        FROM forum_threads ft
        JOIN forums f ON f.id = ft.forum_id
        JOIN course_sections cs ON cs.id = f.section_id
        WHERE cs.instructor_id = ? AND ft.is_locked = 0
    ");
    $stmt_disc->execute([$instructor_user_id]);
    $dash_active_discussions = (int)($stmt_disc->fetchColumn() ?? 0);

    // Recent activities from audit_logs for this instructor's data
    $stmt_recent = $pdo->prepare("
        SELECT
            al.action,
            al.entity_type,
            al.entity_id,
            al.detail,
            al.created_at,
            COALESCE(CONCAT(up.first_name,' ',up.last_name), 'System') AS actor_name
        FROM audit_logs al
        LEFT JOIN users u ON u.id = al.user_id
        LEFT JOIN user_profiles up ON up.user_id = al.user_id
        WHERE al.action IN (
            'submission_created','submission_graded',
            'quiz_attempt_started','quiz_attempt_submitted',
            'course_created','course_updated',
            'enrollment_created','quiz_created'
        )
        AND (
            al.user_id = ?
            OR al.entity_id IN (
                SELECT a.id FROM assignments a
                JOIN course_sections cs ON cs.id = a.section_id WHERE cs.instructor_id = ?
            )
            OR al.entity_id IN (
                SELECT cs.id FROM course_sections cs WHERE cs.instructor_id = ?
            )
            OR (al.action = 'submission_created' AND al.entity_id IN (
                SELECT s.id FROM submissions s
                JOIN assignments a ON a.id = s.assignment_id
                JOIN course_sections cs ON cs.id = a.section_id
                WHERE cs.instructor_id = ?
            ))
            OR (al.action IN ('quiz_attempt_started','quiz_attempt_submitted') AND al.entity_id IN (
                SELECT qa.id FROM quiz_attempts qa
                JOIN quizzes q ON q.id = qa.quiz_id
                JOIN course_sections cs ON cs.id = q.section_id
                WHERE cs.instructor_id = ?
            ))
            OR (al.action = 'enrollment_created' AND al.entity_id IN (
                SELECT e.id FROM enrollments e
                JOIN course_sections cs ON cs.id = e.section_id WHERE cs.instructor_id = ?
            ))
        )
        ORDER BY al.created_at DESC
        LIMIT 8
    ");
    $stmt_recent->execute([
        $instructor_user_id,
        $instructor_user_id,
        $instructor_user_id,
        $instructor_user_id,
        $instructor_user_id,
        $instructor_user_id
    ]);
    $dash_recent_activities = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $dash_active_courses      = count($db_sections);
    $dash_total_students      = (int)array_sum(array_column($db_sections, 'enrolled_count'));
    $dash_archived_courses    = 0;
    $dash_active_discussions  = 0;
    $dash_recent_activities   = [];
}

// Build recent activities JS array
$js_recent_activities = [];
foreach ($dash_recent_activities as $act) {
    $detail = json_decode($act['detail'] ?? '{}', true) ?: [];
    $label = '';
    $icon_color = 'rgba(204,58,114,0.14)';
    $icon_char  = '◈';
    $action = $act['action'];
    $actor  = htmlspecialchars($act['actor_name']);
    $time   = $act['created_at'];

    if ($action === 'submission_created') {
        $asgn_id = $detail['assignment_id'] ?? '';
        $label = "<strong>Assignment Submitted</strong> — {$actor}";
        $icon_color = 'rgba(204,58,114,0.14)'; $icon_char = '◈';
    } elseif ($action === 'submission_graded') {
        $score = $detail['score'] ?? '';
        $label = "<strong>Submission Graded</strong> — Student ID {$detail['student_id']} · Score: {$score}";
        $icon_color = 'rgba(74,174,232,0.14)'; $icon_char = '✓';
    } elseif ($action === 'quiz_attempt_started') {
        $label = "<strong>Quiz Attempt Started</strong> — {$actor}";
        $icon_color = 'rgba(224,144,16,0.14)'; $icon_char = '◆';
    } elseif ($action === 'quiz_attempt_submitted') {
        $score = $detail['score'] ?? '';
        $label = "<strong>Quiz Submitted</strong> — {$actor}" . ($score ? " · Score: {$score}" : '');
        $icon_color = 'rgba(74,174,232,0.14)'; $icon_char = '◉';
    } elseif ($action === 'course_created') {
        $title = htmlspecialchars($detail['title'] ?? '');
        $label = "<strong>Course Created</strong> — {$title}";
        $icon_color = 'rgba(74,174,232,0.14)'; $icon_char = '⊕';
    } elseif ($action === 'course_updated') {
        $title = htmlspecialchars($detail['new_title'] ?? $detail['old_title'] ?? '');
        $new_status = $detail['new_status'] ?? '';
        $label = "<strong>Course Updated</strong> — {$title}" . ($new_status ? " · Status: {$new_status}" : '');
        $icon_color = 'rgba(224,144,16,0.14)'; $icon_char = '◆';
    } elseif ($action === 'enrollment_created') {
        $label = "<strong>Student Enrolled</strong> — {$actor}";
        $icon_color = 'rgba(204,58,114,0.14)'; $icon_char = '⊙';
    } elseif ($action === 'quiz_created') {
        $title = htmlspecialchars($detail['title'] ?? '');
        $label = "<strong>Quiz Created</strong> — {$title}";
        $icon_color = 'rgba(74,174,232,0.14)'; $icon_char = '◉';
    } else {
        $label = "<strong>" . ucfirst(str_replace('_', ' ', $action)) . "</strong> — {$actor}";
    }

    $js_recent_activities[] = [
        'label'      => $label,
        'icon_color' => $icon_color,
        'icon_char'  => $icon_char,
        'time'       => $time,
    ];
}
$js_recent_activities_json = json_encode($js_recent_activities);

// ===== LOAD ASSIGNMENTS FROM DB =====
try {
    $stmt_asg = $pdo->prepare("
        SELECT a.id, a.title, a.instructions, a.max_score, a.due_date, a.status, a.section_id,
               c.title AS course_title, c.code AS course_code,
               CONCAT(c.title, ' (', c.code, ')') AS course_label,
               (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS sub_count,
               (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = a.section_id AND e.status='enrolled') AS total_enrolled
        FROM assignments a
        JOIN course_sections cs ON cs.id = a.section_id
        JOIN courses c ON c.id = cs.course_id
        WHERE cs.instructor_id = ?
        ORDER BY a.due_date DESC
        LIMIT 30
    ");
    $stmt_asg->execute([$instructor_user_id]);
    $db_asg_raw = $stmt_asg->fetchAll();
    $js_assignments = array_map(function($a) {
        return [
            'id'         => (int)$a['id'],
            'title'      => $a['title'],
            'instructions' => $a['instructions'] ?? '',
            'submissionType' => 'file',
            'course'     => $a['course_label'],
            'due'        => $a['due_date'] ? date('M j, Y', strtotime($a['due_date'])) : 'TBD',
            'status'     => $a['status'] === 'published' ? 'published' : ($a['status'] === 'draft' ? 'draft' : 'closed'),
            'submissions'=> (int)$a['sub_count'],
            'total'      => (int)$a['total_enrolled'],
            'points'     => (int)($a['max_score'] ?? 100),
            'section_id' => (int)$a['section_id'],
            'due_raw'    => $a['due_date'] ? date('Y-m-d', strtotime($a['due_date'])) : '',
        ];
    }, $db_asg_raw);
} catch (Exception $e) { $js_assignments = []; }
$js_assignments_json = json_encode($js_assignments, JSON_UNESCAPED_UNICODE);

// ===== LOAD SUBMISSIONS FROM DB =====
try {
    $stmt_sub = $pdo->prepare("
        SELECT s.id, s.assignment_id, s.student_id, s.status, s.score, s.feedback,
               s.submitted_at, s.is_late,
               CONCAT(up.first_name, ' ', up.last_name) AS student_name,
               a.title AS assignment_title, a.max_score,
               CONCAT(c.title, ' (', c.code, ')') AS course_label
        FROM submissions s
        JOIN user_profiles up ON up.user_id = s.student_id
        JOIN assignments a ON a.id = s.assignment_id
        JOIN course_sections cs ON cs.id = a.section_id
        JOIN courses c ON c.id = cs.course_id
        WHERE cs.instructor_id = ?
        ORDER BY s.submitted_at DESC
        LIMIT 60
    ");
    $stmt_sub->execute([$instructor_user_id]);
    $js_submissions = array_map(function($s) {
        $name = $s['student_name'];
        $words = explode(' ', $name);
        $ini = '';
        foreach ($words as $w) { if (isset($w[0])) $ini .= strtoupper($w[0]); }
        $ini = substr($ini, 0, 2);
        $maxScore  = (int)($s['max_score'] ?? 100);
        $gradeStr  = ($s['score'] !== null) ? $s['score'] . '/' . $maxScore : '—';
        $isLate    = (bool)$s['is_late'];
        $rawStatus = $s['status'];
        if ($rawStatus === 'graded')    $dispStatus = 'Graded';
        elseif ($isLate)               $dispStatus = 'Late';
        else                           $dispStatus = 'Submitted';
        $submitted = $s['submitted_at'] ? date('M j, g:i A', strtotime($s['submitted_at'])) : '—';
        return [
            'id'         => (int)$s['id'],
            'initials'   => $ini,
            'name'       => $name,
            'assignment' => $s['assignment_title'],
            'course'     => $s['course_label'],
            'submitted'  => $submitted,
            'status'     => $dispStatus,
            'grade'      => $gradeStr,
            'file'       => null,
            'comment'    => $s['feedback'] ?? '',
        ];
    }, $stmt_sub->fetchAll());
} catch (Exception $e) { $js_submissions = []; }
$js_submissions_json = json_encode($js_submissions, JSON_UNESCAPED_UNICODE);

// ===== LOAD QUIZZES + QUIZ RESULTS FROM DB =====
try {
    $stmt_qz = $pdo->prepare("
        SELECT q.id, q.title, q.time_limit_min, q.max_score, q.status, q.section_id,
               CONCAT(c.title, ' (', c.code, ')') AS course_label,
               (SELECT COUNT(DISTINCT qa.student_id) FROM quiz_attempts qa WHERE qa.quiz_id=q.id AND qa.status IN ('submitted','graded')) AS attempt_count,
               (SELECT COUNT(*) FROM enrollments e WHERE e.section_id=q.section_id AND e.status='enrolled') AS enrolled_count,
               (SELECT ROUND(AVG(qa.score),0) FROM quiz_attempts qa WHERE qa.quiz_id=q.id AND qa.status IN ('submitted','graded')) AS avg_score,
               (SELECT COUNT(*) FROM quiz_questions qst WHERE qst.quiz_id=q.id) AS question_count
        FROM quizzes q
        JOIN course_sections cs ON cs.id = q.section_id
        JOIN courses c ON c.id = cs.course_id
        WHERE cs.instructor_id = ?
        ORDER BY q.created_at DESC
    ");
    $stmt_qz->execute([$instructor_user_id]);
    $db_qz_raw = $stmt_qz->fetchAll();
    $js_quizzes = array_map(function($q) {
        $tl = (int)($q['time_limit_min'] ?? 30);
        return [
            'id'         => (int)$q['id'],
            'title'      => $q['title'],
            'course'     => $q['course_label'],
            'questions'  => (int)($q['question_count'] ?? 10),
            'timeLimit'  => $tl . ' min',
            'status'     => $q['status'] ?? 'draft',
            'attempts'   => (int)($q['attempt_count'] ?? 0),
            'total'      => (int)($q['enrolled_count'] ?? 0),
            'avgScore'   => (int)($q['avg_score'] ?? 0),
            'section_id' => (int)$q['section_id'],
        ];
    }, $db_qz_raw);
    $js_quiz_results = [];
    foreach ($db_qz_raw as $qz) {
        $stmt_qa = $pdo->prepare("
            SELECT qa.score, qa.time_taken_sec, qa.status AS attempt_status,
                   CONCAT(up.first_name, ' ', up.last_name) AS student_name
            FROM quiz_attempts qa
            JOIN user_profiles up ON up.user_id = qa.student_id
            WHERE qa.quiz_id = ?
            ORDER BY qa.score DESC
            LIMIT 30
        ");
        $stmt_qa->execute([$qz['id']]);
        $snap = [];
        foreach ($stmt_qa->fetchAll() as $r) {
            $n = $r['student_name'];
            $ws = explode(' ', $n); $ini = '';
            foreach ($ws as $w) { if (isset($w[0])) $ini .= strtoupper($w[0]); }
            $ini = substr($ini, 0, 2);
            $timeTaken = (($r['time_taken_sec'] ?? 0) > 0) ? (int)ceil($r['time_taken_sec']/60) . ' min' : '—';
            $snap[] = [
                'initials' => $ini,
                'name'     => $n,
                'score'    => (int)($r['score'] ?? 0),
                'total'    => (int)($qz['max_score'] ?? 100),
                'time'     => $timeTaken,
                'status'   => in_array($r['attempt_status'], ['submitted','graded']) ? 'Completed' : 'Missing',
            ];
        }
        $js_quiz_results[(int)$qz['id']] = $snap;
    }
} catch (Exception $e) { $js_quizzes = []; $js_quiz_results = []; }
$js_quizzes_json      = json_encode($js_quizzes,      JSON_UNESCAPED_UNICODE);
$js_quiz_results_json = json_encode($js_quiz_results, JSON_UNESCAPED_UNICODE);

// ===== LOAD DISCUSSIONS FROM DB =====
try {
    $stmt_disc = $pdo->prepare("
        SELECT ft.id, ft.title, ft.body AS description, ft.created_at, ft.is_pinned,
               ft.view_count, ft.forum_id,
               CONCAT(up.first_name, ' ', up.last_name) AS author,
               up.user_id AS author_id,
               CONCAT(c.title, ' (', c.code, ')') AS course_label
        FROM forum_threads ft
        JOIN forums f ON f.id = ft.forum_id
        JOIN course_sections cs ON cs.id = f.section_id
        JOIN courses c ON c.id = cs.course_id
        JOIN user_profiles up ON up.user_id = ft.author_id
        WHERE cs.instructor_id = ?
        ORDER BY ft.created_at DESC
        LIMIT 30
    ");
    $stmt_disc->execute([$instructor_user_id]);
    $db_disc_raw = $stmt_disc->fetchAll();
    $js_discussions = [];
    foreach ($db_disc_raw as $d) {
        $auth = $d['author'];
        $ws = explode(' ', $auth); $ini = '';
        foreach ($ws as $w) { if (isset($w[0])) $ini .= strtoupper($w[0]); }
        $ini = substr($ini, 0, 2);
        $stmt_rep = $pdo->prepare("
            SELECT fr.id, fr.body, fr.created_at, fr.upvotes AS like_count,
                   fr.author_id,
                   CONCAT(up.first_name, ' ', up.last_name) AS author
            FROM forum_replies fr
            JOIN user_profiles up ON up.user_id = fr.author_id
            WHERE fr.thread_id = ?
            ORDER BY fr.created_at ASC
        ");
        $stmt_rep->execute([$d['id']]);
        $replies = [];
        foreach ($stmt_rep->fetchAll() as $r) {
            $rn = $r['author']; $rws = explode(' ', $rn); $ri = '';
            foreach ($rws as $w) { if (isset($w[0])) $ri .= strtoupper($w[0]); }
            $ri = substr($ri, 0, 2);
            $replies[] = [
                'id'            => (int)$r['id'],
                'author'        => $rn,
                'authorInitial' => $ri,
                'authorId'      => (int)$r['author_id'],
                'time'          => $r['created_at'],
                'body'          => $r['body'],
                'likes'         => (int)($r['like_count'] ?? 0),
                'liked'         => false,
            ];
        }
        $isInstr = ((int)$d['author_id'] === (int)$instructor_user_id);
        $js_discussions[] = [
            'id'            => (int)$d['id'],
            'title'         => $d['title'],
            'author'        => $isInstr ? 'You (Prof. ' . $instructor_name . ')' : $auth,
            'authorInitial' => $ini,
            'authorId'      => (int)$d['author_id'],
            'course'        => $d['course_label'],
            'createdAt'     => $d['created_at'],
            'description'   => $d['description'],
            'replies'       => $replies,
        ];
    }
} catch (Exception $e) { $js_discussions = []; }
$js_discussions_json = json_encode($js_discussions, JSON_UNESCAPED_UNICODE);
$current_user_id = (int)$instructor_user_id;

// ===== LOAD ANNOUNCEMENTS FROM DB (keyed by section_id) =====
try {
    $stmt_ann = $pdo->prepare("
        SELECT ann.id, ann.title, ann.body, ann.is_pinned, ann.published_at, ann.scope_id AS section_id
        FROM announcements ann
        WHERE ann.author_id = ? AND ann.scope = 'section'
        ORDER BY ann.is_pinned DESC, ann.published_at DESC
    ");
    $stmt_ann->execute([$instructor_user_id]);
    $js_announcements = [];
    foreach ($stmt_ann->fetchAll() as $ann) {
        $sid = (int)$ann['section_id'];
        if (!isset($js_announcements[$sid])) $js_announcements[$sid] = [];
        $js_announcements[$sid][] = [
            'id'     => (int)$ann['id'],
            'title'  => $ann['title'],
            'body'   => $ann['body'],
            'author' => 'Prof. ' . $instructor_name,
            'date'   => $ann['published_at'],
            'pinned' => (bool)$ann['is_pinned'],
        ];
    }
} catch (Exception $e) { $js_announcements = []; }
$js_announcements_json = json_encode($js_announcements, JSON_UNESCAPED_UNICODE);

// ===== LOAD ADMIN ANNOUNCEMENTS (platform-wide + user-specific) =====
$js_admin_announcements = [];
try {
    $stmt_adm = $pdo->prepare("
        SELECT a.id, a.title, a.body, a.is_pinned, a.created_at,
               COALESCE(up.display_name, CONCAT(up.first_name,' ',up.last_name), u.email) AS author_name
        FROM announcements a
        JOIN users u ON u.id = a.author_id
        LEFT JOIN user_profiles up ON up.user_id = a.author_id
        LEFT JOIN announcement_reads ar
               ON ar.announcement_id = a.id AND ar.user_id = ?
        WHERE (
                (a.scope = 'platform'
                 AND NOT EXISTS (SELECT 1 FROM announcement_targets at2
                                 WHERE at2.announcement_id = a.id))
             OR EXISTS (SELECT 1 FROM announcement_targets at2
                        WHERE at2.announcement_id = a.id AND at2.user_id = ?)
              )
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND ar.announcement_id IS NULL
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 20
    ");
    $stmt_adm->execute([$instructor_user_id, $instructor_user_id]);
    foreach ($stmt_adm->fetchAll() as $ann) {
        $body = $ann['body'];
        $priority = 'normal';
        if (preg_match('/^\[PRIORITY:(normal|important|urgent)\]/', $body, $m)) {
            $priority = $m[1];
            $body = substr($body, strlen($m[0]));
        }
        $js_admin_announcements[] = [
            'id'       => (int)$ann['id'],
            'title'    => $ann['title'],
            'body'     => $body,
            'priority' => $priority,
            'pinned'   => (bool)$ann['is_pinned'],
            'author'   => $ann['author_name'] ?: 'Admin',
            'date'     => $ann['created_at'],
        ];
    }
} catch (Exception $e) {}
$js_admin_announcements_json = json_encode($js_admin_announcements, JSON_UNESCAPED_UNICODE);

// ===== LOAD NOTIFICATIONS FROM DB =====
try {
    $stmt_notif = $pdo->prepare("
        SELECT n.id, n.notification_type AS type, n.title, n.message, n.is_read, n.created_at
        FROM notifications n
        WHERE n.recipient_id = ?
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt_notif->execute([$instructor_user_id]);
    $type_icon = ['submission'=>'📥','discussion'=>'🗣️','enrollment'=>'👥','quiz'=>'🧪','assignment'=>'📋','system'=>'🔔'];
    $type_bg   = [
        'submission'=>'rgba(204,58,114,0.14)','discussion'=>'rgba(224,144,16,0.14)',
        'enrollment'=>'rgba(74,174,232,0.14)','quiz'=>'rgba(204,58,114,0.14)',
        'assignment'=>'rgba(74,174,232,0.14)','system'=>'rgba(74,174,232,0.14)',
    ];
    $js_notifications = array_map(function($n) use ($type_icon, $type_bg) {
        $type = $n['type'] ?? 'system';
        $diff = time() - strtotime($n['created_at']);
        if ($diff < 3600)       $ts = max(1,(int)round($diff/60)) . ' min ago';
        elseif ($diff < 86400)  $ts = (int)round($diff/3600) . ' hrs ago';
        elseif ($diff < 172800) $ts = 'Yesterday';
        else                    $ts = (int)round($diff/86400) . ' days ago';
        return [
            'icon'  => $type_icon[$type] ?? '🔔',
            'bg'    => $type_bg[$type]   ?? 'rgba(74,174,232,0.14)',
            'title' => $n['title'],
            'time'  => $ts,
            'unread'=> !(bool)$n['is_read'],
            'type'  => $type,
        ];
    }, $stmt_notif->fetchAll());
} catch (Exception $e) { $js_notifications = []; }
$js_notifications_json = json_encode($js_notifications, JSON_UNESCAPED_UNICODE);

// ===== LOAD STUDENT ROSTERS FROM DB (keyed by course_code) =====
try {
    $js_rosters = [];
    foreach ($db_sections as $sec) {
        if ($sec['course_status'] === 'archived') continue;
        $code = $sec['course_code'];
        $stmt_stu = $pdo->prepare("
            SELECT CONCAT(up.first_name, ' ', up.last_name) AS student_name
            FROM enrollments e
            JOIN user_profiles up ON up.user_id = e.student_id
            WHERE e.section_id = ? AND e.status = 'enrolled'
            ORDER BY up.last_name ASC, up.first_name ASC
        ");
        $stmt_stu->execute([$sec['section_id']]);
        $js_rosters[$code] = array_column($stmt_stu->fetchAll(), 'student_name');
    }
} catch (Exception $e) { $js_rosters = []; }
$js_rosters_json = json_encode($js_rosters, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnFlow – Instructor Demo</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #CC3A72;
  --primary-light: #FAE0EB;
  --primary-dark: #a82860;
  --secondary: #4AAEE8;
  --accent: #E09010;
  --danger: #D84040;
  --purple: #4AAEE8;
  --bg: #FDF0F5;
  --surface: #FFFFFF;
  --surface-2: #F8E4EF;
  --border: #F0C0D8;
  --text: #2a0e1c;
  --text-2: #7a3a58;  
  --text-3: #c090a8;
  --shadow: 0 2px 12px rgba(204,58,114,0.10);
  --shadow-md: 0 4px 24px rgba(204,58,114,0.18);
  --radius: 12px;
  --radius-sm: 8px;
  --sidebar-w: 240px;
}
[data-theme="dark"] {
  --primary: #E8608A;
  --primary-light: #2e1f2a;
  --primary-dark: #c43f68;
  --secondary: #60B8E8;
  --accent: #E8A84A;
  --danger: #E06868;
  --purple: #60B8E8;

  --bg: #16161f;
  --surface: #1f1f2e;
  --surface-2: #272738;
  --border: #343450;
  --text: #eceaf6;
  --text-2: #9e96bc;
  --text-3: #565070;
  --shadow: 0 2px 14px rgba(0,0,0,0.38);
  --shadow-md: 0 4px 28px rgba(0,0,0,0.50);
}

*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.6;transition:background .3s,color .3s}
button{cursor:pointer;font-family:inherit;border:none;outline:none}
input,textarea,select{font-family:inherit;outline:none}
.app-shell{display:flex;min-height:100vh}
.sidebar{
  width:var(--sidebar-w);height:100vh;background:var(--surface);
  border-right:1px solid var(--border);display:flex;flex-direction:column;
  position:fixed;left:0;top:0;z-index:100;transition:.3s;overflow:hidden;
}
#navMenu{flex:1;overflow-y:auto;overflow-x:hidden;}
.sidebar.collapsed{width:64px}
.sidebar-header{flex-shrink:0;padding:18px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border)}
.sidebar-brand{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;white-space:nowrap;overflow:hidden;transition:.3s}
.sidebar.collapsed .sidebar-brand,.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}
.sidebar.collapsed .sidebar-brand{display:none}
.nav-item{
  display:flex;align-items:center;gap:12px;padding:9px 16px;
  cursor:pointer;transition:.15s;color:var(--text-2);position:relative;margin:1px 8px;border-radius:var(--radius-sm);
  white-space:nowrap;
}
.nav-item:hover{background:var(--surface-2);color:var(--text)}
.nav-item.active{background:var(--primary-light);color:var(--primary);font-weight:600}
.nav-item.active::before{content:'';position:absolute;left:-8px;top:50%;transform:translateY(-50%);width:3px;height:60%;background:var(--primary);border-radius:2px}
.nav-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center;opacity:.85}
.nav-item.active .nav-icon{opacity:1}
.nav-label{font-size:13px;font-weight:500;transition:.3s}
.nav-badge{margin-left:auto;background:var(--danger);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px}
.sidebar-footer{flex-shrink:0;padding:10px 12px;border-top:1px solid var(--border);display:flex;align-items:center;gap:8px}
.sidebar-footer .logout-btn{flex-shrink:0;width:32px;height:32px;border-radius:var(--radius-sm);background:rgba(216,64,64,0.09);color:var(--danger);border:1.5px solid rgba(216,64,64,0.18);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s}
.sidebar-footer .logout-btn:hover{background:rgba(216,64,64,0.18)}
.user-card{display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0}
.user-info{overflow:hidden}
.user-name{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-role{font-size:11px;color:var(--text-3)}
.sidebar.collapsed .user-info{display:none}
.topbar{height:60px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:12px;position:sticky;top:0;z-index:50;}
.topbar-left{display:flex;align-items:center;gap:12px;flex:1}
.topbar-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--text)}
.topbar-right{display:flex;align-items:center;gap:8px}
.icon-btn{width:36px;height:36px;border-radius:var(--radius-sm);background:var(--surface-2);display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;border:1px solid var(--border);transition:.2s;color:var(--text-2);}
.icon-btn:hover{background:var(--primary-light);color:var(--primary);border-color:var(--primary)}
.notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:var(--danger);border:2px solid var(--surface)}
.main-content{margin-left:var(--sidebar-w);flex:1;transition:.3s;min-height:100vh;display:flex;flex-direction:column}
.main-content.expanded{margin-left:64px}
.content-area{padding:24px;flex:1}
.page-header{margin-bottom:24px}
.page-header h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:700}
.page-header p{color:var(--text-2);font-size:13px;margin-top:2px}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-3);margin-bottom:6px}
.breadcrumb span:last-child{color:var(--text)}

.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow)}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:24px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:20px 20px 18px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:14px;
  transition:all .2s ease;cursor:pointer;position:relative;overflow:hidden;
}
.stat-card::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,var(--primary),var(--primary-dark));
  opacity:0;transition:.2s;
}
.stat-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:var(--border-strong)}
.stat-card:hover::after{opacity:1}
.stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-icon.blue{background:rgba(58,159,216,0.12);color:#1a72a8}
.stat-icon.amber{background:rgba(212,130,10,0.12);color:#9a5800}
.stat-icon.pink{background:rgba(196,48,94,0.12);color:var(--primary-dark)}
.stat-icon.green{background:rgba(34,160,80,0.12);color:#1a7a40}
[data-theme="dark"] .stat-icon.blue{color:var(--secondary)}
[data-theme="dark"] .stat-icon.amber{color:var(--accent)}
[data-theme="dark"] .stat-icon.pink{color:var(--primary)}
[data-theme="dark"] .stat-icon.green{color:#4ace80}
.stat-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;line-height:1}
.stat-label{font-size:12px;color:var(--text-2);margin-top:4px;font-weight:500}
.stat-trend{font-size:11px;margin-top:5px;font-weight:600}
.stat-trend.up{color:#1a72a8}
.stat-trend.down{color:var(--danger)}
[data-theme="dark"] .stat-trend.up{color:var(--secondary)}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text)}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--surface-2)}
.avatar-sm{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.2px}
.badge-blue{background:rgba(74,174,232,0.15);color:#1a7cbf}
.badge-green{background:rgba(74,174,232,0.15);color:#1a7cbf}
.badge-amber{background:rgba(224,144,16,0.15);color:#9a6000}
.badge-red{background:rgba(216,64,64,0.15);color:#aa2020}
.badge-gray{background:var(--surface-2);color:var(--text-2)}
.badge-purple{background:rgba(204,58,114,0.15);color:#a82860}
.badge-pink{background:rgba(204,58,114,0.15);color:#a82860}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 20px;border-radius:var(--radius-sm);font-size:14px;font-weight:600;transition:.2s;cursor:pointer;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 4px 12px rgba(204,58,114,0.35)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(204,58,114,0.42)}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text-2)}
.btn-outline:hover{border-color:var(--primary);color:var(--primary)}
.btn-ghost{background:transparent;color:var(--text-2);padding:8px 12px}
.btn-ghost:hover{background:var(--surface-2);color:var(--text)}
.btn-danger{background:rgba(216,64,64,0.1);color:var(--danger);border:none}
.btn-sm{padding:7px 14px;font-size:12px}
.btn-full{width:100%}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text-2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.input-wrap{position:relative}
.input-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:16px;pointer-events:none}
.input-wrap input{width:100%;padding:11px 13px 11px 38px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:14px;transition:.2s;}
.input-wrap input:focus{border-color:var(--primary);background:var(--surface)}
select{padding:9px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;cursor:pointer;}
select:focus{border-color:var(--primary)}
.progress-bar{height:5px;border-radius:3px;background:var(--surface-2);overflow:hidden}
.progress-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--primary),var(--primary-dark));transition:.6s}
.activity-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
.activity-item:last-child{border-bottom:none}
.act-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.act-body{flex:1}
.act-text{font-size:13px}.act-time{font-size:11px;color:var(--text-3);margin-top:2px}
.audit-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.audit-row:last-child{border-bottom:none}
.audit-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.search-wrap{position:relative;flex:1;max-width:300px}
.search-wrap input{width:100%;padding:9px 12px 9px 36px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;}
.search-wrap::before{content:'';position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none}
.upload-zone{border:2px dashed var(--border);border-radius:var(--radius);padding:24px;text-align:center;cursor:pointer;transition:.2s;background:var(--surface-2);}
.upload-zone:hover{border-color:var(--primary);background:var(--primary-light)}
.notif-panel{position:absolute;top:48px;right:0;width:320px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);display:none;z-index:200}
.notif-panel.open{display:block}
.notif-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.notif-header h3{font-size:14px;font-weight:700}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);transition:.15s}
.notif-item:hover{background:var(--surface-2)}
.notif-item.unread{background:var(--primary-light)}
.notif-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.notif-text{font-size:12px;color:var(--text-2);margin-top:2px}
.notif-time{font-size:11px;color:var(--text-3);margin-top:4px}
.auth-link{color:var(--primary);font-weight:600;cursor:pointer}
.chat-layout{display:flex;flex-direction:column;height:calc(100vh - 160px)}
.chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:14px}
.chat-bubble{max-width:75%;padding:12px 16px;border-radius:var(--radius);font-size:13px;line-height:1.6}
.chat-bubble.user{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.chat-bubble.ai{background:var(--surface);border:1px solid var(--border);align-self:flex-start;border-bottom-left-radius:4px}
.chat-meta{font-size:11px;color:var(--text-3);margin-top:4px;display:flex;align-items:center;gap:4px}
.chat-input-area{padding:14px;border-top:1px solid var(--border);display:flex;align-items:flex-end;gap:10px;background:var(--surface);}
.chat-input{flex:1;padding:11px 14px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;resize:none;max-height:120px;transition:.2s;}
.chat-input:focus{border-color:var(--primary);background:var(--surface)}
.chat-send{width:40px;height:40px;border-radius:10px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;transition:.2s;flex-shrink:0}
.chat-send:hover{background:var(--primary-dark);transform:scale(1.05)}
.ai-typing{display:flex;gap:4px;align-items:center;padding:4px 0}
.typing-dot{width:7px;height:7px;border-radius:50%;background:var(--text-3);animation:blink 1.2s infinite}
.typing-dot:nth-child(2){animation-delay:.2s}.typing-dot:nth-child(3){animation-delay:.4s}
@keyframes blink{0%,80%,100%{opacity:.3}40%{opacity:1}}
.setting-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border);}
.setting-row:last-child{border-bottom:none}
.setting-info h4{font-size:14px;font-weight:600}
.setting-info p{font-size:12px;color:var(--text-2);margin-top:2px}
.toggle{position:relative;width:44px;height:24px}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:12px;cursor:pointer;transition:.3s}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s;}
.toggle input:checked + .toggle-slider{background:var(--primary)}
.toggle input:checked + .toggle-slider::before{transform:translateX(20px)}
.discussion-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:12px;cursor:pointer;transition:.2s;box-shadow:var(--shadow)}
.discussion-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);border-color:var(--primary)}
.discussion-reply{background:var(--surface-2);border-radius:var(--radius-sm);padding:14px;margin-top:10px;border-left:3px solid var(--primary)}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:300;display:none;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:500px;box-shadow:var(--shadow-md)}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.modal-header h2{font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
.modal-footer{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--text);color:#fff;padding:12px 22px;border-radius:50px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.18);transition:transform .3s ease,opacity .3s ease;opacity:0;pointer-events:none;white-space:nowrap;z-index:999}
.toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.toast.success{background:var(--primary)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.mobile-open{transform:translateX(0)}
  .main-content{margin-left:0!important}
  .sidebar-overlay{display:block;opacity:0;pointer-events:none;transition:.3s}
  .sidebar-overlay.open{opacity:1;pointer-events:all}
  .stats-grid{grid-template-columns:1fr 1fr}
  .grid-2{grid-template-columns:1fr}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .content-area{padding:16px}
}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-in{animation:fadeUp .3s ease}
.discussions-container{display:grid;grid-template-columns:350px 1fr;gap:24px;height:calc(100vh - 160px)}
.discussions-list-panel{background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden}
.discussions-header{padding:16px;border-bottom:1px solid var(--border)}
.discussions-search{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;transition:border-color .2s}
.discussions-search:focus{outline:none;border-color:var(--primary)}
.discussions-list{flex:1;overflow-y:auto;padding:8px}
.discussions-list::-webkit-scrollbar{width:5px}
.discussions-list::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.discussion-item{padding:12px;margin-bottom:8px;border-radius:var(--radius-sm);background:var(--surface-2);border:1.5px solid transparent;cursor:pointer;transition:.2s}
.discussion-item:hover{background:var(--primary-light);border-color:var(--primary)}
.discussion-item.active{background:var(--primary-light);border-color:var(--primary)}
.discussion-item-title{font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.discussion-item-meta{font-size:12px;color:var(--text-2);display:flex;justify-content:space-between;align-items:center;gap:8px}
.discussion-count{display:inline-flex;align-items:center;gap:4px;background:var(--primary-light);color:var(--primary);padding:2px 6px;border-radius:4px;font-size:11px;font-weight:700}
.course-badge-disc{display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:3px 8px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.course-filter-btn{display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:12px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit}
.course-filter-btn:hover{border-color:var(--primary);color:var(--primary)}
.course-filter-btn.active{background:linear-gradient(135deg,#667eea,#764ba2);border-color:transparent;color:#fff}
.course-filter-chip{display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:12px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit}
.course-filter-chip:hover{border-color:var(--primary);color:var(--primary)}
.course-filter-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.notif-filter-chip{display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:12px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit}
.notif-filter-chip:hover{border-color:var(--primary);color:var(--primary)}
.notif-filter-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.rec-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:11px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit;line-height:1.5}
.rec-chip:hover{border-color:var(--primary);color:var(--primary)}
.rec-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.new-disc-btn{margin:12px;padding:10px;background:var(--primary);color:#fff;border-radius:var(--radius-sm);font-size:13px;font-weight:600;text-align:center;cursor:pointer;transition:.2s;border:none;font-family:inherit}
.new-disc-btn:hover{background:var(--primary-dark);transform:translateY(-1px)}
.discussion-detail-panel{background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden}
.discussion-detail-header{padding:20px;border-bottom:1px solid var(--border)}
.discussion-detail-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:8px}
.discussion-detail-meta{display:flex;align-items:center;gap:16px;font-size:13px;color:var(--text-2);flex-wrap:wrap}
.discussion-detail-content{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column}
.discussion-detail-content::-webkit-scrollbar{width:5px}
.discussion-detail-content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.discussion-original-post{margin-bottom:24px;padding:16px;background:var(--primary-light);border:2px solid var(--primary);border-radius:var(--radius)}
.post-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.post-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.post-info{flex:1;min-width:0}
.post-author{font-weight:700;font-size:14px;color:var(--primary);display:flex;align-items:center;gap:8px}
.post-author::after{content:'Original Post';font-size:10px;font-weight:700;background:var(--primary);color:#fff;padding:2px 7px;border-radius:4px}
.post-time{font-size:12px;color:var(--text-3);margin-top:2px}
.post-body{font-size:13px;color:var(--text-2);line-height:1.6;margin-top:12px;white-space:pre-line}
.post-actions{display:flex;gap:10px;margin-top:12px}
.post-action-btn{display:flex;align-items:center;gap:6px;padding:7px 12px;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text-2);font-size:12px;font-weight:500;cursor:pointer;transition:.2s;font-family:inherit}
.post-action-btn:hover{color:var(--primary);border-color:var(--primary)}
.post-action-btn.liked{background:rgba(220,32,32,0.1);color:#dc2020;border-color:#dc2020}
.main-post-reply-composer{margin-top:12px;padding-top:12px;border-top:1px solid var(--border);display:none}
.main-post-reply-composer.active{display:block}
.replies-section{flex:1;margin-bottom:20px}
.reply-item{margin-bottom:14px;padding:12px 16px;background:var(--surface-2);border-left:3px solid var(--primary);border-radius:var(--radius-sm);animation:fadeUp .25s ease}
.reply-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.reply-avatar{width:30px;height:30px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0}
.reply-author{font-weight:600;font-size:13px;color:var(--text)}
.reply-time{font-size:11px;color:var(--text-3);margin-left:auto}
.reply-body{font-size:13px;color:var(--text-2);line-height:1.5;margin-left:40px;margin-bottom:8px;white-space:pre-line}
.reply-actions{display:flex;gap:10px;margin-left:40px}
.reply-action-btn{display:flex;align-items:center;gap:4px;padding:4px 8px;background:none;border:none;color:var(--text-3);font-size:11px;cursor:pointer;transition:.15s;font-family:inherit}
.reply-action-btn:hover{color:var(--primary)}
.reply-action-btn.liked{color:#dc2020}
.discussion-composer{padding:10px 12px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:6px;background:var(--surface)}
.composer-label{font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px}
.composer-textarea{width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;resize:none;min-height:45px;transition:border-color .2s}
.composer-textarea:focus{outline:none;border-color:var(--primary)}
.composer-actions{display:flex;justify-content:flex-end;gap:8px}
.disc-empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;text-align:center;color:var(--text-3)}
.disc-empty-icon{font-size:44px;margin-bottom:12px;opacity:.5}

/* New Discussion Modal */
.disc-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:400;display:none;align-items:center;justify-content:center;padding:20px}
.disc-modal-overlay.open{display:flex}
.disc-modal{background:var(--surface);border-radius:16px;padding:26px;width:100%;max-width:480px;box-shadow:var(--shadow-md);animation:fadeUp .25s ease}
.disc-modal-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:18px}
.disc-form-group{display:flex;flex-direction:column;margin-bottom:14px}
.disc-form-group label{font-size:12px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.disc-form-group input,.disc-form-group textarea,.disc-form-group select{padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;transition:border-color .2s}
.disc-form-group input:focus,.disc-form-group textarea:focus,.disc-form-group select:focus{outline:none;border-color:var(--primary)}
.disc-form-group textarea{resize:vertical;min-height:90px}
.disc-modal-actions{display:flex;gap:8px;margin-top:16px}
.disc-modal-actions button{flex:1;padding:10px;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:.2s}
.ai-context-btn{width:40px;height:40px;border-radius:10px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;border:1px solid var(--border);transition:.2s;flex-shrink:0;color:var(--text-2)}
.ai-context-btn:hover{background:var(--primary-light);color:var(--primary);border-color:var(--primary)}
.ai-context-modal{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:400;display:none;align-items:center;justify-content:center;padding:20px}
.ai-context-modal.open{display:flex}
.ai-context-modal-content{background:var(--surface);border-radius:16px;padding:26px;width:100%;max-width:320px;box-shadow:var(--shadow-md);animation:fadeUp .25s ease}
.ai-context-modal-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:16px}
.ai-context-list{display:flex;flex-direction:column;gap:8px}
.ai-context-item{padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);cursor:pointer;font-size:13px;transition:.2s;font-family:inherit}
.ai-context-item:hover{border-color:var(--primary);background:var(--primary-light);color:var(--primary)}
.ai-context-item.active{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-color:var(--primary-dark)}
.ai-context-badge{display:inline-block;background:var(--primary);color:#fff;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;margin-left:auto}

/* ═══════════════════════════════════════
   NEW ANALYTICS TAB
═══════════════════════════════════════ */
.analytics-section-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:4px}
.analytics-section-sub{font-size:13px;color:var(--text-2);margin-bottom:20px}
.analytics-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
@media(max-width:1100px){.analytics-grid-4{grid-template-columns:repeat(2,1fr)}}
@media(max-width:700px){.analytics-grid-4{grid-template-columns:1fr}}
.analytics-stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;box-shadow:var(--shadow);transition:.2s}
.analytics-stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.analytics-stat-label{font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}
.analytics-stat-value{font-family:'Syne',sans-serif;font-size:30px;letter-spacing:-.03em;color:var(--text);line-height:1;margin-bottom:6px}
.analytics-stat-change{font-size:12px;color:var(--text-2);font-weight:500}
.analytics-stat-change.up{color:#22C55E}
.analytics-stat-change.down{color:#EF4444}
.analytics-chip-row{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.analytics-chip{display:inline-flex;align-items:center;padding:6px 14px;border-radius:20px;background:var(--surface-2);border:1px solid var(--border);cursor:pointer;font-size:12px;font-weight:500;color:var(--text-2);transition:.2s;font-family:inherit;white-space:nowrap}
.analytics-chip:hover{border-color:var(--primary);color:var(--primary)}
.analytics-chip.active{background:var(--primary-light);border-color:var(--primary);color:var(--primary)}
@media(max-width:768px){.discussions-container{grid-template-columns:1fr}.discussions-list-panel{display:none}}

/* ===== LOGIN PAGE ===== */
.login-page{position:fixed;inset:0;background:radial-gradient(ellipse at 10% 90%, rgba(204,58,114,0.18) 0%, transparent 45%),radial-gradient(ellipse at 90% 10%, rgba(74,174,232,0.14) 0%, transparent 45%),var(--bg);display:flex;align-items:center;justify-content:center;z-index:1000;padding:32px 20px;display:none}
.login-page.active{display:flex}
.login-card{background:var(--surface);border-radius:24px;padding:44px 40px;width:100%;max-width:440px;box-shadow:var(--shadow-md);border:1px solid var(--border);animation:fadeUp .45s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
.login-brand{display:flex;align-items:center;gap:11px;margin-bottom:32px}
.login-brand-icon{width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 4px 12px rgba(204,58,114,0.3)}
.login-brand-name{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--text)}
.login-brand-name span{color:var(--primary)}
.login-tab-row{display:flex;background:var(--surface-2);border-radius:var(--radius-sm);padding:4px;margin-bottom:28px;gap:4px}
.login-tab-btn{flex:1;padding:9px;border-radius:7px;border:none;background:transparent;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;color:var(--text-2);cursor:pointer;transition:.2s}
.login-tab-btn.active{background:var(--surface);color:var(--primary);box-shadow:var(--shadow)}
.login-panel{display:none}
.login-panel.active{display:block;animation:fadeUp .3s ease}
.login-title{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;margin-bottom:5px}
.login-sub{color:var(--text-2);font-size:13px;margin-bottom:24px}
.login-form-group{margin-bottom:15px}
.login-form-label{display:block;font-size:11px;font-weight:700;color:var(--text-2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px}
.login-input-wrap{position:relative}
.login-input-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none;opacity:.7}
.login-input-wrap input{width:100%;padding:11px 13px 11px 40px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px;transition:.2s}
.login-input-wrap input::placeholder{color:var(--text-3)}
.login-input-wrap input:focus{border-color:var(--primary);background:var(--surface);outline:none;box-shadow:0 0 0 3px rgba(204,58,114,0.10)}
.login-input-wrap .eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:15px;color:var(--text-3);padding:2px;transition:color .2s}
.login-input-wrap .eye-btn:hover{color:var(--primary)}
.login-btn-primary{width:100%;padding:12px;border-radius:var(--radius-sm);border:none;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;letter-spacing:.3px;cursor:pointer;box-shadow:0 4px 14px rgba(204,58,114,0.35);transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px}
.login-btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(204,58,114,0.44)}
.login-divider{text-align:center;color:var(--text-3);font-size:12px;margin:18px 0;position:relative}
.login-divider::before,.login-divider::after{content:'';position:absolute;top:50%;width:40%;height:1px;background:var(--border)}
.login-divider::before{left:0}
.login-divider::after{right:0}
.login-social-row{display:flex;gap:10px}
.login-btn-social{flex:1;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;color:var(--text-2);cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px}
.login-btn-social:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.login-footer{text-align:center;color:var(--text-2);font-size:13px;margin-top:20px}
.login-footer-link{color:var(--primary);font-weight:700;cursor:pointer;background:none;border:none;font-family:inherit;font-size:13px}
.login-footer-link:hover{text-decoration:underline}
.login-toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--text);color:#fff;padding:12px 22px;border-radius:50px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.18);transition:transform .3s ease,opacity .3s ease;opacity:0;pointer-events:none;white-space:nowrap;z-index:999}
.login-toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.login-toast.success{background:var(--primary)}

</style>
<?php if ($db_theme): ?>
<style id="lf-theme-vars">
:root {
  --primary: hsl(<?php echo htmlspecialchars($db_theme['primary_color']); ?>);
  --primary-dark: hsl(<?php echo htmlspecialchars($db_theme['primary_dark']); ?>);
  --primary-light: hsl(<?php echo htmlspecialchars($db_theme['primary_light']); ?>);
  --bg: hsl(<?php echo htmlspecialchars($db_theme['bg_color']); ?>);
  --surface: hsl(<?php echo htmlspecialchars($db_theme['surface_color']); ?>);
  --border: hsl(<?php echo htmlspecialchars($db_theme['border_color']); ?>);
  --text: hsl(<?php echo htmlspecialchars($db_theme['text_color']); ?>);
  --text-2: hsl(<?php echo htmlspecialchars($db_theme['text_secondary']); ?>);
  <?php if ($db_theme['accent_color']): ?>--secondary: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>); --accent: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>);<?php endif; ?>
  --primary-glow: hsla(<?php echo htmlspecialchars($db_theme['primary_color']); ?>, 0.12);
}
</style>
<?php endif; ?>
</head>
<body>



<div class="app-shell">
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
            <svg width="30" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <polygon points="26,13 41,20.5 26,28 11,20.5" fill="var(--primary)"/>
          <line x1="38" y1="20.5" x2="38" y2="31" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="38" cy="33" r="2.2" fill="var(--primary)"/>
          <path d="M13,36 Q18,32 23,36 Q28,40 33,36 Q38,32 43,36" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" fill="none"/>
          <path d="M15,42 Q20,38 25,42 Q30,46 35,42 Q40,38 45,42" stroke="var(--primary)" stroke-width="1.4" stroke-linecap="round" fill="none" opacity="0.6"/>
        </svg>
      <div class="sidebar-brand">Learn<span style="color:var(--primary)">Flow</span></div>
    </div>
    <div id="navMenu"></div>
    <div class="sidebar-footer">
      <div class="user-card" style="flex:1;min-width:0;display:flex;align-items:center;gap:10px;padding:4px 2px;border-radius:var(--radius-sm);cursor:pointer;transition:.15s" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
        <div class="user-avatar" id="sidebarAvatar" style="overflow:hidden;flex-shrink:0"><?php if($instructor_photo_url): ?><img src="<?php echo htmlspecialchars($instructor_photo_url); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"><?php else: ?><?php echo $initials; ?><?php endif; ?></div>
        <div class="user-info" style="overflow:hidden">
          <div class="user-name"><?php echo htmlspecialchars($instructor_name); ?></div>
          <div class="user-role">Instructor</div>
        </div>
      </div>
      <button onclick="logout()" class="logout-btn" title="Sign Out">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 5 12 10 7"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      </button>
    </div>
  </aside>

  <div class="main-content" id="mainContent">
    <div class="topbar">
      <div class="topbar-left">
        <button class="icon-btn" onclick="toggleSidebar()" style="display:flex;align-items:center;justify-content:center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
        <div class="topbar-title" id="topbarTitle">Dashboard</div>
      </div>
      <div class="topbar-right">
        <div style="position:relative">
          <div class="icon-btn" onclick="toggleNotifs()" style="position:relative;display:flex;align-items:center;justify-content:center">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg><?php if(array_sum(array_column($js_notifications,'unread'))>0): ?><div class="notif-dot" id="topNotifDot"></div><?php endif; ?>
          </div>
          <div class="notif-panel" id="notifPanel">
            <div class="notif-header">
              <h3>Notifications <span id="topNotifUnreadBadge" style="font-size:11px;background:var(--danger);color:#fff;padding:1px 7px;border-radius:10px;font-weight:700;margin-left:4px;display:<?php echo array_sum(array_column($js_notifications,'unread'))>0?'inline':'none'; ?>"><?php echo array_sum(array_column($js_notifications,'unread')) ?: ''; ?></span></h3>
              <span class="auth-link" style="font-size:12px" onclick="markAllNotifsRead();document.getElementById('notifPanel').classList.remove('open')">Mark all read</span>
            </div>
            <div id="topNotifList"></div>
            <div style="padding:12px 16px;text-align:center;border-top:1px solid var(--border)">
              <span class="auth-link" style="font-size:12px" onclick="navigate('notifications');document.getElementById('notifPanel').classList.remove('open')">View all</span>
            </div>
          </div>
        </div>
        <div class="icon-btn" onclick="toggleDark()" id="darkBtn" style="display:flex;align-items:center;justify-content:center"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg></div>
        <div class="icon-btn" onclick="navigate('settings')" style="display:flex;align-items:center;justify-content:center"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg></div>
        <div class="user-avatar" id="topbarAvatar" style="cursor:pointer;overflow:hidden" onclick="navigate('settings')"><?php if($instructor_photo_url): ?><img src="<?php echo htmlspecialchars($instructor_photo_url); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"><?php else: ?><?php echo $initials; ?><?php endif; ?></div>
      </div>
    </div>
    <div class="content-area" id="contentArea"></div>
  </div>
</div>

<div class="disc-modal-overlay" id="newDiscModal">
  <div class="disc-modal">
    <div class="disc-modal-title">Start a New Discussion</div>
    <div class="disc-form-group">
      <label>Course or Class</label>
      <select id="disc-course" required>
        <option value="">-- Select a Course --</option>
        <?php foreach ($db_sections as $sec): ?>
        <option value="<?php echo htmlspecialchars($sec['course_title'].' ('.$sec['course_code'].')'); ?>"><?php echo htmlspecialchars($sec['course_title'].' ('.$sec['course_code'].')'); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="disc-form-group">
      <label>Discussion Title</label>
      <input type="text" id="disc-title" placeholder="e.g., How to approach normalization?">
    </div>
    <div class="disc-form-group">
      <label>Your Question or Topic</label>
      <textarea id="disc-body" placeholder="Provide details about your question or topic..."></textarea>
    </div>
    <div class="disc-modal-actions">
      <button style="background:var(--surface-2);border:1px solid var(--border);color:var(--text-2)" onclick="closeNewDiscModal()">Cancel</button>
      <button style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff" onclick="createNewDiscussion()">Create Discussion</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addCourseModal">
  <div class="modal">
    <div class="modal-header">
      <h2>➕ Add New Course</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('addCourseModal')">×</button>
    </div>
    <div class="form-group">
      <label class="form-label">Course Title</label>
      <div class="input-wrap">
        <span class="icon">📚</span>
        <input type="text" id="newCourseTitle" placeholder="e.g. Advanced Web Technologies">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Course Code</label>
      <div class="input-wrap">
        <span class="icon">#️⃣</span>
        <input type="text" id="newCourseCode" placeholder="e.g. CS 401">
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label class="form-label">Section</label>
        <select id="newCourseSection" style="width:100%">
          <option>BSIT-2A</option><option>BSIT-2B</option><option>BSCS-2A</option>
          <option>BSIT-4A</option><option>BSIT-3A</option><option>BSCS-3A</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Units</label>
        <select id="newCourseUnits" style="width:100%">
          <option>3 Units</option><option>6 Units (Lab)</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">School Year</label>
      <select id="newCourseYear" style="width:100%">
        <option>2023–2024</option><option>2024–2025</option>
        <option selected>2025–2026</option><option>2026–2027</option>
      </select>
    </div>
    <div id="addCourseError" style="display:none;color:var(--danger);font-size:12px;margin-bottom:8px;padding:8px 12px;background:rgba(216,64,64,0.08);border-radius:var(--radius-sm)"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('addCourseModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitNewCourse()">Create Course</button>
    </div>
  </div>
</div>

<div class="ai-context-modal" id="aiContextModal" onclick="if(event.target===this)closeModal('aiContextModal')">
  <div class="ai-context-modal-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div class="ai-context-modal-title" style="margin-bottom:0">Select Course Context</div>
      <button onclick="closeModal('aiContextModal')" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:18px;line-height:1;padding:2px 6px;border-radius:6px;transition:.2s" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
    </div>
    <div class="ai-context-list" id="aiContextList"></div>
  </div>
</div>

<div class="toast" id="toast"></div>


<script>
// ===== STATE =====
let sidebarCollapsed = false;
let darkMode = false;
let currentPage = 'dashboard';
let notifOpen = false;
// ===== COURSES (loaded from database) =====
const courses = <?php echo $js_courses_json; ?>;

// ===== SVG ICON LIBRARY =====
const IC = {
  home:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
  courses:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>`,
  assignments:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>`,
  quizzes:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-4"/><path d="M15 3H9v4h6V3z"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/></svg>`,
  submissions:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>`,
  randomizer:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>`,
  discussions:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>`,
  bell:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>`,
  settings:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>`,
  moon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>`,
  sun:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`,
  menu:`<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>`,
  logout:`<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
  camera:`<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>`,
  save:`<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>`,
  upload:`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>`,
  download:`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>`,
  file:`<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>`,
  draft:`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>`,
  archive:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`,
  users:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>`,
};

// ===== NAV =====
const navItems = [
  { label:'Dashboard',       id:'dashboard',          icon:IC.home, badge:<?php echo count($js_admin_announcements); ?> },
  { label:'Courses',         id:'inst-courses',       icon:IC.courses },
  { label:'Archived',        id:'archived',           icon:IC.archive },
  { label:'Assignments',     id:'inst-assignments',   icon:IC.assignments },
  { label:'Quizzes',         id:'inst-quizzes',       icon:IC.quizzes },
  { label:'Submissions',     id:'submissions',        icon:IC.submissions },
  { label:'Randomizer',      id:'randomizer',         icon:IC.randomizer },
  { label:'Discussions',     id:'discussions',        icon:IC.discussions },
  { label:'Notifications',   id:'notifications',      icon:IC.bell, badge:<?php echo array_sum(array_column($js_notifications, 'unread')) ?: 0; ?> },
  { label:'Settings',        id:'settings',           icon:IC.settings },
];

const pageTitles = {
  dashboard:'Dashboard','inst-courses':'Course Management',
  'inst-assignments':'Create Assignment','inst-quizzes':'Quiz Builder',
  submissions:'Student Submissions', randomizer:'Randomizer & Attendance', discussions:'Discussions',
  notifications:'Notifications', settings:'Settings',
  'quiz-results':'Quiz Results', archived:'Archived Courses'
};

function renderNav() {
  document.getElementById('navMenu').innerHTML = navItems.map(item=>`
    <div class="nav-item${item.id===currentPage?' active':''}" onclick="navigate('${item.id}')" data-nav="${item.id}">
      <span class="nav-icon">${item.icon}</span>
      <span class="nav-label">${item.label}</span>
      ${item.badge?`<span class="nav-badge">${item.badge}</span>`:''}
    </div>`).join('');
}

function navigate(id) {
  currentPage = id;
  document.querySelectorAll('.nav-item').forEach(el=>el.classList.remove('active'));
  const el = document.querySelector(`[data-nav="${id}"]`);
  if(el) el.classList.add('active');
  document.getElementById('topbarTitle').textContent = pageTitles[id] || id;
  renderContent(id);
  notifOpen = false;
  document.getElementById('notifPanel').classList.remove('open');
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// ===== SIDEBAR =====
function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const mc = document.getElementById('mainContent');
  if(window.innerWidth <= 768) {
    sb.classList.toggle('mobile-open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
  } else {
    sidebarCollapsed = !sidebarCollapsed;
    sb.classList.toggle('collapsed', sidebarCollapsed);
    mc.classList.toggle('expanded', sidebarCollapsed);
  }
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// ===== ANNOUNCEMENT BADGE HELPERS =====
function setDashboardAnnBadge(count) {
  navItems[0].badge = count;
  renderNav();
}

let _annPollTimer = null;
function startAnnUnreadPolling() {
  const poll = async () => {
    try {
      const res  = await fetch('api-ann-read.php?action=get_unread');
      const data = await res.json();
      if (data.success) setDashboardAnnBadge(data.unread);
    } catch(e) {}
  };
  _annPollTimer = setInterval(poll, 60_000);
}

document.addEventListener("DOMContentLoaded", () => {
  const saved = localStorage.getItem('theme');
  darkMode = saved ? saved === 'dark' : false;
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('darkBtn').innerHTML = darkMode ? IC.sun : IC.moon;
  if (adminAnnouncements.length > 0) startAnnUnreadPolling();
});

// ── Live theme listener: picks up changes broadcast by admin ─────────────────
(function _lfSetupThemeListener() {
  function _hslParts(hsl) {
    const m = String(hsl).match(/([\d.]+)\s+([\d.]+)%\s+([\d.]+)%/);
    return m ? [parseFloat(m[1]), parseFloat(m[2]), parseFloat(m[3])] : [0,0,50];
  }
  function _shiftL(hsl, delta) {
    const [h, s, l] = _hslParts(hsl);
    return `${h} ${s}% ${Math.max(0, Math.min(100, l + delta))}%`;
  }
  function _darkVariants(t) {
    return {
      p: _shiftL(t.p, +8), d: _shiftL(t.d, +6), l: _shiftL(t.p, -35),
      bg: _shiftL(t.p, -47), surface: _shiftL(t.p, -43), surface2: _shiftL(t.p, -40),
      surface3: _shiftL(t.p, -37), border: _shiftL(t.p, -30), borderSt: _shiftL(t.p, -25),
      text: _shiftL(t.p, +48), text2: _shiftL(t.p, +20), text3: _shiftL(t.p, -15), acc: t.acc,
    };
  }
  function _applyThemeVars(t) {
    let style = document.getElementById('lf-theme-vars');
    if (!style) { style = document.createElement('style'); style.id = 'lf-theme-vars'; document.head.appendChild(style); }
    const dk = _darkVariants(t);
    const surf = t.surface || '0 0% 100%';
    style.textContent = `
:root {
  --primary:      hsl(${t.p});
  --primary-dark: hsl(${t.d});
  --primary-light:hsl(${t.l});
  --bg:           hsl(${t.bg});
  --surface:      hsl(${surf});
  --surface-2:    hsl(${_shiftL(t.bg, -3)});
  --surface-3:    hsl(${_shiftL(t.bg, -6)});
  --border:       hsl(${t.border});
  --border-strong:hsl(${_shiftL(t.border, -8)});
  --text:         hsl(${t.text});
  --text-2:       hsl(${t.text2});
  --text-3:       hsl(${_shiftL(t.text2, +20)});
  --secondary:    hsl(${t.acc});
  --primary-glow: hsla(${t.p}, 0.12);
}
[data-theme="dark"] {
  --primary:      hsl(${dk.p});
  --primary-dark: hsl(${dk.d});
  --primary-light:hsl(${dk.l});
  --bg:           hsl(${dk.bg});
  --surface:      hsl(${dk.surface});
  --surface-2:    hsl(${dk.surface2});
  --surface-3:    hsl(${dk.surface3});
  --border:       hsl(${dk.border});
  --border-strong:hsl(${dk.borderSt});
  --text:         hsl(${dk.text});
  --text-2:       hsl(${dk.text2});
  --text-3:       hsl(${dk.text3});
  --secondary:    hsl(${dk.acc});
  --primary-glow: hsla(${dk.p}, 0.18);
}`;
  }
  function _applyFromStorage() {
    try {
      const raw = localStorage.getItem('lf-theme-data');
      if (raw) _applyThemeVars(JSON.parse(raw));
    } catch(e) {}
  }
  // BroadcastChannel — instant cross-tab (same origin)
  try {
    const _bc = new BroadcastChannel('lf-theme');
    _bc.onmessage = (e) => {
      if (e.data && e.data.type === 'theme-update') {
        try { _applyThemeVars(JSON.parse(e.data.theme)); } catch(err) {}
      }
    };
  } catch(e) {}
  // storage event — fires in other tabs when localStorage changes
  window.addEventListener('storage', (e) => {
    if (e.key === 'lf-theme-data' && e.newValue) {
      try { _applyThemeVars(JSON.parse(e.newValue)); } catch(err) {}
    }
  });
  // Apply on DOMContentLoaded so a freshly-opened tab picks up the last saved theme
  document.addEventListener('DOMContentLoaded', _applyFromStorage);
})();

function toggleDark() {
  darkMode = !darkMode;
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('darkBtn').innerHTML = darkMode ? IC.sun : IC.moon;
  localStorage.setItem('theme', darkMode ? 'dark' : 'light');
}

// ===== NOTIFICATIONS =====
function toggleNotifs() {
  notifOpen = !notifOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifOpen);
  if (notifOpen) renderTopNotifList();
}
document.addEventListener('click', e=>{
  if(!e.target.closest('.icon-btn') && !e.target.closest('.notif-panel')) {
    document.getElementById('notifPanel').classList.remove('open');
    notifOpen = false;
  }
});
// ===== MODAL =====
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ===== TOAST =====
function showToast(msg, type='') {
  const t = document.getElementById('toast');
  const icons = { success:'✓', error:'✗', warning:'⚠️', warn:'⚠️' };
  t.textContent = (icons[type]||'ℹ️') + ' ' + msg;
  t.className = 'toast' + (type?' '+type:'') + ' show';
  setTimeout(()=>t.classList.remove('show'), 3200);
}

// ===== RENDER CONTENT =====
function renderContent(id) {
  const area = document.getElementById('contentArea');
  area.innerHTML = '';
  const map = {
    dashboard: renderDashboard,
    'inst-courses': renderInstCourses,
    'inst-assignments': renderInstAssignments,
    'inst-quizzes': renderInstQuizzes,
    submissions: renderSubmissions,
    randomizer: renderRandomizer,
    discussions: renderDiscussions,
    notifications: renderNotifications,
    settings: renderSettings,
    'quiz-results': renderQuizResults,
    archived: renderArchivedCourses,
  };
  area.innerHTML = (map[id] || (()=>`<div style="text-align:center;padding:60px 20px;color:var(--text-2)"><div style="font-size:48px;margin-bottom:12px">🚧</div><div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700">Coming Soon</div></div>`))();
  if (id === 'inst-quizzes') setTimeout(qzRender, 10);
  if (id === 'inst-courses') setTimeout(() => { populateSectionDropdown(); filterCourseTable(); }, 10);
  if (id === 'inst-assignments') setTimeout(filterAsgPanel, 10);
  if (id === 'inst-quizzes')    setTimeout(filterQzPanel, 10);
  if (id === 'notifications') { notifFilter = 'all'; }
  if (id === 'quiz-results')  { qzrFilterCourse = 'all'; qzrFilterStatus = 'all'; qzrSortKey = null; qzrSortAsc = true; setTimeout(filterQzResults, 10); }
  area.classList.remove('fade-in');
  void area.offsetWidth;
  area.classList.add('fade-in');
}

// ===== BAR CHART =====
function animateBars() {
  setTimeout(()=>{
    [72,78,74,89].forEach((v,i)=>{
      const bar = document.getElementById(`bar${i}`);
      if(bar) { bar.style.transition='.6s ease'; bar.style.height=v+'%'; }
    });
  }, 100);
}


// ===== PAGES =====

// Recent activities from DB (PHP-injected)
const dashRecentActivities = <?php echo $js_recent_activities_json; ?>;

function timeAgo(dateStr) {
  const now = new Date();
  const then = new Date(dateStr.replace(' ', 'T') + '+00:00');
  const diff = Math.floor((now - then) / 1000);
  if (diff < 60) return 'Just now';
  if (diff < 3600) return Math.floor(diff/60) + ' min ago';
  if (diff < 86400) return Math.floor(diff/3600) + ' hr ago';
  if (diff < 172800) return 'Yesterday';
  return then.toLocaleDateString('en-PH', {month:'short', day:'numeric'});
}

function renderDashboard() {
  const now = new Date();
  const timeStr = now.toLocaleString('en-PH',{dateStyle:'medium',timeStyle:'short'});

  const activityRows = dashRecentActivities.length > 0
    ? dashRecentActivities.map(act => `
        <div class="activity-item">
          <div class="act-icon" style="background:${act.icon_color}">${act.icon_char}</div>
          <div class="act-body"><div class="act-text">${act.label}</div><div class="act-time">${timeAgo(act.time)}</div></div>
        </div>`).join('')
    : `<div style="text-align:center;padding:24px;color:var(--text-3);font-size:13px">No recent activity found.</div>`;

  return `
    <div class="page-header">
      <div class="breadcrumb"><span>Home</span><span>›</span><span>Dashboard</span></div>
      <h1>Good ${getGreeting()}, <?php echo htmlspecialchars($instructor_name); ?>! 👋</h1>
      <p>Here's your teaching overview for today</p>
    </div>
    <div class="stats-grid">
      <div class="stat-card" onclick="navigate('inst-courses')" title="Go to Course Management">
        <div class="stat-icon blue">${IC.courses}</div>
        <div>
          <div class="stat-val"><?php echo $dash_active_courses; ?></div>
          <div class="stat-label">Active Courses</div>
          <div class="stat-trend up">Published &amp; running</div>
        </div>
      </div>
      <div class="stat-card" onclick="navigate('inst-courses')" title="View Students">
        <div class="stat-icon amber">${IC.users}</div>
        <div>
          <div class="stat-val"><?php echo $dash_total_students; ?></div>
          <div class="stat-label">Total Students</div>
          <div class="stat-trend up">Across all sections</div>
        </div>
      </div>
      <div class="stat-card" onclick="navigate('archived')" title="View Archived Courses">
        <div class="stat-icon green">${IC.archive}</div>
        <div>
          <div class="stat-val"><?php echo $dash_archived_courses; ?></div>
          <div class="stat-label">Archived Courses</div>
          <div class="stat-trend up">Total archived</div>
        </div>
      </div>
      <div class="stat-card" onclick="navigate('discussions')" title="Go to Discussions">
        <div class="stat-icon pink">${IC.discussions}</div>
        <div>
          <div class="stat-val"><?php echo $dash_active_discussions; ?></div>
          <div class="stat-label">Active Discussions</div>
          <div class="stat-trend up">Open threads</div>
        </div>
      </div>
    </div>
    ${adminAnnouncements.length > 0 ? `
    <div class="card" id="adminAnnSection" style="margin-bottom:20px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="font-size:15px;font-weight:700">📢 Announcements from Admin</h3>
        <span class="badge badge-pink" id="adminAnnBadge">${adminAnnouncements.length}</span>
      </div>
      <div id="adminAnnWrapper">${adminAnnouncements.map(a => renderAdminAnnouncementCard(a)).join('')}</div>
    </div>` : ''}

    <div class="grid-2">
      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h3 style="font-size:15px;font-weight:700">Recent Activity</h3>
          <span class="badge badge-pink">Live</span>
        </div>
        ${activityRows}
      </div>
      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h3 style="font-size:15px;font-weight:700">Audit Trail</h3>
        </div>
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:12px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div><div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">User</div><div style="font-size:13px;font-weight:600;margin-top:2px"><?php echo htmlspecialchars($instructor_name); ?></div></div>
            <div><div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">Role</div><div style="font-size:13px;font-weight:600;margin-top:2px">Instructor</div></div>
            <div><div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">Login Time</div><div style="font-size:13px;font-weight:600;margin-top:2px">${timeStr}</div></div>
            <div><div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">Status</div><div class="badge badge-green" style="margin-top:4px">● Active</div></div>
          </div>
        </div>
      </div>
    </div>`;
}

// ===== COURSE DATA (loaded from database) =====
const instCourseData = <?php echo $js_inst_data_json; ?>;

// Announcements loaded from DB, keyed by section_id
let courseAnnouncements = <?php echo $js_announcements_json; ?>;

// Admin announcements (platform-wide + user-specific)
const adminAnnouncements = <?php echo $js_admin_announcements_json; ?>;

function renderAdminAnnouncementCard(a) {
  const priorityColor = a.priority === 'urgent' ? '#D84040' : a.priority === 'important' ? '#E09010' : '#4AAEE8';
  const priorityLabel = a.priority === 'urgent' ? '🚨 Urgent' : a.priority === 'important' ? '⚠️ Important' : '📢 Announcement';
  const dateStr = a.date ? new Date(a.date).toLocaleDateString('en-PH', {month:'short',day:'numeric',year:'numeric'}) : '';
  return `<div id="ann-card-${a.id}" style="padding:14px 16px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);margin-bottom:10px;transition:opacity .25s,max-height .3s;overflow:hidden;${a.pinned?'border-left:3px solid var(--primary);':''}">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:11px;font-weight:700;color:${priorityColor}">${priorityLabel}</span>
        <span style="font-size:11px;color:var(--text-3)">${dateStr}</span>
      </div>
      <button onclick="dismissAnnouncement(${a.id})" title="Dismiss" style="flex-shrink:0;background:none;border:none;cursor:pointer;font-size:15px;color:var(--text-3);padding:0 2px;line-height:1;transition:color .15s" onmouseover="this.style.color='#D84040'" onmouseout="this.style.color='var(--text-3)'">✕</button>
    </div>
    <div style="font-weight:700;font-size:14px;margin-bottom:4px">${a.title}</div>
    <div style="font-size:13px;color:var(--text-2);line-height:1.6">${a.body}</div>
    <div style="font-size:11px;color:var(--text-3);margin-top:8px">— ${a.author}</div>
  </div>`;
}

async function dismissAnnouncement(id) {
  const card = document.getElementById('ann-card-' + id);
  if (!card) return;
  card.style.opacity = '0';
  card.style.maxHeight = card.offsetHeight + 'px';
  await new Promise(r => setTimeout(r, 30));
  card.style.maxHeight = '0';
  card.style.paddingTop = '0';
  card.style.paddingBottom = '0';
  card.style.marginBottom = '0';
  await new Promise(r => setTimeout(r, 300));
  card.remove();
  const wrapper = document.getElementById('adminAnnWrapper');
  const remaining = wrapper ? wrapper.querySelectorAll('[id^="ann-card-"]').length : 0;
  const badge = document.getElementById('adminAnnBadge');
  if (badge) badge.textContent = remaining;
  setDashboardAnnBadge(remaining);
  if (remaining === 0) {
    if (_annPollTimer) { clearInterval(_annPollTimer); _annPollTimer = null; }
    const section = document.getElementById('adminAnnSection');
    if (section) { section.style.opacity='0'; section.style.transition='opacity .2s'; setTimeout(()=>section.remove(),200); }
  }
  try {
    await fetch('api-ann-read.php?action=mark_read', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({announcement_id: id})
    });
  } catch(e) {}
}

// Archived course data loaded from DB
const archivedCourseData = <?php echo $js_archived_data_json; ?>;

let openCourseId = null;

function renderInstCourses() {
  openCourseId = null;
  return `
    <div class="page-header"><h1>Course Management</h1><p>Manage your active courses and sections</p></div>

    <!-- ── Toolbar: search + filters + new button ── -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
      <!-- Search input -->
      <div style="position:relative;flex:1;min-width:200px;max-width:320px">
        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:14px;pointer-events:none">🔍</span>
        <input id="courseSearchInput" type="text" placeholder="Search by name, code, section…"
          style="width:100%;padding:9px 12px 9px 34px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;transition:.2s"
          oninput="filterCourseTable()"
          onfocus="this.style.borderColor='var(--primary)'"
          onblur="this.style.borderColor='var(--border)'">
      </div>

      <!-- Section filter dropdown -->
      <div style="position:relative;min-width:180px">
        <select id="courseSectionFilter"
          style="width:100%;padding:9px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;cursor:pointer;transition:.2s;appearance:none;-webkit-appearance:none;padding-right:32px"
          onfocus="this.style.borderColor='var(--primary)'"
          onblur="this.style.borderColor='var(--border)'"
          onchange="filterCourseTable()">
          <option value="">All Sections</option>
        </select>
        <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text-3);font-size:12px">▾</span>
      </div>

    </div>

    <div class="card">
      <!-- Result count -->
      <div id="courseResultCount" style="font-size:12px;color:var(--text-3);margin-bottom:10px"></div>
      <div class="table-wrap">
        <table id="courseTable">
          <thead>
            <tr>
              <th style="cursor:pointer;user-select:none" onclick="sortCourseTable('name')">
                Course <span id="sort-name" style="opacity:.4">⇅</span>
              </th>
              <th>Section</th>
              <th style="cursor:pointer;user-select:none" onclick="sortCourseTable('students')">
                Students <span id="sort-students" style="opacity:.4">⇅</span>
              </th>
              <th style="cursor:pointer;user-select:none" onclick="sortCourseTable('prog')">
                Completion <span id="sort-prog" style="opacity:.4">⇅</span>
              </th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="courseTableBody"></tbody>
        </table>
      </div>
      <div id="courseEmptyState" style="display:none;text-align:center;padding:40px 20px;color:var(--text-3)">
        <div style="font-size:36px;margin-bottom:10px">🔍</div>
        <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No courses found</div>
        <div style="font-size:12px">Try a different search term or filter</div>
      </div>
    </div>`;
}

// ── Course table state ────────────────────────────────────────────
let courseSortKey    = null;
let courseSortAsc    = true;

// Populate section dropdown from live data
function populateSectionDropdown() {
  const sel = document.getElementById('courseSectionFilter');
  if (!sel) return;
  const sections = [...new Set(instCourseData.map(r => r.section))].sort();
  // Keep "All Sections" option, rebuild the rest
  sel.innerHTML = '<option value="">All Sections</option>' +
    sections.map(s => `<option value="${s}">${s}</option>`).join('');
}

function sortCourseTable(key) {
  if (courseSortKey === key) { courseSortAsc = !courseSortAsc; }
  else { courseSortKey = key; courseSortAsc = true; }
  // update sort icons
  ['name','students','prog'].forEach(k => {
    const el = document.getElementById('sort-' + k);
    if (el) el.textContent = k === key ? (courseSortAsc ? '↑' : '↓') : '⇅';
    if (el) el.style.opacity = k === key ? '1' : '.4';
  });
  filterCourseTable();
}

function filterCourseTable() {
  const q   = (document.getElementById('courseSearchInput')?.value || '').toLowerCase();
  const tbody = document.getElementById('courseTableBody');
  const empty = document.getElementById('courseEmptyState');
  const count = document.getElementById('courseResultCount');
  if (!tbody) return;

  let data = [...instCourseData];

  // text search
  if (q) data = data.filter(r =>
    r.name.toLowerCase().includes(q) ||
    r.code.toLowerCase().includes(q) ||
    r.section.toLowerCase().includes(q)
  );

  // section dropdown filter
  const sectionSel = document.getElementById('courseSectionFilter');
  const sectionVal = sectionSel ? sectionSel.value : '';
  if (sectionVal) data = data.filter(r => r.section === sectionVal);

  // sort
  if (courseSortKey) {
    data.sort((a, b) => {
      const va = a[courseSortKey], vb = b[courseSortKey];
      return courseSortAsc
        ? (typeof va === 'string' ? va.localeCompare(vb) : va - vb)
        : (typeof va === 'string' ? vb.localeCompare(va) : vb - va);
    });
  }

  // result count
  if (count) count.textContent = data.length === instCourseData.length
    ? '' : `Showing ${data.length} of ${instCourseData.length} courses`;

  if (data.length === 0) {
    tbody.innerHTML = '';
    if (empty) empty.style.display = 'block';
    return;
  }
  if (empty) empty.style.display = 'none';

  tbody.innerHTML = data.map(r => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">${r.emoji||'📚'}</div>
          <div>
            <div style="font-weight:600;font-size:13px">${r.name}</div>
            <div style="font-size:11px;color:var(--text-3)">${r.code}</div>
          </div>
        </div>
      </td>
      <td><span style="font-size:12px;font-weight:600;background:var(--surface-2);border:1px solid var(--border);border-radius:6px;padding:3px 8px">${r.section}</span></td>
      <td>
        <div style="display:flex;align-items:center;gap:5px">
          <span style="font-size:13px;font-weight:600">${r.students}</span>
          <span style="font-size:11px;color:var(--text-3)">enrolled</span>
        </div>
      </td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="progress-bar" style="width:80px"><div class="progress-fill" style="width:${r.prog}%"></div></div>
          <span style="font-size:11px;color:var(--text-2);min-width:28px">${r.prog}%</span>
        </div>
      </td>
      <td><span class="badge badge-green">● Active</span></td>
      <td>
        <div style="display:flex;align-items:center;gap:6px">
          <button class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap" onclick="openCourseView(${r.id})">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Open
          </button>
          <button class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap;color:var(--danger);border-color:var(--danger)" onclick="promptArchiveCourse(${r.id},'${r.name.replace(/'/g,"\\'")} (${r.code})')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            Archive
          </button>
        </div>
      </td>
    </tr>`).join('');
}

// ===== ARCHIVE / RESTORE =====
function promptArchiveCourse(sectionId, courseLabel) {
  const reason = prompt(`Archive "${courseLabel}"?\n\nOptionally enter a reason (or leave blank):`);
  if (reason === null) return; // cancelled
  archiveCourse(sectionId, reason.trim());
}

function archiveCourse(sectionId, reason) {
  const fd = new FormData();
  fd.append('action','archive_course');
  fd.append('section_id', sectionId);
  if (reason) fd.append('reason', reason);
  showToast('Archiving course…');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const idx = instCourseData.findIndex(c => c.id === sectionId);
        if (idx !== -1) {
          const [course] = instCourseData.splice(idx, 1);
          archivedCourseData.push({ ...course, archived_at: new Date().toISOString(), archive_reason: reason || null });
        }
        filterCourseTable();
        showToast('Course archived successfully', 'success');
        setTimeout(() => navigate('archived'), 600);
      } else {
        showToast(data.message || 'Archive failed', 'error');
      }
    })
    .catch(() => showToast('Network error — could not archive', 'error'));
}

function unarchiveCourse(sectionId) {
  if (!confirm('Restore this course to your active courses list?')) return;
  const fd = new FormData();
  fd.append('action','unarchive_course');
  fd.append('section_id', sectionId);
  showToast('Restoring course…');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const idx = archivedCourseData.findIndex(c => c.id === sectionId);
        if (idx !== -1) {
          const [course] = archivedCourseData.splice(idx, 1);
          delete course.archived_at; delete course.archive_reason;
          instCourseData.push(course);
        }
        showToast('Course restored!', 'success');
        setTimeout(() => navigate('inst-courses'), 600);
      } else {
        showToast(data.message || 'Restore failed', 'error');
      }
    })
    .catch(() => showToast('Network error — could not restore', 'error'));
}

// ===== ARCHIVED COURSES PAGE =====
function renderArchivedCourses() {
  const fmtDate = iso => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
  };
  return `
    <div class="page-header">
      <div class="breadcrumb"><span onclick="navigate('inst-courses')" style="cursor:pointer;color:var(--primary)">Courses</span><span>›</span><span>Archived</span></div>
      <h1>Archived Courses</h1>
      <p>Courses you have archived. Restore any course to make it active again.</p>
    </div>
    ${archivedCourseData.length === 0 ? `
      <div style="text-align:center;padding:80px 20px;color:var(--text-2)">
        <div style="width:64px;height:64px;border-radius:18px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--text-3)"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg></div>
        <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:8px">No Archived Courses</div>
        <div style="font-size:13px;color:var(--text-3)">When you archive a course it will appear here. You can restore it at any time.</div>
        <button class="btn btn-primary" style="margin-top:20px" onclick="navigate('inst-courses')">← Back to Courses</button>
      </div>` : `
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px">
      ${archivedCourseData.map(r => `
        <div style="border:1.5px solid var(--border);border-radius:var(--radius);padding:22px;background:var(--surface);position:relative;opacity:.88">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
            <div style="font-size:28px">${r.emoji || '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>'}</div>
            <div>
              <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;line-height:1.3">${r.name}</div>
              <div style="font-size:11px;color:var(--text-3)">${r.code} · ${r.section}</div>
            </div>
          </div>
          <div style="display:flex;gap:18px;font-size:12px;color:var(--text-2);margin-bottom:14px">
            <span>👥 ${r.students} students</span>
            <span>📅 Archived ${fmtDate(r.archived_at)}</span>
          </div>
          ${r.archive_reason ? `<div style="font-size:12px;color:var(--text-3);background:var(--surface-2);border-radius:8px;padding:8px 12px;margin-bottom:14px;font-style:italic">"${r.archive_reason}"</div>` : ''}
          <button class="btn btn-primary btn-sm" style="width:100%;justify-content:center" onclick="unarchiveCourse(${r.id})">↩ Restore Course</button>
        </div>`).join('')}
    </div>`}`;
}

// ===== ADD NEW COURSE =====
const courseEmojis = ['💻','🌐','🧮','🎯','📡','🔬','🧪','📐','🛠️','📊','🤖','🎲'];
function submitNewCourse() {
  const titleEl   = document.getElementById('newCourseTitle');
  const codeEl    = document.getElementById('newCourseCode');
  const sectionEl = document.getElementById('newCourseSection');
  const errEl     = document.getElementById('addCourseError');

  const title   = titleEl.value.trim();
  const code    = codeEl.value.trim();
  const section = sectionEl.value;

  errEl.style.display = 'none';
  if (!title) { errEl.textContent = '⚠ Course title is required.'; errEl.style.display = 'block'; titleEl.focus(); return; }
  if (!code)  { errEl.textContent = '⚠ Course code is required (e.g. CS 401).'; errEl.style.display = 'block'; codeEl.focus(); return; }
  if (instCourseData.find(c => c.code.toLowerCase() === code.toLowerCase())) {
    errEl.textContent = `⚠ Course code "${code}" already exists.`; errEl.style.display = 'block'; codeEl.focus(); return;
  }

  const fd = new FormData();
  fd.append('action', 'create_course');
  fd.append('title', title);
  fd.append('code', code);
  fd.append('section', section);
  showToast('Creating course…');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { errEl.textContent = '⚠ ' + data.message; errEl.style.display = 'block'; return; }
      const usedEmojis = instCourseData.map(c => c.emoji);
      const availEmoji = courseEmojis.filter(e => !usedEmojis.includes(e));
      const emoji = availEmoji.length ? availEmoji[0] : courseEmojis[instCourseData.length % courseEmojis.length];
      instCourseData.push({ id: data.section_id, name: title, code, section, students: 0, prog: 0, emoji, color: '#CC3A72' });
      courseAnnouncements[data.section_id] = [];
      titleEl.value = ''; codeEl.value = ''; errEl.style.display = 'none';
      closeModal('addCourseModal');
      showToast(data.message || `Course "${title}" created! 🎉`, 'success');
      navigate('inst-courses');
    })
    .catch(() => {
      // optimistic fallback
      const newId = Math.max(...instCourseData.map(c => c.id), 0) + 1;
      const usedEmojis = instCourseData.map(c => c.emoji);
      const availEmoji = courseEmojis.filter(e => !usedEmojis.includes(e));
      const emoji = availEmoji.length ? availEmoji[0] : courseEmojis[instCourseData.length % courseEmojis.length];
      instCourseData.push({ id: newId, name: title, code, section, students: 0, prog: 0, emoji, color: '#CC3A72' });
      courseAnnouncements[newId] = [];
      titleEl.value = ''; codeEl.value = ''; errEl.style.display = 'none';
      closeModal('addCourseModal');
      showToast(`Course "${title}" added (offline)`, 'warning');
      navigate('inst-courses');
    });
}

function openCourseView(courseId) {
  openCourseId = courseId;
  const course = instCourseData.find(c=>c.id===courseId);
  if (!course) return;
  document.getElementById('topbarTitle').textContent = `${course.name} (${course.code})`;
  document.getElementById('contentArea').innerHTML = renderCourseDetail(courseId);
  document.getElementById('contentArea').classList.remove('fade-in');
  void document.getElementById('contentArea').offsetWidth;
  document.getElementById('contentArea').classList.add('fade-in');
}

function renderCourseDetail(courseId) {
  const course = instCourseData.find(c=>c.id===courseId);
  const anns = courseAnnouncements[courseId] || [];
  const fmtDate = d => {
    const t = typeof d === 'number' ? d : new Date(d).getTime();
    const diff = Date.now() - t;
    const days = Math.floor(diff/86400000);
    const hrs  = Math.floor((diff%86400000)/3600000);
    if (days > 0) return days === 1 ? 'Yesterday' : `${days} days ago`;
    if (hrs > 0)  return `${hrs}h ago`;
    return 'Just now';
  };

  const html = `
    <!-- ── Header ── -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <button class="btn btn-ghost btn-sm" onclick="navigate('inst-courses')" style="padding:8px 12px">← Back to Courses</button>
      <div style="flex:1;min-width:0">
        <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800">${course.emoji} ${course.name} <span style="font-size:14px;color:var(--text-3);font-weight:500">${course.code}</span></div>
        <div style="font-size:12px;color:var(--text-2);margin-top:2px">Section ${course.section}</div>
      </div>
      <span class="badge badge-green" style="font-size:12px;padding:5px 12px">● Active</span>
    </div>

    <!-- ── Stats row (4 boxes, DB-driven) ── -->
    <div id="cdStats-${courseId}" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
      <div class="card" style="text-align:center;padding:16px 12px">
        <div id="cdStat-students-${courseId}" style="font-size:26px;font-weight:800;font-family:'Syne',sans-serif;color:var(--primary)">—</div>
        <div style="font-size:11px;color:var(--text-2);margin-top:3px;font-weight:600">Enrolled</div>
      </div>
      <div class="card" style="text-align:center;padding:16px 12px">
        <div id="cdStat-modules-${courseId}" style="font-size:26px;font-weight:800;font-family:'Syne',sans-serif;color:var(--primary)">—</div>
        <div style="font-size:11px;color:var(--text-2);margin-top:3px;font-weight:600">Modules</div>
      </div>
      <div class="card" style="text-align:center;padding:16px 12px">
        <div id="cdStat-ann-${courseId}" style="font-size:26px;font-weight:800;font-family:'Syne',sans-serif;color:var(--primary)">—</div>
        <div style="font-size:11px;color:var(--text-2);margin-top:3px;font-weight:600">Announcements</div>
      </div>
      <div class="card" style="text-align:center;padding:16px 12px">
        <div id="cdStat-acts-${courseId}" style="font-size:26px;font-weight:800;font-family:'Syne',sans-serif;color:var(--primary)">—</div>
        <div style="font-size:11px;color:var(--text-2);margin-top:3px;font-weight:600">Activities</div>
      </div>
    </div>

    <!-- ── Filter bar ── -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);padding:12px 16px">
      <span style="font-size:12px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">Filter</span>
      <div style="display:flex;gap:6px;flex-wrap:wrap;flex:1">
        <button id="cdFilter-all-${courseId}"  class="course-filter-chip active" onclick="setCdFilter(${courseId},'all')">All</button>
        <button id="cdFilter-ann-${courseId}"  class="course-filter-chip"        onclick="setCdFilter(${courseId},'ann')">📢 Announcements</button>
        <button id="cdFilter-mod-${courseId}"  class="course-filter-chip"        onclick="setCdFilter(${courseId},'mod')">📚 Modules</button>
      </div>
      <div style="position:relative;min-width:160px">
        <input id="cdSearch-${courseId}" type="text" placeholder="Search…"
          style="width:100%;padding:7px 10px 7px 30px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:12px;transition:.2s"
          oninput="filterCourseDetail(${courseId})"
          onfocus="this.style.borderColor='var(--primary)'"
          onblur="this.style.borderColor='var(--border)'">
        <span style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:12px;pointer-events:none">🔍</span>
      </div>
    </div>

    <!-- ── Side-by-side layout ── -->
    <div id="cdPanels-${courseId}" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">

      <!-- LEFT: Announcements -->
      <div id="cdAnnPanel-${courseId}" class="card" style="padding:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:8px;flex-wrap:wrap">
          <div>
            <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700">📢 Announcements</div>
            <div style="font-size:11px;color:var(--text-2);margin-top:2px">Notify all students in this course</div>
          </div>
          <button class="btn btn-primary btn-sm" onclick="openAnnComposerCD(${courseId})">+ Post</button>
        </div>

        <!-- Composer -->
        <div id="annComposerWrap-${courseId}" style="display:none;margin-bottom:16px;padding:16px;background:var(--surface-2);border-radius:var(--radius);border:1.5px dashed var(--primary)">
          <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:10px">📝 New Announcement</div>
          <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" id="annTitle-${courseId}" placeholder="e.g. Lab 6 Posted, Exam Reminder…" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px">
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea id="annBody-${courseId}" placeholder="Write your announcement…" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:80px"></textarea>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer">
              <input type="checkbox" id="annPin-${courseId}" style="accent-color:var(--primary)"> 📌 Pin
            </label>
            <div style="margin-left:auto;display:flex;gap:6px">
              <button class="btn btn-outline btn-sm" onclick="closeAnnComposer(${courseId})">Cancel</button>
              <button class="btn btn-primary btn-sm" onclick="postAnnouncement(${courseId})">📤 Post</button>
            </div>
          </div>
        </div>

        <!-- Announcements list -->
        <div id="annList-${courseId}">
          ${anns.length === 0
            ? `<div style="text-align:center;padding:32px 16px;color:var(--text-3)"><div style="font-size:36px;margin-bottom:8px">📭</div><div style="font-size:13px;font-weight:600;color:var(--text-2)">No announcements yet</div><div style="font-size:11px;margin-top:4px">Post one to notify students.</div></div>`
            : anns.map(a => renderAnnCard(a, courseId, fmtDate)).join('')
          }
        </div>
      </div>

      <!-- RIGHT: Modules -->
      <div id="cdModPanel-${courseId}" class="card" style="padding:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:8px;flex-wrap:wrap">
          <div>
            <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700">📚 Course Modules</div>
            <div style="font-size:11px;color:var(--text-2);margin-top:2px">Learning materials for students</div>
          </div>
          <button class="btn btn-primary btn-sm" onclick="openModuleComposer(${courseId})">+ Post</button>
        </div>

        <!-- Module composer -->
        <div id="modComposerWrap-${courseId}" style="display:none;margin-bottom:16px;padding:16px;background:var(--surface-2);border-radius:var(--radius);border:1.5px dashed var(--primary)">
          <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:10px">New Module</div>
          <div class="form-group">
            <label class="form-label">Module Title</label>
            <input type="text" id="modTitle-${courseId}" placeholder="e.g. Week 1: Introduction to OOP" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px">
          </div>
          <div class="form-group">
            <label class="form-label">Description <span style="color:var(--text-3);font-weight:400">(optional)</span></label>
            <textarea id="modDesc-${courseId}" placeholder="What will students learn…" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:70px"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Attach File <span style="color:var(--text-3);font-weight:400">(optional)</span></label>
            <input type="file" id="modFile-${courseId}" accept=".pdf,.pptx,.ppt,.docx,.doc,.mp4,.mov,.mp3,.jpg,.jpeg,.png,.zip,.txt"
              style="width:100%;padding:7px 9px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:11px;cursor:pointer">
          </div>
          <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
            <button class="btn btn-outline btn-sm" onclick="closeModuleComposer(${courseId})">Cancel</button>
            <button class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:5px" onclick="postModule(${courseId})">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Post Module
            </button>
          </div>
        </div>

        <!-- Modules list -->
        <div id="modList-${courseId}">
          <div style="text-align:center;padding:28px 16px;color:var(--text-3)">
            <div style="font-size:13px">Loading modules…</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Responsive CSS for side-by-side panels -->
    <style>
      @media (max-width: 700px) {
        #cdPanels-${courseId} { grid-template-columns: 1fr !important; }
        #cdStats-${courseId}  { grid-template-columns: repeat(2,1fr) !important; }
      }
    </style>`;

  setTimeout(() => {
    loadModules(courseId);
    loadCourseStats(courseId);
  }, 80);
  return html;
}

// ── Filter helpers for course detail ────────────────────────────────
let _cdFilter = {};
function setCdFilter(courseId, filter) {
  _cdFilter[courseId] = filter;
  ['all','ann','mod'].forEach(f => {
    const btn = document.getElementById(`cdFilter-${f}-${courseId}`);
    if (btn) btn.classList.toggle('active', f === filter);
  });
  const annPanel = document.getElementById(`cdAnnPanel-${courseId}`);
  const modPanel = document.getElementById(`cdModPanel-${courseId}`);
  const panels   = document.getElementById(`cdPanels-${courseId}`);
  if (!annPanel || !modPanel) return;
  if (filter === 'all') {
    annPanel.style.display = '';
    modPanel.style.display = '';
    if (panels) panels.style.gridTemplateColumns = '1fr 1fr';
  } else if (filter === 'ann') {
    annPanel.style.display = '';
    modPanel.style.display = 'none';
    if (panels) panels.style.gridTemplateColumns = '1fr';
  } else if (filter === 'mod') {
    annPanel.style.display = 'none';
    modPanel.style.display = '';
    if (panels) panels.style.gridTemplateColumns = '1fr';
  }
}

function filterCourseDetail(courseId) {
  const q = (document.getElementById(`cdSearch-${courseId}`)?.value || '').toLowerCase().trim();
  // Filter announcements
  const annItems = document.querySelectorAll(`#annList-${courseId} [data-ann-search]`);
  annItems.forEach(el => { el.style.display = !q || el.dataset.annSearch.toLowerCase().includes(q) ? '' : 'none'; });
  // Filter modules
  const modItems = document.querySelectorAll(`#modList-${courseId} [data-mod-search]`);
  modItems.forEach(el => { el.style.display = !q || el.dataset.modSearch.toLowerCase().includes(q) ? '' : 'none'; });
}

function openAnnComposerCD(courseId) {
  const wrap = document.getElementById(`annComposerWrap-${courseId}`);
  if (wrap) { wrap.style.display = 'block'; wrap.scrollIntoView({behavior:'smooth',block:'nearest'}); }
}

// ── Load DB stats for course detail ─────────────────────────────────
function loadCourseStats(courseId) {
  fetch(`${window.location.pathname}?action=get_course_stats&section_id=${courseId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const s = data.stats;
      const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? '0'; };
      set(`cdStat-students-${courseId}`,  s.students     ?? '0');
      set(`cdStat-modules-${courseId}`,   s.modules      ?? '0');
      set(`cdStat-ann-${courseId}`,       s.announcements?? '0');
      set(`cdStat-acts-${courseId}`,      (parseInt(s.assignments||0) + parseInt(s.quizzes||0)));
    })
    .catch(() => {});
}

function openModuleComposer(courseId) {
  const wrap = document.getElementById(`modComposerWrap-${courseId}`);
  if (wrap) { wrap.style.display = 'block'; wrap.scrollIntoView({behavior:'smooth',block:'nearest'}); }
}
function closeModuleComposer(courseId) {
  const wrap = document.getElementById(`modComposerWrap-${courseId}`);
  if (wrap) wrap.style.display = 'none';
}

// ===== MODULE EDIT HELPERS =====
function openModEdit(modId, courseId) {
  document.getElementById(`mod-view-${modId}`).style.display = 'none';
  document.getElementById(`mod-edit-${modId}`).style.display = 'block';
}
function closeModEdit(modId) {
  document.getElementById(`mod-edit-${modId}`).style.display = 'none';
  document.getElementById(`mod-view-${modId}`).style.display = 'block';
}
function saveModEdit(modId, courseId) {
  const title = document.getElementById(`mod-edit-title-${modId}`)?.value.trim();
  const desc  = document.getElementById(`mod-edit-desc-${modId}`)?.value.trim();
  if (!title) { showToast('Module title is required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_module');
  fd.append('module_id', modId);
  fd.append('title', title);
  fd.append('description', desc || '');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        loadModules(courseId);
        showToast('Module updated!', 'success');
      } else {
        showToast(data.message || 'Failed to update module', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}
function deleteModuleCard(modId, courseId) {
  if (!confirm('Delete this module and all its lessons?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_module');
  fd.append('module_id', modId);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        loadModules(courseId);
        loadCourseStats(courseId);
        showToast('Module deleted', 'success');
      } else {
        showToast(data.message || 'Failed to delete module', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}


function postModule(courseId) {
  const title = (document.getElementById(`modTitle-${courseId}`)?.value || '').trim();
  const desc  = (document.getElementById(`modDesc-${courseId}`)?.value || '').trim();
  if (!title) { showToast('Please enter a module title', 'error'); return; }

  const fileInput = document.getElementById(`modFile-${courseId}`);
  const fd = new FormData();
  fd.append('action', 'post_module');
  fd.append('section_id', courseId);
  fd.append('title', title);
  fd.append('description', desc);
  if (fileInput && fileInput.files[0]) fd.append('module_file', fileInput.files[0]);

  fetch(window.location.pathname, { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Module posted successfully!', 'success');
        document.getElementById(`modTitle-${courseId}`).value = '';
        document.getElementById(`modDesc-${courseId}`).value = '';
        const fi = document.getElementById(`modFile-${courseId}`);
        if (fi) fi.value = '';
        closeModuleComposer(courseId);
        loadModules(courseId);
        loadCourseStats(courseId);
      } else {
        showToast(data.message || 'Failed to post module', 'error');
      }
    })
    .catch(() => showToast('Network error — could not post module', 'error'));
}

function loadModules(courseId) {
  const listEl = document.getElementById(`modList-${courseId}`);
  if (!listEl) return;

  fetch(`${window.location.pathname}?action=get_modules&section_id=${courseId}`)
    .then(r => r.json())
    .then(data => {
      const mods = data.modules || [];
      if (mods.length === 0) {
        listEl.innerHTML = `<div style="text-align:center;padding:36px 16px;color:var(--text-3)"><div style="font-size:36px;margin-bottom:8px">📭</div><div style="font-size:13px;font-weight:600;color:var(--text-2)">No modules yet</div><div style="font-size:11px;margin-top:4px">Post your first module to get started.</div></div>`;
        // Update modules stat counter to 0
        const mEl = document.getElementById(`cdStat-modules-${courseId}`);
        if (mEl) mEl.textContent = '0';
        return;
      }
      const fileIcon = url => {
        const ext = (url || '').split('.').pop().toLowerCase();
        if (['mp4','mov','avi'].includes(ext)) return '🎬';
        if (['mp3','wav'].includes(ext)) return '🎵';
        if (ext === 'pdf') return '📄';
        if (['ppt','pptx'].includes(ext)) return '📊';
        if (['doc','docx'].includes(ext)) return '📝';
        if (['jpg','jpeg','png','gif','webp'].includes(ext)) return '🖼️';
        if (ext === 'zip') return '🗜️';
        return '📎';
      };
      listEl.innerHTML = mods.map((m, i) => {
        const filesHtml = (m.files && m.files.length > 0)
          ? `<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px">` +
            m.files.map(f => `
              <a href="${f.resource_url}" target="_blank" download
                 style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border:1.5px solid var(--border);border-radius:20px;background:var(--surface-2);font-size:11px;font-weight:600;color:var(--primary);text-decoration:none;transition:.2s"
                 onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                ${fileIcon(f.resource_url)} ${f.title}
              </a>`).join('') +
            `</div>` : '';
        const searchText = `${m.title} ${m.description||''}`.toLowerCase();
        const safeModTitle = (m.title||'').replace(/"/g,'&quot;');
        const safeModDesc  = (m.description||'').replace(/"/g,'&quot;');
        return `
        <div data-mod-search="${searchText.replace(/"/g,'&quot;')}"
             style="border:1.5px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:10px;background:var(--surface);transition:.2s" id="mod-card-${m.id}">
          <!-- View mode -->
          <div id="mod-view-${m.id}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
              <div style="display:flex;align-items:center;gap:9px;flex:1;min-width:0">
                <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0">${i + 1}</div>
                <div style="flex:1;min-width:0">
                  <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;line-height:1.3">${m.title}</div>
                  ${m.description ? `<div style="font-size:11px;color:var(--text-2);margin-top:2px;line-height:1.5">${m.description}</div>` : ''}
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
                <span class="badge ${m.is_published ? 'badge-green' : 'badge-amber'}" style="font-size:10px">${m.is_published ? '● Published' : '◌ Draft'}</span>
                <button onclick="openModEdit(${m.id},${courseId})" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:13px;padding:3px 7px;border-radius:6px;transition:.2s" title="Edit Module" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-3)'">✏️</button>
                <button onclick="deleteModuleCard(${m.id},${courseId})" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:15px;padding:2px 6px;border-radius:6px;transition:.2s" title="Delete Module" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;margin-top:9px;font-size:11px;color:var(--text-3)">
              <span>📖 ${m.lesson_count} lesson${m.lesson_count != 1 ? 's' : ''}</span>
              ${m.files && m.files.length ? `<span>📎 ${m.files.length} file${m.files.length!=1?'s':''}</span>` : ''}
              ${m.published_at ? `<span>📅 ${new Date(m.published_at).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</span>` : ''}
            </div>
            ${filesHtml}
          </div>
          <!-- Edit mode -->
          <div id="mod-edit-${m.id}" style="display:none">
            <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:10px">✏️ Edit Module</div>
            <div class="form-group">
              <label class="form-label">Module Title</label>
              <input type="text" id="mod-edit-title-${m.id}" value="${safeModTitle}" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px">
            </div>
            <div class="form-group">
              <label class="form-label">Description <span style="color:var(--text-3);font-weight:400">(optional)</span></label>
              <textarea id="mod-edit-desc-${m.id}" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:70px">${m.description||''}</textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:6px">
              <button class="btn btn-outline btn-sm" onclick="closeModEdit(${m.id})">Cancel</button>
              <button class="btn btn-primary btn-sm" onclick="saveModEdit(${m.id},${courseId})">💾 Save Changes</button>
            </div>
          </div>
        </div>`;
      }).join('');
      // Update modules stat counter live
      const published = mods.filter(m => m.is_published).length;
      const mEl = document.getElementById(`cdStat-modules-${courseId}`);
      if (mEl) mEl.textContent = published;
    })
    .catch(() => {
      if (listEl) listEl.innerHTML = `<div style="text-align:center;padding:28px;color:var(--danger);font-size:13px">⚠️ Could not load modules.</div>`;
    });
}



function renderAnnCard(a, courseId, fmtDate) {
  if (!fmtDate) fmtDate = d => {
    const diff = Date.now() - new Date(d).getTime();
    const days = Math.floor(diff/86400000);
    const hrs  = Math.floor((diff%86400000)/3600000);
    if (days > 0) return days === 1 ? 'Yesterday' : `${days} days ago`;
    if (hrs > 0)  return `${hrs}h ago`;
    return 'Just now';
  };
  const _annSearch = `${a.title} ${a.body}`.toLowerCase().replace(/"/g,"&quot;");
  const safeTitle = (a.title||'').replace(/"/g,'&quot;');
  const safeBody  = (a.body||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  return `
    <div data-ann-search="${_annSearch}" style="border:1.5px solid ${a.pinned?'var(--primary)':'var(--border)'};border-radius:var(--radius);padding:18px;margin-bottom:12px;background:${a.pinned?'var(--primary-light)':'var(--surface)'};transition:.2s" id="ann-${a.id}-${courseId}">
      <!-- View mode -->
      <div id="ann-view-${a.id}-${courseId}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            ${a.pinned?'<span style="font-size:11px;font-weight:700;background:var(--primary);color:#fff;padding:2px 8px;border-radius:20px">\u{1F4CC} Pinned</span>':''}
            <div style="font-family:\'Syne\',sans-serif;font-size:15px;font-weight:700">${a.title}</div>
          </div>
          <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
            <button onclick="openAnnEdit(${a.id},${courseId})" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:13px;padding:3px 7px;border-radius:6px;transition:.2s" title="Edit" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-3)'">\u{270F}\uFE0F</button>
            <button onclick="deleteAnnouncement(${courseId},${a.id})" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:16px;padding:2px 6px;border-radius:6px;transition:.2s" title="Delete" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-3)'">\u2715</button>
          </div>
        </div>
        <div style="font-size:13px;color:var(--text-2);line-height:1.6;margin-bottom:12px;white-space:pre-line">${a.body}</div>
        <div style="display:flex;align-items:center;gap:12px;font-size:11px;color:var(--text-3)">
          <span>\u{1F464} ${a.author}</span>
          <span>\u{1F550} ${fmtDate(a.date)}</span>
        </div>
      </div>
      <!-- Edit mode -->
      <div id="ann-edit-${a.id}-${courseId}" style="display:none">
        <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:10px">\u270F\uFE0F Edit Announcement</div>
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" id="ann-edit-title-${a.id}-${courseId}" value="${safeTitle}" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px">
        </div>
        <div class="form-group">
          <label class="form-label">Message</label>
          <textarea id="ann-edit-body-${a.id}-${courseId}" style="width:100%;padding:9px 11px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:80px">${a.body||''}</textarea>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
          <input type="checkbox" id="ann-edit-pin-${a.id}-${courseId}" ${a.pinned?'checked':''}>
          <label for="ann-edit-pin-${a.id}-${courseId}" style="font-size:12px;cursor:pointer">\u{1F4CC} Pin this announcement</label>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:6px">
          <button class="btn btn-outline btn-sm" onclick="closeAnnEdit(${a.id},${courseId})">Cancel</button>
          <button class="btn btn-primary btn-sm" onclick="saveAnnEdit(${a.id},${courseId})">\u{1F4BE} Save Changes</button>
        </div>
      </div>
    </div>`;
}

function openAnnModal(courseId) {
  const wrap = document.getElementById(`annComposerWrap-${courseId}`);
  if (wrap) { wrap.style.display='block'; wrap.scrollIntoView({behavior:'smooth',block:'nearest'}); }
  else { showToast('Reload the course page first','error'); }
}
function closeAnnComposer(courseId) {
  const wrap = document.getElementById(`annComposerWrap-${courseId}`);
  if (wrap) wrap.style.display='none';
}

// ===== ANNOUNCEMENT EDIT HELPERS =====
function openAnnEdit(annId, courseId) {
  document.getElementById(`ann-view-${annId}-${courseId}`).style.display = 'none';
  document.getElementById(`ann-edit-${annId}-${courseId}`).style.display = 'block';
}
function closeAnnEdit(annId, courseId) {
  document.getElementById(`ann-edit-${annId}-${courseId}`).style.display = 'none';
  document.getElementById(`ann-view-${annId}-${courseId}`).style.display = 'block';
}
function saveAnnEdit(annId, courseId) {
  const title  = document.getElementById(`ann-edit-title-${annId}-${courseId}`)?.value.trim();
  const body   = document.getElementById(`ann-edit-body-${annId}-${courseId}`)?.value.trim();
  const pinned = document.getElementById(`ann-edit-pin-${annId}-${courseId}`)?.checked || false;
  if (!title) { showToast('Title is required', 'error'); return; }
  if (!body)  { showToast('Message is required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_announcement');
  fd.append('ann_id', annId);
  fd.append('title', title);
  fd.append('body', body);
  if (pinned) fd.append('pinned', '1');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const ann = (courseAnnouncements[courseId] || []).find(a => a.id === annId);
        if (ann) { ann.title = title; ann.body = body; ann.pinned = pinned; }
        const listEl = document.getElementById(`annList-${courseId}`);
        if (listEl) {
          const fmtDate = d => { const diff=Date.now()-new Date(d); const days=Math.floor(diff/86400000); const hrs=Math.floor((diff%86400000)/3600000); if(days>0) return days===1?'Yesterday':`${days} days ago`; if(hrs>0) return `${hrs}h ago`; return 'Just now'; };
          listEl.innerHTML = courseAnnouncements[courseId].map(a=>renderAnnCard(a,courseId,fmtDate)).join('');
        }
        showToast('Announcement updated!', 'success');
      } else {
        showToast(data.message || 'Failed to update', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}


function postAnnouncement(courseId) {
  const title  = document.getElementById(`annTitle-${courseId}`)?.value.trim();
  const body   = document.getElementById(`annBody-${courseId}`)?.value.trim();
  const pinned = document.getElementById(`annPin-${courseId}`)?.checked || false;
  if (!title) { showToast('Please enter an announcement title','error'); return; }
  if (!body)  { showToast('Please write a message','error'); return; }
  const fd = new FormData();
  fd.append('action','post_announcement');
  fd.append('section_id', courseId);
  fd.append('title', title);
  fd.append('body', body);
  if (pinned) fd.append('pinned','1');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const newAnn = { id: data.id || Date.now(), title, body, author:'Prof. <?php echo htmlspecialchars($instructor_name); ?>', date: new Date().toISOString(), pinned };
        if (!courseAnnouncements[courseId]) courseAnnouncements[courseId]=[];
        if (pinned) courseAnnouncements[courseId].unshift(newAnn);
        else courseAnnouncements[courseId].push(newAnn);
        const listEl = document.getElementById(`annList-${courseId}`);
        if (listEl) {
          const fmtDate = d => { const diff=Date.now()-new Date(d); const days=Math.floor(diff/86400000); const hrs=Math.floor((diff%86400000)/3600000); if(days>0) return days===1?'Yesterday':`${days} days ago`; if(hrs>0) return `${hrs}h ago`; return 'Just now'; };
          listEl.innerHTML = courseAnnouncements[courseId].map(a=>renderAnnCard(a,courseId,fmtDate)).join('');
        }
        closeAnnComposer(courseId);
        document.getElementById(`annTitle-${courseId}`).value='';
        document.getElementById(`annBody-${courseId}`).value='';
        loadCourseStats(courseId);
        showToast('Announcement posted!','success');
      } else {
        showToast(data.message || 'Failed to post announcement','error');
      }
    })
    .catch(() => showToast('Network error — announcement not saved','error'));
}

function deleteAnnouncement(courseId, annId) {
  if (!confirm('Delete this announcement?')) return;
  const fd = new FormData();
  fd.append('action','delete_announcement');
  fd.append('ann_id', annId);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        courseAnnouncements[courseId] = (courseAnnouncements[courseId]||[]).filter(a=>a.id!==annId);
        const listEl = document.getElementById(`annList-${courseId}`);
        if (listEl) {
          if (courseAnnouncements[courseId].length===0) {
            listEl.innerHTML = `<div style="text-align:center;padding:40px 20px;color:var(--text-3)"><div style="font-size:40px;margin-bottom:10px">📭</div><div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No announcements yet</div><div style="font-size:12px">Post an announcement to notify all students in this course.</div></div>`;
          } else {
            const fmtDate = d => { const diff=Date.now()-new Date(d); const days=Math.floor(diff/86400000); const hrs=Math.floor((diff%86400000)/3600000); if(days>0) return days===1?'Yesterday':`${days} days ago`; if(hrs>0) return `${hrs}h ago`; return 'Just now'; };
            listEl.innerHTML = courseAnnouncements[courseId].map(a=>renderAnnCard(a,courseId,fmtDate)).join('');
          }
        }
        loadCourseStats(courseId);
        showToast('Announcement deleted');
      } else {
        showToast(data.message || 'Failed to delete announcement','error');
      }
    })
    .catch(() => showToast('Network error — try again','error'));
}

// ===== RECENT ASSIGNMENTS DATA (loaded from DB) =====
let recentAssignments = <?php echo $js_assignments_json; ?>;

// ── Assignment panel filter state ────────────────────────────
let asgPanelCourse = 'all';
let asgPanelStatus = 'all';

function setAsgCourseChip(el, val) {
  asgPanelCourse = val;
  document.querySelectorAll('.asg-course-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterAsgPanel();
}
function setAsgStatusChip(el, val) {
  asgPanelStatus = val;
  document.querySelectorAll('.asg-status-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterAsgPanel();
}

function filterAsgPanel() {
  const q      = (document.getElementById('asgSearchInput')?.value || '').toLowerCase();
  const list   = document.getElementById('asgPanelList');
  const empty  = document.getElementById('asgPanelEmpty');
  const countEl = document.getElementById('asgPanelCount');
  if (!list) return;

  let data = recentAssignments.filter(a => {
    const matchCourse = asgPanelCourse === 'all' || a.course === asgPanelCourse;
    const matchStatus = asgPanelStatus === 'all' || a.status === asgPanelStatus;
    const matchQ      = !q || a.title.toLowerCase().includes(q) || a.course.toLowerCase().includes(q);
    return matchCourse && matchStatus && matchQ;
  });

  if (countEl) countEl.textContent = data.length === recentAssignments.length
    ? `${recentAssignments.length} assignments` : `${data.length} of ${recentAssignments.length} shown`;

  if (data.length === 0) {
    list.innerHTML = '';
    if (empty) empty.style.display = 'flex';
    return;
  }
  if (empty) empty.style.display = 'none';

  const asgStatusBadge = s => s === 'published' ? 'badge-green' : s === 'draft' ? 'badge-amber' : 'badge-gray';
  const asgStatusLabel = s => s === 'published' ? '● Published' : s === 'draft' ? '◌ Draft' : '✕ Closed';

  list.innerHTML = data.map(a => {
    const pct = a.total > 0 ? Math.round(a.submissions / a.total * 100) : 0;
    const realIdx = recentAssignments.indexOf(a);
    return `
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);transition:.15s" id="asg-card-${a.id}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:5px;cursor:pointer" onclick="openAssignmentDetail(${realIdx})">
          <div style="font-size:13px;font-weight:600;line-height:1.4;flex:1">${a.title}</div>
          <span class="badge ${asgStatusBadge(a.status)}" style="font-size:10px;white-space:nowrap;flex-shrink:0">${asgStatusLabel(a.status)}</span>
        </div>
        <div style="font-size:11px;color:var(--text-3);margin-bottom:7px;display:flex;gap:8px;flex-wrap:wrap;cursor:pointer" onclick="openAssignmentDetail(${realIdx})">
          <span>📚 ${a.course}</span><span>📅 ${a.due}</span><span>⭐ ${a.points} pts</span>
        </div>
        ${a.status !== 'draft'
          ? `<div style="display:flex;align-items:center;gap:8px;cursor:pointer" onclick="openAssignmentDetail(${realIdx})">
               <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:${pct}%"></div></div>
               <span style="font-size:11px;color:var(--text-2);white-space:nowrap">${a.submissions}/${a.total}</span>
             </div>`
          : `<div style="font-size:11px;color:var(--accent);font-weight:600;cursor:pointer" onclick="openAssignmentDetail(${realIdx})">◌ Not yet published</div>`}
        <div style="display:flex;gap:6px;margin-top:8px">
          <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 8px" onclick="event.stopPropagation();openEditAssignmentModal(${realIdx})" title="Edit assignment">✏️ Edit</button>
          <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 8px;color:var(--danger)" onclick="event.stopPropagation();deleteAssignment(${realIdx})" title="Delete assignment">✕ Delete</button>
        </div>
      </div>`;
  }).join('');
}

function renderInstAssignments() {
  const courses = [...new Set(recentAssignments.map(a => a.course))];

  return `
    <div class="page-header"><h1>Create Assignment</h1><p>Post a new assignment for your students</p></div>
    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

      <!-- ── LEFT: Create form ── -->
      <div class="card">
        <div class="form-group"><label class="form-label">Assignment Title</label><div class="input-wrap"><input type="text" placeholder="e.g. Lab Activity 5: Exception Handling"></div></div>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Course</label><select style="width:100%"><option>Object-Oriented Programming (IT 106)</option><option>Web Programming (IT 301)</option><option>Data Structures and Algorithms (IT 201)</option></select></div>
          <div class="form-group"><label class="form-label">Due Date</label><input type="date" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit"></div>
        </div>
        <div class="form-group"><label class="form-label">Instructions</label><textarea style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);resize:vertical;min-height:120px;font-family:inherit;font-size:13px" placeholder="Write the assignment instructions here..."></textarea></div>
        <div class="form-group">
          <label class="form-label">Attachments (optional)</label>
          <input type="file" id="instAsgFileInput" multiple accept=".pdf,.docx,.doc,.zip,.pptx,.xlsx,.py,.java,.cpp,.txt,.png,.jpg" style="display:none" onchange="instHandleAsgFiles(this)">
          <div class="upload-zone"
            onclick="document.getElementById('instAsgFileInput').click()"
            ondragover="event.preventDefault();this.style.borderColor='var(--primary)';this.style.background='var(--primary-light)'"
            ondragleave="this.style.borderColor='';this.style.background=''"
            ondrop="instHandleAsgDrop(event)">
            <div style="font-size:36px;margin-bottom:8px">📎</div>
            <div style="font-weight:600">Click to browse or drag &amp; drop files</div>
            <div style="font-size:12px;color:var(--text-2);margin-top:4px">PDF, DOCX, ZIP, images — Max 50MB each</div>
          </div>
          <div id="instAsgFileList" style="margin-top:10px;display:flex;flex-direction:column;gap:6px"></div>
        </div>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Max Points</label><div class="input-wrap"><input type="number" placeholder="100" value="100"></div></div>
          <div class="form-group"><label class="form-label">Submission Type</label><select style="width:100%"><option>File Upload</option><option>Online Text</option><option>Both</option></select></div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px" onclick="instPublishAssignment()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Publish
          </button>
          <button class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px" onclick="saveDraftAssignment()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Save Draft
          </button>
        </div>
      </div>

      <!-- ── RIGHT: Recent Assignments ── -->
      <div>
        <div class="card" style="padding:0;overflow:hidden">

          <!-- Panel header -->
          <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
              <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700">Recent Assignments</div>
              <span class="badge badge-pink" style="font-size:10px">${recentAssignments.filter(a=>a.status==='published').length} live</span>
            </div>

            <!-- Search -->
            <div style="position:relative;margin-bottom:10px">
              <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;pointer-events:none">🔍</span>
              <input id="asgSearchInput" type="text" placeholder="Search assignments…"
                style="width:100%;padding:7px 10px 7px 30px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:12px;transition:.2s"
                oninput="filterAsgPanel()"
                onfocus="this.style.borderColor='var(--primary)'"
                onblur="this.style.borderColor='var(--border)'">
            </div>

            <!-- Course chips -->
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px">
              <button class="asg-course-chip rec-chip active" data-c="all" onclick="setAsgCourseChip(this,'all')">All</button>
              ${courses.map(c => {
                const short = c.split(' ')[0];
                return `<button class="asg-course-chip rec-chip" data-c="${c}" onclick="setAsgCourseChip(this,'${c.replace(/'/g,"\\'")}')">${short}</button>`;
              }).join('')}
            </div>

            <!-- Status chips -->
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="asg-status-chip rec-chip active" onclick="setAsgStatusChip(this,'all')">All Status</button>
              <button class="asg-status-chip rec-chip" onclick="setAsgStatusChip(this,'published')">● Published</button>
              <button class="asg-status-chip rec-chip" onclick="setAsgStatusChip(this,'draft')">◌ Draft</button>
              <button class="asg-status-chip rec-chip" onclick="setAsgStatusChip(this,'closed')">✕ Closed</button>
            </div>
          </div>

          <!-- Summary strip -->
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center;border-bottom:1px solid var(--border)">
            <div style="padding:10px 4px;border-right:1px solid var(--border)">
              <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--primary)">${recentAssignments.filter(a=>a.status==='published').length}</div>
              <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Published</div>
            </div>
            <div style="padding:10px 4px;border-right:1px solid var(--border)">
              <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--accent)">${recentAssignments.filter(a=>a.status==='draft').length}</div>
              <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Drafts</div>
            </div>
            <div style="padding:10px 4px">
              <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--text-2)">${recentAssignments.filter(a=>a.status==='closed').length}</div>
              <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Closed</div>
            </div>
          </div>

          <!-- Result count -->
          <div id="asgPanelCount" style="font-size:11px;color:var(--text-3);padding:6px 18px 0"></div>

          <!-- Assignment list (populated by filterAsgPanel) -->
          <div id="asgPanelList" style="max-height:460px;overflow-y:auto"></div>

          <!-- Empty state -->
          <div id="asgPanelEmpty" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:32px 20px;color:var(--text-3)">
            <div style="font-size:32px;margin-bottom:8px">📭</div>
            <div style="font-size:13px;font-weight:600;color:var(--text-2)">No assignments match</div>
            <div style="font-size:11px;margin-top:4px">Try a different filter or search term</div>
          </div>

          <!-- Footer -->
          <div style="padding:10px 18px;border-top:1px solid var(--border);text-align:center">
            <button class="btn btn-ghost btn-sm" style="width:100%;font-size:12px" onclick="navigate('submissions')">View All Submissions →</button>
          </div>
        </div>
      </div>

    </div>`;
}

// ===== RECENT QUIZZES DATA (loaded from DB) =====
let recentQuizzes = <?php echo $js_quizzes_json; ?>;

// ── Refresh quiz list from server ────────────────────────────────
async function refreshQuizzesFromDB() {
  try {
    const res  = await fetch(window.location.pathname + '?action=get_quizzes');
    const data = await res.json();
    if (data.success && Array.isArray(data.quizzes)) {
      recentQuizzes = data.quizzes.map(q => ({
        id        : parseInt(q.id),
        title     : q.title,
        course    : q.course_title ? q.course_title + ' (' + (q.section_code||'') + ')' : (q.course_label || ''),
        questions : parseInt(q.question_count) || 0,
        timeLimit : (parseInt(q.time_limit_min) || 0) + ' min',
        status    : q.status || 'draft',
        attempts  : parseInt(q.attempt_count) || 0,
        total     : parseInt(q.enrolled_count) || 0,
        avgScore  : parseInt(q.avg_score)  || 0,
        section_id: parseInt(q.section_id),
      }));
      filterQzPanel();
    }
  } catch(e) { console.warn('Could not refresh quizzes:', e); }
}



// ── Quiz panel filter state ───────────────────────────────────
let qzPanelCourse = 'all';
let qzPanelStatus = 'all';

function setQzCourseChip(el, val) {
  qzPanelCourse = val;
  document.querySelectorAll('.qz-course-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterQzPanel();
}
function setQzStatusChip(el, val) {
  qzPanelStatus = val;
  document.querySelectorAll('.qz-status-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterQzPanel();
}

function filterQzPanel() {
  const q      = (document.getElementById('qzSearchInput')?.value || '').toLowerCase();
  const list   = document.getElementById('qzPanelList');
  const empty  = document.getElementById('qzPanelEmpty');
  const countEl = document.getElementById('qzPanelCount');
  if (!list) return;

  let data = recentQuizzes.filter(qz => {
    const matchCourse = qzPanelCourse === 'all' || qz.course === qzPanelCourse;
    const matchStatus = qzPanelStatus === 'all' || qz.status === qzPanelStatus;
    const matchQ      = !q || qz.title.toLowerCase().includes(q) || qz.course.toLowerCase().includes(q);
    return matchCourse && matchStatus && matchQ;
  });

  if (countEl) countEl.textContent = data.length === recentQuizzes.length
    ? `${recentQuizzes.length} quizzes` : `${data.length} of ${recentQuizzes.length} shown`;

  if (data.length === 0) {
    list.innerHTML = '';
    if (empty) empty.style.display = 'flex';
    return;
  }
  if (empty) empty.style.display = 'none';

  const qzStatusBadge = s => s === 'published' ? 'badge-green' : s === 'draft' ? 'badge-amber' : 'badge-gray';
  const qzStatusLabel = s => s === 'published' ? '● Live' : s === 'draft' ? '◌ Draft' : '✕ Closed';

  list.innerHTML = data.map(qz => {
    const realIdx = recentQuizzes.indexOf(qz);
    const pct = qz.total > 0 ? Math.round(qz.attempts / qz.total * 100) : 0;
    const scoreColor = qz.avgScore >= 85 ? 'var(--secondary)' : qz.avgScore >= 70 ? 'var(--accent)' : qz.avgScore > 0 ? 'var(--danger)' : 'var(--text-3)';
    return `
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);transition:.15s" id="qz-card-${qz.id}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:5px;cursor:pointer" onclick="openQuizDetail(${realIdx})">
          <div style="font-size:13px;font-weight:600;line-height:1.4;flex:1">${qz.title}</div>
          <span class="badge ${qzStatusBadge(qz.status)}" style="font-size:10px;white-space:nowrap;flex-shrink:0">${qzStatusLabel(qz.status)}</span>
        </div>
        <div style="font-size:11px;color:var(--text-3);margin-bottom:7px;display:flex;gap:8px;flex-wrap:wrap;cursor:pointer" onclick="openQuizDetail(${realIdx})">
          <span>📚 ${qz.course}</span><span>❓ ${qz.questions} Qs</span><span>⏱ ${qz.timeLimit}</span>
        </div>
        ${qz.status !== 'draft' ? `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:pointer" onclick="openQuizDetail(${realIdx})">
          <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:${pct}%"></div></div>
          <span style="font-size:11px;color:var(--text-2);white-space:nowrap">${qz.attempts}/${qz.total} taken</span>
        </div>
        <div style="display:flex;align-items:center;gap:4px;cursor:pointer" onclick="openQuizDetail(${realIdx})">
          <span style="font-size:11px;color:var(--text-3)">Avg score:</span>
          <span style="font-size:12px;font-weight:700;color:${scoreColor}">${qz.avgScore > 0 ? qz.avgScore + '%' : '—'}</span>
        </div>` : `<div style="font-size:11px;color:var(--accent);font-weight:600;cursor:pointer" onclick="openQuizDetail(${realIdx})">◌ Not yet published</div>`}
        <div style="display:flex;gap:6px;margin-top:8px">
          <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 8px" onclick="event.stopPropagation();openEditQuizModal(${realIdx})" title="Edit quiz">✏️ Edit</button>
          <button class="btn btn-ghost btn-sm" style="font-size:11px;padding:3px 8px;color:var(--danger)" onclick="event.stopPropagation();deleteQuiz(${realIdx})" title="Delete quiz">✕ Delete</button>
        </div>
      </div>`;
  }).join('');
}

// ===== QUIZ STUDENT RESULT SNAPSHOTS (loaded from DB) =====
let quizResultSnapshots = <?php echo $js_quiz_results_json; ?>;

function openQuizDetail(idx) {
  const qz = recentQuizzes[idx];
  if (!qz) return;

  const results = quizResultSnapshots[qz.id] || [];
  const completed = results.filter(r => r.status === 'Completed');
  const missing   = results.filter(r => r.status === 'Missing');
  const pct = qz.total > 0 ? Math.round(qz.attempts / qz.total * 100) : 0;

  const scores = completed.map(r => r.score);
  const highest = scores.length ? Math.max(...scores) : 0;
  const lowest  = scores.length ? Math.min(...scores) : 0;
  const passing = scores.filter(s => s >= 75).length;

  const qzStatusBadge = s => s==='published'?'badge-green':s==='draft'?'badge-amber':'badge-gray';
  const qzStatusLabel = s => s==='published'?'● Live':s==='draft'?'◌ Draft':'✕ Closed';
  const scoreColor = s => s >= 85 ? '#22C55E' : s >= 75 ? 'var(--accent)' : s > 0 ? 'var(--danger)' : 'var(--text-3)';

  // score bands for distribution bar
  const band = (lo, hi) => scores.filter(s => s >= lo && s <= hi).length;
  const b90 = band(90,100), b80 = band(80,89), b70 = band(70,79), b60 = band(60,69), bFail = band(0,59);
  const bMax = Math.max(b90, b80, b70, b60, bFail, 1);

  let overlay = document.getElementById('quizDetailOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'quizDetailOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px';
    overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };
    document.body.appendChild(overlay);
  }

  overlay.innerHTML = `
    <div style="background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:660px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-md);animation:fadeUp .25s ease">

      <!-- Header -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px">
        <div style="flex:1;min-width:0">
          <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;margin-bottom:8px;line-height:1.3">🧪 ${qz.title}</div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span class="badge ${qzStatusBadge(qz.status)}">${qzStatusLabel(qz.status)}</span>
            <span style="font-size:12px;color:var(--text-2)">📚 ${qz.course}</span>
            <span style="font-size:12px;color:var(--text-2)">❓ ${qz.questions} questions</span>
            <span style="font-size:12px;color:var(--text-2)">⏱ ${qz.timeLimit}</span>
          </div>
        </div>
        <button onclick="document.getElementById('quizDetailOverlay').remove()"
          style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:22px;line-height:1;flex-shrink:0;padding:2px 6px;border-radius:8px;transition:.2s"
          onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
      </div>

      ${qz.status === 'draft' ? `
        <div style="text-align:center;padding:40px 20px;color:var(--text-3)">
          <div style="font-size:40px;margin-bottom:12px">📝</div>
          <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:6px">This quiz is still a draft</div>
          <div style="font-size:12px;margin-bottom:20px">Publish it to make it available to students.</div>
          <button class="btn btn-primary btn-sm" onclick="document.getElementById('quizDetailOverlay').remove();showToast('Opening Quiz Builder…');navigate('inst-quizzes')">📤 Go to Quiz Builder</button>
        </div>` : `

      <!-- Summary stats grid -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px">
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:13px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--primary)">${qz.attempts}/${qz.total}</div>
          <div style="font-size:10px;color:var(--text-2);margin-top:3px">Completed</div>
          <div style="margin-top:6px"><div class="progress-bar" style="height:3px"><div class="progress-fill" style="width:${pct}%"></div></div></div>
        </div>
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:13px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:${scoreColor(qz.avgScore)}">${qz.avgScore > 0 ? qz.avgScore+'%' : '—'}</div>
          <div style="font-size:10px;color:var(--text-2);margin-top:3px">Avg Score</div>
        </div>
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:13px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#22C55E">${highest > 0 ? highest+'%' : '—'}</div>
          <div style="font-size:10px;color:var(--text-2);margin-top:3px">Highest</div>
        </div>
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:13px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--danger)">${lowest > 0 ? lowest+'%' : '—'}</div>
          <div style="font-size:10px;color:var(--text-2);margin-top:3px">Lowest</div>
        </div>
      </div>

      <!-- Score distribution -->
      ${scores.length > 0 ? `
      <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:16px;margin-bottom:20px">
        <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;margin-bottom:14px">Score Distribution</div>
        <div style="display:flex;align-items:flex-end;gap:8px;height:70px">
          ${[['90–100','#22C55E',b90],['80–89','var(--secondary)',b80],['70–79','var(--accent)',b70],['60–69','#f97316',b60],['0–59','var(--danger)',bFail]].map(([label,color,count])=>`
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
              <div style="font-size:10px;color:var(--text-2);font-weight:700">${count}</div>
              <div style="width:100%;background:${color};border-radius:4px 4px 0 0;height:${Math.round(count/bMax*52)+4}px;min-height:4px;transition:.5s"></div>
              <div style="font-size:9px;color:var(--text-3);text-align:center;white-space:nowrap">${label}</div>
            </div>`).join('')}
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:10px;font-size:11px;color:var(--text-3)">
          <span>✅ Passing (≥75%): <strong style="color:var(--text)">${passing} students</strong></span>
          <span>❌ Failing (&lt;75%): <strong style="color:var(--danger)">${completed.length - passing} students</strong></span>
        </div>
      </div>` : ''}

      <!-- Student results table -->
      <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;margin-bottom:12px">Student Results</div>
      ${results.length === 0
        ? `<div style="text-align:center;padding:32px;color:var(--text-3)">
            <div style="font-size:36px;margin-bottom:10px">📭</div>
            <div style="font-size:13px;font-weight:600;color:var(--text-2)">No submissions yet</div>
           </div>`
        : `<div class="table-wrap">
            <table>
              <thead><tr><th>Student</th><th>Score</th><th>Time</th><th>Status</th></tr></thead>
              <tbody>
                ${results.map(r => `
                  <tr>
                    <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar-sm">${r.initials}</div><span style="font-weight:600">${r.name}</span></div></td>
                    <td>
                      ${r.status === 'Completed'
                        ? `<div style="display:flex;align-items:center;gap:8px">
                            <div class="progress-bar" style="width:60px;height:6px"><div class="progress-fill" style="width:${r.score}%;background:${scoreColor(r.score)}"></div></div>
                            <strong style="color:${scoreColor(r.score)}">${r.score}%</strong>
                           </div>`
                        : `<span style="color:var(--text-3)">—</span>`}
                    </td>
                    <td style="font-size:12px;color:var(--text-2)">${r.time}</td>
                    <td><span class="badge ${r.status==='Completed'?(r.score>=75?'badge-green':'badge-red'):'badge-gray'}">${r.status==='Completed'?(r.score>=75?'✓ Passed':'✗ Failed'):r.status}</span></td>
                  </tr>`).join('')}
              </tbody>
            </table>
           </div>`}
      `}

      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('quizDetailOverlay').remove()">Close</button>
        ${qz.status !== 'draft' ? `<button class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:5px" onclick="document.getElementById('quizDetailOverlay').remove();exportQuizResultsCSV(${recentQuizzes.indexOf(qz)})"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export Results</button>` : ''}
      </div>
    </div>`;
}

function renderInstQuizzes() {
  const qzCourses = [...new Set(recentQuizzes.map(q => q.course))];

  return `
    <div class="page-header"><h1>Quiz Builder</h1><p>Build and publish quizzes for your students</p></div>
    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

      <!-- ── LEFT: Builder form ── -->
      <div class="card">
        <div class="form-group"><label class="form-label">Quiz Title</label>
          <div class="input-wrap"><input type="text" id="qz-title" placeholder="e.g. Web Technologies Quiz 4"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group"><label class="form-label">Course</label>
            <select id="qz-course" style="width:100%">
              ${instCourseData.map(c=>`<option value="${c.name} (${c.code})">${c.name} (${c.code})</option>`).join('')}
            </select>
          </div>
          <div class="form-group"><label class="form-label">Time Limit</label>
            <select id="qz-time" style="width:100%">
              <option>10 minutes</option>
              <option>30 minutes</option>
              <option>60 minutes</option>
              <option>No limit</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group"><label class="form-label">Due Date <span style="color:var(--text-3);font-weight:400">(optional)</span></label>
            <div class="input-wrap"><input type="datetime-local" id="qz-due" style="width:100%"></div>
          </div>
          <div class="form-group"><label class="form-label">Max Score</label>
            <div class="input-wrap"><input type="number" id="qz-maxscore" value="100" min="1" max="1000" style="width:100%"></div>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Description / Topics <span style="color:var(--text-3);font-weight:400">(shown to students)</span></label>
          <div class="input-wrap"><input type="text" id="qz-desc" placeholder="e.g. Covers loops, functions, and OOP basics"></div>
        </div>
        <div id="qz-questions-wrap"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
          <button class="btn btn-outline btn-sm" onclick="qzAddQuestion('mc')">＋ Multiple Choice</button>
          <button class="btn btn-outline btn-sm" onclick="qzAddQuestion('tf')">＋ True / False</button>
          <button class="btn btn-outline btn-sm" onclick="qzAddQuestion('id')">＋ Identification</button>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px" onclick="qzPublish()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Publish Quiz</button>
          <button class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px" onclick="saveDraftQuiz()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg> Save Draft</button>
        </div>
      </div>

      <!-- ── RIGHT: Recent Quizzes ── -->
      <div>
        <div class="card" style="padding:0;overflow:hidden">

          <!-- Panel header -->
          <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
              <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700">Recent Quizzes</div>
              <span class="badge badge-blue" style="font-size:10px">${recentQuizzes.filter(q=>q.status==='published').length} live</span>
            </div>

            <!-- Search -->
            <div style="position:relative;margin-bottom:10px">
              <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;pointer-events:none">🔍</span>
              <input id="qzSearchInput" type="text" placeholder="Search quizzes…"
                style="width:100%;padding:7px 10px 7px 30px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:12px;transition:.2s"
                oninput="filterQzPanel()"
                onfocus="this.style.borderColor='var(--primary)'"
                onblur="this.style.borderColor='var(--border)'">
            </div>

            <!-- Course chips -->
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px">
              <button class="qz-course-chip rec-chip active" onclick="setQzCourseChip(this,'all')">All</button>
              ${qzCourses.map(c => {
                const short = c.split(' ')[0];
                return `<button class="qz-course-chip rec-chip" onclick="setQzCourseChip(this,'${c.replace(/'/g,"\\'")}')">${short}</button>`;
              }).join('')}
            </div>

            <!-- Status chips -->
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <button class="qz-status-chip rec-chip active" onclick="setQzStatusChip(this,'all')">All Status</button>
              <button class="qz-status-chip rec-chip" onclick="setQzStatusChip(this,'published')">● Live</button>
              <button class="qz-status-chip rec-chip" onclick="setQzStatusChip(this,'draft')">◌ Draft</button>
              <button class="qz-status-chip rec-chip" onclick="setQzStatusChip(this,'closed')">✕ Closed</button>
            </div>
          </div>

          <!-- Summary strip -->
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center;border-bottom:1px solid var(--border)">
            <div style="padding:10px 4px;border-right:1px solid var(--border)">
              <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--primary)">${recentQuizzes.filter(q=>q.status==='published').length}</div>
              <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Live</div>
            </div>
            <div style="padding:10px 4px;border-right:1px solid var(--border)">
              <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--accent)">${recentQuizzes.filter(q=>q.status==='draft').length}</div>
              <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Drafts</div>
            </div>
            <div style="padding:10px 4px">
              <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--text-2)">${recentQuizzes.filter(q=>q.status==='closed').length}</div>
              <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Closed</div>
            </div>
          </div>

          <!-- Result count -->
          <div id="qzPanelCount" style="font-size:11px;color:var(--text-3);padding:6px 18px 0"></div>

          <!-- Quiz list (populated by filterQzPanel) -->
          <div id="qzPanelList" style="max-height:460px;overflow-y:auto"></div>

          <!-- Empty state -->
          <div id="qzPanelEmpty" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:32px 20px;color:var(--text-3)">
            <div style="font-size:32px;margin-bottom:8px">🔍</div>
            <div style="font-size:13px;font-weight:600;color:var(--text-2)">No quizzes match</div>
            <div style="font-size:11px;margin-top:4px">Try a different filter or search term</div>
          </div>

          <!-- Footer -->
          <div style="padding:10px 18px;border-top:1px solid var(--border);text-align:center">
            <button class="btn btn-ghost btn-sm" style="width:100%;font-size:12px" onclick="navigate('quiz-results')">View All Quiz Results →</button>
          </div>
        </div>
      </div>

    </div>`;
}

// ===== QUIZ BUILDER STATE =====
let qzQuestions = [];

function qzAddQuestion(type) {
  const id = Date.now();
  qzQuestions.push({ id, type, text:'', options:['','','',''], correct:0, answer:'' });
  qzRender();
}

function qzRemove(id) {
  qzQuestions = qzQuestions.filter(q => q.id !== id);
  qzRender();
}

function qzRender() {
  const wrap = document.getElementById('qz-questions-wrap');
  if (!wrap) return;
  if (qzQuestions.length === 0) {
    wrap.innerHTML = `<div style="text-align:center;padding:32px;color:var(--text-3);border:2px dashed var(--border);border-radius:var(--radius);margin-bottom:16px">
      <div style="font-size:32px;margin-bottom:8px">📋</div>
      <div style="font-size:13px">No questions yet — add one below</div>
    </div>`;
    return;
  }
  wrap.innerHTML = qzQuestions.map((q, idx) => {
    const typeLabel = q.type === 'mc' ? '🔘 Multiple Choice' : q.type === 'tf' ? '✅ True / False' : '✏️ Identification';
    const typeBadge = q.type === 'mc' ? 'badge-blue' : q.type === 'tf' ? 'badge-green' : 'badge-amber';
    let answerUI = '';
    if (q.type === 'mc') {
      answerUI = ['A','B','C','D'].map((l,i) => `
        <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
          <div style="width:28px;height:28px;border-radius:8px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">${l}</div>
          <input type="text" placeholder="Option ${l}" value="${q.options[i]||''}"
            oninput="qzSetOption(${q.id},${i},this.value)"
            style="flex:1;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
          <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:var(--text-2);cursor:pointer;white-space:nowrap">
            <input type="radio" name="ans_${q.id}" ${q.correct===i?'checked':''} onchange="qzSetCorrect(${q.id},${i})">Correct
          </label>
        </div>`).join('');
    } else if (q.type === 'tf') {
      answerUI = `
        <div style="display:flex;gap:12px;margin-top:8px">
          <label style="display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:var(--radius-sm);border:1.5px solid ${q.answer==='true'?'var(--primary)':'var(--border)'};background:${q.answer==='true'?'var(--primary-light)':'var(--surface-2)'};cursor:pointer;font-size:13px;font-weight:600;transition:.2s">
            <input type="radio" name="tf_${q.id}" value="true" ${q.answer==='true'?'checked':''} onchange="qzSetAnswer(${q.id},'true')" style="accent-color:var(--primary)"> ✅ True
          </label>
          <label style="display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:var(--radius-sm);border:1.5px solid ${q.answer==='false'?'var(--danger)':'var(--border)'};background:${q.answer==='false'?'rgba(216,64,64,0.08)':'var(--surface-2)'};cursor:pointer;font-size:13px;font-weight:600;transition:.2s">
            <input type="radio" name="tf_${q.id}" value="false" ${q.answer==='false'?'checked':''} onchange="qzSetAnswer(${q.id},'false')" style="accent-color:var(--danger)"> ❌ False
          </label>
        </div>`;
    } else {
      answerUI = `
        <div style="margin-top:8px">
          <input type="text" placeholder="Expected answer (students must match this exactly)" value="${q.answer||''}"
            oninput="qzSetAnswer(${q.id},this.value)"
            style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
          <div style="font-size:11px;color:var(--text-3);margin-top:5px">💡 Tip: Use simple keywords; matching is case-insensitive</div>
        </div>`;
    }
    return `
      <div style="border:1.5px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:14px;background:var(--surface)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <div style="width:26px;height:26px;border-radius:8px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0">${idx+1}</div>
          <span class="badge ${typeBadge}" style="font-size:11px">${typeLabel}</span>
          <button onclick="qzRemove(${q.id})" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--text-3);font-size:16px;line-height:1;padding:2px 6px;border-radius:6px;transition:.2s" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
        </div>
        <input type="text" placeholder="Enter your question here…" value="${q.text||''}"
          oninput="qzSetText(${q.id},this.value)"
          style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;margin-bottom:4px">
        ${answerUI}
      </div>`;
  }).join('');
}

function qzSetText(id, val)         { const q=qzQuestions.find(x=>x.id===id); if(q) q.text=val; }
function qzSetOption(id, i, val)    { const q=qzQuestions.find(x=>x.id===id); if(q) q.options[i]=val; }
function qzSetCorrect(id, i)        { const q=qzQuestions.find(x=>x.id===id); if(q){ q.correct=i; qzRender(); } }
function qzSetAnswer(id, val)       { const q=qzQuestions.find(x=>x.id===id); if(q){ q.answer=val; qzRender(); } }

function qzPublish() {
  const title = document.getElementById('qz-title')?.value.trim();
  if (!title) { showToast('Please enter a quiz title','error'); return; }
  if (qzQuestions.length === 0) { showToast('Add at least one question first','error'); return; }
  const courseLabel = document.getElementById('qz-course')?.value || '';
  const timeLimit   = document.getElementById('qz-time')?.value || '30 minutes';
  const courseObj   = instCourseData.find(c => (c.name + ' (' + c.code + ')') === courseLabel);
  const section_id  = courseObj ? courseObj.id : 0;
  if (!section_id) { showToast('Please select a valid course','error'); return; }
  const fd = new FormData();
  fd.append('action','save_quiz');
  fd.append('title', title);
  fd.append('section_id', section_id);
  fd.append('time_limit', timeLimit);
  fd.append('status','published');
  fd.append('questions', JSON.stringify(qzQuestions));
  fd.append('description', document.getElementById('qz-desc')?.value.trim() || '');
  fd.append('due_date',    document.getElementById('qz-due')?.value || '');
  fd.append('max_score',   document.getElementById('qz-maxscore')?.value || '100');
  showToast('Publishing quiz…');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        qzQuestions = [];
        document.getElementById('qz-title').value = '';
        if (document.getElementById('qz-desc'))     document.getElementById('qz-desc').value = '';
        if (document.getElementById('qz-due'))      document.getElementById('qz-due').value = '';
        if (document.getElementById('qz-maxscore')) document.getElementById('qz-maxscore').value = '100';
        showToast('Quiz published! 🎉', 'success');
        refreshQuizzesFromDB().then(() => renderContent('inst-quizzes'));
      } else {
        showToast(data.message || 'Error saving quiz', 'error');
      }
    })
    .catch(err => {
      showToast('Network error — check connection', 'error');
      console.error(err);
    });
}


// ===== SUBMISSIONS DATA (loaded from DB) =====
let allSubmissions = <?php echo $js_submissions_json; ?>;

let subFilterCourse = 'all';
let subFilterStatus = 'all';

function setSubCourseFilter(el, val) {
  subFilterCourse = val;
  document.querySelectorAll('.sub-course-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterSubmissionsTable();
}
function setSubStatusFilter(el, val) {
  subFilterStatus = val;
  document.querySelectorAll('.sub-status-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterSubmissionsTable();
}

function filterSubmissionsTable() {
  const q      = (document.getElementById('subSearch')?.value || '').toLowerCase();
  const tbody  = document.getElementById('subTableBody');
  const countEl= document.getElementById('subCount');
  const emptyEl= document.getElementById('subEmpty');
  if (!tbody) return;

  let data = allSubmissions.map((r, i) => ({ ...r, _idx: i })).filter(r => {
    const matchCourse = subFilterCourse === 'all' || r.course === subFilterCourse;
    const matchStatus = subFilterStatus === 'all' || r.status === subFilterStatus;
    const matchQ      = !q || r.name.toLowerCase().includes(q)
                            || r.assignment.toLowerCase().includes(q)
                            || r.course.toLowerCase().includes(q);
    return matchCourse && matchStatus && matchQ;
  });

  if (countEl) countEl.textContent = data.length === allSubmissions.length
    ? `${allSubmissions.length} submissions`
    : `${data.length} of ${allSubmissions.length} shown`;

  if (data.length === 0) {
    tbody.innerHTML = '';
    if (emptyEl) emptyEl.style.display = 'block';
    return;
  }
  if (emptyEl) emptyEl.style.display = 'none';

  const badgeClass = s => s==='Submitted'?'badge-blue':s==='Graded'?'badge-green':s==='Late'?'badge-amber':'badge-red';
  const actionLabel = s => (s==='Submitted'||s==='Late') ? '✏️ Grade' : s==='Graded' ? '👁 View' : '—';

  tbody.innerHTML = data.map(r => `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="avatar-sm">${r.initials}</div>
          <div>
            <div style="font-weight:600;font-size:13px">${r.name}</div>
            <div style="font-size:11px;color:var(--text-3)">${r.course}</div>
          </div>
        </div>
      </td>
      <td style="font-size:12px">${r.assignment}</td>
      <td style="font-size:12px;color:var(--text-2)">${r.submitted}</td>
      <td><span class="badge ${badgeClass(r.status)}">${r.status}</span></td>
      <td><strong style="color:${r.grade==='—'?'var(--text-3)':'var(--text)'}">${r.grade}</strong></td>
      <td>
        ${r.status !== 'Missing'
          ? `<button class="btn btn-ghost btn-sm" style="color:var(--primary)" onclick="openSubmissionDetail(${r._idx})">${actionLabel(r.status)}</button>`
          : `<span style="font-size:12px;color:var(--text-3)">—</span>`}
      </td>
    </tr>`).join('');
}

// ===== SUBMISSION DETAIL / GRADE MODAL =====
function openSubmissionDetail(idx) {
  const r = allSubmissions[idx];
  if (!r) return;
  const isGradeable = r.status === 'Submitted' || r.status === 'Late';
  const badgeClass = s => s==='Submitted'?'badge-blue':s==='Graded'?'badge-green':s==='Late'?'badge-amber':'badge-red';

  let overlay = document.getElementById('subDetailOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'subDetailOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px';
    overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };
    document.body.appendChild(overlay);
  }

  const gradeVal = r.grade !== '—' ? r.grade.replace('/100','') : '';

  overlay.innerHTML = `
    <div style="background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:560px;max-height:88vh;overflow-y:auto;box-shadow:var(--shadow-md);animation:fadeUp .25s ease">

      <!-- Header -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px">
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;margin-bottom:6px">
            ${isGradeable ? '✏️ Grade Submission' : '👁 View Submission'}
          </div>
          <span class="badge ${badgeClass(r.status)}">${r.status}</span>
        </div>
        <button onclick="document.getElementById('subDetailOverlay').remove()"
          style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:22px;line-height:1;flex-shrink:0;padding:2px 6px;border-radius:8px;transition:.2s"
          onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
      </div>

      <!-- Student info -->
      <div style="display:flex;align-items:center;gap:14px;padding:16px;background:var(--surface-2);border-radius:var(--radius);margin-bottom:16px;border:1px solid var(--border)">
        <div class="user-avatar" style="width:44px;height:44px;font-size:15px;border-radius:12px">${r.initials}</div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:14px">${r.name}</div>
          <div style="font-size:12px;color:var(--text-2);margin-top:2px">${r.course}</div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:11px;color:var(--text-3)">Submitted</div>
          <div style="font-size:12px;font-weight:600;margin-top:2px">${r.submitted}</div>
        </div>
      </div>

      <!-- Assignment info -->
      <div style="padding:14px 16px;border:1.5px solid var(--border);border-radius:var(--radius);margin-bottom:16px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:6px">Assignment</div>
        <div style="font-size:14px;font-weight:600">${r.assignment}</div>
      </div>

      <!-- File attachment -->
      ${r.file ? `
      <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border:1.5px solid var(--border);border-radius:var(--radius);margin-bottom:16px;background:var(--surface-2)">
        <span style="font-size:24px">${r.file.endsWith('.pdf')?'📄':r.file.endsWith('.docx')?'📝':'🗜️'}</span>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${r.file}</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:2px">Submitted file</div>
        </div>
        <button class="btn btn-outline btn-sm" onclick="showToast('Downloading ${r.file}…','success')">⬇ Download</button>
      </div>` : ''}

      <!-- Grade input (always shown, editable when gradeable) -->
      <div style="padding:16px;border:1.5px solid ${isGradeable?'var(--primary)':'var(--border)'};border-radius:var(--radius);margin-bottom:16px;background:${isGradeable?'var(--primary-light)':'var(--surface-2)'}">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:${isGradeable?'var(--primary)':'var(--text-3)'};margin-bottom:10px">Grade (out of 100)</div>
        <div style="display:flex;align-items:center;gap:10px">
          <input type="number" id="subGradeInput" min="0" max="100" value="${gradeVal}"
            ${isGradeable?'':'readonly'}
            placeholder="0–100"
            style="width:100px;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid ${isGradeable?'var(--primary)':'var(--border)'};background:var(--surface);color:var(--text);font-family:inherit;font-size:16px;font-weight:700;text-align:center;${isGradeable?'':'opacity:.75'}">
          <span style="font-size:14px;color:var(--text-2)">/&nbsp;100</span>
          ${!isGradeable && r.grade !== '—' ? `<span style="margin-left:auto;font-size:20px;font-weight:800;color:var(--primary)">${r.grade}</span>` : ''}
        </div>
        <div style="margin-top:10px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:6px">Feedback / Comment</div>
          <textarea id="subCommentInput" ${isGradeable?'':'readonly'}
            style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:13px;resize:vertical;min-height:80px;${isGradeable?'':'opacity:.75'}"
            placeholder="Write feedback for the student…">${r.comment}</textarea>
        </div>
      </div>

      <!-- Footer buttons -->
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('subDetailOverlay').remove()">Cancel</button>
        ${isGradeable ? `<button class="btn btn-primary btn-sm" onclick="saveSubmissionGrade(${idx})">💾 Save Grade</button>` : ''}
      </div>
    </div>`;
}

function saveSubmissionGrade(idx) {
  const gradeInput   = document.getElementById('subGradeInput');
  const commentInput = document.getElementById('subCommentInput');
  const val = parseFloat(gradeInput?.value);
  if (isNaN(val) || val < 0 || val > 100) {
    showToast('Enter a valid grade (0–100)', 'error'); return;
  }
  const sub = allSubmissions[idx];
  if (!sub) return;
  const fd = new FormData();
  fd.append('action','save_grade');
  fd.append('submission_id', sub.id || 0);
  fd.append('score', val);
  fd.append('feedback', commentInput?.value.trim() || '');
  fetc
h(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      sub.grade   = val + '/100';
      sub.status  = 'Graded';
      sub.comment = commentInput?.value.trim() || '';
      document.getElementById('subDetailOverlay')?.remove();
      filterSubmissionsTable();
      showToast(data.success ? 'Grade saved! ✓' : (data.message || 'Saved locally'), 'success');
    })
    .catch(() => {
      sub.grade   = val + '/100';
      sub.status  = 'Graded';
      sub.comment = commentInput?.value.trim() || '';
      document.getElementById('subDetailOverlay')?.remove();
      filterSubmissionsTable();
      showToast('Grade saved locally (offline)', 'warning');
    });
}

function renderSubmissions() {
  subFilterCourse = 'all';
  subFilterStatus = 'all';
  const uniqueCourses = [...new Set(allSubmissions.map(r => r.course))];
  setTimeout(filterSubmissionsTable, 10);
  return `
    <div class="page-header"><h1>Student Submissions</h1><p>Review and grade student work</p></div>

    <!-- Search + course chips -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap">
      <div style="position:relative;flex:1;min-width:200px;max-width:320px">
        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:14px;pointer-events:none">🔍</span>
        <input id="subSearch" type="text" placeholder="Search students, assignments…"
          style="width:100%;padding:9px 12px 9px 34px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;transition:.2s"
          oninput="filterSubmissionsTable()"
          onfocus="this.style.borderColor='var(--primary)'"
          onblur="this.style.borderColor='var(--border)'">
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="sub-course-chip rec-chip active" onclick="setSubCourseFilter(this,'all')">All Courses</button>
        ${uniqueCourses.map(c => {
          const short = c.split(' (')[0];
          return `<button class="sub-course-chip rec-chip" onclick="setSubCourseFilter(this,'${c.replace(/'/g,"\\'")}')"> ${short}</button>`;
        }).join('')}
      </div>
    </div>

    <!-- Status chips -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
      <button class="sub-status-chip rec-chip active" onclick="setSubStatusFilter(this,'all')">All</button>
      <button class="sub-status-chip rec-chip" onclick="setSubStatusFilter(this,'Submitted')">📥 Submitted</button>
      <button class="sub-status-chip rec-chip" onclick="setSubStatusFilter(this,'Graded')">✅ Graded</button>
      <button class="sub-status-chip rec-chip" onclick="setSubStatusFilter(this,'Late')">⚠️ Late</button>
      <button class="sub-status-chip rec-chip" onclick="setSubStatusFilter(this,'Missing')">❌ Missing</button>
    </div>

    <div class="card">
      <div id="subCount" style="font-size:12px;color:var(--text-3);margin-bottom:10px"></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Student</th><th>Assignment</th><th>Submitted</th><th>Status</th><th>Grade</th><th>Action</th></tr></thead>
          <tbody id="subTableBody"></tbody>
        </table>
      </div>
      <div id="subEmpty" style="display:none;text-align:center;padding:40px 20px;color:var(--text-3)">
        <div style="font-size:36px;margin-bottom:10px">🔍</div>
        <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No submissions found</div>
        <div style="font-size:12px">Try a different search or filter</div>
      </div>
    </div>`;
}

// ===== DISCUSSIONS DATA (loaded from DB, timestamps converted to Date objects) =====
let discussionData = (<?php echo $js_discussions_json; ?>).map(d => ({
  ...d,
  createdAt: new Date(d.createdAt),
  replies: d.replies.map(r => ({ ...r, time: new Date(r.time) }))
}));

const currentUserId = <?php echo $current_user_id; ?>;

let currentDiscussionId = null;
let selectedCourseFilter = null;

function renderDiscussions() {
  setTimeout(() => {
    renderDiscussionsList();
    const searchEl = document.getElementById('disc-search-input');
    if (searchEl) {
      searchEl.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const filtered = discussionData.filter(d =>
          d.title.toLowerCase().includes(q) || d.author.toLowerCase().includes(q) || d.description.toLowerCase().includes(q)
        );
        renderDiscussionsList(filtered);
      });
    }
  }, 10);

  return `
    <div class="page-header"><h1>Discussions</h1><p>Manage and participate in course discussions</p></div>
    <div class="discussions-container">
      <!-- LEFT PANEL -->
      <div class="discussions-list-panel">
        <div class="discussions-header">
          <input type="text" class="discussions-search" id="disc-search-input" placeholder="Search discussions...">
          <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap">
            ${[...new Set(discussionData.map(d=>d.course))].map(c => {
              const short = c.replace(/\s*\(.*?\)\s*/g,'').split(' ').slice(0,2).join(' ');
              return `<button class="course-filter-btn" data-course="${c}" onclick="filterDiscByCourse('${c.replace(/'/g,"\\'")}',this)">${short}</button>`;
            }).join('')}
          </div>
        </div>
        <div class="discussions-list" id="disc-list-container"></div>
        <button class="new-disc-btn" onclick="openNewDiscModal()">+ New Discussion</button>
      </div>
      <!-- RIGHT PANEL -->
      <div class="discussion-detail-panel">
        <div id="disc-detail-panel" style="display:flex;flex-direction:column;height:100%;overflow:hidden">
          <div class="disc-empty-state">
            <div class="disc-empty-icon">○</div>
            <div style="font-size:14px;color:var(--text-2);margin-bottom:6px">Select a discussion to view</div>
            <div style="font-size:12px">Choose from the list on the left or create a new one</div>
          </div>
        </div>
      </div>
    </div>`;
}

function renderDiscussionsList(discussions) {
  const data = discussions || (selectedCourseFilter ? discussionData.filter(d => d.course === selectedCourseFilter) : discussionData);
  const container = document.getElementById('disc-list-container');
  if (!container) return;
  if (data.length === 0) {
    container.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-3);font-size:13px">No discussions found</div>';
    return;
  }
  container.innerHTML = data.map(d => `
    <div class="discussion-item ${currentDiscussionId === d.id ? 'active' : ''}" onclick="selectDiscussion(${d.id})">
      <div class="discussion-item-title">${d.title}</div>
      <div class="discussion-item-meta">
        <span>${d.author}</span>
        <span class="discussion-count">${d.replies.length}</span>
      </div>
      <div style="margin-top:6px"><span class="course-badge-disc">${d.course}</span></div>
    </div>`).join('');
}

function filterDiscByCourse(course, btn) {
  selectedCourseFilter = selectedCourseFilter === course ? null : course;
  document.querySelectorAll('.course-filter-btn').forEach(b => b.classList.toggle('active', b.dataset.course === selectedCourseFilter));
  renderDiscussionsList();
}

function selectDiscussion(id) {
  currentDiscussionId = id;
  renderDiscussionsList();
  renderDiscussionDetail(id);
}

function renderDiscussionDetail(id) {
  const discussion = discussionData.find(d => d.id === id);
  if (!discussion) return;
  const panel = document.getElementById('disc-detail-panel');
  const fmt = date => {
    const diff = Date.now() - date;
    const days = Math.floor(diff / 86400000);
    const hours = Math.floor((diff % 86400000) / 3600000);
    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    return 'Just now';
  };
  panel.innerHTML = `
    <div class="discussion-detail-header">
      <div class="discussion-detail-title">${discussion.title}</div>
      <div class="discussion-detail-meta">
        <span>${discussion.author}</span>
        <span>${fmt(discussion.createdAt)}</span>
        <span>${discussion.replies.length} replies</span>
        <span class="course-badge-disc">${discussion.course}</span>
      </div>
    </div>
    <div class="discussion-detail-content">
      <div class="discussion-original-post">
        <div class="post-header">
          <div class="post-avatar">${discussion.authorInitial}</div>
          <div class="post-info">
            <div class="post-author">${discussion.author}</div>
            <div class="post-time">${fmt(discussion.createdAt)}</div>
          </div>
        </div>
        <div class="post-body">${discussion.description}</div>
        <!-- Original post edit mode -->
        <div id="disc-post-edit-${discussion.id}" style="display:none;margin-top:10px">
          <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:8px">✏️ Edit Discussion</div>
          <input type="text" id="disc-edit-title-${discussion.id}" value="${(discussion.title||'').replace(/"/g,'&quot;')}" style="width:100%;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;margin-bottom:8px">
          <textarea id="disc-edit-body-${discussion.id}" style="width:100%;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:80px">${discussion.description||''}</textarea>
          <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px">
            <button class="btn btn-outline btn-sm" onclick="closeDiscPostEdit(${discussion.id})">Cancel</button>
            <button class="btn btn-primary btn-sm" onclick="saveDiscPostEdit(${discussion.id})">💾 Save Changes</button>
          </div>
        </div>
        <div class="post-actions">
          <button class="post-action-btn" onclick="likeDiscPost(this)">♥ <span>Like</span></button>
          <button class="post-action-btn" onclick="toggleDiscComposer(${discussion.id})">Reply</button>
          ${discussion.authorId === currentUserId ? `<button class="post-action-btn" onclick="openDiscPostEdit(${discussion.id})" style="font-size:12px">✏️ Edit</button>` : ''}
          ${discussion.authorId === currentUserId ? `<button class="post-action-btn" onclick="deleteDiscussion(${discussion.id})" style="font-size:12px;color:var(--danger)">✕ Delete</button>` : ''}
        </div>
        <div class="main-post-reply-composer" id="main-composer-${discussion.id}">
          <textarea class="composer-textarea" id="main-textarea-${discussion.id}" placeholder="Write a reply to this discussion..."></textarea>
          <div class="composer-actions">
            <button class="btn btn-outline btn-sm" onclick="toggleDiscComposer(${discussion.id})">Cancel</button>
            <button class="btn btn-primary btn-sm" onclick="submitMainReply(${discussion.id})">Post Reply</button>
          </div>
        </div>
      </div>
      <div class="replies-section" id="replies-${discussion.id}">
        ${discussion.replies.map(r => `
          <div class="reply-item" id="reply-card-${r.id}">
            <div class="reply-header">
              <div class="reply-avatar">${r.authorInitial}</div>
              <div class="reply-author">${r.author}</div>
              <div class="reply-time">${fmt(r.time)}</div>
            </div>
            <!-- View mode -->
            <div id="reply-view-${r.id}">
              <div class="reply-body">${r.body}</div>
              <div class="reply-actions">
                <button class="reply-action-btn ${r.liked?'liked':''}" onclick="likeDiscReply(this,${discussion.id},${r.id})">❤️ <span>${r.likes}</span></button>
                ${r.authorId === currentUserId ? `<button class="reply-action-btn" onclick="openReplyEdit(${r.id})" style="font-size:12px" title="Edit reply">✏️ Edit</button>` : ''}
                ${r.authorId === currentUserId ? `<button class="reply-action-btn" onclick="deleteDiscReply(${r.id},${discussion.id})" style="font-size:12px;color:var(--danger)" title="Delete reply">✕ Delete</button>` : ''}
              </div>
            </div>
            <!-- Edit mode -->
            <div id="reply-edit-${r.id}" style="display:none;margin-top:8px">
              <textarea id="reply-edit-ta-${r.id}" style="width:100%;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--primary);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:60px">${(r.body||'').replace(/`/g,"'")}</textarea>
              <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:6px">
                <button class="btn btn-outline btn-sm" onclick="closeReplyEdit(${r.id})">Cancel</button>
                <button class="btn btn-primary btn-sm" onclick="saveReplyEdit(${r.id},${discussion.id})">💾 Save</button>
              </div>
            </div>
          </div>`).join('')}
      </div>
    </div>`;
}

function likeDiscPost(btn) {
  btn.classList.toggle('liked');
  btn.querySelector('span').textContent = btn.classList.contains('liked') ? 'Liked' : 'Like';
}

// ===== DISCUSSION POST EDIT HELPERS =====
function openDiscPostEdit(discId) {
  document.getElementById(`disc-post-edit-${discId}`).style.display = 'block';
}
function closeDiscPostEdit(discId) {
  document.getElementById(`disc-post-edit-${discId}`).style.display = 'none';
}
function saveDiscPostEdit(discId) {
  const title = document.getElementById(`disc-edit-title-${discId}`)?.value.trim();
  const body  = document.getElementById(`disc-edit-body-${discId}`)?.value.trim();
  if (!title || !body) { showToast('Title and body are required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_discussion');
  fd.append('thread_id', discId);
  fd.append('title', title);
  fd.append('body', body);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const disc = discussionData.find(d => d.id === discId);
        if (disc) { disc.title = title; disc.description = body; }
        closeDiscPostEdit(discId);
        renderDiscussionDetail(discId);
        renderDiscussionsList();
        showToast('Discussion updated!', 'success');
      } else {
        showToast(data.message || 'Failed to update discussion', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}

// ===== DISCUSSION REPLY EDIT HELPERS =====
function openReplyEdit(replyId) {
  document.getElementById(`reply-view-${replyId}`).style.display = 'none';
  document.getElementById(`reply-edit-${replyId}`).style.display = 'block';
  const ta = document.getElementById(`reply-edit-ta-${replyId}`);
  if (ta) setTimeout(() => ta.focus(), 50);
}
function closeReplyEdit(replyId) {
  document.getElementById(`reply-edit-${replyId}`).style.display = 'none';
  document.getElementById(`reply-view-${replyId}`).style.display = 'block';
}
function saveReplyEdit(replyId, discId) {
  const body = document.getElementById(`reply-edit-ta-${replyId}`)?.value.trim();
  if (!body) { showToast('Reply cannot be empty', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_reply');
  fd.append('reply_id', replyId);
  fd.append('body', body);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const disc = discussionData.find(d => d.id === discId);
        if (disc) {
          const reply = disc.replies.find(r => r.id === replyId);
          if (reply) reply.body = body;
        }
        renderDiscussionDetail(discId);
        showToast('Reply updated!', 'success');
      } else {
        showToast(data.message || 'Failed to update reply', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}
function deleteDiscReply(replyId, discId) {
  if (!confirm('Delete this reply?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_reply');
  fd.append('reply_id', replyId);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const disc = discussionData.find(d => d.id === discId);
        if (disc) disc.replies = disc.replies.filter(r => r.id !== replyId);
        renderDiscussionDetail(discId);
        renderDiscussionsList();
        showToast('Reply deleted', 'success');
      } else {
        showToast(data.message || 'Failed to delete reply', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}


function toggleDiscComposer(id) {
  const c = document.getElementById(`main-composer-${id}`);
  if (c) {
    c.classList.toggle('active');
    if (c.classList.contains('active')) setTimeout(() => document.getElementById(`main-textarea-${id}`).focus(), 80);
  }
}

function _postReplyToServer(threadId, body) {
  const fd = new FormData();
  fd.append('action','post_reply');
  fd.append('thread_id', threadId);
  fd.append('body', body);
  return fetch(window.location.pathname, { method:'POST', body:fd }).then(r=>r.json()).catch(()=>({success:false}));
}

function submitMainReply(id) {
  const ta = document.getElementById(`main-textarea-${id}`);
  const body = ta.value.trim();
  if (!body) { showToast('Please write something first','error'); return; }
  const disc = discussionData.find(d => d.id === id);
  _postReplyToServer(id, body).then(data => {
    disc.replies.push({ id: data.reply_id || (Math.max(...disc.replies.map(r=>r.id),0)+1), author:'Prof. <?php echo htmlspecialchars($instructor_name); ?>', authorInitial:'<?php echo htmlspecialchars($initials); ?>', time:new Date(), body, likes:0, liked:false });
    ta.value = '';
    toggleDiscComposer(id);
    renderDiscussionDetail(id);
    renderDiscussionsList();
    showToast('Reply posted!','success');
  });
}

function submitDiscReply(id) {
  const ta = document.getElementById(`reply-textarea-${id}`);
  const body = ta.value.trim();
  if (!body) { showToast('Please write something first','error'); return; }
  const disc = discussionData.find(d => d.id === id);
  _postReplyToServer(id, body).then(data => {
    disc.replies.push({ id: data.reply_id || (Math.max(...disc.replies.map(r=>r.id),0)+1), author:'Prof. <?php echo htmlspecialchars($instructor_name); ?>', authorInitial:'<?php echo htmlspecialchars($initials); ?>', time:new Date(), body, likes:0, liked:false });
    ta.value = '';
    renderDiscussionDetail(id);
    renderDiscussionsList();
    showToast('Reply posted!','success');
  });
}

function likeDiscReply(btn, discId, replyId) {
  btn.classList.toggle('liked');
  const disc = discussionData.find(d => d.id === discId);
  const reply = disc.replies.find(r => r.id === replyId);
  reply.liked = btn.classList.contains('liked');
  reply.likes += reply.liked ? 1 : -1;
  btn.querySelector('span').textContent = reply.likes;
}

function openNewDiscModal() { document.getElementById('newDiscModal').classList.add('open'); }
function closeNewDiscModal() {
  document.getElementById('newDiscModal').classList.remove('open');
  document.getElementById('disc-title').value = '';
  document.getElementById('disc-body').value = '';
  document.getElementById('disc-course').value = '';
}

function createNewDiscussion() {
  const title = document.getElementById('disc-title').value.trim();
  const body = document.getElementById('disc-body').value.trim();
  const courseLabel = document.getElementById('disc-course').value;
  if (!title || !body || !courseLabel) { showToast('Please fill in all fields','error'); return; }
  // Find section_id from courseLabel
  const courseObj = instCourseData.find(c => c.name + ' (' + c.code + ')' === courseLabel || c.code === courseLabel);
  const section_id = courseObj ? courseObj.id : 0;
  const fd = new FormData();
  fd.append('action','create_discussion');
  fd.append('section_id', section_id);
  fd.append('title', title);
  fd.append('body', body);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      const newDisc = { id: data.thread_id || (Math.max(...discussionData.map(d=>d.id),0)+1), title, author:'You (Prof. <?php echo htmlspecialchars($instructor_name); ?>)', authorInitial:'<?php echo htmlspecialchars($initials); ?>', course:courseLabel, createdAt:new Date(), description:body, replies:[] };
      discussionData.unshift(newDisc);
      closeNewDiscModal();
      renderDiscussionsList();
      selectDiscussion(newDisc.id);
      showToast('Discussion created!','success');
    })
    .catch(() => {
      const newDisc = { id: Math.max(...discussionData.map(d=>d.id),0)+1, title, author:'You (Prof. <?php echo htmlspecialchars($instructor_name); ?>)', authorInitial:'<?php echo htmlspecialchars($initials); ?>', course:courseLabel, createdAt:new Date(), description:body, replies:[] };
      discussionData.unshift(newDisc);
      closeNewDiscModal();
      renderDiscussionsList();
      selectDiscussion(newDisc.id);
      showToast('Discussion created (offline)','warning');
    });
}

// ===== ANALYTICS DATA =====
const analyticsMonth = {
  gpa:'3.4', gpaChange:'↑ 0.2 this month',
  attendance:'94%', attendChange:'↑ 2%',
  submissions:'57/86', ontime:'80% on time',
  quiz:'82%', quizChange:'↑ 4%',
  weekly:[4,6.5,5,8,3,5.5,2],
  days:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
  quizScores:[72,78,85,88,92],
  quizLabels:['Q1','Q2','Q3','Q4','Q5'],
  subjects:[
    {name:'OOP (IT 106)',score:91,color:'#22C55E'},
    {name:'Web Programming (IT 301)',score:84,color:'#2563EB'},
    {name:'Data Structures (IT 201)',score:87,color:'#F59E0B'},
    {name:'Capstone (IT 411)',score:78,color:'#E06868'}
  ],
  courses:[
    {name:'OOP (IT 106)',hours:32},
    {name:'Web Programming (IT 301)',hours:24},
    {name:'Data Structures (IT 201)',hours:20},
    {name:'Capstone (IT 411)',hours:10}
  ],
  assignments:[
    {title:'Lab 5 — Exception Handling',status:'Pending Review',badge:'badge-amber',days:'Due today'},
    {title:'OOP Lab 4 — Polymorphism',status:'Graded',badge:'badge-green',grade:'87/100 avg'},
    {title:'Web Quiz 3',status:'Completed',badge:'badge-green',grade:'81% avg'},
    {title:'Capstone Proposal',status:'In Review',badge:'badge-blue',days:'Submitted'}
  ]
};
const analyticsSemester = {
  gpa:'3.7', gpaChange:'↑ 0.4 this semester',
  attendance:'91%', attendChange:'↑ 1%',
  submissions:'212/320', ontime:'85% on time',
  quiz:'84%', quizChange:'↑ 6%',
  weekly:[5,7,6,8,4,6,3],
  days:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
  quizScores:[70,75,80,88,90],
  quizLabels:['Q1','Q2','Q3','Q4','Q5'],
  subjects:[
    {name:'OOP (IT 106)',score:93,color:'#22C55E'},
    {name:'Web Programming (IT 301)',score:86,color:'#2563EB'},
    {name:'Data Structures (IT 201)',score:89,color:'#F59E0B'},
    {name:'Capstone (IT 411)',score:82,color:'#E06868'}
  ],
  courses:[
    {name:'OOP (IT 106)',hours:98},
    {name:'Web Programming (IT 301)',hours:75},
    {name:'Data Structures (IT 201)',hours:62},
    {name:'Capstone (IT 411)',hours:35}
  ],
  assignments:[
    {title:'Lab 5 — Exception Handling',status:'Graded',badge:'badge-green',grade:'90/100 avg'},
    {title:'OOP Lab 4 — Polymorphism',status:'Graded',badge:'badge-green',grade:'87/100 avg'},
    {title:'Web Quiz 3',status:'Completed',badge:'badge-green',grade:'81% avg'},
    {title:'Capstone Proposal',status:'Graded',badge:'badge-green',grade:'88/100 avg'}
  ]
};
let currentAnalyticsPeriod = 'month';

function switchAnalyticsPeriod(period, el) {
  currentAnalyticsPeriod = period;
  document.querySelectorAll('#analyticsChips .analytics-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  loadAnalyticsData(period === 'month' ? analyticsMonth : analyticsSemester);
}

function loadAnalyticsData(data) {
  document.getElementById('an-gpa').textContent = data.gpa;
  document.getElementById('an-gpa-c').textContent = data.gpaChange;
  document.getElementById('an-att').textContent = data.attendance;
  document.getElementById('an-att-c').textContent = data.attendChange;
  document.getElementById('an-sub').textContent = data.submissions;
  document.getElementById('an-sub-c').textContent = data.ontime;
  document.getElementById('an-quiz').textContent = data.quiz;
  document.getElementById('an-quiz-c').textContent = data.quizChange;
  drawWeeklyChart(data.weekly, data.days);
  drawQuizChart(data.quizScores, data.quizLabels);
  drawPerformance(data.subjects);
  drawTimeSpent(data.courses);
  drawAssignments(data.assignments);
}

function drawWeeklyChart(hours, days) {
  const max = Math.max(...hours);
  const avg = (hours.reduce((a,b)=>a+b,0)/hours.length).toFixed(1);
  const total = hours.reduce((a,b)=>a+b,0).toFixed(1);
  const gridLines = [0,25,50,75,100].reverse().map(pct=>`
    <div style="position:absolute;left:0;right:0;top:${100-pct}%;display:flex;align-items:center;gap:6px;pointer-events:none">
      <span style="font-size:10px;color:var(--text-3);width:22px;text-align:right;flex-shrink:0">${Math.round((pct/100)*max)}h</span>
      <div style="flex:1;height:1px;background:var(--border);opacity:.7"></div>
    </div>`).join('');
  const bars = hours.map((h,i)=>{
    const pct = Math.max(6,(h/max)*100);
    const isHigh = h===max;
    return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end;flex:1;gap:5px;position:relative;cursor:pointer"
      onmouseover="this.querySelector('.wt').style.opacity='1';this.querySelector('.wb').style.filter='brightness(1.15)'"
      onmouseout="this.querySelector('.wt').style.opacity='0';this.querySelector('.wb').style.filter='none'">
      <div class="wt" style="opacity:0;transition:opacity .15s;position:absolute;top:-24px;background:var(--text);color:var(--surface);font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;white-space:nowrap;pointer-events:none">${h}h</div>
      <div class="wb" style="width:min(100%,28px);height:${pct}%;background:${isHigh?'var(--primary)':'var(--surface-2)'};border:1px solid ${isHigh?'var(--primary-dark)':'var(--border)'};border-radius:4px 4px 0 0;transition:filter .15s"></div>
      <span style="font-size:10px;color:var(--text-3);font-weight:500">${days[i]}</span>
    </div>`;
  }).join('');
  const el = document.getElementById('an-weekly-chart');
  if(el) el.innerHTML = `<div style="width:100%">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
      <div style="display:flex;align-items:center;gap:5px"><div style="width:9px;height:9px;border-radius:2px;background:var(--primary)"></div><span style="font-size:11px;color:var(--text-2)">Peak</span></div>
      <div style="display:flex;align-items:center;gap:5px"><div style="width:9px;height:9px;border-radius:2px;background:var(--surface-2);border:1px solid var(--border)"></div><span style="font-size:11px;color:var(--text-2)">Other</span></div>
      <div style="margin-left:auto;font-size:11px;color:var(--text-2)">Avg <strong>${avg}h</strong> · Total <strong>${total}h</strong></div>
    </div>
    <div style="position:relative;padding-left:28px">
      <div style="position:absolute;left:0;top:0;bottom:20px;right:0;pointer-events:none">${gridLines}</div>
      <div style="display:flex;align-items:flex-end;height:130px;gap:6px;position:relative;z-index:1">${bars}</div>
    </div></div>`;
}

function drawQuizChart(scores, labels) {
  const avg = Math.round(scores.reduce((a,b)=>a+b,0)/scores.length);
  const trend = scores[scores.length-1]-scores[0];
  const trendColor = trend>=0?'#22C55E':'#EF4444';
  const barColor = s => s>=90?'#22C55E':s>=75?'var(--secondary)':'var(--accent)';
  const gridLines = [0,25,50,75,100].reverse().map(pct=>`
    <div style="position:absolute;left:0;right:0;top:${100-pct}%;display:flex;align-items:center;gap:6px;pointer-events:none">
      <span style="font-size:10px;color:var(--text-3);width:28px;text-align:right;flex-shrink:0">${pct}%</span>
      <div style="flex:1;height:1px;background:var(--border);opacity:.7"></div>
    </div>`).join('');
  const avgLine = `<div style="position:absolute;left:32px;right:0;top:${100-avg}%;pointer-events:none;z-index:2">
    <div style="border-top:1.5px dashed var(--primary);opacity:.6;position:relative">
      <span style="position:absolute;right:0;top:-16px;font-size:10px;color:var(--primary);font-weight:700;background:var(--surface);padding:0 4px">${avg}% avg</span>
    </div></div>`;
  const bars = scores.map((s,i)=>`
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end;flex:1;gap:5px;position:relative;cursor:pointer"
      onmouseover="this.querySelector('.qt').style.opacity='1';this.querySelector('.qb').style.filter='brightness(1.15)'"
      onmouseout="this.querySelector('.qt').style.opacity='0';this.querySelector('.qb').style.filter='none'">
      <div class="qt" style="opacity:0;transition:opacity .15s;position:absolute;top:-24px;background:var(--text);color:var(--surface);font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;pointer-events:none">${s}%</div>
      <div class="qb" style="width:min(100%,28px);height:${Math.max(6,s)}%;background:${barColor(s)};border-radius:4px 4px 0 0;transition:filter .15s"></div>
      <span style="font-size:10px;color:var(--text-3);font-weight:500">${labels[i]}</span>
    </div>`).join('');
  const el = document.getElementById('an-quiz-chart');
  if(el) el.innerHTML = `<div style="width:100%">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap">
      <div style="display:flex;gap:8px">
        <div style="display:flex;align-items:center;gap:4px"><div style="width:9px;height:9px;border-radius:2px;background:#22C55E"></div><span style="font-size:11px;color:var(--text-2)">≥90%</span></div>
        <div style="display:flex;align-items:center;gap:4px"><div style="width:9px;height:9px;border-radius:2px;background:var(--secondary)"></div><span style="font-size:11px;color:var(--text-2)">≥75%</span></div>
        <div style="display:flex;align-items:center;gap:4px"><div style="width:9px;height:9px;border-radius:2px;background:var(--accent)"></div><span style="font-size:11px;color:var(--text-2)">&lt;75%</span></div>
      </div>
      <div style="margin-left:auto;font-size:11px;color:var(--text-2)">Trend <strong style="color:${trendColor}">${trend>=0?'↑ +':'↓ '}${trend}pts</strong></div>
    </div>
    <div style="position:relative;padding-left:34px">
      <div style="position:absolute;left:0;top:0;bottom:20px;right:0;pointer-events:none">${gridLines}${avgLine}</div>
      <div style="display:flex;align-items:flex-end;height:130px;gap:8px;position:relative;z-index:1">${bars}</div>
    </div></div>`;
}

function drawPerformance(subjects) {
  const el = document.getElementById('an-performance');
  if(!el) return;
  el.innerHTML = subjects.map(s=>`
    <div style="display:flex;align-items:center;gap:14px;padding-bottom:14px;border-bottom:1px solid var(--border)">
      <div style="font-size:13px;font-weight:500;width:160px;flex-shrink:0;color:var(--text)">${s.name}</div>
      <div style="flex:1;height:8px;background:var(--surface-2);border-radius:100px;overflow:hidden">
        <div style="height:100%;width:${s.score}%;background:${s.color};border-radius:100px;transition:width .8s ease"></div>
      </div>
      <div style="font-size:14px;font-weight:700;color:${s.color};min-width:40px;text-align:right">${s.score}%</div>
    </div>`).join('');
}

function drawTimeSpent(courses) {
  const total = courses.reduce((a,b)=>a+b.hours,0);
  const el = document.getElementById('an-timespent');
  if(!el) return;
  el.innerHTML = courses.map(c=>`
    <div style="display:flex;align-items:center;justify-content:space-between;padding:11px;background:var(--surface-2);border-radius:10px;border:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:10px;flex:1">
        <div>
          <div style="font-size:13px;font-weight:500;color:var(--text)">${c.name}</div>
          <div style="font-size:11px;color:var(--text-3)">${((c.hours/total)*100).toFixed(0)}% of total</div>
        </div>
      </div>
      <div style="font-size:15px;font-weight:700;color:var(--text)">${c.hours}h</div>
    </div>`).join('');
}

function drawAssignments(assignments) {
  const el = document.getElementById('an-assignments');
  if(!el) return;
  el.innerHTML = assignments.map(a=>`
    <div style="display:flex;align-items:center;justify-content:space-between;padding:11px;background:var(--surface-2);border-radius:10px;border:1px solid var(--border)">
      <div>
        <div style="font-size:13px;font-weight:500;margin-bottom:3px;color:var(--text)">${a.title}</div>
        <div style="font-size:11px;color:var(--text-3)">${a.days||'Completed'}</div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="badge ${a.badge}" style="white-space:nowrap">${a.status}</span>
        ${a.grade?`<span style="font-size:13px;font-weight:700;color:var(--text)">${a.grade}</span>`:''}
      </div>
    </div>`).join('');
}

function initAnalytics() {
  loadAnalyticsData(analyticsMonth);
}

function renderAnalytics() {
  setTimeout(() => initAnalytics(), 30);
  return `
    <div class="page-header">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <div class="analytics-section-title">Learning Analytics</div>
          <div class="analytics-section-sub">Track student performance across your courses</div>
        </div>
        <div class="analytics-chip-row" id="analyticsChips">
          <button class="analytics-chip active" onclick="switchAnalyticsPeriod('month',this)">This Month</button>
          <button class="analytics-chip" onclick="switchAnalyticsPeriod('semester',this)">Semester</button>
        </div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="analytics-grid-4" style="margin-bottom:24px">
      <div class="analytics-stat-card">
        <div class="analytics-stat-label">Avg. Grade</div>
        <div class="analytics-stat-value" id="an-gpa">3.4</div>
        <div class="analytics-stat-change up" id="an-gpa-c">↑ 0.2 this month</div>
      </div>
      <div class="analytics-stat-card">
        <div class="analytics-stat-label">Attendance</div>
        <div class="analytics-stat-value" id="an-att">94%</div>
        <div class="analytics-stat-change up" id="an-att-c">↑ 2%</div>
      </div>
      <div class="analytics-stat-card">
        <div class="analytics-stat-label">Submissions</div>
        <div class="analytics-stat-value" id="an-sub">57/86</div>
        <div class="analytics-stat-change" id="an-sub-c">80% on time</div>
      </div>
      <div class="analytics-stat-card">
        <div class="analytics-stat-label">Quiz Average</div>
        <div class="analytics-stat-value" id="an-quiz">82%</div>
        <div class="analytics-stat-change up" id="an-quiz-c">↑ 4%</div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid-2" style="margin-bottom:24px">
      <div class="card" style="padding:22px">
        <div style="font-size:15px;font-weight:700;margin-bottom:4px">Weekly Engagement</div>
        <div style="font-size:12px;color:var(--text-2);margin-bottom:16px">Avg. student activity hours per day</div>
        <div id="an-weekly-chart"></div>
      </div>
      <div class="card" style="padding:22px">
        <div style="font-size:15px;font-weight:700;margin-bottom:4px">Quiz Score Trend</div>
        <div style="font-size:12px;color:var(--text-2);margin-bottom:16px">Class average per quiz</div>
        <div id="an-quiz-chart"></div>
      </div>
    </div>

    <!-- Performance by Subject -->
    <div class="card" style="padding:22px;margin-bottom:24px">
      <div style="font-size:15px;font-weight:700;margin-bottom:18px">Performance by Course</div>
      <div id="an-performance" style="display:flex;flex-direction:column;gap:0"></div>
    </div>

    <!-- Time Spent & Assignments -->
    <div class="grid-2">
      <div class="card" style="padding:22px">
        <div style="font-size:15px;font-weight:700;margin-bottom:14px">Time Spent (hours)</div>
        <div id="an-timespent" style="display:flex;flex-direction:column;gap:10px"></div>
      </div>
      <div class="card" style="padding:22px">
        <div style="font-size:15px;font-weight:700;margin-bottom:14px">Assignment Status</div>
        <div id="an-assignments" style="display:flex;flex-direction:column;gap:10px"></div>
      </div>
    </div>`;
}


// ===== RANDOMIZER & ATTENDANCE =====
function renderRandomizer() {
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Instructor</span><span>›</span><span>Randomizer & Attendance</span></div>
    <h1>Randomizer & Attendance</h1>
    <p>Pick students randomly from those marked present. Grade them after selection.</p>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">

    <!-- Attendance Panel -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700">Attendance Sheet</div>
          <div style="font-size:12px;color:var(--text-2);margin-top:2px">Mark students present before randomizing</div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-ghost btn-sm" onclick="attendanceMarkAll(true)">✓ All Present</button>
          <button class="btn btn-ghost btn-sm" onclick="attendanceMarkAll(false)">✗ Clear</button>
        </div>
      </div>

      <div style="margin-bottom:12px">
        <select id="randCourseSelect" onchange="loadCourseRoster()" style="width:100%;padding:9px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
          <option value="">— Select a Course —</option>
          <option value="IT 106">IT 106 · Object-Oriented Programming (BSIT-2A)</option>
          <option value="IT 301">IT 301 · Web Programming (BSIT-2B)</option>
          <option value="IT 201">IT 201 · Data Structures and Algorithms (BSCS-2A)</option>
          <option value="IT 411">IT 411 · Capstone Project (BSIT-4A)</option>
        </select>
      </div>

      <div id="attendanceList" style="max-height:420px;overflow-y:auto">
        <div style="text-align:center;padding:40px 0;color:var(--text-3)">
          <div style="font-size:32px;margin-bottom:8px">📋</div>
          <div style="font-size:13px">Select a course to load students</div>
        </div>
      </div>

      <div id="attendanceSummary" style="display:none;margin-top:12px;padding:10px 14px;border-radius:var(--radius-sm);background:var(--surface-2);font-size:12px;color:var(--text-2);justify-content:space-between">
        <span id="presentCount">0 present</span>
        <span id="absentCount">0 absent</span>
      </div>
    </div>

    <!-- Randomizer Panel -->
    <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:flex-start">
      <div style="width:100%;margin-bottom:16px">
        <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:4px">Student Randomizer</div>
        <div style="font-size:12px;color:var(--text-2)">Picks from present students only · Each student picked once</div>
      </div>

      <!-- Card display area -->
      <div id="randCardWrap" style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;margin:8px 0 20px">
        <div id="randCard" style="
          width:180px;height:180px;border-radius:20px;
          background:linear-gradient(135deg,var(--primary),var(--primary-dark));
          display:flex;flex-direction:column;align-items:center;justify-content:center;
          box-shadow:0 8px 32px rgba(204,58,114,.3);
          color:#fff;font-family:'Syne',sans-serif;
          transition:transform .15s ease,box-shadow .3s;
          cursor:default;user-select:none;
        ">
          <div style="font-size:40px;margin-bottom:8px">🎴</div>
          <div style="font-size:13px;font-weight:700;opacity:.8">Ready to pick</div>
        </div>
      </div>

      <!-- Stats row -->
      <div style="display:flex;gap:12px;margin-bottom:20px;width:100%">
        <div style="flex:1;background:var(--surface-2);border-radius:var(--radius-sm);padding:10px;text-align:center">
          <div style="font-size:20px;font-weight:800;font-family:'Syne',sans-serif" id="randRemaining">0</div>
          <div style="font-size:11px;color:var(--text-2)">Remaining</div>
        </div>
        <div style="flex:1;background:var(--surface-2);border-radius:var(--radius-sm);padding:10px;text-align:center">
          <div style="font-size:20px;font-weight:800;font-family:'Syne',sans-serif" id="randPicked">0</div>
          <div style="font-size:11px;color:var(--text-2)">Picked</div>
        </div>
        <div style="flex:1;background:var(--surface-2);border-radius:var(--radius-sm);padding:10px;text-align:center">
          <div style="font-size:20px;font-weight:800;font-family:'Syne',sans-serif" id="randTotal">0</div>
          <div style="font-size:11px;color:var(--text-2)">Present</div>
        </div>
      </div>

      <!-- Buttons -->
      <div style="display:flex;flex-direction:column;gap:8px;width:100%">
        <button class="btn btn-primary btn-full" id="randPickBtn" onclick="randPickStudent()" style="font-size:15px;padding:14px">
          🎲 Pick Random Student
        </button>
        <button class="btn btn-outline btn-full btn-sm" onclick="randReset()">↺ Reset Randomizer</button>
      </div>
    </div>
  </div>

  <!-- Picked Students Log with Grading -->
  <div class="card" id="pickedLogCard" style="display:none">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div>
        <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700">Picked Students · Grade Them</div>
        <div style="font-size:12px;color:var(--text-2);margin-top:2px">Students are removed from the pool once picked</div>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="exportGrades()">📥 Export Grades</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Score <span style="font-weight:400;text-transform:none;letter-spacing:0">(out of 10)</span></th>
            <th>Remarks</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="pickedTableBody"></tbody>
      </table>
    </div>
  </div>`;
}

// ===== RANDOMIZER STATE (rosters loaded from DB, keyed by course code) =====
const rosters = <?php echo $js_rosters_json; ?>;

let attendanceMap    = {};  // { studentName: true/false }
let randPool         = [];  // present students not yet picked
let randPickedList   = [];  // { name, order, score, remarks }

function loadCourseRoster() {
  const course = document.getElementById('randCourseSelect').value;
  const list   = document.getElementById('attendanceList');
  const summary = document.getElementById('attendanceSummary');

  if (!course) {
    list.innerHTML = '<div style="text-align:center;padding:40px 0;color:var(--text-3)"><div style="font-size:32px;margin-bottom:8px">📋</div><div style="font-size:13px">Select a course to load students</div></div>';
    summary.style.display = 'none';
    return;
  }

  attendanceMap = {};
  randPool = [];
  randPickedList = [];

  const students = rosters[course] || [];
  students.forEach(s => attendanceMap[s] = false);

  renderAttendanceList();
  updateRandStats();
  document.getElementById('attendanceSummary').style.display = 'flex';
  document.getElementById('pickedLogCard').style.display = 'none';
  document.getElementById('pickedTableBody').innerHTML = '';
  resetRandCard();
}

function renderAttendanceList() {
  const list = document.getElementById('attendanceList');
  const students = Object.keys(attendanceMap);
  list.innerHTML = students.map((s, i) => {
    const present = attendanceMap[s];
    const isPicked = randPickedList.some(p => p.name === s);
    return `
    <div style="display:flex;align-items:center;gap:12px;padding:9px 10px;border-radius:var(--radius-sm);margin-bottom:4px;background:${present ? 'rgba(52,211,153,.08)' : 'transparent'};border:1px solid ${present ? 'rgba(52,211,153,.25)' : 'var(--border)'}; transition:.15s">
      <label style="display:flex;align-items:center;gap:10px;cursor:${isPicked?'default':'pointer'};flex:1;${isPicked?'opacity:.5':''}">
        <input type="checkbox" ${present?'checked':''} ${isPicked?'disabled':''} onchange="toggleAttendance('${s.replace(/'/g,"\'")}', this.checked)"
          style="width:16px;height:16px;accent-color:var(--primary);cursor:${isPicked?'default':'pointer'}">
        <span style="font-size:13px;font-weight:${present?'600':'400'};color:${present?'var(--text)':'var(--text-2)'}">${s}</span>
      </label>
      ${isPicked ? '<span class="badge badge-green" style="font-size:10px">Picked</span>' : (present ? '<span style="font-size:11px;color:var(--success);font-weight:600">Present</span>' : '<span style="font-size:11px;color:var(--text-3)">Absent</span>')}
    </div>`;
  }).join('');
  updateAttendanceSummary();
}

function toggleAttendance(name, val) {
  attendanceMap[name] = val;
  syncRandPool();
  renderAttendanceList();
  updateRandStats();
}

function attendanceMarkAll(val) {
  Object.keys(attendanceMap).forEach(k => {
    if (!randPickedList.some(p => p.name === k)) attendanceMap[k] = val;
  });
  syncRandPool();
  renderAttendanceList();
  updateRandStats();
}

function syncRandPool() {
  randPool = Object.keys(attendanceMap).filter(s =>
    attendanceMap[s] && !randPickedList.some(p => p.name === s)
  );
}

function updateAttendanceSummary() {
  const total   = Object.keys(attendanceMap).length;
  const present = Object.values(attendanceMap).filter(Boolean).length;
  document.getElementById('presentCount').textContent = present + ' present';
  document.getElementById('absentCount').textContent  = (total - present) + ' absent';
}

function updateRandStats() {
  syncRandPool();
  document.getElementById('randRemaining').textContent = randPool.length;
  document.getElementById('randPicked').textContent    = randPickedList.length;
  const total = Object.values(attendanceMap).filter(Boolean).length;
  document.getElementById('randTotal').textContent     = total;
}

function randPickStudent() {
  syncRandPool();
  if (randPool.length === 0) {
    showToast(
      randPickedList.length > 0
        ? 'All present students have been picked!'
        : 'No present students to pick from. Mark attendance first.',
      'warn'
    );
    return;
  }

  // Shuffle animation
  const card = document.getElementById('randCard');
  const btn  = document.getElementById('randPickBtn');
  btn.disabled = true;

  let ticks = 0;
  const maxTicks = 14;
  const interval = setInterval(() => {
    const temp = randPool[Math.floor(Math.random() * randPool.length)];
    const firstName = temp.split(' ')[0];
    card.innerHTML = `
      <div style="font-size:28px;margin-bottom:6px">${getStudentEmoji(temp)}</div>
      <div style="font-size:13px;font-weight:800;text-align:center;padding:0 12px;line-height:1.3">${firstName}</div>`;
    card.style.transform = 'scale(1.04) rotate(' + (Math.random()*4-2) + 'deg)';
    ticks++;

    if (ticks >= maxTicks) {
      clearInterval(interval);

      // Final pick
      const finalIdx  = Math.floor(Math.random() * randPool.length);
      const picked    = randPool[finalIdx];
      randPool.splice(finalIdx, 1);
      randPickedList.push({ name: picked, order: randPickedList.length + 1, score: '', remarks: '' });

      card.style.transform = 'scale(1.08) rotate(0deg)';
      card.style.boxShadow = '0 12px 40px rgba(204,58,114,.5)';
      card.innerHTML = `
        <div style="font-size:38px;margin-bottom:8px">${getStudentEmoji(picked)}</div>
        <div style="font-size:15px;font-weight:800;text-align:center;padding:0 14px;line-height:1.3">${picked}</div>
        <div style="font-size:10px;opacity:.7;margin-top:6px">#${randPickedList.length} picked</div>`;
      setTimeout(() => {
        card.style.transform = 'scale(1) rotate(0deg)';
      }, 300);

      renderAttendanceList();
      updateRandStats();
      renderPickedTable();
      showToast('Picked: ' + picked, 'success');
      btn.disabled = false;
    }
  }, 80);
}

function getStudentEmoji(name) {
  const emojis = ['🎓','📚','✏️','🌟','🏆','💡','📖','🎯','🔬','🧠'];
  let code = 0;
  for (let i = 0; i < name.length; i++) code += name.charCodeAt(i);
  return emojis[code % emojis.length];
}

function resetRandCard() {
  const card = document.getElementById('randCard');
  if (!card) return;
  card.style.transform = '';
  card.style.boxShadow = '0 8px 32px rgba(204,58,114,.3)';
  card.innerHTML = '<div style="font-size:40px;margin-bottom:8px">🎴</div><div style="font-size:13px;font-weight:700;opacity:.8">Ready to pick</div>';
}

function randReset() {
  if (randPickedList.length > 0 && !confirm('Reset the randomizer? Picked list and grades will be cleared.')) return;
  randPickedList = [];
  syncRandPool();
  resetRandCard();
  updateRandStats();
  renderAttendanceList();
  document.getElementById('pickedLogCard').style.display = 'none';
  document.getElementById('pickedTableBody').innerHTML = '';
  showToast('Randomizer reset', '');
}

function renderPickedTable() {
  const tbody = document.getElementById('pickedTableBody');
  if (!tbody) return;
  document.getElementById('pickedLogCard').style.display = 'block';

  tbody.innerHTML = randPickedList.map((p, i) => `
    <tr>
      <td style="color:var(--text-3);font-weight:700">${p.order}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="avatar-sm">${p.name.split(' ').map(w=>w[0]).join('').slice(0,2)}</div>
          <span style="font-weight:600">${p.name}</span>
        </div>
      </td>
      <td>
        <input type="number" min="0" max="10" step="0.5"
          value="${p.score}"
          placeholder="0–10"
          onchange="updatePickedScore(${i}, this.value)"
          style="width:80px;padding:6px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;text-align:center">
      </td>
      <td>
        <input type="text"
          value="${p.remarks}"
          placeholder="Optional remark"
          onchange="updatePickedRemarks(${i}, this.value)"
          style="width:160px;padding:6px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
      </td>
      <td>
        ${p.score !== '' ? '<span class="badge badge-green">Graded</span>' : '<span class="badge badge-gray">Pending</span>'}
      </td>
    </tr>
  `).join('');
}

function updatePickedScore(idx, val) {
  randPickedList[idx].score = val;
  renderPickedTable();
}
function updatePickedRemarks(idx, val) {
  randPickedList[idx].remarks = val;
}

function exportGrades() {
  if (randPickedList.length === 0) { showToast('No grades to export yet', ''); return; }
  const course = document.getElementById('randCourseSelect').value || 'Course';
  let csv = 'Order,Student Name,Score,Remarks\n';
  randPickedList.forEach(p => {
    csv += `${p.order},"${p.name}","${p.score}","${p.remarks}"\n`;
  });
  const blob = new Blob([csv], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = course.replace(/\s/g,'_') + '_randomizer_grades.csv';
  a.click();
  showToast('Grades exported as CSV!', 'success');
}


// ===== INSTRUCTOR ASSIGNMENT FILE ATTACH =====
let instAsgFiles = [];

function instHandleAsgFiles(input) {
  Array.from(input.files).forEach(f => {
    if (!instAsgFiles.find(x => x.name === f.name)) instAsgFiles.push(f);
  });
  instRenderAsgFileList();
  input.value = ''; // reset so same file can be re-added after removal
}

function instHandleAsgDrop(e) {
  e.preventDefault();
  e.currentTarget.style.borderColor = '';
  e.currentTarget.style.background = '';
  Array.from(e.dataTransfer.files).forEach(f => {
    if (!instAsgFiles.find(x => x.name === f.name)) instAsgFiles.push(f);
  });
  instRenderAsgFileList();
}

function instRenderAsgFileList() {
  const list = document.getElementById('instAsgFileList');
  if (!list) return;
  if (instAsgFiles.length === 0) { list.innerHTML = ''; return; }
  list.innerHTML = instAsgFiles.map((f, i) => {
    const size = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : (f.size / 1024).toFixed(0) + ' KB';
    const ext  = f.name.split('.').pop().toUpperCase();
    const icon = ext === 'PDF' ? '📄' : ext === 'ZIP' ? '🗜️' : ['PNG','JPG','JPEG','GIF','WEBP'].includes(ext) ? '🖼️' : ['DOCX','DOC'].includes(ext) ? '📝' : '📎';
    return `<div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--surface-2);border:1.5px solid var(--border);border-radius:var(--radius-sm)">
      <span style="font-size:20px">${icon}</span>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${f.name}</div>
        <div style="font-size:11px;color:var(--text-3)">${ext} &nbsp;·&nbsp; ${size}</div>
      </div>
      <button onclick="instRemoveAsgFile(${i})"
        style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:16px;padding:2px 6px;border-radius:6px;transition:.2s;line-height:1"
        onmouseover="this.style.color='var(--danger)'"
        onmouseout="this.style.color='var(--text-3)'"
        title="Remove">✕</button>
    </div>`;
  }).join('');
}

function instRemoveAsgFile(i) {
  instAsgFiles.splice(i, 1);
  instRenderAsgFileList();
}

function instPublishAssignment() {
  const titleEl       = document.querySelector('#contentArea .input-wrap input[type="text"]');
  const courseEl      = document.querySelector('#contentArea select');
  const dueDateEl     = document.querySelector('#contentArea input[type="date"]');
  const instrEl       = document.querySelector('#contentArea textarea');
  const title         = titleEl  ? titleEl.value.trim() : '';
  const courseLabel   = courseEl ? courseEl.value : '';
  const due_date      = dueDateEl ? dueDateEl.value : '';
  const instructions  = instrEl  ? instrEl.value.trim() : '';
  if (!title) { showToast('Please enter an assignment title','error'); return; }
  const courseObj = instCourseData.find(c => (c.name + ' (' + c.code + ')') === courseLabel);
  const section_id = courseObj ? courseObj.id : 0;
  if (!section_id) { showToast('Please select a valid course','error'); return; }
  const fd = new FormData();
  fd.append('action','publish_assignment');
  fd.append('section_id', section_id);
  fd.append('title', title);
  fd.append('instructions', instructions);
  fd.append('due_date', due_date);
  fd.append('max_score','100');
  fd.append('status','published');
  showToast('Publishing assignment…');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      const due = due_date ? new Date(due_date).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : 'TBD';
      const enrolled = courseObj ? courseObj.students : 0;
      recentAssignments.unshift({ id: data.assignment_id || 0, title, course:courseLabel, due, status:'published', submissions:0, total:enrolled, points:100, section_id });
      if (recentAssignments.length > 12) recentAssignments.pop();
      instAsgFiles = [];
      instRenderAsgFileList();
      showToast(data.success ? 'Assignment published! 🎉' : 'Published (offline)', 'success');
      setTimeout(() => renderContent('inst-assignments'), 700);
    })
    .catch(() => {
      instAsgFiles = [];
      instRenderAsgFileList();
      showToast('Published (offline)', 'warning');
      setTimeout(() => renderContent('inst-assignments'), 700);
    });
}

// ===== SETTINGS: UPLOAD PHOTO =====
function uploadProfilePhoto(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('action', 'upload_photo');
  fd.append('photo', file);
  showToast('Uploading photo…');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || 'Photo updated!', 'success');
        const ts = Date.now();
        const imgHtml = `<img src="${data.avatar_url}?t=${ts}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">`;
        // Settings page avatar
        const initEl = document.getElementById('instAvatarInitials');
        const imgEl  = document.getElementById('instAvatarImg');
        if (initEl) initEl.style.display = 'none';
        if (imgEl)  { imgEl.src = data.avatar_url + '?t=' + ts; imgEl.style.display = 'block'; }
        // Sidebar avatar
        const sidebarAv = document.getElementById('sidebarAvatar');
        if (sidebarAv) sidebarAv.innerHTML = imgHtml;
        // Topbar avatar
        const topbarAv = document.getElementById('topbarAvatar');
        if (topbarAv) topbarAv.innerHTML = imgHtml;
      } else {
        showToast(data.message || 'Upload failed', 'error');
      }
    })
    .catch(() => showToast('Network error — could not upload photo', 'error'));
}

// ===== SETTINGS: SAVE PROFILE =====
function saveProfile() {
  const fullName    = (document.getElementById('profFullName')?.value || '').trim();
  const email       = (document.getElementById('profEmail')?.value || '').trim();
  const dept        = (document.getElementById('profDept')?.value || '').trim();
  const designation = (document.getElementById('profDesignation')?.value || '').trim();
  if (!fullName || !email) { showToast('Name and email are required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('full_name', fullName);
  fd.append('email', email);
  fd.append('department', dept);
  fd.append('designation', designation);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast(data.message || 'Profile updated!', 'success');
        const nameEl = document.getElementById('settingsDisplayName');
        if (nameEl && data.display_name) nameEl.textContent = data.display_name;
      } else {
        showToast(data.message || 'Save failed', 'error');
      }
    })
    .catch(() => showToast('Network error — could not save profile', 'error'));
}

// ===== QUIZ: EXPORT RESULTS CSV =====
function exportQuizResultsCSV(idx) {
  const qz = recentQuizzes[idx];
  const results = quizResultSnapshots[qz.id] || [];
  if (!results.length) { showToast('No results to export yet', ''); return; }
  let csv = 'Student Name,Score,Time Spent,Status\n';
  results.forEach(r => {
    const score = r.status === 'Completed' ? r.score + '%' : '—';
    csv += `"${r.name}","${score}","${r.time}","${r.status}"\n`;
  });
  const blob = new Blob([csv], { type:'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = (qz.title || 'quiz').replace(/[^a-z0-9]/gi,'_') + '_results.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  showToast('Results exported!', 'success');
}

// ===== SAVE DRAFT: QUIZ =====
function saveDraftQuiz() {
  const title      = document.getElementById('qz-title')?.value.trim();
  const courseLabel= document.getElementById('qz-course')?.value;
  const timeLimit  = document.getElementById('qz-time')?.value;
  if (!title && qzQuestions.length === 0) { showToast('Nothing to save yet', ''); return; }
  // Save to localStorage as backup
  localStorage.setItem('lf_qz_draft', JSON.stringify({ title, course:courseLabel, timeLimit, questions:qzQuestions, savedAt:new Date().toISOString() }));
  if (!title) { showToast('Draft saved locally!', 'success'); return; }
  const courseObj  = instCourseData.find(c => (c.name + ' (' + c.code + ')') === courseLabel);
  const section_id = courseObj ? courseObj.id : 0;
  if (!section_id) { showToast('Draft saved locally (select a course to save to DB)', ''); return; }
  const fd = new FormData();
  fd.append('action','save_quiz');
  fd.append('title', title);
  fd.append('section_id', section_id);
  fd.append('time_limit', timeLimit || '');
  fd.append('status','draft');
  fd.append('questions', JSON.stringify(qzQuestions));
  fd.append('description', document.getElementById('qz-desc')?.value.trim() || '');
  fd.append('due_date',    document.getElementById('qz-due')?.value || '');
  fd.append('max_score',   document.getElementById('qz-maxscore')?.value || '100');
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Draft saved!', 'success');
        refreshQuizzesFromDB().then(() => renderContent('inst-quizzes'));
      } else {
        showToast(data.message || 'Error saving draft', 'error');
      }
    })
    .catch(() => showToast('Draft saved locally!', 'success'));
}

// ===== SAVE DRAFT: ASSIGNMENT =====
function saveDraftAssignment() {
  const titleEl   = document.querySelector('#contentArea .input-wrap input[type="text"]');
  const courseEl  = document.querySelector('#contentArea select');
  const dueDateEl = document.querySelector('#contentArea input[type="date"]');
  const instrEl   = document.querySelector('#contentArea textarea');
  const title = titleEl?.value.trim() || '';
  if (!title) { showToast('Enter a title before saving', ''); return; }
  const draft = {
    title,
    course: courseEl?.value || '',
    due:    dueDateEl?.value || '',
    instructions: instrEl?.value || '',
    savedAt: new Date().toISOString()
  };
  localStorage.setItem('lf_asg_draft', JSON.stringify(draft));
  showToast('Draft saved locally!', 'success');
}

// ===== LOGOUT FUNCTION =====
function logout() {
  if (confirm('Are you sure you want to sign out?')) {
    showToast('Signing out...', 'success');
    setTimeout(() => {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'learnflow-logout.php';
      document.body.appendChild(form);
      form.submit();
    }, 800);
  }
}

// ===== MISSING HELPERS =====
function getGreeting() {
  const h = new Date().getHours();
  if (h < 12) return 'morning';
  if (h < 18) return 'afternoon';
  return 'evening';
}

// ===== QUIZ RESULTS PAGE =====
let qzrFilterCourse = 'all';
let qzrFilterStatus = 'all';
let qzrSortKey = null;
let qzrSortAsc = true;

function renderQuizResults() {
  const allCourses = [...new Set(recentQuizzes.map(q => q.course))];

  // aggregate totals across all quizzes with results
  const allScores = recentQuizzes.flatMap(qz =>
    (quizResultSnapshots[qz.id] || []).filter(r => r.status === 'Completed').map(r => r.score)
  );
  const overallAvg   = allScores.length ? Math.round(allScores.reduce((a,b)=>a+b,0)/allScores.length) : 0;
  const totalTakers  = recentQuizzes.reduce((s,q) => s + q.attempts, 0);
  const totalPassing = allScores.filter(s => s >= 75).length;
  const passRate     = allScores.length ? Math.round(totalPassing / allScores.length * 100) : 0;

  return `
    <div class="page-header">
      <div class="breadcrumb">
        <span style="cursor:pointer;color:var(--primary)" onclick="navigate('inst-quizzes')">← Quiz Builder</span>
        <span>›</span><span>Quiz Results</span>
      </div>
      <h1>Quiz Results</h1>
      <p>Overview of all quiz performance across your courses</p>
    </div>

    <!-- Summary stat cards -->
    <div class="stats-grid" style="margin-bottom:24px">
      <div class="stat-card">
        <div class="stat-icon blue">🧪</div>
        <div><div class="stat-val">${recentQuizzes.length}</div><div class="stat-label">Total Quizzes</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">👥</div>
        <div><div class="stat-val">${totalTakers}</div><div class="stat-label">Total Attempts</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">📊</div>
        <div><div class="stat-val">${overallAvg > 0 ? overallAvg+'%' : '—'}</div><div class="stat-label">Overall Avg Score</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon ${passRate >= 75 ? 'green' : passRate >= 50 ? 'amber' : 'red'}">✅</div>
        <div><div class="stat-val">${passRate > 0 ? passRate+'%' : '—'}</div><div class="stat-label">Pass Rate</div></div>
      </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
      <!-- Toolbar -->
      <div style="padding:16px 18px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
          <!-- Search -->
          <div style="position:relative;flex:1;min-width:200px;max-width:320px">
            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:14px;pointer-events:none">🔍</span>
            <input id="qzrSearch" type="text" placeholder="Search quizzes…"
              style="width:100%;padding:9px 12px 9px 32px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;transition:.2s"
              oninput="filterQzResults()"
              onfocus="this.style.borderColor='var(--primary)'"
              onblur="this.style.borderColor='var(--border)'">
          </div>
          <div id="qzrCount" style="font-size:12px;color:var(--text-3);white-space:nowrap"></div>
        </div>

        <!-- Course chips -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">
          <button class="qzr-course-chip course-filter-chip active" onclick="setQzrCourseChip(this,'all')">All Courses</button>
          ${allCourses.map(c => `<button class="qzr-course-chip course-filter-chip" onclick="setQzrCourseChip(this,'${c.replace(/'/g,"\\'")}')">${c.split(' (')[0]}</button>`).join('')}
        </div>

        <!-- Status chips -->
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button class="qzr-status-chip course-filter-chip active" onclick="setQzrStatusChip(this,'all')">All Status</button>
          <button class="qzr-status-chip course-filter-chip" onclick="setQzrStatusChip(this,'published')">● Live</button>
          <button class="qzr-status-chip course-filter-chip" onclick="setQzrStatusChip(this,'closed')">✕ Closed</button>
          <button class="qzr-status-chip course-filter-chip" onclick="setQzrStatusChip(this,'draft')">◌ Draft</button>
        </div>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <table id="qzrTable">
          <thead>
            <tr>
              <th style="cursor:pointer;user-select:none" onclick="sortQzResults('title')">Quiz <span id="qzr-sort-title" style="opacity:.4">⇅</span></th>
              <th>Course</th>
              <th>Status</th>
              <th style="cursor:pointer;user-select:none" onclick="sortQzResults('attempts')">Attempts <span id="qzr-sort-attempts" style="opacity:.4">⇅</span></th>
              <th style="cursor:pointer;user-select:none" onclick="sortQzResults('avgScore')">Avg Score <span id="qzr-sort-avgScore" style="opacity:.4">⇅</span></th>
              <th>Pass Rate</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="qzrTableBody"></tbody>
        </table>
      </div>

      <!-- Empty state -->
      <div id="qzrEmpty" style="display:none;text-align:center;padding:48px 20px;color:var(--text-3)">
        <div style="font-size:36px;margin-bottom:10px">🔍</div>
        <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No quizzes match</div>
        <div style="font-size:12px">Try a different search term or filter</div>
      </div>
    </div>`;
}

function setQzrCourseChip(btn, val) {
  qzrFilterCourse = val;
  document.querySelectorAll('.qzr-course-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterQzResults();
}
function setQzrStatusChip(btn, val) {
  qzrFilterStatus = val;
  document.querySelectorAll('.qzr-status-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterQzResults();
}
function sortQzResults(key) {
  if (qzrSortKey === key) qzrSortAsc = !qzrSortAsc;
  else { qzrSortKey = key; qzrSortAsc = true; }
  ['title','attempts','avgScore'].forEach(k => {
    const el = document.getElementById('qzr-sort-' + k);
    if (el) { el.textContent = k === key ? (qzrSortAsc ? '↑' : '↓') : '⇅'; el.style.opacity = k === key ? '1' : '.4'; }
  });
  filterQzResults();
}

function filterQzResults() {
  const q      = (document.getElementById('qzrSearch')?.value || '').toLowerCase();
  const tbody  = document.getElementById('qzrTableBody');
  const emptyEl = document.getElementById('qzrEmpty');
  const countEl = document.getElementById('qzrCount');
  if (!tbody) return;

  let data = recentQuizzes.map((qz, i) => ({ ...qz, _idx: i })).filter(qz => {
    const matchCourse = qzrFilterCourse === 'all' || qz.course === qzrFilterCourse;
    const matchStatus = qzrFilterStatus === 'all' || qz.status === qzrFilterStatus;
    const matchQ      = !q || qz.title.toLowerCase().includes(q) || qz.course.toLowerCase().includes(q);
    return matchCourse && matchStatus && matchQ;
  });

  if (qzrSortKey) {
    data.sort((a, b) => {
      const va = a[qzrSortKey], vb = b[qzrSortKey];
      return qzrSortAsc
        ? (typeof va === 'string' ? va.localeCompare(vb) : va - vb)
        : (typeof va === 'string' ? vb.localeCompare(va) : vb - va);
    });
  }

  if (countEl) countEl.textContent = data.length === recentQuizzes.length
    ? `${recentQuizzes.length} quizzes` : `Showing ${data.length} of ${recentQuizzes.length}`;

  if (data.length === 0) {
    tbody.innerHTML = '';
    if (emptyEl) emptyEl.style.display = 'block';
    return;
  }
  if (emptyEl) emptyEl.style.display = 'none';

  const qzStatusBadge = s => s==='published'?'badge-green':s==='draft'?'badge-amber':'badge-gray';
  const qzStatusLabel = s => s==='published'?'● Live':s==='draft'?'◌ Draft':'✕ Closed';
  const scoreColor = s => s >= 85 ? '#22C55E' : s >= 70 ? 'var(--accent)' : s > 0 ? 'var(--danger)' : 'var(--text-3)';

  tbody.innerHTML = data.map(qz => {
    const results  = quizResultSnapshots[qz.id] || [];
    const scores   = results.filter(r => r.status === 'Completed').map(r => r.score);
    const passing  = scores.filter(s => s >= 75).length;
    const passRate = scores.length ? Math.round(passing / scores.length * 100) : 0;
    const pct      = qz.total > 0 ? Math.round(qz.attempts / qz.total * 100) : 0;

    return `
      <tr style="cursor:pointer" onclick="openQuizDetail(${qz._idx})"
        onmouseover="this.querySelectorAll('td').forEach(td=>td.style.background='var(--surface-2)')"
        onmouseout="this.querySelectorAll('td').forEach(td=>td.style.background='')">
        <td>
          <div style="font-weight:600">${qz.title}</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:2px">❓ ${qz.questions} questions &nbsp;·&nbsp; ⏱ ${qz.timeLimit}</div>
        </td>
        <td style="font-size:12px;color:var(--text-2)">${qz.course}</td>
        <td><span class="badge ${qzStatusBadge(qz.status)}">${qzStatusLabel(qz.status)}</span></td>
        <td>
          ${qz.status !== 'draft' ? `
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bar" style="width:60px"><div class="progress-fill" style="width:${pct}%"></div></div>
              <span style="font-size:12px">${qz.attempts}/${qz.total}</span>
            </div>` : `<span style="color:var(--text-3);font-size:12px">—</span>`}
        </td>
        <td>
          <strong style="color:${scoreColor(qz.avgScore)};font-size:14px">${qz.avgScore > 0 ? qz.avgScore + '%' : '—'}</strong>
        </td>
        <td>
          ${scores.length > 0
            ? `<div style="display:flex;align-items:center;gap:8px">
                <div class="progress-bar" style="width:50px"><div class="progress-fill" style="width:${passRate}%;background:${passRate>=75?'#22C55E':'var(--accent)'}"></div></div>
                <span style="font-size:12px;font-weight:600;color:${passRate>=75?'#22C55E':'var(--accent)'}">${passRate}%</span>
               </div>`
            : `<span style="color:var(--text-3);font-size:12px">—</span>`}
        </td>
        <td>
          <button class="btn btn-ghost btn-sm" style="color:var(--primary);font-size:11px"
            onclick="event.stopPropagation();openQuizDetail(${qz._idx})">🔍 View</button>
        </td>
      </tr>`;
  }).join('');
}

// ===== NOTIFICATIONS DATA (loaded from DB) =====
let allNotifications = <?php echo $js_notifications_json; ?>;
let notifFilter = 'all';

function setNotifFilter(btn, filter) {
  notifFilter = filter;
  document.querySelectorAll('.notif-filter-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderNotifList();
}

function markAllNotifsRead() {
  allNotifications.forEach(n => n.unread = false);
  renderNotifList();
  renderTopNotifList();
  // remove nav badge
  const badge = document.querySelector('[data-nav="notifications"] .nav-badge');
  if (badge) badge.remove();
  // hide top dot and panel badge
  const dot = document.getElementById('topNotifDot');
  if (dot) dot.style.display = 'none';
  const panelBadge = document.getElementById('topNotifUnreadBadge');
  if (panelBadge) panelBadge.style.display = 'none';
  showToast('All notifications marked as read', 'success');
}

function markOneNotifRead(id) {
  const n = allNotifications.find(x => x.id === id);
  if (n && n.unread) {
    n.unread = false;
    renderNotifList();
    renderTopNotifList();
    const remaining = allNotifications.filter(x => x.unread).length;
    if (remaining === 0) {
      const badge = document.querySelector('[data-nav="notifications"] .nav-badge');
      if (badge) badge.remove();
      const dot = document.getElementById('topNotifDot');
      if (dot) dot.style.display = 'none';
      const panelBadge = document.getElementById('topNotifUnreadBadge');
      if (panelBadge) panelBadge.style.display = 'none';
    } else {
      const panelBadge = document.getElementById('topNotifUnreadBadge');
      if (panelBadge) { panelBadge.textContent = remaining; panelBadge.style.display = 'inline'; }
    }
  }
}

function renderNotifList() {
  const container = document.getElementById('notifListContainer');
  const countEl   = document.getElementById('notifCount');
  if (!container) return;

  let data = allNotifications;
  if (notifFilter === 'unread')     data = data.filter(n => n.unread);
  else if (notifFilter === 'submission') data = data.filter(n => n.type === 'submission');
  else if (notifFilter === 'discussion') data = data.filter(n => n.type === 'discussion');
  else if (notifFilter === 'enrollment') data = data.filter(n => n.type === 'enrollment');
  else if (notifFilter === 'quiz')       data = data.filter(n => n.type === 'quiz');

  const unreadCount = allNotifications.filter(n => n.unread).length;
  if (countEl) countEl.textContent = unreadCount > 0 ? `${unreadCount} unread` : 'All caught up';

  if (data.length === 0) {
    container.innerHTML = `<div style="text-align:center;padding:48px 20px;color:var(--text-3)">
      <div style="font-size:40px;margin-bottom:12px">🔕</div>
      <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No notifications here</div>
      <div style="font-size:12px">Try a different filter</div>
    </div>`;
    return;
  }

  container.innerHTML = data.map(n => `
    <div class="notif-item${n.unread?' unread':''}" onclick="markOneNotifRead(${n.id||0})">
      <div class="notif-icon" style="background:${n.bg}">${n.icon}</div>
      <div style="flex:1">
        <div style="font-size:13px;font-weight:${n.unread?'700':'500'}">${n.title}</div>
        <div class="notif-time">${n.time}</div>
      </div>
      ${n.unread ? `<div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:4px"></div>` : ''}
    </div>`).join('');
}

function renderTopNotifList() {
  const container = document.getElementById('topNotifList');
  if (!container) return;
  const items = allNotifications.slice(0, 5);
  if (items.length === 0) {
    container.innerHTML = '<div style="padding:16px;text-align:center;font-size:12px;color:var(--text-3)">No notifications</div>';
    return;
  }
  container.innerHTML = items.map(n => `
    <div class="notif-item${n.unread?' unread':''}" style="padding:10px 16px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid var(--border)">
      <div class="notif-icon" style="background:${n.bg};width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">${n.icon}</div>
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:${n.unread?'700':'500'};line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${n.title}</div>
        <div class="notif-time" style="font-size:11px;color:var(--text-3);margin-top:2px">${n.time}</div>
      </div>
      ${n.unread?'<div style="width:7px;height:7px;border-radius:50%;background:var(--danger);flex-shrink:0;margin-top:4px"></div>':''}
    </div>`).join('');
}

function renderNotifications() {
  const unreadCount = allNotifications.filter(n => n.unread).length;
  setTimeout(renderNotifList, 10);
  return `
    <div class="page-header">
      <div class="breadcrumb"><span>Home</span><span>›</span><span>Notifications</span></div>
      <h1>Notifications</h1>
      <p>Stay updated on student activity and course events</p>
    </div>
    <div class="card">
      <!-- Header row -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:10px">
          <h3 style="font-size:15px;font-weight:700">All Notifications</h3>
          <span id="notifCount" style="font-size:12px;color:var(--text-3);padding:3px 10px;background:var(--surface-2);border-radius:20px;font-weight:600">${unreadCount > 0 ? unreadCount+' unread' : 'All caught up'}</span>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="markAllNotifsRead()">✓ Mark all read</button>
      </div>

      <!-- Filter chips -->
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px">
        <button class="notif-filter-chip active" data-filter="all"        onclick="setNotifFilter(this,'all')">All</button>
        <button class="notif-filter-chip"         data-filter="unread"     onclick="setNotifFilter(this,'unread')">🔵 Unread</button>
        <button class="notif-filter-chip"         data-filter="submission" onclick="setNotifFilter(this,'submission')">📥 Submissions</button>
        <button class="notif-filter-chip"         data-filter="discussion" onclick="setNotifFilter(this,'discussion')">🗣️ Discussions</button>
        <button class="notif-filter-chip"         data-filter="enrollment" onclick="setNotifFilter(this,'enrollment')">👥 Enrollment</button>
        <button class="notif-filter-chip"         data-filter="quiz"       onclick="setNotifFilter(this,'quiz')">🧪 Quizzes</button>
      </div>

      <!-- Notification list -->
      <div id="notifListContainer"></div>
    </div>`;
}

function renderSettings() {
  const _pref = JSON.parse(localStorage.getItem('lf_inst_prefs')||'{}');
  const darkOn      = darkMode;
  const emailNotifs = _pref.emailNotifs  !== false;
  const subAlerts   = _pref.subAlerts    !== false;
  const discAlerts  = _pref.discAlerts   !== false;
  const gradeRemind = _pref.gradeRemind  === true;

  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>Settings</span></div>
    <h1>Settings</h1>
    <p>Manage your account and preferences</p>
  </div>

  <div class="grid-2">
    <!-- Left: Profile card + info -->
    <div>
      <div class="card" style="margin-bottom:16px;text-align:center">
        <div style="position:relative;width:70px;height:70px;margin:0 auto 14px">
          <div id="instAvatarInitials" style="width:70px;height:70px;border-radius:18px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#fff;overflow:hidden">
            <?php if ($instructor_photo_url): ?>
              <img src="<?php echo htmlspecialchars($instructor_photo_url); ?>" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?php echo $initials; ?>
            <?php endif; ?>
          </div>
          <img id="instAvatarImg" src="" alt="" style="display:none;width:70px;height:70px;border-radius:18px;object-fit:cover;position:absolute;top:0;left:0">
        </div>
        <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700" id="settingsDisplayName"><?php echo htmlspecialchars($instructor_name); ?></div>
        <div style="font-size:12px;color:var(--text-2);margin-top:3px"><?php echo htmlspecialchars($instructor_email); ?></div>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:10px">
          <span class="badge badge-pink">Instructor</span>
          <span class="badge badge-gray"><?php echo htmlspecialchars($instructor_dept); ?></span>
        </div>
        <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" onchange="uploadProfilePhoto(this)">
        <button class="btn btn-outline btn-sm btn-full" style="margin-top:14px" onclick="document.getElementById('photoFileInput').click()">📷 Change Photo</button>
      </div>

      <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Profile Information</h3>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-wrap"><span class="icon">👤</span><input type="text" id="profFullName" value="<?php echo htmlspecialchars($instructor_name); ?>"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap"><span class="icon">✉️</span><input type="email" id="profEmail" value="<?php echo htmlspecialchars($instructor_email); ?>"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Employee ID</label>
          <div class="input-wrap"><span class="icon">#️⃣</span><input type="text" value="<?php echo htmlspecialchars($instructor_row['employee_id'] ?? 'EMP-2024-036'); ?>" readonly style="opacity:.7"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Designation</label>
          <div class="input-wrap"><span class="icon">🎓</span><input type="text" id="profDesignation" value="<?php echo htmlspecialchars($instructor_designation); ?>" readonly style="opacity:.7"></div>
        </div>
        <button class="btn btn-primary btn-sm" onclick="saveProfile()">💾 Save Changes</button>
      </div>

      <div class="card">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Account</h3>
        <button class="btn btn-danger btn-sm" onclick="logout()" style="display:flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </button>
      </div>
    </div>

    <!-- Right: Notifications + Appearance -->
    <div>
      <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Notification Preferences</h3>
        <div class="setting-row">
          <div class="setting-info"><h4>Email Notifications</h4><p>Receive alerts via email</p></div>
          <label class="toggle"><input type="checkbox" ${emailNotifs?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_inst_prefs')||'{}');p.emailNotifs=el.checked;localStorage.setItem('lf_inst_prefs',JSON.stringify(p));showToast(el.checked?'Email notifications on':'Email notifications off','success')})(this)"><span class="toggle-slider"></span></label>
        </div>
        <div class="setting-row">
          <div class="setting-info"><h4>Submission Alerts</h4><p>Notify when students submit work</p></div>
          <label class="toggle"><input type="checkbox" ${subAlerts?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_inst_prefs')||'{}');p.subAlerts=el.checked;localStorage.setItem('lf_inst_prefs',JSON.stringify(p));showToast(el.checked?'Submission alerts on':'Submission alerts off','success')})(this)"><span class="toggle-slider"></span></label>
        </div>
        <div class="setting-row">
          <div class="setting-info"><h4>Discussion Alerts</h4><p>Notify on new forum activity</p></div>
          <label class="toggle"><input type="checkbox" ${discAlerts?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_inst_prefs')||'{}');p.discAlerts=el.checked;localStorage.setItem('lf_inst_prefs',JSON.stringify(p));showToast(el.checked?'Discussion alerts on':'Discussion alerts off','success')})(this)"><span class="toggle-slider"></span></label>
        </div>
        <div class="setting-row">
          <div class="setting-info"><h4>Grading Reminders</h4><p>Remind when assignments are ungraded 24h+</p></div>
          <label class="toggle"><input type="checkbox" ${gradeRemind?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_inst_prefs')||'{}');p.gradeRemind=el.checked;localStorage.setItem('lf_inst_prefs',JSON.stringify(p));showToast(el.checked?'Grading reminders on':'Grading reminders off','success')})(this)"><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Appearance</h3>
        <div class="setting-row">
          <div class="setting-info"><h4>Dark Mode</h4><p>Use dark theme throughout</p></div>
          <label class="toggle"><input type="checkbox" id="instDarkToggle" ${darkOn?'checked':''} onchange="toggleDark()"><span class="toggle-slider"></span></label>
        </div>
      </div>
    </div>
  </div>`;
}

// ===== EDIT / DELETE ASSIGNMENT =====
function openEditAssignmentModal(idx) {
  const a = recentAssignments[idx];
  if (!a) return;
  let overlay = document.getElementById('editAsgOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'editAsgOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:500;display:flex;align-items:center;justify-content:center;padding:20px';
    overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };
    document.body.appendChild(overlay);
  }
  overlay.innerHTML = `
    <div style="background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-md);animation:fadeUp .25s ease">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700">✏️ Edit Assignment</div>
        <button onclick="document.getElementById('editAsgOverlay').remove()" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:20px;line-height:1">✕</button>
      </div>
      
      <div class="form-group"><label class="form-label">Title</label>
        <input id="editAsgTitle" type="text" value="${(a.title||'').replace(/"/g,'&quot;')}" style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
      </div>
      
      <div class="form-group"><label class="form-label">Course</label>
        <input type="text" value="${(a.course||'').replace(/"/g,'&quot;')}" disabled style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text-2);font-family:inherit;font-size:13px;cursor:not-allowed">
      </div>
      
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Due Date</label>
          <input id="editAsgDue" type="date" value="${a.due_raw||''}" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit">
        </div>
        <div class="form-group"><label class="form-label">Max Points</label>
          <input id="editAsgPoints" type="number" value="${a.points||100}" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit">
        </div>
      </div>
      
      <div class="form-group"><label class="form-label">Instructions</label>
        <textarea id="editAsgInstructions" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;resize:vertical;min-height:80px">${(a.instructions||'').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</textarea>
      </div>
      
      <div class="form-group"><label class="form-label">Submission Type</label>
        <select id="editAsgSubmissionType" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
          <option value="file" ${a.submissionType === 'file' ? 'selected' : ''}>File Upload</option>
          <option value="text" ${a.submissionType === 'text' ? 'selected' : ''}>Online Text</option>
          <option value="both" ${a.submissionType === 'both' ? 'selected' : ''}>Both</option>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">Attachments (optional)</label>
        <input type="file" id="editAsgFileInput" multiple style="display:none" onchange="editHandleAsgFiles(this)">
        <div class="upload-zone"
          onclick="document.getElementById('editAsgFileInput').click()"
          ondragover="event.preventDefault();this.style.borderColor='var(--primary)';this.style.background='var(--primary-light)'"
          ondragleave="this.style.borderColor='';this.style.background=''"
          ondrop="editHandleAsgDrop(event)"
          style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:20px;text-align:center;cursor:pointer;transition:.2s">
          <div style="font-size:28px;margin-bottom:8px">📎</div>
          <div style="font-weight:600;font-size:13px">Click to browse or drag &amp; drop files</div>
          <div style="font-size:11px;color:var(--text-2);margin-top:4px">PDF, DOCX, ZIP, images — Max 50MB each</div>
        </div>
        <div id="editAsgFileList" style="margin-top:10px;display:flex;flex-direction:column;gap:6px"></div>
      </div>
      
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('editAsgOverlay').remove()">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="saveEditAssignment(${idx})">💾 Save Changes</button>
      </div>
    </div>`;
}

function saveEditAssignment(idx) {
  const a = recentAssignments[idx];
  if (!a || !a.id) { showToast('Cannot edit this assignment', 'error'); return; }
  const title  = document.getElementById('editAsgTitle')?.value.trim();
  const due    = document.getElementById('editAsgDue')?.value;
  const points = parseInt(document.getElementById('editAsgPoints')?.value) || 100;
  const instructions = document.getElementById('editAsgInstructions')?.value.trim() || '';
  const submissionType = document.getElementById('editAsgSubmissionType')?.value || 'file';
  if (!title) { showToast('Title is required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_assignment');
  fd.append('assignment_id', a.id);
  fd.append('title', title);
  fd.append('due_date', due || '');
  fd.append('max_score', points);
  fd.append('instructions', instructions);
  fd.append('submission_type', submissionType);
  
  // Add any attached files
  const fileInput = document.getElementById('editAsgFileInput');
  if (fileInput?.files?.length > 0) {
    for (let f of fileInput.files) {
      fd.append('attachments[]', f);
    }
  }
  
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        recentAssignments[idx].title  = title;
        recentAssignments[idx].instructions = instructions;
        recentAssignments[idx].submissionType = submissionType;
        recentAssignments[idx].due    = due ? new Date(due).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : 'TBD';
        recentAssignments[idx].due_raw= due;
        recentAssignments[idx].points = points;
        document.getElementById('editAsgOverlay')?.remove();
        filterAsgPanel();
        showToast('Assignment updated!', 'success');
      } else { showToast(data.message || 'Failed to update', 'error'); }
    })
    .catch(() => {
      recentAssignments[idx].title = title;
      document.getElementById('editAsgOverlay')?.remove();
      filterAsgPanel();
      showToast('Updated (offline)', 'warning');
    });
}

function editHandleAsgFiles(input) {
  Array.from(input.files).forEach(f => {
    // For edit modal, we just keep the files in the input element
  });
  editRenderAsgFileList();
  input.value = ''; // reset so same file can be re-added after removal
}

function editHandleAsgDrop(e) {
  e.preventDefault();
  e.currentTarget.style.borderColor = '';
  e.currentTarget.style.background = '';
  const fileInput = document.getElementById('editAsgFileInput');
  if (fileInput) {
    Array.from(e.dataTransfer.files).forEach(f => {
      // Add files to the input
      const dt = new DataTransfer();
      for (let existing of fileInput.files) {
        dt.items.add(existing);
      }
      if (!Array.from(fileInput.files).find(x => x.name === f.name)) {
        dt.items.add(f);
      }
      fileInput.files = dt.files;
    });
    editRenderAsgFileList();
  }
}

function editRenderAsgFileList() {
  const list = document.getElementById('editAsgFileList');
  const fileInput = document.getElementById('editAsgFileInput');
  if (!list || !fileInput) return;
  if (fileInput.files.length === 0) { list.innerHTML = ''; return; }
  list.innerHTML = Array.from(fileInput.files).map((f, i) => {
    const size = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : (f.size / 1024).toFixed(0) + ' KB';
    const ext  = f.name.split('.').pop().toUpperCase();
    const icon = ext === 'PDF' ? '📄' : ext === 'ZIP' ? '🗜️' : ['PNG','JPG','JPEG','GIF','WEBP'].includes(ext) ? '🖼️' : ['DOCX','DOC'].includes(ext) ? '📝' : '📎';
    return `<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px;border-radius:var(--radius-sm);background:var(--surface-3);font-size:12px">
              <span>${icon} ${f.name} <span style="color:var(--text-2)">(${size})</span></span>
              <button type="button" class="btn btn-ghost btn-xs" onclick="editRemoveFile(${i})" style="padding:2px 6px;color:var(--danger)">✕</button>
            </div>`;
  }).join('');
}

function editRemoveFile(idx) {
  const fileInput = document.getElementById('editAsgFileInput');
  if (!fileInput) return;
  const dt = new DataTransfer();
  Array.from(fileInput.files).forEach((f, i) => {
    if (i !== idx) dt.items.add(f);
  });
  fileInput.files = dt.files;
  editRenderAsgFileList();
}

function deleteAssignment(idx) {
  const a = recentAssignments[idx];
  if (!a) return;
  if (!confirm(`Delete "${a.title}"? This will also remove all student submissions for this assignment.`)) return;
  if (!a.id) {
    recentAssignments.splice(idx, 1);
    filterAsgPanel();
    showToast('Assignment removed', 'success');
    return;
  }
  const fd = new FormData();
  fd.append('action', 'delete_assignment');
  fd.append('assignment_id', a.id);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        recentAssignments.splice(idx, 1);
        filterAsgPanel();
        showToast('Assignment deleted!', 'success');
      } else { showToast(data.message || 'Failed to delete', 'error'); }
    })
    .catch(() => {
      recentAssignments.splice(idx, 1);
      filterAsgPanel();
      showToast('Deleted (offline)', 'warning');
    });
}

// ===== EDIT / DELETE QUIZ =====
function openEditQuizModal(idx) {
  const qz = recentQuizzes[idx];
  if (!qz) return;
  let overlay = document.getElementById('editQzOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'editQzOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:500;display:flex;align-items:center;justify-content:center;padding:20px';
    overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };
    document.body.appendChild(overlay);
  }
  const timeMins = parseInt(qz.timeLimit) || 30;
  overlay.innerHTML = `
    <div style="background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:480px;box-shadow:var(--shadow-md);animation:fadeUp .25s ease">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700">✏️ Edit Quiz</div>
        <button onclick="document.getElementById('editQzOverlay').remove()" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:20px;line-height:1">✕</button>
      </div>
      <div class="form-group"><label class="form-label">Quiz Title</label>
        <input id="editQzTitle" type="text" value="${(qz.title||'').replace(/"/g,'&quot;')}" style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Time Limit (mins)</label>
          <input id="editQzTime" type="number" value="${timeMins}" min="1" style="width:100%;padding:10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit">
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select id="editQzStatus" style="width:100%">
            <option value="draft" ${qz.status==='draft'?'selected':''}>◌ Draft</option>
            <option value="published" ${qz.status==='published'?'selected':''}>● Published</option>
          </select>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('editQzOverlay').remove()">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="saveEditQuiz(${idx})">💾 Save Changes</button>
      </div>
    </div>`;
}

function saveEditQuiz(idx) {
  const qz = recentQuizzes[idx];
  if (!qz || !qz.id) { showToast('Cannot edit this quiz', 'error'); return; }
  const title  = document.getElementById('editQzTitle')?.value.trim();
  const time   = parseInt(document.getElementById('editQzTime')?.value) || 30;
  const status = document.getElementById('editQzStatus')?.value || 'draft';
  if (!title) { showToast('Title is required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_quiz');
  fd.append('quiz_id', qz.id);
  fd.append('title', title);
  fd.append('time_limit', time + ' minutes');
  fd.append('status', status);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('editQzOverlay')?.remove();
        showToast('Quiz updated!', 'success');
        refreshQuizzesFromDB().then(() => filterQzPanel());
      } else { showToast(data.message || 'Failed to update', 'error'); }
    })
    .catch(() => {
      recentQuizzes[idx].title = title;
      document.getElementById('editQzOverlay')?.remove();
      filterQzPanel();
      showToast('Updated (offline)', 'warning');
    });
}

function deleteQuiz(idx) {
  const qz = recentQuizzes[idx];
  if (!qz) return;
  if (!confirm(`Delete "${qz.title}"? This will also remove all student attempts for this quiz.`)) return;
  if (!qz.id) {
    recentQuizzes.splice(idx, 1);
    filterQzPanel();
    showToast('Quiz removed', 'success');
    return;
  }
  const fd = new FormData();
  fd.append('action', 'delete_quiz');
  fd.append('quiz_id', qz.id);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        recentQuizzes.splice(idx, 1);
        filterQzPanel();
        showToast('Quiz deleted!', 'success');
      } else { showToast(data.message || 'Failed to delete', 'error'); }
    })
    .catch(() => {
      recentQuizzes.splice(idx, 1);
      filterQzPanel();
      showToast('Deleted (offline)', 'warning');
    });
}

// ===== DELETE DISCUSSION =====
function deleteDiscussion(discId) {
  if (!confirm('Delete this entire discussion thread and all its replies?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_discussion');
  fd.append('thread_id', discId);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const i = discussionData.findIndex(d => d.id === discId);
        if (i !== -1) discussionData.splice(i, 1);
        currentDiscussionId = null;
        renderDiscussionsList();
        const panel = document.getElementById('disc-detail-panel');
        if (panel) panel.innerHTML = `<div class="disc-empty-state"><div class="disc-empty-icon">○</div><div style="font-size:14px;color:var(--text-2);margin-bottom:6px">Discussion deleted</div></div>`;
        showToast('Discussion deleted!', 'success');
      } else { showToast(data.message || 'Failed to delete', 'error'); }
    })
    .catch(() => {
      const i = discussionData.findIndex(d => d.id === discId);
      if (i !== -1) discussionData.splice(i, 1);
      currentDiscussionId = null;
      renderDiscussionsList();
      showToast('Deleted (offline)', 'warning');
    });
}

// ===== ASSIGNMENT DETAIL MODAL =====
// Per-assignment submission snapshots keyed by recentAssignments index
const asgSubmissionSnapshots = [
  // Lab Activity 5: Exception Handling (OOP)
  [
    { initials:'RG', name:'Ryza Marie Gabriel',    submitted:'May 9, 2:15 PM',  status:'Submitted', grade:'—',      file:'Lab5_Gabriel.zip'   },
    { initials:'AS', name:'Aricelle Sarmiento',     submitted:'May 8, 10:00 PM', status:'Graded',    grade:'96/100', file:'Lab5_Sarmiento.zip' },
    { initials:'NA', name:'Nicole Abalos',           submitted:'May 9, 8:30 AM',  status:'Submitted', grade:'—',      file:'Lab5_Abalos.zip'    },
    { initials:'MA', name:'Micah Antipolo',          submitted:'May 11, 9:00 AM', status:'Late',      grade:'72/100', file:'Lab5_Antipolo.zip'  },
    { initials:'JD', name:'Juan Dela Cruz',          submitted:'May 9, 1:00 PM',  status:'Graded',    grade:'89/100', file:'Lab5_Cruz.zip'      },
  ],
  // Project 2: Responsive Portfolio (Web Prog)
  [
    { initials:'RG', name:'Ryza Marie Gabriel',    submitted:'Apr 27, 11:59 PM',status:'Graded',    grade:'92/100', file:'Project2_Gabriel.zip'   },
    { initials:'AS', name:'Aricelle Sarmiento',     submitted:'Apr 28, 8:00 AM', status:'Late',      grade:'80/100', file:'Project2_Sarmiento.zip' },
    { initials:'NA', name:'Nicole Abalos',           submitted:'Apr 27, 5:00 PM', status:'Graded',    grade:'95/100', file:'Project2_Abalos.zip'    },
    { initials:'WO', name:'Win Heart Ordaniel',      submitted:'—',               status:'Missing',   grade:'—',      file:null                     },
  ],
  // Problem Set 3: Sorting Algorithms (Data Struct)
  [
    { initials:'RC', name:'Rico Cruz',             submitted:'Apr 24, 5:00 PM', status:'Graded', grade:'84/100', file:'PS3_Cruz.pdf'     },
    { initials:'MB', name:'Mario Bautista',         submitted:'Apr 26, 9:00 AM', status:'Late',   grade:'70/100', file:'PS3_Bautista.pdf' },
    { initials:'LP', name:'Lena Park',              submitted:'Apr 24, 2:00 PM', status:'Graded', grade:'91/100', file:'PS3_Park.pdf'     },
  ],
  // Capstone Proposal Draft — draft, no submissions
  [],
  // Lab Activity 4: Inheritance (OOP)
  [
    { initials:'NA', name:'Nicole Abalos',    submitted:'Apr 22, 8:00 AM', status:'Graded', grade:'88/100', file:'Lab4_Abalos.zip'    },
    { initials:'MA', name:'Micah Antipolo',   submitted:'Apr 20, 3:45 PM', status:'Late',   grade:'75/100', file:'Lab4_Antipolo.zip'  },
    { initials:'WO', name:'Win Heart Ordaniel',submitted:'—',              status:'Missing',grade:'—',      file:null                 },
    { initials:'JD', name:'Juan Dela Cruz',   submitted:'Apr 18, 4:00 PM', status:'Graded', grade:'82/100', file:'Lab4_Cruz.zip'      },
  ],
];

function openAssignmentDetail(idx) {
  const a = recentAssignments[idx];
  if (!a) return;
  const subs = asgSubmissionSnapshots[idx] || [];
  const pct  = a.total > 0 ? Math.round(a.submissions / a.total * 100) : 0;
  const asgStatusBadge = s => s==='published'?'badge-green':s==='draft'?'badge-amber':'badge-gray';
  const asgStatusLabel = s => s==='published'?'● Published':s==='draft'?'◌ Draft':'✕ Closed';
  const subBadge = s => s==='Submitted'?'badge-blue':s==='Graded'?'badge-green':s==='Late'?'badge-amber':'badge-red';

  let overlay = document.getElementById('asgDetailOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'asgDetailOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px';
    overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };
    document.body.appendChild(overlay);
  }

  const gradedCount  = subs.filter(s => s.status === 'Graded').length;
  const pendingCount = subs.filter(s => s.status === 'Submitted' || s.status === 'Late').length;

  overlay.innerHTML = `
    <div style="background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:620px;max-height:88vh;overflow-y:auto;box-shadow:var(--shadow-md);animation:fadeUp .25s ease">

      <!-- Header -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px">
        <div style="flex:1;min-width:0">
          <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;margin-bottom:8px;line-height:1.3">${a.title}</div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span class="badge ${asgStatusBadge(a.status)}">${asgStatusLabel(a.status)}</span>
            <span style="font-size:12px;color:var(--text-2)">📚 ${a.course}</span>
            <span style="font-size:12px;color:var(--text-2)">📅 ${a.due}</span>
            <span style="font-size:12px;color:var(--text-2)">⭐ ${a.points} pts</span>
          </div>
        </div>
        <button onclick="document.getElementById('asgDetailOverlay').remove()"
          style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:22px;line-height:1;flex-shrink:0;padding:2px 6px;border-radius:8px;transition:.2s"
          onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
      </div>

      <!-- Summary stats -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:14px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--primary)">${a.submissions}/${a.total}</div>
          <div style="font-size:11px;color:var(--text-2);margin-top:3px">Submitted</div>
          <div style="margin-top:6px"><div class="progress-bar" style="height:4px"><div class="progress-fill" style="width:${pct}%"></div></div></div>
        </div>
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:14px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:#22C55E">${gradedCount}</div>
          <div style="font-size:11px;color:var(--text-2);margin-top:3px">Graded</div>
        </div>
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:14px;text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--accent)">${pendingCount}</div>
          <div style="font-size:11px;color:var(--text-2);margin-top:3px">Pending Grade</div>
        </div>
      </div>

      <!-- Student submissions list -->
      <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;margin-bottom:12px">Student Submissions</div>
      ${subs.length === 0
        ? `<div style="text-align:center;padding:32px;color:var(--text-3)">
            <div style="font-size:36px;margin-bottom:10px">${a.status==='draft'?'📝':'📭'}</div>
            <div style="font-size:13px;font-weight:600;color:var(--text-2)">${a.status==='draft'?'This assignment is still a draft — publish it first':'No submissions yet'}</div>
          </div>`
        : `<div class="table-wrap">
            <table>
              <thead><tr><th>Student</th><th>Submitted</th><th>Status</th><th>Grade</th><th>File</th></tr></thead>
              <tbody>
                ${subs.map(s => `
                  <tr>
                    <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar-sm">${s.initials}</div><span style="font-weight:600">${s.name}</span></div></td>
                    <td style="font-size:12px;color:var(--text-2)">${s.submitted}</td>
                    <td><span class="badge ${subBadge(s.status)}">${s.status}</span></td>
                    <td><strong style="color:${s.grade==='—'?'var(--text-3)':'var(--text)'}">${s.grade}</strong></td>
                    <td>
                      ${s.file
                        ? `<button class="btn btn-ghost btn-sm" style="font-size:11px" onclick="showToast('Downloading ${s.file}…','success')">⬇ ${s.file.split('.').pop().toUpperCase()}</button>`
                        : `<span style="font-size:12px;color:var(--text-3)">—</span>`}
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>`}

      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px">
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('asgDetailOverlay').remove()">Close</button>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('asgDetailOverlay').remove();navigate('submissions')">Go to Submissions →</button>
      </div>
    </div>`;
}

// ===== INIT =====
renderNav();
navigate('dashboard');
</script>
</body>
</html>