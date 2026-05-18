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
try {
    $th_stmt = $pdo->query("SELECT * FROM theme_settings WHERE id=1 LIMIT 1");
    $db_theme = $th_stmt ? $th_stmt->fetch() : null;
} catch(Exception $e) { $db_theme = null; }

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
  --primary-light: #FCEDF3;
  --primary-dark: #9e1f47;
  --primary-glow: rgba(196,48,94,0.12);
  --secondary: #3A9FD8;
  --secondary-light: #D0EDFB;
  --accent: #D4820A;
  --danger: #D03030;
  --purple: #3A9FD8;
  --bg: #FAF5F8;
  --surface: #FFFFFF;
  --surface-2: #F4EBF1;
  --surface-3: #EEE0EB;
  --border: #EAD5E2;
  --border-strong: #D8BECE;
  --text: #1E0A14;
  --text-2: #6B3050;
  --text-3: #B888A0;
  --shadow: 0 1px 4px rgba(196,48,94,0.06), 0 4px 16px rgba(196,48,94,0.07);
  --shadow-md: 0 4px 8px rgba(196,48,94,0.08), 0 12px 36px rgba(196,48,94,0.14);
  --shadow-float: 0 8px 16px rgba(196,48,94,0.1), 0 24px 56px rgba(196,48,94,0.15);
  --radius: 14px;
  --radius-sm: 9px;
  --radius-xs: 6px;
  --sidebar-w: 248px;
  --topbar-h: 62px;
}
[data-theme="dark"] {
  --primary: #E05A88;
  --primary-light: #2a1822;
  --primary-dark: #b83060;
  --primary-glow: rgba(224,90,136,0.15);
  --secondary: #58B2E0;
  --accent: #E0A040;
  --danger: #E05858;
  --purple: #58B2E0;
  --bg: #130d10;
  --surface: #1c1419;
  --surface-2: #241a20;
  --surface-3: #2d2028;
  --border: #3a2530;
  --border-strong: #4d3040;
  --text: #f0e8ec;
  --text-2: #a07888;
  --text-3: #5a3848;
  --shadow: 0 2px 8px rgba(0,0,0,0.4), 0 8px 24px rgba(0,0,0,0.3);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.5), 0 16px 40px rgba(0,0,0,0.35);
  --shadow-float: 0 8px 24px rgba(0,0,0,0.6), 0 24px 64px rgba(0,0,0,0.4);
}

*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.6;transition:background .3s,color .3s}
button{cursor:pointer;font-family:inherit;border:none;outline:none}
input,textarea,select{font-family:inherit;outline:none}
a{text-decoration:none;color:inherit}

/* ===== APP SHELL ===== */
.app-shell{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{
  width:var(--sidebar-w);min-height:100vh;background:var(--surface);
  border-right:1px solid var(--border);display:flex;flex-direction:column;
  position:fixed;left:0;top:0;z-index:100;transition:.3s cubic-bezier(.4,0,.2,1);overflow-y:auto;overflow-x:hidden;
}
.sidebar.collapsed{width:64px}
.sidebar-header{
  padding:20px 16px 16px;display:flex;align-items:center;gap:11px;
  border-bottom:1px solid var(--border);
}
.sidebar-brand{
  font-family:'Syne',sans-serif;font-size:18px;font-weight:800;
  white-space:nowrap;overflow:hidden;transition:.3s;
}
.sidebar.collapsed .sidebar-brand,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}
.sidebar.collapsed .sidebar-brand{display:none}
.sidebar.collapsed .sidebar-summary{display:none}

.nav-item{
  display:flex;align-items:center;gap:12px;padding:9px 14px;cursor:pointer;
  transition:all .15s ease;color:var(--text-2);position:relative;margin:1px 10px;
  border-radius:var(--radius-sm);white-space:nowrap;
}
.nav-item:hover{background:var(--surface-2);color:var(--text)}
.nav-item.active{background:var(--primary-glow);color:var(--primary);font-weight:600}
.nav-item.active::before{
  content:'';position:absolute;left:-10px;top:50%;transform:translateY(-50%);
  width:3px;height:56%;background:var(--primary);border-radius:0 3px 3px 0;
}
.nav-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center;opacity:.85}
.nav-item.active .nav-icon{opacity:1}
.nav-label{font-size:13px;font-weight:500;transition:.3s}
.nav-badge{
  margin-left:auto;background:var(--primary);color:#fff;
  font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;
  min-width:20px;text-align:center;
}

.sidebar-footer{margin-top:auto;padding:14px;border-top:1px solid var(--border)}
.user-card{display:flex;align-items:center;gap:10px;padding:8px 6px;border-radius:var(--radius-sm);cursor:pointer;transition:.15s}
.user-card:hover{background:var(--surface-2)}
.user-avatar{
  width:34px;height:34px;border-radius:9px;
  background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:12px;flex-shrink:0;letter-spacing:.5px;
}
.user-info{overflow:hidden}
.user-name{font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-role{font-size:11px;color:var(--text-3)}
.sidebar.collapsed .user-info{display:none}

/* ===== TOPBAR ===== */
.topbar{
  height:var(--topbar-h);background:var(--surface);border-bottom:1px solid var(--border);
  display:flex;align-items:center;padding:0 22px;gap:12px;
  position:sticky;top:0;z-index:50;
}
.topbar-left{display:flex;align-items:center;gap:12px;flex:1}
.topbar-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--text)}
.topbar-right{display:flex;align-items:center;gap:6px}
.icon-btn{
  width:36px;height:36px;border-radius:var(--radius-sm);background:var(--surface-2);
  display:flex;align-items:center;justify-content:center;font-size:16px;
  cursor:pointer;border:1px solid var(--border);transition:all .2s ease;color:var(--text-2);
  position:relative;
}
.icon-btn:hover{background:var(--primary-light);color:var(--primary);border-color:var(--border-strong);transform:translateY(-1px);box-shadow:var(--shadow)}
.notif-dot{
  position:absolute;top:6px;right:6px;width:7px;height:7px;
  border-radius:50%;background:var(--primary);border:1.5px solid var(--surface);
  pointer-events:none;
}

/* ===== MAIN CONTENT ===== */
.main-content{margin-left:var(--sidebar-w);flex:1;transition:.3s;min-height:100vh;display:flex;flex-direction:column}
.main-content.expanded{margin-left:64px}
.content-area{padding:24px;flex:1}

/* ===== BASE COMPONENTS ===== */
.page-header{margin-bottom:28px}
.page-header h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;line-height:1.2}
.page-header p{color:var(--text-2);font-size:13px;margin-top:4px}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-3);margin-bottom:8px;text-transform:uppercase;letter-spacing:.6px;font-weight:600}
.breadcrumb span:last-child{color:var(--text-2)}

.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow)}

/* STAT CARDS */
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
.stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.stat-icon.pink{background:rgba(196,48,94,0.12);color:var(--primary-dark)}
.stat-icon.amber{background:rgba(212,130,10,0.12);color:#9a5800}
.stat-icon.blue{background:rgba(58,159,216,0.12);color:#1a72a8}
.stat-icon.red{background:rgba(208,48,48,0.12);color:#a01818}
[data-theme="dark"] .stat-icon.pink{color:var(--primary)}
[data-theme="dark"] .stat-icon.amber{color:var(--accent)}
[data-theme="dark"] .stat-icon.blue{color:var(--secondary)}
[data-theme="dark"] .stat-icon.red{color:var(--danger)}
.stat-val{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;line-height:1}
.stat-label{font-size:12px;color:var(--text-2);margin-top:4px;font-weight:500}
.stat-trend{font-size:11px;margin-top:5px;font-weight:600}
.stat-trend.up{color:#1a72a8}
.stat-trend.down{color:var(--danger)}
[data-theme="dark"] .stat-trend.up{color:var(--secondary)}

/* GRID */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}

/* COURSE GRID */
.course-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:16px}
.course-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  overflow:hidden;cursor:pointer;transition:all .22s ease;box-shadow:var(--shadow);
}
.course-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-float);border-color:var(--border-strong)}
.course-thumb{height:132px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:50px}
.course-badge{
  position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.35);
  color:#fff;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
  backdrop-filter:blur(6px);letter-spacing:.4px;
}
.bg-pink{background:linear-gradient(135deg,#C4305E 0%,#8A1840 100%)}
.bg-blue{background:linear-gradient(135deg,#3A9FD8 0%,#175f8a 100%)}
.bg-amber{background:linear-gradient(135deg,#D4820A 0%,#8A4800 100%)}
.bg-purple{background:linear-gradient(135deg,#7C4FD8,#C4305E)}
.bg-teal{background:linear-gradient(135deg,#0EA898,#3A9FD8)}
.bg-rose{background:linear-gradient(135deg,#E83050,#C4305E)}
.course-body{padding:16px 18px}
.course-title{font-weight:700;font-size:14px;margin-bottom:8px;line-height:1.4;letter-spacing:-.1px}
.course-meta{display:flex;align-items:center;gap:10px;color:var(--text-3);font-size:11px;font-weight:500}
.progress-bar{height:4px;border-radius:3px;background:var(--surface-3);overflow:hidden}
.progress-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--primary),var(--primary-dark));transition:.8s cubic-bezier(.4,0,.2,1)}
.progress-label{display:flex;justify-content:space-between;font-size:11px;color:var(--text-2);margin:10px 0 5px;font-weight:600}
.course-footer{padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}

/* BADGES */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.1px}
.badge-pink{background:rgba(196,48,94,0.11);color:var(--primary-dark)}
.badge-blue{background:rgba(58,159,216,0.12);color:#1260a0}
.badge-green{background:rgba(58,159,216,0.12);color:#1260a0}
.badge-amber{background:rgba(212,130,10,0.12);color:#8a5000}
.badge-red{background:rgba(208,48,48,0.12);color:#900000}
.badge-gray{background:var(--surface-2);color:var(--text-2)}
.badge-archived{background:var(--surface-2);color:var(--text-3);border:1px dashed var(--border)}
[data-theme="dark"] .badge-pink{color:var(--primary)}
[data-theme="dark"] .badge-blue,[data-theme="dark"] .badge-green{color:var(--secondary)}
[data-theme="dark"] .badge-amber{color:var(--accent)}
[data-theme="dark"] .badge-red{color:var(--danger)}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 18px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;transition:all .2s ease;cursor:pointer;letter-spacing:.1px}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 2px 8px rgba(196,48,94,0.28)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(196,48,94,0.4)}
.btn-primary:active{transform:translateY(0);box-shadow:0 1px 4px rgba(196,48,94,0.2)}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text-2)}
.btn-outline:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.btn-ghost{background:transparent;color:var(--text-2);padding:8px 12px}
.btn-ghost:hover{background:var(--surface-2);color:var(--text)}
.btn-danger{background:rgba(208,48,48,0.1);color:var(--danger);border:none}
.btn-danger:hover{background:rgba(208,48,48,0.16)}
.btn-success{background:linear-gradient(135deg,#3A9FD8,#175f8a);color:#fff}
.btn-sm{padding:6px 13px;font-size:12px}
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

/* QUIZ */
.quiz-option{
  display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:var(--radius-sm);
  border:1.5px solid var(--border);cursor:pointer;transition:all .15s ease;font-size:13px;margin-bottom:8px;
}
.quiz-option:hover{border-color:var(--primary);background:var(--primary-light);transform:translateX(2px)}
.quiz-option.selected{border-color:var(--primary);background:var(--primary-light);color:var(--primary);font-weight:600}
.quiz-option.correct{border-color:var(--secondary);background:rgba(58,159,216,0.1);color:#1260a0}
.quiz-option.wrong{border-color:var(--danger);background:rgba(208,48,48,0.07);color:var(--danger)}
.option-circle{
  width:22px;height:22px;border-radius:50%;border:2px solid var(--border-strong);
  display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;
  flex-shrink:0;transition:.15s;
}
.quiz-option.selected .option-circle{border-color:var(--primary);background:var(--primary);color:#fff}
[data-theme="dark"] .quiz-option.correct{color:var(--secondary)}

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

/* DISCUSSION */
.discussion-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:12px;cursor:pointer;transition:all .2s;box-shadow:var(--shadow)}
.discussion-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);border-color:var(--border-strong)}
.discussion-reply{background:var(--surface-2);border-radius:var(--radius-sm);padding:14px;margin-top:10px;border-left:3px solid var(--primary)}
.discussions-container{display:grid;grid-template-columns:320px 1fr;gap:22px;margin-bottom:20px}
.discussions-list-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden}
.discussions-header{padding:16px;border-bottom:1px solid var(--border)}
.discussions-search{width:100%;padding:10px 14px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;font-family:inherit;transition:.2s}
.discussions-search:focus{outline:none;border-color:var(--primary)}
.discussions-list{flex:1;overflow-y:auto;padding:10px}
.discussions-list::-webkit-scrollbar{width:6px}
.discussions-list::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.discussion-item{padding:14px;border-radius:var(--radius-sm);background:var(--surface-2);border:1.5px solid transparent;cursor:pointer;transition:.2s;margin-bottom:10px}
.discussion-item:hover{background:var(--primary-light);border-color:var(--primary)}
.discussion-item.active{background:var(--primary-light);border-color:var(--primary)}
.discussion-item-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.discussion-item-meta{font-size:12px;color:var(--text-2);display:flex;justify-content:space-between;align-items:center;gap:8px}
.discussion-count{display:inline-flex;align-items:center;gap:4px;background:var(--primary-light);color:var(--primary);padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700}
.new-disc-btn{margin:12px;border-radius:var(--radius-sm);background:var(--primary);color:#fff;font-size:13px;font-weight:600;text-align:center;cursor:pointer;transition:.2s;border:none;padding:12px}
.new-disc-btn:hover{background:var(--primary-dark)}
.discussion-detail-panel{background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden}
.discussion-detail-header{padding:20px;border-bottom:1px solid var(--border)}
.discussion-detail-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:8px}
.discussion-detail-meta{display:flex;align-items:center;gap:16px;font-size:13px;color:var(--text-2);flex-wrap:wrap}
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
  position:absolute;top:calc(100% + 8px);right:0;width:318px;background:var(--surface);
  border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-float);
  z-index:200;display:none;
}
.notif-panel.open{display:block;animation:fadeUp .18s ease}
.notif-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.notif-header h3{font-size:14px;font-weight:700}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);cursor:pointer;transition:.15s}
.notif-item:hover{background:var(--surface-2)}
.notif-item.unread{background:var(--primary-light)}
.notif-item:last-child{border-bottom:none}
.notif-text{font-size:12px;line-height:1.5}
.notif-time{font-size:10px;color:var(--text-3);margin-top:2px;font-weight:500}
.auth-link{color:var(--primary);font-weight:700;cursor:pointer;font-size:12px}
.auth-link:hover{text-decoration:underline}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:400;display:none;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)}
.modal-overlay.open{display:flex;animation:fadeIn .2s ease}
.modal{background:var(--surface);border-radius:18px;padding:28px;width:100%;max-width:500px;box-shadow:var(--shadow-float);animation:fadeUp .25s ease;max-height:90vh;overflow-y:auto;border:1px solid var(--border)}
.modal-lg{max-width:640px}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.modal-header h2{font-family:'Fraunces',serif;font-size:18px;font-weight:700;letter-spacing:-.2px}
.modal-footer{display:flex;justify-content:flex-end;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)}


/* TOAST */
.toast{
  position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);
  background:var(--text);color:#fff;padding:11px 20px;border-radius:50px;
  font-size:13px;font-weight:600;box-shadow:0 8px 28px rgba(0,0,0,0.2);
  transition:transform .28s cubic-bezier(.4,0,.2,1),opacity .28s ease;opacity:0;pointer-events:none;
  white-space:nowrap;z-index:999;letter-spacing:.1px;
}
.toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.toast.success{background:var(--primary)}
.toast.warning{background:var(--accent)}
.toast.error{background:var(--danger)}

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

  <!-- SIDEBAR OVERLAY (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- ===== SIDEBAR ===== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <img src="data:image/png;base64,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" style="width:34px;height:34px;border-radius:10px;object-fit:cover;flex-shrink:0">
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
      <div class="user-card">
        <div class="user-avatar" id="sidebarUserAvatar"><?php if ($student_avatar_url): ?><img src="<?php echo htmlspecialchars($student_avatar_url); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:9px" alt=""><?php else: ?><?php echo htmlspecialchars($initials); ?><?php endif; ?></div>
        <div class="user-info">
          <div class="user-name"><?php echo htmlspecialchars($student_name); ?></div>
          <div class="user-role">Student · BSIT 3-1</div>
        </div>
      </div>
      <button onclick="doLogout()" style="width:100%;margin-top:10px;padding:9px 14px;border-radius:var(--radius-sm);background:rgba(208,48,48,0.09);color:var(--danger);border:1.5px solid rgba(208,48,48,0.18);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:.2s;" onmouseover="this.style.background='rgba(208,48,48,0.16)'" onmouseout="this.style.background='rgba(208,48,48,0.09)'">
        <span style="display:flex;align-items:center;gap:7px"><?php echo str_replace(['width="16"','height="16"'],['width="14"','height="14"'],str_replace(['stroke-width="2"'],['stroke-width="2.2"'],'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>')); ?> Sign Out</span>
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