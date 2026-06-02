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
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ===== AUTH GUARD =====
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header('Location: learnflow-login.php');
    exit;
}

// ===== LOAD STUDENT PROFILE FROM DB =====
$student_user_id = (int)($_SESSION['user_id'] ?? 0);
if (!$student_user_id) {
    header('Location: learnflow-login.php');
    exit;
}
try {
    $stmt = $pdo->prepare("
        SELECT u.email, up.first_name, up.last_name, up.display_name, up.avatar_url,
               sp.student_id, sp.program, sp.year_level, sp.section
        FROM users u
        JOIN user_profiles up ON up.user_id = u.id
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$student_user_id]);
    $student_row = $stmt->fetch();
} catch (Exception $e) { $student_row = null; }

$student_name       = $student_row ? ($student_row['display_name'] ??
                        trim(($student_row['first_name'] ?? '') . ' ' . ($student_row['last_name'] ?? '')))
                        : ($_SESSION['user'] ?? 'Nicole Abalos');
$student_email      = $student_row ? $student_row['email']                   : ($_SESSION['email'] ?? 'nicole.abalos@school.edu.ph');
$student_id         = $student_row ? ($student_row['student_id'] ?? '2023-00142') : '2023-00142';
$student_section    = $student_row ? ($student_row['section']           ?? 'BSIT 3-1')   : 'BSIT 3-1';
$student_avatar_url = $student_row ? ($student_row['avatar_url']        ?? null)          : null;

$name_parts = explode(' ', $student_name);
$initials   = '';
foreach ($name_parts as $p) {
    if (isset($p[0]) && ctype_alpha($p[0])) $initials .= strtoupper($p[0]);
}
$initials = substr($initials, 0, 2);

// ===== AJAX HANDLERS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    // Submit assignment
    if ($action === 'submit_assignment') {
        try {
            $assignment_id = (int)($_POST['assignment_id'] ?? 0);
            $remarks = trim($_POST['remarks'] ?? '');
            $chk = $pdo->prepare("SELECT id FROM submissions WHERE assignment_id=? AND student_id=?");
            $chk->execute([$assignment_id, $student_user_id]);
            if ($chk->fetch()) {
                $pdo->prepare("UPDATE submissions SET content=?, submitted_at=NOW(), status='submitted' WHERE assignment_id=? AND student_id=?")
                    ->execute([$remarks, $assignment_id, $student_user_id]);
            } else {
                $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, content, status) VALUES (?,?,?,'submitted')")
                    ->execute([$assignment_id, $student_user_id, $remarks]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    // Post discussion reply
    if ($action === 'post_reply') {
        try {
            $thread_id = (int)($_POST['thread_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if (!$body) { echo json_encode(['success'=>false,'message'=>'Empty reply']); exit; }
            $pdo->prepare("INSERT INTO forum_replies (thread_id, author_id, body) VALUES (?,?,?)")
                ->execute([$thread_id, $student_user_id, $body]);
            $reply_id = (int)$pdo->lastInsertId();
            // Notify the instructor of the course this thread belongs to
            try {
                $ni = $pdo->prepare("
                    SELECT cs.instructor_id, ft.title
                    FROM forum_threads ft
                    JOIN forums f ON f.id = ft.forum_id
                    JOIN course_sections cs ON cs.id = f.section_id
                    WHERE ft.id = ?
                ");
                $ni->execute([$thread_id]);
                $ni_row = $ni->fetch();
                if ($ni_row && $ni_row['instructor_id'] && $ni_row['instructor_id'] != $student_user_id) {
                    $notif_msg = $student_name . ' replied to "' . substr($ni_row['title'], 0, 60) . '"';
                    $pdo->prepare("INSERT INTO notifications (recipient_id, notification_type, title, message) VALUES (?,?,?,?)")
                        ->execute([$ni_row['instructor_id'], 'discussion', 'New Reply in Discussion', $notif_msg]);
                }
            } catch (Exception $ne) {}
            echo json_encode(['success' => true, 'reply_id' => $reply_id]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        exit;
    }

    // Edit own discussion reply
    if ($action === 'update_reply') {
        try {
            $reply_id = (int)($_POST['reply_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if (!$body) { echo json_encode(['success'=>false,'message'=>'Reply cannot be empty']); exit; }
            $chk = $pdo->prepare("SELECT id FROM forum_replies WHERE id=? AND author_id=?");
            $chk->execute([$reply_id, $student_user_id]);
            if (!$chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Not authorised']); exit; }
            $pdo->prepare("UPDATE forum_replies SET body=? WHERE id=? AND author_id=?")
                ->execute([$body, $reply_id, $student_user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }

    // Delete own discussion reply
    if ($action === 'delete_reply') {
        try {
            $reply_id = (int)($_POST['reply_id'] ?? 0);
            $chk = $pdo->prepare("SELECT id FROM forum_replies WHERE id=? AND author_id=?");
            $chk->execute([$reply_id, $student_user_id]);
            if (!$chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Not authorised']); exit; }
            $pdo->prepare("DELETE FROM forum_replies WHERE id=? AND author_id=?")->execute([$reply_id, $student_user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }

    // Update own discussion thread
    if ($action === 'update_thread') {
        try {
            $thread_id = (int)($_POST['thread_id'] ?? 0);
            $title     = trim($_POST['title'] ?? '');
            $body      = trim($_POST['body'] ?? '');
            if (!$thread_id || !$title || !$body) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }
            $chk = $pdo->prepare("SELECT id FROM forum_threads WHERE id=? AND author_id=?");
            $chk->execute([$thread_id, $student_user_id]);
            if (!$chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
            $pdo->prepare("UPDATE forum_threads SET title=?, body=? WHERE id=? AND author_id=?")
                ->execute([$title, $body, $thread_id, $student_user_id]);
            echo json_encode(['success' => true, 'message' => 'Discussion updated!']);
        } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }

    // Delete own discussion thread
    if ($action === 'delete_thread') {
        try {
            $thread_id = (int)($_POST['thread_id'] ?? 0);
            if (!$thread_id) { echo json_encode(['success'=>false,'message'=>'Missing thread_id']); exit; }
            $chk = $pdo->prepare("SELECT id FROM forum_threads WHERE id=? AND author_id=?");
            $chk->execute([$thread_id, $student_user_id]);
            if (!$chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Not authorized']); exit; }
            $pdo->prepare("DELETE FROM forum_replies WHERE thread_id=?")->execute([$thread_id]);
            $pdo->prepare("DELETE FROM forum_threads WHERE id=? AND author_id=?")->execute([$thread_id, $student_user_id]);
            echo json_encode(['success' => true, 'message' => 'Discussion deleted!']);
        } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }

    // Post new discussion thread
    if ($action === 'post_thread') {
        try {
            $section_id = (int)($_POST['section_id'] ?? 0);
            $title      = trim($_POST['title'] ?? '');
            $body       = trim($_POST['body'] ?? '');
            if (!$section_id || !$title || !$body) {
                echo json_encode(['success'=>false,'message'=>'All fields required']); exit;
            }
            // Verify student is enrolled in this section
            $chk = $pdo->prepare("SELECT id FROM enrollments WHERE section_id=? AND student_id=? AND status='enrolled'");
            $chk->execute([$section_id, $student_user_id]);
            if (!$chk->fetch()) { echo json_encode(['success'=>false,'message'=>'Not enrolled']); exit; }
            // Get or create forum for this section
            $sf = $pdo->prepare("SELECT id FROM forums WHERE section_id=? LIMIT 1");
            $sf->execute([$section_id]);
            $forum = $sf->fetch();
            if (!$forum) {
                $pdo->prepare("INSERT INTO forums (section_id, title, description) VALUES (?,?,?)")
                    ->execute([$section_id, 'General Discussion', 'Course discussion forum']);
                $forum_id = (int)$pdo->lastInsertId();
            } else {
                $forum_id = (int)$forum['id'];
            }
            $pdo->prepare("INSERT INTO forum_threads (forum_id, author_id, title, body) VALUES (?,?,?,?)")
                ->execute([$forum_id, $student_user_id, $title, $body]);
            $thread_id = (int)$pdo->lastInsertId();
            // Get course code for return and notify instructor
            $sc = $pdo->prepare("
                SELECT c.code, cs.instructor_id
                FROM course_sections cs JOIN courses c ON c.id=cs.course_id
                WHERE cs.id=?
            ");
            $sc->execute([$section_id]);
            $course_row = $sc->fetch();
            if ($course_row && $course_row['instructor_id']) {
                $notif_msg = $student_name . ' posted a new discussion: "' . substr($title, 0, 60) . '"';
                $pdo->prepare("INSERT INTO notifications (recipient_id, notification_type, title, message) VALUES (?,?,?,?)")
                    ->execute([$course_row['instructor_id'], 'discussion', 'New Discussion Posted', $notif_msg]);
            }
            echo json_encode(['success'=>true,'thread_id'=>$thread_id,'course_code'=>($course_row['code']??'')]);
        } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }

    // Mark one notification as read
    if ($action === 'mark_notif_read') {
        $notif_id = (int)($_POST['notif_id'] ?? 0);
        if ($notif_id) {
            try {
                $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND recipient_id=?")
                    ->execute([$notif_id, $student_user_id]);
            } catch (Exception $e) {}
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Mark all notifications as read
    if ($action === 'mark_all_notifs_read') {
        try {
            $pdo->prepare("UPDATE notifications SET is_read=1 WHERE recipient_id=? AND is_read=0")
                ->execute([$student_user_id]);
        } catch (Exception $e) {}
        echo json_encode(['success' => true]);
        exit;
    }

    // Upload profile photo
    if ($action === 'upload_photo') {
        try {
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success'=>false,'message'=>'No file uploaded']); exit;
            }
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                echo json_encode(['success'=>false,'message'=>'Invalid file type']); exit;
            }
            $filename  = 'avatar_' . $student_user_id . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
            $url = 'uploads/avatars/' . $filename;
            $pdo->prepare("UPDATE user_profiles SET avatar_url=? WHERE user_id=?")->execute([$url, $student_user_id]);
            echo json_encode(['success'=>true,'avatar_url'=>$url,'message'=>'Photo updated!']);
        } catch (Exception $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        exit;
    }
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
            LEFT JOIN lessons l ON l.module_id = m.id AND l.is_published = 1
            WHERE m.section_id = ? AND m.is_published = 1
            GROUP BY m.id
            ORDER BY m.sort_order ASC
        ");
        $stmt_mods->execute([$section_id]);
        $mods = $stmt_mods->fetchAll();
        foreach ($mods as &$mod) {
            $stmt_files = $pdo->prepare("SELECT id, title, lesson_type, resource_url FROM lessons WHERE module_id=? AND is_published=1 ORDER BY sort_order ASC");
            $stmt_files->execute([$mod['id']]);
            $mod['lessons'] = $stmt_files->fetchAll();
        }
        echo json_encode(['success' => true, 'modules' => $mods]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'modules' => []]);
    }
    exit;
}

// ===== AJAX: GET QUIZZES FOR STUDENT =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_quizzes') {
    header('Content-Type: application/json');
    // Re-load enrolled section IDs for this request
    try {
        $stmt_enr = $pdo->prepare("
            SELECT cs.id FROM enrollments e
            JOIN course_sections cs ON cs.id = e.section_id
            JOIN courses c ON c.id = cs.course_id
            WHERE e.student_id = ? AND e.status = 'enrolled' AND c.status != 'archived'
        ");
        $stmt_enr->execute([$student_user_id]);
        $ajax_section_ids = array_column($stmt_enr->fetchAll(), 'id');
    } catch (Exception $e) { $ajax_section_ids = []; }

    if (empty($ajax_section_ids)) {
        echo json_encode(['success' => true, 'quizzes' => []]);
        exit;
    }

    try {
        $in = implode(',', array_fill(0, count($ajax_section_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT q.id, q.section_id, q.title,
                   COALESCE(q.description, '') AS description,
                   COALESCE(q.time_limit_min, 0) AS duration_minutes,
                   COALESCE(q.max_score, 100)     AS total_points,
                   COALESCE(q.due_date, '')        AS due_date,
                   COUNT(DISTINCT qq.id)            AS question_count,
                   c.code                           AS course_code,
                   COALESCE(up.display_name, CONCAT(up.first_name,' ',up.last_name)) AS instructor_name,
                   qa.id AS attempt_id, qa.score, qa.submitted_at
            FROM quizzes q
            JOIN course_sections cs ON cs.id = q.section_id
            JOIN courses c ON c.id = cs.course_id
            JOIN users u ON u.id = cs.instructor_id
            JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id
            LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.student_id = ?
            WHERE q.section_id IN ($in) AND q.status = 'published'
            GROUP BY q.id, q.section_id, q.title, q.description, q.time_limit_min, q.max_score,
                     q.due_date, c.code, up.display_name, up.first_name, up.last_name,
                     qa.id, qa.score, qa.submitted_at
            ORDER BY q.created_at DESC
        ");
        $stmt->execute(array_merge([$student_user_id], $ajax_section_ids));
        $quizzes_out = [];
        foreach ($stmt->fetchAll() as $row) {
            $attempted = $row['attempt_id'] !== null && $row['submitted_at'] !== null;
            $max = max(1, (int)$row['total_points']);
            $pct = $attempted ? min(100, round($row['score'] / $max * 100)) : null;
            $instr_p = explode(' ', $row['instructor_name']);
            $due_formatted = '';
            if ($row['due_date'] && $row['due_date'] !== '') {
                $due_formatted = date('M j, Y g:i A', strtotime($row['due_date']));
                $days_left = max(0, (int)ceil((strtotime($row['due_date']) - time()) / 86400));
            } else {
                $due_formatted = 'TBD';
                $days_left = 7;
            }
            if ($attempted) {
                $result = $pct >= 90 ? 'Perfect' : ($pct >= 75 ? 'Passed' : 'Average');
                $quizzes_out[] = [
                    'id'         => (int)$row['id'],
                    'title'      => $row['title'],
                    'course'     => $row['course_code'],
                    'section_id' => (int)$row['section_id'],
                    'status'     => 'completed',
                    'score'      => $pct,
                    'date'       => date('M j, Y', strtotime($row['submitted_at'])),
                    'result'     => $result,
                ];
            } else {
                $quizzes_out[] = [
                    'id'         => (int)$row['id'],
                    'title'      => $row['title'],
                    'course'     => $row['course_code'],
                    'section_id' => (int)$row['section_id'],
                    'status'     => 'upcoming',
                    'questions'  => (int)$row['question_count'],
                    'duration'   => (int)$row['duration_minutes'],
                    'due'        => $due_formatted,
                    'daysLeft'   => $days_left,
                    'instructor' => 'Prof. ' . end($instr_p),
                    'topics'     => $row['description'] ?: 'See quiz details',
                ];
            }
        }
        echo json_encode(['success' => true, 'quizzes' => $quizzes_out]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'quizzes' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: GET QUIZ QUESTIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_quiz_questions') {
    header('Content-Type: application/json');
    $quiz_id = (int)($_GET['quiz_id'] ?? 0);
    if (!$quiz_id) {
        echo json_encode(['success' => false, 'message' => 'Missing quiz_id']);
        exit;
    }
    try {
        $stmt_check = $pdo->prepare("
            SELECT q.id, q.title, q.time_limit_min, q.max_score
            FROM quizzes q
            JOIN enrollments e ON e.section_id = q.section_id AND e.student_id = ?
            WHERE q.id = ? AND q.status = 'published' AND e.status = 'enrolled'
            LIMIT 1
        ");
        $stmt_check->execute([$student_user_id, $quiz_id]);
        $quiz = $stmt_check->fetch();
        if (!$quiz) {
            echo json_encode(['success' => false, 'message' => 'Quiz not found or you are not enrolled in this course']);
            exit;
        }
        $stmt_att = $pdo->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ? AND submitted_at IS NOT NULL LIMIT 1");
        $stmt_att->execute([$quiz_id, $student_user_id]);
        if ($stmt_att->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already completed this quiz']);
            exit;
        }
        $stmt_q = $pdo->prepare("SELECT id, question_text, question_type, choices, correct_answer FROM quiz_questions WHERE quiz_id = ? ORDER BY order_num ASC, id ASC");
        $stmt_q->execute([$quiz_id]);
        $questions = [];
        foreach ($stmt_q->fetchAll() as $row) {
            $choices = [];
            $db_type = $row['question_type'];
            if ($db_type === 'multiple_choice' && $row['choices']) {
                $decoded = json_decode($row['choices'], true);
                $choices = is_array($decoded) ? $decoded : [];
            } elseif ($db_type === 'true_false') {
                $choices = ['True', 'False'];
            }
            // Map DB type names to short names the JS engine expects
            $js_type = $db_type === 'multiple_choice' ? 'mc'
                     : ($db_type === 'true_false'    ? 'tf' : 'sa');
            $questions[] = [
                'id'      => (int)$row['id'],
                'type'    => $js_type,
                'text'    => $row['question_text'],
                'choices' => $choices,
            ];
        }
        echo json_encode([
            'success'        => true,
            'quiz_id'        => (int)$quiz['id'],
            'title'          => $quiz['title'],
            'time_limit_min' => (int)($quiz['time_limit_min'] ?? 30),
            'max_score'      => (int)($quiz['max_score'] ?? 100),
            'questions'      => $questions,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== AJAX: SUBMIT QUIZ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_quiz') {
    header('Content-Type: application/json');
    $quiz_id     = (int)($_POST['quiz_id'] ?? 0);
    $answers_raw = $_POST['answers'] ?? '{}';
    $time_taken  = (int)($_POST['time_taken_sec'] ?? 0);
    if (!$quiz_id) {
        echo json_encode(['success' => false, 'message' => 'Missing quiz_id']);
        exit;
    }
    try {
        $stmt_check = $pdo->prepare("
            SELECT q.id, q.max_score
            FROM quizzes q
            JOIN enrollments e ON e.section_id = q.section_id AND e.student_id = ?
            WHERE q.id = ? AND q.status = 'published' AND e.status = 'enrolled'
            LIMIT 1
        ");
        $stmt_check->execute([$student_user_id, $quiz_id]);
        $quiz = $stmt_check->fetch();
        if (!$quiz) {
            echo json_encode(['success' => false, 'message' => 'Quiz not found or you are not enrolled']);
            exit;
        }
        $stmt_att = $pdo->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ? AND submitted_at IS NOT NULL LIMIT 1");
        $stmt_att->execute([$quiz_id, $student_user_id]);
        if ($stmt_att->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already submitted this quiz']);
            exit;
        }
        $stmt_q = $pdo->prepare("SELECT id, question_type, correct_answer FROM quiz_questions WHERE quiz_id = ? ORDER BY order_num ASC, id ASC");
        $stmt_q->execute([$quiz_id]);
        $questions   = $stmt_q->fetchAll();
        $answers     = json_decode($answers_raw, true) ?: [];
        $correct_cnt = 0;
        $total_cnt   = count($questions);
        foreach ($questions as $q) {
            $qid   = (int)$q['id'];
            $given = $answers[$qid] ?? null;
            if ($given === null) continue;
            $type     = $q['question_type'];
            $expected = $q['correct_answer'];
            if ($type === 'multiple_choice') {
                if ((string)$given === (string)$expected) $correct_cnt++;
            } elseif ($type === 'true_false') {
                // JS sends 'true'/'false'; DB stores '0' (True) or '1' (False)
                $normalized = (strtolower(trim((string)$given)) === 'true') ? '0' : '1';
                if ($normalized === (string)$expected) $correct_cnt++;
            } else {
                if (strtolower(trim((string)$given)) === strtolower(trim($expected))) $correct_cnt++;
            }
        }
        $max_score = max(1, (int)$quiz['max_score']);
        $score_raw = $total_cnt > 0 ? round($correct_cnt / $total_cnt * $max_score) : 0;
        $stmt_ins  = $pdo->prepare("
            INSERT INTO quiz_attempts (quiz_id, student_id, score, status, submitted_at, time_taken_sec)
            VALUES (?, ?, ?, 'submitted', NOW(), ?)
        ");
        $stmt_ins->execute([$quiz_id, $student_user_id, $score_raw, $time_taken]);
        $pct = min(100, (int)round($score_raw / $max_score * 100));
        echo json_encode([
            'success'   => true,
            'score'     => $score_raw,
            'max_score' => $max_score,
            'pct'       => $pct,
            'correct'   => $correct_cnt,
            'total'     => $total_cnt,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ===== LOAD ENROLLED COURSES FROM DB =====
$bg_colors = ['bg-pink','bg-blue','bg-amber','bg-purple','bg-teal','bg-rose'];
$course_emojis_map = ['IT 106'=>'💻','IT 301'=>'🌐','IT 201'=>'🧮','IT 411'=>'🎯'];
try {
    $stmt = $pdo->prepare("
        SELECT cs.id AS section_id, cs.section_code, cs.room, cs.schedule,
               c.id AS course_id, c.code AS course_code, c.title AS course_title, c.units,
               COALESCE(up.display_name, CONCAT(up.first_name,' ',up.last_name)) AS instructor_name,
               (SELECT COUNT(*) FROM enrollments e2 WHERE e2.section_id=cs.id AND e2.status='enrolled') AS total_students
        FROM enrollments e
        JOIN course_sections cs ON cs.id = e.section_id
        JOIN courses c ON c.id = cs.course_id
        JOIN users u ON u.id = cs.instructor_id
        JOIN user_profiles up ON up.user_id = u.id
        WHERE e.student_id = ? AND e.status = 'enrolled' AND c.status != 'archived'
        ORDER BY c.code
    ");
    $stmt->execute([$student_user_id]);
    $enrolled_sections = $stmt->fetchAll();
} catch (Exception $e) { $enrolled_sections = []; }

$js_student_courses = [];
foreach ($enrolled_sections as $i => $sec) {
    $instr_parts = explode(' ', $sec['instructor_name']);
    $js_student_courses[] = [
        'id'         => $sec['section_id'],
        'title'      => $sec['course_title'],
        'code'       => $sec['course_code'],
        'section'    => $sec['section_code'],
        'prog'       => 0,
        'emoji'      => $course_emojis_map[$sec['course_code']] ?? '📖',
        'bg'         => $bg_colors[$i % count($bg_colors)],
        'instructor' => 'Prof. ' . end($instr_parts),
        'units'      => $sec['units'] . ' units',
        'students'   => (int)$sec['total_students'],
    ];
}
$js_student_courses_json = json_encode($js_student_courses, JSON_UNESCAPED_UNICODE);
$enrolled_section_ids    = array_column($enrolled_sections, 'section_id');

// ===== LOAD ANNOUNCEMENTS FOR ENROLLED SECTIONS =====
$js_student_announcements = [];
if (!empty($enrolled_section_ids)) {
    try {
        $in = implode(',', array_fill(0, count($enrolled_section_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT a.id, a.scope_id AS section_id, a.title, a.body, a.is_pinned AS pinned, a.created_at,
                   COALESCE(up.display_name, CONCAT(up.first_name,' ',up.last_name)) AS author_name
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            JOIN user_profiles up ON up.user_id = u.id
            WHERE a.scope = 'section' AND a.scope_id IN ($in)
            ORDER BY a.is_pinned DESC, a.created_at DESC
        ");
        $stmt->execute($enrolled_section_ids);
        foreach ($stmt->fetchAll() as $ann) {
            $sid = $ann['section_id'];
            if (!isset($js_student_announcements[$sid])) $js_student_announcements[$sid] = [];
            $ann_parts = explode(' ', $ann['author_name']);
            $js_student_announcements[$sid][] = [
                'id'     => $ann['id'],
                'title'  => $ann['title'],
                'body'   => $ann['body'],
                'pinned' => (bool)$ann['pinned'],
                'author' => 'Prof. ' . end($ann_parts),
                'date'   => $ann['created_at'],
            ];
        }
    } catch (Exception $e) {}
}
$js_student_announcements_json = json_encode($js_student_announcements, JSON_UNESCAPED_UNICODE);

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
    $stmt_adm->execute([$student_user_id, $student_user_id]);
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

// ===== LOAD ASSIGNMENTS & SUBMISSIONS =====
$js_asg_data = [];
$_debug_asg_error = '';
if (!empty($enrolled_section_ids)) {
    try {
        $in = implode(',', array_fill(0, count($enrolled_section_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT a.id, a.section_id, a.title, a.due_date, a.max_score,
                   c.code AS course_code, c.title AS course_title,
                   sub.id AS sub_id, sub.status AS sub_status, sub.score, sub.feedback
            FROM assignments a
            JOIN course_sections cs ON cs.id = a.section_id
            JOIN courses c ON c.id = cs.course_id
            LEFT JOIN submissions sub ON sub.assignment_id=a.id AND sub.student_id=?
            WHERE a.section_id IN ($in) AND a.status='published'
            ORDER BY a.due_date ASC
        ");
        $stmt->execute(array_merge([$student_user_id], $enrolled_section_ids));
        foreach ($stmt->fetchAll() as $row) {
            $status = 'pending';
            if ($row['score'] !== null) $status = 'graded';
            elseif ($row['sub_id'])     $status = 'submitted';
            $due = $row['due_date'] ? date('M j, Y', strtotime($row['due_date'])) : 'TBD';
            $js_asg_data[] = [
                'id'         => (int)$row['id'],
                'title'      => $row['title'],
                'course'     => $row['course_title'] . ' (' . $row['course_code'] . ')',
                'course_code'=> $row['course_code'],
                'due'        => $due,
                'due_raw'    => $row['due_date'],
                'status'     => $status,
                'points'     => (int)($row['max_score'] ?? 100),
                'grade'      => $row['score'] !== null ? $row['score'] . '/' . ($row['max_score'] ?? 100) : null,
                'sub_id'     => $row['sub_id'],
                'section_id' => (int)$row['section_id'],
                'icon'       => '📋',
            ];
        }
    } catch (Exception $e) { $_debug_asg_error = $e->getMessage(); }
}
$js_asg_data_json = json_encode($js_asg_data, JSON_UNESCAPED_UNICODE);

// ===== LOAD QUIZZES & ATTEMPTS =====
$js_quiz_data = [];
$_debug_quiz_error = '';
$_debug_quiz_columns = [];
if (!empty($enrolled_section_ids)) {
    try {
        $in = implode(',', array_fill(0, count($enrolled_section_ids), '?'));
        // Query uses exact column names from instructor save_quiz INSERT
        $stmt = $pdo->prepare("
            SELECT q.id, q.section_id, q.title,
                   COALESCE(q.description, '') AS description,
                   COALESCE(q.time_limit_min, 0) AS duration_minutes,
                   COALESCE(q.max_score, 100)     AS total_points,
                   COALESCE(q.due_date, '')        AS due_date,
                   COUNT(DISTINCT qq.id)            AS question_count,
                   c.code                           AS course_code,
                   COALESCE(up.display_name, CONCAT(up.first_name,' ',up.last_name)) AS instructor_name,
                   qa.id AS attempt_id, qa.score, qa.submitted_at
            FROM quizzes q
            JOIN course_sections cs ON cs.id = q.section_id
            JOIN courses c ON c.id = cs.course_id
            JOIN users u ON u.id = cs.instructor_id
            JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id
            LEFT JOIN quiz_attempts qa ON qa.quiz_id=q.id AND qa.student_id=?
            WHERE q.section_id IN ($in) AND q.status='published'
            GROUP BY q.id, q.section_id, q.title, q.description, q.time_limit_min, q.max_score,
                     q.due_date, c.code, up.display_name, up.first_name, up.last_name,
                     qa.id, qa.score, qa.submitted_at
            ORDER BY q.created_at DESC
        ");
        $stmt->execute(array_merge([$student_user_id], $enrolled_section_ids));
        foreach ($stmt->fetchAll() as $row) {
            $attempted = $row['attempt_id'] !== null && $row['submitted_at'] !== null;
            $max = max(1, (int)$row['total_points']);
            $pct = $attempted ? min(100, round($row['score'] / $max * 100)) : null;
            $instr_p = explode(' ', $row['instructor_name']);
            $due_raw = $row['due_date'] ?? '';
            if ($due_raw && $due_raw !== '') {
                $due_formatted = date('M j, Y g:i A', strtotime($due_raw));
                $days_left = max(0, (int)ceil((strtotime($due_raw) - time()) / 86400));
            } else {
                $due_formatted = 'TBD';
                $days_left = 7;
            }
            if ($attempted) {
                $result = $pct >= 90 ? 'Perfect' : ($pct >= 75 ? 'Passed' : 'Average');
                $js_quiz_data[] = [
                    'id'         => (int)$row['id'],
                    'title'      => $row['title'],
                    'course'     => $row['course_code'],
                    'section_id' => (int)$row['section_id'],
                    'status'     => 'completed',
                    'score'      => $pct,
                    'date'       => date('M j, Y', strtotime($row['submitted_at'])),
                    'result'     => $result,
                ];
            } else {
                $js_quiz_data[] = [
                    'id'         => (int)$row['id'],
                    'title'      => $row['title'],
                    'course'     => $row['course_code'],
                    'section_id' => (int)$row['section_id'],
                    'status'     => 'upcoming',
                    'questions'  => (int)($row['question_count'] ?? 0),
                    'duration'   => (int)($row['duration_minutes'] ?? 0),
                    'due'        => $due_formatted,
                    'daysLeft'   => $days_left,
                    'instructor' => 'Prof. ' . end($instr_p),
                    'topics'     => $row['description'] ?: 'See quiz details',
                ];
            }
        }
    } catch (Exception $e) { $_debug_quiz_error = $e->getMessage(); }
}
$js_quiz_data_json = json_encode($js_quiz_data, JSON_UNESCAPED_UNICODE);

// ===== LOAD DISCUSSION THREADS =====
$js_disc_threads = [];
if (!empty($enrolled_section_ids)) {
    try {
        $in = implode(',', array_fill(0, count($enrolled_section_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT ft.id, ft.title, ft.body, ft.created_at, ft.forum_id,
                   COALESCE(up.display_name, CONCAT(up.first_name,' ',up.last_name)) AS author_name,
                   u.id AS author_id,
                   c.code AS course_code,
                   (SELECT COUNT(*) FROM forum_replies fr WHERE fr.thread_id=ft.id) AS reply_count
            FROM forum_threads ft
            JOIN users u ON u.id = ft.author_id
            JOIN user_profiles up ON up.user_id = u.id
            JOIN forums f ON f.id = ft.forum_id
            JOIN course_sections cs ON cs.id = f.section_id
            JOIN courses c ON c.id = cs.course_id
            WHERE f.section_id IN ($in)
            ORDER BY ft.created_at DESC
            LIMIT 20
        ");
        $stmt->execute($enrolled_section_ids);
        foreach ($stmt->fetchAll() as $t) {
            $stmt2 = $pdo->prepare("
                SELECT fr.id, fr.body, fr.created_at,
                       COALESCE(up2.display_name, CONCAT(up2.first_name,' ',up2.last_name)) AS author_name,
                       u2.id AS author_id,
                       (SELECT cs3.instructor_id FROM forums f3 JOIN course_sections cs3 ON cs3.id=f3.section_id WHERE f3.id=?) AS instructor_id
                FROM forum_replies fr
                JOIN users u2 ON u2.id = fr.author_id
                JOIN user_profiles up2 ON up2.user_id = u2.id
                WHERE fr.thread_id = ?
                ORDER BY fr.created_at ASC
            ");
            $stmt2->execute([$t['forum_id'], $t['id']]);
            $replies_raw = $stmt2->fetchAll();
            $diff    = time() - strtotime($t['created_at']);
            $timeAgo = $diff < 3600 ? max(1,(int)round($diff/60)).'m ago'
                     : ($diff < 86400 ? (int)round($diff/3600).'h ago'
                     : (int)round($diff/86400).'d ago');
            $is_self = ($t['author_id'] == $student_user_id);
            $a_parts = explode(' ', $t['author_name']);
            $a_ini   = '';
            foreach ($a_parts as $p) { if (isset($p[0]) && ctype_alpha($p[0])) $a_ini .= strtoupper($p[0]); }
            $a_ini   = substr($a_ini, 0, 2);
            $comments = [];
            foreach ($replies_raw as $r) {
                $diff_r = time() - strtotime($r['created_at']);
                $t_ago  = $diff_r < 3600 ? max(1,(int)round($diff_r/60)).'m ago'
                        : ($diff_r < 86400 ? (int)round($diff_r/3600).'h ago'
                        : (int)round($diff_r/86400).'d ago');
                $r_parts = explode(' ', $r['author_name']);
                $r_ini   = '';
                foreach ($r_parts as $p) { if (isset($p[0]) && ctype_alpha($p[0])) $r_ini .= strtoupper($p[0]); }
                $r_ini   = substr($r_ini, 0, 2);
                $comments[] = [
                    'id'          => $r['id'],
                    'authorId'    => (int)$r['author_id'],
                    'initials'    => $r_ini,
                    'author'      => $r['author_name'],
                    'time'        => $t_ago,
                    'body'        => $r['body'],
                    'likes'       => 0,
                    'isInstructor'=> ($r['author_id'] == $r['instructor_id']),
                ];
            }
            $js_disc_threads[] = [
                'id'             => $t['id'],
                'title'          => $t['title'],
                'author'         => $is_self ? 'You' : $t['author_name'],
                'authorId'       => (int)$t['author_id'],
                'authorInitials' => $a_ini,
                'course'         => $t['course_code'],
                'timeAgo'        => $timeAgo,
                'replies'        => (int)$t['reply_count'],
                'body'           => $t['body'],
                'postedTime'     => $timeAgo,
                'isOriginalPost' => true,
                'comments'       => $comments,
            ];
        }
    } catch (Exception $e) {}
}
$js_disc_threads_json = json_encode($js_disc_threads, JSON_UNESCAPED_UNICODE);

// ===== LOAD NOTIFICATIONS =====
$type_icon = ['assignment'=>'📝','quiz'=>'🧪','grade'=>'✅','discussion'=>'💬','announcement'=>'📢','system'=>'🔔'];
$type_bg   = [
    'assignment'  => 'rgba(196,48,94,0.14)',
    'quiz'        => 'rgba(196,48,94,0.14)',
    'grade'       => 'rgba(58,159,216,0.14)',
    'discussion'  => 'rgba(212,130,10,0.14)',
    'announcement'=> 'rgba(58,159,216,0.14)',
    'system'      => 'rgba(58,159,216,0.14)',
];
$js_notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, notification_type AS type, title, message AS text, is_read, created_at
        FROM notifications WHERE recipient_id=? ORDER BY created_at DESC LIMIT 20
    ");
    $stmt->execute([$student_user_id]);
    foreach ($stmt->fetchAll() as $n) {
        $diff = time() - strtotime($n['created_at']);
        if ($diff < 3600)       $ts = max(1,(int)round($diff/60)).' min ago';
        elseif ($diff < 86400)  $ts = (int)round($diff/3600).' hrs ago';
        elseif ($diff < 172800) $ts = 'Yesterday';
        else                    $ts = (int)round($diff/86400).' days ago';
        $type = $n['type'] ?? 'system';
        $js_notifications[] = [
            'id'    => (int)$n['id'],
            'icon'  => $type_icon[$type] ?? '🔔',
            'bg'    => $type_bg[$type]   ?? 'rgba(58,159,216,0.14)',
            'title' => $n['title'],
            'text'  => $n['text'],
            'time'  => $ts,
            'type'  => $type,
            'unread'=> !(bool)$n['is_read'],
        ];
    }
} catch (Exception $e) {}
$js_notifications_json = json_encode($js_notifications, JSON_UNESCAPED_UNICODE);
$unread_notif_count = count(array_filter($js_notifications, fn($n) => $n['unread']));

$theme = $_COOKIE['theme'] ?? 'light';

// ===== LOAD CUSTOM THEME FROM DB =====
$db_theme = null;
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!empty($_SESSION['theme_preview']) && is_array($_SESSION['theme_preview'])) {
    $db_theme = $_SESSION['theme_preview'];
} else {
    try {
        $th_stmt = $pdo->query("SELECT * FROM theme_settings WHERE id=1 LIMIT 1");
        $db_theme = $th_stmt ? $th_stmt->fetch() : null;
    } catch(Exception $e) { $db_theme = null; }
}

// ===== DASHBOARD STATS =====
$stat_courses         = count($js_student_courses);
$stat_pending_asg     = count(array_filter($js_asg_data, fn($a) => $a['status'] === 'pending'));
$stat_upcoming_quizzes= count(array_filter($js_quiz_data, fn($q) => $q['status'] === 'upcoming'));
$completed_quizzes    = array_filter($js_quiz_data, fn($q) => $q['status'] === 'completed' && isset($q['score']));
$stat_avg_grade       = count($completed_quizzes)
    ? round(array_sum(array_column(array_values($completed_quizzes), 'score')) / count($completed_quizzes))
    : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnFlow – Student Portal</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<style>
/* ===== CSS VARIABLES ===== */
:root {
  --primary: #C4305E;
  --primary-hsl: 336 67% 52%;
  --primary-light: #FAF5F7;
  --primary-dark: #9E1F47;
  --primary-glow: rgba(196,48,94,0.08);
  --secondary: #3A9FD8;
  --secondary-light: #F0F9FF;
  --accent: #D4820A;
  --success: #10B981;
  --danger: #EF4444;
  --warning: #F59E0B;
  --purple: #8B5CF6;
  --bg: #F8FAFC;
  --surface: #FFFFFF;
  --surface-highlight: hsl(var(--primary-hsl) / .12);
  --surface-highlight-2: hsl(var(--primary-hsl) / .08);
  --surface-muted: hsl(var(--primary-hsl) / .05);
  --border-highlight: hsl(var(--primary-hsl) / .16);
  --surface-2: #F1F5F9;
  --surface-3: #E2E8F0;
  --surface-glass: rgba(255,255,255,0.85);
  --border: #E2E8F0;
  --border-strong: #CBD5E1;
  --text: #0F172A;
  --text-2: #475569;
  --text-3: #94A3B8;
  --shadow: 0 1px 4px rgba(15,23,42,0.04), 0 4px 16px rgba(15,23,42,0.05);
  --shadow-md: 0 4px 8px rgba(15,23,42,0.05), 0 12px 36px rgba(15,23,42,0.08);
  --shadow-float: 0 8px 16px rgba(15,23,42,0.06), 0 24px 56px rgba(15,23,42,0.10);
  --shadow-glass: 0 8px 32px rgba(15,23,42,0.10);
  --radius: 18px;
  --radius-sm: 12px;
  --radius-xs: 8px;
  --sidebar-w: 248px;
  --topbar-h: 62px;
}
[data-theme="dark"] {
  --primary: #E05A88;
  --primary-light: #2D1923;
  --primary-dark: #B83060;
  --primary-glow: rgba(224,90,136,0.15);
  --secondary: #58B2E0;
  --secondary-light: #1A2F3D;
  --accent: #FBBF24;
  --success: #34D399;
  --warning: #FBBF24;
  --danger: #F87171;
  --purple: #A78BFA;
  --bg: #0A090B;
  --surface: #121115;
  --surface-2: #1E1D22;
  --surface-3: #2A292F;
  --surface-glass: rgba(18, 17, 21, 0.85);
  --border: #232227;
  --border-strong: #333238;
  --text: #F4F4F5;
  --text-2: #A1A1AA;
  --text-3: #52525B;
  --shadow: 0 2px 8px rgba(0,0,0,0.45), 0 8px 24px rgba(0,0,0,0.35);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.55), 0 16px 40px rgba(0,0,0,0.40);
  --shadow-float: 0 8px 24px rgba(0,0,0,0.65), 0 24px 64px rgba(0,0,0,0.45);
  --shadow-glass: 0 8px 32px rgba(0,0,0,0.50);
}

*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.6;transition:background .3s,color .3s}
button{cursor:pointer;font-family:inherit;border:none;outline:none}
input,textarea,select{font-family:inherit;outline:none}
a{text-decoration:none;color:inherit}

/* ===== APP SHELL ===== */
.app-shell{display:flex;min-height:100vh}

/* ===== SIDEBAR - Enhanced */
.sidebar{
  width:var(--sidebar-w);height:100vh;background:var(--surface-highlight);
  border-right:1.5px solid var(--border-highlight);display:flex;flex-direction:column;
  position:fixed;left:0;top:0;z-index:100;transition:.3s cubic-bezier(.4,0,.2,1);overflow:hidden;box-shadow:2px 0 8px rgba(196,48,94,0.04);
}
#navMenu{flex:1;overflow-y:auto;overflow-x:hidden;}
.sidebar.collapsed{width:64px}
.sidebar-header{
  flex-shrink:0;padding:22px 18px 18px;display:flex;align-items:center;gap:12px;
  border-bottom:1.5px solid var(--border);background:linear-gradient(135deg,rgba(196,48,94,0.04),rgba(58,159,216,0.02));
}
.sidebar-brand{
  font-family:'Syne',sans-serif;font-size:18px;font-weight:900;
  white-space:nowrap;overflow:hidden;transition:.3s;letter-spacing:-.3px;
}
.sidebar.collapsed .sidebar-brand,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}
.sidebar.collapsed .sidebar-brand{display:none}
.sidebar.collapsed .sidebar-summary{display:none}

.nav-item{
  display:flex;align-items:center;gap:13px;padding:10px 14px;cursor:pointer;
  transition:all .2s cubic-bezier(.4,0,.2,1);color:var(--text-2);position:relative;margin:2px 10px;
  border-radius:var(--radius-sm);white-space:nowrap;font-weight:500;
}
.nav-item:hover{background:linear-gradient(135deg,rgba(196,48,94,0.08),rgba(58,159,216,0.06));color:var(--text);transform:translateX(3px)}
.nav-item.active{background:linear-gradient(135deg,rgba(196,48,94,0.16),rgba(196,48,94,0.1));color:var(--primary);font-weight:700}
.nav-item.active::before{
  content:'';position:absolute;left:-12px;top:50%;transform:translateY(-50%);
  width:4px;height:60%;background:linear-gradient(to bottom,var(--primary),var(--secondary));border-radius:0 3px 3px 0;
  transition:.2s;
}
.nav-icon{font-size:18px;flex-shrink:0;width:22px;text-align:center;opacity:.75;transition:.2s}
.nav-item.active .nav-icon{opacity:1;transform:scale(1.15)}
.nav-label{font-size:13px;font-weight:600;transition:.3s;letter-spacing:-.1px}
.nav-badge{
  margin-left:auto;background:linear-gradient(135deg,var(--primary),#9e1f47);color:#fff;
  font-size:10px;font-weight:800;padding:2px 8px;border-radius:12px;
  min-width:22px;text-align:center;transition:.2s;
}


.sidebar-footer{flex-shrink:0;padding:12px 12px;border-top:1.5px solid var(--border);display:flex;align-items:center;gap:9px;background:linear-gradient(135deg,rgba(196,48,94,0.02),rgba(58,159,216,0.01))}
.sidebar-footer .logout-btn{flex-shrink:0;width:36px;height:36px;border-radius:var(--radius-sm);background:linear-gradient(135deg,rgba(208,48,48,0.12),rgba(208,48,48,0.08));color:var(--danger);border:1.5px solid rgba(208,48,48,0.25);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;font-weight:700}
.sidebar-footer .logout-btn:hover{background:linear-gradient(135deg,rgba(208,48,48,0.2),rgba(208,48,48,0.12));border-color:var(--danger);transform:translateY(-2px)}
.user-card{display:flex;align-items:center;gap:11px;padding:8px 6px;border-radius:var(--radius-sm);cursor:pointer;transition:.2s cubic-bezier(.4,0,.2,1);flex:1;min-width:0}
.user-card:hover{background:var(--surface-2)}
.user-avatar{
  width:36px;height:36px;border-radius:10px;
  background:linear-gradient(135deg,var(--primary),#9e1f47);
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:800;font-size:13px;flex-shrink:0;letter-spacing:.5px;transition:.2s;
}
.user-card:hover .user-avatar{transform:scale(1.08);box-shadow:0 4px 12px rgba(196,48,94,0.3)}
.user-info{overflow:hidden}
.user-name{font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-.1px}
.user-role{font-size:11px;color:var(--text-3);font-weight:500}
.sidebar.collapsed .user-info{display:none}

/* TOPBAR - Enhanced */
.topbar{
  height:var(--topbar-h);background:var(--surface-highlight);border-bottom:1.5px solid var(--border-highlight);
  display:flex;align-items:center;padding:0 24px;gap:14px;
  position:sticky;top:0;z-index:50;box-shadow:0 1px 6px rgba(196,48,94,0.05);
}
.topbar-left{display:flex;align-items:center;gap:14px;flex:1}
.topbar-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:800;color:var(--text);letter-spacing:-.3px}
.topbar-right{display:flex;align-items:center;gap:8px}
.icon-btn{
  width:38px;height:38px;border-radius:var(--radius-sm);background:var(--surface-2);
  display:flex;align-items:center;justify-content:center;font-size:17px;
  cursor:pointer;border:1.5px solid var(--border);transition:all .25s cubic-bezier(.4,0,.2,1);color:var(--text-2);
  position:relative;font-weight:600;
}
.icon-btn:hover{background:linear-gradient(135deg,rgba(196,48,94,0.1),rgba(58,159,216,0.08));color:var(--primary);border-color:var(--primary);transform:translateY(-2px);box-shadow:0 4px 12px rgba(196,48,94,0.15)}
.icon-btn:active{transform:translateY(0)}
.notif-dot{
  position:absolute;top:6px;right:6px;width:8px;height:8px;
  border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));border:2px solid var(--surface);
  pointer-events:none;animation:pulse 2s infinite;
}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(196,48,94,0.5)}50%{box-shadow:0 0 0 6px rgba(196,48,94,0)}}


/* ===== MAIN CONTENT ===== */
.main-content{margin-left:var(--sidebar-w);flex:1;transition:.3s;min-height:100vh;display:flex;flex-direction:column}
.main-content.expanded{margin-left:64px}
.content-area{padding:24px;flex:1}

/* ===== BASE COMPONENTS ===== */
.page-header{margin-bottom:32px}
.page-header h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:900;line-height:1.2;letter-spacing:-.5px;background:linear-gradient(135deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.page-header p{color:var(--text-2);font-size:14px;margin-top:6px;font-weight:600;letter-spacing:-.1px}
.breadcrumb{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-3);margin-bottom:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:700}
.breadcrumb span:last-child{color:var(--primary);font-weight:800}

.card{
  background:hsl(var(--primary-hsl) / .08);
  border:1.5px solid hsl(var(--primary-hsl) / .14);
  border-radius:var(--radius);
  padding:24px;
  box-shadow:var(--shadow);
  transition:.3s;
}

/* STAT CARDS - Enhanced with Glassmorphism */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:24px}
.stat-card{
  background:hsl(var(--primary-hsl) / .06);
  border:1.5px solid hsl(var(--primary-hsl) / .14);
  border-radius:var(--radius);
  padding:22px 20px 20px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:16px;
  transition:all .3s cubic-bezier(.4,0,.2,1);cursor:pointer;position:relative;overflow:hidden;
}
.stat-card::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(196,48,94,0.06),rgba(58,159,216,0.04));
  opacity:0;transition:.3s;pointer-events:none;
}
.stat-card::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,var(--primary),var(--secondary),var(--accent));
  opacity:0;transition:.3s;
}
.stat-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-float);border-color:var(--border-strong)}
.stat-card:hover::before{opacity:1}
.stat-card:hover::after{opacity:1}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;transition:.3s}
.stat-icon.pink{background:linear-gradient(135deg,rgba(196,48,94,0.15),rgba(196,48,94,0.08));color:var(--primary)}
.stat-icon.amber{background:linear-gradient(135deg,rgba(212,130,10,0.15),rgba(212,130,10,0.08));color:#9a5800}
.stat-icon.blue{background:linear-gradient(135deg,rgba(58,159,216,0.15),rgba(58,159,216,0.08));color:var(--secondary)}
.stat-icon.red{background:linear-gradient(135deg,rgba(208,48,48,0.15),rgba(208,48,48,0.08));color:#a01818}
[data-theme="dark"] .stat-icon.pink{color:var(--primary)}
[data-theme="dark"] .stat-icon.amber{color:var(--accent)}
[data-theme="dark"] .stat-icon.blue{color:var(--secondary)}
[data-theme="dark"] .stat-icon.red{color:var(--danger)}
.stat-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:900;line-height:1;letter-spacing:-1px}
.stat-label{font-size:12px;color:var(--text-2);margin-top:2px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.stat-trend{font-size:11px;margin-top:6px;font-weight:700;letter-spacing:.2px}
.stat-trend.up{color:var(--success)}
.stat-trend.down{color:var(--danger)}

/* GRID */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}

/* COURSE GRID - Enhanced with Better Gradients */
.course-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:18px}
.course-card{
  background:hsl(var(--primary-hsl) / .08);
  border:1.5px solid hsl(var(--primary-hsl) / .14);
  border-radius:var(--radius);
  overflow:hidden;cursor:pointer;transition:all .3s cubic-bezier(.4,0,.2,1);box-shadow:var(--shadow);
  position:relative;
}
.course-card::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,0.12),rgba(196,48,94,0.05));
  opacity:0;transition:.3s;pointer-events:none;z-index:1;
}
.course-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:var(--shadow-float);border-color:var(--border-strong)}
.course-card:hover::before{opacity:1}
.course-thumb{height:140px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:56px;transition:.3s;background:linear-gradient(135deg,#f0e8ec,#fcedf3)}
.course-card:hover .course-thumb{transform:scale(1.08);filter:brightness(1.1)}
.course-badge{
  position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.3);
  color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;
  backdrop-filter:blur(12px);letter-spacing:.4px;border:1px solid rgba(255,255,255,0.3);
  transition:.2s;
}
.course-card:hover .course-badge{background:rgba(0,0,0,0.5);transform:scale(1.1)}
.bg-pink{background:linear-gradient(135deg,#D64878 0%,#C4305E 50%,#8A1840 100%)}
.bg-blue{background:linear-gradient(135deg,#4EAFDD 0%,#3A9FD8 50%,#175f8a 100%)}
.bg-amber{background:linear-gradient(135deg,#E09424 0%,#D4820A 50%,#8A4800 100%)}
.bg-purple{background:linear-gradient(135deg,#8F5FE0,#7C4FD8 50%,#C4305E)}
.bg-teal{background:linear-gradient(135deg,#26B5A8 0%,#0EA898 50%,#3A9FD8)}
.bg-rose{background:linear-gradient(135deg,#E84C62 0%,#E83050 50%,#C4305E)}
.course-body{padding:18px 18px 16px;position:relative;z-index:2}
.course-title{font-weight:700;font-size:15px;margin-bottom:8px;line-height:1.4;letter-spacing:-.1px;color:var(--text)}
.course-meta{display:flex;align-items:center;gap:12px;color:var(--text-2);font-size:12px;font-weight:600}
.progress-bar{height:5px;border-radius:4px;background:var(--surface-3);overflow:hidden;margin-top:12px}
.progress-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--primary),var(--secondary));transition:.8s cubic-bezier(.4,0,.2,1)}
.progress-label{display:flex;justify-content:space-between;font-size:11px;color:var(--text-2);margin:10px 0 6px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.course-footer{padding:14px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2);position:relative;z-index:2}

/* BADGES - Enhanced */
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.1px;transition:.2s;border:none}
.badge-pink{background:linear-gradient(135deg,rgba(196,48,94,0.16),rgba(196,48,94,0.1));color:var(--primary);font-weight:700}
.badge-blue{background:linear-gradient(135deg,rgba(58,159,216,0.16),rgba(58,159,216,0.1));color:var(--secondary);font-weight:700}
.badge-green{background:linear-gradient(135deg,rgba(14,168,152,0.16),rgba(14,168,152,0.1));color:var(--success);font-weight:700}
.badge-amber{background:linear-gradient(135deg,rgba(212,130,10,0.16),rgba(212,130,10,0.1));color:var(--accent);font-weight:700}
.badge-red{background:linear-gradient(135deg,rgba(208,48,48,0.16),rgba(208,48,48,0.1));color:var(--danger);font-weight:700}
.badge-gray{background:var(--surface-2);color:var(--text-2);border:1.5px solid var(--border);font-weight:600}
.badge-archived{background:var(--surface-2);color:var(--text-3);border:1px dashed var(--border);font-weight:600}
[data-theme="dark"] .badge-pink{background:linear-gradient(135deg,rgba(224,90,136,0.2),rgba(224,90,136,0.1));color:var(--primary)}
[data-theme="dark"] .badge-blue,[data-theme="dark"] .badge-green{background:linear-gradient(135deg,rgba(58,159,216,0.2),rgba(58,159,216,0.1));color:var(--secondary)}
[data-theme="dark"] .badge-amber{background:linear-gradient(135deg,rgba(224,160,64,0.2),rgba(224,160,64,0.1));color:var(--accent)}
[data-theme="dark"] .badge-red{background:linear-gradient(135deg,rgba(224,88,88,0.2),rgba(224,88,88,0.1));color:var(--danger)}

/* BUTTONS - Modern & Eye-catching */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 20px;border-radius:var(--radius-sm);font-size:13px;font-weight:700;transition:all .25s cubic-bezier(.4,0,.2,1);cursor:pointer;letter-spacing:.2px;position:relative;overflow:hidden}
.btn::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,0.3),rgba(255,255,255,0));
  opacity:0;transition:.3s;
}
.btn-primary{background:linear-gradient(135deg,#D64878,var(--primary),#8A1840);color:#fff;box-shadow:0 4px 12px rgba(196,48,94,0.35);border:none}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(196,48,94,0.45);background:linear-gradient(135deg,#E05A90,#D64878,#9e1f47)}
.btn-primary:active{transform:translateY(-1px)}
.btn-primary::before{opacity:1}
.btn-outline{background:transparent;border:2px solid var(--border);color:var(--text-2);font-weight:700}
.btn-outline:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.btn-ghost{background:transparent;color:var(--text-2);padding:9px 14px;font-weight:600}
.btn-ghost:hover{background:var(--surface-2);color:var(--primary)}
.btn-danger{background:linear-gradient(135deg,#E05858,#D03030);color:#fff;border:none;font-weight:700;box-shadow:0 4px 12px rgba(208,48,48,0.3)}
.btn-danger:hover{background:linear-gradient(135deg,#E07070,#E05858);transform:translateY(-2px);box-shadow:0 6px 16px rgba(208,48,48,0.4)}
.btn-success{background:linear-gradient(135deg,#26B5A8,var(--success),#0EA898);color:#fff;box-shadow:0 4px 12px rgba(14,168,152,0.3);font-weight:700}
.btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(14,168,152,0.4)}
.btn-sm{padding:8px 15px;font-size:12px}
.btn-full{width:100%}

/* FORM */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:11px;font-weight:700;color:var(--text-2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.7px}
.input-wrap{position:relative}
.input-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none;opacity:.6}
.input-wrap input,.input-wrap textarea{
  width:100%;padding:10px 13px 10px 38px;border-radius:var(--radius-sm);
  border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);
  font-size:13px;transition:all .2s;
}
.input-wrap input::placeholder{color:var(--text-3)}
.input-wrap input:focus{border-color:var(--primary);background:var(--surface);outline:none;box-shadow:0 0 0 3px var(--primary-glow)}
textarea{width:100%;padding:10px 13px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;transition:all .2s;resize:vertical;}
textarea:focus{border-color:var(--primary);background:var(--surface);outline:none;box-shadow:0 0 0 3px var(--primary-glow)}
select{padding:9px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;cursor:pointer;}
select:focus{border-color:var(--primary);outline:none}

/* TABS ROW (page-level) */
.tabs-row{display:flex;gap:2px;padding:3px;background:var(--surface-2);border-radius:var(--radius-sm);width:fit-content;margin-bottom:20px}
.tab-pill{padding:7px 16px;border-radius:var(--radius-xs);font-size:12px;font-weight:600;cursor:pointer;color:var(--text-2);transition:.15s;border:none;background:transparent;}
.tab-pill.active{background:var(--surface);color:var(--primary);font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,0.08)}
.tab-pill:hover:not(.active){background:rgba(196,48,94,0.06);color:var(--primary)}

/* TABLES */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.6px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text)}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--surface-2)}
.avatar-sm{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700}

/* ACTIVITY */
.activity-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
.activity-item:last-child{border-bottom:none}
.act-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.act-body{flex:1}
.act-text{font-size:13px}
.act-time{font-size:11px;color:var(--text-3);margin-top:2px;font-weight:500}

/* AUDIT */
.audit-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.audit-row:last-child{border-bottom:none}
.audit-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}

/* SEARCH */
.search-wrap{position:relative;flex:1;max-width:320px}
.search-wrap input{width:100%;padding:9px 12px 9px 34px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;}
.search-wrap input:focus{border-color:var(--primary);outline:none;background:var(--surface)}
.search-wrap::before{content:'🔍';position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13px;pointer-events:none}

/* UPLOAD ZONE */
.upload-zone{border:2px dashed var(--border);border-radius:var(--radius);padding:28px;text-align:center;cursor:pointer;transition:all .2s;background:var(--surface-2);}
.upload-zone:hover{border-color:var(--primary);background:var(--primary-light)}

/* PROGRESS BAR (charts) */
.chart-bar-row{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.chart-bar-label{font-size:12px;color:var(--text-2);width:90px;flex-shrink:0;text-align:right}
.chart-bar-track{flex:1;height:7px;background:var(--surface-2);border-radius:4px;overflow:hidden}
.chart-bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--primary),var(--primary-dark));transition:.8s cubic-bezier(.4,0,.2,1)}
.chart-bar-val{font-size:12px;font-weight:700;color:var(--text-2);width:36px;text-align:right}

/* QUIZ - Enhanced */
.quiz-option{
  display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:var(--radius-sm);
  border:1.5px solid var(--border);cursor:pointer;transition:all .2s cubic-bezier(.4,0,.2,1);font-size:13px;margin-bottom:10px;
  background:var(--surface-2);position:relative;overflow:hidden;font-weight:500;
}
.quiz-option::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(196,48,94,0.1),rgba(58,159,216,0.05));
  opacity:0;transition:.2s;
}
.quiz-option:hover{border-color:var(--primary);background:linear-gradient(135deg,rgba(196,48,94,0.08),var(--primary-light));transform:translateX(4px)}
.quiz-option.selected{border-color:var(--primary);background:linear-gradient(135deg,rgba(196,48,94,0.14),var(--primary-light));color:var(--primary);font-weight:700;box-shadow:0 4px 12px rgba(196,48,94,0.2)}
.quiz-option.correct{border-color:var(--success);background:linear-gradient(135deg,rgba(14,168,152,0.12),rgba(14,168,152,0.06));color:var(--success);font-weight:700}
.quiz-option.wrong{border-color:var(--danger);background:linear-gradient(135deg,rgba(208,48,48,0.12),rgba(208,48,48,0.06));color:var(--danger);font-weight:700}
.option-circle{
  width:24px;height:24px;border-radius:50%;border:2.5px solid var(--border);
  display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;
  flex-shrink:0;transition:all .2s;
}
.quiz-option.selected .option-circle{border-color:var(--primary);background:linear-gradient(135deg,var(--primary),#9e1f47);color:#fff}
[data-theme="dark"] .quiz-option.correct{color:var(--success)}


/* AI CHAT */
.chat-messages{flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:16px}
.chat-bubble{max-width:75%;padding:12px 16px;border-radius:var(--radius);font-size:13px;line-height:1.6}
.chat-bubble.user{
  background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  color:#fff;align-self:flex-end;border-bottom-right-radius:4px;
}
.chat-bubble.ai{
  background:var(--surface);border:1px solid var(--border);
  align-self:flex-start;border-bottom-left-radius:4px;
}
.chat-meta{font-size:11px;color:var(--text-3);margin-top:4px;display:flex;align-items:center;gap:4px;font-weight:500}
.chat-input-area{padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:flex-end;gap:10px;background:var(--surface);}
.chat-input{flex:1;padding:11px 14px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;resize:none;max-height:120px;transition:all .2s;}
.chat-input:focus{border-color:var(--primary);background:var(--surface);outline:none;box-shadow:0 0 0 3px var(--primary-glow)}
.chat-send{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;transition:all .2s;flex-shrink:0}
.chat-send:hover{transform:scale(1.07) translateY(-1px);box-shadow:0 4px 12px rgba(196,48,94,0.4)}
.ai-typing{display:flex;gap:4px;align-items:center;padding:4px 0}
.typing-dot{width:6px;height:6px;border-radius:50%;background:var(--text-3);animation:blink 1.2s infinite}
.typing-dot:nth-child(2){animation-delay:.2s}.typing-dot:nth-child(3){animation-delay:.4s}
@keyframes blink{0%,80%,100%{opacity:.25}40%{opacity:1}}

/* ICON UTILITIES */
.icon-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.2em;
  height: 1.2em;
  color: var(--primary);
  font-weight: bold;
  font-size: inherit;
  background: rgba(196,48,94,0.12);
  border-radius: 6px;
}

/* SETTINGS TOGGLE */
.setting-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)}
.setting-row:last-child{border-bottom:none}
.stu-notif-chip{display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:12px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit}
.stu-notif-chip:hover{border-color:var(--primary);color:var(--primary)}
.stu-notif-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.notif-filter-chip{display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:12px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit}
.notif-filter-chip:hover{border-color:var(--primary);color:var(--primary)}
.notif-filter-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.rec-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);cursor:pointer;font-size:11px;font-weight:500;color:var(--text-2);transition:.2s;white-space:nowrap;font-family:inherit;line-height:1.5}
.rec-chip:hover{border-color:var(--primary);color:var(--primary)}
.rec-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
.setting-info h4{font-size:14px;font-weight:600}
.setting-info p{font-size:12px;color:var(--text-2);margin-top:2px}
.toggle{position:relative;width:42px;height:23px}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:var(--border-strong);border-radius:12px;cursor:pointer;transition:.25s}
.toggle-slider::before{content:'';position:absolute;width:17px;height:17px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,0.2)}
.toggle input:checked + .toggle-slider{background:var(--primary)}
.toggle input:checked + .toggle-slider::before{transform:translateX(19px)}

/* DISCUSSION - Enhanced */
.discussion-card{background:hsl(var(--primary-hsl) / .08);border:1.5px solid hsl(var(--primary-hsl) / .14);border-radius:var(--radius);padding:20px;margin-bottom:14px;cursor:pointer;transition:all .3s cubic-bezier(.4,0,.2,1);box-shadow:var(--shadow);position:relative;overflow:hidden}
.discussion-card::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(196,48,94,0.04),rgba(58,159,216,0.02));
  opacity:0;transition:.3s;
}
.discussion-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-float);border-color:var(--border-strong)}
.discussion-card:hover::before{opacity:1}
.discussion-reply{background:linear-gradient(135deg,rgba(196,48,94,0.06),rgba(58,159,216,0.03));border-radius:var(--radius-sm);padding:16px;margin-top:12px;border-left:4px solid var(--primary);transition:.2s}
.discussions-container{display:grid;grid-template-columns:330px 1fr;gap:24px;margin-bottom:20px}
.discussions-list-panel{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden;transition:.3s}
.discussions-list-panel:hover{border-color:var(--border-strong)}
.discussions-header{padding:18px;border-bottom:1.5px solid var(--border)}
.discussions-search{width:100%;padding:10px 14px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;font-weight:500;transition:.2s}
.discussions-search:focus{outline:none;border-color:var(--primary);background:var(--surface);box-shadow:0 0 0 3px rgba(196,48,94,0.12)}
.discussions-list{flex:1;overflow-y:auto;padding:10px}
.discussions-list::-webkit-scrollbar{width:6px}
.discussions-list::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.discussion-item{padding:14px;border-radius:var(--radius-sm);background:var(--surface-2);border:1.5px solid transparent;cursor:pointer;transition:all .2s cubic-bezier(.4,0,.2,1);margin-bottom:10px;position:relative;overflow:hidden}
.discussion-item::before{
  content:'';position:absolute;left:0;top:0;width:3px;height:0;background:linear-gradient(to bottom,var(--primary),var(--secondary));transition:.3s;
}
.discussion-item:hover{background:var(--primary-light);border-color:var(--primary);transform:translateX(4px)}
.discussion-item:hover::before{height:100%}
.discussion-item.active{background:var(--primary-light);border-color:var(--primary)}
.discussion-item-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-.2px}
.discussion-item-meta{font-size:12px;color:var(--text-2);display:flex;justify-content:space-between;align-items:center;gap:8px;font-weight:500}
.discussion-count{display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,rgba(196,48,94,0.16),rgba(196,48,94,0.1));color:var(--primary);padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;transition:.2s}
.new-disc-btn{margin:14px;border-radius:var(--radius-sm);background:linear-gradient(135deg,#D64878,var(--primary));color:#fff;font-size:13px;font-weight:700;text-align:center;cursor:pointer;transition:.2s;border:none;padding:12px;box-shadow:0 4px 12px rgba(196,48,94,0.3);letter-spacing:.1px}
.new-disc-btn:hover{background:linear-gradient(135deg,#E05A90,#D64878);transform:translateY(-2px);box-shadow:0 6px 18px rgba(196,48,94,0.4)}
.discussion-detail-panel{background:var(--surface);border-radius:var(--radius);border:1.5px solid var(--border);display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden}
.discussion-detail-header{padding:22px;border-bottom:1.5px solid var(--border);background:linear-gradient(135deg,rgba(196,48,94,0.04),rgba(58,159,216,0.02))}
.discussion-detail-title{font-family:'Syne',sans-serif;font-size:19px;font-weight:800;margin-bottom:10px;letter-spacing:-.3px;color:var(--text)}
.discussion-detail-meta{display:flex;align-items:center;gap:18px;font-size:13px;color:var(--text-2);flex-wrap:wrap;font-weight:600}
.discussion-detail-content{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column}
.discussion-detail-content::-webkit-scrollbar{width:6px}
.discussion-detail-content::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.discussion-original-post{margin-bottom:24px;padding:16px;background:var(--primary-light);border:2px solid var(--primary);border-radius:var(--radius)}
.post-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.post-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.post-info{flex:1;min-width:0}
.post-author{font-weight:700;font-size:14px;color:var(--primary)}
.post-time{font-size:12px;color:var(--text-3);margin-top:2px}
.post-body{font-size:13px;color:var(--text-2);line-height:1.6;white-space:pre-line}
.post-actions{display:flex;gap:10px;margin-top:12px}
.post-action-btn{display:flex;align-items:center;gap:6px;padding:8px 10px;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text-2);font-size:12px;font-weight:500;cursor:pointer;transition:.2s}
.post-action-btn:hover{color:var(--primary);border-color:var(--primary)}
.reply-item{margin-bottom:14px;padding:12px 16px;background:var(--surface-2);border-left:3px solid var(--primary);border-radius:var(--radius-sm)}
.reply-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.reply-avatar{width:30px;height:30px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0}
.reply-author{font-weight:600;font-size:13px;color:var(--text)}
.reply-time{font-size:11px;color:var(--text-3);margin-left:auto}
.reply-body{font-size:13px;color:var(--text-2);line-height:1.5;white-space:pre-line}
.reply-actions{display:flex;gap:10px}
.reply-action-btn{display:flex;align-items:center;gap:4px;padding:5px 10px;background:none;border:none;color:var(--text-3);font-size:11px;cursor:pointer;transition:.15s}
.reply-action-btn:hover{color:var(--primary)}
.discussion-composer{margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:10px}
.composer-label{font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px}
.composer-textarea{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;resize:none;min-height:70px;transition:border-color .2s}
.composer-textarea:focus{outline:none;border-color:var(--primary)}
.composer-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}

/* NOTIFICATION PANEL */
.notif-panel{
  position:absolute;top:calc(100% + 10px);right:0;width:340px;background:var(--surface);
  border:1.5px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-float);
  z-index:200;display:none;backdrop-filter:blur(12px);overflow:hidden;
}
.notif-panel::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(196,48,94,0.04),rgba(58,159,216,0.02));
  pointer-events:none;z-index:0;
}
.notif-panel.open{display:block;animation:fadeUp .2s ease}
.notif-header{padding:16px 18px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1}
.notif-header h3{font-size:15px;font-weight:800;letter-spacing:-.1px}
.notif-item{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-bottom:1.5px solid var(--border);cursor:pointer;transition:all .2s;background:transparent;position:relative;z-index:1}
.notif-item:hover{background:linear-gradient(135deg,rgba(196,48,94,0.06),rgba(58,159,216,0.04))}
.notif-item.unread{background:linear-gradient(135deg,rgba(196,48,94,0.12),rgba(196,48,94,0.08))}
.notif-item:last-child{border-bottom:none}
.notif-text{font-size:13px;line-height:1.5;font-weight:500;color:var(--text)}
.notif-time{font-size:11px;color:var(--text-3);margin-top:3px;font-weight:600;letter-spacing:.2px}
.auth-link{color:var(--primary);font-weight:700;cursor:pointer;font-size:12px;transition:.2s}
.auth-link:hover{opacity:.8;text-decoration:underline}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:400;display:none;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(8px);animation:fadeIn .2s ease}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border-radius:22px;padding:32px;width:100%;max-width:520px;box-shadow:var(--shadow-glass);animation:fadeUp .25s ease;max-height:90vh;overflow-y:auto;border:1.5px solid var(--border);position:relative}
.modal::before{
  content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(196,48,94,0.04),rgba(58,159,216,0.02));
  border-radius:22px;pointer-events:none;
}
.modal-lg{max-width:680px}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;position:relative;z-index:1}
.modal-header h2{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;letter-spacing:-.3px;color:var(--text)}
.modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:20px;border-top:1.5px solid var(--border);position:relative;z-index:1}


/* TOAST */
.toast{
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);
  background:var(--text);color:#fff;padding:12px 22px;border-radius:50px;
  font-size:13px;font-weight:700;box-shadow:0 8px 28px rgba(0,0,0,0.25);
  transition:transform .28s cubic-bezier(.4,0,.2,1),opacity .28s ease;opacity:0;pointer-events:none;
  white-space:nowrap;z-index:999;letter-spacing:.2px;
}
.toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.toast.success{background:linear-gradient(135deg,var(--primary),#9e1f47);box-shadow:0 8px 28px rgba(196,48,94,0.3)}
.toast.warning{background:linear-gradient(135deg,var(--accent),#c4670a);box-shadow:0 8px 28px rgba(212,130,10,0.3)}
.toast.error{background:linear-gradient(135deg,var(--danger),#b01818);box-shadow:0 8px 28px rgba(208,48,48,0.3)}

/* SIDEBAR OVERLAY */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99}

/* ANIMATIONS */
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideIn{from{opacity:0;transform:translateX(-8px)}to{opacity:1;transform:translateX(0)}}
.fade-in{animation:fadeUp .28s cubic-bezier(.4,0,.2,1)}

/* RESPONSIVE */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.mobile-open{transform:translateX(0)}
  .main-content{margin-left:0!important}
  .sidebar-overlay{display:block;opacity:0;pointer-events:none;transition:.3s}
  .sidebar-overlay.open{opacity:1;pointer-events:all}
  .stats-grid{grid-template-columns:1fr 1fr}
  .grid-2,.grid-3{grid-template-columns:1fr}
  .course-grid{grid-template-columns:1fr}
  .notif-panel{width:290px;right:-60px}
  .asg-two-col{grid-template-columns:1fr!important}
  .discussions-container{grid-template-columns:1fr!important}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .content-area{padding:16px}
  .tabs-row{width:100%}
  .tab-pill{flex:1;text-align:center}
}
</style>
<?php if ($db_theme): ?>
<?php
if (!function_exists('php_hsl_parts')) {
    function php_hsl_parts($hsl) {
        if (preg_match('/([\d.]+)\s+([\d.]+)%\s+([\d.]+)%/', (string)$hsl, $m)) {
            return [(float)$m[1], (float)$m[2], (float)$m[3]];
        }
        return [0, 0, 50];
    }
    function php_adjust_hsl($hsl, $s, $l) {
        $parts = php_hsl_parts($hsl);
        return "{$parts[0]} {$s}% {$l}%";
    }
    function php_shift_l($hsl, $delta) {
        $parts = php_hsl_parts($hsl);
        $newL = max(0, min(100, $parts[2] + $delta));
        return "{$parts[0]} {$parts[1]}% {$newL}%";
    }
}
$primary_hsl = $db_theme['primary_color'];
$primary_parts = php_hsl_parts($primary_hsl);
$hue = $primary_parts[0];

if ($db_theme['is_dark']) {
    $dk_p = $db_theme['primary_color'];
    $dk_d = $db_theme['primary_dark'];
    $dk_l = $db_theme['primary_light'];
    $dk_bg = $db_theme['bg_color'];
    $dk_sf = $db_theme['surface_color'];
    $dk_sf2 = php_shift_l($db_theme['bg_color'], 4);
    $dk_sf3 = php_shift_l($db_theme['bg_color'], 8);
    $dk_bd = $db_theme['border_color'];
    $dk_bd_st = php_shift_l($db_theme['border_color'], 8);
    $dk_tx = $db_theme['text_color'];
    $dk_tx2 = $db_theme['text_secondary'];
    $dk_tx3 = php_shift_l($db_theme['text_secondary'], -15);
} else {
    $dk_p = php_adjust_hsl($primary_hsl, 80, 65);
    $dk_d = php_adjust_hsl($primary_hsl, 80, 53);
    $dk_l = php_adjust_hsl($primary_hsl, 30, 20);
    $dk_bg = "{$hue} 12% 8%";
    $dk_sf = "{$hue} 12% 12%";
    $dk_sf2 = "{$hue} 12% 16%";
    $dk_sf3 = "{$hue} 12% 20%";
    $dk_bd = "{$hue} 12% 18%";
    $dk_bd_st = "{$hue} 12% 25%";
    $dk_tx = "{$hue} 8% 92%";
    $dk_tx2 = "{$hue} 8% 70%";
    $dk_tx3 = "{$hue} 8% 45%";
}
?>
<style id="lf-theme-vars">
:root {
  --primary: hsl(<?php echo htmlspecialchars($db_theme['primary_color']); ?>);
  --primary-dark: hsl(<?php echo htmlspecialchars($db_theme['primary_dark']); ?>);
  --primary-light: hsl(<?php echo htmlspecialchars($db_theme['primary_light']); ?>);
  --bg: hsl(<?php echo htmlspecialchars($db_theme['bg_color']); ?>);
  --surface: hsl(<?php echo htmlspecialchars($db_theme['surface_color']); ?>);
  --surface-2: hsl(<?php echo htmlspecialchars($db_theme['surface_color']); ?> / 0.72);
  --border: hsl(<?php echo htmlspecialchars($db_theme['border_color']); ?>);
  --text: hsl(<?php echo htmlspecialchars($db_theme['text_color']); ?>);
  --text-2: hsl(<?php echo htmlspecialchars($db_theme['text_secondary']); ?>);
  --text-3: hsl(<?php echo htmlspecialchars($db_theme['text_secondary']); ?> / 0.6);
  <?php if ($db_theme['accent_color']): ?>--secondary: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>); --accent: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>);<?php endif; ?>
  --primary-glow: hsla(<?php echo htmlspecialchars($db_theme['primary_color']); ?>, 0.12);
}
[data-theme="dark"] {
  --primary: hsl(<?php echo htmlspecialchars($dk_p); ?>);
  --primary-dark: hsl(<?php echo htmlspecialchars($dk_d); ?>);
  --primary-light: hsl(<?php echo htmlspecialchars($dk_l); ?>);
  --bg: hsl(<?php echo htmlspecialchars($dk_bg); ?>);
  --surface: hsl(<?php echo htmlspecialchars($dk_sf); ?>);
  --surface-2: hsl(<?php echo htmlspecialchars($dk_sf2); ?>);
  --surface-3: hsl(<?php echo htmlspecialchars($dk_sf3); ?>);
  --border: hsl(<?php echo htmlspecialchars($dk_bd); ?>);
  --border-strong: hsl(<?php echo htmlspecialchars($dk_bd_st); ?>);
  --text: hsl(<?php echo htmlspecialchars($dk_tx); ?>);
  --text-2: hsl(<?php echo htmlspecialchars($dk_tx2); ?>);
  --text-3: hsl(<?php echo htmlspecialchars($dk_tx3); ?>);
  <?php if ($db_theme['accent_color']): ?>--secondary: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>); --accent: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>);<?php endif; ?>
  --primary-glow: hsla(<?php echo htmlspecialchars($dk_p); ?>, 0.18);
}
</style>
<?php endif; ?>
</head>
<body>

<div class="app-shell">

  <!-- SIDEBAR OVERLAY (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- ===== SIDEBAR ===== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <svg width="40" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <polygon points="26,13 41,20.5 26,28 11,20.5" fill="var(--primary)"/>
          <line x1="38" y1="20.5" x2="38" y2="31" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="38" cy="33" r="2.2" fill="var(--primary)"/>
          <path d="M13,36 Q18,32 23,36 Q28,40 33,36 Q38,32 43,36" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" fill="none"/>
          <path d="M15,42 Q20,38 25,42 Q30,46 35,42 Q40,38 45,42" stroke="var(--primary)" stroke-width="1.4" stroke-linecap="round" fill="none" opacity="0.6"/>
        </svg>
            <div class="sidebar-brand">Learn<span style="color:var(--primary)">Flow</span></div>
    </div>

    <div id="navMenu" style="padding:8px 0"></div>

    <!-- SIDEBAR SUMMARY (replaces Analytics tab) -->
    <div class="sidebar-summary" style="padding:8px 14px 10px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:8px">Overview</div>
      <div style="display:flex;flex-direction:column;gap:6px">
        <div onclick="navigate('assignments')" style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;border-radius:var(--radius-sm);background:var(--surface-2);cursor:pointer;transition:.15s" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='var(--surface-2)'">
          <span style="font-size:12px;color:var(--text-2)">📝 Pending</span>
          <span style="font-size:13px;font-weight:700;color:var(--accent)"><?php echo (int)$stat_pending_asg; ?></span>
        </div>
        <div onclick="navigate('quizzes')" style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;border-radius:var(--radius-sm);background:var(--surface-2);cursor:pointer;transition:.15s" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='var(--surface-2)'">
          <span style="font-size:12px;color:var(--text-2)">🧪 Upcoming</span>
          <span style="font-size:13px;font-weight:700;color:var(--secondary)"><?php echo (int)$stat_upcoming_quizzes; ?></span>
        </div>
      </div>
    </div>

    <div class="sidebar-footer">
      <div class="user-card" style="flex:1;min-width:0;display:flex;align-items:center;gap:10px;padding:4px 2px;border-radius:var(--radius-sm);cursor:pointer;transition:.15s" onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
        <div class="user-avatar" id="sidebarUserAvatar" style="flex-shrink:0"><?php if ($student_avatar_url): ?><img src="<?php echo htmlspecialchars($student_avatar_url); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:9px" alt=""><?php else: ?><?php echo htmlspecialchars($initials); ?><?php endif; ?></div>
        <div class="user-info" style="overflow:hidden">
          <div class="user-name"><?php echo htmlspecialchars($student_name); ?></div>
          <div class="user-role">Student · BSIT 3-1</div>
        </div>
      </div>
      <button onclick="doLogout()" class="logout-btn" title="Sign Out">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 5 12 10 7"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      </button>
    </div>
  </aside>

  <!-- ===== MAIN CONTENT ===== -->
  <div class="main-content" id="mainContent">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <button class="icon-btn" id="menuBtn" onclick="toggleSidebar()" title="Toggle sidebar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
        <div class="topbar-title" id="topbarTitle">Dashboard</div>
      </div>
      <div class="topbar-right">
        <!-- Notifications -->
        <div style="position:relative;display:flex;align-items:center">
          <div class="icon-btn" id="notifBtn" onclick="toggleNotifs()" title="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            <?php if ($unread_notif_count > 0): ?>
            <div class="notif-dot" id="topbarNotifDot"></div>
            <?php else: ?>
            <div class="notif-dot" id="topbarNotifDot" style="display:none"></div>
            <?php endif; ?>
          </div>
          <div class="notif-panel" id="notifPanel">
            <div class="notif-header">
              <h3>Notifications <span id="topNotifUnreadBadge" style="font-size:11px;background:var(--danger);color:#fff;padding:1px 7px;border-radius:10px;font-weight:700;margin-left:4px;display:<?php echo $unread_notif_count > 0 ? 'inline' : 'none'; ?>"><?php echo $unread_notif_count ?: ''; ?></span></h3>
              <span class="auth-link" style="font-size:12px" onclick="stuMarkAllRead();closeNotifs()">Mark all read</span>
            </div>
            <div id="topNotifList"></div>
            <div style="padding:12px 16px;text-align:center;border-top:1px solid var(--border)">
              <span class="auth-link" style="font-size:12px" onclick="navigate('notifications');closeNotifs()">View all notifications</span>
            </div>
          </div>
        </div>
        <div class="icon-btn" onclick="toggleDark()" id="darkBtn" title="Toggle dark mode"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg></div>
        <div class="icon-btn" onclick="navigate('settings')" title="Settings"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg></div>
        <div class="user-avatar" id="topbarUserAvatar" style="cursor:pointer" onclick="navigate('settings')" title="Account Settings"><?php if ($student_avatar_url): ?><img src="<?php echo htmlspecialchars($student_avatar_url); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:9px" alt=""><?php else: ?><?php echo $initials; ?><?php endif; ?></div>
      </div>
    </div>

    <!-- CONTENT AREA -->
    <div class="content-area" id="contentArea"></div>
  </div>

</div><!-- end app-shell -->

<!-- ===== QUIZ MODAL ===== -->
<div class="modal-overlay" id="quizModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2>📝 <span id="quizModalTitle">Web Technologies Quiz 3</span></h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('quizModal')">✕</button>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding:10px 14px;background:var(--surface-2);border-radius:var(--radius-sm)">
      <span style="font-size:13px;color:var(--text-2)">Question <strong id="qNum">1</strong> of <strong id="qTotal">5</strong></span>
      <span style="font-size:13px;color:var(--danger);font-weight:600">⏰ <span id="quizTimer">10:00</span></span>
    </div>
    <!-- Progress -->
    <div class="progress-bar" style="margin-bottom:16px">
      <div class="progress-fill" id="quizProgress" style="width:20%"></div>
    </div>
    <div id="quizBody"></div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" id="prevBtn" onclick="quizNav(-1)" disabled>← Prev</button>
      <button class="btn btn-primary btn-sm" id="nextBtn" onclick="quizNav(1)">Next →</button>
    </div>
  </div>
</div>

<!-- ===== SUBMIT ASSIGNMENT MODAL ===== -->
<div class="modal-overlay" id="submitModal">
  <div class="modal">
    <div class="modal-header">
      <h2>📤 Submit Assignment</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('submitModal')">✕</button>
    </div>
    <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px">
      <div style="font-weight:700;font-size:14px" id="submitTitle">Lab Activity 4: Inheritance & Polymorphism</div>
      <div style="font-size:12px;color:var(--text-2);margin-top:4px" id="submitCourse">OOP (CS 311) · Due: Apr 25, 2026</div>
    </div>
    <input type="file" id="submitFileInput" multiple accept=".pdf,.docx,.doc,.zip,.pptx,.xlsx,.py,.java,.cpp,.txt" style="display:none" onchange="handleSubmitFiles(this)">
    <div class="upload-zone" onclick="document.getElementById('submitFileInput').click()" ondragover="event.preventDefault();this.style.borderColor='var(--primary)'" ondragleave="this.style.borderColor=''" ondrop="handleSubmitDrop(event)">
      <div style="font-size:36px;margin-bottom:8px">📁</div>
      <div style="font-weight:600;font-size:14px">Click to browse or drag & drop files</div>
      <div style="font-size:12px;color:var(--text-2);margin-top:4px">PDF, DOCX, ZIP, code files — Max 10MB each</div>
    </div>
    <div id="submitFileList" style="margin-top:10px;display:flex;flex-direction:column;gap:6px"></div>
    <div class="form-group" style="margin-top:14px">
      <label class="form-label">Remarks (optional)</label>
      <textarea id="submitRemarks" style="min-height:70px" placeholder="Add any notes for your instructor..."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" onclick="closeModal('submitModal')">Cancel</button>
      <button class="btn btn-success btn-sm" onclick="doSubmitAssignment()">📤 Submit</button>
    </div>
  </div>
</div>

<!-- ===== INSTRUCTIONS MODAL ===== -->
<div class="modal-overlay" id="instrModal">
  <div class="modal">
    <div class="modal-header">
      <h2>📋 <span id="instrModalTitle">Quiz Instructions</span></h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('instrModal')">✕</button>
    </div>
    <div id="instrModalBody" style="font-size:13px;color:var(--text-2);line-height:1.8"></div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" onclick="closeModal('instrModal')">Close</button>
    </div>
  </div>
</div>

<!-- ===== TOAST ===== -->
<div class="toast" id="toast"></div>

<script>
// ===== STATE =====
let sidebarCollapsed = false;
let darkMode = false;
let currentPage = 'dashboard';
let notifOpen = false;
let quizCurrent = 0;
let quizAnswers = {};
let quizTimerSecs = 600;
let quizTimerInt = null;

// AI chat history
let chatHistory = [];

const pageTitles = {
  dashboard:'Dashboard',
  courses:'My Courses',
  assignments:'Assignments',
  quizzes:'Quizzes',
  discussions:'Discussions',
  notifications:'Notifications',
  settings:'Settings',
};

// ===== INIT =====
// ===== SVG ICON SYSTEM =====
const IC = {
  home:        `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
  courses:     `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>`,
  assignments: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>`,
  quizzes:     `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  discussions: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>`,
  analytics:   `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`,
  bell:        `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>`,
  settings:    `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>`,
  logout:      `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
  moon:        `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>`,
  sun:         `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`,
};

// ===== NAV ITEMS =====
const _UNREAD_COUNT = <?php echo (int)$unread_notif_count; ?>;
const _PENDING_ASG  = <?php echo (int)$stat_pending_asg; ?>;
const _UPCOMING_QZ  = <?php echo (int)$stat_upcoming_quizzes; ?>;
const navItems = [
  { id:'dashboard',    label:'Dashboard',    icon:IC.home,        badge:<?php echo count($js_admin_announcements); ?> },
  { id:'courses',      label:'My Courses',   icon:IC.courses,     badge:0 },
  { id:'assignments',  label:'Assignments',  icon:IC.assignments, badge:_PENDING_ASG },
  { id:'quizzes',      label:'Quizzes',      icon:IC.quizzes,     badge:_UPCOMING_QZ },
  { id:'discussions',  label:'Discussions',  icon:IC.discussions, badge:0 },
  { id:'notifications',label:'Notifications',icon:IC.bell,        badge:_UNREAD_COUNT },
  { id:'settings',     label:'Settings',     icon:IC.settings,    badge:0 },
];

function renderNav() {
  const menu = document.getElementById('navMenu');
  if (!menu) return;
  menu.innerHTML = navItems.map(n => `
    <div class="nav-item${n.id === currentPage ? ' active' : ''}" onclick="navigate('${n.id}')" data-nav="${n.id}">
      <span class="nav-icon">${n.icon}</span>
      <span class="nav-label">${n.label}</span>
      ${n.badge > 0 ? `<span class="nav-badge">${n.badge}</span>` : ''}
    </div>`).join('');
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

document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('lf-theme');
  darkMode = saved === 'dark';
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('darkBtn').innerHTML = darkMode ? IC.sun : IC.moon;
  renderNav();
  navigate('dashboard');
  if (adminAnnouncements.length > 0) startAnnUnreadPolling();
  // Sync notification dot with actual unread count
  _syncNotifUI();
});

// ===== NAVIGATION =====
function navigate(id) {
  currentPage = id;
  renderNav();
  document.getElementById('topbarTitle').textContent = pageTitles[id] || id;
  const area = document.getElementById('contentArea');
  area.innerHTML = '';
  const renders = {
    dashboard: renderDashboard, courses: renderCourses,
    assignments: renderAssignments, quizzes: renderQuizzes,
    discussions: renderDiscussions,
    notifications: renderNotifications, settings: renderSettings,
  };
  area.innerHTML = (renders[id] || (() => `<div style="text-align:center;padding:60px;color:var(--text-2)"><div style="font-size:48px;margin-bottom:12px">🚧</div><div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700">Coming Soon</div></div>`))();
  area.classList.remove('fade-in');
  void area.offsetWidth;
  area.classList.add('fade-in');
  closeNotifs();
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('open');
  // After render hooks
  if (id === 'assignments') setTimeout(filterAssignments, 0);
  if (id === 'quizzes') {
    setTimeout(filterQzStudentPanel, 0);
    refreshQuizzesFromDB(); // always fetch fresh data from DB when tab opens
  }
  if (id === 'discussions') setTimeout(() => { if (_discThreads.length > 0) _discActiveId = _discThreads[0].id; }, 0);
}

// ===== SIDEBAR =====
function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const mc = document.getElementById('mainContent');
  if (window.innerWidth <= 768) {
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
  function _adjustHSL(hsl, sOverride, lOverride) {
    const [h, s, l] = _hslParts(hsl);
    const finalS = sOverride !== null ? sOverride : s;
    const finalL = lOverride !== null ? lOverride : l;
    return `${h} ${finalS}% ${finalL}%`;
  }
  function _darkVariants(t) {
    // If the theme itself is natively dark, use its database variables directly
    if (t.is_dark) {
      return {
        p: t.p,
        d: t.d,
        l: t.l,
        bg: t.bg,
        surface: t.surface,
        surface2: t.surface2 || _shiftL(t.bg, +4),
        surface3: t.surface3 || _shiftL(t.bg, +8),
        border: t.border,
        borderSt: t.borderSt || _shiftL(t.border, +8),
        text: t.text,
        text2: t.text2,
        text3: t.text3 || _shiftL(t.text2, -15),
        acc: t.acc,
      };
    }
    // If the active theme is a light theme, dynamically generate a premium neutral slate/zinc dark mode using its hue!
    const hue = _hslParts(t.p)[0];
    return {
      p: _adjustHSL(t.p, 80, 65),
      d: _adjustHSL(t.p, 80, 53),
      l: _adjustHSL(t.p, 30, 20),
      bg: `${hue} 12% 8%`,
      surface: `${hue} 12% 12%`,
      surface2: `${hue} 12% 16%`,
      surface3: `${hue} 12% 20%`,
      border: `${hue} 12% 18%`,
      borderSt: `${hue} 12% 25%`,
      text: `${hue} 8% 92%`,
      text2: `${hue} 8% 70%`,
      text3: `${hue} 8% 45%`,
      acc: t.acc,
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
  --surface-2:    hsl(${surf} / 0.72);
  --border:       hsl(${t.border});
  --border-strong:hsl(${_shiftL(t.border, -8)});
  --text:         hsl(${t.text});
  --text-2:       hsl(${t.text2});
  --text-3:       hsl(${t.text2} / 0.6);
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

// ===== DARK MODE =====
function toggleDark() {
  darkMode = !darkMode;
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('darkBtn').innerHTML = darkMode ? IC.sun : IC.moon;
  localStorage.setItem('lf-theme', darkMode ? 'dark' : 'light');
}

// ===== NOTIFICATIONS =====
function toggleNotifs() {
  notifOpen = !notifOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifOpen);
  if (notifOpen) renderTopNotifList();
}
function closeNotifs() {
  notifOpen = false;
  document.getElementById('notifPanel').classList.remove('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.icon-btn') && !e.target.closest('.notif-panel')) closeNotifs();
});

// ===== MODAL =====
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ===== QUIZ INSTRUCTIONS =====
const _quizInstructions = {
  'Web Technologies Quiz 3': {
    title: 'Web Technologies Quiz 3 — Instructions',
    body: `<ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:8px">
      <li><strong>Course:</strong> CS 322 — Web Technologies</li>
      <li><strong>Duration:</strong> 10 minutes</li>
      <li><strong>Questions:</strong> 5 multiple-choice questions</li>
      <li><strong>Coverage:</strong> HTML5, CSS3, JavaScript Basics</li>
      <li><strong>Passing Score:</strong> 60%</li>
      <li>Once started, the timer cannot be paused. Make sure you have a stable connection before beginning.</li>
      <li>Each question has only one correct answer. Unanswered questions will be marked as incorrect.</li>
    </ul>`
  },
  'Data Structures Midterm': {
    title: 'Data Structures Midterm — Instructions',
    body: `<ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:8px">
      <li><strong>Course:</strong> CS 312 — Data Structures</li>
      <li><strong>Duration:</strong> 60 minutes</li>
      <li><strong>Questions:</strong> 25 multiple-choice questions</li>
      <li><strong>Coverage:</strong> Arrays, Linked Lists, Stacks, Queues, Trees</li>
      <li><strong>Passing Score:</strong> 50%</li>
      <li>Once started, the timer cannot be paused. Do not refresh the page during the exam.</li>
      <li>Open notes are <strong>not</strong> allowed. Academic integrity is strictly enforced.</li>
    </ul>`
  },
};

function openQuizInstructions(quizTitle) {
  const info = _quizInstructions[quizTitle] || {
    title: quizTitle + ' — Instructions',
    body: '<p>Please read the quiz carefully before starting. Follow all instructions given by your instructor. Once the timer begins, it cannot be stopped.</p>'
  };
  document.getElementById('instrModalTitle').textContent = info.title;
  document.getElementById('instrModalBody').innerHTML = info.body;
  openModal('instrModal');
}


// ===== TOAST =====
function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  const icons = { success:'🌸', error:'❌', warning:'⚠️' };
  t.textContent = (icons[type] || 'ℹ️') + ' ' + msg;
  t.className = 'toast' + (type ? ' ' + type : '') + ' show';
  setTimeout(() => t.classList.remove('show'), 3200);
}

function doLogout() {
  showToast('You have been signed out.');
  setTimeout(() => { window.location.reload(); }, 1500);
}

// ===== QUIZ ENGINE (loads real questions from DB) =====
let quizCurrentId        = null;
let quizCurrentQuestions = [];
let quizStartTimestamp   = null;
let quizTimeLimitMin     = 30;

function openQuiz(quizId, title, timeLimitMin) {
  quizCurrentId        = quizId;
  quizTimeLimitMin     = timeLimitMin || 30;
  quizCurrent          = 0;
  quizAnswers          = {};
  quizStartTimestamp   = Date.now();
  quizCurrentQuestions = [];

  document.getElementById('quizModalTitle').textContent = title || 'Quiz';
  document.getElementById('qTotal').textContent = '…';
  document.getElementById('prevBtn').style.display = '';
  document.getElementById('nextBtn').style.display = '';
  document.getElementById('nextBtn').onclick = () => quizNav(1);
  document.getElementById('quizBody').innerHTML = `
    <div style="text-align:center;padding:32px;color:var(--text-3)">
      <div style="font-size:32px;margin-bottom:10px">⏳</div>
      <div style="font-size:13px">Loading questions…</div>
    </div>`;
  openModal('quizModal');
  clearInterval(quizTimerInt);

  fetch(`${window.location.pathname}?action=get_quiz_questions&quiz_id=${quizId}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        document.getElementById('quizBody').innerHTML = `
          <div style="text-align:center;padding:32px;color:var(--danger)">
            <div style="font-size:32px;margin-bottom:10px">❌</div>
            <div style="font-size:13px;font-weight:600">${data.message || 'Could not load quiz'}</div>
          </div>`;
        document.getElementById('nextBtn').style.display = 'none';
        document.getElementById('prevBtn').style.display = 'none';
        return;
      }
      quizCurrentQuestions = data.questions || [];
      quizTimeLimitMin     = data.time_limit_min || quizTimeLimitMin;
      quizTimerSecs        = quizTimeLimitMin * 60;
      document.getElementById('qTotal').textContent = quizCurrentQuestions.length;
      renderQuizQ();
      startQuizTimer();
    })
    .catch(() => {
      document.getElementById('quizBody').innerHTML = `
        <div style="text-align:center;padding:32px;color:var(--danger)">
          <div style="font-size:32px;margin-bottom:10px">⚠️</div>
          <div style="font-size:13px;font-weight:600">Network error — please try again</div>
        </div>`;
    });
}

function renderQuizQ() {
  const q = quizCurrentQuestions[quizCurrent];
  if (!q) return;
  document.getElementById('qNum').textContent  = quizCurrent + 1;
  document.getElementById('prevBtn').disabled  = quizCurrent === 0;
  document.getElementById('nextBtn').textContent = quizCurrent === quizCurrentQuestions.length - 1 ? '✓ Submit' : 'Next →';
  const pct = Math.round(((quizCurrent + 1) / quizCurrentQuestions.length) * 100);
  document.getElementById('quizProgress').style.width = pct + '%';

  const labels = ['A','B','C','D','E','F'];
  let optionsHtml = '';
  if (q.type === 'mc') {
    optionsHtml = (q.choices || []).map((opt, i) => {
      const sel = quizAnswers[quizCurrent] === i;
      return `<div class="quiz-option${sel ? ' selected' : ''}" onclick="selectAnswer(${i})">
        <div class="option-circle">${labels[i] || i+1}</div>
        <span>${opt}</span>
      </div>`;
    }).join('');
  } else if (q.type === 'tf') {
    optionsHtml = ['True','False'].map((opt, i) => {
      const val = opt.toLowerCase();
      const sel = quizAnswers[quizCurrent] === val;
      return `<div class="quiz-option${sel ? ' selected' : ''}" onclick="selectTFAnswer('${val}')">
        <div class="option-circle">${i === 0 ? '✅' : '❌'}</div>
        <span>${opt}</span>
      </div>`;
    }).join('');
  } else {
    const cur = (quizAnswers[quizCurrent] || '').toString().replace(/"/g,'&quot;');
    optionsHtml = `<input type="text" value="${cur}" placeholder="Type your answer here…"
      oninput="quizAnswers[quizCurrent]=this.value"
      style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;margin-top:8px">`;
  }

  document.getElementById('quizBody').innerHTML = `
    <div style="margin-bottom:18px">
      <div style="font-size:14px;font-weight:600;line-height:1.5;margin-bottom:14px">
        <span style="color:var(--text-3);font-size:12px;display:block;margin-bottom:4px">QUESTION ${quizCurrent+1}</span>
        ${q.text}
      </div>
      ${optionsHtml}
    </div>`;
}

function selectAnswer(i) {
  quizAnswers[quizCurrent] = i;
  renderQuizQ();
}
function selectTFAnswer(val) {
  quizAnswers[quizCurrent] = val;
  renderQuizQ();
}
function quizNav(dir) {
  if (dir === 1 && quizCurrent === quizCurrentQuestions.length - 1) { submitQuiz(); return; }
  quizCurrent = Math.max(0, Math.min(quizCurrentQuestions.length - 1, quizCurrent + dir));
  renderQuizQ();
}

function submitQuiz() {
  clearInterval(quizTimerInt);
  const timeTakenSec = quizStartTimestamp ? Math.round((Date.now() - quizStartTimestamp) / 1000) : 0;
  const answersMap   = {};
  quizCurrentQuestions.forEach((q, i) => {
    if (quizAnswers[i] !== undefined) answersMap[q.id] = quizAnswers[i];
  });

  document.getElementById('quizBody').innerHTML = `
    <div style="text-align:center;padding:24px;color:var(--text-3)">
      <div style="font-size:32px;margin-bottom:10px">⏳</div>
      <div style="font-size:13px">Submitting your answers…</div>
    </div>`;
  document.getElementById('nextBtn').disabled = true;
  document.getElementById('prevBtn').disabled = true;

  const fd = new FormData();
  fd.append('action',        'submit_quiz');
  fd.append('quiz_id',       quizCurrentId);
  fd.append('answers',       JSON.stringify(answersMap));
  fd.append('time_taken_sec', timeTakenSec);

  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      document.getElementById('nextBtn').disabled = false;
      document.getElementById('prevBtn').disabled = false;
      if (data.success) {
        const pct = data.pct ?? 0;
        document.getElementById('quizBody').innerHTML = `
          <div style="text-align:center;padding:20px">
            <div style="font-size:56px;margin-bottom:14px">${pct >= 80 ? '🎉' : pct >= 60 ? '😊' : '😔'}</div>
            <div style="font-family:'Syne',sans-serif;font-size:40px;font-weight:800;color:${pct >= 80 ? 'var(--primary)' : pct >= 60 ? 'var(--accent)' : 'var(--danger)'}">${pct}%</div>
            <div style="font-size:16px;font-weight:600;margin:10px 0">You scored ${data.score} out of ${data.max_score}</div>
            <div style="font-size:13px;color:var(--text-2)">${data.correct} of ${data.total} questions correct</div>
            <div style="font-size:13px;color:var(--text-2);margin-top:6px">${pct >= 80 ? 'Excellent work! Keep it up!' : pct >= 60 ? 'Good effort! Review the missed items.' : 'Keep studying! You can do it!'}</div>
          </div>`;
        const entry = _quizData.find(q => q.id === quizCurrentId);
        if (entry) {
          entry.status = 'completed';
          entry.score  = pct;
          entry.result = pct >= 90 ? 'Perfect' : pct >= 75 ? 'Passed' : 'Average';
        }
        document.getElementById('prevBtn').style.display = 'none';
        document.getElementById('nextBtn').textContent = '✕ Close';
        document.getElementById('nextBtn').onclick = () => {
          closeModal('quizModal');
          refreshQuizzesFromDB();
        };
        document.getElementById('quizProgress').style.width = '100%';
      } else {
        document.getElementById('quizBody').innerHTML = `
          <div style="text-align:center;padding:32px;color:var(--danger)">
            <div style="font-size:32px;margin-bottom:10px">❌</div>
            <div style="font-size:13px;font-weight:600">${data.message || 'Submission failed — please try again'}</div>
          </div>`;
      }
    })
    .catch(() => {
      document.getElementById('nextBtn').disabled = false;
      document.getElementById('prevBtn').disabled = false;
      document.getElementById('quizBody').innerHTML = `
        <div style="text-align:center;padding:32px;color:var(--danger)">
          <div style="font-size:32px;margin-bottom:10px">⚠️</div>
          <div style="font-size:13px;font-weight:600">Network error — could not submit. Please try again.</div>
        </div>`;
    });
}

function startQuizTimer() {
  clearInterval(quizTimerInt);
  quizTimerInt = setInterval(() => {
    quizTimerSecs--;
    const m = Math.floor(quizTimerSecs / 60), s = quizTimerSecs % 60;
    const el = document.getElementById('quizTimer');
    if (el) el.textContent = `${m}:${s.toString().padStart(2, '0')}`;
    if (quizTimerSecs <= 0) { clearInterval(quizTimerInt); submitQuiz(); }
  }, 1000);
}


// ===== HELPER =====
function getGreeting() {
  const h = new Date().getHours();
  return h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening';
}

// ============================================================
// ===== PAGE RENDERERS =====
// ============================================================

// ===== DASHBOARD =====
function renderDashboard() {
  const now = new Date();
  const timeStr = now.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>Dashboard</span></div>
    <h1>Good ${getGreeting()}, <?php echo htmlspecialchars(explode(' ', $student_name)[0]); ?>! 👋</h1>
    <p>Here's your learning overview for today</p>
  </div>

  <!-- Clickable summary cards -->
  <div class="stats-grid">
    <div class="stat-card" onclick="navigate('courses')" title="Go to My Courses">
      <div class="stat-icon pink">${IC.courses}</div>
      <div>
        <div class="stat-val"><?php echo $stat_courses; ?></div>
        <div class="stat-label">Enrolled Courses</div>
        <div class="stat-trend up">This semester</div>
      </div>
    </div>
    <div class="stat-card" onclick="navigate('assignments')" title="Go to Assignments">
      <div class="stat-icon amber">${IC.assignments}</div>
      <div>
        <div class="stat-val"><?php echo $stat_pending_asg; ?></div>
        <div class="stat-label">Pending Assignments</div>
        <div class="stat-trend down">Due this week</div>
      </div>
    </div>
    <div class="stat-card" onclick="navigate('quizzes')" title="Go to Quizzes">
      <div class="stat-icon blue">${IC.quizzes}</div>
      <div>
        <div class="stat-val"><?php echo $stat_upcoming_quizzes; ?></div>
        <div class="stat-label">Upcoming Quizzes</div>
        <div class="stat-trend up">Scheduled</div>
      </div>
    </div>
    <div class="stat-card" onclick="navigate('quizzes')" title="View Quizzes">
      <div class="stat-icon pink">${IC.analytics}</div>
      <div>
        <div class="stat-val"><?php echo $stat_avg_grade > 0 ? $stat_avg_grade . '%' : '—'; ?></div>
        <div class="stat-label">Quiz Average</div>
        <div class="stat-trend up">Based on completed quizzes</div>
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
    <!-- Recent Activity -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="font-size:15px;font-weight:700">Recent Activity</h3>
        <span class="badge badge-pink">Live</span>
      </div>
      <div class="activity-item">
        <div class="act-icon" style="background:rgba(204,58,114,0.14)">📝</div>
        <div class="act-body">
          <div class="act-text"><strong>Assignment Submitted</strong> — OOP Lab 3</div>
          <div class="act-time">5 minutes ago</div>
        </div>
      </div>
      <div class="activity-item">
        <div class="act-icon" style="background:rgba(74,174,232,0.14)">✅</div>
        <div class="act-body">
          <div class="act-text"><strong>Quiz Completed</strong> — Web Dev Quiz 2 · 92%</div>
          <div class="act-time">2 hours ago</div>
        </div>
      </div>
      <div class="activity-item">
        <div class="act-icon" style="background:rgba(224,144,16,0.14)">📚</div>
        <div class="act-body">
          <div class="act-text"><strong>Course Material</strong> — Viewed Chapter 7 · Database Management</div>
          <div class="act-time">Yesterday</div>
        </div>
      </div>
      <div class="activity-item">
        <div class="act-icon" style="background:rgba(204,58,114,0.14)">💬</div>
        <div class="act-body">
          <div class="act-text"><strong>Discussion Post</strong> — Replied in Design Patterns thread</div>
          <div class="act-time">2 days ago</div>
        </div>
      </div>
    </div>

    <!-- Audit Trail -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="font-size:15px;font-weight:700">🔍 Audit Trail</h3>
      </div>
      <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:12px;margin-bottom:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div>
            <div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">User</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px"><?php echo htmlspecialchars($student_name); ?></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">Role</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px">Student</div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">Login Time</div>
            <div style="font-size:13px;font-weight:600;margin-top:2px">${timeStr}</div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">Status</div>
            <div class="badge badge-green" style="margin-top:5px">● Active</div>
          </div>
        </div>
      </div>
      <div class="audit-row">
        <div class="audit-dot" style="background:var(--secondary)"></div>
        <div style="flex:1;font-size:12px">Login — ${timeStr}</div>
        <span class="badge badge-green">Success</span>
      </div>
      <div class="audit-row">
        <div class="audit-dot" style="background:var(--primary)"></div>
        <div style="flex:1;font-size:12px">Dashboard viewed</div>
        <span class="badge badge-blue">View</span>
      </div>
      <div class="audit-row">
        <div class="audit-dot" style="background:var(--accent)"></div>
        <div style="flex:1;font-size:12px">Assignment submitted — OOP Lab 3</div>
        <span class="badge badge-amber">Submit</span>
      </div>
      <div class="audit-row">
        <div class="audit-dot" style="background:var(--secondary)"></div>
        <div style="flex:1;font-size:12px">Previous login — Apr 21, 2026 09:14 AM</div>
        <span class="badge badge-gray">History</span>
      </div>
    </div>
  </div>

  <!-- Upcoming deadlines quick-view -->
  <div class="card" style="margin-top:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h3 style="font-size:15px;font-weight:700">⏰ Upcoming Deadlines</h3>
      <button class="btn btn-ghost btn-sm" onclick="navigate('assignments')">View all →</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      ${[
        ['Apr 24','Final Project Proposal','Web Technologies (CS 322)','badge-red','Tomorrow'],
        ['Apr 25','Lab Activity 4: Inheritance','OOP (CS 311)','badge-amber','2 days'],
        ['Apr 25','Web Dev Quiz 3','CS 322 · Quiz','badge-blue','2 days'],
        ['Apr 26','Research Paper: Binary Trees','Data Structures (CS 312)','badge-amber','3 days'],
      ].map(([date, title, course, badge, rel]) => `
        <div style="display:flex;align-items:center;gap:14px;padding:10px;background:var(--surface-2);border-radius:var(--radius-sm)">
          <div style="text-align:center;min-width:44px">
            <div style="font-size:10px;color:var(--text-3);font-weight:700;text-transform:uppercase">${date.split(' ')[0]}</div>
            <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;line-height:1;color:var(--primary)">${date.split(' ')[1]}</div>
          </div>
          <div style="flex:1">
            <div style="font-weight:600;font-size:13px">${title}</div>
            <div style="font-size:11px;color:var(--text-2);margin-top:2px">${course}</div>
          </div>
          <span class="badge ${badge}">${rel}</span>
        </div>`).join('')}
    </div>
  </div>`;
}

// ===== STUDENT COURSE & ANNOUNCEMENT DATA (from DB) =====
const studentCourses = <?php echo $js_student_courses_json; ?>;
const studentCourseAnnouncements = <?php echo $js_student_announcements_json; ?>;
const adminAnnouncements = <?php echo $js_admin_announcements_json; ?>;

function renderAdminAnnouncementCard(a) {
  const priorityColor = a.priority === 'urgent' ? 'var(--danger)' : a.priority === 'important' ? 'var(--accent)' : 'var(--secondary)';
  const priorityLabel = a.priority === 'urgent' ? '🚨 Urgent' : a.priority === 'important' ? '⚠️ Important' : '📢 Announcement';
  const dateStr = a.date ? new Date(a.date).toLocaleDateString('en-PH', {month:'short',day:'numeric',year:'numeric'}) : '';
  return `<div id="ann-card-${a.id}" style="padding:14px 16px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);margin-bottom:10px;transition:opacity .25s,max-height .3s;overflow:hidden;${a.pinned?'border-left:3px solid var(--primary);':''}">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-size:11px;font-weight:700;color:${priorityColor}">${priorityLabel}</span>
        <span style="font-size:11px;color:var(--text-3)">${dateStr}</span>
      </div>
      <button onclick="dismissAnnouncement(${a.id})" title="Dismiss" style="flex-shrink:0;background:none;border:none;cursor:pointer;font-size:15px;color:var(--text-3);padding:0 2px;line-height:1;transition:color .15s" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
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

// ===== MY COURSES =====
function renderCourses() {
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>My Courses</span></div>
    <h1>My Courses</h1>
    <p>${studentCourses.length} enrolled courses this semester</p>
  </div>

  <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
    <div class="search-wrap" style="max-width:280px"><input type="text" placeholder="Search courses..." oninput="filterCourses(this.value)"></div>
    <select id="statusFilter" onchange="filterCourses()" style="min-width:140px">
      <option value="">All Status</option>
      <option value="in-progress">In Progress</option>
      <option value="completed">Completed</option>
    </select>
    <div style="margin-left:auto;font-size:12px;color:var(--text-2)">
      <span id="courseCount">${studentCourses.length} courses</span>
    </div>
  </div>

  <div class="course-grid" id="courseGrid">
    ${studentCourses.map(c => `
    <div class="course-card">
      <div class="course-thumb ${c.bg}">
        ${c.emoji}
        <div class="course-badge">${c.code}</div>
      </div>
      <div class="course-body">
        <div class="course-title">${c.title}</div>
        <div class="course-meta">
          <span>👤 ${c.instructor}</span>
          <span>📖 ${c.units}</span>
        </div>
        <div>
          <div class="progress-label"><span>Progress</span><span>${c.prog}%</span></div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:${c.prog}%"></div>
          </div>
        </div>
      </div>
      <div class="course-footer">
        <span class="badge ${c.prog === 100 ? 'badge-green' : 'badge-pink'}">● ${c.prog === 100 ? 'Completed' : 'In Progress'}</span>
        <button class="btn btn-primary btn-sm" onclick="continueCourse(${c.id})">Continue →</button>
      </div>
    </div>`).join('')}
  </div>`;
}

function continueCourse(courseId) {
  const course = studentCourses.find(c=>c.id===courseId);
  if (!course) return;
  currentPage = 'courses';
  renderNav();
  document.getElementById('topbarTitle').textContent = course.title;
  const area = document.getElementById('contentArea');
  area.innerHTML = renderStudentCourseView(courseId);
  area.classList.remove('fade-in');
  void area.offsetWidth;
  area.classList.add('fade-in');
  closeSidebar();
}

// ===== COURSE VIEW TAB STATE =====
let _courseViewTab = 'announcements';

function switchCourseTab(tab, courseId) {
  _courseViewTab = tab;
  document.querySelectorAll('.course-view-tab').forEach(b => b.classList.remove('active'));
  const activeBtn = document.getElementById('cvTab-' + tab);
  if (activeBtn) activeBtn.classList.add('active');
  const body = document.getElementById('courseViewTabBody');
  if (!body) return;
  if (tab === 'announcements') body.innerHTML = _renderCourseAnnouncements(courseId);
  else if (tab === 'assignments') body.innerHTML = _renderCourseAssignments(courseId);
  else if (tab === 'quizzes')    body.innerHTML = _renderCourseQuizzes(courseId);
  else if (tab === 'modules')    _loadCourseModules(courseId, body);
  else if (tab === 'discussion') body.innerHTML = _renderCourseDiscussion(courseId);
}

function _renderCourseAnnouncements(courseId) {
  const anns = studentCourseAnnouncements[courseId] || [];
  const fmtDate = d => {
    const ms = typeof d === 'string' ? Date.parse(d.replace(' ','T')) : (d instanceof Date ? d.getTime() : Number(d));
    const diff = Date.now() - ms;
    const days = Math.floor(diff/86400000);
    const hrs  = Math.floor((diff%86400000)/3600000);
    if (days > 0) return days === 1 ? 'Yesterday' : `${days} days ago`;
    if (hrs  > 0) return `${hrs}h ago`;
    return 'Just now';
  };
  const allSorted = [...anns.filter(a=>a.pinned), ...anns.filter(a=>!a.pinned)];

  if (!allSorted.length) return `
    <div style="text-align:center;padding:48px 20px;color:var(--text-3)">
      <div style="font-size:44px;margin-bottom:12px;opacity:.5">📭</div>
      <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No announcements yet</div>
      <div style="font-size:12px">Your instructor hasn't posted anything yet. Check back later.</div>
    </div>`;

  return allSorted.map(a => `
    <div style="border:1.5px solid ${a.pinned?'var(--primary)':'var(--border)'};border-radius:var(--radius);padding:18px;margin-bottom:14px;background:${a.pinned?'var(--primary-light)':'var(--surface-2)'}">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
        ${a.pinned?'<span style="font-size:11px;font-weight:700;background:var(--primary);color:#fff;padding:2px 8px;border-radius:20px">📌 Pinned</span>':''}
        <div style="font-family:\'Syne\',sans-serif;font-size:15px;font-weight:700;color:var(--text)">${a.title}</div>
      </div>
      <div style="font-size:13px;color:var(--text-2);line-height:1.65;margin-bottom:12px;white-space:pre-line">${a.body}</div>
      <div style="display:flex;align-items:center;gap:14px;font-size:11px;color:var(--text-3);border-top:1px solid var(--border);padding-top:10px;margin-top:4px">
        <span>👤 ${a.author}</span>
        <span>🕐 ${fmtDate(a.date)}</span>
      </div>
    </div>`).join('');
}

function _renderCourseAssignments(courseId) {
  // courseId is the section_id — match strictly as integers
  const sid = parseInt(courseId, 10);
  const asgs = _asgData.filter(a => parseInt(a.section_id, 10) === sid);

  const statusCfg = {
    pending:   { badge: 'badge-amber', label: 'Pending' },
    submitted: { badge: 'badge-blue',  label: 'Submitted' },
    graded:    { badge: 'badge-green', label: 'Graded' },
  };

  if (!asgs.length) return `
    <div style="text-align:center;padding:48px 20px;color:var(--text-3)">
      <div style="font-size:44px;margin-bottom:12px;opacity:.5">📝</div>
      <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No assignments yet</div>
      <div style="font-size:12px">Your instructor hasn't posted any assignments for this course yet.</div>
    </div>`;

  return asgs.map(a => {
    const cfg = statusCfg[a.status] || statusCfg.pending;
    return `
    <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);padding:16px 18px;display:flex;align-items:center;gap:14px;margin-bottom:10px;transition:.2s;box-shadow:var(--shadow)"
         onmouseenter="this.style.borderColor='var(--primary)';this.style.boxShadow='var(--shadow-md)'"
         onmouseleave="this.style.borderColor='var(--border)';this.style.boxShadow='var(--shadow)'">
      <div style="width:42px;height:42px;border-radius:11px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">📝</div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:14px">${a.title}</div>
        <div style="font-size:12px;color:var(--text-2);margin-top:3px">
          📅 Due: ${a.due} &nbsp;·&nbsp; 🏆 ${a.points} pts
          ${a.grade ? ` &nbsp;·&nbsp; <span style="color:var(--primary);font-weight:700">Score: ${a.grade}</span>` : ''}
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0">
        <span class="badge ${cfg.badge}">${cfg.label}</span>
        ${a.status === 'pending' ? `<button class="btn btn-primary btn-sm" onclick="openSubmitModal(${a.id})">📤 Submit</button>` : ''}
      </div>
    </div>`;
  }).join('');
}

function _renderCourseQuizzes(courseId) {
  const sid = parseInt(courseId, 10);
  const quizzes = _quizData.filter(q => parseInt(q.section_id, 10) === sid);

  if (!quizzes.length) return `
    <div style="text-align:center;padding:48px 20px;color:var(--text-3)">
      <div style="font-size:44px;margin-bottom:12px;opacity:.5">🧪</div>
      <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No quizzes yet</div>
      <div style="font-size:12px">Your instructor hasn't posted any quizzes for this course yet.</div>
    </div>`;

  const upcoming  = quizzes.filter(q => q.status === 'upcoming');
  const completed = quizzes.filter(q => q.status === 'completed');
  let html = '';

  if (upcoming.length) {
    html += upcoming.map(q => {
      const daysColor = (q.daysLeft || 7) <= 3 ? 'var(--danger)' : 'var(--accent)';
      return `
      <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);padding:16px 18px;margin-bottom:10px;transition:.2s;box-shadow:var(--shadow)"
           onmouseenter="this.style.borderColor='var(--primary)';this.style.boxShadow='var(--shadow-md)'"
           onmouseleave="this.style.borderColor='var(--border)';this.style.boxShadow='var(--shadow)'">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
          <div style="flex:1">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <span style="font-size:20px">🧪</span>
              <div style="font-weight:700;font-size:14px">${q.title}</div>
            </div>
            <div style="font-size:12px;color:var(--text-2)">
              ${q.questions ? q.questions + ' questions &nbsp;·&nbsp; ' : ''}${q.duration ? q.duration + ' min &nbsp;·&nbsp; ' : ''}Posted by ${q.instructor || 'Instructor'}
            </div>
            ${q.topics ? `<div style="margin-top:8px;padding:7px 11px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:12px;color:var(--text-2)">📌 Topics: ${q.topics}</div>` : ''}
          </div>
          <div style="text-align:right;flex-shrink:0">
            <span class="badge badge-amber" style="display:block;margin-bottom:6px">Upcoming</span>
            <div style="font-size:11px;color:var(--text-3)">Due: ${q.due || 'TBD'}</div>
            ${q.daysLeft != null ? `<div style="font-size:11px;font-weight:600;margin-top:2px;color:${daysColor}">⏰ ${q.daysLeft} days left</div>` : ''}
          </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:14px">
          <button class="btn btn-primary btn-sm" onclick="openQuiz(${q.id}, '${q.title.replace(/'/g,"\\'")}', ${q.duration || 30})">▶ Start Quiz</button>
          <button class="btn btn-outline btn-sm"  onclick="openQuizInstructions('${q.title.replace(/'/g,"\\'")}')">📋 Instructions</button>
        </div>
      </div>`;
    }).join('');
  }

  if (completed.length) {
    html += `
    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin:16px 0 8px">Past Results</div>
    <div style="background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:var(--surface-2);font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px">
            <th style="padding:10px 14px;text-align:left;font-weight:700">Quiz</th>
            <th style="padding:10px 14px;text-align:center;font-weight:700">Score</th>
            <th style="padding:10px 14px;text-align:center;font-weight:700">Date</th>
            <th style="padding:10px 14px;text-align:center;font-weight:700">Result</th>
          </tr>
        </thead>
        <tbody>
          ${completed.map(q => {
            const rb = q.result==='Perfect'?'badge-pink':q.result==='Average'?'badge-amber':'badge-green';
            const icon = q.result==='Perfect'?' 🌸':q.result==='Passed'?' ✓':'';
            return `<tr style="border-top:1px solid var(--border)">
              <td style="padding:11px 14px;font-weight:600;font-size:13px">${q.title}</td>
              <td style="padding:11px 14px;text-align:center;font-weight:700;color:var(--primary)">${q.score}/100</td>
              <td style="padding:11px 14px;text-align:center;font-size:12px;color:var(--text-2)">${q.date}</td>
              <td style="padding:11px 14px;text-align:center"><span class="badge ${rb}">${q.result}${icon}</span></td>
            </tr>`;
          }).join('')}
        </tbody>
      </table>
    </div>`;
  }

  return html;
}

function renderStudentCourseView(courseId) {
  const course = studentCourses.find(c => c.id === courseId);
  _courseViewTab = 'announcements';

  const anns   = studentCourseAnnouncements[courseId] || [];
  const sid    = parseInt(courseId, 10);
  const asgs   = _asgData.filter(a  => parseInt(a.section_id,  10) === sid);
  const quizz  = _quizData.filter(q => parseInt(q.section_id,  10) === sid);

  const pendingAsgs     = asgs.filter(a  => a.status === 'pending').length;
  const upcomingQuizzes = quizz.filter(q => q.status === 'upcoming').length;

  return `
    <!-- Back + header -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px">
      <button class="btn btn-ghost btn-sm" onclick="navigate('courses')" style="padding:8px 12px">← My Courses</button>
      <div style="flex:1">
        <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800">${course.emoji} ${course.title} <span style="font-size:13px;color:var(--text-3);font-weight:500">${course.code}</span></div>
        <div style="font-size:12px;color:var(--text-2);margin-top:2px">👤 ${course.instructor} &nbsp;·&nbsp; 📖 ${course.units} &nbsp;·&nbsp; 👥 ${course.students} students</div>
      </div>
      <span class="badge ${course.prog===100?'badge-green':'badge-pink'}" style="font-size:12px;padding:5px 12px">● ${course.prog===100?'Completed':'In Progress'}</span>
    </div>

    <!-- Progress bar -->
    <div class="card" style="margin-bottom:20px;padding:16px 20px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;font-weight:600">Course Progress</span>
        <span style="font-size:14px;font-weight:800;color:var(--primary)">${course.prog}%</span>
      </div>
      <div class="progress-bar" style="height:8px;border-radius:6px">
        <div class="progress-fill" style="width:${course.prog}%"></div>
      </div>
    </div>

    <!-- Tab bar -->
    <div class="tabs-row" style="margin-bottom:16px">
      <button id="cvTab-announcements" class="tab-pill course-view-tab active" onclick="switchCourseTab('announcements',${courseId})">
        📢 Announcements
        <span style="background:var(--primary-light);color:var(--primary);font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">${anns.length}</span>
      </button>
      <button id="cvTab-assignments" class="tab-pill course-view-tab" onclick="switchCourseTab('assignments',${courseId})">
        📝 Assignments
        ${pendingAsgs > 0
          ? `<span style="background:#fff3e0;color:var(--accent);font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">${pendingAsgs} pending</span>`
          : `<span style="background:var(--surface-2);color:var(--text-3);font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">${asgs.length}</span>`}
      </button>
      <button id="cvTab-quizzes" class="tab-pill course-view-tab" onclick="switchCourseTab('quizzes',${courseId})">
        🧪 Quizzes
        ${upcomingQuizzes > 0
          ? `<span style="background:var(--secondary-light);color:var(--secondary);font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">${upcomingQuizzes} upcoming</span>`
          : `<span style="background:var(--surface-2);color:var(--text-3);font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">${quizz.length}</span>`}
      </button>
      <button id="cvTab-modules" class="tab-pill course-view-tab" onclick="switchCourseTab('modules',${courseId})">
        📦 Modules
      </button>
      <button id="cvTab-discussion" class="tab-pill course-view-tab" onclick="switchCourseTab('discussion',${courseId})">
        💬 Discussion
        ${(() => { const sid = parseInt(courseId,10); const cnt = _discThreads.filter(t => { const c = studentCourses.find(x=>x.id===sid); return c && t.course===c.code; }).length; return cnt > 0 ? `<span style="background:var(--surface-2);color:var(--text-3);font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">${cnt}</span>` : ''; })()}
      </button>
    </div>

    <!-- Tab body -->
    <div class="card" id="courseViewTabBody">
      ${_renderCourseAnnouncements(courseId)}
    </div>`;
}

function _loadCourseModules(courseId, bodyEl) {
  bodyEl.innerHTML = `<div style="text-align:center;padding:28px 16px;color:var(--text-3);font-size:13px">Loading modules…</div>`;

  const fileIcon = url => {
    const ext = (url || '').split('.').pop().toLowerCase();
    if (['mp4','mov','avi'].includes(ext)) return '🎬';
    if (['mp3','wav'].includes(ext))       return '🎵';
    if (ext === 'pdf')                     return '📄';
    if (['ppt','pptx'].includes(ext))      return '📊';
    if (['doc','docx'].includes(ext))      return '📝';
    if (['jpg','jpeg','png','gif','webp'].includes(ext)) return '🖼️';
    if (ext === 'zip')                     return '🗜️';
    return '📎';
  };
  const typeIcon = t => ({reading:'📖',video:'🎬',audio:'🎵',slide:'📊',scorm:'🎓'}[t] || '🔗');

  fetch(`${window.location.pathname}?action=get_modules&section_id=${courseId}`)
    .then(r => r.json())
    .then(data => {
      const mods = data.modules || [];
      if (!mods.length) {
        bodyEl.innerHTML = `
          <div style="text-align:center;padding:48px 20px;color:var(--text-3)">
            <div style="font-size:44px;margin-bottom:12px;opacity:.5">📭</div>
            <div style="font-size:14px;font-weight:600;color:var(--text-2);margin-bottom:4px">No modules posted yet</div>
            <div style="font-size:12px">Your instructor hasn't uploaded any course content yet. Check back later.</div>
          </div>`;
        return;
      }
      bodyEl.innerHTML = `
        <div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between">
          <div style="font-size:12px;color:var(--text-3)">${mods.length} module${mods.length !== 1 ? 's' : ''} available</div>
          <span class="badge badge-pink">${mods.reduce((s,m)=>s+(m.lesson_count||0),0)} lessons total</span>
        </div>` +
        mods.map((m, i) => {
          const lessons = m.lessons || [];
          const lessonsHtml = lessons.length
            ? `<div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:6px">` +
              lessons.map(l => l.resource_url
                ? `<a href="${l.resource_url}" target="_blank"
                     style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid var(--border);border-radius:20px;background:var(--surface-2);font-size:11px;font-weight:600;color:var(--primary);text-decoration:none;transition:.2s"
                     onmouseover="this.style.borderColor='var(--primary)';this.style.background='var(--primary-light)'"
                     onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--surface-2)'">
                    ${fileIcon(l.resource_url)} ${l.title}
                  </a>`
                : `<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border:1.5px solid var(--border);border-radius:20px;background:var(--surface-2);font-size:11px;font-weight:600;color:var(--text-2)">
                    ${typeIcon(l.lesson_type)} ${l.title}
                  </span>`
              ).join('') +
              `</div>` : '';
          return `
            <div style="border:1.5px solid var(--border);border-radius:var(--radius);padding:16px 18px;margin-bottom:14px;background:var(--surface);transition:.2s"
                 onmouseover="this.style.borderColor='var(--primary)';this.style.boxShadow='var(--shadow-md)'"
                 onmouseout="this.style.borderColor='var(--border)';this.style.boxShadow=''">
              <div style="display:flex;align-items:center;gap:12px">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0">${i + 1}</div>
                <div style="flex:1;min-width:0">
                  <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;line-height:1.3">${m.title}</div>
                  ${m.description ? `<div style="font-size:12px;color:var(--text-2);margin-top:3px;line-height:1.5">${m.description}</div>` : ''}
                </div>
                <div style="text-align:right;flex-shrink:0">
                  <span class="badge badge-pink" style="font-size:10px">${m.lesson_count} lesson${m.lesson_count != 1 ? 's' : ''}</span>
                  ${m.published_at ? `<div style="font-size:10px;color:var(--text-3);margin-top:3px">📅 ${new Date(m.published_at).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</div>` : ''}
                </div>
              </div>
              ${lessonsHtml}
            </div>`;
        }).join('');
    })
    .catch(() => {
      bodyEl.innerHTML = `<div style="text-align:center;padding:28px;color:var(--danger);font-size:13px">⚠️ Could not load modules. Please refresh.</div>`;
    });
}

function filterCourses(val) {
  const searchVal = (typeof val === 'string' ? val : document.querySelector('[oninput*="filterCourses"]')?.value || '').toLowerCase();
  const statusVal = document.getElementById('statusFilter')?.value || '';
  const grid = document.getElementById('courseGrid');
  if (!grid) return;
  const cards = grid.querySelectorAll('.course-card');
  let visible = 0;
  cards.forEach((card, i) => {
    const c = studentCourses[i];
    const matchSearch = !searchVal || c.title.toLowerCase().includes(searchVal) || c.code.toLowerCase().includes(searchVal) || c.instructor.toLowerCase().includes(searchVal);
    const matchStatus = !statusVal || (statusVal === 'completed' && c.prog === 100) || (statusVal === 'in-progress' && c.prog < 100);
    card.style.display = (matchSearch && matchStatus) ? '' : 'none';
    if (matchSearch && matchStatus) visible++;
  });
  const cnt = document.getElementById('courseCount');
  if (cnt) cnt.textContent = visible + ' course' + (visible !== 1 ? 's' : '');
}

// ===== ASSIGNMENTS DATA & LOGIC (from DB) =====
const _asgData = <?php echo $js_asg_data_json; ?>;
let _asgPageCourse = 'all';
let _asgPageStatus = 'all';

function parseDue(str) { return new Date(str); }

function setAsgPageCourse(el, val) {
  _asgPageCourse = val;
  document.querySelectorAll('.stu-asg-course-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterAssignments();
}

function setAsgPageStatus(el, val) {
  _asgPageStatus = val;
  document.querySelectorAll('.stu-asg-status-chip').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  filterAssignments();
}

function filterAssignments() {
  const searchVal = (document.getElementById('asgSearch')?.value || '').toLowerCase();
  const statusCfg = { pending:{badge:'badge-amber',label:'Pending'}, submitted:{badge:'badge-blue',label:'Submitted'}, graded:{badge:'badge-green',label:'Graded'} };

  let filtered = _asgData.filter(a => {
    const matchStatus = _asgPageStatus === 'all' || a.status === _asgPageStatus;
    const matchSearch = !searchVal || a.title.toLowerCase().includes(searchVal) || a.course.toLowerCase().includes(searchVal);
    const matchCourse = _asgPageCourse === 'all' || a.course === _asgPageCourse;
    return matchStatus && matchSearch && matchCourse;
  });
  filtered.sort((a, b) => parseDue(a.due) - parseDue(b.due));

  const countEl = document.getElementById('asgPanelCount');
  if (countEl) countEl.textContent = filtered.length === _asgData.length
    ? `${_asgData.length} assignment${_asgData.length !== 1 ? 's' : ''}`
    : `${filtered.length} of ${_asgData.length} shown`;

  const list  = document.getElementById('asgList');
  const empty = document.getElementById('asgListEmpty');
  if (!list) return;

  if (!filtered.length) {
    list.innerHTML = '';
    if (empty) empty.style.display = 'flex';
    return;
  }
  if (empty) empty.style.display = 'none';

  list.innerHTML = filtered.map(a => {
    const cfg = statusCfg[a.status] || {badge:'badge-amber',label:'Pending'};
    return `
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);transition:.15s;cursor:pointer"
        onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:5px">
          <div style="font-size:13px;font-weight:600;line-height:1.4;flex:1">${a.title}</div>
          <span class="badge ${cfg.badge}" style="font-size:10px;white-space:nowrap;flex-shrink:0">${cfg.label}</span>
        </div>
        <div style="font-size:11px;color:var(--text-3);margin-bottom:7px;display:flex;gap:8px;flex-wrap:wrap">
          <span>📚 ${a.course}</span><span>📅 ${a.due}</span><span>⭐ ${a.points} pts</span>
          ${a.grade ? `<span style="color:var(--primary);font-weight:700">Score: ${a.grade}</span>` : ''}
        </div>
        ${a.status === 'pending'
          ? `<div style="display:flex;align-items:center;gap:8px"><div class="progress-bar" style="flex:1;height:4px"><div class="progress-fill" style="width:0%"></div></div><button class="btn btn-primary btn-sm" style="font-size:11px;padding:4px 10px" onclick="event.stopPropagation();openSubmitModal(${a.id})">📤 Submit</button></div>`
          : a.status === 'graded'
            ? `<div style="font-size:11px;color:var(--secondary);font-weight:600">✅ Graded</div>`
            : `<div style="font-size:11px;color:var(--accent);font-weight:600">📬 Awaiting review</div>`}
      </div>`;
  }).join('');
}

function renderAssignments() {
  _asgPageCourse = 'all';
  _asgPageStatus = 'all';
  const pending   = _asgData.filter(a => a.status === 'pending').length;
  const submitted = _asgData.filter(a => a.status === 'submitted').length;
  const graded    = _asgData.filter(a => a.status === 'graded').length;
  const courses   = [...new Set(_asgData.map(a => a.course))];
  const upcoming  = [..._asgData].filter(a => a.status === 'pending').sort((a,b) => parseDue(a.due)-parseDue(b.due)).slice(0,4);
  const recentGraded = [..._asgData].filter(a => a.status === 'graded' && a.grade).sort((a,b) => parseDue(b.due)-parseDue(a.due)).slice(0,3);
  const completionPct = _asgData.length ? Math.round((submitted+graded)/_asgData.length*100) : 0;

  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>Assignments</span></div>
    <h1>Assignments</h1>
    <p>${_asgData.length} total &nbsp;·&nbsp; ${pending} pending &nbsp;·&nbsp; ${submitted} submitted &nbsp;·&nbsp; ${graded} graded</p>
  </div>

  <div class="asg-two-col" style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

    <!-- LEFT: Assignment list panel (mirrors instructor's Recent Assignments panel) -->
    <div class="card" style="padding:0;overflow:hidden">

      <!-- Panel header -->
      <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700">My Assignments</div>
          <span class="badge badge-pink" style="font-size:10px">${pending} pending</span>
        </div>

        <!-- Search -->
        <div style="position:relative;margin-bottom:10px">
          <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;pointer-events:none">🔍</span>
          <input id="asgSearch" type="text" placeholder="Search assignments…"
            style="width:100%;padding:7px 10px 7px 30px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:12px;transition:.2s"
            oninput="filterAssignments()"
            onfocus="this.style.borderColor='var(--primary)'"
            onblur="this.style.borderColor='var(--border)'">
        </div>

        <!-- Course chips -->
        ${courses.length > 0 ? `
        <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px">
          <button class="stu-asg-course-chip rec-chip active" onclick="setAsgPageCourse(this,'all')">All</button>
          ${courses.map(c => `<button class="stu-asg-course-chip rec-chip" onclick="setAsgPageCourse(this,'${c.replace(/'/g,"\\'")}')"> ${c.split(' ')[0]}</button>`).join('')}
        </div>` : ''}

        <!-- Status chips -->
        <div style="display:flex;gap:5px;flex-wrap:wrap">
          <button class="stu-asg-status-chip rec-chip active" onclick="setAsgPageStatus(this,'all')">All</button>
          <button class="stu-asg-status-chip rec-chip" onclick="setAsgPageStatus(this,'pending')">⏳ Pending</button>
          <button class="stu-asg-status-chip rec-chip" onclick="setAsgPageStatus(this,'submitted')">📬 Submitted</button>
          <button class="stu-asg-status-chip rec-chip" onclick="setAsgPageStatus(this,'graded')">✅ Graded</button>
        </div>
      </div>

      <!-- Summary count strip -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center;border-bottom:1px solid var(--border)">
        <div style="padding:10px 4px;border-right:1px solid var(--border)">
          <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--accent)">${pending}</div>
          <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Pending</div>
        </div>
        <div style="padding:10px 4px;border-right:1px solid var(--border)">
          <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--secondary)">${submitted}</div>
          <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Submitted</div>
        </div>
        <div style="padding:10px 4px">
          <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--primary)">${graded}</div>
          <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Graded</div>
        </div>
      </div>

      <!-- Result count -->
      <div id="asgPanelCount" style="font-size:11px;color:var(--text-3);padding:6px 18px 0">${_asgData.length} assignments</div>

      <!-- Assignment list (populated by filterAssignments) -->
      <div id="asgList" style="max-height:520px;overflow-y:auto"></div>

      <!-- Empty state -->
      <div id="asgListEmpty" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:32px 20px;color:var(--text-3)">
        <div style="font-size:32px;margin-bottom:8px">📭</div>
        <div style="font-size:13px;font-weight:600;color:var(--text-2)">No assignments match</div>
        <div style="font-size:11px;margin-top:4px">Try a different filter or search term</div>
      </div>
    </div>

    <!-- RIGHT: Summary sidebar -->
    <div>

      <!-- Progress card -->
      <div class="card" style="margin-bottom:16px;padding:0;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
          <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700">📊 My Progress</div>
        </div>
        <div style="padding:14px 16px;display:flex;flex-direction:column;gap:10px">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:12px;color:var(--text-2)">Total Assigned</span>
            <span style="font-size:14px;font-weight:700">${_asgData.length}</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:12px;color:var(--text-2)">Completion Rate</span>
            <span style="font-size:14px;font-weight:700;color:var(--primary)">${completionPct}%</span>
          </div>
          <div class="progress-bar" style="height:6px">
            <div class="progress-fill" style="width:${completionPct}%"></div>
          </div>
        </div>
      </div>

      <!-- Upcoming deadlines -->
      ${upcoming.length ? `
      <div class="card" style="margin-bottom:16px;padding:0;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
          <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700">⏰ Upcoming Deadlines</div>
        </div>
        <div>
          ${upcoming.map(a => `
          <div style="display:flex;align-items:center;gap:10px;padding:9px 16px;border-bottom:1px solid var(--border);cursor:pointer;transition:.15s"
            onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''"
            onclick="openSubmitModal(${a.id})">
            <div style="width:32px;height:32px;border-radius:9px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">📝</div>
            <div style="flex:1;min-width:0">
              <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${a.title}</div>
              <div style="font-size:10px;color:var(--text-3);margin-top:2px">📅 ${a.due} &nbsp;·&nbsp; ⭐ ${a.points} pts</div>
            </div>
            <span class="badge badge-amber" style="font-size:9px">Due</span>
          </div>`).join('')}
        </div>
      </div>` : `
      <div class="card" style="margin-bottom:16px;text-align:center;color:var(--text-3)">
        <div style="font-size:28px;margin-bottom:8px">🎉</div>
        <div style="font-size:12px;font-weight:600;color:var(--text-2)">No pending deadlines!</div>
        <div style="font-size:11px;margin-top:4px">You're all caught up.</div>
      </div>`}

      <!-- Recent grades -->
      ${recentGraded.length ? `
      <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
          <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700">✅ Recent Grades</div>
        </div>
        <div>
          ${recentGraded.map(a => `
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 16px;border-bottom:1px solid var(--border)">
            <div style="flex:1;min-width:0">
              <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${a.title}</div>
              <div style="font-size:10px;color:var(--text-3)">${a.course}</div>
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--primary)">${a.grade}</div>
          </div>`).join('')}
        </div>
      </div>` : ''}

    </div>
  </div>`;
}

function openSubmitModal(id) {
  const asg = _asgData.find(a => a.id === id);
  document.getElementById('submitTitle').textContent  = asg ? asg.title : 'Assignment';
  document.getElementById('submitCourse').textContent = asg ? asg.course + ' · Due: ' + asg.due : '';
  document.getElementById('submitModal').dataset.asgId = id;
  openModal('submitModal');
}

// ===== QUIZZES DATA & LOGIC (from DB) =====
let _quizData = <?php echo $js_quiz_data_json; ?>;
let _qzTabMode = 'upcoming'; // 'upcoming' | 'past' | 'all'

// Fetch latest quizzes from DB (called when quiz tab opens or after notification)
async function refreshQuizzesFromDB() {
  try {
    const res  = await fetch(window.location.pathname + '?action=get_quizzes');
    const data = await res.json();
    if (data.success && Array.isArray(data.quizzes)) {
      _quizData = data.quizzes;
      filterQzStudentPanel();
    }
  } catch(e) { console.warn('Could not refresh quizzes:', e); }
}

function _renderQuizCard(q) {
  const daysColor = q.daysLeft <= 3 ? 'var(--danger)' : 'var(--accent)';
  return `<div class="card" style="margin-bottom:12px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
          <span style="display:flex;align-items:center;color:var(--primary)">${IC.quizzes}</span>
          <div style="font-weight:700;font-size:15px">${q.title}</div>
        </div>
        <div style="font-size:12px;color:var(--text-2)">${q.course} &nbsp;·&nbsp; ${q.questions} questions &nbsp;·&nbsp; ${q.duration} minutes &nbsp;·&nbsp; Posted by ${q.instructor}</div>
        <div style="margin-top:10px;padding:8px 12px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:12px;color:var(--text-2)">
          📌 Topics: ${q.topics}
        </div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <span class="badge badge-amber" style="display:block;margin-bottom:6px">Upcoming</span>
        <div style="font-size:11px;color:var(--text-3)">Due: ${q.due}</div>
        <div style="font-size:11px;font-weight:600;margin-top:2px;color:${daysColor}">⏰ ${q.daysLeft} days left</div>
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:14px">
      <button class="btn btn-primary btn-sm" onclick="openQuiz(${q.id}, '${q.title}', ${q.duration || 30})">▶ Start Quiz</button>
      <button class="btn btn-outline btn-sm" onclick="openQuizInstructions('${q.title}')">📋 Instructions</button>
    </div>
  </div>`;
}

function _renderPastTable(quizzes) {
  if (!quizzes.length) return '';
  let rows = quizzes.map(q => {
    const rb = q.result==='Perfect' ? 'badge-pink' : q.result==='Average' ? 'badge-amber' : 'badge-green';
    const icon = q.result==='Perfect' ? ' 🌸' : q.result==='Passed' ? ' ✓' : '';
    return `<tr><td><strong>${q.title}</strong></td><td>${q.course}</td><td><strong style="color:var(--primary)">${q.score}/100</strong></td><td>${q.date}</td><td><span class="badge ${rb}">${q.result}${icon}</span></td></tr>`;
  }).join('');
  return `<div class="card"><h3 style="font-size:13px;font-weight:700;margin-bottom:12px">Past Results</h3><div class="table-wrap"><table><thead><tr><th>Quiz</th><th>Course</th><th>Score</th><th>Date Taken</th><th>Result</th></tr></thead><tbody>${rows}</tbody></table></div></div>`;
}

// ===== STUDENT QUIZ PANEL STATE =====
let _stuQzCourseFilter = 'all';
let _stuQzStatusFilter = 'all';

function setStudentQzCourseChip(btn, val) {
  _stuQzCourseFilter = val;
  document.querySelectorAll('.qz-course-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterQzStudentPanel();
}
function setStudentQzStatusChip(btn, val) {
  _stuQzStatusFilter = val;
  document.querySelectorAll('.qz-status-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterQzStudentPanel();
}

function filterQzStudentPanel() {
  const list    = document.getElementById('qzStudentPanelList');
  const empty   = document.getElementById('qzStudentPanelEmpty');
  const countEl = document.getElementById('qzStudentPanelCount');
  if (!list) return;

  const search = (document.getElementById('qzSearchInput')?.value || '').toLowerCase();

  const data = _quizData.filter(q => {
    const matchCourse  = _stuQzCourseFilter === 'all' || q.course === _stuQzCourseFilter;
    const matchStatus  = _stuQzStatusFilter === 'all' || q.status === _stuQzStatusFilter;
    const matchSearch  = !search || q.title.toLowerCase().includes(search) || q.course.toLowerCase().includes(search);
    return matchCourse && matchStatus && matchSearch;
  });

  if (countEl) countEl.textContent = data.length + ' ' + (data.length === 1 ? 'quiz' : 'quizzes') + ' shown';

  if (!data.length) {
    list.innerHTML = '';
    if (empty) empty.style.display = 'flex';
    return;
  }
  if (empty) empty.style.display = 'none';

  const statusBadgeClass = s => s === 'upcoming'  ? 'badge-amber'
                              : s === 'submitted'  ? 'badge-blue'
                              : s === 'completed'  ? 'badge-green'
                              : 'badge-gray';
  const statusLabel      = s => s === 'upcoming'  ? '⏳ Pending'
                              : s === 'submitted'  ? '📤 Submitted'
                              : s === 'completed'  ? '✅ Graded'
                              : s;

  list.innerHTML = data.map(q => {
    const scoreColor = q.score >= 90 ? 'var(--primary)' : q.score >= 75 ? 'var(--secondary)' : q.score > 0 ? 'var(--danger)' : 'var(--text-3)';
    const isGraded   = q.status === 'completed' && q.score != null;
    const rb = q.result === 'Perfect' ? 'badge-pink' : q.result === 'Average' ? 'badge-amber' : 'badge-green';
    return `
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);transition:.15s"
        onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background=''">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:5px">
          <div style="font-size:13px;font-weight:600;line-height:1.4;flex:1">${q.title}</div>
          <span class="badge ${statusBadgeClass(q.status)}" style="font-size:10px;white-space:nowrap;flex-shrink:0">${statusLabel(q.status)}</span>
        </div>
        <div style="font-size:11px;color:var(--text-3);margin-bottom:7px;display:flex;gap:8px;flex-wrap:wrap">
          <span>📚 ${q.course}</span>
          ${q.duration ? `<span>⏱ ${q.duration} min</span>` : ''}
          ${q.date     ? `<span>📅 ${q.date}</span>`         : ''}
        </div>
        ${isGraded ? `
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
          <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:${q.score}%"></div></div>
          <span style="font-size:12px;font-weight:700;color:${scoreColor}">${q.score}/100</span>
          ${q.result ? `<span class="badge ${rb}" style="font-size:10px">${q.result}</span>` : ''}
        </div>` : q.status === 'upcoming' ? `
        <div style="font-size:11px;color:var(--accent);font-weight:600">⏳ Not yet attempted</div>
        <div style="display:flex;gap:8px;margin-top:10px">
          <button class="btn btn-primary btn-sm" onclick="openQuiz(${q.id}, '${q.title.replace(/'/g,"\\'")}', ${q.duration || 30})">▶ Start Quiz</button>
          <button class="btn btn-outline btn-sm" onclick="openQuizInstructions('${q.title.replace(/'/g,"\\'")}')">📋 Instructions</button>
        </div>` : `
        <div style="font-size:11px;color:var(--secondary);font-weight:600">📤 Awaiting instructor review</div>`}
      </div>`;
  }).join('');
}

function filterAndSearchQuizzes() {
  const searchVal = (document.getElementById('quizSearch')?.value || '').toLowerCase();
  const filterVal  = document.getElementById('quizFilter')?.value || '';
  let filtered = _quizData.filter(q => {
    const matchSearch  = !searchVal || q.title.toLowerCase().includes(searchVal) || q.course.toLowerCase().includes(searchVal);
    const matchFilter  = !filterVal || q.status === filterVal || (filterVal === 'perfect' && q.result === 'Perfect');
    const matchTab     = _qzTabMode === 'all' ||
                         (_qzTabMode === 'upcoming' && q.status === 'upcoming') ||
                         (_qzTabMode === 'past'     && q.status === 'completed');
    return matchSearch && matchFilter && matchTab;
  });
  const cnt = document.getElementById('quizCount');
  if (cnt) cnt.textContent = filtered.length + ' ' + (filtered.length === 1 ? 'quiz' : 'quizzes');
  const upcoming  = filtered.filter(q => q.status === 'upcoming');
  const completed = filtered.filter(q => q.status === 'completed');
  let html = '';
  if (upcoming.length)  html += upcoming.map(_renderQuizCard).join('');
  if (completed.length) html += _renderPastTable(completed);
  if (!filtered.length) html = `<div style="text-align:center;padding:40px;color:var(--text-2)"><div style="font-size:36px;margin-bottom:8px">❌</div><div>No quizzes found</div></div>`;
  const tc = document.getElementById('quizTabContent');
  if (tc) tc.innerHTML = html;
}

function switchQzTab(tab, btn) {
  _qzTabMode = tab === 'past' ? 'past' : 'upcoming';
  document.querySelectorAll('#qzTabRow .tab-pill').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterAndSearchQuizzes();
}

function renderQuizzes() {
  _qzTabMode = 'all';
  const pending   = _quizData.filter(q=>q.status==='upcoming').length;
  const submitted = _quizData.filter(q=>q.status==='submitted').length;
  const graded    = _quizData.filter(q=>q.status==='completed').length;
  const avgScore  = (() => { const s = _quizData.filter(q=>q.score); return s.length ? Math.round(s.reduce((a,q)=>a+q.score,0)/s.length) : 0; })();

  // Build unique course list for chips
  const qzCourses = [...new Set(_quizData.map(q => q.course))];

  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>Quizzes</span></div>
    <h1>Quizzes</h1>
    <p>Track your quiz submissions and results</p>
  </div>

  <div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-val">${pending}</div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon blue">📤</div><div><div class="stat-val">${submitted}</div><div class="stat-label">Submitted</div></div></div>
    <div class="stat-card"><div class="stat-icon pink">✅</div><div><div class="stat-val">${graded}</div><div class="stat-label">Graded</div></div></div>
    <div class="stat-card"><div class="stat-icon blue">📊</div><div><div class="stat-val">${avgScore ? avgScore + '%' : '—'}</div><div class="stat-label">Avg. Score</div></div></div>
  </div>

  <div class="card" style="padding:0;overflow:hidden">

    <!-- Panel header -->
    <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700">Recent Quizzes</div>
        <span class="badge badge-blue" style="font-size:10px">${pending} pending</span>
      </div>

      <!-- Search -->
      <div style="position:relative;margin-bottom:10px">
        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:13px;pointer-events:none">🔍</span>
        <input id="qzSearchInput" type="text" placeholder="Search quizzes…"
          style="width:100%;padding:7px 10px 7px 30px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:12px;transition:.2s"
          oninput="filterQzStudentPanel()"
          onfocus="this.style.borderColor='var(--primary)'"
          onblur="this.style.borderColor='var(--border)'">
      </div>

      <!-- Course chips -->
      <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px">
        <button class="qz-course-chip rec-chip active" onclick="setStudentQzCourseChip(this,'all')">All</button>
        ${qzCourses.map(c => `<button class="qz-course-chip rec-chip" onclick="setStudentQzCourseChip(this,'${c.replace(/'/g,"\\'")}')">${c}</button>`).join('')}
      </div>

      <!-- Status chips -->
      <div style="display:flex;gap:5px;flex-wrap:wrap">
        <button class="qz-status-chip rec-chip active" onclick="setStudentQzStatusChip(this,'all')">All Status</button>
        <button class="qz-status-chip rec-chip" onclick="setStudentQzStatusChip(this,'upcoming')">⏳ Pending</button>
        <button class="qz-status-chip rec-chip" onclick="setStudentQzStatusChip(this,'submitted')">📤 Submitted</button>
        <button class="qz-status-chip rec-chip" onclick="setStudentQzStatusChip(this,'completed')">✅ Graded</button>
      </div>
    </div>

    <!-- Summary strip -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center;border-bottom:1px solid var(--border)">
      <div style="padding:10px 4px;border-right:1px solid var(--border)">
        <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--accent)">${pending}</div>
        <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Pending</div>
      </div>
      <div style="padding:10px 4px;border-right:1px solid var(--border)">
        <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--secondary)">${submitted}</div>
        <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Submitted</div>
      </div>
      <div style="padding:10px 4px">
        <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700;color:var(--primary)">${graded}</div>
        <div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px">Graded</div>
      </div>
    </div>

    <!-- Result count -->
    <div id="qzStudentPanelCount" style="font-size:11px;color:var(--text-3);padding:6px 18px 0"></div>

    <!-- Quiz list -->
    <div id="qzStudentPanelList" style="max-height:520px;overflow-y:auto"></div>

    <!-- Empty state -->
    <div id="qzStudentPanelEmpty" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:32px 20px;color:var(--text-3)">
      <div style="font-size:36px;margin-bottom:8px">🔍</div>
      <div style="font-size:13px;font-weight:600">No quizzes match your filters</div>
    </div>

  </div>`;
}

// ===== DISCUSSIONS DATA & LOGIC (from DB) =====
const _discThreads = <?php echo $js_disc_threads_json; ?>;
const currentStudentId = <?php echo (int)$student_user_id; ?>;
let _discActiveId = _discThreads.length > 0 ? _discThreads[0].id : 0;

// ── helpers ──────────────────────────────────────────────────────────────────
function _discBuildListItem(t) {
  return `
    <div class="discussion-item${t.id === _discActiveId ? ' active' : ''}"
         onclick="selectThread(${t.id})" data-tid="${t.id}">
      <div class="discussion-item-title">${t.title}</div>
      <div class="discussion-item-meta">
        <span>${t.author}</span>
        <span class="discussion-count" id="disc-count-${t.id}">${t.replies} ${t.replies === 1 ? 'reply' : 'replies'}</span>
      </div>
      <div style="margin-top:8px"><span class="badge badge-pink" style="font-size:10px">${t.course}</span></div>
    </div>`;
}

function _discBuildDetail(t) {
  const commentsHtml = (t.comments || []).map(c => `
    <div class="reply-item" id="reply-card-${c.id}">
      <div class="reply-header">
        <div class="reply-avatar" style="${c.isInstructor ? 'background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;' : ''}">${c.initials}</div>
        <div style="flex:1">
          <div class="reply-author" style="display:flex;align-items:center;gap:7px">
            ${c.author}
            ${c.isInstructor ? `<span style="background:var(--primary);color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px">Instructor</span>` : ''}
          </div>
          <div class="reply-time">${c.time}</div>
        </div>
      </div>
      <!-- View mode -->
      <div id="reply-view-${c.id}">
        <div class="reply-body" style="white-space:pre-line">${c.body}</div>
        <div class="reply-actions">
          ${c.authorId === currentStudentId ? `<button class="reply-action-btn" onclick="stuOpenReplyEdit(${c.id})" style="font-size:12px" title="Edit reply">✏️ Edit</button>` : ''}
          ${c.authorId === currentStudentId ? `<button class="reply-action-btn" onclick="stuDeleteReply(${c.id},${t.id})" style="font-size:12px;color:var(--danger)" title="Delete reply">✕ Delete</button>` : ''}
        </div>
      </div>
      <!-- Edit mode -->
      <div id="reply-edit-${c.id}" style="display:none;margin-top:8px">
        <textarea id="reply-edit-ta-${c.id}" style="width:100%;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--primary);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:60px">${(c.body||'').replace(/`/g,"'")}</textarea>
        <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:6px">
          <button class="btn btn-outline btn-sm" onclick="stuCloseReplyEdit(${c.id})">Cancel</button>
          <button class="btn btn-primary btn-sm" onclick="stuSaveReplyEdit(${c.id},${t.id})">💾 Save</button>
        </div>
      </div>
    </div>`).join('');

  return `
    <div class="discussion-detail-header">
      <div class="discussion-detail-title">${t.title}</div>
      <div class="discussion-detail-meta">
        <span>${t.author}</span>
        <span>${t.timeAgo}</span>
        <span id="disc-detail-count-${t.id}">${t.replies} ${t.replies === 1 ? 'reply' : 'replies'}</span>
        <span class="badge badge-pink" style="font-size:11px">${t.course}</span>
      </div>
    </div>
    <div class="discussion-detail-content">
      <div class="discussion-original-post">
        <div class="post-header">
          <div class="post-avatar">${t.authorInitials}</div>
          <div class="post-info">
            <div style="display:flex;align-items:center;gap:8px">
              <div class="post-author">${t.author}</div>
              <span style="background:var(--primary);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px">OP</span>
            </div>
            <div class="post-time">${t.postedTime}</div>
          </div>
        </div>
        <div class="post-body" style="white-space:pre-line" id="thread-body-view-${t.id}">${t.body}</div>
        <!-- Thread inline edit form (owner only) -->
        <div id="thread-edit-form-${t.id}" style="display:none;margin-top:10px">
          <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:8px">✏️ Edit Discussion</div>
          <input type="text" id="thread-edit-title-${t.id}" value="${(t.title||'').replace(/"/g,'&quot;')}" style="width:100%;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;margin-bottom:8px">
          <textarea id="thread-edit-body-${t.id}" style="width:100%;padding:8px 10px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface);color:var(--text);font-family:inherit;font-size:12px;resize:vertical;min-height:80px">${(t.body||'').replace(/`/g,"'")}</textarea>
          <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px">
            <button class="btn btn-outline btn-sm" onclick="stuCloseThreadEdit(${t.id})">Cancel</button>
            <button class="btn btn-primary btn-sm" onclick="stuSaveThreadEdit(${t.id})">💾 Save Changes</button>
          </div>
        </div>
        <div class="post-actions">
          <button class="post-action-btn" onclick="_discToggleReply()">💬 Reply</button>
          ${t.authorId === currentStudentId ? `<button class="post-action-btn" onclick="stuOpenThreadEdit(${t.id})" style="font-size:12px">✏️ Edit</button>` : ''}
          ${t.authorId === currentStudentId ? `<button class="post-action-btn" onclick="stuDeleteThread(${t.id})" style="font-size:12px;color:var(--danger)">✕ Delete</button>` : ''}
        </div>
      </div>
      ${commentsHtml}
      <div class="discussion-composer" id="replyComposer" style="display:none">
        <div class="composer-label">Write a Reply</div>
        <textarea class="composer-textarea" id="replyText" placeholder="Share your thoughts or answer..."></textarea>
        <div class="composer-actions">
          <button class="btn btn-ghost btn-sm" onclick="_discToggleReply()">Cancel</button>
          <button class="btn btn-primary btn-sm" onclick="_discPostReply()">📤 Post Reply</button>
        </div>
      </div>
    </div>`;
}

function _discRenderList(threads) {
  const list = document.getElementById('discList');
  if (!list) return;
  if (!threads.length) {
    list.innerHTML = `<div style="padding:32px 16px;text-align:center;color:var(--text-3);font-size:13px">
      <div style="font-size:36px;margin-bottom:10px;opacity:.5">💬</div>No discussions yet</div>`;
    return;
  }
  list.innerHTML = threads.map(t => _discBuildListItem(t)).join('');
}

function _discToggleReply() {
  const c = document.getElementById('replyComposer');
  if (!c) return;
  const opening = c.style.display === 'none';
  c.style.display = opening ? 'flex' : 'none';
  if (opening) document.getElementById('replyText')?.focus();
}

// ===== STUDENT THREAD EDIT/DELETE =====
function stuOpenThreadEdit(threadId) {
  document.getElementById('thread-body-view-' + threadId).style.display = 'none';
  document.getElementById('thread-edit-form-' + threadId).style.display = 'block';
  const ti = document.getElementById('thread-edit-title-' + threadId);
  if (ti) setTimeout(() => ti.focus(), 50);
}
function stuCloseThreadEdit(threadId) {
  document.getElementById('thread-edit-form-' + threadId).style.display = 'none';
  document.getElementById('thread-body-view-' + threadId).style.display = 'block';
}
function stuSaveThreadEdit(threadId) {
  const title = document.getElementById('thread-edit-title-' + threadId)?.value.trim();
  const body  = document.getElementById('thread-edit-body-' + threadId)?.value.trim();
  if (!title || !body) { showToast('Title and message are required', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_thread');
  fd.append('thread_id', threadId);
  fd.append('title', title);
  fd.append('body', body);
  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const t = _discThreads.find(x => x.id === threadId);
        if (t) { t.title = title; t.body = body; }
        stuCloseThreadEdit(threadId);
        const detail = document.getElementById('discDetail');
        if (detail && t) detail.innerHTML = _discBuildDetail(t);
        _discRenderList(_discThreads);
        showToast('Discussion updated!', 'success');
      } else {
        showToast(data.message || 'Failed to update discussion', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}
function stuDeleteThread(threadId) {
  if (!confirm('Delete this discussion? All replies will also be removed.')) return;
  const fd = new FormData();
  fd.append('action', 'delete_thread');
  fd.append('thread_id', threadId);
  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const idx = _discThreads.findIndex(x => x.id === threadId);
        if (idx !== -1) _discThreads.splice(idx, 1);
        _discActiveId = _discThreads.length > 0 ? _discThreads[0].id : 0;
        _discRenderList(_discThreads);
        const detail = document.getElementById('discDetail');
        if (detail) {
          const next = _discThreads[0];
          detail.innerHTML = next ? _discBuildDetail(next) : '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;padding:48px 20px;color:var(--text-3)"><div style="font-size:44px;margin-bottom:12px;opacity:.5">💬</div><div style="font-size:14px;font-weight:600;color:var(--text-2)">No thread selected</div></div>';
        }
        showToast('Discussion deleted', 'success');
      } else {
        showToast(data.message || 'Failed to delete discussion', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}

// ===== STUDENT REPLY EDIT/DELETE =====
function stuOpenReplyEdit(replyId) {
  document.getElementById(`reply-view-${replyId}`).style.display = 'none';
  document.getElementById(`reply-edit-${replyId}`).style.display = 'block';
  const ta = document.getElementById(`reply-edit-ta-${replyId}`);
  if (ta) setTimeout(() => ta.focus(), 50);
}
function stuCloseReplyEdit(replyId) {
  document.getElementById(`reply-edit-${replyId}`).style.display = 'none';
  document.getElementById(`reply-view-${replyId}`).style.display = 'block';
}
function stuSaveReplyEdit(replyId, discId) {
  const body = document.getElementById(`reply-edit-ta-${replyId}`)?.value.trim();
  if (!body) { showToast('Reply cannot be empty', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'update_reply');
  fd.append('reply_id', replyId);
  fd.append('body', body);
  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const t = _discThreads.find(x => x.id === discId);
        if (t) {
          const c = t.comments.find(x => x.id === replyId);
          if (c) c.body = body;
        }
        const detail = document.getElementById('discDetail');
        if (detail && t) detail.innerHTML = _discBuildDetail(t);
        showToast('Reply updated!', 'success');
      } else {
        showToast(data.message || 'Failed to update reply', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}
function stuDeleteReply(replyId, discId) {
  if (!confirm('Delete this reply?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_reply');
  fd.append('reply_id', replyId);
  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const t = _discThreads.find(x => x.id === discId);
        if (t) {
          t.comments = t.comments.filter(c => c.id !== replyId);
          t.replies  = t.comments.length;
        }
        // Re-render detail
        const detail = document.getElementById('discDetail');
        if (detail && t) detail.innerHTML = _discBuildDetail(t);
        // Update sidebar counts
        const sideCount   = document.getElementById('disc-count-' + discId);
        const detailCount = document.getElementById('disc-detail-count-' + discId);
        if (sideCount)   sideCount.textContent   = (t?.replies ?? 0) + ' ' + ((t?.replies ?? 0) === 1 ? 'reply' : 'replies');
        if (detailCount) detailCount.textContent = (t?.replies ?? 0) + ' ' + ((t?.replies ?? 0) === 1 ? 'reply' : 'replies');
        showToast('Reply deleted', 'success');
      } else {
        showToast(data.message || 'Failed to delete reply', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}

function _discPostReply() {
  const ta = document.getElementById('replyText');
  const text = ta?.value?.trim();
  if (!text) { showToast('Please write something first', 'error'); return; }
  const t = _discThreads.find(x => x.id === _discActiveId);
  if (!t) return;

  const stuInitials = <?php echo json_encode($initials); ?>;
  const stuName     = <?php echo json_encode($student_name); ?>;
  const newComment  = { id: 0, authorId: currentStudentId, initials: stuInitials, author: stuName, time: 'Just now', body: text, likes: 0, isInstructor: false };
  t.comments.push(newComment);
  t.replies = t.comments.length;

  // Re-render detail panel and restore composer open
  const detail = document.getElementById('discDetail');
  if (detail) detail.innerHTML = _discBuildDetail(t);
  // Re-open composer so user sees it was posted
  const freshComposer = document.getElementById('replyComposer');
  if (freshComposer) freshComposer.style.display = 'none';

  // Update sidebar reply counts
  const sideCount  = document.getElementById('disc-count-' + t.id);
  const detailCount = document.getElementById('disc-detail-count-' + t.id);
  if (sideCount)   sideCount.textContent   = t.replies + (t.replies === 1 ? ' reply' : ' replies');
  if (detailCount) detailCount.textContent = t.replies + (t.replies === 1 ? ' reply' : ' replies');

  showToast('Reply posted! 🎉', 'success');

  // Persist
  const fd = new FormData();
  fd.append('action', 'post_reply');
  fd.append('thread_id', _discActiveId);
  fd.append('body', text);
  fetch(location.href, { method: 'POST', body: fd }).then(r => r.json()).then(data => {
    if (data.reply_id) newComment.id = data.reply_id;
  }).catch(() => {});
}

function selectThread(id) {
  _discActiveId = id;
  // Update active state in sidebar
  document.querySelectorAll('.discussion-item').forEach(el => {
    el.classList.toggle('active', parseInt(el.dataset.tid) === id);
  });
  const t = _discThreads.find(x => x.id === id);
  const detail = document.getElementById('discDetail');
  if (detail) detail.innerHTML = t ? _discBuildDetail(t) : '';
}

function filterDiscussions() {
  const val = (document.getElementById('discSearch')?.value || '').toLowerCase();
  const filtered = val
    ? _discThreads.filter(t => t.title.toLowerCase().includes(val) || t.author.toLowerCase().includes(val) || t.course.toLowerCase().includes(val))
    : _discThreads;
  _discRenderList(filtered);
}

function _discPostThread() {
  const titleEl   = document.getElementById('newDiscTitle');
  const bodyEl    = document.getElementById('newDiscBody');
  const sectionEl = document.getElementById('newDiscSection');
  const title  = titleEl?.value?.trim();
  const body   = bodyEl?.value?.trim();
  const sectionId = parseInt(sectionEl?.value || '0', 10);
  if (!title)     { showToast('Please enter a title', 'error'); return; }
  if (!body)      { showToast('Please write a message', 'error'); return; }
  if (!sectionId) { showToast('Please select a course', 'error'); return; }

  const course = studentCourses.find(c => c.id === sectionId);
  const stuInitials = <?php echo json_encode($initials); ?>;
  const stuName     = <?php echo json_encode($student_name); ?>;

  closeModal('newThreadModal');
  showToast('Posting discussion…', '');

  const fd = new FormData();
  fd.append('action', 'post_thread');
  fd.append('section_id', sectionId);
  fd.append('title', title);
  fd.append('body', body);
  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message || 'Failed to post', 'error'); return; }
      const newThread = {
        id:             data.thread_id,
        title:          title,
        author:         'You',
        authorInitials: stuInitials,
        course:         data.course_code || (course ? course.code : ''),
        timeAgo:        'Just now',
        replies:        0,
        body:           body,
        postedTime:     'Just now',
        isOriginalPost: true,
        comments:       [],
      };
      _discThreads.unshift(newThread);
      _discActiveId = newThread.id;
      // Re-render list and show new thread detail
      _discRenderList(_discThreads);
      const detail = document.getElementById('discDetail');
      if (detail) detail.innerHTML = _discBuildDetail(newThread);
      if (titleEl) titleEl.value = '';
      if (bodyEl)  bodyEl.value  = '';
      showToast('Discussion posted! 🎉', 'success');
    })
    .catch(() => showToast('Network error — please retry', 'error'));
}

// ===== COURSE-SCOPED DISCUSSION TAB =====
function _renderCourseDiscussion(courseId) {
  const sid    = parseInt(courseId, 10);
  const course = studentCourses.find(c => c.id === sid);
  const code   = course ? course.code : '';
  const threads = _discThreads.filter(t => t.course === code);

  // build unique id namespace so selectThread still works
  const listId   = 'cdiscList-' + sid;
  const detailId = 'cdiscDetail-' + sid;

  function buildListHtml(list) {
    if (!list.length) return `<div style="padding:32px 16px;text-align:center;color:var(--text-3);font-size:13px"><div style="font-size:36px;margin-bottom:10px;opacity:.5">💬</div>No discussions yet for this course</div>`;
    return list.map(t => `
      <div class="discussion-item${t.id === _discActiveId ? ' active' : ''}"
           onclick="_cdiscSelect(${sid},'${detailId}',${t.id})" data-tid="${t.id}" data-scope="${sid}">
        <div class="discussion-item-title">${t.title}</div>
        <div class="discussion-item-meta">
          <span>${t.author}</span>
          <span class="discussion-count" id="disc-count-${t.id}">${t.replies} ${t.replies === 1 ? 'reply' : 'replies'}</span>
        </div>
      </div>`).join('');
  }

  const firstThread = threads[0] || null;
  const emptyDetail = `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;padding:48px 20px;color:var(--text-3)"><div style="font-size:44px;margin-bottom:12px;opacity:.5">💬</div><div style="font-size:14px;font-weight:600;color:var(--text-2)">No thread selected</div><div style="font-size:12px;margin-top:6px">Pick a thread from the list or start a new one.</div></div>`;

  return `
  <div class="discussions-container" style="min-height:520px">
    <div class="discussions-list-panel">
      <div class="discussions-header">
        <input class="discussions-search" type="text" placeholder="Search discussions..."
          oninput="_cdiscFilter(${sid},'${listId}','${detailId}',this.value)">
      </div>
      <div class="discussions-list" id="${listId}">
        ${buildListHtml(threads)}
      </div>
      <button class="new-disc-btn" onclick="_cdiscOpenNew(${sid})">+ New Discussion</button>
    </div>
    <div class="discussion-detail-panel" id="${detailId}" style="min-height:400px">
      ${firstThread ? _discBuildDetail(firstThread) : emptyDetail}
    </div>
  </div>

  <!-- Inline New Thread Modal for course tab -->
  <div class="modal-overlay" id="cdiscNewModal-${sid}">
    <div class="modal">
      <div class="modal-header">
        <h2>💬 New Discussion</h2>
        <button class="btn btn-ghost btn-sm" onclick="closeModal('cdiscNewModal-${sid}')">✕</button>
      </div>
      <div class="form-group">
        <label class="form-label">Title</label>
        <div class="input-wrap">
          <span class="icon">📌</span>
          <input type="text" id="cdiscTitle-${sid}" placeholder="e.g. Question about Chapter 5…">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Message</label>
        <textarea id="cdiscBody-${sid}" style="width:100%;min-height:110px;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;resize:vertical"
                  placeholder="Write your question or discussion topic…"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline btn-sm" onclick="closeModal('cdiscNewModal-${sid}')">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="_cdiscPostThread(${sid},'${listId}','${detailId}')">📤 Post</button>
      </div>
    </div>
  </div>`;
}

function _cdiscOpenNew(sid) { openModal('cdiscNewModal-' + sid); }

function _cdiscSelect(sid, detailId, threadId) {
  _discActiveId = threadId;
  document.querySelectorAll('.discussion-item[data-scope="' + sid + '"]').forEach(el => {
    el.classList.toggle('active', parseInt(el.dataset.tid) === threadId);
  });
  const t = _discThreads.find(x => x.id === threadId);
  const detail = document.getElementById(detailId);
  if (detail) detail.innerHTML = t ? _discBuildDetail(t) : '';
}

function _cdiscFilter(sid, listId, detailId, val) {
  const course = studentCourses.find(c => c.id === sid);
  const code   = course ? course.code : '';
  const filtered = val.trim()
    ? _discThreads.filter(t => t.course === code && (t.title.toLowerCase().includes(val.toLowerCase()) || t.author.toLowerCase().includes(val.toLowerCase())))
    : _discThreads.filter(t => t.course === code);
  const list = document.getElementById(listId);
  if (!list) return;
  if (!filtered.length) {
    list.innerHTML = `<div style="padding:32px 16px;text-align:center;color:var(--text-3);font-size:13px"><div style="font-size:36px;margin-bottom:10px;opacity:.5">💬</div>No matching discussions</div>`;
    return;
  }
  list.innerHTML = filtered.map(t => `
    <div class="discussion-item${t.id === _discActiveId ? ' active' : ''}"
         onclick="_cdiscSelect(${sid},'${detailId}',${t.id})" data-tid="${t.id}" data-scope="${sid}">
      <div class="discussion-item-title">${t.title}</div>
      <div class="discussion-item-meta">
        <span>${t.author}</span>
        <span class="discussion-count" id="disc-count-${t.id}">${t.replies} ${t.replies === 1 ? 'reply' : 'replies'}</span>
      </div>
    </div>`).join('');
}

function _cdiscPostThread(sid, listId, detailId) {
  const titleEl = document.getElementById('cdiscTitle-' + sid);
  const bodyEl  = document.getElementById('cdiscBody-' + sid);
  const title   = titleEl?.value?.trim();
  const body    = bodyEl?.value?.trim();
  if (!title) { showToast('Please enter a title', 'error'); return; }
  if (!body)  { showToast('Please write a message', 'error'); return; }

  const stuInitials = <?php echo json_encode($initials); ?>;
  const stuName     = <?php echo json_encode($student_name); ?>;
  const course      = studentCourses.find(c => c.id === sid);

  closeModal('cdiscNewModal-' + sid);
  showToast('Posting discussion…', '');

  const fd = new FormData();
  fd.append('action', 'post_thread');
  fd.append('section_id', sid);
  fd.append('title', title);
  fd.append('body', body);
  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message || 'Failed to post', 'error'); return; }
      const newThread = {
        id:             data.thread_id,
        title:          title,
        author:         'You',
        authorInitials: stuInitials,
        course:         data.course_code || (course ? course.code : ''),
        timeAgo:        'Just now',
        replies:        0,
        body:           body,
        postedTime:     'Just now',
        isOriginalPost: true,
        comments:       [],
      };
      _discThreads.unshift(newThread);
      _discActiveId = newThread.id;
      if (titleEl) titleEl.value = '';
      if (bodyEl)  bodyEl.value  = '';
      // Re-render list
      _cdiscFilter(sid, listId, detailId, '');
      // Show new thread detail
      const detail = document.getElementById(detailId);
      if (detail) detail.innerHTML = _discBuildDetail(newThread);
      showToast('Discussion posted! 🎉', 'success');
    })
    .catch(() => showToast('Network error — please retry', 'error'));
}

// ===== DISCUSSIONS =====
function renderDiscussions() {
  _discActiveId = _discThreads.length > 0 ? _discThreads[0].id : 0;
  const first = _discThreads[0] || null;
  const emptyDetail = `
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;padding:48px 20px;color:var(--text-3)">
      <div style="font-size:44px;margin-bottom:12px;opacity:.5">💬</div>
      <div style="font-size:14px;font-weight:600;color:var(--text-2)">No thread selected</div>
      <div style="font-size:12px;margin-top:6px">Pick a thread from the list or start a new one.</div>
    </div>`;

  const courseOptions = studentCourses.map(c =>
    `<option value="${c.id}">${c.code} — ${c.title}</option>`).join('');

  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>Discussions</span></div>
    <h1>Discussions</h1>
    <p>${_discThreads.length} thread${_discThreads.length !== 1 ? 's' : ''} across your courses</p>
  </div>

  <div class="discussions-container">
    <!-- Thread list panel -->
    <div class="discussions-list-panel">
      <div class="discussions-header">
        <input class="discussions-search" type="text" id="discSearch"
               placeholder="Search discussions..." oninput="filterDiscussions()">
      </div>
      <div class="discussions-list" id="discList">
        ${_discThreads.length
          ? _discThreads.map(t => _discBuildListItem(t)).join('')
          : `<div style="padding:32px 16px;text-align:center;color:var(--text-3);font-size:13px"><div style="font-size:36px;margin-bottom:10px;opacity:.5">💬</div>No discussions yet</div>`}
      </div>
      <button class="new-disc-btn" onclick="openModal('newThreadModal')">+ New Discussion</button>
    </div>

    <!-- Thread detail panel -->
    <div class="discussion-detail-panel" id="discDetail">
      ${first ? _discBuildDetail(first) : emptyDetail}
    </div>
  </div>

  <!-- New Thread Modal -->
  <div class="modal-overlay" id="newThreadModal">
    <div class="modal">
      <div class="modal-header">
        <h2>💬 New Discussion</h2>
        <button class="btn btn-ghost btn-sm" onclick="closeModal('newThreadModal')">✕</button>
      </div>
      <div class="form-group">
        <label class="form-label">Course</label>
        <select id="newDiscSection" style="width:100%;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px">
          <option value="">— Select a course —</option>
          ${courseOptions}
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Title</label>
        <div class="input-wrap">
          <span class="icon">📌</span>
          <input type="text" id="newDiscTitle" placeholder="e.g. Question about Chapter 5…"
                 onkeydown="if(event.key==='Enter')document.getElementById('newDiscBody').focus()">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Message</label>
        <textarea id="newDiscBody" style="width:100%;min-height:110px;padding:10px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;resize:vertical"
                  placeholder="Write your question or discussion topic…"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline btn-sm" onclick="closeModal('newThreadModal')">Cancel</button>
        <button class="btn btn-primary btn-sm" onclick="_discPostThread()">📤 Post</button>
      </div>
    </div>
  </div>`;
}
// ===== NOTIFICATIONS (from DB) =====
const _notifData = <?php echo $js_notifications_json; ?>;
let _stuNotifFilter = 'all';

function stuSetNotifFilter(btn, filter) {
  _stuNotifFilter = filter;
  document.querySelectorAll('.notif-filter-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderStuNotifList();
}

function _syncNotifUI() {
  const unread = _notifData.filter(n => n.unread).length;
  // Sidebar nav badge
  const ni = navItems.find(n => n.id === 'notifications');
  if (ni) { ni.badge = unread; renderNav(); }
  // Topbar dot
  const dot = document.getElementById('topbarNotifDot');
  if (dot) dot.style.display = unread > 0 ? '' : 'none';
  // Topbar panel badge
  const panelBadge = document.getElementById('topNotifUnreadBadge');
  if (panelBadge) {
    panelBadge.textContent = unread || '';
    panelBadge.style.display = unread > 0 ? 'inline' : 'none';
  }
}

function stuMarkAllRead() {
  _notifData.forEach(n => n.unread = false);
  renderStuNotifList();
  renderTopNotifList();
  _syncNotifUI();
  const badge = document.querySelector('[data-nav="notifications"] .nav-badge');
  if (badge) badge.remove();
  const countEl = document.getElementById('stuNotifCount');
  if (countEl) countEl.textContent = 'All caught up';
  showToast('All notifications marked as read', 'success');
  // Persist to DB
  const fd = new FormData();
  fd.append('action', 'mark_all_notifs_read');
  fetch(location.href, { method: 'POST', body: fd }).catch(() => {});
}

function stuMarkOneRead(id) {
  const n = _notifData.find(x => x.id === id);
  if (n && n.unread) {
    n.unread = false;
    renderStuNotifList();
    renderTopNotifList();
    const remaining = _notifData.filter(x => x.unread).length;
    if (remaining === 0) {
      const badge = document.querySelector('[data-nav="notifications"] .nav-badge');
      if (badge) badge.remove();
      const dot = document.getElementById('topbarNotifDot');
      if (dot) dot.style.display = 'none';
      const panelBadge = document.getElementById('topNotifUnreadBadge');
      if (panelBadge) panelBadge.style.display = 'none';
    } else {
      const panelBadge = document.getElementById('topNotifUnreadBadge');
      if (panelBadge) { panelBadge.textContent = remaining; panelBadge.style.display = 'inline'; }
    }
    const countEl = document.getElementById('stuNotifCount');
    if (countEl) countEl.textContent = remaining > 0 ? `${remaining} unread` : 'All caught up';
    // Persist to DB
    const fd = new FormData();
    fd.append('action', 'mark_notif_read');
    fd.append('notif_id', id);
    fetch(location.href, { method: 'POST', body: fd }).catch(() => {});
  }
}

function renderStuNotifList() {
  const container = document.getElementById('stuNotifList');
  const countEl   = document.getElementById('stuNotifCount');
  if (!container) return;

  let data = _notifData;
  if (_stuNotifFilter === 'unread')       data = data.filter(n => n.unread);
  else if (_stuNotifFilter === 'assignment')  data = data.filter(n => n.type === 'assignment');
  else if (_stuNotifFilter === 'quiz')        data = data.filter(n => n.type === 'quiz');
  else if (_stuNotifFilter === 'grade')       data = data.filter(n => n.type === 'grade');
  else if (_stuNotifFilter === 'discussion')  data = data.filter(n => n.type === 'discussion');
  else if (_stuNotifFilter === 'announcement')data = data.filter(n => n.type === 'announcement');

  const unreadCount = _notifData.filter(n => n.unread).length;
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
    <div class="notif-item${n.unread ? ' unread' : ''}" onclick="stuMarkOneRead(${n.id})">
      <div class="notif-icon" style="background:${n.bg};width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">${n.icon}</div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:${n.unread ? '700' : '500'}">${n.title}</div>
        <div class="notif-text" style="font-size:12px;color:var(--text-2);margin-top:2px;line-height:1.4">${n.text}</div>
        <div class="notif-time">${n.time}</div>
      </div>
      ${n.unread ? `<div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:4px"></div>` : ''}
    </div>`).join('');
}

function renderTopNotifList() {
  const container = document.getElementById('topNotifList');
  if (!container) return;
  const items = _notifData.slice(0, 5);
  if (!items.length) {
    container.innerHTML = '<div style="padding:16px;text-align:center;font-size:12px;color:var(--text-3)">No notifications</div>';
    return;
  }
  container.innerHTML = items.map(n => `
    <div class="notif-item${n.unread ? ' unread' : ''}" style="padding:10px 16px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid var(--border);cursor:pointer" onclick="stuMarkOneRead(${n.id});closeNotifs();navigate('notifications')">
      <div class="notif-icon" style="background:${n.bg};width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">${n.icon}</div>
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:${n.unread ? '700' : '500'};line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${n.title}</div>
        <div class="notif-text" style="font-size:11px;color:var(--text-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${n.text}</div>
        <div class="notif-time" style="font-size:10px;color:var(--text-3);margin-top:2px;font-weight:500">${n.time}</div>
      </div>
      ${n.unread ? '<div style="width:7px;height:7px;border-radius:50%;background:var(--danger);flex-shrink:0;margin-top:4px"></div>' : ''}
    </div>`).join('');
}

function renderNotifications() {
  const unreadCount = _notifData.filter(n => n.unread).length;
  setTimeout(renderStuNotifList, 10);
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Home</span><span>›</span><span>Notifications</span></div>
    <h1>Notifications</h1>
    <p>Stay updated on grades, assignments, and course activity</p>
  </div>
  <div class="card">
    <!-- Header row -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
      <div style="display:flex;align-items:center;gap:10px">
        <h3 style="font-size:15px;font-weight:700">All Notifications</h3>
        <span id="stuNotifCount" style="font-size:12px;color:var(--text-3);padding:3px 10px;background:var(--surface-2);border-radius:20px;font-weight:600">${unreadCount > 0 ? unreadCount + ' unread' : 'All caught up'}</span>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="stuMarkAllRead()">✓ Mark all read</button>
    </div>

    <!-- Filter chips -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px">
      <button class="notif-filter-chip active" onclick="stuSetNotifFilter(this,'all')">All</button>
      <button class="notif-filter-chip"        onclick="stuSetNotifFilter(this,'unread')">🔵 Unread</button>
      <button class="notif-filter-chip"        onclick="stuSetNotifFilter(this,'assignment')">📝 Assignments</button>
      <button class="notif-filter-chip"        onclick="stuSetNotifFilter(this,'quiz')">🧪 Quizzes</button>
      <button class="notif-filter-chip"        onclick="stuSetNotifFilter(this,'grade')">✅ Grades</button>
      <button class="notif-filter-chip"        onclick="stuSetNotifFilter(this,'discussion')">💬 Discussions</button>
      <button class="notif-filter-chip"        onclick="stuSetNotifFilter(this,'announcement')">📢 Announcements</button>
    </div>

    <!-- Notification list -->
    <div id="stuNotifList"></div>
  </div>`;
}

// ===== SETTINGS =====
function renderSettings() {
  const _pref = JSON.parse(localStorage.getItem('lf_stu_prefs')||'{}');
  const darkOn       = typeof darkMode !== 'undefined' ? darkMode : false;
  const gradeNotifs  = _pref.gradeNotifs  !== false;
  const deadlineAlert= _pref.deadlineAlert !== false;
  const annNotifs    = _pref.annNotifs    !== false;
  const discussNotifs= _pref.discussNotifs === true;

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
          <div id="stuAvatarInitials" style="width:70px;height:70px;border-radius:18px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#fff"><?php echo $initials; ?></div>
          <img id="stuAvatarImg" src="" alt="" style="display:none;width:70px;height:70px;border-radius:18px;object-fit:cover;position:absolute;top:0;left:0">
        </div>
        <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700"><?php echo htmlspecialchars($student_name); ?></div>
        <div style="font-size:12px;color:var(--text-2);margin-top:3px"><?php echo htmlspecialchars($student_email); ?></div>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:10px">
          <span class="badge badge-pink">Student</span>
          <span class="badge badge-gray"><?php echo htmlspecialchars($student_section ?: 'BSIT'); ?></span>
        </div>
        <input type="file" id="stuPhotoInput" accept="image/*" style="display:none" onchange="previewStuPhoto(this)">
        <button class="btn btn-outline btn-sm btn-full" style="margin-top:14px" onclick="document.getElementById('stuPhotoInput').click()">📷 Change Photo</button>
      </div>

      <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Profile Information</h3>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-wrap"><span class="icon">👤</span><input type="text" id="stuProfName" value="<?php echo htmlspecialchars($student_name); ?>"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap"><span class="icon">✉️</span><input type="email" id="stuProfEmail" value="<?php echo htmlspecialchars($student_email); ?>"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Student ID</label>
          <div class="input-wrap"><span class="icon">#️⃣</span><input type="text" value="<?php echo htmlspecialchars($student_id); ?>" readonly style="opacity:.7"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Program / Section</label>
          <div class="input-wrap"><span class="icon">🎓</span><input type="text" value="<?php echo htmlspecialchars($student_section); ?>" readonly style="opacity:.7"></div>
        </div>
        <button class="btn btn-primary btn-sm" onclick="stuSaveProfile()">💾 Save Changes</button>
      </div>

      <div class="card">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Account</h3>
        <button class="btn btn-danger btn-sm" onclick="doLogout()" style="display:flex;align-items:center;gap:6px">
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
          <div class="setting-info"><h4>Grade Updates</h4><p>Notify when an instructor grades your work</p></div>
          <label class="toggle"><input type="checkbox" ${gradeNotifs?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_stu_prefs')||'{}');p.gradeNotifs=el.checked;localStorage.setItem('lf_stu_prefs',JSON.stringify(p));showToast(el.checked?'Grade notifications on':'Grade notifications off')})(this)"><span class="toggle-slider"></span></label>
        </div>
        <div class="setting-row">
          <div class="setting-info"><h4>Deadline Reminders</h4><p>Alert 24 hours before assignment due dates</p></div>
          <label class="toggle"><input type="checkbox" ${deadlineAlert?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_stu_prefs')||'{}');p.deadlineAlert=el.checked;localStorage.setItem('lf_stu_prefs',JSON.stringify(p));showToast(el.checked?'Deadline reminders on':'Deadline reminders off')})(this)"><span class="toggle-slider"></span></label>
        </div>
        <div class="setting-row">
          <div class="setting-info"><h4>Announcements</h4><p>Notify on new course announcements</p></div>
          <label class="toggle"><input type="checkbox" ${annNotifs?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_stu_prefs')||'{}');p.annNotifs=el.checked;localStorage.setItem('lf_stu_prefs',JSON.stringify(p));showToast(el.checked?'Announcement notifications on':'Announcement notifications off')})(this)"><span class="toggle-slider"></span></label>
        </div>
        <div class="setting-row">
          <div class="setting-info"><h4>Discussion Replies</h4><p>Notify when someone replies to your post</p></div>
          <label class="toggle"><input type="checkbox" ${discussNotifs?'checked':''} onchange="(function(el){const p=JSON.parse(localStorage.getItem('lf_stu_prefs')||'{}');p.discussNotifs=el.checked;localStorage.setItem('lf_stu_prefs',JSON.stringify(p));showToast(el.checked?'Discussion notifications on':'Discussion notifications off')})(this)"><span class="toggle-slider"></span></label>
        </div>
      </div>

      <div class="card">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Appearance</h3>
        <div class="setting-row">
          <div class="setting-info"><h4>Dark Mode</h4><p>Use dark theme throughout</p></div>
          <label class="toggle"><input type="checkbox" id="stuDarkToggle" ${darkOn?'checked':''} onchange="toggleDark()"><span class="toggle-slider"></span></label>
        </div>
      </div>
    </div>
  </div>`;
}

function stuSaveProfile() {
  const name  = (document.getElementById('stuProfName')?.value  || '').trim();
  const email = (document.getElementById('stuProfEmail')?.value || '').trim();
  if (!name || !email) { showToast('Name and email are required'); return; }
  const fd = new FormData();
  fd.append('action','update_profile'); fd.append('full_name',name); fd.append('email',email);
  fetch(window.location.pathname, { method:'POST', body:fd })
    .then(r => r.json()).then(d => showToast(d.success ? (d.message||'Profile updated!') : (d.message||'Save failed')))
    .catch(() => showToast('Profile updated!'));
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

function doLogout() {
  logout();
}

// ===== SUBMIT FILE HELPERS =====
let submitAttachedFiles = [];
function handleSubmitFiles(input) {
  Array.from(input.files).forEach(f => {
    if (!submitAttachedFiles.find(x => x.name === f.name)) submitAttachedFiles.push(f);
  });
  renderSubmitFileList();
}
function handleSubmitDrop(e) {
  e.preventDefault();
  e.currentTarget.style.borderColor = '';
  Array.from(e.dataTransfer.files).forEach(f => {
    if (!submitAttachedFiles.find(x => x.name === f.name)) submitAttachedFiles.push(f);
  });
  renderSubmitFileList();
}
function renderSubmitFileList() {
  const list = document.getElementById('submitFileList');
  if (!list) return;
  if (submitAttachedFiles.length === 0) { list.innerHTML = ''; return; }
  list.innerHTML = submitAttachedFiles.map((f, i) => {
    const size = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + 'MB' : (f.size / 1024).toFixed(0) + 'KB';
    const ext = f.name.split('.').pop().toUpperCase();
    return `<div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--surface-2);border:1.5px solid var(--border);border-radius:var(--radius-sm)">
      <span style="font-size:18px">${ext==='PDF'?'📄':ext==='ZIP'?'🗜':'📎'}</span>
      <div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${f.name}</div><div style="font-size:11px;color:var(--text-3)">${size}</div></div>
      <button onclick="removeSubmitFile(${i})" style="background:none;border:none;cursor:pointer;color:var(--text-3);font-size:16px;padding:2px 6px;border-radius:6px" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-3)'">✕</button>
    </div>`;
  }).join('');
}
function removeSubmitFile(i) { submitAttachedFiles.splice(i, 1); renderSubmitFileList(); }
function doSubmitAssignment() {
  if (submitAttachedFiles.length === 0) { showToast('Please attach at least one file', 'error'); return; }
  const remarks   = document.getElementById('submitRemarks');
  const asgId     = parseInt(document.getElementById('submitModal').dataset.asgId || '0');
  const remarksTxt = remarks ? remarks.value.trim() : '';
  closeModal('submitModal');
  submitAttachedFiles = [];
  renderSubmitFileList();
  if (remarks) remarks.value = '';

  // Update local data immediately
  const asg = _asgData.find(a => a.id === asgId);
  if (asg) { asg.status = 'submitted'; filterAssignments(); }
  showToast('Assignment submitted! 🎉', 'success');

  // Persist to DB
  const fd = new FormData();
  fd.append('action', 'submit_assignment');
  fd.append('assignment_id', asgId);
  fd.append('remarks', remarksTxt);
  fetch(location.href, { method:'POST', body:fd })
    .then(r => r.json())
    .catch(() => {});
}

// ===== PHOTO CHANGE =====
function _updateAllAvatars(url) {
  const imgHtml = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:9px" alt="">`;
  const topEl = document.getElementById('topbarUserAvatar');
  if (topEl) topEl.innerHTML = imgHtml;
  const sbEl = document.getElementById('sidebarUserAvatar');
  if (sbEl) sbEl.innerHTML = imgHtml;
  // Settings page preview (only present when on settings page)
  const img = document.getElementById('stuAvatarImg');
  const initEl = document.getElementById('stuAvatarInitials');
  if (img) { img.src = url; img.style.display = 'block'; }
  if (initEl) initEl.style.display = 'none';
}

function previewStuPhoto(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  if (!file.type.startsWith('image/')) { showToast('Please select an image file', 'error'); return; }
  // Show local preview immediately across all avatar instances
  const reader = new FileReader();
  reader.onload = e => { _updateAllAvatars(e.target.result); };
  reader.readAsDataURL(file);
  // Upload to server and replace data URL with permanent server URL
  const fd = new FormData();
  fd.append('action', 'upload_photo');
  fd.append('photo', file);
  fetch(window.location.pathname, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.avatar_url) {
        _updateAllAvatars(data.avatar_url);
        showToast('Photo updated!', 'success');
      } else {
        showToast(data.message || 'Photo upload failed', 'error');
      }
    })
    .catch(() => {
      showToast('Photo updated locally — sync failed', 'warning');
    });
}

// reset files when submit modal opens
const _origOpenModal = openModal;
openModal = function(id) {
  if (id === 'submitModal') {
    submitAttachedFiles = [];
    const list = document.getElementById('submitFileList');
    if (list) list.innerHTML = '';
    const remarks = document.getElementById('submitRemarks');
    if (remarks) remarks.value = '';
  }
  _origOpenModal(id);
};

</script>

<!-- ===== LEARNFLOW DEBUG PANEL ===== -->
<!-- Open your browser DevTools console and type:  showDebug()  -->
<div id="lfDebugPanel" style="display:none;position:fixed;bottom:0;left:0;right:0;max-height:60vh;overflow:auto;background:#0d1117;color:#e6edf3;font-family:monospace;font-size:12px;z-index:99999;border-top:2px solid #f85149;padding:16px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <strong style="color:#f85149;font-size:14px">🔍 LearnFlow Debug Panel</strong>
    <button onclick="document.getElementById('lfDebugPanel').style.display='none'" style="background:#f85149;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer">✕ Close</button>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div>
      <div style="color:#79c0ff;font-weight:700;margin-bottom:6px">📦 Enrolled section IDs (from DB)</div>
      <pre style="background:#161b22;padding:8px;border-radius:4px;margin:0"><?php echo htmlspecialchars(json_encode($enrolled_section_ids, JSON_PRETTY_PRINT)); ?></pre>

      <div style="color:#79c0ff;font-weight:700;margin:10px 0 6px">📝 Assignment data (<?php echo count($js_asg_data); ?> rows)</div>
      <?php if ($_debug_asg_error): ?>
        <pre style="background:#3d1c1c;color:#f85149;padding:8px;border-radius:4px;margin:0">SQL ERROR: <?php echo htmlspecialchars($_debug_asg_error); ?></pre>
      <?php else: ?>
        <pre style="background:#161b22;padding:8px;border-radius:4px;margin:0;max-height:200px;overflow:auto"><?php echo htmlspecialchars(json_encode($js_asg_data, JSON_PRETTY_PRINT)); ?></pre>
      <?php endif; ?>

      <div style="color:#79c0ff;font-weight:700;margin:10px 0 6px">🧪 Quiz data (<?php echo count($js_quiz_data); ?> rows)</div>
      <?php if ($_debug_quiz_error): ?>
        <pre style="background:#3d1c1c;color:#f85149;padding:8px;border-radius:4px;margin:0">SQL ERROR: <?php echo htmlspecialchars($_debug_quiz_error); ?></pre>
      <?php else: ?>
        <pre style="background:#161b22;padding:8px;border-radius:4px;margin:0;max-height:200px;overflow:auto"><?php echo htmlspecialchars(json_encode($js_quiz_data, JSON_PRETTY_PRINT)); ?></pre>
      <?php endif; ?>
    </div>
    <div>
      <div style="color:#79c0ff;font-weight:700;margin-bottom:6px">🗄️ Quizzes table columns</div>
      <pre style="background:#161b22;padding:8px;border-radius:4px;margin:0"><?php echo htmlspecialchars(json_encode($_debug_quiz_columns, JSON_PRETTY_PRINT)); ?></pre>

      <div style="color:#79c0ff;font-weight:700;margin:10px 0 6px">👤 Student user ID</div>
      <pre style="background:#161b22;padding:8px;border-radius:4px;margin:0"><?php echo htmlspecialchars((string)$student_user_id); ?></pre>

      <div style="color:#79c0ff;font-weight:700;margin:10px 0 6px">📚 studentCourses (JS — section IDs)</div>
      <pre id="dbgCourses" style="background:#161b22;padding:8px;border-radius:4px;margin:0"></pre>

      <div style="color:#79c0ff;font-weight:700;margin:10px 0 6px">🔎 _asgData section_ids vs _quizData section_ids</div>
      <pre id="dbgIds" style="background:#161b22;padding:8px;border-radius:4px;margin:0"></pre>
    </div>
  </div>
</div>
<script>
function showDebug() {
  const p = document.getElementById('lfDebugPanel');
  p.style.display = 'block';
  document.getElementById('dbgCourses').textContent =
    JSON.stringify(studentCourses.map(c=>({id:c.id, code:c.code, title:c.title})), null, 2);
  document.getElementById('dbgIds').textContent =
    'Assignments section_ids: ' + JSON.stringify([...new Set(_asgData.map(a=>a.section_id))]) +
    '\nQuizzes section_ids:     ' + JSON.stringify([...new Set(_quizData.map(q=>q.section_id))]) +
    '\nCourse ids:              ' + JSON.stringify(studentCourses.map(c=>c.id));
}
// Auto-show if no data
window.addEventListener('DOMContentLoaded', () => {
  if (_asgData.length === 0 && _quizData.length === 0) {
    setTimeout(showDebug, 1000);
  }
});
</script>
</body>
</html>