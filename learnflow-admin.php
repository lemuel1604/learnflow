<?php
ob_start();
session_start();

// ===== AUTH GUARD =====
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: learnflow-login.php');
    exit;
}

// ===== DB + AUTH =====
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/auth.php';

// ===== AJAX HANDLER (must come before any HTML output) =====
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');

    $action   = $_POST['action'] ?? $_GET['action'] ?? '';
    $admin_id = (int)($_SESSION['user_id'] ?? 0);

    // ── ADMIN DASHBOARD / ANALYTICS / NOTIFICATIONS (DB-backed) ─────────────
    // These are used by the admin frontend to replace the current hardcoded/demo UI.
    if ($action === 'admin_dashboard_kpis') {
        // Total users
        $rUsers = $conn->query("SELECT COUNT(*) AS cnt FROM users");
        $usersTotal = $rUsers ? (int)$rUsers->fetch_assoc()['cnt'] : 0;

        // Users by role
        $usersRoleMap = ['student'=>0,'instructor'=>0,'admin'=>0];
        $rRole = $conn->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role");
        if ($rRole) {
            while ($row = $rRole->fetch_assoc()) {
                if (isset($usersRoleMap[$row['role']])) $usersRoleMap[$row['role']] = (int)$row['cnt'];
            }
        }

        // Active courses
        $rCourses = $conn->query("SELECT COUNT(*) AS cnt FROM courses WHERE status='published'");
        $coursesActive = $rCourses ? (int)$rCourses->fetch_assoc()['cnt'] : 0;

        // Total enrollments
        $rEnroll = $conn->query("SELECT COUNT(*) AS cnt FROM enrollments");
        $enrollTotal = $rEnroll ? (int)$rEnroll->fetch_assoc()['cnt'] : 0;

        // Total departments
        $rDepts = $conn->query("SELECT COUNT(*) AS cnt FROM departments");
        $deptsTotal = $rDepts ? (int)$rDepts->fetch_assoc()['cnt'] : 0;

        if (ob_get_level()) ob_clean();
        echo json_encode([
            'success'    => true,
            'kpis' => [
                'usersTotal'   => $usersTotal,
                'usersByRole'  => $usersRoleMap,
                'coursesActive'=> $coursesActive,
                'enrollTotal'  => $enrollTotal,
                'deptsTotal'   => $deptsTotal,
            ],
        ]);
        exit;
    }

    if ($action === 'admin_notifications') {
        // Build admin notifications from audit_logs and announcements.
        // 1) Recent admin/system events (last 48h)
        $since = date('Y-m-d H:i:s', strtotime('-48 hours'));

        $notif = [];

        $evRes = $conn->query("
            SELECT action, COUNT(*) AS cnt, MAX(created_at) AS last_at
            FROM audit_logs
            WHERE created_at >= '{$since}'
            GROUP BY action
            ORDER BY last_at DESC
            LIMIT 8
        ");

        if ($evRes) {
            while ($r = $evRes->fetch_assoc()) {
                $actionName = $r['action'];
                $cnt = (int)$r['cnt'];
                $lastAt = $r['last_at'];

                // Map action to user-friendly icon + category + importance
                $type = 'info';
                if (strpos($actionName, 'failed') !== false || strpos($actionName, 'abuse') !== false) $type = 'warn';
                if (strpos($actionName, 'error') !== false) $type = 'error';

                $label = 'Activity';
                if ($actionName === 'user_registered') $label = 'New Registrations';
                if ($actionName === 'enrollment_created') $label = 'New Enrollment(s)';
                if ($actionName === 'assignment_created') $label = 'New Assignment Posted';
                if ($actionName === 'course_updated') $label = 'Course Updated';
                if ($actionName === 'magic_link_login') $label = 'Magic Link Logins';

                $notif[] = [
                    'id' => md5($actionName.'_'.$since),
                    'icon' => '🔔',
                    'title' => $label,
                    'message' => $cnt . ' event(s) for ' . $actionName,
                    'created_at' => $lastAt,
                    'category' => 'audit',
                    'priority' => $type,
                    'is_unread' => true
                ];
            }
        }

        // 2) Announcements that are scheduled but not yet published
        $annRes = $conn->query("
            SELECT id, title, published_at, created_at, author_id
            FROM announcements
            WHERE published_at IS NOT NULL
              AND published_at > NOW()
            ORDER BY published_at ASC
            LIMIT 5
        ");
        if ($annRes) {
            while ($r = $annRes->fetch_assoc()) {
                $notif[] = [
                    'id' => 'ann_'.$r['id'],
                    'icon' => '📢',
                    'title' => 'Scheduled Announcement',
                    'message' => $r['title'],
                    'created_at' => $r['published_at'],
                    'category' => 'announcements',
                    'priority' => 'info',
                    'is_unread' => true
                ];
            }
        }

        // Sort by created_at desc
        usort($notif, function($a,$b){
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });
        $notif = array_slice($notif, 0, 10);

        ob_clean();
        echo json_encode(['success'=>true,'notifications'=>$notif]);
        exit;
    }

    // ── RECENT ACTIVITY (audit_logs) ─────────────────────────
    if ($action === 'admin_recent_activity') {
        $res = $conn->query("
            SELECT
                al.action,
                al.entity_type,
                al.created_at,
                COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email, 'System') AS actor_name,
                u.role AS actor_role
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.user_id
            LEFT JOIN user_profiles up ON up.user_id = al.user_id
            ORDER BY al.created_at DESC
            LIMIT 8
        ");
        $items = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $act   = $r['action'];
                $actor = $r['actor_name'] ?: 'System';
                $role  = $r['actor_role'] ?: '';
                $type  = 'info';
                $title = ucwords(str_replace('_', ' ', $act));
                $desc  = $actor . ($role ? ' (' . $role . ')' : '');
                switch ($act) {
                    case 'user_registered':      $title='New User Registered';   $type='success'; $desc=$actor.' created an account'; break;
                    case 'enrollment_created':   $title='New Enrollment';         $type='info';    $desc=$actor.' enrolled in a course'; break;
                    case 'course_updated':       $title='Course Updated';         $type='info';    $desc='Course updated by '.$actor; break;
                    case 'course_created':       $title='Course Created';         $type='success'; $desc='New course by '.$actor; break;
                    case 'magic_link_login':     $title='User Login';             $type='info';    $desc=$actor.' signed in'; break;
                    case 'assignment_created':   $title='Assignment Posted';      $type='info';    $desc='New assignment by '.$actor; break;
                    case 'announcement_created': $title='Announcement Sent';      $type='primary'; $desc='Broadcast by '.$actor; break;
                }
                if (strpos($act,'failed') !== false) $type = 'warn';
                if (strpos($act,'delete') !== false) $type = 'warn';
                $items[] = ['title'=>$title,'desc'=>$desc,'type'=>$type,'created_at'=>$r['created_at']];
            }
        }
        ob_clean();
        echo json_encode(['success'=>true,'activity'=>$items]);
        exit;
    }

    // ── GET DEPARTMENTS ──────────────────────────────────────
    if ($action === 'get_departments') {
        // Ensure department names match the college values stored in the programs table.
        // This corrects any historical naming mismatches (e.g. "College of Business Administration"
        // vs. "College of Business and Accountancy") so the student_count subquery works correctly.
        $conn->query("
            UPDATE departments d
            JOIN (
                SELECT DISTINCT college FROM programs
            ) prog_colleges
                ON TRIM(LOWER(prog_colleges.college)) LIKE CONCAT('%', TRIM(LOWER(SUBSTRING_INDEX(d.name,' ',3))), '%')
            SET d.name = prog_colleges.college
            WHERE d.name <> prog_colleges.college
              AND LENGTH(prog_colleges.college) > 5
        ");

        $sql = "
            SELECT  d.id,
                    d.code,
                    d.name,
                    d.description,
                    d.created_at,
                    CONCAT(COALESCE(up.first_name,''),' ',COALESCE(up.last_name,'')) AS head_name,
                    (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.id AND c.status='published') AS course_count,
                    (SELECT COUNT(*) FROM instructor_profiles ip WHERE ip.department_id = d.id) AS faculty_count,
                    (SELECT COUNT(DISTINCT sp.user_id)
                     FROM student_profiles sp
                     JOIN programs prog ON prog.code = sp.program
                     JOIN users usr ON usr.id = sp.user_id
                     WHERE prog.college = d.name
                       AND usr.status != 'suspended') AS student_count
            FROM   departments d
            LEFT JOIN users u ON u.id = d.head_user_id
            LEFT JOIN user_profiles up ON up.user_id = d.head_user_id
            ORDER BY d.name ASC
        ";
        $result = $conn->query($sql);
        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = [
                'id'            => (int)$row['id'],
                'code'          => $row['code'],
                'name'          => $row['name'],
                'description'   => $row['description'] ?? '',
                'head'          => trim($row['head_name']) ?: '—',
                'course_count'  => (int)$row['course_count'],
                'faculty_count' => (int)$row['faculty_count'],
                'student_count' => (int)$row['student_count'],
                'created_at'    => date('M j, Y', strtotime($row['created_at'])),
            ];
        }
        ob_clean();
        echo json_encode(['success' => true, 'departments' => $departments]);
        exit;
    }

    // ── GET PROGRAMS ─────────────────────────────────────────
    if ($action === 'get_programs') {
        $result = $conn->query("SELECT id, code, name, college FROM programs ORDER BY college ASC, name ASC");
        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = [
                'id'      => (int)$row['id'],
                'code'    => $row['code'],
                'name'    => $row['name'],
                'college' => $row['college'],
            ];
        }
        ob_clean();
        echo json_encode(['success' => true, 'programs' => $programs]);
        exit;
    }

    // ── GET NEXT IDs ─────────────────────────────────────────
    if ($action === 'get_next_ids') {
        $year = date('y'); // e.g. "26"

        // Next student ID: find highest sequence for current year prefix
        $res = $conn->query("SELECT student_id FROM student_profiles WHERE student_id LIKE '{$year}-%' ORDER BY student_id DESC LIMIT 1");
        $nextStudent = $year . '-00001';
        if ($res && $row = $res->fetch_assoc()) {
            $parts = explode('-', $row['student_id']);
            $seq   = isset($parts[1]) ? (int)$parts[1] + 1 : 1;
            $nextStudent = $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
        }

        // Next employee ID: find highest EMP sequence
        $res2 = $conn->query("SELECT employee_id FROM instructor_profiles WHERE employee_id LIKE 'EMP-%' ORDER BY employee_id DESC LIMIT 1");
        $nextEmployee = 'EMP-0001';
        if ($res2 && $row2 = $res2->fetch_assoc()) {
            $parts2 = explode('-', $row2['employee_id']);
            $seq2   = isset($parts2[1]) ? (int)$parts2[1] + 1 : 1;
            $nextEmployee = 'EMP-' . str_pad($seq2, 4, '0', STR_PAD_LEFT);
        }

        ob_clean();
        echo json_encode(['success' => true, 'student_id' => $nextStudent, 'employee_id' => $nextEmployee]);
        exit;
    }

    // ── GET DEPT DETAIL (courses + faculty) ──────────────────
    if ($action === 'get_dept_detail') {
        $dept_id = (int)($_GET['dept_id'] ?? $_POST['dept_id'] ?? 0);
        if (!$dept_id) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Missing dept_id']); exit; }

        $cstmt = $conn->prepare("
            SELECT id, code, title, status FROM courses WHERE department_id=? ORDER BY code ASC LIMIT 50
        ");
        $cstmt->bind_param('i', $dept_id);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        $courses = [];
        while ($r = $cres->fetch_assoc()) $courses[] = $r;
        $cstmt->close();

        $fstmt = $conn->prepare("
            SELECT up.first_name, up.last_name, ip.designation, ip.employee_id
            FROM   instructor_profiles ip
            JOIN   user_profiles up ON up.user_id = ip.user_id
            JOIN   users u ON u.id = ip.user_id
            WHERE  ip.department_id = ?
            ORDER  BY up.last_name ASC LIMIT 50
        ");
        $fstmt->bind_param('i', $dept_id);
        $fstmt->execute();
        $fres = $fstmt->get_result();
        $faculty = [];
        while ($r = $fres->fetch_assoc()) {
            $faculty[] = [
                'name'        => trim($r['first_name'].' '.$r['last_name']),
                'designation' => $r['designation'] ?: 'Instructor',
                'employee_id' => $r['employee_id'],
            ];
        }
        $fstmt->close();

        ob_clean();
        echo json_encode(['success'=>true, 'courses'=>$courses, 'faculty'=>$faculty]);
        exit;
    }

    // ── GET DEPT STUDENTS ─────────────────────────────────────
    if ($action === 'get_dept_students') {
        $dept_id = (int)($_GET['dept_id'] ?? 0);
        if (!$dept_id) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Missing dept_id']); exit; }

        // Get the college name for this department
        $dRow = $conn->query("SELECT name FROM departments WHERE id=$dept_id LIMIT 1");
        if (!$dRow || !($dept = $dRow->fetch_assoc())) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'College not found']); exit;
        }
        $collegeName = $conn->real_escape_string($dept['name']);

        $sstmt = $conn->prepare("
            SELECT  up.first_name, up.last_name, up.avatar_url,
                    sp.student_id, sp.program, sp.year_level, sp.section,
                    u.status
            FROM    student_profiles sp
            JOIN    programs prog ON prog.code = sp.program
            JOIN    users u       ON u.id = sp.user_id
            LEFT JOIN user_profiles up ON up.user_id = sp.user_id
            WHERE   prog.college = ?
            ORDER BY up.last_name ASC, up.first_name ASC
            LIMIT 200
        ");
        $sstmt->bind_param('s', $dept['name']);
        $sstmt->execute();
        $sres = $sstmt->get_result();
        $students = [];
        while ($r = $sres->fetch_assoc()) {
            $students[] = [
                'name'       => trim(($r['first_name']??'').' '.($r['last_name']??'')),
                'avatar_url' => $r['avatar_url'] ?? '',
                'student_id' => $r['student_id'] ?? '',
                'program'    => $r['program'] ?? '',
                'year_level' => $r['year_level'] ? (int)$r['year_level'] : '',
                'section'    => $r['section'] ?? '',
                'status'     => $r['status'] ?? 'active',
            ];
        }
        $sstmt->close();
        ob_clean();
        echo json_encode(['success'=>true, 'students'=>$students]);
        exit;
    }

    // ── CREATE DEPARTMENT ─────────────────────────────────────
    if ($action === 'create_department') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $desc = trim($_POST['description'] ?? '');
        if (!$name || !$code) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Name and code are required']); exit; }
        $stmt = $conn->prepare("INSERT INTO departments (code, name, description) VALUES (?,?,?)");
        $stmt->bind_param('sss', $code, $name, $desc);
        $ok = $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();
        ob_clean();
        if ($ok) echo json_encode(['success'=>true,'id'=>$new_id,'message'=>"College \"$name\" created."]);
        else      echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
        exit;
    }

    // ── UPDATE DEPARTMENT ─────────────────────────────────────
    if ($action === 'update_department') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $desc = trim($_POST['description'] ?? '');
        if (!$id || !$name || !$code) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }
        $stmt = $conn->prepare("UPDATE departments SET code=?, name=?, description=? WHERE id=?");
        $stmt->bind_param('sssi', $code, $name, $desc, $id);
        $ok = $stmt->execute();
        $stmt->close();
        ob_clean();
        echo json_encode(['success'=>$ok,'message'=>$ok?"College updated.":'DB error: '.$conn->error]);
        exit;
    }

    // ── DELETE DEPARTMENT ─────────────────────────────────────
    if ($action === 'delete_department') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }
        $stmt = $conn->prepare("DELETE FROM departments WHERE id=?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        ob_clean();
        echo json_encode(['success'=>$ok,'message'=>$ok?'College deleted.':'DB error: '.$conn->error]);
        exit;
    }

    // ── GET USERS ────────────────────────────────────────────
    if ($action === 'get_users') {
        $sql = "
            SELECT  u.id,
                    up.first_name,
                    up.last_name,
                    up.display_name,
                    up.avatar_url,
                    u.email,
                    u.role,
                    u.status,
                    u.created_at,
                    u.last_login_at,
                    COALESCE(d.name, prog.college, '') AS dept_name,
                    sp.student_id,
                    sp.program,
                    sp.year_level,
                    sp.section,
                    ip.employee_id,
                    ip.department_id,
                    ip.designation
            FROM    users u
            LEFT JOIN user_profiles up       ON up.user_id = u.id
            LEFT JOIN instructor_profiles ip ON ip.user_id = u.id
            LEFT JOIN departments d          ON d.id = ip.department_id
            LEFT JOIN student_profiles sp    ON sp.user_id = u.id
            LEFT JOIN programs prog          ON prog.code  = sp.program
            ORDER BY u.created_at DESC
        ";
        $result = $conn->query($sql);
        $users  = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id'          => (int)$row['id'],
                'name'        => $row['display_name'] ?: trim($row['first_name'].' '.$row['last_name']),
                'first_name'  => $row['first_name'] ?? '',
                'last_name'   => $row['last_name'] ?? '',
                'email'       => $row['email'],
                'role'        => $row['role'],
                'status'      => $row['status'],
                'dept_name'   => $row['dept_name'] ?? '',
                'avatar_url'  => $row['avatar_url'] ?? '',
                'joined'      => date('M j, Y', strtotime($row['created_at'])),
                'lastActive'  => $row['last_login_at']
                                  ? date('M j, Y', strtotime($row['last_login_at']))
                                  : 'Never',
                'student_id'  => $row['student_id'] ?? '',
                'program'     => $row['program'] ?? '',
                'year_level'  => $row['year_level'] ? (int)$row['year_level'] : '',
                'section'     => $row['section'] ?? '',
                'employee_id' => $row['employee_id'] ?? '',
                'department_id' => $row['department_id'] ? (int)$row['department_id'] : '',
                'designation' => $row['designation'] ?? '',
            ];
        }
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }

    // ── UPLOAD ADMIN PHOTO ────────────────────────────────
    if ($action === 'upload_admin_photo') {
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']); exit;
        }
        $file    = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowed)) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, WebP, or GIF allowed.']); exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB.']); exit;
        }
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'avatar_' . $admin_id . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'Failed to save file.']); exit;
        }
        $avatarUrl = 'uploads/avatars/' . $filename;
        $stmt = $conn->prepare("UPDATE user_profiles SET avatar_url = ? WHERE user_id = ?");
        if ($stmt) { $stmt->bind_param('si', $avatarUrl, $admin_id); $stmt->execute(); $stmt->close(); }
        ob_clean(); echo json_encode(['success' => true, 'avatar_url' => $avatarUrl]); exit;
    }

    // ── CREATE SINGLE USER ───────────────────────────────────
    if ($action === 'create_user') {
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => trim($_POST['last_name']  ?? ''),
            'email'      => trim($_POST['email']      ?? ''),
            'role'       => trim($_POST['role']       ?? 'student'),
            // Generate a temporary password (not used, but required by register_user)
            'password'   => bin2hex(random_bytes(16)),
        ];

        if (!$data['first_name'] || !$data['last_name']) {
            echo json_encode(['success' => false, 'message' => 'First name and last name are required.']);
            exit;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid e-mail address.']);
            exit;
        }

        if ($data['role'] === 'student' && empty(trim($_POST['student_id'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required for student accounts.']);
            exit;
        }

        if ($data['role'] === 'instructor' && empty(trim($_POST['employee_id'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'Employee ID is required for instructor accounts.']);
            exit;
        }

        // Direct SQL insert — no stored procedure required
        $emailEsc = $conn->real_escape_string($data['email']);
        $dupCheck = $conn->query("SELECT id FROM users WHERE email='$emailEsc' LIMIT 1");
        if ($dupCheck && $dupCheck->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
            exit;
        }
        $passHash  = password_hash($data['password'], PASSWORD_BCRYPT);
        $roleEsc   = $conn->real_escape_string($data['role']);
        $statusEsc = $conn->real_escape_string(
            in_array(trim($_POST['status'] ?? ''), ['active','inactive','suspended','pending'], true)
                ? trim($_POST['status']) : 'pending'
        );
        $conn->query("INSERT INTO users (email, password_hash, role, status)
                      VALUES ('$emailEsc', '$passHash', '$roleEsc', '$statusEsc')");
        $uid = (int)$conn->insert_id;
        if (!$uid) {
            echo json_encode(['success' => false, 'message' => 'Failed to create user. Please try again.']);
            exit;
        }
        $fnEsc = $conn->real_escape_string($data['first_name']);
        $lnEsc = $conn->real_escape_string($data['last_name']);
        $conn->query("INSERT INTO user_profiles (user_id, first_name, last_name)
                      VALUES ($uid, '$fnEsc', '$lnEsc')");

        // Role-specific profile
        if ($data['role'] === 'student') {
            $sid   = $conn->real_escape_string($_POST['student_id'] ?? '');
            $prog  = $conn->real_escape_string($_POST['program']    ?? '');
            $yr    = (int)($_POST['year_level'] ?? 0);
            $sec   = $conn->real_escape_string($_POST['section']    ?? '');
            if ($sid) {
                $conn->query("INSERT IGNORE INTO student_profiles (user_id,student_id,program,year_level,section)
                              VALUES ($uid,'$sid','$prog',".($yr?:NULL).",'$sec')");
            }
        } elseif ($data['role'] === 'instructor') {
            $eid  = $conn->real_escape_string($_POST['employee_id']   ?? '');
            $dept = (int)($_POST['department_id'] ?? 0);
            $des  = $conn->real_escape_string($_POST['designation']   ?? '');
            if ($eid) {
                $conn->query("INSERT IGNORE INTO instructor_profiles (user_id,employee_id,department_id,designation)
                              VALUES ($uid,'$eid',".($dept?:NULL).",'$des')");
            }
        }

        // Send welcome email with sign-in link
        require_once __DIR__ . '/config/mail.php';
        $user_name   = trim($data['first_name'] . ' ' . $data['last_name']) ?: $data['email'];
        $token_result = create_magic_link_token($conn, $data['email']);
        $email_sent  = false;
        if ($token_result['success']) {
            $email_sent = send_welcome_email($data['email'], $token_result['token'], $user_name, $data['role']);
        }

        $msg = $email_sent
            ? "User created. A welcome email with sign-in link was sent to {$data['email']}."
            : "User created. Welcome email could not be sent to {$data['email']}.";
        echo json_encode(['success' => true, 'user_id' => $uid, 'message' => $msg]);
        exit;
    }

    // ── BULK CREATE USERS (from file import) ─────────────────
    if ($action === 'bulk_create_users') {
        $raw = $_POST['users'] ?? '[]';
        $list = json_decode($raw, true) ?: [];
        $created = 0; $failed = 0; $msgs = [];

        foreach ($list as $item) {
            $role = $item['role'] ?? 'student';
            if ($role === 'student' && empty(trim($item['student_id'] ?? ''))) {
                $failed++;
                $msgs[] = 'A student row is missing Student ID: ' . ($item['email'] ?? 'unknown email');
                continue;
            }
            if ($role === 'instructor' && empty(trim($item['employee_id'] ?? ''))) {
                $failed++;
                $msgs[] = 'An instructor row is missing Employee ID: ' . ($item['email'] ?? 'unknown email');
                continue;
            }
            // --- Direct SQL insert (no stored procedure required) ---
            $emailEsc = $conn->real_escape_string(trim($item['email'] ?? ''));
            if (empty($emailEsc)) {
                $failed++;
                $msgs[] = 'Missing email for a row.';
                continue;
            }
            $dupCheck = $conn->query("SELECT id FROM users WHERE email='$emailEsc' LIMIT 1");
            if ($dupCheck && $dupCheck->num_rows > 0) {
                $failed++;
                $msgs[] = "Email already exists: $emailEsc";
                continue;
            }
            $passHash  = password_hash($item['password'] ?? 'LearnFlow@2025', PASSWORD_BCRYPT);
            $roleEsc   = $conn->real_escape_string($role);
            $statusEsc = $conn->real_escape_string(
                in_array($item['status'] ?? '', ['active','inactive','suspended','pending'], true)
                    ? $item['status'] : 'pending'
            );
            $conn->query("INSERT INTO users (email, password_hash, role, status)
                          VALUES ('$emailEsc', '$passHash', '$roleEsc', '$statusEsc')");
            $uid = (int)$conn->insert_id;
            if (!$uid) {
                $failed++;
                $msgs[] = "DB insert failed for: $emailEsc";
                continue;
            }
            $fnEsc = $conn->real_escape_string(trim($item['first_name'] ?? ''));
            $lnEsc = $conn->real_escape_string(trim($item['last_name']  ?? ''));
            $conn->query("INSERT INTO user_profiles (user_id, first_name, last_name)
                          VALUES ($uid, '$fnEsc', '$lnEsc')");

            $role = $item['role'] ?? 'student';
            if ($role === 'student' && !empty($item['student_id'])) {
                $sid        = $conn->real_escape_string($item['student_id']);
                $program    = $conn->real_escape_string($item['program'] ?? '');
                $year_level = (int)($item['year_level'] ?? 0);
                $section    = $conn->real_escape_string($item['section'] ?? '');
                $conn->query("INSERT IGNORE INTO student_profiles (user_id,student_id,program,year_level,section) VALUES ($uid,'$sid','$program'," . ($year_level ?: 'NULL') . ", '$section')");
            } elseif ($role === 'instructor' && !empty($item['employee_id'])) {
                $eid          = $conn->real_escape_string($item['employee_id']);
                $designation  = $conn->real_escape_string($item['designation'] ?? '');

                // Accept either a numeric department_id or a department name/code string
                $deptRaw = $item['department_id'] ?? $item['department'] ?? '';
                if (is_numeric($deptRaw) && (int)$deptRaw > 0) {
                    $dept = (int)$deptRaw;
                } elseif (!empty($deptRaw)) {
                    $deptEsc = $conn->real_escape_string(trim($deptRaw));
                    $dRes = $conn->query("SELECT id FROM departments WHERE name='$deptEsc' OR code='$deptEsc' LIMIT 1");
                    $dept = ($dRes && $dRow = $dRes->fetch_assoc()) ? (int)$dRow['id'] : 0;
                } else {
                    $dept = 0;
                }

                $conn->query("INSERT IGNORE INTO instructor_profiles (user_id,employee_id,department_id,designation) VALUES ($uid,'$eid'," . ($dept ?: 'NULL') . ", '$designation')");
            }

            // Send welcome email — wrapped so a mail failure never aborts the import
            try {
                $mailFile = __DIR__ . '/config/mail.php';
                if (file_exists($mailFile)) {
                    require_once $mailFile;
                    $wName  = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')) ?: ($item['email'] ?? '');
                    $wToken = create_magic_link_token($conn, $item['email'] ?? '');
                    if (!empty($wToken['success'])) {
                        send_welcome_email($item['email'] ?? '', $wToken['token'], $wName, $item['role'] ?? 'student');
                    }
                }
            } catch (\Throwable $e) {
                // Mail failed — user is still created; log and continue
                error_log('bulk_create_users mail error: ' . $e->getMessage());
            }

            $created++;
        }
        echo json_encode(['success' => $created > 0, 'created' => $created, 'failed' => $failed,
                          'message' => $failed ? implode('; ', array_unique($msgs)) : 'Done.']);
        exit;
    }

    // ── UPDATE USER DETAILS ─────────────────────────────────
    if ($action === 'update_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid user selected.']);
            exit;
        }

        $userRes = $conn->query("SELECT * FROM users WHERE id=$user_id LIMIT 1");
        $user = $userRes ? $userRes->fetch_assoc() : null;
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        $email = trim($_POST['email'] ?? $user['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid e-mail address.']);
            exit;
        }

        $exists = $conn->query("SELECT id FROM users WHERE email='" . $conn->real_escape_string($email) . "' AND id<>$user_id LIMIT 1");
        if ($exists && $exists->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'This email is already used by another account.']);
            exit;
        }

        $role = trim($_POST['role'] ?? $user['role']);
        if (!in_array($role, ['admin', 'instructor', 'student'], true)) {
            $role = 'student';
        }

        $status = trim($_POST['status'] ?? $user['status']);
        if (!in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
            $status = $user['status'];
        }

        $profileRes = $conn->query("SELECT * FROM user_profiles WHERE user_id=$user_id LIMIT 1");
        $profile = $profileRes ? $profileRes->fetch_assoc() : [];

        $first_name = trim($_POST['first_name'] ?? ($profile['first_name'] ?? ''));
        $last_name = trim($_POST['last_name'] ?? ($profile['last_name'] ?? ''));
        if (!$first_name || !$last_name) {
            echo json_encode(['success' => false, 'message' => 'First name and last name are required.']);
            exit;
        }

        $student_id = trim($_POST['student_id'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');

        if ($role === 'student' && $student_id === '') {
            echo json_encode(['success' => false, 'message' => 'Student ID is required for student accounts.']);
            exit;
        }

        if ($role === 'instructor' && $employee_id === '') {
            echo json_encode(['success' => false, 'message' => 'Employee ID is required for instructor accounts.']);
            exit;
        }

        $conn->query("UPDATE users SET email='" . $conn->real_escape_string($email) . "', role='" . $conn->real_escape_string($role) . "', status='" . $conn->real_escape_string($status) . "' WHERE id=$user_id");

        if ($profile) {
            $conn->query("UPDATE user_profiles SET first_name='" . $conn->real_escape_string($first_name) . "', last_name='" . $conn->real_escape_string($last_name) . "' WHERE user_id=$user_id");
        } else {
            $conn->query("INSERT INTO user_profiles (user_id, first_name, last_name) VALUES ($user_id, '" . $conn->real_escape_string($first_name) . "', '" . $conn->real_escape_string($last_name) . "')");
        }

        if ($role === 'student') {
            $conn->query("DELETE FROM instructor_profiles WHERE user_id=$user_id");
            $program = $conn->real_escape_string(trim($_POST['program'] ?? ''));
            $year_level = (int)($_POST['year_level'] ?? 0);
            $section = $conn->real_escape_string(trim($_POST['section'] ?? ''));
            $studentExists = $conn->query("SELECT user_id FROM student_profiles WHERE user_id=$user_id LIMIT 1");
            if ($studentExists && $studentExists->num_rows > 0) {
                $conn->query("UPDATE student_profiles SET student_id='" . $conn->real_escape_string($student_id) . "', program='" . $program . "', year_level=" . ($year_level ?: 'NULL') . ", section='" . $section . "' WHERE user_id=$user_id");
            } else {
                $conn->query("INSERT INTO student_profiles (user_id, student_id, program, year_level, section) VALUES ($user_id, '" . $conn->real_escape_string($student_id) . "', '" . $program . "', " . ($year_level ?: 'NULL') . ", '" . $section . "')");
            }
        } elseif ($role === 'instructor') {
            $conn->query("DELETE FROM student_profiles WHERE user_id=$user_id");
            $department_id = (int)($_POST['department_id'] ?? 0);
            $designation = $conn->real_escape_string(trim($_POST['designation'] ?? ''));
            $instrExists = $conn->query("SELECT user_id FROM instructor_profiles WHERE user_id=$user_id LIMIT 1");
            if ($instrExists && $instrExists->num_rows > 0) {
                $conn->query("UPDATE instructor_profiles SET employee_id='" . $conn->real_escape_string($employee_id) . "', department_id=" . ($department_id ?: 'NULL') . ", designation='" . $designation . "' WHERE user_id=$user_id");
            } else {
                $conn->query("INSERT INTO instructor_profiles (user_id, employee_id, department_id, designation) VALUES ($user_id, '" . $conn->real_escape_string($employee_id) . "', " . ($department_id ?: 'NULL') . ", '" . $designation . "')");
            }
        } else {
            $conn->query("DELETE FROM student_profiles WHERE user_id=$user_id");
            $conn->query("DELETE FROM instructor_profiles WHERE user_id=$user_id");
        }

        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        exit;
    }

    // ── DELETE USER ───────────────────────────────────────
    if ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid user selected.']);
            exit;
        }

        // Admins cannot be deleted
        $roleCheck = $conn->query("SELECT role FROM users WHERE id=$user_id LIMIT 1");
        if ($roleCheck && ($roleRow = $roleCheck->fetch_assoc()) && $roleRow['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Admin accounts cannot be deleted.']);
            exit;
        }

        $conn->query("DELETE FROM student_profiles WHERE user_id=$user_id");
        $conn->query("DELETE FROM instructor_profiles WHERE user_id=$user_id");
        $conn->query("DELETE FROM user_profiles WHERE user_id=$user_id");
        $conn->query("DELETE FROM auth_tokens WHERE user_id=$user_id");
        $conn->query("DELETE FROM users WHERE id=$user_id");

        if ($conn->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to delete user.']);
        }
        exit;
    }

    // ── BULK USER ACTIONS ─────────────────────────────────
    if ($action === 'bulk_user_action') {
        $rawIds = $_POST['user_ids'] ?? '[]';
        $ids = json_decode($rawIds, true);
        if (!is_array($ids) || empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No users selected.']);
            exit;
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No valid users selected.']);
            exit;
        }

        $actionType = trim($_POST['bulk_action'] ?? '');
        $idList = implode(',', $ids);
        if (!$idList) {
            echo json_encode(['success' => false, 'message' => 'Invalid user list.']);
            exit;
        }

        if ($actionType === 'suspend') {
            // Exclude admin accounts from bulk suspension
            $conn->query("UPDATE users SET status='suspended' WHERE id IN ($idList) AND role <> 'admin'");
            echo json_encode(['success' => true, 'message' => 'Selected non-admin users suspended.']);
            exit;
        }

        if ($actionType === 'delete') {
            // Exclude admin accounts from bulk deletion
            $nonAdminRes = $conn->query("SELECT id FROM users WHERE id IN ($idList) AND role <> 'admin'");
            $nonAdminIds = [];
            while ($r = $nonAdminRes->fetch_assoc()) $nonAdminIds[] = (int)$r['id'];
            if (empty($nonAdminIds)) {
                echo json_encode(['success' => false, 'message' => 'Admin accounts cannot be deleted. No other users were selected.']);
                exit;
            }
            $safeList = implode(',', $nonAdminIds);
            $skipped  = count($ids) - count($nonAdminIds);
            $conn->query("DELETE FROM student_profiles WHERE user_id IN ($safeList)");
            $conn->query("DELETE FROM instructor_profiles WHERE user_id IN ($safeList)");
            $conn->query("DELETE FROM user_profiles WHERE user_id IN ($safeList)");
            $conn->query("DELETE FROM auth_tokens WHERE user_id IN ($safeList)");
            $conn->query("DELETE FROM users WHERE id IN ($safeList)");
            $msg = count($nonAdminIds) . ' user(s) deleted.';
            if ($skipped > 0) $msg .= " $skipped admin account(s) were skipped.";
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }

        if ($actionType === 'email') {
            require_once __DIR__ . '/config/mail.php';
            $result = $conn->query("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id WHERE u.id IN ($idList)");
            $sent = 0; $failed = 0;
            while ($row = $result->fetch_assoc()) {
                $email = $row['email'];
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: $email;
                $token_result = create_magic_link_token($conn, $email);
                if ($token_result['success'] && send_magic_link_email($email, $token_result['token'], $name)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
            echo json_encode(['success' => $sent > 0, 'message' => $sent > 0 ? "Magic links sent to {$sent} user(s)." : 'No emails were sent.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown bulk action.']);
        exit;
    }

    // ── GET COURSES ──────────────────────────────────────
    if ($action === 'get_courses') {
        $sql = "
            SELECT
                c.id, c.code, c.title, c.description, c.units, c.status,
                c.department_id, c.created_at,
                d.name AS dept_name,
                (SELECT cs2.instructor_id
                 FROM course_sections cs2
                 WHERE cs2.course_id = c.id ORDER BY cs2.id ASC LIMIT 1) AS instructor_id,
                (SELECT COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up2.first_name,''),' ',IFNULL(up2.last_name,''))),''), u2.email)
                 FROM course_sections cs2
                 JOIN users u2 ON u2.id = cs2.instructor_id
                 LEFT JOIN user_profiles up2 ON up2.user_id = cs2.instructor_id
                 WHERE cs2.course_id = c.id ORDER BY cs2.id ASC LIMIT 1) AS instructor_name,
                (SELECT at2.label
                 FROM course_sections cs3
                 JOIN academic_terms at2 ON at2.id = cs3.term_id
                 WHERE cs3.course_id = c.id ORDER BY cs3.id DESC LIMIT 1) AS semester,
                (SELECT COUNT(*) FROM enrollments e
                 JOIN course_sections cs4 ON cs4.id = e.section_id
                 WHERE cs4.course_id = c.id AND e.status = 'enrolled') AS enrolled,
                (SELECT COALESCE(SUM(cs5.max_students), 0)
                 FROM course_sections cs5 WHERE cs5.course_id = c.id) AS capacity
            FROM courses c
            LEFT JOIN departments d ON d.id = c.department_id
            ORDER BY c.code ASC
        ";
        $result = $conn->query($sql);
        if (!$result) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]); exit;
        }
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = [
                'id'             => (int)$row['id'],
                'code'           => $row['code'],
                'title'          => $row['title'],
                'description'    => $row['description'] ?? '',
                'units'          => (int)$row['units'],
                'status'         => $row['status'],
                'department_id'  => $row['department_id'] ? (int)$row['department_id'] : null,
                'dept_name'      => $row['dept_name'] ?? '',
                'instructor_id'  => $row['instructor_id'] ? (int)$row['instructor_id'] : null,
                'instructor'     => $row['instructor_name'] ?? '—',
                'semester'       => $row['semester'] ?? '—',
                'enrolled'       => (int)$row['enrolled'],
                'capacity'       => (int)$row['capacity'],
                'created_at'     => date('M j, Y', strtotime($row['created_at'])),
            ];
        }
        ob_clean(); echo json_encode(['success' => true, 'courses' => $courses]); exit;
    }

    // ── GET COURSE META (instructors + terms + departments for dropdowns) ──
    if ($action === 'get_course_meta') {
        $instrResult = $conn->query("
            SELECT u.id,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email) AS name,
                   COALESCE(ip.department_id, 0) AS department_id
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN instructor_profiles ip ON ip.user_id = u.id
            WHERE u.role = 'instructor' AND u.status = 'active'
            ORDER BY name ASC
        ");
        $instructors = [];
        if ($instrResult) {
            while ($r = $instrResult->fetch_assoc()) {
                $instructors[] = [
                    'id'            => (int)$r['id'],
                    'name'          => $r['name'],
                    'department_id' => (int)$r['department_id'],
                ];
            }
        }
        $termResult = $conn->query("SELECT id, label FROM academic_terms ORDER BY id DESC");
        $terms = [];
        if ($termResult) {
            while ($r = $termResult->fetch_assoc()) {
                $terms[] = ['id' => (int)$r['id'], 'label' => $r['label']];
            }
        }
        $deptResult = $conn->query("SELECT id, name, code FROM departments ORDER BY name ASC");
        $departments = [];
        if ($deptResult) {
            while ($r = $deptResult->fetch_assoc()) {
                $departments[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'code' => $r['code']];
            }
        }
        ob_clean(); echo json_encode(['success' => true, 'instructors' => $instructors, 'terms' => $terms, 'departments' => $departments]); exit;
    }

    // ── CREATE COURSE ─────────────────────────────────────
    if ($action === 'create_course') {
        $code  = strtoupper(trim($_POST['code']  ?? ''));
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $units = (int)($_POST['units'] ?? 3);
        $dept  = (int)($_POST['department_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'draft');

        if (!$code)  { ob_clean(); echo json_encode(['success' => false, 'message' => 'Course code is required.']); exit; }
        if (!$title) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Course title is required.']); exit; }
        if ($units < 1 || $units > 12) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Units must be between 1 and 12.']); exit; }
        if (!in_array($status, ['draft', 'published', 'archived'])) $status = 'draft';

        $dupCheck = $conn->prepare("SELECT id FROM courses WHERE code = ? LIMIT 1");
        $dupCheck->bind_param('s', $code);
        $dupCheck->execute();
        if ($dupCheck->get_result()->num_rows > 0) {
            $dupCheck->close();
            ob_clean(); echo json_encode(['success' => false, 'message' => "Course code \"$code\" already exists."]); exit;
        }
        $dupCheck->close();

        $stmt = $conn->prepare("INSERT INTO courses (code, title, description, department_id, units, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $deptVal = $dept ?: null;
        $stmt->bind_param('sssiisi', $code, $title, $desc, $deptVal, $units, $status, $admin_id);
        if (!$stmt->execute()) {
            ob_clean(); echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]); exit;
        }
        $new_id    = (int)$conn->insert_id;
        $stmt->close();

        // If an instructor was selected, create a default course section
        $instr_id = (int)($_POST['instructor_id'] ?? 0);
        if ($instr_id && $new_id) {
            $termQ   = $conn->query("SELECT id FROM academic_terms WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $termRow = $termQ ? $termQ->fetch_assoc() : null;
            if (!$termRow) {
                // Fall back to the most recent term
                $termQ   = $conn->query("SELECT id FROM academic_terms ORDER BY id DESC LIMIT 1");
                $termRow = $termQ ? $termQ->fetch_assoc() : null;
            }
            if ($termRow) {
                $term_id = (int)$termRow['id'];
                $secCode = $code . '-S1';
                $secStmt = $conn->prepare("INSERT INTO course_sections (course_id, term_id, instructor_id, section_code) VALUES (?, ?, ?, ?)");
                $secStmt->bind_param('iiis', $new_id, $term_id, $instr_id, $secCode);
                $secStmt->execute();
                $secStmt->close();
            }
        }

        ob_clean(); echo json_encode(['success' => true, 'id' => $new_id, 'message' => "Course \"$code — $title\" created."]); exit;
    }

    // ── UPDATE COURSE ─────────────────────────────────────
    if ($action === 'update_course') {
        $id    = (int)($_POST['id'] ?? 0);
        $code  = strtoupper(trim($_POST['code']  ?? ''));
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $units = (int)($_POST['units'] ?? 3);
        $dept  = (int)($_POST['department_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'published');

        if (!$id)    { ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid course ID.']); exit; }
        if (!$code)  { ob_clean(); echo json_encode(['success' => false, 'message' => 'Course code is required.']); exit; }
        if (!$title) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Course title is required.']); exit; }
        if ($units < 1 || $units > 12) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Units must be between 1 and 12.']); exit; }
        if (!in_array($status, ['draft', 'published', 'archived'])) $status = 'published';

        $dupCheck = $conn->prepare("SELECT id FROM courses WHERE code = ? AND id <> ? LIMIT 1");
        $dupCheck->bind_param('si', $code, $id);
        $dupCheck->execute();
        if ($dupCheck->get_result()->num_rows > 0) {
            $dupCheck->close();
            ob_clean(); echo json_encode(['success' => false, 'message' => "Course code \"$code\" is already used by another course."]); exit;
        }
        $dupCheck->close();

        $deptVal = $dept ?: null;
        $stmt = $conn->prepare("UPDATE courses SET code=?, title=?, description=?, department_id=?, units=?, status=? WHERE id=?");
        $stmt->bind_param('sssiisi', $code, $title, $desc, $deptVal, $units, $status, $id);
        $ok = $stmt->execute();
        $stmt->close();

        // Update or create section instructor if provided
        $instr_id = (int)($_POST['instructor_id'] ?? 0);
        if ($ok && $instr_id) {
            $secCheck = $conn->prepare("SELECT id FROM course_sections WHERE course_id = ? ORDER BY id ASC LIMIT 1");
            $secCheck->bind_param('i', $id);
            $secCheck->execute();
            $secRow = $secCheck->get_result()->fetch_assoc();
            $secCheck->close();
            if ($secRow) {
                $updSec = $conn->prepare("UPDATE course_sections SET instructor_id = ? WHERE id = ?");
                $updSec->bind_param('ii', $instr_id, $secRow['id']);
                $updSec->execute();
                $updSec->close();
            } else {
                $termQ   = $conn->query("SELECT id FROM academic_terms WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
                $termRow = $termQ ? $termQ->fetch_assoc() : null;
                if (!$termRow) {
                    $termQ   = $conn->query("SELECT id FROM academic_terms ORDER BY id DESC LIMIT 1");
                    $termRow = $termQ ? $termQ->fetch_assoc() : null;
                }
                if ($termRow) {
                    $term_id = (int)$termRow['id'];
                    $secCode = $code . '-S1';
                    $secStmt = $conn->prepare("INSERT INTO course_sections (course_id, term_id, instructor_id, section_code) VALUES (?, ?, ?, ?)");
                    $secStmt->bind_param('iiis', $id, $term_id, $instr_id, $secCode);
                    $secStmt->execute();
                    $secStmt->close();
                }
            }
        }

        ob_clean(); echo json_encode(['success' => $ok, 'message' => $ok ? 'Course updated successfully.' : 'DB error: ' . $conn->error]); exit;
    }

    // ── ARCHIVE/RESTORE COURSE ────────────────────────────
    if ($action === 'archive_course') {
        $id     = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if (!$id) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid course ID.']); exit; }

        $cur = $conn->prepare("SELECT status FROM courses WHERE id = ? LIMIT 1");
        $cur->bind_param('i', $id);
        $cur->execute();
        $row = $cur->get_result()->fetch_assoc();
        $cur->close();
        if (!$row) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Course not found.']); exit; }

        if ($row['status'] === 'archived') {
            // Restore
            $stmt = $conn->prepare("UPDATE courses SET status='published', archived_at=NULL, archived_by=NULL, archive_reason=NULL WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute(); $stmt->close();
            ob_clean(); echo json_encode(['success' => true, 'new_status' => 'published', 'message' => 'Course restored to published.']); exit;
        } else {
            // Archive
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE courses SET status='archived', archived_at=?, archived_by=?, archive_reason=? WHERE id=?");
            $stmt->bind_param('sisi', $now, $admin_id, $reason, $id);
            $stmt->execute(); $stmt->close();
            // Log in course_archives
            $log = $conn->prepare("INSERT INTO course_archives (course_id, action, performed_by, reason) VALUES (?, 'archived', ?, ?)");
            $log->bind_param('iis', $id, $admin_id, $reason);
            $log->execute(); $log->close();
            ob_clean(); echo json_encode(['success' => true, 'new_status' => 'archived', 'message' => 'Course archived.']); exit;
        }
    }

    // ── DELETE COURSE ─────────────────────────────────────
    if ($action === 'delete_course') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { ob_clean(); echo json_encode(['success' => false, 'message' => 'Invalid course ID.']); exit; }

        // Block if there are active enrollments
        $enrollCheck = $conn->prepare("
            SELECT COUNT(*) AS cnt FROM enrollments e
            JOIN course_sections cs ON cs.id = e.section_id
            WHERE cs.course_id = ? AND e.status = 'enrolled'
        ");
        $enrollCheck->bind_param('i', $id);
        $enrollCheck->execute();
        $enrollRow = $enrollCheck->get_result()->fetch_assoc();
        $enrollCheck->close();
        if ((int)$enrollRow['cnt'] > 0) {
            ob_clean(); echo json_encode([
                'success' => false,
                'message' => "Cannot delete: this course has {$enrollRow['cnt']} active enrollment(s). Archive it instead."
            ]); exit;
        }

        $del = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $del->bind_param('i', $id);
        $ok  = $del->execute();
        $del->close();
        ob_clean(); echo json_encode(['success' => $ok, 'message' => $ok ? 'Course deleted.' : 'DB error: ' . $conn->error]); exit;
    }

    // ── GET ENROLLMENTS ──────────────────────────────────────
    if ($action === 'get_enrollments') {
        $status_filter = trim($_GET['status'] ?? '');
        $search        = trim($_GET['search'] ?? '');
        $course_filter = trim($_GET['course'] ?? '');

        $where_clauses = ['1=1'];
        $params = [];
        $types  = '';

        if ($status_filter && in_array($status_filter, ['enrolled','dropped','completed','failed'])) {
            $where_clauses[] = 'e.status = ?';
            $params[] = $status_filter;
            $types   .= 's';
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where_clauses[] = "(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,'')) LIKE ? OR u.email LIKE ? OR c.code LIKE ? OR c.title LIKE ?)";
            array_push($params, $like, $like, $like, $like);
            $types .= 'ssss';
        }
        if ($course_filter !== '') {
            $where_clauses[] = 'c.code = ?';
            $params[] = $course_filter;
            $types   .= 's';
        }

        $where = implode(' AND ', $where_clauses);
        $sql = "
            SELECT e.id, e.student_id, e.section_id, e.status, e.enrolled_at, e.final_grade,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email) AS student_name,
                   u.email AS student_email,
                   sp.student_id AS student_number,
                   c.code AS course_code, c.title AS course_title,
                   cs.section_code,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up2.first_name,''),' ',IFNULL(up2.last_name,''))),''), u2.email) AS instructor_name,
                   at.label AS term_label
            FROM enrollments e
            JOIN users u ON u.id = e.student_id
            LEFT JOIN user_profiles up ON up.user_id = e.student_id
            LEFT JOIN student_profiles sp ON sp.user_id = e.student_id
            JOIN course_sections cs ON cs.id = e.section_id
            JOIN courses c ON c.id = cs.course_id
            LEFT JOIN users u2 ON u2.id = cs.instructor_id
            LEFT JOIN user_profiles up2 ON up2.user_id = cs.instructor_id
            LEFT JOIN academic_terms at ON at.id = cs.term_id
            WHERE $where
            ORDER BY e.enrolled_at DESC
            LIMIT 500
        ";
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $conn->query($sql);
        }
        if (!$result) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'Query failed: '.$conn->error]); exit;
        }
        $enrollments = [];
        while ($row = $result->fetch_assoc()) {
            $enrollments[] = [
                'id'             => (int)$row['id'],
                'student_id'     => (int)$row['student_id'],
                'section_id'     => (int)$row['section_id'],
                'status'         => $row['status'],
                'student_name'   => $row['student_name'],
                'student_email'  => $row['student_email'],
                'student_number' => $row['student_number'] ?? '',
                'course_code'    => $row['course_code'],
                'course_title'   => $row['course_title'],
                'section_code'   => $row['section_code'],
                'instructor'     => $row['instructor_name'] ?? '—',
                'term'           => $row['term_label'] ?? '—',
                'enrolled_at'    => $row['enrolled_at'] ? date('M j, Y', strtotime($row['enrolled_at'])) : '',
                'final_grade'    => $row['final_grade'],
            ];
        }
        ob_clean(); echo json_encode(['success'=>true,'enrollments'=>$enrollments]); exit;
    }

    // ── GET ENROLLMENT STATS ──────────────────────────────────
    if ($action === 'get_enrollment_stats') {
        $stats = ['total'=>0,'enrolled'=>0,'dropped'=>0,'completed'=>0,'failed'=>0];
        $result = $conn->query("SELECT status, COUNT(*) AS cnt FROM enrollments GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $key = $row['status'];
                if (isset($stats[$key])) $stats[$key] = (int)$row['cnt'];
                $stats['total'] += (int)$row['cnt'];
            }
        }
        ob_clean(); echo json_encode(['success'=>true,'stats'=>$stats]); exit;
    }

    // ── GET STUDENTS LIST (for enrollment modal) ──────────────
    if ($action === 'get_students_list') {
        $result = $conn->query("
            SELECT u.id,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email) AS name,
                   u.email, sp.student_id AS student_number, sp.program, sp.year_level
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            WHERE u.role = 'student' AND u.status = 'active'
            ORDER BY name ASC
        ");
        $students = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $students[] = [
                    'id'             => (int)$row['id'],
                    'name'           => $row['name'],
                    'email'          => $row['email'],
                    'student_number' => $row['student_number'] ?? '',
                    'program'        => $row['program'] ?? '',
                    'year_level'     => $row['year_level'] ? (int)$row['year_level'] : null,
                ];
            }
        }
        ob_clean(); echo json_encode(['success'=>true,'students'=>$students]); exit;
    }

    // ── GET AVAILABLE STUDENTS FOR A SECTION ─────────────────
    if ($action === 'get_available_students') {
        $section_id = (int)($_GET['section_id'] ?? 0);
        if (!$section_id) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'Invalid section ID.']); exit;
        }
        $stmt = $conn->prepare("
            SELECT u.id,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email) AS name,
                   u.email, sp.student_id AS student_number
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            WHERE u.role = 'student' AND u.status = 'active'
              AND u.id NOT IN (
                  SELECT e.student_id FROM enrollments e
                  WHERE e.section_id = ? AND e.status = 'enrolled'
              )
            ORDER BY name ASC
        ");
        $stmt->bind_param('i', $section_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'id'             => (int)$row['id'],
                'name'           => $row['name'],
                'email'          => $row['email'],
                'student_number' => $row['student_number'] ?? '',
            ];
        }
        ob_clean(); echo json_encode(['success'=>true,'students'=>$students]); exit;
    }

    // ── GET SECTIONS LIST (for enrollment modal) ──────────────
    if ($action === 'get_sections_list') {
        $result = $conn->query("
            SELECT cs.id, cs.section_code, cs.max_students,
                   c.code AS course_code, c.title AS course_title,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email) AS instructor_name,
                   at.label AS term_label,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = cs.id AND e.status = 'enrolled') AS enrolled_count
            FROM course_sections cs
            JOIN courses c ON c.id = cs.course_id
            LEFT JOIN users u ON u.id = cs.instructor_id
            LEFT JOIN user_profiles up ON up.user_id = cs.instructor_id
            LEFT JOIN academic_terms at ON at.id = cs.term_id
            WHERE c.status = 'published'
            ORDER BY c.code ASC, cs.section_code ASC
        ");
        $sections = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = [
                    'id'           => (int)$row['id'],
                    'section_code' => $row['section_code'],
                    'course_code'  => $row['course_code'],
                    'course_title' => $row['course_title'],
                    'instructor'   => $row['instructor_name'] ?? '—',
                    'term'         => $row['term_label'] ?? '—',
                    'max_students' => $row['max_students'] ? (int)$row['max_students'] : null,
                    'enrolled'     => (int)$row['enrolled_count'],
                ];
            }
        }
        ob_clean(); echo json_encode(['success'=>true,'sections'=>$sections]); exit;
    }

    // ── ENROLL STUDENT ────────────────────────────────────────
    if ($action === 'enroll_student') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $section_id = (int)($_POST['section_id'] ?? 0);
        if (!$student_id || !$section_id) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'Student and section are required.']); exit;
        }
        $chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'student' LIMIT 1");
        $chk->bind_param('i', $student_id); $chk->execute();
        if (!$chk->get_result()->num_rows) { $chk->close(); ob_clean(); echo json_encode(['success'=>false,'message'=>'Invalid student.']); exit; }
        $chk->close();

        $schk = $conn->prepare("SELECT cs.id, cs.max_students, (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = cs.id AND e.status = 'enrolled') AS enrolled FROM course_sections cs WHERE cs.id = ? LIMIT 1");
        $schk->bind_param('i', $section_id); $schk->execute();
        $srow = $schk->get_result()->fetch_assoc(); $schk->close();
        if (!$srow) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Invalid section.']); exit; }
        if ($srow['max_students'] && (int)$srow['enrolled'] >= (int)$srow['max_students']) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'Section is at full capacity.']); exit;
        }

        $dup = $conn->prepare("SELECT id, status FROM enrollments WHERE student_id = ? AND section_id = ? LIMIT 1");
        $dup->bind_param('ii', $student_id, $section_id); $dup->execute();
        $dupRow = $dup->get_result()->fetch_assoc(); $dup->close();
        if ($dupRow) {
            if ($dupRow['status'] === 'enrolled') {
                ob_clean(); echo json_encode(['success'=>false,'message'=>'Student is already enrolled in this section.']); exit;
            }
            $reac = $conn->prepare("UPDATE enrollments SET status = 'enrolled', updated_at = NOW() WHERE id = ?");
            $reac->bind_param('i', $dupRow['id']); $reac->execute(); $reac->close();
            ob_clean(); echo json_encode(['success'=>true,'message'=>'Student re-enrolled successfully.']); exit;
        }

        $ins = $conn->prepare("INSERT INTO enrollments (student_id, section_id, status) VALUES (?, ?, 'enrolled')");
        $ins->bind_param('ii', $student_id, $section_id);
        $ok = $ins->execute(); $ins->close();
        ob_clean(); echo json_encode(['success'=>$ok,'message'=>$ok?'Student enrolled successfully.':'DB error: '.$conn->error]); exit;
    }

    // ── DROP ENROLLMENT ───────────────────────────────────────
    if ($action === 'drop_enrollment') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Invalid enrollment ID.']); exit; }
        $stmt = $conn->prepare("UPDATE enrollments SET status = 'dropped', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute(); $stmt->close();
        ob_clean(); echo json_encode(['success'=>$ok,'message'=>$ok?'Enrollment dropped.':'DB error: '.$conn->error]); exit;
    }

    // ── GET STUDENT SECTIONS (for bulk enroll modal) ─────────
    if ($action === 'get_student_sections') {
        $result = $conn->query("
            SELECT sp.section, COUNT(*) AS student_count
            FROM student_profiles sp
            JOIN users u ON u.id = sp.user_id
            WHERE u.status = 'active' AND sp.section IS NOT NULL AND sp.section != ''
            GROUP BY sp.section
            ORDER BY sp.section ASC
        ");
        $sections = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sections[] = [
                    'section'       => $row['section'],
                    'student_count' => (int)$row['student_count'],
                ];
            }
        }
        ob_clean(); echo json_encode(['success'=>true,'sections'=>$sections]); exit;
    }

    // ── BULK ENROLL BY STUDENT SECTION ────────────────────────
    if ($action === 'bulk_enroll_by_section') {
        $section_id     = (int)($_POST['section_id']     ?? 0);
        $student_section = trim($_POST['student_section'] ?? '');
        if (!$section_id || $student_section === '') {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'Course section and student section are required.']); exit;
        }

        // Capacity check
        $schk = $conn->prepare("
            SELECT cs.max_students,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.section_id = cs.id AND e.status = 'enrolled') AS enrolled
            FROM course_sections cs WHERE cs.id = ? LIMIT 1
        ");
        $schk->bind_param('i', $section_id); $schk->execute();
        $srow = $schk->get_result()->fetch_assoc(); $schk->close();
        if (!$srow) { ob_clean(); echo json_encode(['success'=>false,'message'=>'Invalid course section.']); exit; }

        // Get students in this section not already enrolled
        $stmt = $conn->prepare("
            SELECT u.id FROM users u
            JOIN student_profiles sp ON sp.user_id = u.id
            WHERE u.role = 'student' AND u.status = 'active'
              AND sp.section = ?
              AND u.id NOT IN (
                  SELECT e.student_id FROM enrollments e
                  WHERE e.section_id = ? AND e.status = 'enrolled'
              )
        ");
        $stmt->bind_param('si', $student_section, $section_id);
        $stmt->execute();
        $result = $stmt->get_result(); $stmt->close();
        $students = [];
        while ($row = $result->fetch_assoc()) $students[] = (int)$row['id'];

        if (empty($students)) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'All students in '.$student_section.' are already enrolled in this section.']); exit;
        }

        if ($srow['max_students']) {
            $available = (int)$srow['max_students'] - (int)$srow['enrolled'];
            if (count($students) > $available) {
                ob_clean(); echo json_encode(['success'=>false,'message'=>'Not enough capacity. '.count($students).' student(s) to enroll but only '.$available.' slot(s) available.']); exit;
            }
        }

        $ins = $conn->prepare("
            INSERT INTO enrollments (student_id, section_id, status) VALUES (?, ?, 'enrolled')
            ON DUPLICATE KEY UPDATE status='enrolled', updated_at=NOW()
        ");
        $count = 0;
        foreach ($students as $sid) {
            $ins->bind_param('ii', $sid, $section_id);
            if ($ins->execute()) $count++;
        }
        $ins->close();
        ob_clean(); echo json_encode(['success'=>true,'message'=>"Successfully enrolled {$count} student(s) from {$student_section}."]); exit;
    }

    // ── GET STUDENT ENROLLMENT HISTORY ───────────────────────
    if ($action === 'get_student_enrollments') {
        $student_id = (int)($_GET['student_id'] ?? 0);
        if (!$student_id) {
            ob_clean(); echo json_encode(['success'=>false,'message'=>'Invalid student ID.']); exit;
        }
        $stmt = $conn->prepare("
            SELECT e.id, e.status, e.enrolled_at, e.updated_at, e.final_grade,
                   c.code AS course_code, c.title AS course_title, c.units,
                   cs.section_code,
                   COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), u.email) AS instructor_name,
                   at.label AS term_label, at.start_date, at.end_date
            FROM enrollments e
            JOIN course_sections cs ON cs.id = e.section_id
            JOIN courses c ON c.id = cs.course_id
            LEFT JOIN users u ON u.id = cs.instructor_id
            LEFT JOIN user_profiles up ON up.user_id = cs.instructor_id
            LEFT JOIN academic_terms at ON at.id = cs.term_id
            WHERE e.student_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $history = [];
        $counts  = ['enrolled'=>0,'dropped'=>0,'completed'=>0,'failed'=>0];
        while ($row = $result->fetch_assoc()) {
            $s = $row['status'];
            if (isset($counts[$s])) $counts[$s]++;
            $history[] = [
                'id'           => (int)$row['id'],
                'status'       => $s,
                'course_code'  => $row['course_code'],
                'course_title' => $row['course_title'],
                'units'        => $row['units'],
                'section_code' => $row['section_code'],
                'instructor'   => $row['instructor_name'] ?? '—',
                'term'         => $row['term_label'] ?? '—',
                'enrolled_at'  => $row['enrolled_at'] ? date('M j, Y', strtotime($row['enrolled_at'])) : '',
                'updated_at'   => $row['updated_at'] ? date('M j, Y', strtotime($row['updated_at'])) : '',
                'final_grade'  => $row['final_grade'],
            ];
        }
        ob_clean(); echo json_encode(['success'=>true,'history'=>$history,'counts'=>$counts]); exit;
    }

    // ── GET THEME ─────────────────────────────────────────────
    if ($action === 'get_theme') {
        $res = $conn->query("SELECT * FROM theme_settings WHERE id=1 LIMIT 1");
        $theme_row = $res ? $res->fetch_assoc() : null;
        ob_clean();
        echo json_encode(['success' => true, 'theme' => $theme_row ?: null]);
        exit;
    }

    // ── SAVE THEME ────────────────────────────────────────────
    if ($action === 'save_theme') {
        $name           = $conn->real_escape_string(trim($_POST['name']          ?? 'Custom'));
        $primary_color  = $conn->real_escape_string(trim($_POST['primary_color'] ?? '336 67% 52%'));
        $primary_dark   = $conn->real_escape_string(trim($_POST['primary_dark']  ?? '336 67% 40%'));
        $primary_light  = $conn->real_escape_string(trim($_POST['primary_light'] ?? '336 100% 97%'));
        $bg_color       = $conn->real_escape_string(trim($_POST['bg_color']      ?? '336 100% 97%'));
        $surface_color  = $conn->real_escape_string(trim($_POST['surface_color'] ?? '0 0% 100%'));
        $border_color   = $conn->real_escape_string(trim($_POST['border_color']  ?? '336 60% 87%'));
        $text_color     = $conn->real_escape_string(trim($_POST['text_color']    ?? '336 60% 10%'));
        $text_secondary = $conn->real_escape_string(trim($_POST['text_secondary']?? '336 40% 47%'));
        $accent_color   = $conn->real_escape_string(trim($_POST['accent_color']  ?? '207 80% 60%'));
        $is_dark        = (int)($_POST['is_dark'] ?? 0);

        $ok = $conn->query("
            INSERT INTO theme_settings (id,name,primary_color,primary_dark,primary_light,
                bg_color,surface_color,border_color,text_color,text_secondary,accent_color,is_dark)
            VALUES (1,'$name','$primary_color','$primary_dark','$primary_light',
                '$bg_color','$surface_color','$border_color','$text_color','$text_secondary','$accent_color',$is_dark)
            ON DUPLICATE KEY UPDATE
                name='$name',primary_color='$primary_color',primary_dark='$primary_dark',
                primary_light='$primary_light',bg_color='$bg_color',surface_color='$surface_color',
                border_color='$border_color',text_color='$text_color',text_secondary='$text_secondary',
                accent_color='$accent_color',is_dark=$is_dark
        ");
        ob_clean();
        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Theme saved.' : $conn->error]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ===== SESSION DATA =====
$admin_name = $_SESSION['user'] ?? 'System Administrator';
$admin_email = $_SESSION['email'] ?? 'admin@learnflow.edu';
$admin_initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', $admin_name))));
$admin_initials = substr($admin_initials, 0, 2);
$theme = $_COOKIE['theme'] ?? 'dark';

// ===== LOAD CUSTOM THEME FROM DB =====
$db_theme = null;
$db_theme_res = $conn->query("SELECT * FROM theme_settings WHERE id=1 LIMIT 1");
if ($db_theme_res) $db_theme = $db_theme_res->fetch_assoc();

// Load admin avatar from DB
$admin_avatar_url = '';
$admin_user_id = (int)($_SESSION['user_id'] ?? 0);
if ($admin_user_id) {
    $avatarRes = $conn->query("SELECT avatar_url FROM user_profiles WHERE user_id = {$admin_user_id} LIMIT 1");
    if ($avatarRes && $avatarRow = $avatarRes->fetch_assoc()) {
        $admin_avatar_url = $avatarRow['avatar_url'] ?? '';
    }
}

// Logout handled by learnflow-logout.php
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnFlow – Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
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
  <?php if ($db_theme['accent_color']): ?>--secondary: hsl(<?php echo htmlspecialchars($db_theme['accent_color']); ?>);<?php endif; ?>
  --primary-glow: hsla(<?php echo htmlspecialchars($db_theme['primary_color']); ?>, 0.12);
}
</style>
<?php endif; ?>
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
  --primary-glow: #ed3ac61f;
  --success: #10B981;
  --warning: #F59E0B;
  --info: #3B82F6;
  --surface: #FFFFFF;
  --surface-2: #F8E4EF;
  --border: #F0C0D8;
  --text: #2a0e1c;
  --text-2: #7a3a58;  
  --text-3: #c090a8;
  --surface-3: #DDD6FE;
  --border: #DDD6FE;
  --border-strong: #C4B5FD;
  --shadow: 0 2px 12px rgba(124,58,237,0.09);
  --shadow-md: 0 4px 24px rgba(124,58,237,0.16);
  --shadow-lg: 0 8px 40px rgba(124,58,237,0.22);
  --radius: 14px;
  --radius-sm: 9px;
  --radius-xs: 6px;
  --sidebar-w: 250px;
  --topbar-h: 62px;
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
  --primary-glow: rgba(167,139,250,0.13);
  --success: #34D399;
  --warning: #FBBF24;
  --info: #60A5FA;
  --surface-3: #271c52;
  --shadow: 0 2px 14px rgba(0,0,0,0.45);
  --shadow-md: 0 4px 28px rgba(0,0,0,0.55);
  --shadow-lg: 0 8px 48px rgba(0,0,0,0.65);
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
  position:fixed;left:0;top:0;z-index:100;transition:.3s cubic-bezier(.4,0,.2,1);
  overflow-y:auto;overflow-x:hidden;
}
.sidebar.collapsed{width:64px}
.sidebar-header{
  padding:20px 16px 16px;display:flex;align-items:center;gap:11px;
  border-bottom:1px solid var(--border);
}
.sidebar-brand{font-family:'Syne',sans-serif;font-size:19px;font-weight:800;white-space:nowrap;overflow:hidden;transition:.3s;letter-spacing:-.3px}
.sidebar.collapsed .sidebar-brand,.sidebar.collapsed .nav-label,.sidebar.collapsed .nav-section-label{opacity:0;width:0;overflow:hidden}
.sidebar.collapsed .sidebar-brand{display:none}
.nav-section-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-3);padding:14px 18px 4px;white-space:nowrap;transition:.3s}
.nav-item{
  display:flex;align-items:center;gap:12px;padding:9px 14px;cursor:pointer;
  transition:all .15s ease;color:var(--text-2);position:relative;margin:1px 10px;
  border-radius:var(--radius-sm);white-space:nowrap;
}
.nav-item:hover{background:var(--surface-2);color:var(--text)}
.nav-item.active{background:var(--primary-glow);color:var(--primary);font-weight:600}
.nav-item.active::before{content:'';position:absolute;left:-10px;top:50%;transform:translateY(-50%);width:3px;height:56%;background:var(--primary);border-radius:0 3px 3px 0}
.nav-icon{font-size:16px;flex-shrink:0;width:20px;text-align:center;opacity:.85}
.nav-item.active .nav-icon{opacity:1}
.nav-label{font-size:13px;font-weight:500;transition:.3s}
.nav-badge{margin-left:auto;background:var(--danger);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;min-width:20px;text-align:center}
.nav-badge.success{background:var(--success)}
.sidebar-footer{margin-top:auto;padding:14px;border-top:1px solid var(--border)}
.user-card{display:flex;align-items:center;gap:10px;padding:8px 6px;border-radius:var(--radius-sm);cursor:pointer;transition:.15s}
.user-card:hover{background:var(--surface-2)}
.user-avatar{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0;letter-spacing:.5px}
.user-info{overflow:hidden}
.user-name{font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-role{font-size:11px;color:var(--text-3)}
.sidebar.collapsed .user-info{display:none}

/* ===== TOPBAR ===== */
.topbar{height:var(--topbar-h);background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 22px;gap:12px;position:sticky;top:0;z-index:50}
.topbar-left{display:flex;align-items:center;gap:12px;flex:1}
.topbar-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--text);letter-spacing:-.2px}
.topbar-right{display:flex;align-items:center;gap:6px}
.icon-btn{width:36px;height:36px;border-radius:var(--radius-sm);background:var(--surface-2);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;border:1px solid var(--border);transition:all .2s ease;color:var(--text-2);position:relative}
.icon-btn:hover{background:var(--primary-light);color:var(--primary);border-color:var(--border-strong);transform:translateY(-1px);box-shadow:var(--shadow)}
.notif-dot{position:absolute;top:5px;right:5px;min-width:16px;height:16px;border-radius:8px;background:var(--danger);border:1.5px solid var(--surface);display:none;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;padding:0 3px;line-height:1}

/* ===== MAIN CONTENT ===== */
.main-content{margin-left:var(--sidebar-w);flex:1;transition:.3s;min-height:100vh;display:flex;flex-direction:column}
.main-content.expanded{margin-left:64px}
.content-area{padding:24px;flex:1}

/* ===== PAGE HEADER ===== */
.page-header{margin-bottom:24px}
.page-header h1{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.4px}
.page-header p{color:var(--text-2);font-size:13px;margin-top:4px}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-3);margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;font-weight:600}
.breadcrumb span:last-child{color:var(--text-2)}

/* ===== CARDS ===== */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow)}

/* ===== STATS ===== */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:20px;box-shadow:var(--shadow);display:flex;align-items:center;gap:16px;
  transition:all .2s ease;cursor:pointer;position:relative;overflow:hidden;
}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--stat-color,var(--primary)),transparent);opacity:.5}
.stat-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.stat-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;line-height:1}
.stat-label{font-size:12px;color:var(--text-2);margin-top:3px}
.stat-change{font-size:11px;margin-top:4px;font-weight:600}
.stat-change.up{color:var(--success)}
.stat-change.down{color:var(--danger)}

/* ===== GRIDS ===== */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}

/* ===== TABLE ===== */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text)}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--surface-2)}
.avatar-sm{width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700}

/* ===== BADGES ===== */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.2px}
.badge-purple{background:rgba(124,58,237,0.15);color:#7C3AED}
.badge-blue{background:rgba(59,130,246,0.15);color:#3B82F6}
.badge-green{background:rgba(16,185,129,0.15);color:#059669}
.badge-amber{background:rgba(245,158,11,0.15);color:#B45309}
.badge-red{background:rgba(239,68,68,0.15);color:#DC2626}
.badge-gray{background:var(--surface-2);color:var(--text-2)}
.badge-cyan{background:rgba(6,182,212,0.15);color:#0891B2}
[data-theme="dark"] .badge-purple{color:var(--primary)}
[data-theme="dark"] .badge-blue{color:var(--info)}
[data-theme="dark"] .badge-green{color:var(--success)}
[data-theme="dark"] .badge-amber{color:var(--warning)}
[data-theme="dark"] .badge-red{color:var(--danger)}

/* ===== BUTTONS ===== */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 18px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;transition:.2s;cursor:pointer}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 4px 12px rgba(124,58,237,0.3)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(124,58,237,0.4)}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text-2)}
.btn-outline:hover{border-color:var(--primary);color:var(--primary)}
.btn-ghost{background:transparent;color:var(--text-2);padding:8px 12px}
.btn-ghost:hover{background:var(--surface-2);color:var(--text)}
.btn-danger{background:rgba(239,68,68,0.1);color:var(--danger);border:1px solid rgba(239,68,68,0.2)}
.btn-danger:hover{background:rgba(239,68,68,0.2)}
.btn-success{background:rgba(16,185,129,0.1);color:var(--success);border:1px solid rgba(16,185,129,0.2)}
.btn-success:hover{background:rgba(16,185,129,0.2)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-full{width:100%}

/* ===== FORM ===== */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text-2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.input-wrap{position:relative}
.input-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:16px;pointer-events:none}
.input-wrap input,.input-wrap select{width:100%;padding:10px 13px 10px 38px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;transition:.2s}
.input-wrap input:focus,.input-wrap select:focus{border-color:var(--primary);background:var(--surface)}
input[type="text"]:not(.input-wrap input),
input[type="email"]:not(.input-wrap input),
input[type="password"]:not(.input-wrap input),
input[type="number"]:not(.input-wrap input),
select:not(.input-wrap select),
textarea{width:100%;padding:10px 13px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;transition:.2s;font-family:inherit}
textarea{resize:vertical;min-height:80px}
input:focus,select:focus,textarea:focus{border-color:var(--primary);background:var(--surface)}

/* ===== PROGRESS ===== */
.progress-bar{height:6px;border-radius:4px;background:var(--surface-2);overflow:hidden}
.progress-fill{height:100%;border-radius:4px;transition:.6s}
.progress-fill.purple{background:linear-gradient(90deg,var(--primary),var(--primary-dark))}
.progress-fill.green{background:linear-gradient(90deg,var(--success),#047857)}
.progress-fill.amber{background:linear-gradient(90deg,var(--warning),#92400e)}
.progress-fill.blue{background:linear-gradient(90deg,var(--info),#1d4ed8)}

/* ===== TOGGLE ===== */
.toggle{position:relative;width:44px;height:24px}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:12px;cursor:pointer;transition:.3s}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s}
.toggle input:checked + .toggle-slider{background:var(--primary)}
.toggle input:checked + .toggle-slider::before{transform:translateX(20px)}

/* ===== SETTING ROW ===== */
.setting-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)}
.setting-row:last-child{border-bottom:none}
.setting-info h4{font-size:14px;font-weight:600}
.setting-info p{font-size:12px;color:var(--text-2);margin-top:2px}

/* ===== MODAL ===== */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:300;display:none;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border-radius:20px;padding:28px;width:100%;max-width:520px;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto}
.modal-lg{max-width:680px}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.modal-header h2{font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
.modal-footer{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}

/* ===== TOAST ===== */
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--text);color:var(--bg);padding:12px 22px;border-radius:50px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.25);transition:transform .3s ease,opacity .3s ease;opacity:0;pointer-events:none;white-space:nowrap;z-index:999}
.toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.toast.success{background:var(--success);color:#fff}
.toast.error{background:var(--danger);color:#fff}
.toast.warn{background:var(--warning);color:#fff}

/* ===== NOTIF PANEL ===== */
.notif-panel{position:absolute;top:48px;right:0;width:340px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);display:none;z-index:200}
.notif-panel.open{display:block}
.notif-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);transition:.15s;cursor:pointer}
.notif-item:hover{background:var(--surface-2)}
.notif-item.unread{background:var(--primary-light)}
.notif-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;background:var(--surface-2)}
.notif-text{font-size:12px;color:var(--text-2);margin-top:2px}
.notif-time{font-size:11px;color:var(--text-3);margin-top:4px}

/* ===== SIDEBAR OVERLAY ===== */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.mobile-open{transform:translateX(0)}
  .main-content{margin-left:0!important}
  .sidebar-overlay{display:block;opacity:0;pointer-events:none;transition:.3s}
  .sidebar-overlay.open{opacity:1;pointer-events:all}
  .stats-grid{grid-template-columns:1fr 1fr}
  .grid-2,.grid-3,.grid-4{grid-template-columns:1fr}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .content-area{padding:16px}
}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-in{animation:fadeUp .3s ease}

/* ===== ADMIN SPECIFIC ===== */
.admin-hero{
  background:linear-gradient(135deg,var(--primary-dark),var(--primary),#8B5CF6);
  border-radius:var(--radius);padding:28px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden;
}
.admin-hero::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.08)}
.admin-hero::after{content:'';position:absolute;bottom:-60px;right:80px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,0.05)}
.hero-tag{background:rgba(255,255,255,0.2);color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.5px;margin-bottom:10px;display:inline-block}
.hero-title{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin-bottom:6px}
.hero-sub{font-size:13px;opacity:.85}
.hero-stats{display:flex;gap:24px;margin-top:20px}
.hero-stat-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:700}
.hero-stat-label{font-size:11px;opacity:.75;margin-top:2px}
.quick-actions{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap}
.quick-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:var(--radius-sm);background:var(--surface);border:1.5px solid var(--border);color:var(--text-2);font-size:13px;font-weight:600;transition:.2s;cursor:pointer}
.quick-btn:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light);transform:translateY(-1px);box-shadow:var(--shadow)}
.quick-btn .qicon{font-size:16px}
.activity-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
.activity-item:last-child{border-bottom:none}
.act-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.act-text{font-size:13px;line-height:1.4}
.act-time{font-size:11px;color:var(--text-3);margin-top:3px}
.search-wrap{position:relative}
.search-wrap input{width:100%;padding:9px 12px 9px 36px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px;transition:.2s}
.search-wrap input:focus{border-color:var(--primary);outline:none;background:var(--surface)}
.search-wrap::before{content:'🔍';position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13px;pointer-events:none}
.mini-chart{display:flex;align-items:flex-end;gap:3px;height:50px}
.mini-bar{flex:1;background:linear-gradient(180deg,var(--primary),rgba(124,58,237,.3));border-radius:3px 3px 0 0;min-width:10px;transition:.3s}
.role-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.role-admin{background:rgba(124,58,237,.15);color:var(--primary)}
.role-instructor{background:rgba(245,158,11,.15);color:#b45309}
.role-student{background:rgba(16,185,129,.15);color:#059669}
[data-theme="dark"] .role-instructor{color:var(--warning)}
[data-theme="dark"] .role-student{color:var(--success)}
[data-theme="dark"] .role-admin{color:var(--primary)}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
.status-dot.online{background:var(--success)}
.status-dot.offline{background:var(--text-3)}
.status-dot.suspended{background:var(--danger)}
.tabbar{display:flex;gap:2px;background:var(--surface-2);padding:4px;border-radius:var(--radius-sm);margin-bottom:18px;width:fit-content}
.tab-btn{padding:7px 16px;border-radius:6px;font-size:13px;font-weight:600;color:var(--text-2);transition:.2s;cursor:pointer;background:transparent;border:none;font-family:inherit}
.tab-btn.active{background:var(--surface);color:var(--primary);box-shadow:var(--shadow)}
.sys-metric{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.sys-metric:last-child{border-bottom:none}
.sys-metric-val{font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
.sys-metric-label{font-size:12px;color:var(--text-2)}
.log-entry{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);font-size:12px}
.log-entry:last-child{border-bottom:none}
.log-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:4px}
.log-dot.info{background:var(--info)}
.log-dot.warn{background:var(--warning)}
.log-dot.error{background:var(--danger)}
.log-dot.success{background:var(--success)}
.log-time{color:var(--text-3);white-space:nowrap;font-size:11px;margin-top:2px}
.plan-card{border-radius:var(--radius);padding:18px;border:1.5px solid var(--border);transition:.2s;cursor:pointer}
.plan-card:hover{border-color:var(--primary);box-shadow:var(--shadow-md)}
.plan-card.active-plan{border-color:var(--primary);background:var(--primary-light)}
.inline-flex{display:inline-flex;align-items:center;gap:6px}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.section-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.donut-ring{width:80px;height:80px;border-radius:50%;background:conic-gradient(var(--primary) 0% 72%,var(--surface-2) 72% 100%);display:flex;align-items:center;justify-content:center;position:relative}
.donut-inner{width:56px;height:56px;background:var(--surface);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.bulk-action-bar{background:var(--primary-light);border:1.5px solid var(--primary);border-radius:var(--radius-sm);padding:10px 16px;display:flex;align-items:center;gap:12px;margin-bottom:12px;display:none}
.bulk-action-bar.visible{display:flex}
.filter-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
.filter-chip{padding:5px 12px;border-radius:20px;background:var(--surface-2);border:1.5px solid var(--border);font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;transition:.2s;white-space:nowrap}
.filter-chip:hover,.filter-chip.active{background:var(--primary-light);border-color:var(--primary);color:var(--primary)}
.trend-up::before{content:'↑ ';color:var(--success)}
.trend-down::before{content:'↓ ';color:var(--danger)}

/* ===== REDESIGNED DASHBOARD ===== */
.dash-hero{
  position:relative;background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 55%,#8B5CF6 100%);
  border-radius:var(--radius);padding:30px 32px;margin-bottom:20px;color:#fff;
  overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:20px;
}
.dash-hero-orb{position:absolute;border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none}
.dash-orb-1{width:240px;height:240px;top:-90px;right:160px}
.dash-orb-2{width:120px;height:120px;bottom:-40px;right:30px;background:rgba(255,255,255,.05)}
.dash-orb-3{width:60px;height:60px;top:20px;right:380px;background:rgba(255,255,255,.08)}
.dash-hero-left{position:relative;z-index:1}
.dash-hero-eyebrow{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;opacity:.7;margin-bottom:10px}
.dash-hero-title{font-family:'Syne',sans-serif;font-size:clamp(20px,3vw,28px);font-weight:800;margin-bottom:5px;line-height:1.2}
.dash-hero-title strong{opacity:.8}
.dash-hero-date{font-size:12px;opacity:.65}
.dash-hero-right{display:flex;gap:0;position:relative;z-index:1;background:rgba(255,255,255,.08);border-radius:12px;padding:0;overflow:hidden;border:1px solid rgba(255,255,255,.15);flex-shrink:0}
.dash-hm{padding:14px 20px;text-align:center;border-right:1px solid rgba(255,255,255,.15);transition:.2s;cursor:default}
.dash-hm:last-child{border-right:none}
.dash-hm:hover{background:rgba(255,255,255,.08)}
.dash-hm-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;line-height:1}
.dash-hm-lbl{font-size:10px;opacity:.65;margin-top:4px;white-space:nowrap;letter-spacing:.5px}

/* Quick actions */
.dash-qactions{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.dqa-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:50px;font-size:12px;font-weight:600;border:1.5px solid var(--border);background:var(--surface);color:var(--text-2);cursor:pointer;transition:all .2s ease}
.dqa-btn svg{flex-shrink:0}
.dqa-btn:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light);transform:translateY(-1px);box-shadow:var(--shadow)}
.dqa-btn.dqa-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-color:transparent;color:#fff;box-shadow:0 4px 14px rgba(204,58,114,.35)}
.dqa-btn.dqa-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(204,58,114,.48)}

/* KPI cards */
.dash-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
@media(max-width:960px){.dash-kpis{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.dash-kpis{grid-template-columns:1fr}}
.kpi-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:18px 18px 14px;box-shadow:var(--shadow);cursor:pointer;
  transition:all .2s ease;position:relative;overflow:hidden;
}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--kpi-c,var(--primary))}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px}
.kpi-icon-wrap{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.kpi-trend-badge{font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;display:inline-flex;align-items:center;gap:3px}
.kpi-trend-badge.up{background:rgba(16,185,129,.12);color:var(--success)}
.kpi-trend-badge.down{background:rgba(216,64,64,.10);color:var(--danger)}
.kpi-trend-badge.neutral{background:var(--surface-2);color:var(--text-3)}
.kpi-val{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;line-height:1;margin-bottom:3px}
.kpi-label{font-size:12px;color:var(--text-2)}
.kpi-spark{display:flex;align-items:flex-end;gap:3px;height:28px;margin-top:12px}
.kpi-spark-b{flex:1;border-radius:2px 2px 0 0;background:var(--kpi-c,var(--primary));opacity:.25;transition:.3s}
.kpi-spark-b.hi{opacity:.85}

/* Dashboard main grid */
.dash-main{display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:20px}
@media(max-width:960px){.dash-main{grid-template-columns:1fr}}
.dash-right-col{display:flex;flex-direction:column;gap:16px}

/* Activity item */
.dash-act-item{display:flex;align-items:flex-start;gap:12px;padding:11px 0;border-bottom:1px solid var(--border)}
.dash-act-item:last-child{border-bottom:none}
.dash-act-ico{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.dash-act-title{font-size:13px;font-weight:600;line-height:1.3}
.dash-act-desc{font-size:12px;color:var(--text-2);margin-top:2px}
.dash-act-time{font-size:11px;color:var(--text-3);margin-top:3px;font-weight:500}

/* User breakdown */
.ubd-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.ubd-row:last-child{margin-bottom:0}
.ubd-label{font-size:12px;font-weight:600;width:72px;flex-shrink:0}
.ubd-bar-wrap{flex:1;height:7px;border-radius:4px;background:var(--surface-2);overflow:hidden}
.ubd-bar{height:100%;border-radius:4px;transition:.6s}
.ubd-count{font-size:12px;font-weight:700;color:var(--text-2);width:38px;text-align:right;flex-shrink:0}
.ubd-pct{font-size:10px;color:var(--text-3);width:32px;text-align:right;flex-shrink:0}

/* Enrollment bars */
.enroll-chart{display:flex;align-items:flex-end;gap:5px;height:72px;margin:14px 0 6px}
.enroll-b-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.enroll-bar-fill{width:100%;border-radius:4px 4px 0 0;background:linear-gradient(180deg,var(--primary),rgba(204,58,114,.3));transition:.4s;min-height:4px}
.enroll-b-lbl{font-size:9px;color:var(--text-3);white-space:nowrap}

/* Platform health */
.health-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border)}
.health-item:last-child{border-bottom:none}
.health-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.health-name{font-size:12px;flex:1}
.health-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px}
.hs-ok{background:rgba(16,185,129,.12);color:var(--success)}
.hs-warn{background:rgba(245,158,11,.12);color:var(--warning)}
.hs-err{background:rgba(216,64,64,.10);color:var(--danger)}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <img src="data:image/png;base64,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" style="width:38px;height:38px;border-radius:10px;object-fit:cover;flex-shrink:0">
    <div class="sidebar-brand">Learn<span style="color:var(--primary)">Flow</span></div>
  </div>

  <div id="navMenu"></div>

  <div class="sidebar-footer">
    <div class="user-card" onclick="navigate('settings')">
      <?php if ($admin_avatar_url): ?>
        <img src="<?php echo htmlspecialchars($admin_avatar_url); ?>" alt="" class="user-avatar" style="object-fit:cover;padding:0">
      <?php else: ?>
        <div class="user-avatar"><?php echo $admin_initials; ?></div>
      <?php endif; ?>
      <div class="user-info">
        <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
        <div class="user-role">Super Administrator</div>
      </div>
    </div>
    <button onclick="doLogout()" style="width:100%;margin-top:10px;padding:9px 14px;border-radius:var(--radius-sm);background:rgba(216,64,64,0.09);color:var(--danger);border:1.5px solid rgba(216,64,64,0.18);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:.2s;" onmouseover="this.style.background='rgba(216,64,64,0.16)'" onmouseout="this.style.background='rgba(216,64,64,0.09)'">
      🚪 Sign Out
    </button>
  </div>
</aside>

<div class="main-content" id="mainContent">
  <div class="topbar">
    <div class="topbar-left">
      <button class="icon-btn" onclick="toggleSidebar()" style="display:flex;align-items:center;justify-content:center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="topbar-title" id="topbarTitle">Admin Dashboard</div>
    </div>
    <div class="topbar-right">
      <div style="position:relative">
        <div class="icon-btn" onclick="toggleNotifs()" style="position:relative;display:flex;align-items:center;justify-content:center">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg><div class="notif-dot" id="notifDot"></div>
        </div>
        <div class="notif-panel" id="notifPanel">
          <div class="notif-header">
            <h3 style="font-size:14px;font-weight:700">Admin Alerts <span id="notifPanelCount" style="font-size:11px;font-weight:600;color:var(--text-3)"></span></h3>
            <span style="color:var(--primary);font-size:12px;font-weight:600;cursor:pointer" onclick="navigate('notifications')">View all</span>
          </div>
          <div id="notifList">
            <div style="text-align:center;padding:20px;color:var(--text-3);font-size:12px">Loading alerts…</div>
          </div>
        </div>
      </div>
      <div class="icon-btn" onclick="toggleDark()" id="darkBtn" style="display:flex;align-items:center;justify-content:center"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg></div>
      <div class="icon-btn" onclick="navigate('settings')" style="display:flex;align-items:center;justify-content:center"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg></div>
      <?php if ($admin_avatar_url): ?>
        <img src="<?php echo htmlspecialchars($admin_avatar_url); ?>" alt="" class="user-avatar" id="topbarAvatar" style="object-fit:cover;padding:0;cursor:pointer" onclick="navigate('settings')">
      <?php else: ?>
        <div class="user-avatar" id="topbarAvatar" style="cursor:pointer" onclick="navigate('settings')"><?php echo $admin_initials; ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="content-area fade-in" id="contentArea"></div>
</div>

<!-- ===== MODALS ===== -->

<!-- ── ENROLL STUDENT MODAL ── -->
<div class="modal-overlay" id="enrollModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h2>📋 Enroll Students</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('enrollModal')">✕</button>
    </div>

    <!-- Tab switcher -->
    <div class="tabbar" style="margin-bottom:18px" id="enrollTabBar">
      <button class="tab-btn active" onclick="switchEnrollTab('section', this)">By Student Section</button>
      <button class="tab-btn" onclick="switchEnrollTab('individual', this)">Individual Student</button>
    </div>

    <!-- ── BY STUDENT SECTION TAB ── -->
    <div id="enrollTabSection">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:16px">Pick a section, then choose which student group to bulk-enroll into it.</p>

      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">Course *</label>
        <select id="enrollBulkSectionSel" onchange="onBulkChange()"
          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text);font-size:13px">
          <option value="">— Select course —</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">Student Section to Enroll *</label>
        <select id="enrollBulkStudentSectionSel" onchange="onBulkChange()"
          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text);font-size:13px">
          <option value="">— Select student section —</option>
        </select>
      </div>
      <div id="enrollBulkPreview" style="font-size:12px;min-height:16px;margin-bottom:4px"></div>
    </div>

    <!-- ── INDIVIDUAL STUDENT TAB ── -->
    <div id="enrollTabIndividual" style="display:none">
      <p style="font-size:13px;color:var(--text-2);margin-bottom:16px">Enroll a single student into a section.</p>

      <div class="form-group" style="margin-bottom:14px">
        <label class="form-label">Course *</label>
        <select id="enrollIndSectionSel" onchange="onIndChange()"
          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text);font-size:13px">
          <option value="">— Select course —</option>
        </select>
      </div>

      <div class="form-group" id="enrollStudentGroup" style="margin-bottom:8px;display:none">
        <label class="form-label">Student *</label>
        <select id="enrollStudentSel"
          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text);font-size:13px">
          <option value="">— Select student —</option>
        </select>
      </div>
    </div>

    <div id="enrollModalMsg" style="font-size:12px;min-height:18px;margin-top:10px"></div>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('enrollModal')">Cancel</button>
      <button class="btn btn-primary" id="enrollSubmitBtn" onclick="submitEnrollAction()">Enroll</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addUserModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2>👤 Add New User</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('addUserModal')">✕</button>
    </div>

    <!-- Tab switcher -->
    <div class="tabbar" style="margin-bottom:18px" id="addUserTabBar">
      <button class="tab-btn active" onclick="switchAddTab('manual',this)">Manual Entry</button>
      <button class="tab-btn" onclick="switchAddTab('import',this)">Import from File</button>
    </div>

    <!-- ── MANUAL ENTRY TAB ── -->
    <div id="addTabManual">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">First Name *</label><input type="text" id="newFirstName" placeholder="e.g. Maria" oninput="autoFillEmail()"></div>
        <div class="form-group"><label class="form-label">Last Name *</label><input type="text" id="newLastName" placeholder="e.g. Santos" oninput="autoFillEmail()"></div>
      </div>
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;gap:6px">
          Email Address *
          <span id="emailAutoTag" style="display:none;font-size:10px;font-weight:600;letter-spacing:.4px;background:linear-gradient(135deg,var(--secondary),var(--primary));color:#fff;padding:2px 7px;border-radius:20px">✨ Auto-filled</span>
        </label>
        <input type="email" id="newUserEmail" placeholder="user@plpasig.edu.ph" oninput="onEmailManualEdit()">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Role *</label>
          <select id="newUserRole" onchange="toggleRoleFields(this.value)">
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select id="newUserStatus">
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Student-specific fields -->
      <div id="studentFields">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group"><label class="form-label">Student ID *</label>
            <div style="display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;background:var(--surface)">
              <span id="newStudentIdPrefix" style="padding:0 10px;font-family:monospace;font-size:13px;font-weight:700;color:var(--primary);background:var(--primary-light);border-right:1.5px solid var(--border);white-space:nowrap;line-height:38px"><?php echo date('y'); ?>-</span>
              <input type="text" id="newStudentIdSeq" placeholder="00001" maxlength="10" style="border:none;outline:none;flex:1;padding:0 10px;font-family:monospace;font-size:13px;background:transparent;height:38px" oninput="syncStudentId()">
              <input type="hidden" id="newStudentId">
            </div>
          </div>
          <div class="form-group"><label class="form-label">Year Level</label>
            <select id="newYearLevel" onchange="syncSection()">
              <option value="">— select —</option>
              <option>1</option><option>2</option><option>3</option><option>4</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group"><label class="form-label">Program / Course</label>
            <select id="newProgram" onchange="syncSection();syncStudentCollege()"><option value="">— loading programs —</option></select>
          </div>
          <div class="form-group"><label class="form-label">Section</label>
            <div style="display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;background:var(--surface)">
              <span id="newSectionPrefix" style="padding:0 10px;font-family:monospace;font-size:13px;font-weight:700;color:var(--primary);background:var(--primary-light);border-right:1.5px solid var(--border);white-space:nowrap;line-height:38px">—</span>
              <select id="newSectionLetter" style="border:none;outline:none;flex:1;padding:0 10px;font-size:13px;background:transparent;height:38px" onchange="syncSection()">
                <option value="">— letter —</option>
                <option>A</option><option>B</option><option>C</option><option>D</option>
                <option>E</option><option>F</option><option>G</option><option>H</option>
              </select>
              <input type="hidden" id="newSection">
            </div>
          </div>
        </div>
        <div class="form-group" id="studentCollegeRow" style="margin-top:2px">
          <label class="form-label">College</label>
          <div id="studentCollegeDisplay" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:13px;color:var(--text-2);background:var(--bg);min-height:38px;display:flex;align-items:center">— select a program first —</div>
        </div>
      </div>

      <!-- Instructor-specific fields -->
      <div id="instructorFields" style="display:none">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group"><label class="form-label">Employee ID *</label>
            <div style="display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;background:var(--surface)">
              <span style="padding:0 10px;font-family:monospace;font-size:13px;font-weight:700;color:var(--primary);background:var(--primary-light);border-right:1.5px solid var(--border);white-space:nowrap;line-height:38px">EMP-</span>
              <input type="text" id="newEmployeeIdSeq" placeholder="0001" maxlength="10" style="border:none;outline:none;flex:1;padding:0 10px;font-family:monospace;font-size:13px;background:transparent;height:38px" oninput="syncEmployeeId()">
              <input type="hidden" id="newEmployeeId">
            </div>
          </div>
          <div class="form-group"><label class="form-label">College</label>
            <select id="newDeptId">
              <option value="">— loading colleges —</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Designation</label><input type="text" id="newDesignation" placeholder="e.g. Full-time Faculty"></div>
      </div>

      <!-- Magic Link Notice -->
      <div style="background:linear-gradient(135deg, rgba(74,174,232,.12), rgba(204,58,114,.08));border:1.5px solid var(--secondary);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:12px;font-size:12px;color:var(--text-2)">
        <div style="font-weight:600;color:var(--text);margin-bottom:4px">✉️ Magic Link Login</div>
        A magic link will be sent to the user's email for activation. No manual password needed!
      </div>

      <div id="manualStatus" style="font-size:12px;min-height:18px;margin-bottom:4px"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
        <button class="btn btn-primary" onclick="createUser()" id="createUserBtn" style="display:inline-flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Create User</button>
      </div>
    </div>

    <!-- ── IMPORT FROM FILE TAB ── -->
    <div id="addTabImport" style="display:none">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap">
        <p style="font-size:13px;color:var(--text-2);margin:0;flex:1;min-width:200px">
          Upload a <strong>PDF, CSV, or TXT</strong> file containing user data. Names, emails, roles, and IDs are extracted automatically. Review and confirm before saving.
        </p>
        <button class="btn btn-outline btn-sm" onclick="downloadImportTemplate()" title="Download a pre-filled CSV template" style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0">
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          CSV Template
        </button>
      </div>

      <!-- Drop zone -->
      <div id="importDropZone" style="border:2px dashed var(--border);border-radius:12px;padding:32px;text-align:center;cursor:pointer;transition:.2s;margin-bottom:14px"
           onclick="document.getElementById('importFileInput').click()"
           ondragover="event.preventDefault();this.style.borderColor='var(--primary)'"
           ondragleave="this.style.borderColor='var(--border)'"
           ondrop="handleImportDrop(event)">
        <div style="font-size:36px;margin-bottom:8px">📄</div>
        <div style="font-weight:600;margin-bottom:4px">Drop file here or click to browse</div>
        <div style="font-size:12px;color:var(--text-3)">Supported: PDF, CSV, TXT · Max 5 MB</div>
        <input type="file" id="importFileInput" accept=".pdf,.csv,.txt" style="display:none" onchange="handleImportFile(this.files[0])">
      </div>

      <!-- File info -->
      <div id="importFileInfo" style="display:none;background:var(--surface-2);border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:12px;align-items:center;justify-content:space-between">
        <span id="importFileName" style="font-weight:600"></span>
        <button class="btn btn-ghost btn-sm" onclick="clearImport()">✕ Remove</button>
      </div>

      <!-- Parsing progress -->
      <div id="importProgress" style="display:none;margin-bottom:12px">
        <div style="font-size:13px;color:var(--text-2);margin-bottom:6px" id="importProgressLabel">Extracting data from file…</div>
        <div style="height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden">
          <div id="importProgressBar" style="height:100%;background:linear-gradient(90deg,var(--primary),var(--primary-dark));border-radius:3px;width:0%;transition:width .4s"></div>
        </div>
      </div>

      <!-- Preview table -->
      <div id="importPreview" style="display:none">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <div style="font-size:13px;font-weight:600" id="importPreviewLabel">0 users found</div>
          <div style="display:flex;gap:6px">
            <button class="btn btn-ghost btn-sm" onclick="clearImport()" style="display:inline-flex;align-items:center;gap:5px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg> Clear</button>
          </div>
        </div>
        <div class="table-wrap" style="max-height:240px;overflow-y:auto">
          <table id="importPreviewTable" style="font-size:12px">
            <thead><tr>
              <th><input type="checkbox" checked onchange="toggleAllImport(this)"></th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>ID No.</th>
              <th>Status</th>
              <th>Program / College</th>
              <th>⚠</th>
            </tr></thead>
            <tbody id="importPreviewBody"></tbody>
          </table>
        </div>
        <div id="importErrors" style="margin-top:8px;font-size:12px;color:var(--danger)"></div>
      </div>

      <div id="importStatus" style="font-size:12px;min-height:18px;margin-bottom:4px"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
        <button class="btn btn-primary" id="importSaveBtn" onclick="saveImportedUsers()" style="display:none">💾 Save <span id="importSaveCount">0</span> Users</button>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addCourseModal">
  <div class="modal">
    <div class="modal-header">
      <h2 id="addCourseModalTitle">📚 Add New Course</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('addCourseModal')">✕</button>
    </div>
    <input type="hidden" id="editCourseId" value="">
    <div class="form-group">
      <label class="form-label">Course Title <span style="color:var(--danger)">*</span></label>
      <input type="text" id="newCourseTitle" placeholder="e.g. Advanced Web Technologies">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label class="form-label">Course Code <span style="color:var(--danger)">*</span></label>
        <input type="text" id="newCourseCode" placeholder="e.g. CS 401" style="text-transform:uppercase">
      </div>
      <div class="form-group">
        <label class="form-label">Units <span style="color:var(--danger)">*</span></label>
        <select id="newCourseUnits">
          <option value="1">1 Unit</option>
          <option value="2">2 Units</option>
          <option value="3" selected>3 Units</option>
          <option value="4">4 Units</option>
          <option value="5">5 Units</option>
          <option value="6">6 Units (Lab)</option>
        </select>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label class="form-label">College</label>
        <select id="newCourseDept" onchange="onCourseDeptChange()">
          <option value="">— Select College —</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="newCourseStatus">
          <option value="draft">Draft</option>
          <option value="published" selected>Published</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Assign Instructor</label>
      <select id="newCourseInstructor">
        <option value="">— Select a college first —</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea id="newCourseDesc" placeholder="Brief course description..."></textarea>
    </div>
    <div id="addCourseStatus" style="min-height:18px;font-size:13px;margin:4px 0"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('addCourseModal')">Cancel</button>
      <button class="btn btn-primary" id="saveCourseBtn" onclick="saveCourse()" style="display:inline-flex;align-items:center;gap:6px">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create Course
      </button>
    </div>
  </div>
</div>

<!-- Confirm Delete Course Modal -->
<div class="modal-overlay" id="delCourseModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h2>🗑 Delete Course</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('delCourseModal')">✕</button>
    </div>
    <p style="margin:12px 0;font-size:14px;color:var(--text-2)">
      Permanently delete <strong id="delCourseNameLabel"></strong>?<br>
      <span style="font-size:12px;color:var(--danger)">This cannot be undone. Courses with active enrollments cannot be deleted — archive them instead.</span>
    </p>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" onclick="closeModal('delCourseModal')">Cancel</button>
      <button class="btn btn-danger btn-sm" onclick="confirmDeleteCourse()" style="display:inline-flex;align-items:center;gap:6px">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        Delete
      </button>
    </div>
  </div>
</div>

<!-- Archive Course Modal -->
<div class="modal-overlay" id="archiveCourseModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <h2 id="archiveCourseTitle">🗄 Archive Course</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('archiveCourseModal')">✕</button>
    </div>
    <p id="archiveCourseMsg" style="margin:10px 0 14px;font-size:14px;color:var(--text-2)"></p>
    <div id="archiveReasonWrap" class="form-group">
      <label class="form-label">Reason (optional)</label>
      <input type="text" id="archiveCourseReason" placeholder="e.g. End of semester, curriculum change…">
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" onclick="closeModal('archiveCourseModal')">Cancel</button>
      <button class="btn btn-primary btn-sm" id="archiveCourseBtn" onclick="confirmArchiveCourse()" style="display:inline-flex;align-items:center;gap:6px">Confirm</button>
    </div>
  </div>
</div>

<!-- View Course Modal -->
<div class="modal-overlay" id="viewCourseModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h2 id="viewCourseTitle">📚 Course Details</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('viewCourseModal')">✕</button>
    </div>
    <div id="viewCourseContent"></div>
    <div class="modal-footer" id="viewCourseFooter">
      <button class="btn btn-outline btn-sm" onclick="closeModal('viewCourseModal')">Close</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="announceModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2>📢 Send Announcement</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('announceModal')">✕</button>
    </div>
    <div class="form-group"><label class="form-label">Title</label><input type="text" id="annModalTitle" placeholder="Announcement title..."></div>
    <div class="form-group">
      <label class="form-label">Target Users</label>
      <div style="margin-bottom:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button type="button" class="btn btn-outline btn-sm" onclick="annSelectAll()">All Users</button>
        <button type="button" class="btn btn-outline btn-sm" onclick="annSelectByRole('student')">Students Only</button>
        <button type="button" class="btn btn-outline btn-sm" onclick="annSelectByRole('instructor')">Instructors Only</button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="annClearAll()">Clear</button>
      </div>
      <div style="position:relative;margin-bottom:6px">
        <input type="text" id="annUserSearch" placeholder="Search users by name or email..." oninput="filterAnnUsers()"
          style="width:100%;padding:9px 12px 9px 34px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;transition:.2s"
          onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);opacity:.5;font-size:14px;pointer-events:none">🔍</span>
      </div>
      <div id="annUserList" style="max-height:200px;overflow-y:auto;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);padding:6px">
        <div style="text-align:center;color:var(--text-3);padding:20px;font-size:13px">Loading users…</div>
      </div>
      <div id="annSelectedCount" style="font-size:12px;color:var(--text-2);margin-top:6px">0 users selected (send to all if none selected)</div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group"><label class="form-label">Priority</label><select id="annModalPriority"><option value="normal">Normal</option><option value="important">Important</option><option value="urgent">Urgent</option></select></div>
      <div class="form-group"><label class="form-label">Schedule (optional)</label><input type="datetime-local" id="annModalSchedule"></div>
    </div>
    <div class="form-group"><label class="form-label">Message</label><textarea id="annModalBody" style="min-height:120px" placeholder="Write your announcement here..."></textarea></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('announceModal')">Cancel</button>
      <button class="btn btn-primary" onclick="sendAdminAnnouncement()">📢 Send Now</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="viewUserModal">
  <div class="modal">
    <div class="modal-header">
      <h2 id="viewUserTitle">User Details</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('viewUserModal')">✕</button>
    </div>
    <div id="viewUserContent"></div>
    <div id="viewUserStatusMessage" style="font-size:12px;min-height:18px;margin-top:10px;color:var(--danger)"></div>
    <div class="modal-footer">
      <button class="btn btn-danger btn-sm" id="viewUserDeleteBtn" onclick="deleteUser()" style="display:inline-flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg> Delete</button>
      <button class="btn btn-danger btn-sm" id="viewUserSuspendBtn" onclick="toggleUserStatus(activeUserId)" style="display:inline-flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg> Suspend</button>
      <button class="btn btn-outline btn-sm" id="viewUserEnrollHistBtn" style="display:none;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg> Enrollment History</button>
      <button class="btn btn-outline" onclick="closeModal('viewUserModal')">Close</button>
      <button class="btn btn-primary btn-sm" id="saveUserBtn" onclick="saveUserChanges()" style="display:inline-flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save</button>
    </div>
  </div>
</div>


<!-- ── STUDENT ENROLLMENT HISTORY MODAL ── -->
<div class="modal-overlay" id="studentEnrollHistoryModal">
  <div class="modal" style="max-width:680px">
    <div class="modal-header">
      <h2>📋 Enrollment History — <span id="enrollHistoryStudentName"></span></h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('studentEnrollHistoryModal')">✕</button>
    </div>

    <!-- Summary chips -->
    <div id="enrollHistorySummary" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"></div>

    <!-- Table -->
    <div class="table-wrap" style="max-height:420px;overflow-y:auto">
      <table>
        <thead>
          <tr>
            <th>Course</th>
            <th>Section</th>
            <th>Term</th>
            <th>Instructor</th>
            <th>Date Enrolled</th>
            <th>Status</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody id="enrollHistoryTbody">
          <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-3)">Loading…</td></tr>
        </tbody>
      </table>
    </div>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('studentEnrollHistoryModal')">Close</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="viewAnnModal">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <h2>📢 Announcement Detail</h2>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('viewAnnModal')">✕</button>
    </div>
    <div id="viewAnnContent"></div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" onclick="closeModal('viewAnnModal')">Close</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ===== STATE =====
let sidebarCollapsed = false;
let darkMode = true;
let currentPage = 'dashboard';
let usersFilter = 'all';
let selectedUsers = [];
let activeUserId = null;
let _adminAvatarUrl = '<?php echo addslashes($admin_avatar_url); ?>';

// ===== ICON LIBRARY =====
const IC = {
  home:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>`,
  analytics:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`,
  users:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>`,
  courses:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>`,
  enrollments:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>`,
  departments:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>`,
  announcements:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 17H2a3 3 0 000 6h20a3 3 0 000-6z"/><path d="M13 10H2l10-9v9z"/></svg>`,
  bell:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>`,
  settings:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>`,
  moon:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>`,
  sun:`<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`,
  menu:`<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>`,
  userPlus:`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>`,
  alert:`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  storage:`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>`,
  check:`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
};

// ===== NAV CONFIG =====
const navItems = [
  { id:'dashboard',     label:'Dashboard',      icon:IC.home },
  { id:'users',         label:'User Management',icon:IC.users },
  { id:'courses',       label:'Course Registry', icon:IC.courses },
  { id:'enrollments',   label:'Enrollments',    icon:IC.enrollments },
  { id:'departments',   label:'Colleges',       icon:IC.departments },
  { id:'announcements', label:'Announcements',  icon:IC.announcements },
  { id:'notifications', label:'Notifications',  icon:IC.bell },
  { id:'settings',      label:'Admin Settings', icon:IC.settings },
];

const pageTitles = {
  dashboard:'Admin Dashboard', users:'User Management',
  courses:'Course Registry', enrollments:'Enrollment Management', departments:'Colleges',
  announcements:'Announcements', reports:'Reports & Flags',
  audit:'Audit Logs', backup:'Backup & Data', notifications:'Notifications', settings:'Admin Settings'
};

// ===== NAV RENDER =====
function renderNav() {
  document.getElementById('navMenu').innerHTML = navItems.map(item => `
    <div class="nav-item${item.id===currentPage?' active':''}" onclick="navigate('${item.id}')" data-nav="${item.id}">
      <span class="nav-icon">${item.icon}</span>
      <span class="nav-label">${item.label}</span>
      ${item.badge ? `<span class="nav-badge">${item.badge}</span>` : ''}
    </div>
  `).join('');
}

async function navigate(id) {
  currentPage = id;
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  const el = document.querySelector(`[data-nav="${id}"]`);
  if (el) el.classList.add('active');
  document.getElementById('topbarTitle').textContent = pageTitles[id] || id;
  const ca = document.getElementById('contentArea');
  ca.classList.remove('fade-in');
  setTimeout(async () => {
    if (id === 'users') {
      await loadUsersFromDB();
    } else if (id === 'enrollments') {
      await loadEnrollmentsFromDB();
    }
    ca.classList.add('fade-in');
    renderContent(id);
  }, 50);
  notifOpen = false;
  document.getElementById('notifPanel').classList.remove('open');
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('open');
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

// ===== DARK MODE =====
// Listen for live theme updates broadcast by admin (other tabs / same tab)
(function _lfSetupThemeListener() {
  function _applyFromStorage() {
    try {
      const raw = localStorage.getItem('lf-theme-data');
      if (raw && typeof _applyThemeVars === 'function') _applyThemeVars(JSON.parse(raw));
    } catch(e) {}
  }
  // BroadcastChannel — instant, same-origin cross-tab
  try {
    const _bc = new BroadcastChannel('lf-theme');
    _bc.onmessage = (e) => {
      if (e.data && e.data.type === 'theme-update') {
        try { _applyThemeVars(JSON.parse(e.data.theme)); } catch(err) {}
      }
    };
  } catch(e) {}
  // storage event — fires in OTHER tabs when localStorage changes
  window.addEventListener('storage', (e) => {
    if (e.key === 'lf-theme-data' && e.newValue) {
      try { _applyThemeVars(JSON.parse(e.newValue)); } catch(err) {}
    }
  });
  // Apply on load in case a theme was saved before this tab opened
  document.addEventListener('DOMContentLoaded', _applyFromStorage);
})();

document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('admin-theme');
  darkMode = saved ? saved === 'dark' : true;
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('darkBtn').innerHTML = darkMode ? IC.sun : IC.moon;
  renderNav();
  navigate('dashboard');
  loadUsersFromDB();
  initNotifBadge();
});

function toggleDark() {
  darkMode = !darkMode;
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('darkBtn').innerHTML = darkMode ? IC.sun : IC.moon;
  localStorage.setItem('admin-theme', darkMode ? 'dark' : 'light');
}

// ===== NOTIFS =====
let notifOpen = false;
function _getReadNotifIds() {
  try { return new Set(JSON.parse(localStorage.getItem('admin_read_notif_ids') || '[]')); }
  catch(e) { return new Set(); }
}
function _saveReadNotifIds(ids) {
  try { localStorage.setItem('admin_read_notif_ids', JSON.stringify([...ids])); } catch(e) {}
}
function _updateBellDot(unreadCount) {
  const dot = document.getElementById('notifDot');
  const countEl = document.getElementById('notifPanelCount');
  if (!dot) return;
  if (unreadCount > 0) {
    dot.style.display = 'flex';
    dot.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
  } else {
    dot.style.display = 'none';
    dot.textContent = '';
  }
  if (countEl) countEl.textContent = unreadCount > 0 ? `(${unreadCount} new)` : '';
}
function toggleNotifs() {
  notifOpen = !notifOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifOpen);
  if (notifOpen) loadTopbarNotifs(true);
}
async function loadTopbarNotifs(force) {
  const list = document.getElementById('notifList');
  if (!list) return;
  list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-3);font-size:12px">Loading alerts…</div>';
  try {
    const fd = new FormData(); fd.append('action','admin_notifications');
    const res = await fetch('', {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
    const data = await res.json();
    if (!data.success || !data.notifications || !data.notifications.length) {
      list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-3);font-size:12px">No recent alerts</div>';
      _updateBellDot(0);
      return;
    }
    const readIds = _getReadNotifIds();
    const iMap = {warn:'⚠️',error:'🚨'};
    const notifs = data.notifications.slice(0,5).map(n => ({...n, is_unread: !readIds.has(n.id)}));
    list.innerHTML = notifs.map(n => {
      const ic = n.icon || iMap[n.priority] || '🔔';
      const msg = n.message.length > 48 ? n.message.slice(0,48)+'…' : n.message;
      return `<div class="notif-item${n.is_unread?' unread':''}" onclick="markOneNotifRead(${JSON.stringify(n.id)},this);navigate('notifications')" style="cursor:pointer">
        <div class="notif-icon" style="background:rgba(124,58,237,.14);display:flex;align-items:center;justify-content:center;font-size:14px">${ic}</div>
        <div style="flex:1">
          <div style="font-size:12px;font-weight:700">${n.title}</div>
          <div class="notif-time">${msg}</div>
          <div class="notif-time" style="font-size:10px;margin-top:2px">${_timeAgo(n.created_at)}</div>
        </div>
      </div>`;
    }).join('');
    const unreadCount = data.notifications.filter(n => !readIds.has(n.id)).length;
    _updateBellDot(unreadCount);
  } catch(e) {
    list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-3);font-size:12px">Failed to load</div>';
  }
}
async function initNotifBadge() {
  try {
    const fd = new FormData(); fd.append('action','admin_notifications');
    const res = await fetch('', {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
    const data = await res.json();
    if (!data.success || !data.notifications) { _updateBellDot(0); return; }
    const readIds = _getReadNotifIds();
    const unreadCount = data.notifications.filter(n => !readIds.has(n.id)).length;
    _updateBellDot(unreadCount);
  } catch(e) { _updateBellDot(0); }
}
function markOneNotifRead(id, el) {
  const readIds = _getReadNotifIds();
  if (readIds.has(id)) return; // already read — nothing to do
  readIds.add(id);
  _saveReadNotifIds(readIds);

  // Update bell count immediately
  const dot = document.getElementById('notifDot');
  const current = dot ? parseInt(dot.textContent, 10) || 0 : 0;
  const next = Math.max(0, current - 1);
  _updateBellDot(next);

  // Update the panel count label
  const countEl = document.getElementById('notifPanelCount');
  if (countEl && next > 0) countEl.textContent = `(${next} new)`;
  else if (countEl) countEl.textContent = '';

  // Update the clicked element's visual state inline (no full re-render)
  if (el) {
    el.style.background = 'transparent';
    el.onmouseleave = () => { el.style.background = 'transparent'; };
    const badge = el.querySelector('.badge-red');
    if (badge) badge.remove();
    // Also remove the unread class if present (topbar panel items)
    el.classList.remove('unread');
    // Update the page header count
    const hdr = document.querySelector('.notif-page-hdr');
    if (hdr) {
      const u = Math.max(0, next);
      hdr.textContent = u + ' unread alert' + (u !== 1 ? 's' : '');
    }
  }
}
async function markAllNotifsRead() {
  try {
    const fd = new FormData(); fd.append('action','admin_notifications');
    const res = await fetch('', {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
    const data = await res.json();
    if (data.success && data.notifications) {
      const readIds = _getReadNotifIds();
      data.notifications.forEach(n => readIds.add(n.id));
      _saveReadNotifIds(readIds);
    }
  } catch(e) {}
  _updateBellDot(0);
  await loadNotificationsPage();
  showToast('All notifications marked as read', 'success');
}
async function loadDashboardKPIs() {
  try {
    const hdr = {'X-Requested-With':'XMLHttpRequest'};
    const fd = new FormData(); fd.append('action','admin_dashboard_kpis');
    const res = await fetch('', {method:'POST', headers:hdr, body:fd});
    const data = await res.json();
    if (!data.success) return;
    const k = data.kpis;
    const set = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
    const css = (id, prop, val) => { const e = document.getElementById(id); if (e) e.style[prop] = val; };
    // 4 KPI cards + hero bar
    set('dash-hero-users',          (+k.usersTotal).toLocaleString());
    set('dash-hero-courses',        (+k.coursesActive).toLocaleString());
    set('dash-hero-enrollments',    (+k.enrollTotal).toLocaleString());
    set('dash-kpi-users-val',       (+k.usersTotal).toLocaleString());
    set('dash-kpi-courses-val',     (+k.coursesActive).toLocaleString());
    set('dash-kpi-enrollments-val', (+k.enrollTotal).toLocaleString());
    set('dash-kpi-depts-val',       (+k.deptsTotal).toLocaleString());
    // User Breakdown
    const tot = +k.usersTotal || 1;
    const s = +k.usersByRole.student, i = +k.usersByRole.instructor, a = +k.usersByRole.admin;
    const sp = Math.round(s/tot*100), ip = Math.round(i/tot*100), ap = Math.round(a/tot*100);
    set('dash-ubd-total',  tot.toLocaleString()+' total');
    set('dash-ubd-cnt-s', s.toLocaleString()); set('dash-ubd-pct-s', sp+'%'); css('dash-ubd-bar-s','width',sp+'%');
    set('dash-ubd-cnt-i', i.toLocaleString()); set('dash-ubd-pct-i', ip+'%'); css('dash-ubd-bar-i','width',ip+'%');
    set('dash-ubd-cnt-a', a.toLocaleString()); set('dash-ubd-pct-a', ap+'%'); css('dash-ubd-bar-a','width',ap+'%');
    // Recent activity (fire-and-forget)
    loadRecentActivity();
  } catch(e) { console.warn('loadDashboardKPIs:', e); }
}
function _timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (diff < 60)    return diff + 's ago';
  if (diff < 3600)  return Math.floor(diff/60) + ' min ago';
  if (diff < 86400) return Math.floor(diff/3600) + ' hr' + (Math.floor(diff/3600)>1?'s':'') + ' ago';
  return Math.floor(diff/86400) + ' day' + (Math.floor(diff/86400)>1?'s':'') + ' ago';
}
function _actIcon(type, c) {
  const p = `width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="${c}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;
  if (type==='success') return `<svg ${p}><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`;
  if (type==='warn')    return `<svg ${p}><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
  if (type==='primary') return `<svg ${p}><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>`;
  return `<svg ${p}><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>`;
}
async function loadRecentActivity() {
  const container = document.getElementById('dash-recent-activity');
  if (!container) return;
  try {
    const fd = new FormData(); fd.append('action','admin_recent_activity');
    const res = await fetch('', {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
    const data = await res.json();
    if (!data.success || !data.activity.length) {
      container.innerHTML = '<div style="color:var(--text-3);font-size:12px;padding:14px 0;text-align:center">No recent activity found</div>';
      return;
    }
    const colorMap = {
      success: ['rgba(16,185,129,.14)','#10b981'],
      info:    ['rgba(59,130,246,.14)', '#3B82F6'],
      warn:    ['rgba(239,68,68,.14)',  '#ef4444'],
      primary: ['rgba(204,58,114,.14)','#CC3A72'],
    };
    container.innerHTML = data.activity.map(a => {
      const [bg, c] = colorMap[a.type] || colorMap.info;
      return `<div class="dash-act-item">
        <div class="dash-act-ico" style="background:${bg}">${_actIcon(a.type, c)}</div>
        <div style="flex:1;min-width:0">
          <div class="dash-act-title">${a.title}</div>
          <div class="dash-act-desc">${a.desc}</div>
          <div class="dash-act-time">${_timeAgo(a.created_at)}</div>
        </div>
      </div>`;
    }).join('');
  } catch(e) {
    console.warn('loadRecentActivity:', e);
    const container2 = document.getElementById('dash-recent-activity');
    if (container2) container2.innerHTML = '<div style="color:var(--text-3);font-size:12px;padding:14px 0;text-align:center">Unable to load activity</div>';
  }
}
async function loadNotificationsPage() {
  const el = document.getElementById('notif-page-list');
  if (!el) return;
  try {
    const fd = new FormData(); fd.append('action','admin_notifications');
    const res = await fetch('', {method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
    const data = await res.json();
    if (!data.success || !data.notifications || !data.notifications.length) {
      el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-3)">No notifications found</div>';
      return;
    }
    const readIds = _getReadNotifIds();
    const iMap = {audit:'📋',announcements:'📢',warn:'⚠️',error:'🚨'};
    el.innerHTML = data.notifications.map(n => {
      const ic = n.icon || iMap[n.category] || iMap[n.priority] || '🔔';
      const unread = !readIds.has(n.id);
      const bg = unread ? 'var(--primary-light)' : 'transparent';
      const nidJson = JSON.stringify(n.id);
      return `<div style="display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;background:${bg}"
        onclick="markOneNotifRead(${nidJson},this)"
        onmouseenter="this.style.background='var(--surface-2)'"
        onmouseleave="this.style.background=_getReadNotifIds().has(${nidJson})?'transparent':'var(--primary-light)'">
        <div style="width:38px;height:38px;border-radius:10px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">${ic}</div>
        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
            <span style="font-weight:700;font-size:13px">${n.title}</span>
            ${unread?'<span class="badge badge-red" style="font-size:9px;padding:2px 7px">New</span>':''}
          </div>
          <div style="font-size:12px;color:var(--text-2);line-height:1.5">${n.message}</div>
          <div style="font-size:10px;color:var(--text-3);margin-top:3px">${_timeAgo(n.created_at)}</div>
        </div>
      </div>`;
    }).join('');
    const unreadCount = data.notifications.filter(n => !readIds.has(n.id)).length;
    const hdr = document.querySelector('.notif-page-hdr');
    if (hdr) hdr.textContent = unreadCount+' unread alert'+(unreadCount!==1?'s':'');
    _updateBellDot(unreadCount);
  } catch(e) {
    if (el) el.innerHTML = '<div style="text-align:center;padding:40px;color:var(--danger)">Failed to load notifications</div>';
  }
}
document.addEventListener('click', e => {
  if (!e.target.closest('#notifPanel') && !e.target.closest('.icon-btn')) {
    document.getElementById('notifPanel').classList.remove('open');
    notifOpen = false;
  }
});

// ===== MODAL =====
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openAddUserModal(tab) {
  resetAddUserForm();
  clearImport();
  _nextIds = null; // reset ID cache so fresh sequence is fetched
  loadDepartments();
  loadPrograms('newProgram').then(syncSection);
  openModal('addUserModal');
  // Switch to correct tab after modal opens
  setTimeout(() => {
    const tabBtn = [...document.querySelectorAll('#addUserTabBar .tab-btn')]
      .find(b => b.textContent.toLowerCase().includes(tab === 'import' ? 'import' : 'manual'));
    switchAddTab(tab, tabBtn);
  }, 10);
}

// ===== TOAST =====
function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast' + (type ? ' ' + type : '') + ' show';
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ===== USER DATA (loaded from DB via PHP) =====
let users = [];

async function loadUsersFromDB() {
  try {
    const res = await fetch('?action=get_users', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) {
      users = data.users;
      // Re-render if already on users page
      if (currentPage === 'users') {
        document.getElementById('usersTbody').innerHTML = users.map(u => userRow(u)).join('');
        updateUserStats();
      }
    }
  } catch (e) {
    console.error('Failed to load users', e);
  }
}

// Load departments from database
async function loadDepartments(selectId = 'newDeptId', selectedId = '') {
  try {
    const res = await fetch('?action=get_departments', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success && data.departments) {
      const deptSelect = document.getElementById(selectId);
      if (!deptSelect) return;
      deptSelect.innerHTML = '<option value="">— select —</option>' +
        data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
      if (selectedId) deptSelect.value = selectedId;
    }
  } catch (e) {
    console.error('Failed to load departments', e);
  }
}

// Load programs from database
async function loadPrograms(selectId = 'newProgram', selectedCode = '') {
  try {
    const res = await fetch('?action=get_programs', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success && data.programs) {
      const sel = document.getElementById(selectId);
      if (!sel) return;
      // Group by college
      const byCollege = {};
      data.programs.forEach(p => {
        if (!byCollege[p.college]) byCollege[p.college] = [];
        byCollege[p.college].push(p);
      });
      sel.innerHTML = '<option value="">— select program —</option>' +
        Object.entries(byCollege).map(([college, progs]) =>
          `<optgroup label="${college}">` +
          progs.map(p => `<option value="${p.code}" data-college="${p.college}">${p.code} — ${p.name}</option>`).join('') +
          `</optgroup>`
        ).join('');
      if (selectedCode) sel.value = selectedCode;
    }
  } catch (e) {
    console.error('Failed to load programs', e);
  }
}

function updateUserStats() {
  const stats = {
    total: users.length,
    students: users.filter(u => u.role === 'student').length,
    instructors: users.filter(u => u.role === 'instructor').length,
    admins: users.filter(u => u.role === 'admin').length,
  };
  document.querySelectorAll('[data-user-stat]').forEach(el => {
    el.textContent = stats[el.dataset.userStat] ?? el.textContent;
  });
}

// ===== COURSES DATA =====
const allCourses = [
  { code:'CS 311', title:'Object-Oriented Programming', instructor:'Prof. Lemuel Duran', enrolled:42, capacity:50, status:'active', semester:'1st 2025-26' },
  { code:'IT 301', title:'Web Programming', instructor:'Prof. Ana Reyes', enrolled:38, capacity:45, status:'active', semester:'1st 2025-26' },
  { code:'CS 312', title:'Data Structures & Algorithms', instructor:'Prof. Lemuel Duran', enrolled:35, capacity:50, status:'active', semester:'1st 2025-26' },
  { code:'CS 322', title:'Web Technologies', instructor:'Prof. Leo Ramos', enrolled:29, capacity:40, status:'active', semester:'1st 2025-26' },
  { code:'IT 411', title:'Capstone Project 1', instructor:'Prof. Ana Reyes', enrolled:22, capacity:30, status:'active', semester:'1st 2025-26' },
  { code:'CS 201', title:'Discrete Mathematics', instructor:'Prof. Leo Ramos', enrolled:48, capacity:50, status:'active', semester:'1st 2025-26' },
  { code:'IT 201', title:'Computer Organization', instructor:'Prof. Carlo Tan', enrolled:0, capacity:45, status:'inactive', semester:'2nd 2025-26' },
];

// ===== CONTENT ROUTER =====
function renderContent(id) {
  const el = document.getElementById('contentArea');
  const renders = {
    dashboard: renderDashboard,
    users: renderUsers,
    courses: renderCourses,
    enrollments: renderEnrollments,
    departments: renderDepartments,
    announcements: renderAnnouncements,
    notifications: renderNotifications,
    settings: renderSettings,
  };
  el.innerHTML = (renders[id] || (() => `<div class="card"><p>Page coming soon.</p></div>`))();
  if (id === 'announcements') renderAnnRows();
}

// ===== DASHBOARD =====
function renderDashboard() {
  const h = new Date().getHours();
  const greeting = h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
  const dateStr = new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
  const totalUsers = users.length || 1284;
  const students   = users.filter(u=>u.role==='student').length  || 1198;
  const instructors= users.filter(u=>u.role==='instructor').length || 74;
  const admins     = users.filter(u=>u.role==='admin').length    || 12;

  const sparkData = {
    users:      [60,72,58,80,70,85,78,90,68,88,75,95],
    courses:    [30,35,38,40,42,42,44,45,46,46,47,47],
    enrollments:[200,240,210,290,270,310,280,340,300,360,330,385],
    depts:      [4,4,5,5,5,6,6,6,6,7,7,7],
  };
  function spark(data, kpiC) {
    const max = Math.max(...data);
    return data.map((v,i) => `<div class="kpi-spark-b${i===data.length-1?' hi':''}" style="height:${Math.round(v/max*100)}%;background:${kpiC}"></div>`).join('');
  }

  setTimeout(loadDashboardKPIs, 0);
  return `
  <!-- ══ HERO ══ -->
  <div class="dash-hero">
    <div class="dash-hero-orb dash-orb-1"></div>
    <div class="dash-hero-orb dash-orb-2"></div>
    <div class="dash-hero-orb dash-orb-3"></div>
    <div class="dash-hero-left">
      <div class="dash-hero-eyebrow">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Admin Control Panel
      </div>
      <div class="dash-hero-title">${greeting}, <strong><?php echo htmlspecialchars(explode(' ',$admin_name)[0]); ?></strong></div>
      <div class="dash-hero-date">${dateStr}</div>
    </div>
    <div class="dash-hero-right">
      <div class="dash-hm"><div class="dash-hm-val" id="dash-hero-users">—</div><div class="dash-hm-lbl">Total Users</div></div>
      <div class="dash-hm"><div class="dash-hm-val" id="dash-hero-courses">—</div><div class="dash-hm-lbl">Courses</div></div>
      <div class="dash-hm"><div class="dash-hm-val" id="dash-hero-enrollments">—</div><div class="dash-hm-lbl">Enrollments</div></div>
      <div class="dash-hm"><div class="dash-hm-val">98.7%</div><div class="dash-hm-lbl">Uptime</div></div>
    </div>
  </div>

  <!-- ══ QUICK ACTIONS ══ -->
  <div class="dash-qactions">
    <button class="dqa-btn dqa-primary" onclick="openAddUserModal('manual')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
      Add User
    </button>
    <button class="dqa-btn" onclick="openAddCourseModal()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
      New Course
    </button>
    <button class="dqa-btn" onclick="openModal('announceModal')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
      Broadcast
    </button>
    <button class="dqa-btn" onclick="navigate('departments')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
      Colleges
    </button>
    <button class="dqa-btn" onclick="navigate('enrollments')">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Enrollments
    </button>
  </div>

  <!-- ══ KPI CARDS ══ -->
  <div class="dash-kpis">
    <div class="kpi-card" style="--kpi-c:#CC3A72" onclick="navigate('users')">
      <div class="kpi-top">
        <div class="kpi-icon-wrap" style="background:rgba(204,58,114,.12)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#CC3A72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <span class="kpi-trend-badge up" id="dash-kpi-users-trend">↑ +12</span>
      </div>
      <div class="kpi-val" id="dash-kpi-users-val">—</div>
      <div class="kpi-label">Total Users</div>
      <div class="kpi-spark">${spark(sparkData.users,'#CC3A72')}</div>
    </div>

    <div class="kpi-card" style="--kpi-c:#10B981" onclick="navigate('courses')">
      <div class="kpi-top">
        <div class="kpi-icon-wrap" style="background:rgba(16,185,129,.12)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
        </div>
        <span class="kpi-trend-badge up" id="dash-kpi-courses-trend">↑ +3</span>
      </div>
      <div class="kpi-val" id="dash-kpi-courses-val">—</div>
      <div class="kpi-label">Active Courses</div>
      <div class="kpi-spark">${spark(sparkData.courses,'#10B981')}</div>
    </div>

    <div class="kpi-card" style="--kpi-c:#3B82F6" onclick="navigate('enrollments')">
      <div class="kpi-top">
        <div class="kpi-icon-wrap" style="background:rgba(59,130,246,.12)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <span class="kpi-trend-badge up" id="dash-kpi-enrollments-trend">↑ +148</span>
      </div>
      <div class="kpi-val" id="dash-kpi-enrollments-val">—</div>
      <div class="kpi-label">Total Enrollments</div>
      <div class="kpi-spark">${spark(sparkData.enrollments,'#3B82F6')}</div>
    </div>

    <div class="kpi-card" style="--kpi-c:#8B5CF6" onclick="navigate('departments')">
      <div class="kpi-top">
        <div class="kpi-icon-wrap" style="background:rgba(139,92,246,.12)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
        </div>
        <span class="kpi-trend-badge neutral">Stable</span>
      </div>
      <div class="kpi-val" id="dash-kpi-depts-val">—</div>
      <div class="kpi-label">Colleges</div>
      <div class="kpi-spark">${spark(sparkData.depts,'#8B5CF6')}</div>
    </div>
  </div>

  <!-- ══ MAIN GRID ══ -->
  <div class="dash-main">

    <!-- Recent Activity -->
    <div class="card">
      <div class="section-header">
        <div class="section-title">Recent Activity</div>
        <span class="badge badge-gray">Live</span>
      </div>
      <div id="dash-recent-activity">
        <div style="color:var(--text-3);font-size:12px;padding:14px 0;text-align:center">Loading activity…</div>
      </div>
    </div>

    <!-- Right column -->
    <div class="dash-right-col">

      <!-- User Breakdown -->
      <div class="card">
        <div class="section-header">
          <div class="section-title">User Breakdown</div>
          <span class="badge badge-purple" id="dash-ubd-total">— total</span>
        </div>
        <div class="ubd-row">
          <div class="ubd-label">Students</div>
          <div class="ubd-bar-wrap"><div class="ubd-bar" id="dash-ubd-bar-s" style="width:0%;background:linear-gradient(90deg,#10B981,#047857)"></div></div>
          <div class="ubd-count" id="dash-ubd-cnt-s">—</div>
          <div class="ubd-pct" id="dash-ubd-pct-s">—%</div>
        </div>
        <div class="ubd-row">
          <div class="ubd-label">Instructors</div>
          <div class="ubd-bar-wrap"><div class="ubd-bar" id="dash-ubd-bar-i" style="width:0%;background:linear-gradient(90deg,#F59E0B,#B45309)"></div></div>
          <div class="ubd-count" id="dash-ubd-cnt-i">—</div>
          <div class="ubd-pct" id="dash-ubd-pct-i">—%</div>
        </div>
        <div class="ubd-row">
          <div class="ubd-label">Admins</div>
          <div class="ubd-bar-wrap"><div class="ubd-bar" id="dash-ubd-bar-a" style="width:0%;background:linear-gradient(90deg,#CC3A72,#a82860)"></div></div>
          <div class="ubd-count" id="dash-ubd-cnt-a">—</div>
          <div class="ubd-pct" id="dash-ubd-pct-a">—%</div>
        </div>
      </div>

    </div>
  </div>`;
}

// ===== USER MANAGEMENT =====
function renderUsers() {
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>User Management</span></div>
    <h1>User Management</h1>
    <p>Manage all students, instructors, and admin accounts</p>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
    ${[
      ['👥','Total Users','total','badge-purple'],
      ['🎓','Students','students','badge-green'],
      ['👨‍🏫','Instructors','instructors','badge-amber'],
      ['🛡','Admins','admins','badge-blue'],
    ].map(([i,l,key,b])=>`
      <div class="stat-card">
        <div class="stat-icon" style="background:var(--surface-2);font-size:20px">${i}</div>
        <div>
          <div class="stat-val" data-user-stat="${key}">${({total:users.length,students:users.filter(u=>u.role==='student').length,instructors:users.filter(u=>u.role==='instructor').length,admins:users.filter(u=>u.role==='admin').length})[key]}</div>
          <div class="stat-label">${l}</div>
        </div>
      </div>
    `).join('')}
  </div>

  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
      <div class="tabbar">
        <button class="tab-btn active" onclick="filterUsers('all',this)">All Users</button>
        <button class="tab-btn" onclick="filterUsers('student',this)">Students</button>
        <button class="tab-btn" onclick="filterUsers('instructor',this)">Instructors</button>
        <button class="tab-btn" onclick="filterUsers('admin',this)">Admins</button>
      </div>
      <div style="display:flex;gap:8px">
        <div class="search-wrap" style="max-width:200px"><input type="text" placeholder="Search users..." oninput="searchUsers(this.value)"></div>
        <button class="btn btn-outline btn-sm" onclick="openAddUserModal('import')" style="display:inline-flex;align-items:center;gap:5px">${_ico('upload')} Import</button>
        <button class="btn btn-primary btn-sm" onclick="openAddUserModal('manual')" style="display:inline-flex;align-items:center;gap:5px">${_ico('plus')} Add User</button>
      </div>
    </div>

    <div id="bulkBar" class="bulk-action-bar">
      <span id="bulkCount" style="font-size:13px;font-weight:600;color:var(--primary)">0 selected</span>
      <button class="btn btn-outline btn-sm" onclick="bulkAction('email')" style="display:inline-flex;align-items:center;gap:5px">${_ico('email')} Email</button>
      <button class="btn btn-danger btn-sm" onclick="bulkAction('suspend')" style="display:inline-flex;align-items:center;gap:5px">${_ico('ban')} Suspend</button>
      <button class="btn btn-danger btn-sm" id="bulkDeleteBtn" onclick="bulkAction('delete')" style="display:inline-flex;align-items:center;gap:5px">${_ico('trash')} Delete</button>
      <button class="btn btn-ghost btn-sm" onclick="clearSelection()" style="display:inline-flex;align-items:center;gap:5px">${_ico('close')} Clear</button>
    </div>

    <div class="table-wrap">
      <table id="usersTable">
        <thead>
          <tr>
            <th><input type="checkbox" onchange="selectAllUsers(this)"></th>
            <th>User</th>
            <th>Role</th>
            <th>ID No.</th>
            <th>College</th>
            <th>Program</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Last Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="usersTbody">
          ${users.map(u => userRow(u)).join('')}
        </tbody>
      </table>
    </div>
  </div>`;
}

function userRow(u) {
  const initials = (u.name||u.email||'U').split(' ').map(n=>n[0]).join('').slice(0,2).toUpperCase();
  const avatarHtml = u.avatar_url
    ? `<img src="${u.avatar_url}" alt="" class="avatar-sm" style="object-fit:cover;padding:0">`
    : `<div class="avatar-sm">${initials}</div>`;
  return `<tr data-role="${u.role}" data-name="${u.name.toLowerCase()}" data-email="${u.email.toLowerCase()}">
    <td><input type="checkbox" onchange="toggleUserSelect(${u.id},this)"></td>
    <td><div style="display:flex;align-items:center;gap:10px">
      ${avatarHtml}
      <div><div style="font-weight:600;font-size:13px">${u.name}</div><div style="font-size:11px;color:var(--text-3)">${u.email}</div></div>
    </div></td>
    <td><span class="role-chip role-${u.role}">${u.role.charAt(0).toUpperCase()+u.role.slice(1)}</span></td>
    <td style="color:var(--text-2);font-size:12px;font-family:monospace">${u.role==='student'?(u.student_id||'—'):u.role==='instructor'?(u.employee_id||'—'):'—'}</td>
    <td style="color:var(--text-2);font-size:12px">${u.dept_name || '—'}</td>
    <td style="color:var(--text-2);font-size:12px">${u.program || '—'}</td>
    <td><span style="display:flex;align-items:center;gap:5px;font-size:12px"><span class="status-dot ${u.status}"></span>${u.status.charAt(0).toUpperCase()+u.status.slice(1)}</span></td>
    <td style="font-size:12px;color:var(--text-2)">${u.joined}</td>
    <td style="font-size:12px;color:var(--text-2)">${u.lastActive}</td>
    <td>
      <div style="display:flex;gap:3px;align-items:center">
        <button class="btn btn-ghost btn-sm" title="Edit user" onclick="viewUser(${u.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('pencil')}</button>
        <button class="btn btn-outline btn-sm" title="${u.status === 'suspended' ? 'Restore user' : 'Suspend user'}" onclick="toggleUserStatus(${u.id}, '${u.status === 'suspended' ? 'active' : 'suspended'}')" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${u.status === 'suspended' ? _ico('restore') : _ico('archive')}</button>
        ${u.role !== 'admin' ? `<button class="btn btn-danger btn-sm" title="Delete user" onclick="promptDeleteUser(${u.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('trash')}</button>` : ''}
      </div>
    </td>
  </tr>`;
}

function filterUsers(role, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const rows = document.querySelectorAll('#usersTbody tr');
  rows.forEach(r => {
    r.style.display = (role === 'all' || r.dataset.role === role) ? '' : 'none';
  });
}

function searchUsers(q) {
  const rows = document.querySelectorAll('#usersTbody tr');
  rows.forEach(r => {
    const match = r.dataset.name.includes(q.toLowerCase()) || r.dataset.email.includes(q.toLowerCase());
    r.style.display = match ? '' : 'none';
  });
}

function selectAllUsers(cb) {
  const boxes = document.querySelectorAll('#usersTbody input[type=checkbox]');
  boxes.forEach(b => { b.checked = cb.checked; });
  selectedUsers = cb.checked ? users.map(u => u.id) : [];
  updateBulkBar();
}

function toggleUserSelect(id, cb) {
  if (cb.checked) selectedUsers.push(id);
  else selectedUsers = selectedUsers.filter(i => i !== id);
  updateBulkBar();
}

function updateBulkBar() {
  const bar    = document.getElementById('bulkBar');
  const cnt    = document.getElementById('bulkCount');
  const delBtn = document.getElementById('bulkDeleteBtn');
  if (bar && cnt) {
    bar.classList.toggle('visible', selectedUsers.length > 0);
    cnt.textContent = `${selectedUsers.length} selected`;
  }
  if (delBtn) {
    // Hide bulk Delete when every selected user is an admin
    const allAdmins = selectedUsers.every(id => {
      const u = users.find(u => u.id === id);
      return u && u.role === 'admin';
    });
    delBtn.style.display = (selectedUsers.length > 0 && allAdmins) ? 'none' : '';
  }
}

function clearSelection() {
  selectedUsers = [];
  document.querySelectorAll('#usersTbody input[type=checkbox]').forEach(b => b.checked = false);
  updateBulkBar();
}

function promptDeleteUser(id) {
  const u = users.find(u => u.id === id);
  if (!u) return;
  if (u.role === 'admin') { showToast('Admin accounts cannot be deleted.', 'error'); return; }
  if (!confirm(`Delete "${u.name || u.email}"? This cannot be undone.`)) return;
  const body = new FormData();
  body.append('user_id', id);
  fetch('?action=delete_user', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(async data => {
      showToast(data.message, data.success ? 'success' : 'error');
      if (data.success) { await loadUsersFromDB(); if (currentPage === 'users') navigate('users'); }
    })
    .catch(() => showToast('Network error.', 'error'));
}

async function bulkAction(action) {
  if (!selectedUsers.length) {
    showToast('Select at least one user first.', 'error');
    return;
  }

  const body = new FormData();
  body.append('action', 'bulk_user_action');
  body.append('bulk_action', action);
  body.append('user_ids', JSON.stringify(selectedUsers));

  try {
    const res = await fetch('?action=bulk_user_action', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Bulk action completed.', 'success');
      await loadUsersFromDB();
      if (currentPage === 'users') navigate('users');
      clearSelection();
    } else {
      showToast(data.message || 'Bulk action failed.', 'error');
    }
  } catch (e) {
    showToast('⚠ Network error. Please try again.', 'error');
  }
}


function viewUser(id) {
  activeUserId = id;
  const u = users.find(u => u.id === id);
  if (!u) return;
  const initials = (u.name || u.email || 'User').split(' ').map(n => n[0]).join('').slice(0,2).toUpperCase();
  const avatarBanner = u.avatar_url
    ? `<img src="${String(u.avatar_url)}" alt="" style="width:64px;height:64px;border-radius:16px;object-fit:cover;margin:0 auto 10px;display:block">`
    : `<div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;margin:0 auto 10px">${String(initials || '')}</div>`;

  document.getElementById('viewUserTitle').textContent = u.name || u.email;
  document.getElementById('viewUserContent').innerHTML = `
    <!-- Avatar / identity banner -->
    <div style="text-align:center;padding:12px 0 16px;border-bottom:1px solid var(--border);margin-bottom:16px">
      ${avatarBanner}
      <div style="font-weight:700;font-size:16px;margin-bottom:2px">${String(u.name || u.email || '')}</div>
      <div style="font-size:12px;color:var(--text-2);margin-bottom:8px">${String(u.email || '')}</div>
      <div style="display:flex;gap:6px;justify-content:center">
        <span class="role-chip role-${String(u.role || '')}">${String(u.role || '')}</span>
        <span class="badge ${u.status === 'active' ? 'badge-green' : 'badge-red'}">${String(u.status || '')}</span>
      </div>
    </div>
    <!-- First Name + Last Name side by side (no collision) -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group"><label class="form-label">First Name</label><input id="viewUserFirstName" type="text" value="${String(u.first_name || '')}"></div>
      <div class="form-group"><label class="form-label">Last Name</label><input id="viewUserLastName" type="text" value="${String(u.last_name || '')}"></div>
    </div>
    <!-- Email full width -->
    <div class="form-group"><label class="form-label">Email Address</label><input id="viewUserEmail" type="email" value="${String(u.email || '')}"></div>
    <!-- Role + Status side by side -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group"><label class="form-label">Role</label>
        <select id="viewUserRole" onchange="renderViewRoleFields(this.value)">
          <option value="student"${u.role === 'student' ? ' selected' : ''}>Student</option>
          <option value="instructor"${u.role === 'instructor' ? ' selected' : ''}>Instructor</option>
          <option value="admin"${u.role === 'admin' ? ' selected' : ''}>Admin</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Status</label>
        <select id="viewUserStatus">
          <option value="pending"${u.status === 'pending' ? ' selected' : ''}>Pending</option>
          <option value="active"${u.status === 'active' ? ' selected' : ''}>Active</option>
          <option value="inactive"${u.status === 'inactive' ? ' selected' : ''}>Inactive</option>
          <option value="suspended"${u.status === 'suspended' ? ' selected' : ''}>Suspended</option>
        </select>
      </div>
    </div>
    <!-- Role-specific fields (student / instructor / admin) -->
    <div id="viewUserRoleFields" style="margin-top:4px"></div>
    <!-- Metadata row -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
      <div class="form-group"><label class="form-label">Joined</label><input type="text" value="${String(u.joined || '')}" disabled></div>
      <div class="form-group"><label class="form-label">Last Active</label><input type="text" value="${String(u.lastActive || '')}" disabled></div>
    </div>`;

  renderViewRoleFields(u.role, u);
  updateViewModalActions(u);
  document.getElementById('viewUserStatusMessage').textContent = '';
  openModal('viewUserModal');
}

function renderViewRoleFields(role, u) {
  const container = document.getElementById('viewUserRoleFields');
  if (!container) return;

  // Always fall back to the currently active user so fields are never blank
  // when this is called from the role <select> onchange (no u passed).
  const user = (u && Object.keys(u).length > 0)
    ? u
    : (users.find(item => item.id === activeUserId) || {});

  let html = '';

  if (role === 'student') {
    const sectionLetter = (user.section || '').match(/([A-H])$/i)?.[1]?.toUpperCase() || '';
    const yearLevelOpts = ['1','2','3','4'].map(y =>
      `<option${String(user.year_level || '') === y ? ' selected' : ''}>${y}</option>`
    ).join('');
    const letterOpts = ['A','B','C','D','E','F','G','H'].map(l =>
      `<option${l === sectionLetter ? ' selected' : ''}>${l}</option>`
    ).join('');
    const sectionPrefix = (user.program && user.year_level)
      ? `${user.program}-${user.year_level}` : (user.program || user.year_level || '—');
    html = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Student ID</label><input id="viewUserStudentId" type="text" value="${String(user.student_id || '')}"></div>
        <div class="form-group"><label class="form-label">Year Level</label>
          <select id="viewUserYearLevel" onchange="syncViewSection()">
            <option value="">— select —</option>${yearLevelOpts}
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Program / Course</label>
          <select id="viewUserProgram" onchange="syncViewSection()"><option value="">— loading programs —</option></select>
        </div>
        <div class="form-group"><label class="form-label">Section</label>
          <div style="display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;background:var(--surface)">
            <span id="viewUserSectionPrefix" style="padding:0 10px;font-family:monospace;font-size:13px;font-weight:700;color:var(--primary);background:var(--primary-light);border-right:1.5px solid var(--border);white-space:nowrap;line-height:38px">${sectionPrefix}</span>
            <select id="viewUserSectionLetter" style="border:none;outline:none;flex:1;padding:0 10px;font-size:13px;background:transparent;height:38px" onchange="syncViewSection()">
              <option value="">— letter —</option>${letterOpts}
            </select>
            <input type="hidden" id="viewUserSection" value="${String(user.section || '')}">
          </div>
        </div>
      </div>`;
  } else if (role === 'instructor') {
    html = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group"><label class="form-label">Employee ID</label><input id="viewUserEmployeeId" type="text" value="${String(user.employee_id || '')}"></div>
        <div class="form-group"><label class="form-label">College</label><select id="viewUserDeptId"><option value="">— loading colleges —</option></select></div>
      </div>
      <div class="form-group"><label class="form-label">Designation</label><input id="viewUserDesignation" type="text" value="${String(user.designation || '')}"></div>`;
  } else {
    html = `
      <div class="form-group"><label class="form-label">Admin Notes</label><input id="viewUserAdminNotes" type="text" placeholder="Optional internal notes"></div>`;
  }

  container.innerHTML = html;

  // Load departments whenever the instructor select is rendered,
  // whether opened fresh or switched to via the role dropdown.
  if (role === 'instructor') {
    loadDepartments('viewUserDeptId', user.department_id || '');
  }
  // Load programs for student role
  if (role === 'student') {
    loadPrograms('viewUserProgram', user.program || '').then(syncViewSection);
  }
}

function updateViewModalActions(u) {
  const deleteBtn  = document.getElementById('viewUserDeleteBtn');
  const suspendBtn = document.getElementById('viewUserSuspendBtn');
  const histBtn    = document.getElementById('viewUserEnrollHistBtn');

  if (u.role === 'admin') {
    if (deleteBtn)  deleteBtn.style.display  = 'none';
    if (suspendBtn) suspendBtn.style.display = 'none';
    if (histBtn)    histBtn.style.display    = 'none';
    return;
  }

  if (deleteBtn)  deleteBtn.style.display  = '';
  if (suspendBtn) {
    suspendBtn.style.display = '';
    suspendBtn.innerHTML = (u.status === 'suspended' ? _ico('restore') + ' Activate' : _ico('ban') + ' Suspend');
    suspendBtn.style.cssText += 'display:inline-flex;align-items:center;gap:6px';
  }
  if (histBtn) {
    histBtn.style.display = u.role === 'student' ? 'inline-flex' : 'none';
    histBtn.onclick = () => openStudentEnrollHistory(u.id, u.name || u.email);
  }
}

async function saveUserChanges() {
  const statusMsg = document.getElementById('viewUserStatusMessage');
  const btn = document.getElementById('saveUserBtn');
  if (!activeUserId) return;

  const body = new FormData();
  body.append('action', 'update_user');
  body.append('user_id', activeUserId);
  body.append('first_name', document.getElementById('viewUserFirstName').value.trim());
  body.append('last_name', document.getElementById('viewUserLastName').value.trim());
  body.append('email', document.getElementById('viewUserEmail').value.trim());
  body.append('role', document.getElementById('viewUserRole').value);
  body.append('status', document.getElementById('viewUserStatus').value);

  const role = document.getElementById('viewUserRole').value;
  if (role === 'student') {
    body.append('student_id', document.getElementById('viewUserStudentId').value.trim());
    body.append('program', document.getElementById('viewUserProgram').value.trim());
    body.append('year_level', document.getElementById('viewUserYearLevel').value.trim());
    body.append('section', document.getElementById('viewUserSection').value.trim());
  } else if (role === 'instructor') {
    body.append('employee_id', document.getElementById('viewUserEmployeeId').value.trim());
    body.append('department_id', document.getElementById('viewUserDeptId').value);
    body.append('designation', document.getElementById('viewUserDesignation').value.trim());
  }

  btn.disabled = true;
  btn.textContent = '⏳ Saving…';
  statusMsg.textContent = '';

  try {
    const res = await fetch('?action=update_user', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) {
      showToast('✅ User updated successfully!', 'success');
      await loadUsersFromDB();
      if (currentPage === 'users') navigate('users');
      closeModal('viewUserModal');
    } else {
      statusMsg.textContent = data.message || 'Unable to save changes.';
    }
  } catch (e) {
    statusMsg.textContent = '⚠ Network error. Please try again.';
  } finally {
    btn.disabled = false;
    btn.textContent = '💾 Save';
  }
}

async function deleteUser() {
  const statusMsg = document.getElementById('viewUserStatusMessage');
  if (!activeUserId) return;
  if (!confirm('Delete this user permanently?')) return;

  const body = new FormData();
  body.append('action', 'delete_user');
  body.append('user_id', activeUserId);

  try {
    const res = await fetch('?action=delete_user', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) {
      showToast('✅ User deleted.', 'success');
      await loadUsersFromDB();
      if (currentPage === 'users') navigate('users');
      closeModal('viewUserModal');
    } else {
      statusMsg.textContent = data.message || 'Unable to delete user.';
    }
  } catch (e) {
    statusMsg.textContent = '⚠ Network error. Please try again.';
  }
}

async function toggleUserStatus(id, targetStatus) {
  const user = users.find(u => u.id === id);
  if (!user) return;
  const newStatus = targetStatus || (user.status === 'suspended' ? 'active' : 'suspended');
  const body = new FormData();
  body.append('action',     'update_user');
  body.append('user_id',    id);
  body.append('status',     newStatus);
  // Include required fields so the PHP update_user handler does not reject the request
  body.append('first_name', user.first_name || '');
  body.append('last_name',  user.last_name  || '');
  body.append('email',      user.email      || '');
  body.append('role',       user.role       || 'student');
  if (user.role === 'student') {
    body.append('student_id',  user.student_id  || '');
    body.append('program',     user.program     || '');
    body.append('year_level',  user.year_level  || '');
    body.append('section',     user.section     || '');
  } else if (user.role === 'instructor') {
    body.append('employee_id',   user.employee_id   || '');
    body.append('department_id', user.department_id || '');
    body.append('designation',   user.designation   || '');
  }

  const btn = document.getElementById('viewUserSuspendBtn');
  if (btn) btn.disabled = true;

  try {
    const res = await fetch('?action=update_user', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) {
      showToast(`✅ User ${newStatus === 'suspended' ? 'suspended' : 'activated'}.`, 'success');
      await loadUsersFromDB();
      if (currentPage === 'users') navigate('users');
      if (activeUserId === id) viewUser(id);
    } else {
      document.getElementById('viewUserStatusMessage').textContent = data.message || 'Unable to update status.';
    }
  } catch (e) {
    document.getElementById('viewUserStatusMessage').textContent = '⚠ Network error. Please try again.';
  } finally {
    if (btn) btn.disabled = false;
  }
}

// ── Add User: tab switcher ──────────────────────────────────
function switchAddTab(tab, btn) {
  document.querySelectorAll('#addUserTabBar .tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  document.getElementById('addTabManual').style.display = tab === 'manual' ? '' : 'none';
  document.getElementById('addTabImport').style.display = tab === 'import' ? '' : 'none';
}

function toggleRoleFields(role) {
  document.getElementById('studentFields').style.display    = role === 'student'    ? '' : 'none';
  document.getElementById('instructorFields').style.display = role === 'instructor' ? '' : 'none';
  autoFillId(role);
}

// ── Auto-fill Student/Employee ID from DB sequence ───────────
let _nextIds = null; // cache so we only fetch once per modal open

function syncStudentId() {
  const prefix = document.getElementById('newStudentIdPrefix').textContent;
  const seq    = document.getElementById('newStudentIdSeq').value.trim();
  document.getElementById('newStudentId').value = seq ? prefix + seq : '';
}

function syncEmployeeId() {
  const seq = document.getElementById('newEmployeeIdSeq').value.trim();
  document.getElementById('newEmployeeId').value = seq ? 'EMP-' + seq : '';
}

function syncStudentCollege() {
  const progEl  = document.getElementById('newProgram');
  const display = document.getElementById('studentCollegeDisplay');
  if (!progEl || !display) return;
  const opt = progEl.options[progEl.selectedIndex];
  const college = opt ? (opt.dataset.college || '') : '';
  display.textContent = college || '— select a program first —';
  display.style.color = college ? 'var(--text)' : 'var(--text-2)';
}

function syncSection() {
  const progEl   = document.getElementById('newProgram');
  const yearEl   = document.getElementById('newYearLevel');
  const letterEl = document.getElementById('newSectionLetter');
  const prefixEl = document.getElementById('newSectionPrefix');
  const hiddenEl = document.getElementById('newSection');
  if (!progEl || !yearEl || !letterEl) return;
  const code   = progEl.value || '';
  const year   = yearEl.value || '';
  const letter = letterEl.value || '';
  const prefix = (code && year) ? `${code}-${year}` : (code || year || '—');
  if (prefixEl) prefixEl.textContent = prefix;
  if (hiddenEl) hiddenEl.value = (code && year && letter) ? `${code}-${year}${letter}` : '';
}

function syncViewSection() {
  const progEl   = document.getElementById('viewUserProgram');
  const yearEl   = document.getElementById('viewUserYearLevel');
  const letterEl = document.getElementById('viewUserSectionLetter');
  const prefixEl = document.getElementById('viewUserSectionPrefix');
  const hiddenEl = document.getElementById('viewUserSection');
  if (!progEl || !yearEl || !letterEl) return;
  const code   = progEl.value || '';
  const year   = yearEl.value || '';
  const letter = letterEl.value || '';
  const prefix = (code && year) ? `${code}-${year}` : (code || year || '—');
  if (prefixEl) prefixEl.textContent = prefix;
  if (hiddenEl) hiddenEl.value = (code && year && letter) ? `${code}-${year}${letter}` : '';
}

async function autoFillId(role) {
  if (role !== 'student' && role !== 'instructor') return;

  if (!_nextIds) {
    try {
      const res = await fetch('?action=get_next_ids', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      _nextIds  = await res.json();
    } catch(e) {
      console.error('Failed to fetch next IDs', e);
      return;
    }
  }

  if (role === 'student' && _nextIds.student_id) {
    const seqEl = document.getElementById('newStudentIdSeq');
    if (seqEl && !seqEl.value) {
      // Extract just the part after "YY-"
      const parts = _nextIds.student_id.split('-');
      seqEl.value = parts[1] || '';
      syncStudentId();
    }
  } else if (role === 'instructor' && _nextIds.employee_id) {
    const seqEl = document.getElementById('newEmployeeIdSeq');
    if (seqEl && !seqEl.value) {
      const parts = _nextIds.employee_id.split('-');
      seqEl.value = parts[1] || '';
      syncEmployeeId();
    }
  }
}


function togglePassVis() {
  const inp = document.getElementById('newUserPass');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

// ── Email autofill from name ─────────────────────────────────
let _emailAutoFilled = false;   // tracks whether email was auto-generated

function autoFillEmail() {
  const first     = document.getElementById('newFirstName').value.trim();
  const last      = document.getElementById('newLastName').value.trim();
  const emailEl   = document.getElementById('newUserEmail');
  const tagEl     = document.getElementById('emailAutoTag');

  // Only autofill when the field is empty OR was previously auto-filled
  if (emailEl.value && !_emailAutoFilled) return;

  if (first || last) {
    // Normalise: lowercase, remove spaces/accents, strip non-alphanum except dot
    const clean = s => s.toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')  // strip accents
      .replace(/[^a-z0-9]/g, '');                         // keep only a-z 0-9

    const f = clean(last);
    const l = clean(first);

    const generated = (f && l) ? `${f}_${l}@plpasig.edu.ph`
                    : f        ? `${f}@plpasig.edu.ph`
                    :            `${l}@plpasig.edu.ph`;

    emailEl.value    = generated;
    _emailAutoFilled = true;
    if (tagEl) tagEl.style.display = '';
  } else {
    // Both fields cleared — reset email too
    emailEl.value    = '';
    _emailAutoFilled = false;
    if (tagEl) tagEl.style.display = 'none';
  }
}

function onEmailManualEdit() {
  // User typed in the email box themselves — stop overwriting it
  const tagEl = document.getElementById('emailAutoTag');
  _emailAutoFilled = false;
  if (tagEl) tagEl.style.display = 'none';
}


// ── Manual create ───────────────────────────────────────────
async function createUser() {
  const btn   = document.getElementById('createUserBtn');
  const status = document.getElementById('manualStatus');
  const first = document.getElementById('newFirstName').value.trim();
  const last  = document.getElementById('newLastName').value.trim();
  const email = document.getElementById('newUserEmail').value.trim();
  const role  = document.getElementById('newUserRole').value;
  const userStatus = document.getElementById('newUserStatus').value;

  if (!first || !last || !email) {
    status.innerHTML = '<span style="color:var(--danger)">⚠ Please fill in all required fields.</span>';
    return;
  }

  if (role === 'student' && !document.getElementById('newStudentId').value.trim()) {
    status.innerHTML = '<span style="color:var(--danger)">⚠ Student ID is required for student accounts.</span>';
    return;
  }

  if (role === 'instructor' && !document.getElementById('newEmployeeId').value.trim()) {
    status.innerHTML = '<span style="color:var(--danger)">⚠ Employee ID is required for instructor accounts.</span>';
    return;
  }

  const body = new FormData();
  body.append('action',     'create_user');
  body.append('first_name', first);
  body.append('last_name',  last);
  body.append('email',      email);
  body.append('role',       role);
  body.append('status',     userStatus);

  if (role === 'student') {
    body.append('student_id', document.getElementById('newStudentId').value.trim());
    body.append('program',    document.getElementById('newProgram').value.trim());
    body.append('year_level', document.getElementById('newYearLevel').value);
    body.append('section',    document.getElementById('newSection').value.trim());
  } else if (role === 'instructor') {
    body.append('employee_id',  document.getElementById('newEmployeeId').value.trim());
    body.append('department_id',document.getElementById('newDeptId').value);
    body.append('designation',  document.getElementById('newDesignation').value.trim());
  }

  btn.disabled = true;
  btn.textContent = '⏳ Creating…';
  status.textContent = '';

  try {
    const res  = await fetch('?action=create_user', { method:'POST', body, headers:{ 'X-Requested-With':'XMLHttpRequest' } });
    const data = await res.json();
    if (data.success) {
      showToast(`✅ User "${first} ${last}" created successfully!`, 'success');
      closeModal('addUserModal');
      resetAddUserForm();
      await loadUsersFromDB();
      if (currentPage === 'users') navigate('users');
    } else {
      status.innerHTML = `<span style="color:var(--danger)">⚠ ${data.message}</span>`;
    }
  } catch (e) {
    status.innerHTML = '<span style="color:var(--danger)">⚠ Network error. Please try again.</span>';
  } finally {
    btn.disabled = false;
    btn.textContent = '➕ Create User';
  }
}

function resetAddUserForm() {
  ['newFirstName','newLastName','newUserEmail',
   'newStudentId','newStudentIdSeq','newProgram','newSection',
   'newEmployeeId','newEmployeeIdSeq','newDesignation'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  document.getElementById('newUserRole').value   = 'student';
  document.getElementById('newUserStatus').value = 'pending';
  document.getElementById('newYearLevel').value  = '';
  document.getElementById('newDeptId').value     = '';
  const letterEl = document.getElementById('newSectionLetter');
  const prefixEl = document.getElementById('newSectionPrefix');
  if (letterEl) letterEl.value = '';
  if (prefixEl) prefixEl.textContent = '—';
  toggleRoleFields('student');
  document.getElementById('manualStatus').textContent = '';
  _emailAutoFilled = false;
  const tagEl = document.getElementById('emailAutoTag');
  if (tagEl) tagEl.style.display = 'none';
}

// ── File import ─────────────────────────────────────────────
let importedRows = [];

function handleImportDrop(e) {
  e.preventDefault();
  document.getElementById('importDropZone').style.borderColor = 'var(--border)';
  const file = e.dataTransfer.files[0];
  if (file) handleImportFile(file);
}

async function handleImportFile(file) {
  if (!file) return;
  // Reset input so the same file can be re-selected later without re-opening the dialog
  const fileInputEl = document.getElementById('importFileInput');
  if (fileInputEl) fileInputEl.value = '';
  const maxBytes = 5 * 1024 * 1024;
  if (file.size > maxBytes) { showToast('File too large (max 5 MB)', 'error'); return; }

  const allowed = ['application/pdf','text/csv','text/plain'];
  const ext     = file.name.split('.').pop().toLowerCase();
  if (!['pdf','csv','txt'].includes(ext)) { showToast('Unsupported file type', 'error'); return; }

  document.getElementById('importFileInfo').style.display  = 'flex';
  document.getElementById('importFileName').textContent    = `📎 ${file.name}`;
  document.getElementById('importProgress').style.display  = 'block';
  document.getElementById('importPreview').style.display   = 'none';
  document.getElementById('importSaveBtn').style.display   = 'none';
  importedRows = [];

  animateBar(0, 40, 800);

  let text = '';
  if (ext === 'pdf') {
    text = await extractPdfText(file);
  } else {
    text = await file.text();
  }

  animateBar(40, 70, 500);
  document.getElementById('importProgressLabel').textContent = 'Parsing user records…';

  importedRows = parseUserText(text, ext);

  animateBar(70, 100, 400);
  setTimeout(() => {
    document.getElementById('importProgress').style.display = 'none';
    renderImportPreview();
  }, 450);
}

function animateBar(from, to, ms) {
  const bar = document.getElementById('importProgressBar');
  bar.style.transition = `width ${ms}ms ease`;
  bar.style.width = to + '%';
}

async function extractPdfText(file) {
  // Use FileReader to read as text — works for text-based PDFs without external libs
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.onload = e => {
      // Extract printable ASCII from binary PDF
      const raw = e.target.result;
      let text = '';
      for (let i = 0; i < raw.length; i++) {
        const c = raw.charCodeAt(i);
        if (c >= 32 && c < 127) text += raw[i];
        else if (c === 10 || c === 13) text += '\n';
      }
      resolve(text);
    };
    reader.readAsBinaryString(file);
  });
}

function parseUserText(text, ext) {
  const rows = [];
  const lines = text.split(/\r?\n/).map(l => l.trim()).filter(Boolean);

  if (ext === 'csv') {
    // Try CSV parsing: detect header row
    const headerLine = lines[0];
    const delim = headerLine.includes('\t') ? '\t' : ',';
    const headers = headerLine.split(delim).map(h => h.toLowerCase().replace(/[^a-z_]/g,'').trim());

    const colMap = {};
    ['first_name','firstname','first'].forEach(k => { if (headers.includes(k)) colMap.first = headers.indexOf(k); });
    ['last_name','lastname','last'].forEach(k    => { if (headers.includes(k)) colMap.last  = headers.indexOf(k); });
    ['name','full_name','fullname'].forEach(k    => { if (headers.includes(k)) colMap.name  = headers.indexOf(k); });
    ['email','e-mail','emailaddress'].forEach(k  => { if (headers.includes(k)) colMap.email = headers.indexOf(k); });
    ['role','type','usertype'].forEach(k         => { if (headers.includes(k)) colMap.role  = headers.indexOf(k); });
    ['student_id','studentid','id','no'].forEach(k=>{ if (headers.includes(k)) colMap.sid   = headers.indexOf(k); });
    ['employee_id','employeeid','empid'].forEach(k=>{ if (headers.includes(k)) colMap.eid   = headers.indexOf(k); });
    ['status','state'].forEach(k=>{ if (headers.includes(k)) colMap.status = headers.indexOf(k); });
    ['program'].forEach(k=>{ if (headers.includes(k)) colMap.program = headers.indexOf(k); });
    ['year_level','yearlevel','year'].forEach(k=>{ if (headers.includes(k)) colMap.year = headers.indexOf(k); });
    ['section'].forEach(k=>{ if (headers.includes(k)) colMap.section = headers.indexOf(k); });
    ['password','temporary_password','temp_password'].forEach(k=>{ if (headers.includes(k)) colMap.password = headers.indexOf(k); });
    ['department','department_id'].forEach(k=>{ if (headers.includes(k)) colMap.department = headers.indexOf(k); });
    ['designation'].forEach(k=>{ if (headers.includes(k)) colMap.designation = headers.indexOf(k); });
    ['college','collegename','college_name'].forEach(k=>{ if (headers.includes(k)) colMap.college = headers.indexOf(k); });

    for (let i = 1; i < lines.length; i++) {
      const cols = lines[i].split(delim).map(c => c.replace(/^"|"$/g,'').trim());
      let first='', last='', email='', role='student', sid='', eid='', status='pending', program='', year_level='', section='', password='', department='', designation='', college='';

      if (colMap.name !== undefined) {
        const parts = cols[colMap.name]?.split(' ') ?? [];
        first = parts[0] ?? ''; last = parts.slice(1).join(' ');
      } else {
        first = cols[colMap.first ?? -1] ?? '';
        last  = cols[colMap.last  ?? -1] ?? '';
      }
      email      = cols[colMap.email ?? -1] ?? '';
      role       = normalizeRole(cols[colMap.role ?? -1] ?? 'student');
      sid        = cols[colMap.sid  ?? -1] ?? '';
      eid        = cols[colMap.eid  ?? -1] ?? '';
      status     = cols[colMap.status ?? -1] ?? 'pending';
      program    = cols[colMap.program ?? -1] ?? '';
      year_level = cols[colMap.year ?? -1] ?? '';
      section    = cols[colMap.section ?? -1] ?? '';
      password   = cols[colMap.password ?? -1] ?? '';
      department = cols[colMap.department ?? -1] ?? '';
      designation= cols[colMap.designation ?? -1] ?? '';
      college    = cols[colMap.college ?? -1] ?? '';

      if (first || email) rows.push({
        first, last, email, role, sid, eid,
        status, program, year_level, section,
        password, department, designation, college,
        error: validateImportRow({first,last,email,role,sid,eid})
      });
    }

  } else {
    // PDF / TXT: heuristic line-by-line detection
    const emailRe       = /\b([a-z]+_[a-z]+@plpasig\.edu\.ph)\b/i;
    const sidRe         = /\b(\d{2,4}[-–]\d{4,6})\b/;
    const eidRe         = /\b(EMP[-–]?\d{2,6})\b/i;
    const yearRe        = /\b([1-5](?:st|nd|rd|th)?\s*year)\b/i;
    const sectionRe     = /\bsection[:\s]+([A-Z0-9\-]+)\b/i;
    const programRe     = /\b(BSCS|BSIT|BSECE|BSCpE|BSEE|BSME|BSBA|BSED|BEED|BSN|BSPH|BSAB|BSA|BSCA|AB\s*\w+|BS\s*\w+)\b/i;
    const deptRe        = /\bdept(?:artment)?[:\s]+([A-Za-z\s&]+?)(?:\s{2,}|$|[,;])/i;
    const designationRe = /\b(full[\s-]?time|part[\s-]?time)\b/i;

    for (const line of lines) {
      const emailM = line.match(emailRe);
      if (!emailM) continue;

      const email  = emailM[0];
      const before = line.slice(0, emailM.index).trim().replace(/[,|;:]/g,'').trim();
      const parts  = before.split(/\s+/).filter(Boolean);
      const first  = parts[0] ?? '';
      const last   = parts.slice(1).join(' ');
      const role   = normalizeRole(line);
      const sidM   = line.match(sidRe);
      const eidM   = line.match(eidRe);
      const sid    = role === 'student'    ? (sidM?.[1] ?? '') : '';
      const eid    = role === 'instructor' ? (eidM?.[1] ?? '') : '';

      // Student-specific fields
      const programM    = line.match(programRe);
      const program     = role === 'student' ? (programM?.[1] ?? '') : '';
      const yearM       = line.match(yearRe);
      const year_level  = role === 'student' ? (yearM ? yearM[1].replace(/\D/g,'').trim() : '') : '';
      const sectionM    = line.match(sectionRe);
      const section     = role === 'student' ? (sectionM?.[1] ?? '') : '';

      // Instructor-specific fields
      const deptM       = line.match(deptRe);
      const department  = role === 'instructor' ? (deptM?.[1]?.trim() ?? '') : '';
      const desigM      = line.match(designationRe);
      const designation = role === 'instructor'
        ? (desigM ? (/full/i.test(desigM[0]) ? 'Full-time' : 'Part-time') : '')
        : '';

      rows.push({
        first, last, email, role, sid, eid,
        status: 'pending',
        program, year_level, section,
        department, designation,
        password: '',
        error: validateImportRow({first,last,email,role,sid,eid})
      });
    }
  }

  return rows;
}

function normalizeRole(str) {
  if (!str) return 'student';
  const s = str.toLowerCase();
  if (s.includes('admin'))      return 'admin';
  if (s.includes('instructor') || s.includes('teacher') || s.includes('faculty') || s.includes('prof')) return 'instructor';
  return 'student';
}

function validateImportRow(r) {
  const warnings = [];
  if (!r.email || !/\S+@\S+\.\S+/.test(r.email)) warnings.push('Invalid email');
  if (!r.first && !r.last) warnings.push('No name detected');
  if (r.role === 'student' && !r.sid) warnings.push('No student ID');
  if (r.role === 'instructor' && !r.eid) warnings.push('No employee ID');
  return warnings.join(' · ');
}

function renderImportPreview() {
  const tbody = document.getElementById('importPreviewBody');
  const label = document.getElementById('importPreviewLabel');
  const saveBtn = document.getElementById('importSaveBtn');
  const saveCount = document.getElementById('importSaveCount');
  const errDiv = document.getElementById('importErrors');

  document.getElementById('importPreview').style.display = 'block';
  label.textContent = `${importedRows.length} user${importedRows.length !== 1 ? 's' : ''} found`;

  const errorCount = importedRows.filter(r => r.error).length;
  errDiv.textContent = errorCount ? `⚠ ${errorCount} row(s) have warnings — review before saving.` : '';

  tbody.innerHTML = importedRows.map((r, i) => {
    const idField = r.role === 'student' ? (r.sid || '—') : (r.eid || '—');
    const deptField = r.role === 'instructor'
      ? `${r.department || '—'} ${r.designation || ''}`.trim()
      : '';
    const programField = r.role === 'student'
      ? `${r.program || '—'} ${r.year_level ? 'Yr ' + r.year_level : ''} ${r.section || ''}`.trim()
      : '';
    return `
      <tr id="importRow-${i}" ${r.error ? 'style="background:rgba(216,64,64,.07)"' : ''}>
        <td><input type="checkbox" checked data-row="${i}" onchange="updateImportCount()"></td>
        <td>${r.first} ${r.last}</td>
        <td style="font-size:11px">${r.email}</td>
        <td><span class="role-chip role-${r.role}" style="font-size:10px">${r.role}</span></td>
        <td style="font-size:11px;color:var(--text-2)">${idField}</td>
        <td style="font-size:11px;color:var(--text-2)">${r.status || 'pending'}</td>
        <td style="font-size:11px;color:var(--text-2)">${r.role === 'student' ? (programField || '—') : (deptField || '—')}</td>
        <td style="font-size:11px;color:var(--danger)">${r.error ? '⚠' : '✅'}</td>
      </tr>
    `;
  }).join('');

  updateImportCount();
  saveBtn.style.display = importedRows.length ? 'inline-flex' : 'none';
}

function updateImportCount() {
  const checked = document.querySelectorAll('#importPreviewBody input[type=checkbox]:checked').length;
  document.getElementById('importSaveCount').textContent = checked;
}

function toggleAllImport(cb) {
  document.querySelectorAll('#importPreviewBody input[type=checkbox]').forEach(b => b.checked = cb.checked);
  updateImportCount();
}

function downloadImportTemplate() {
  // Two separate sheets — one for students, one for instructors
  const studentHeaders = ['first_name','last_name','email','role','student_id','status','program','year_level','section','college','password'];
  const studentRows = [
    ['Juan','Dela Cruz','delacruz_juan@plpasig.edu.ph','student','25-10001','active','BSIT','1','BSIT-1A','College of Information and Communications Technology','LearnFlow@2025'],
    ['Maria','Santos','santos_maria@plpasig.edu.ph','student','25-10002','active','BSCS','2','BSCS-2B','College of Information and Communications Technology','LearnFlow@2025'],
    ['Carlo','Reyes','reyes_carlo@plpasig.edu.ph','student','25-10003','active','BSIT','3','BSIT-3A','College of Information and Communications Technology','LearnFlow@2025'],
  ];
  const instrHeaders = ['first_name','last_name','email','role','employee_id','status','college','designation','password'];
  const instrRows = [
    ['Ana','Flores','flores_ana@plpasig.edu.ph','instructor','EMP-001','active','College of Information and Communications Technology','Full-time','LearnFlow@2025'],
    ['Ramon','Cruz','cruz_ramon@plpasig.edu.ph','instructor','EMP-002','active','College of Engineering and Technology','Part-time','LearnFlow@2025'],
  ];
  const q = v => `"${String(v).replace(/"/g,'""')}"`;
  const csvContent =
    '# ── STUDENTS ──\r\n' +
    studentHeaders.join(',') + '\r\n' +
    studentRows.map(r => r.map(q).join(',')).join('\r\n') +
    '\r\n\r\n# ── INSTRUCTORS ──\r\n' +
    instrHeaders.join(',') + '\r\n' +
    instrRows.map(r => r.map(q).join(',')).join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'learnflow_users_template.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  showToast('Template downloaded!', 'success');
}

function clearImport() {
  importedRows = [];
  document.getElementById('importFileInput').value = '';
  document.getElementById('importFileInfo').style.display  = 'none';
  document.getElementById('importProgress').style.display  = 'none';
  document.getElementById('importPreview').style.display   = 'none';
  document.getElementById('importSaveBtn').style.display   = 'none';
  document.getElementById('importStatus').textContent      = '';
}

async function saveImportedUsers() {
  const status  = document.getElementById('importStatus');
  const saveBtn = document.getElementById('importSaveBtn');
  const checked = [...document.querySelectorAll('#importPreviewBody input[type=checkbox]:checked')]
                    .map(cb => importedRows[parseInt(cb.dataset.row)]);

  if (!checked.length) { status.innerHTML = '<span style="color:var(--danger)">No users selected.</span>'; return; }

  saveBtn.disabled = true;
  status.innerHTML = `<span style="color:var(--text-2)">⏳ Saving ${checked.length} users…</span>`;

  const body = new FormData();
  body.append('action', 'bulk_create_users');
  body.append('users',  JSON.stringify(checked.map(r => ({
    first_name:   r.first,
    last_name:    r.last,
    email:        r.email,
    role:         r.role,
    student_id:   r.sid,
    employee_id:  r.eid,
    password:     r.password || 'LearnFlow@2025',
    status:       r.status || 'pending',
    program:      r.program || '',
    year_level:   r.year_level || '',
    section:      r.section || '',
    college:      r.college || '',       // student's college name (informational / display)
    department:   r.role === 'instructor' ? (r.department || r.college || '') : '',  // instructor college → resolved to dept ID by PHP
    department_id: '',                   // leave blank; PHP resolves from 'department'
    designation:  r.designation || '',
  }))));

  try {
    const res  = await fetch('?action=bulk_create_users', { method:'POST', body, headers:{ 'X-Requested-With':'XMLHttpRequest' } });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (_) {
      // PHP returned something other than JSON — show the raw output so the error is visible
      const preview = text.replace(/<[^>]*>/g, '').trim().substring(0, 300);
      status.innerHTML = `<span style="color:var(--danger)">⚠ Server error: ${preview || res.status + ' ' + res.statusText}</span>`;
      saveBtn.disabled = false;
      return;
    }
    const ok   = data.created ?? 0;
    const fail = data.failed  ?? 0;
    if (ok > 0) {
      showToast(`✅ ${ok} user(s) created${fail ? `, ${fail} skipped` : ''}`, 'success');
      closeModal('addUserModal');
      clearImport();
      await loadUsersFromDB();
      if (currentPage === 'users') navigate('users');
    } else {
      status.innerHTML = `<span style="color:var(--danger)">⚠ ${data.message || 'All imports failed. Check for duplicates.'}</span>`;
    }
  } catch (e) {
    status.innerHTML = `<span style="color:var(--danger)">⚠ ${e.message || 'Network error.'}</span>`;
  } finally {
    saveBtn.disabled = false;
  }
}

// ===== COURSE REGISTRY — DB-connected =====
let _adminCourses = [];
let _coursesMeta  = { instructors: [], terms: [], departments: [] };
let _delCourseId  = null;
let _archiveCourseId = null;
let _courseFilter = 'All';

function renderCourses() {
  setTimeout(async () => {
    await _loadCoursesMeta();
    await loadCoursesFromDB();
  }, 0);
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Course Registry</span></div>
    <h1>Course Registry</h1>
    <p>Manage all courses across all colleges</p>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div class="filter-row" style="margin:0" id="courseFilterRow">
      ${['All','Published','Draft','Archived'].map((f,i)=>`
        <button class="filter-chip${i===0?' active':''}" onclick="filterCoursesAdmin('${f}',this)">${f}</button>
      `).join('')}
    </div>
    <button class="btn btn-primary btn-sm" onclick="openAddCourseModal()" style="display:inline-flex;align-items:center;gap:6px">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Course
    </button>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Code</th><th>Course Title</th><th>Dept</th><th>Instructor</th><th>Enrolled</th><th>Units</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="coursesTbody">
          <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-3)">⏳ Loading courses…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
  `;
}

async function _loadCoursesMeta() {
  try {
    const res  = await fetch('?action=get_course_meta', { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    if (data.success) {
      _coursesMeta.instructors  = data.instructors  || [];
      _coursesMeta.terms        = data.terms        || [];
      _coursesMeta.departments  = data.departments  || [];
      // populate department dropdown in modal
      const sel = document.getElementById('newCourseDept');
      if (sel) {
        sel.innerHTML = '<option value="">— Select College —</option>' +
          _coursesMeta.departments.map(d => `<option value="${d.id}">${d.name} (${d.code})</option>`).join('');
      }
    }
  } catch(e) { console.warn('Could not load course meta:', e); }
}

async function loadCoursesFromDB() {
  try {
    const res  = await fetch('?action=get_courses', { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch(e) {
      const tb = document.getElementById('coursesTbody');
      if (tb) tb.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--danger)">Failed to parse server response.</td></tr>';
      return;
    }
    if (!data.success) {
      const tb = document.getElementById('coursesTbody');
      if (tb) tb.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--danger)">${data.message||'Error loading courses.'}</td></tr>`;
      return;
    }
    _adminCourses = data.courses || [];
    _renderCourseTable();
  } catch(e) {
    const tb = document.getElementById('coursesTbody');
    if (tb) tb.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--danger)">Network error loading courses.</td></tr>';
  }
}

function _renderCourseTable() {
  const tb = document.getElementById('coursesTbody');
  if (!tb) return;
  let visible = _adminCourses;
  if (_courseFilter === 'Published') visible = _adminCourses.filter(c => c.status === 'published');
  else if (_courseFilter === 'Draft')    visible = _adminCourses.filter(c => c.status === 'draft');
  else if (_courseFilter === 'Archived') visible = _adminCourses.filter(c => c.status === 'archived');
  if (!visible.length) {
    tb.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-3)">No courses found for this filter.</td></tr>`;
    return;
  }
  tb.innerHTML = visible.map(c => {
    const statusBadge = c.status === 'published' ? 'badge-green' : c.status === 'archived' ? 'badge-red' : 'badge-amber';
    const cap = c.capacity > 0 ? c.capacity : null;
    const pct  = cap ? Math.min(100, Math.round(c.enrolled / cap * 100)) : 0;
    const isArchived = c.status === 'archived';
    return `
    <tr id="courseRow-${c.id}">
      <td><span class="badge badge-purple">${_esc(c.code)}</span></td>
      <td style="font-weight:600;max-width:200px">${_esc(c.title)}</td>
      <td style="font-size:12px;color:var(--text-2)">${_esc(c.dept_name || '—')}</td>
      <td style="color:var(--text-2);font-size:12px">${_esc(c.instructor)}</td>
      <td>
        <div style="font-weight:600">${c.enrolled}${cap ? ' / '+cap : ''}</div>
        ${cap ? `<div class="progress-bar" style="width:72px;margin-top:4px"><div class="progress-fill purple" style="width:${pct}%"></div></div>` : ''}
      </td>
      <td style="color:var(--text-3)">${c.units}</td>
      <td><span class="badge ${statusBadge}">${c.status}</span></td>
      <td>
        <div style="display:flex;gap:3px;align-items:center">
          <button class="btn btn-ghost btn-sm" title="Edit course" onclick="editAdminCourse(${c.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('pencil')}</button>
          ${isArchived
            ? `<button class="btn btn-outline btn-sm" title="Restore course" onclick="promptArchiveCourse(${c.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('restore')}</button>
               <button class="btn btn-danger btn-sm" title="Delete course" onclick="promptDeleteCourse(${c.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('trash')}</button>`
            : `<button class="btn btn-outline btn-sm" title="Archive course" onclick="promptArchiveCourse(${c.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('archive')}</button>`
          }
        </div>
      </td>
    </tr>`;
  }).join('');
}

function _esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function onCourseDeptChange() {
  const deptId = parseInt(document.getElementById('newCourseDept').value) || 0;
  _populateCourseInstructors(deptId, 0);
}

function _populateCourseInstructors(deptId, selectedId) {
  const sel = document.getElementById('newCourseInstructor');
  if (!sel) return;
  const list = deptId
    ? _coursesMeta.instructors.filter(i => i.department_id === deptId)
    : _coursesMeta.instructors;
  if (!list.length) {
    sel.innerHTML = deptId
      ? '<option value="">— No instructors in this college —</option>'
      : '<option value="">— Select a college first —</option>';
    return;
  }
  sel.innerHTML = '<option value="">— Select Instructor (optional) —</option>' +
    list.map(i =>
      `<option value="${i.id}" ${i.id === selectedId ? 'selected' : ''}>${_esc(i.name)}</option>`
    ).join('');
}

function _ico(name) {
  const S = 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
  const W = 'xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"';
  const icons = {
    eye:     `<svg ${W} ${S}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`,
    pencil:  `<svg ${W} ${S}><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
    archive: `<svg ${W} ${S}><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`,
    restore: `<svg ${W} ${S}><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.5"/></svg>`,
    trash:   `<svg ${W} ${S}><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>`,
    plus:    `<svg ${W} ${S}><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>`,
    check:   `<svg ${W} ${S}><polyline points="20 6 9 17 4 12"/></svg>`,
    save:    `<svg ${W} ${S}><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>`,
    close:    `<svg ${W} ${S}><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
    email:    `<svg ${W} ${S}><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`,
    ban:      `<svg ${W} ${S}><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>`,
    download: `<svg ${W} ${S}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>`,
    upload:   `<svg ${W} ${S}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>`,
    user:     `<svg ${W} ${S}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    file:     `<svg ${W} ${S}><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>`,
  };
  return icons[name] || '';
}

function filterCoursesAdmin(filter, btn) {
  _courseFilter = filter;
  btn.closest('.filter-row').querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  _renderCourseTable();
}

function viewAdminCourse(id) {
  const c = _adminCourses.find(x => x.id === id);
  if (!c) return;
  const cap = c.capacity > 0 ? c.capacity : null;
  const pct = cap ? Math.min(100, Math.round(c.enrolled / cap * 100)) : 0;
  document.getElementById('viewCourseTitle').textContent = '📚 ' + c.code + ' — ' + c.title;
  document.getElementById('viewCourseContent').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
      ${[
        ['Course Code', c.code],
        ['Status', c.status],
        ['Units', c.units + ' unit(s)'],
        ['College', c.dept_name || '—'],
        ['Instructor', c.instructor],
        ['Semester', c.semester],
        ['Enrolled', c.enrolled + (cap ? ' / '+cap+' students' : ' students')],
        ['Created', c.created_at],
      ].map(([l,v]) => `
        <div class="form-group">
          <label class="form-label">${l}</label>
          <input type="text" value="${_esc(v)}" readonly style="width:100%;padding:10px 13px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px">
        </div>`).join('')}
    </div>
    ${c.description ? `<div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:12px;font-size:13px;color:var(--text-2);margin-bottom:12px">${_esc(c.description)}</div>` : ''}
    ${cap ? `<div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:10px">
      <div style="font-size:11px;color:var(--text-3);font-weight:700;text-transform:uppercase;margin-bottom:6px">Enrollment Progress</div>
      <div class="progress-bar" style="height:8px"><div class="progress-fill purple" style="width:${pct}%"></div></div>
      <div style="font-size:11px;color:var(--text-2);margin-top:5px">${pct}% capacity (${c.enrolled}/${cap})</div>
    </div>` : ''}`;
  document.getElementById('viewCourseFooter').innerHTML = `
    <button class="btn btn-outline btn-sm" onclick="closeModal('viewCourseModal')">Close</button>
    <button class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px" onclick="closeModal('viewCourseModal');editAdminCourse(${id})">${_ico('pencil')} Edit</button>`;
  openModal('viewCourseModal');
}

function editAdminCourse(id) {
  const c = _adminCourses.find(x => x.id === id);
  if (!c) return;
  // Populate the shared add/edit modal
  document.getElementById('addCourseModalTitle').textContent = 'Edit Course';
  document.getElementById('editCourseId').value = id;
  document.getElementById('newCourseTitle').value = c.title;
  document.getElementById('newCourseCode').value  = c.code;
  document.getElementById('newCourseUnits').value  = c.units;
  document.getElementById('newCourseDesc').value   = c.description || '';
  // Status dropdown — add 'archived' option if needed
  const statusSel = document.getElementById('newCourseStatus');
  statusSel.innerHTML = '<option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option>';
  statusSel.value = c.status;
  // College dropdown
  const deptSel = document.getElementById('newCourseDept');
  if (deptSel && _coursesMeta.departments.length) {
    deptSel.innerHTML = '<option value="">— Select College —</option>' +
      _coursesMeta.departments.map(d => `<option value="${d.id}" ${d.id === c.department_id ? 'selected' : ''}>${_esc(d.name)} (${_esc(d.code)})</option>`).join('');
  } else if (deptSel && c.department_id) {
    deptSel.innerHTML = `<option value="${c.department_id}" selected>${_esc(c.dept_name)}</option>`;
  }
  // Populate instructor dropdown filtered by selected department
  _populateCourseInstructors(c.department_id || 0, c.instructor_id || 0);
  document.getElementById('saveCourseBtn').innerHTML = _ico('save') + ' Save Changes';
  document.getElementById('saveCourseBtn').style.cssText += 'display:inline-flex;align-items:center;gap:6px';
  document.getElementById('addCourseStatus').textContent = '';
  openModal('addCourseModal');
}

function openAddCourseModal() {
  document.getElementById('addCourseModalTitle').textContent = 'Add New Course';
  document.getElementById('editCourseId').value  = '';
  document.getElementById('newCourseTitle').value = '';
  document.getElementById('newCourseCode').value  = '';
  document.getElementById('newCourseUnits').value = '3';
  document.getElementById('newCourseDesc').value  = '';
  // Reset status to published/draft only for new courses
  const statusSel = document.getElementById('newCourseStatus');
  statusSel.innerHTML = '<option value="draft">Draft</option><option value="published" selected>Published</option>';
  // Reload dept dropdown
  const deptSel = document.getElementById('newCourseDept');
  if (deptSel) {
    deptSel.innerHTML = '<option value="">— Select College —</option>' +
      _coursesMeta.departments.map(d => `<option value="${d.id}">${_esc(d.name)} (${_esc(d.code)})</option>`).join('');
  }
  // Reset instructor dropdown
  const instrSel = document.getElementById('newCourseInstructor');
  if (instrSel) instrSel.innerHTML = '<option value="">— Select a college first —</option>';
  document.getElementById('saveCourseBtn').innerHTML = _ico('plus') + ' Create Course';
  document.getElementById('saveCourseBtn').style.cssText += 'display:inline-flex;align-items:center;gap:6px';
  document.getElementById('addCourseStatus').textContent = '';
  openModal('addCourseModal');
}

async function saveCourse() {
  const id     = document.getElementById('editCourseId').value;
  const title  = document.getElementById('newCourseTitle').value.trim();
  const code   = document.getElementById('newCourseCode').value.trim().toUpperCase();
  const units  = document.getElementById('newCourseUnits').value;
  const dept   = document.getElementById('newCourseDept').value;
  const status = document.getElementById('newCourseStatus').value;
  const desc   = document.getElementById('newCourseDesc').value.trim();
  const statusEl = document.getElementById('addCourseStatus');
  const btn      = document.getElementById('saveCourseBtn');

  if (!title) { statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Course title is required.</span>'; return; }
  if (!code)  { statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Course code is required.</span>'; return; }
  if (!/^[A-Z0-9 \-\/]+$/.test(code)) { statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Course code may only contain letters, numbers, spaces, hyphens, or slashes.</span>'; return; }

  btn.disabled = true;
  statusEl.innerHTML = '<span style="color:var(--text-2)">⏳ Saving…</span>';

  const instrId = document.getElementById('newCourseInstructor')?.value || '';
  const body = new FormData();
  body.append('action', id ? 'update_course' : 'create_course');
  if (id) body.append('id', id);
  body.append('title', title);
  body.append('code', code);
  body.append('units', units);
  body.append('department_id', dept);
  body.append('status', status);
  body.append('description', desc);
  body.append('instructor_id', instrId);

  try {
    const res  = await fetch(id ? '?action=update_course' : '?action=create_course',
                             { method: 'POST', body, headers: {'X-Requested-With':'XMLHttpRequest'} });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch(e) { statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Server error.</span>'; btn.disabled = false; return; }
    if (data.success) {
      showToast(data.message, 'success');
      closeModal('addCourseModal');
      await loadCoursesFromDB();
    } else {
      statusEl.innerHTML = `<span style="color:var(--danger)">⚠ ${data.message}</span>`;
    }
  } catch(e) {
    statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Network error.</span>';
  }
  btn.disabled = false;
}

function promptDeleteCourse(id) {
  const c = _adminCourses.find(x => x.id === id);
  if (!c) return;
  _delCourseId = id;
  document.getElementById('delCourseNameLabel').textContent = `"${c.code} — ${c.title}"`;
  openModal('delCourseModal');
}

async function confirmDeleteCourse() {
  if (!_delCourseId) return;
  const body = new FormData();
  body.append('action', 'delete_course');
  body.append('id', _delCourseId);
  try {
    const res  = await fetch('?action=delete_course', { method: 'POST', body, headers: {'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    closeModal('delCourseModal');
    if (data.success) {
      showToast(data.message, 'success');
      await loadCoursesFromDB();
    } else {
      showToast('⚠ ' + data.message, 'error');
    }
  } catch(e) { showToast('⚠ Network error.', 'error'); }
  _delCourseId = null;
}

function promptArchiveCourse(id) {
  const c = _adminCourses.find(x => x.id === id);
  if (!c) return;
  _archiveCourseId = id;
  const isArchived = c.status === 'archived';
  document.getElementById('archiveCourseTitle').textContent = isArchived ? 'Restore Course' : 'Archive Course';
  document.getElementById('archiveCourseMsg').textContent   = isArchived
    ? `Restore "${c.code} — ${c.title}" to published status?`
    : `Archive "${c.code} — ${c.title}"? It will be hidden from active listings.`;
  document.getElementById('archiveReasonWrap').style.display = isArchived ? 'none' : '';
  document.getElementById('archiveCourseReason').value = '';
  const archBtn = document.getElementById('archiveCourseBtn');
  archBtn.innerHTML = (isArchived ? _ico('restore') : _ico('archive')) + (isArchived ? ' Restore' : ' Archive');
  archBtn.style.cssText += 'display:inline-flex;align-items:center;gap:6px';
  openModal('archiveCourseModal');
}

async function confirmArchiveCourse() {
  if (!_archiveCourseId) return;
  const body = new FormData();
  body.append('action', 'archive_course');
  body.append('id', _archiveCourseId);
  body.append('reason', document.getElementById('archiveCourseReason')?.value.trim() || '');
  try {
    const res  = await fetch('?action=archive_course', { method: 'POST', body, headers: {'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    closeModal('archiveCourseModal');
    if (data.success) {
      showToast(data.message, 'success');
      await loadCoursesFromDB();
    } else {
      showToast('⚠ ' + data.message, 'error');
    }
  } catch(e) { showToast('⚠ Network error.', 'error'); }
  _archiveCourseId = null;
}

// ===== ENROLLMENTS =====
// ===== ENROLLMENT MODULE STATE =====
let _enrollments = [];
let _enrollStats  = { total:0, enrolled:0, dropped:0, completed:0, failed:0 };
let _enrollSearch = '';
let _enrollStatus = '';
let _enrollCourse = '';
let _enrollStudents = [];
let _enrollSections = [];

async function loadEnrollmentsFromDB() {
  try {
    const params = new URLSearchParams({ action:'get_enrollments' });
    if (_enrollStatus) params.set('status', _enrollStatus);
    if (_enrollSearch) params.set('search', _enrollSearch);
    if (_enrollCourse) params.set('course', _enrollCourse);
    const [eRes, sRes] = await Promise.all([
      fetch('?' + params.toString(), { headers:{'X-Requested-With':'XMLHttpRequest'} }),
      fetch('?action=get_enrollment_stats', { headers:{'X-Requested-With':'XMLHttpRequest'} }),
    ]);
    const [eData, sData] = await Promise.all([eRes.json(), sRes.json()]);
    if (eData.success) _enrollments = eData.enrollments;
    if (sData.success) _enrollStats  = sData.stats;
  } catch(e) { console.error('loadEnrollmentsFromDB:', e); }
}

function renderEnrollments() {
  const statusBadge = s => s==='enrolled'?'badge-green':s==='dropped'?'badge-red':s==='completed'?'badge-blue':'badge-amber';
  const uniqueCodes = [...new Set(_enrollments.map(e=>e.course_code))].sort();

  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Enrollments</span></div>
    <h1>Enrollment Management</h1>
    <p>Manage all student course enrollments — only administrators can enroll or drop students.</p>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
    <div class="stat-card" onclick="applyEnrollFilter('','')">
      <div class="stat-icon" style="background:var(--surface-2)">📋</div>
      <div><div class="stat-val">${_enrollStats.total.toLocaleString()}</div><div class="stat-label">Total Enrollments</div></div>
    </div>
    <div class="stat-card" onclick="applyEnrollFilter('enrolled','')">
      <div class="stat-icon" style="background:rgba(16,185,129,.12)">✅</div>
      <div><div class="stat-val" style="color:var(--success)">${_enrollStats.enrolled.toLocaleString()}</div><div class="stat-label">Active</div></div>
    </div>
    <div class="stat-card" onclick="applyEnrollFilter('dropped','')">
      <div class="stat-icon" style="background:rgba(216,64,64,.12)">🚪</div>
      <div><div class="stat-val" style="color:var(--danger)">${_enrollStats.dropped.toLocaleString()}</div><div class="stat-label">Dropped</div></div>
    </div>
    <div class="stat-card" onclick="applyEnrollFilter('completed','')">
      <div class="stat-icon" style="background:rgba(59,130,246,.12)">🎓</div>
      <div><div class="stat-val" style="color:var(--info)">${_enrollStats.completed.toLocaleString()}</div><div class="stat-label">Completed</div></div>
    </div>
  </div>

  <div class="card">
    <div style="display:flex;gap:8px;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;align-items:center">
      <div style="display:flex;gap:8px;flex-wrap:wrap;flex:1">
        <div class="search-wrap" style="max-width:260px">
          <input type="text" id="enrollSearchInput" placeholder="Search student or course..." value="${_enrollSearch}"
            oninput="debounceEnrollSearch(this.value)">
        </div>
        <select id="enrollStatusSel" onchange="applyEnrollFilter(this.value, document.getElementById('enrollCourseSel').value)"
          style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;font-size:12px;background:var(--surface);color:var(--text)">
          <option value="" ${_enrollStatus===''?'selected':''}>All Statuses</option>
          <option value="enrolled" ${_enrollStatus==='enrolled'?'selected':''}>Enrolled</option>
          <option value="dropped"  ${_enrollStatus==='dropped'?'selected':''}>Dropped</option>
          <option value="completed"${_enrollStatus==='completed'?'selected':''}>Completed</option>
          <option value="failed"   ${_enrollStatus==='failed'?'selected':''}>Failed</option>
        </select>
        <select id="enrollCourseSel" onchange="applyEnrollFilter(document.getElementById('enrollStatusSel').value, this.value)"
          style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;font-size:12px;background:var(--surface);color:var(--text)">
          <option value="">All Courses</option>
          ${uniqueCodes.map(c=>`<option value="${c}" ${_enrollCourse===c?'selected':''}>${c}</option>`).join('')}
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button class="btn btn-outline btn-sm" onclick="exportEnrollCSV()">📤 Export CSV</button>
        <button class="btn btn-primary btn-sm" onclick="openEnrollModal()">+ Enroll Student</button>
      </div>
    </div>
    <div style="font-size:12px;color:var(--text-3);margin-bottom:10px">${_enrollments.length} record${_enrollments.length!==1?'s':''} shown</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Student</th>
            <th>Student No.</th>
            <th>Course</th>
            <th>Section</th>
            <th>Instructor</th>
            <th>Date Enrolled</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="enrollTbody">
          ${_enrollments.length === 0
            ? `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-3)">No enrollment records found.</td></tr>`
            : _enrollments.map(e => `
          <tr id="enroll-row-${e.id}">
            <td>
              <div style="font-weight:600">${e.student_name}</div>
              <div style="font-size:11px;color:var(--text-3)">${e.student_email}</div>
            </td>
            <td style="font-size:12px;color:var(--text-2)">${e.student_number || '—'}</td>
            <td>
              <span class="badge badge-purple">${e.course_code}</span>
              <div style="font-size:11px;color:var(--text-2);margin-top:2px">${e.course_title}</div>
            </td>
            <td style="font-size:12px;color:var(--text-2)">${e.section_code}</td>
            <td style="font-size:12px;color:var(--text-2)">${e.instructor}</td>
            <td style="font-size:12px;color:var(--text-2)">${e.enrolled_at}</td>
            <td><span class="badge ${statusBadge(e.status)}">${e.status}</span></td>
            <td>
              <div style="display:flex;gap:4px">
                ${e.status === 'enrolled'
                  ? `<button class="btn btn-danger btn-sm" onclick="confirmDropEnrollment(${e.id},'${e.student_name.replace(/'/g,"\\'")}','${e.course_code}')">Drop</button>`
                  : `<span style="font-size:11px;color:var(--text-3);padding:4px 8px">${e.status}</span>`
                }
              </div>
            </td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>
  </div>`;
}

function applyEnrollFilter(status, course) {
  _enrollStatus = status;
  _enrollCourse = course;
  loadEnrollmentsFromDB().then(() => {
    const area = document.getElementById('contentArea');
    if (area) area.innerHTML = renderEnrollments();
  });
}

let _enrollSearchTimer = null;
function debounceEnrollSearch(val) {
  _enrollSearch = val;
  clearTimeout(_enrollSearchTimer);
  _enrollSearchTimer = setTimeout(() => {
    loadEnrollmentsFromDB().then(() => {
      const area = document.getElementById('contentArea');
      if (area) area.innerHTML = renderEnrollments();
    });
  }, 400);
}

function confirmDropEnrollment(id, studentName, courseCode) {
  if (!confirm(`Drop ${studentName} from ${courseCode}? This cannot be undone.`)) return;
  dropEnrollment(id);
}

async function dropEnrollment(id) {
  try {
    const fd = new FormData();
    fd.append('action', 'drop_enrollment');
    fd.append('id', id);
    const res  = await fetch('?action=drop_enrollment', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      await loadEnrollmentsFromDB();
      const area = document.getElementById('contentArea');
      if (area) area.innerHTML = renderEnrollments();
    } else {
      showToast(data.message || 'Failed to drop enrollment', 'error');
    }
  } catch(e) {
    showToast('Network error', 'error');
  }
}

/* ── Enrollment modal state ── */
let _enrollStudentSections = [];
let _enrollActiveTab = 'section';

/* ── Tab switcher ── */
function switchEnrollTab(tab, btn) {
  _enrollActiveTab = tab;
  document.getElementById('enrollTabSection').style.display    = tab === 'section'    ? '' : 'none';
  document.getElementById('enrollTabIndividual').style.display = tab === 'individual' ? '' : 'none';
  document.querySelectorAll('#enrollTabBar .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('enrollModalMsg').textContent = '';
  document.getElementById('enrollSubmitBtn').textContent = tab === 'section' ? 'Enroll Section' : 'Enroll Student';
}

/* ── Helper: build flat section options for both tabs ── */
function _buildAllSectionOptions(selectId) {
  const el = document.getElementById(selectId);
  if (!el) return;
  if (_enrollSections.length === 0) {
    el.innerHTML = '<option value="">No sections available</option>';
    return;
  }
  el.innerHTML =
    '<option value="">— Select course —</option>' +
    _enrollSections.map(s =>
      `<option value="${s.id}">${s.course_code} — ${s.course_title}</option>`
    ).join('');
}

/* ── Open modal ── */
async function openEnrollModal() {
  openModal('enrollModal');
  document.getElementById('enrollModalMsg').textContent = '';
  document.getElementById('enrollBulkPreview').textContent = '';
  document.getElementById('enrollBulkSectionSel').innerHTML = '<option value="">Loading…</option>';
  document.getElementById('enrollBulkStudentSectionSel').innerHTML = '<option value="">— Select student section —</option>';
  document.getElementById('enrollIndSectionSel').innerHTML = '<option value="">Loading…</option>';
  document.getElementById('enrollStudentGroup').style.display = 'none';
  document.getElementById('enrollStudentSel').innerHTML = '<option value="">— Select student —</option>';

  try {
    const [secRes, stuSecRes] = await Promise.all([
      fetch('?action=get_sections_list',    { headers:{'X-Requested-With':'XMLHttpRequest'} }),
      fetch('?action=get_student_sections', { headers:{'X-Requested-With':'XMLHttpRequest'} }),
    ]);
    const [secData, stuSecData] = await Promise.all([secRes.json(), stuSecRes.json()]);
    _enrollSections        = secData.success    ? secData.sections    : [];
    _enrollStudentSections = stuSecData.success ? stuSecData.sections : [];

    // Populate both section dropdowns with all available sections (flat list)
    _buildAllSectionOptions('enrollBulkSectionSel');
    _buildAllSectionOptions('enrollIndSectionSel');

    // Populate student section dropdown from user management data
    const stuSecSel = document.getElementById('enrollBulkStudentSectionSel');
    stuSecSel.innerHTML =
      '<option value="">— Select student section —</option>' +
      _enrollStudentSections.map(s =>
        `<option value="${s.section}">${s.section} (${s.student_count} student${s.student_count!==1?'s':''})</option>`
      ).join('');
  } catch(e) {
    document.getElementById('enrollBulkSectionSel').innerHTML = '<option value="">Error loading</option>';
    document.getElementById('enrollIndSectionSel').innerHTML  = '<option value="">Error loading</option>';
  }
}

/* ── Bulk tab: section or student section changed → update preview ── */
function onBulkChange() {
  const sectionId      = parseInt(document.getElementById('enrollBulkSectionSel').value);
  const studentSection = document.getElementById('enrollBulkStudentSectionSel').value;
  const preview        = document.getElementById('enrollBulkPreview');
  document.getElementById('enrollModalMsg').textContent = '';
  preview.textContent = '';

  if (!sectionId || !studentSection) return;

  const match = _enrollSections.find(s => s.id === sectionId);
  if (!match) return;

  const stuSecObj = _enrollStudentSections.find(s => s.section === studentSection);
  const count = stuSecObj ? stuSecObj.student_count : '?';
  const cap   = match.max_students ? ` (capacity: ${match.enrolled}/${match.max_students})` : '';

  preview.style.color = 'var(--success)';
  preview.textContent =
    `${count} student(s) from section "${studentSection}" will be enrolled into ` +
    `${match.section_code} — ${match.course_code}${cap}. Already-enrolled students are skipped.`;
}

/* ── Individual tab: section changed → load available students ── */
async function onIndChange() {
  const sectionId    = parseInt(document.getElementById('enrollIndSectionSel').value);
  const studentGroup = document.getElementById('enrollStudentGroup');
  const studentEl    = document.getElementById('enrollStudentSel');
  document.getElementById('enrollModalMsg').textContent = '';

  if (!sectionId) { studentGroup.style.display = 'none'; return; }

  const match = _enrollSections.find(s => s.id === sectionId);
  if (!match) { studentGroup.style.display = 'none'; return; }

  studentGroup.style.display = '';
  studentEl.innerHTML = '<option value="">Loading students…</option>';
  try {
    const res  = await fetch(`?action=get_available_students&section_id=${match.id}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    const students = data.success ? data.students : [];
    studentEl.innerHTML = students.length === 0
      ? '<option value="">All students already enrolled</option>'
      : '<option value="">— Select student —</option>' +
        students.map(s => `<option value="${s.id}">${s.name}${s.student_number ? ' (' + s.student_number + ')' : ''}</option>`).join('');
  } catch(e) {
    studentEl.innerHTML = '<option value="">Error loading students</option>';
  }
}

/* ── Dispatch ── */
async function submitEnrollAction() {
  if (_enrollActiveTab === 'section') {
    await submitBulkEnroll();
  } else {
    await submitEnrollStudent();
  }
}

/* ── Bulk enroll submit ── */
async function submitBulkEnroll() {
  const sectionId      = parseInt(document.getElementById('enrollBulkSectionSel').value);
  const studentSection = document.getElementById('enrollBulkStudentSectionSel').value;
  const msgEl = document.getElementById('enrollModalMsg');
  if (!sectionId)      { msgEl.style.color='var(--danger)'; msgEl.textContent='Please select a section.'; return; }
  if (!studentSection) { msgEl.style.color='var(--danger)'; msgEl.textContent='Please select a student section to enroll.'; return; }

  const match = _enrollSections.find(s => s.id === sectionId);
  if (!match) { msgEl.style.color='var(--danger)'; msgEl.textContent='Invalid section selected.'; return; }

  const btn = document.getElementById('enrollSubmitBtn');
  btn.disabled = true; btn.textContent = 'Enrolling…'; msgEl.textContent = '';
  try {
    const fd = new FormData();
    fd.append('action',          'bulk_enroll_by_section');
    fd.append('section_id',      match.id);
    fd.append('student_section', studentSection);
    const res  = await fetch('?action=bulk_enroll_by_section', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      closeModal('enrollModal');
      await loadEnrollmentsFromDB();
      const area = document.getElementById('contentArea');
      if (area) area.innerHTML = renderEnrollments();
    } else {
      msgEl.style.color = 'var(--danger)'; msgEl.textContent = data.message || 'Enrollment failed.';
    }
  } catch(e) {
    msgEl.style.color = 'var(--danger)'; msgEl.textContent = 'Network error. Please try again.';
  }
  btn.disabled = false; btn.textContent = 'Enroll Section';
}

/* ── Individual enroll submit ── */
async function submitEnrollStudent() {
  const sectionId = parseInt(document.getElementById('enrollIndSectionSel').value);
  const studentId = document.getElementById('enrollStudentSel').value;
  const msgEl = document.getElementById('enrollModalMsg');
  if (!sectionId)  { msgEl.style.color='var(--danger)'; msgEl.textContent='Please select a section.'; return; }
  if (!studentId)  { msgEl.style.color='var(--danger)'; msgEl.textContent='Please select a student.'; return; }

  const btn = document.getElementById('enrollSubmitBtn');
  btn.disabled = true; btn.textContent = 'Enrolling…'; msgEl.textContent = '';
  try {
    const fd = new FormData();
    fd.append('action',     'enroll_student');
    fd.append('student_id', studentId);
    fd.append('section_id', sectionId);
    const res  = await fetch('?action=enroll_student', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      closeModal('enrollModal');
      await loadEnrollmentsFromDB();
      const area = document.getElementById('contentArea');
      if (area) area.innerHTML = renderEnrollments();
    } else {
      msgEl.style.color = 'var(--danger)'; msgEl.textContent = data.message || 'Enrollment failed.';
    }
  } catch(e) {
    msgEl.style.color = 'var(--danger)'; msgEl.textContent = 'Network error. Please try again.';
  }
  btn.disabled = false; btn.textContent = 'Enroll Student';
}

async function openStudentEnrollHistory(userId, userName) {
  document.getElementById('enrollHistoryStudentName').textContent = userName;
  document.getElementById('enrollHistorySummary').innerHTML = '';
  document.getElementById('enrollHistoryTbody').innerHTML =
    '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-3)">Loading…</td></tr>';
  openModal('studentEnrollHistoryModal');

  try {
    const res  = await fetch(`?action=get_student_enrollments&student_id=${userId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (!data.success) {
      document.getElementById('enrollHistoryTbody').innerHTML =
        `<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--danger)">${data.message || 'Failed to load history.'}</td></tr>`;
      return;
    }

    const history = data.history || [];
    const counts  = data.counts  || {};

    // Summary chips
    const chipDef = [
      { key:'enrolled',  label:'Active',    cls:'badge-green' },
      { key:'completed', label:'Completed', cls:'badge-blue'  },
      { key:'dropped',   label:'Dropped',   cls:'badge-red'   },
      { key:'failed',    label:'Failed',    cls:'badge-amber' },
    ];
    const total = history.length;
    document.getElementById('enrollHistorySummary').innerHTML =
      `<span class="badge badge-gray">${total} total enrollment${total !== 1 ? 's' : ''}</span>` +
      chipDef.filter(c => (counts[c.key] || 0) > 0)
             .map(c => `<span class="badge ${c.cls}">${counts[c.key]} ${c.label}</span>`)
             .join('');

    if (history.length === 0) {
      document.getElementById('enrollHistoryTbody').innerHTML =
        '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-3)">No enrollment records found for this student.</td></tr>';
      return;
    }

    const statusBadge = s =>
      s === 'enrolled'  ? 'badge-green'  :
      s === 'completed' ? 'badge-blue'   :
      s === 'dropped'   ? 'badge-red'    : 'badge-amber';

    document.getElementById('enrollHistoryTbody').innerHTML = history.map(e => `
      <tr>
        <td>
          <div style="font-weight:600">${e.course_code}</div>
          <div style="font-size:11px;color:var(--text-2);max-width:200px;white-space:normal">${e.course_title}</div>
          ${e.units ? `<div style="font-size:10px;color:var(--text-3)">${e.units} units</div>` : ''}
        </td>
        <td style="font-size:12px">${e.section_code}</td>
        <td style="font-size:12px;color:var(--text-2)">${e.term}</td>
        <td style="font-size:12px;color:var(--text-2)">${e.instructor}</td>
        <td style="font-size:12px;color:var(--text-2)">${e.enrolled_at}</td>
        <td><span class="badge ${statusBadge(e.status)}">${e.status}</span></td>
        <td style="font-size:12px;font-weight:600;color:${e.final_grade ? 'var(--text)' : 'var(--text-3)'}">
          ${e.final_grade !== null ? e.final_grade : '—'}
        </td>
      </tr>`).join('');

  } catch (err) {
    console.error('openStudentEnrollHistory:', err);
    document.getElementById('enrollHistoryTbody').innerHTML =
      '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--danger)">Network error loading history.</td></tr>';
  }
}

function exportEnrollCSV() {
  if (!_enrollments.length) { showToast('No records to export.', 'error'); return; }
  const headers = ['Student','Email','Student No.','Course Code','Course Title','Section','Instructor','Date Enrolled','Status'];
  const rows = _enrollments.map(e => [
    e.student_name, e.student_email, e.student_number,
    e.course_code, e.course_title, e.section_code,
    e.instructor, e.enrolled_at, e.status,
  ].map(v => '"' + String(v||'').replace(/"/g,'""') + '"'));
  const csv = [headers, ...rows].map(r=>r.join(',')).join('\n');
  const a = document.createElement('a');
  a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
  a.download = 'enrollments_export.csv';
  a.click();
  showToast('CSV exported successfully.', 'success');
}

// ===== DEPARTMENTS =====
// ===== DEPARTMENTS — module-level state & helpers =====
let _depts = [];
let _delDeptId = null;
const DEPT_COLORS = ['purple','blue','green','amber','teal','rose'];

async function loadDeptsFromDB() {
  try {
    const res  = await fetch('?action=get_departments', { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch(e) {
      console.error('dept JSON parse error:', text.substring(0,300));
      const g = document.getElementById('deptsGrid');
      if (g) g.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--danger);padding:32px">Failed to load colleges.</div>';
      return;
    }
    if (!data.success) {
      const g = document.getElementById('deptsGrid');
      if (g) g.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--danger);padding:32px">' + (data.message||'Error') + '</div>';
      return;
    }
    _depts = data.departments;
    renderDeptsGrid();
  } catch(e) {
    console.error('loadDeptsFromDB error:', e);
  }
}

function renderDeptsGrid() {
  const grid = document.getElementById('deptsGrid');
  if (!grid) return;
  if (!_depts.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-2);padding:40px">No colleges yet.</div>';
    return;
  }
  const colorMap = {purple:'var(--primary)',blue:'var(--info)',green:'var(--success)',amber:'var(--warning)',teal:'#14b8a6',rose:'#fb7185'};
  grid.innerHTML = _depts.map((d,i) => {
    const color = colorMap[DEPT_COLORS[i % DEPT_COLORS.length]];
    const safeName = d.name.replace(/'/g,"\\'");
    return `
    <div class="card" style="border-left:4px solid ${color}">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
        <div>
          <div style="margin-bottom:4px">
            <span style="background:${color}22;color:${color};font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.5px">${d.code}</span>
          </div>
          <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-top:4px">${d.name}</div>
          <div style="font-size:12px;color:var(--text-2);margin-top:3px">Head: ${d.head}</div>
          ${d.description ? `<div style="font-size:12px;color:var(--text-3);margin-top:4px;font-style:italic">${d.description}</div>` : ''}
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0">
          <button class="btn btn-ghost btn-sm" title="Edit" onclick="_editDept(${d.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('pencil')}</button>
          <button class="btn btn-danger btn-sm" title="Delete" onclick="_promptDeleteDept(${d.id},'${safeName}')" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('trash')}</button>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;text-align:center">
        ${[['📚','Courses',d.course_count],['👨‍🏫','Faculty',d.faculty_count],['🎓','Students',d.student_count]].map(([ic,lbl,val])=>`
          <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:10px">
            <div style="font-size:16px;margin-bottom:2px">${ic}</div>
            <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700">${val}</div>
            <div style="font-size:11px;color:var(--text-2)">${lbl}</div>
          </div>`).join('')}
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-outline btn-sm" onclick="_showDeptCourses(${d.id},'${safeName}')">📚 Courses</button>
        <button class="btn btn-outline btn-sm" onclick="_showDeptFaculty(${d.id},'${safeName}')">👥 Faculty</button>
        <button class="btn btn-outline btn-sm" onclick="_showDeptStudents(${d.id},'${safeName}')">🎓 Students</button>
        <button class="btn btn-ghost btn-sm"   onclick="_showDeptReport(${d.id},'${safeName}')">📊 Report</button>
      </div>
    </div>`;
  }).join('');
}

async function _fetchDeptDetail(id) {
  const res  = await fetch('?action=get_dept_detail&dept_id='+id, { headers:{'X-Requested-With':'XMLHttpRequest'} });
  const text = await res.text();
  try { return JSON.parse(text); } catch(e) { return {success:false,message:'Parse error'}; }
}

async function _showDeptCourses(id, name) {
  document.getElementById('deptDetailTitle').textContent = '📚 ' + name + ' — Courses';
  document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--text-2);font-size:13px;padding:12px 0">⏳ Loading…</p>';
  openModal('deptDetailModal');
  const data = await _fetchDeptDetail(id);
  if (!data.success) { document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--danger)">⚠ '+data.message+'</p>'; return; }
  const courses = data.courses;
  document.getElementById('deptDetailContent').innerHTML = courses.length ? `
    <p style="font-size:12px;color:var(--text-2);margin-bottom:12px">${courses.length} course${courses.length!==1?'s':''} in this college</p>
    <div style="display:flex;flex-direction:column;gap:6px;max-height:340px;overflow-y:auto">
      ${courses.map(c=>`
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface-2);border-radius:var(--radius-sm)">
          <div>
            <div style="font-size:13px;font-weight:700">${c.code}</div>
            <div style="font-size:12px;color:var(--text-2)">${c.title}</div>
          </div>
          <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;background:${c.status==='published'?'rgba(22,163,74,.12)':'var(--surface-2)'};color:${c.status==='published'?'#16a34a':'var(--text-3)'}">${c.status}</span>
        </div>`).join('')}
    </div>` : '<p style="color:var(--text-2);font-size:13px;padding:12px 0">No courses linked to this college yet.</p>';
}

async function _showDeptFaculty(id, name) {
  document.getElementById('deptDetailTitle').textContent = '👥 ' + name + ' — Faculty';
  document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--text-2);font-size:13px;padding:12px 0">⏳ Loading…</p>';
  openModal('deptDetailModal');
  const data = await _fetchDeptDetail(id);
  if (!data.success) { document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--danger)">⚠ '+data.message+'</p>'; return; }
  const faculty = data.faculty;
  document.getElementById('deptDetailContent').innerHTML = faculty.length ? `
    <p style="font-size:12px;color:var(--text-2);margin-bottom:12px">${faculty.length} faculty member${faculty.length!==1?'s':''}</p>
    <div style="display:flex;flex-direction:column;gap:6px;max-height:340px;overflow-y:auto">
      ${faculty.map(f=>{
        const initials = f.name.split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase();
        return `
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--surface-2);border-radius:var(--radius-sm)">
          <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0">${initials}</div>
          <div>
            <div style="font-size:13px;font-weight:600">${f.name}</div>
            <div style="font-size:11px;color:var(--text-2)">${f.designation} · ${f.employee_id}</div>
          </div>
        </div>`;
      }).join('')}
    </div>` : '<p style="color:var(--text-2);font-size:13px;padding:12px 0">No faculty assigned to this college yet.</p>';
}

async function _showDeptStudents(id, name) {
  document.getElementById('deptDetailTitle').textContent = '🎓 ' + name + ' — Students';
  document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--text-2);font-size:13px;padding:12px 0">⏳ Loading…</p>';
  openModal('deptDetailModal');
  try {
    const res  = await fetch('?action=get_dept_students&dept_id=' + id, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    if (!data.success) { document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--danger)">⚠ ' + (data.message||'Error') + '</p>'; return; }
    const students = data.students;
    const statusColor = s => s==='active'?'#16a34a':s==='pending'?'#d97706':'#dc2626';
    const statusBg    = s => s==='active'?'rgba(22,163,74,.12)':s==='pending'?'rgba(217,119,6,.12)':'rgba(220,38,38,.12)';
    const yrLabel = y => y ? `Year ${y}` : '';
    document.getElementById('deptDetailContent').innerHTML = students.length ? `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <p style="font-size:12px;color:var(--text-2);margin:0">${students.length} student${students.length!==1?'s':''} enrolled in this college</p>
        <input id="deptStudentSearch" type="text" placeholder="Search…" oninput="_filterDeptStudents(this.value)"
          style="font-size:12px;padding:4px 10px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--surface-2);color:var(--text-1);width:140px">
      </div>
      <div id="deptStudentList" style="display:flex;flex-direction:column;gap:6px;max-height:380px;overflow-y:auto">
        ${students.map(s => {
          const initials = (s.name||'?').split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase();
          const avatar = s.avatar_url
            ? `<img src="${s.avatar_url}" alt="" style="width:34px;height:34px;border-radius:8px;object-fit:cover;flex-shrink:0">`
            : `<div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0">${initials}</div>`;
          const meta = [s.program, yrLabel(s.year_level), s.section].filter(Boolean).join(' · ');
          return `
          <div class="dept-stu-row" data-name="${(s.name||'').toLowerCase()}" data-id="${(s.student_id||'').toLowerCase()}" data-program="${(s.program||'').toLowerCase()}"
            style="display:flex;align-items:center;gap:12px;padding:9px 14px;background:var(--surface-2);border-radius:var(--radius-sm)">
            ${avatar}
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${s.name||'—'}</div>
              <div style="font-size:11px;color:var(--text-2)">${s.student_id ? s.student_id+' · ' : ''}${meta}</div>
            </div>
            <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;flex-shrink:0;background:${statusBg(s.status)};color:${statusColor(s.status)}">${s.status}</span>
          </div>`;
        }).join('')}
      </div>` : '<p style="color:var(--text-2);font-size:13px;padding:12px 0">No students registered under this college yet.</p>';
  } catch(e) {
    document.getElementById('deptDetailContent').innerHTML = '<p style="color:var(--danger)">⚠ Network error.</p>';
  }
}

function _filterDeptStudents(q) {
  const term = q.toLowerCase().trim();
  document.querySelectorAll('#deptStudentList .dept-stu-row').forEach(row => {
    const match = !term
      || row.dataset.name.includes(term)
      || row.dataset.id.includes(term)
      || row.dataset.program.includes(term);
    row.style.display = match ? '' : 'none';
  });
}

function _showDeptReport(id, name) {
  const d = _depts.find(x=>x.id===id);
  if (!d) return;
  document.getElementById('deptDetailTitle').textContent = '📊 ' + name + ' — Summary';
  document.getElementById('deptDetailContent').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
      ${[['📚 Courses',d.course_count],['👥 Faculty',d.faculty_count],['🎓 Students',d.student_count],['🏛 Code',d.code]].map(([l,v])=>`
        <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:14px;text-align:center">
          <div style="font-size:11px;color:var(--text-3);margin-bottom:4px">${l}</div>
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:var(--primary)">${v}</div>
        </div>`).join('')}
    </div>
    <div style="font-size:12px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">College Head</div>
    <div style="padding:10px 14px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:13px;font-weight:600;margin-bottom:6px">${d.head}</div>
    <div style="font-size:11px;color:var(--text-3)">Created: ${d.created_at}</div>`;
  openModal('deptDetailModal');
}

function openAddDeptModal() {
  document.getElementById('deptModalTitle').textContent = '🏛️ Add College';
  document.getElementById('editDeptId').value = '';
  document.getElementById('newDeptCode').value = '';
  document.getElementById('newDeptName').value = '';
  document.getElementById('newDeptDesc').value = '';
  document.getElementById('deptSaveBtn').textContent = '✅ Create';
  document.getElementById('deptModalStatus').textContent = '';
  openModal('addDeptModal');
}

function _editDept(id) {
  const d = _depts.find(x=>x.id===id);
  if (!d) return;
  document.getElementById('deptModalTitle').textContent = 'Edit College';
  document.getElementById('editDeptId').value = d.id;
  document.getElementById('newDeptCode').value = d.code;
  document.getElementById('newDeptName').value = d.name;
  document.getElementById('newDeptDesc').value = d.description || '';
  document.getElementById('deptSaveBtn').textContent = '💾 Save Changes';
  document.getElementById('deptModalStatus').textContent = '';
  openModal('addDeptModal');
}

async function saveDept() {
  const id   = document.getElementById('editDeptId').value;
  const code = document.getElementById('newDeptCode').value.trim().toUpperCase();
  const name = document.getElementById('newDeptName').value.trim();
  const desc = document.getElementById('newDeptDesc').value.trim();
  const statusEl = document.getElementById('deptModalStatus');
  const btn      = document.getElementById('deptSaveBtn');
  if (!code || !name) { statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Code and name are required.</span>'; return; }
  btn.disabled = true;
  statusEl.innerHTML = '<span style="color:var(--text-2)">⏳ Saving…</span>';
  const body = new FormData();
  body.append('action', id ? 'update_department' : 'create_department');
  if (id) body.append('id', id);
  body.append('code', code);
  body.append('name', name);
  body.append('description', desc);
  try {
    const res  = await fetch(id ? '?action=update_department' : '?action=create_department',
                             { method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'} });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch(e) { statusEl.innerHTML='<span style="color:var(--danger)">⚠ Server error.</span>'; btn.disabled=false; return; }
    if (data.success) { showToast(data.message,'success'); closeModal('addDeptModal'); await loadDeptsFromDB(); }
    else statusEl.innerHTML = '<span style="color:var(--danger)">⚠ ' + data.message + '</span>';
  } catch(e) { statusEl.innerHTML = '<span style="color:var(--danger)">⚠ Network error.</span>'; }
  btn.disabled = false;
}

function _promptDeleteDept(id, name) {
  _delDeptId = id;
  document.getElementById('delDeptName').textContent = name;
  openModal('delDeptModal');
}

async function confirmDeleteDept() {
  if (!_delDeptId) return;
  const body = new FormData();
  body.append('action', 'delete_department');
  body.append('id', _delDeptId);
  try {
    const res  = await fetch('?action=delete_department', { method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    closeModal('delDeptModal');
    if (data.success) { showToast(data.message,'success'); await loadDeptsFromDB(); }
    else showToast('⚠ ' + data.message, 'error');
  } catch(e) { showToast('⚠ Network error.','error'); }
  _delDeptId = null;
}

function renderDepartments() {
  // Trigger async load after the HTML is in the DOM
  setTimeout(loadDeptsFromDB, 0);
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Colleges</span></div>
    <h1>Colleges</h1>
    <p>Manage academic colleges and their resources</p>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
    <button class="btn btn-primary btn-sm" onclick="openAddDeptModal()">+ Add College</button>
  </div>
  <div id="deptsGrid" class="grid-2">
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-2)">⏳ Loading colleges…</div>
  </div>

  <!-- Dept Detail Modal -->
  <div class="modal-overlay" id="deptDetailModal">
    <div class="modal" style="max-width:520px">
      <div class="modal-header">
        <h2 id="deptDetailTitle">College Details</h2>
        <button class="btn btn-ghost btn-sm" onclick="closeModal('deptDetailModal')">✕</button>
      </div>
      <div id="deptDetailContent" style="padding:4px 0 8px"></div>
      <div class="modal-footer">
        <button class="btn btn-outline btn-sm" onclick="closeModal('deptDetailModal')">Close</button>
      </div>
    </div>
  </div>

  <!-- Add / Edit Dept Modal -->
  <div class="modal-overlay" id="addDeptModal">
    <div class="modal" style="max-width:460px">
      <div class="modal-header">
        <h2 id="deptModalTitle">🏛️ Add College</h2>
        <button class="btn btn-ghost btn-sm" onclick="closeModal('addDeptModal')">✕</button>
      </div>
      <input type="hidden" id="editDeptId" value="">
      <div class="form-group">
        <label class="form-label">Code <span style="color:var(--danger)">*</span></label>
        <input type="text" id="newDeptCode" placeholder="e.g. CS" maxlength="20" style="text-transform:uppercase">
      </div>
      <div class="form-group">
        <label class="form-label">College Name <span style="color:var(--danger)">*</span></label>
        <input type="text" id="newDeptName" placeholder="e.g. College of Natural Sciences">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <input type="text" id="newDeptDesc" placeholder="Optional short description">
      </div>
      <div id="deptModalStatus" style="min-height:20px;font-size:13px;margin:4px 0"></div>
      <div class="modal-footer">
        <button class="btn btn-outline btn-sm" onclick="closeModal('addDeptModal')">Cancel</button>
        <button class="btn btn-primary btn-sm" id="deptSaveBtn" onclick="saveDept()">✅ Create</button>
      </div>
    </div>
  </div>

  <!-- Confirm Delete Modal -->
  <div class="modal-overlay" id="delDeptModal">
    <div class="modal" style="max-width:380px">
      <div class="modal-header">
        <h2>🗑 Delete College</h2>
        <button class="btn btn-ghost btn-sm" onclick="closeModal('delDeptModal')">✕</button>
      </div>
      <p style="margin:12px 0 20px;font-size:14px;color:var(--text-2)">
        Delete <strong id="delDeptName"></strong>? This cannot be undone.
      </p>
      <div class="modal-footer">
        <button class="btn btn-outline btn-sm" onclick="closeModal('delDeptModal')">Cancel</button>
        <button class="btn btn-danger btn-sm" onclick="confirmDeleteDept()" style="display:inline-flex;align-items:center;gap:6px">${_ico('trash')} Delete</button>
      </div>
    </div>
  </div>`;
}

// ===== ANNOUNCEMENTS =====
let adminAnnouncements = [];

function renderAnnouncements() {
  _annFilter = 'all';
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Announcements</span></div>
    <h1>Announcements</h1>
    <p>Broadcast messages to students, instructors, or all users</p>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div class="tabbar" id="annTabbar">
      <button class="tab-btn active" onclick="filterAnn('all',this)">All</button>
      <button class="tab-btn" onclick="filterAnn('sent',this)">Sent</button>
      <button class="tab-btn" onclick="filterAnn('scheduled',this)">Scheduled</button>
    </div>
    <button class="btn btn-primary" onclick="openAnnounceModal()">📢 New Announcement</button>
  </div>

  <div class="card" id="annTableCard">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Title</th><th>Target</th><th>Priority</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="annTbody"></tbody>
      </table>
    </div>
  </div>`;
}
// ===== REPORTS =====
function renderReports() {
  const reps = [
    {id:1,type:'Content Flag',reporter:'Anonymous',target:'Forum post by Juan dela Cruz',reason:'Inappropriate content',date:'Apr 28, 2026',status:'pending'},
    {id:2,type:'Harassment',reporter:'Maria Santos',target:'Student: Carlo Bautista',reason:'Threatening messages in DM',date:'Apr 27, 2026',status:'pending'},
    {id:3,type:'Plagiarism',reporter:'Prof. Duran',target:'CS 311 Lab 4 submissions (3 students)',reason:'Identical code submissions',date:'Apr 26, 2026',status:'pending'},
    {id:4,type:'Account Abuse',reporter:'System',target:'IP: 203.145.88.12',reason:'Brute force login attempts',date:'Apr 25, 2026',status:'resolved'},
    {id:5,type:'Content Flag',reporter:'Nicole Abalos',target:'Course material in IT 301',reason:'Outdated/incorrect information',date:'Apr 24, 2026',status:'resolved'},
  ];
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Reports & Flags</span></div>
    <h1>Reports & Flags</h1>
    <p>Review and act on reported content, users, and issues</p>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    ${[['🚨','Pending Reports','3','badge-red'],['✅','Resolved','47','badge-green'],['📊','Total this Month','12','badge-blue']].map(([i,l,v,b])=>`
      <div class="stat-card"><div class="stat-icon" style="background:var(--surface-2)">${i}</div><div><div class="stat-val">${v}</div><div class="stat-label">${l}</div></div></div>
    `).join('')}
  </div>

  <div class="card">
    <div class="section-header" style="margin-bottom:16px">
      <div class="section-title">Flagged Reports</div>
      <div class="tabbar" style="margin:0">
        <button class="tab-btn active" onclick="switchTab(this)">All</button>
        <button class="tab-btn" onclick="switchTab(this)">Pending</button>
        <button class="tab-btn" onclick="switchTab(this)">Resolved</button>
      </div>
    </div>
    ${reps.map(r=>`
    <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;margin-bottom:10px;${r.status==='pending'?'border-left:4px solid var(--danger)':''}">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <span class="badge ${r.status==='pending'?'badge-red':'badge-green'}">${r.status}</span>
            <span class="badge badge-gray">${r.type}</span>
            <span style="font-size:11px;color:var(--text-3)">${r.date}</span>
          </div>
          <div style="font-weight:700;margin-bottom:4px">${r.target}</div>
          <div style="font-size:12px;color:var(--text-2)">Reason: ${r.reason}</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:4px">Reported by: ${r.reporter}</div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          ${r.status==='pending' ? `
            <button class="btn btn-success btn-sm" onclick="showToast('Report resolved','success')" style="display:inline-flex;align-items:center;gap:5px">${_ico('check')} Resolve</button>
            <button class="btn btn-danger btn-sm" onclick="showToast('Action taken','error')" style="display:inline-flex;align-items:center;gap:5px">${_ico('ban')} Act</button>
          ` : `<button class="btn btn-ghost btn-sm" onclick="showToast('Viewing report...','');" style="display:inline-flex;align-items:center;gap:5px">${_ico('eye')} View</button>`}
        </div>
      </div>
    </div>`).join('')}
  </div>`;
}

// ===== SYSTEM =====
function renderSystem() {
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>System Health</span></div>
    <h1>System Health</h1>
    <p>Monitor server performance, uptime, and resource usage</p>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
    ${[
      ['🟢','Uptime','98.7%','last 30 days','success'],
      ['🖥','Server Load','42%','within normal range','blue'],
      ['💾','Storage Used','78%','62.4 GB / 80 GB','amber'],
      ['👥','Active Sessions','284','users online now','green'],
    ].map(([i,l,v,s,c])=>`
      <div class="stat-card" style="--stat-color:var(--${c==='success'?'success':c==='blue'?'info':c==='amber'?'warning':'success'})">
        <div class="stat-icon" style="background:var(--surface-2)">${i}</div>
        <div><div class="stat-val">${v}</div><div class="stat-label">${l}</div><div style="font-size:11px;color:var(--text-3);margin-top:3px">${s}</div></div>
      </div>
    `).join('')}
  </div>

  <div class="grid-2" style="margin-bottom:20px">
    <div class="card">
      <div class="section-title" style="margin-bottom:16px">Resource Usage</div>
      ${[
        ['CPU Usage','42%',42,'green'],
        ['RAM Usage','61%',61,'amber'],
        ['Storage','78%',78,'amber'],
        ['Bandwidth','31%',31,'green'],
        ['DB Connections','55%',55,'green'],
      ].map(([l,v,p,c])=>`
        <div style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px">
            <span style="color:var(--text-2)">${l}</span>
            <span style="font-weight:700;color:${p>70?'var(--danger)':p>50?'var(--warning)':'var(--success)'}">${v}</span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill ${c}" style="width:${v}"></div>
          </div>
        </div>
      `).join('')}
    </div>

    <div class="card">
      <div class="section-title" style="margin-bottom:16px">Service Status</div>
      ${[
        ['🌐 Web Server','Online','success'],
        ['🗄 Database','Online','success'],
        ['📧 Email Service','Online','success'],
        ['💾 File Storage','Online','success'],
        ['🔍 Search Index','Rebuilding','amber'],
        ['🔔 Push Notifications','Online','success'],
        ['🤖 AI Service','Online','success'],
        ['📊 Analytics','Degraded','red'],
      ].map(([s,st,c])=>`
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px">
          <span>${s}</span>
          <span class="badge badge-${c==='success'?'green':c==='amber'?'amber':'red'}">${st}</span>
        </div>
      `).join('')}
    </div>
  </div>

  <div class="card">
    <div class="section-header">
      <div class="section-title">Recent System Events</div>
      <button class="btn btn-ghost btn-sm" onclick="navigate('notifications')">Full Audit Log →</button>
    </div>
    ${[
      ['success','Scheduled backup completed successfully','6 hrs ago'],
      ['info','SSL certificate auto-renewed','12 hrs ago'],
      ['warn','Storage reaching 78% capacity — consider cleanup','14 hrs ago'],
      ['error','3 failed login attempts from IP 192.168.1.42','16 hrs ago'],
      ['success','Database optimization ran successfully','1 day ago'],
      ['info','System update v3.2.1 deployed','2 days ago'],
    ].map(([type,msg,time])=>`
      <div class="log-entry">
        <div class="log-dot ${type}" style="margin-top:4px;flex-shrink:0"></div>
        <div style="flex:1"><div style="font-size:13px">${msg}</div><div class="log-time">${time}</div></div>
      </div>
    `).join('')}
  </div>`;
}

// ===== AUDIT LOGS =====
function renderAudit() {
  const logs = [
    {type:'info',user:'System Admin',action:'Logged in','ip':'192.168.1.1',time:'Today 8:42 AM'},
    {type:'success',user:'System Admin',action:'Created user: Nicole Bautista','ip':'192.168.1.1',time:'Today 8:55 AM'},
    {type:'info',user:'Prof. Duran',action:'Logged in','ip':'203.146.12.33',time:'Today 9:01 AM'},
    {type:'warn',user:'Unknown',action:'Failed login attempt (3 times)','ip':'45.33.88.204',time:'Today 9:15 AM'},
    {type:'success',user:'System Admin',action:'Course CS 405 created','ip':'192.168.1.1',time:'Today 9:30 AM'},
    {type:'info',user:'Prof. Reyes',action:'Updated course materials for IT 301','ip':'203.146.12.88',time:'Today 10:05 AM'},
    {type:'error',user:'System',action:'Backup process failed — retrying','ip':'localhost',time:'Today 10:20 AM'},
    {type:'success',user:'System',action:'Backup completed successfully','ip':'localhost',time:'Today 10:22 AM'},
    {type:'warn',user:'Carlo Bautista',action:'Account suspended by admin','ip':'N/A',time:'Today 11:00 AM'},
    {type:'info',user:'Nicole Abalos',action:'Submitted assignment: Lab 4','ip':'192.168.5.22',time:'Today 11:30 AM'},
  ];
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Audit Logs</span></div>
    <h1>Audit Logs</h1>
    <p>Complete trail of all admin and system actions</p>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <div class="search-wrap" style="flex:1;max-width:260px"><input type="text" placeholder="Search logs..."></div>
      <select style="padding:9px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px">
        <option>All Types</option><option>Info</option><option>Warning</option><option>Error</option><option>Success</option>
      </select>
      <select style="padding:9px 12px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--surface-2);color:var(--text);font-size:13px">
        <option>Today</option><option>Last 7 days</option><option>Last 30 days</option><option>Custom range</option>
      </select>
      <button class="btn btn-outline btn-sm" onclick="showToast('Exporting audit log...','success')">📤 Export</button>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Type</th><th>User</th><th>Action</th><th>IP Address</th><th>Timestamp</th></tr></thead>
        <tbody>
          ${logs.map(l=>`
          <tr>
            <td><span class="badge badge-${l.type==='success'?'green':l.type==='warn'?'amber':l.type==='error'?'red':'blue'}">${l.type}</span></td>
            <td style="font-weight:600;font-size:12px">${l.user}</td>
            <td style="font-size:12px;color:var(--text-2)">${l.action}</td>
            <td style="font-size:12px;color:var(--text-3);font-family:monospace">${l.ip}</td>
            <td style="font-size:12px;color:var(--text-2)">${l.time}</td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>
  </div>`;
}

// ===== BACKUP =====
function renderBackup() {
  const backups = [
    {name:'Full Backup – Apr 29',size:'18.4 GB',date:'Apr 29, 2026 06:00 AM',status:'completed',type:'scheduled'},
    {name:'Full Backup – Apr 28',size:'17.9 GB',date:'Apr 28, 2026 06:00 AM',status:'completed',type:'scheduled'},
    {name:'Manual Backup – Apr 27',size:'17.8 GB',date:'Apr 27, 2026 02:15 PM',status:'completed',type:'manual'},
    {name:'Full Backup – Apr 27',size:'17.7 GB',date:'Apr 27, 2026 06:00 AM',status:'completed',type:'scheduled'},
    {name:'Full Backup – Apr 26',size:'17.5 GB',date:'Apr 26, 2026 06:00 AM',status:'failed',type:'scheduled'},
  ];
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Backup & Data</span></div>
    <h1>Backup & Data Management</h1>
    <p>Manage system backups and data exports</p>
  </div>

  <div class="grid-3" style="margin-bottom:20px">
    <div class="card" style="text-align:center">
      <div style="font-size:28px;margin-bottom:8px">✅</div>
      <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700">Today 6:00 AM</div>
      <div style="font-size:12px;color:var(--text-2);margin-top:4px">Last Successful Backup</div>
    </div>
    <div class="card" style="text-align:center">
      <div style="font-size:28px;margin-bottom:8px">💾</div>
      <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700">18.4 GB</div>
      <div style="font-size:12px;color:var(--text-2);margin-top:4px">Latest Backup Size</div>
    </div>
    <div class="card" style="text-align:center">
      <div style="font-size:28px;margin-bottom:8px">🔄</div>
      <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:700">Daily 6:00 AM</div>
      <div style="font-size:12px;color:var(--text-2);margin-top:4px">Backup Schedule</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="section-header">
      <div class="section-title">Manual Backup</div>
    </div>
    <p style="font-size:13px;color:var(--text-2);margin-bottom:16px">Trigger an immediate backup of all platform data. This may take several minutes.</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-primary" onclick="triggerBackup('full')">💾 Full Backup Now</button>
      <button class="btn btn-outline" onclick="triggerBackup('db')">🗄 Database Only</button>
      <button class="btn btn-outline" onclick="triggerBackup('files')">📁 Files Only</button>
      <button class="btn btn-outline btn-sm" onclick="showToast('Scheduling options...','')">⚙️ Configure Schedule</button>
    </div>
    <div id="backupProgress" style="display:none;margin-top:16px">
      <div style="font-size:13px;color:var(--text-2);margin-bottom:8px">Backup in progress...</div>
      <div class="progress-bar" style="height:8px"><div class="progress-fill purple" id="bpFill" style="width:0%;transition:width .4s"></div></div>
    </div>
  </div>

  <div class="card">
    <div class="section-header">
      <div class="section-title">Backup History</div>
      <button class="btn btn-outline btn-sm" onclick="showToast('Exporting list...','success')">📤 Export</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Backup Name</th><th>Size</th><th>Date</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          ${backups.map(b=>`
          <tr>
            <td style="font-weight:600">${b.name}</td>
            <td style="color:var(--text-2)">${b.size}</td>
            <td style="font-size:12px;color:var(--text-2)">${b.date}</td>
            <td><span class="badge ${b.type==='scheduled'?'badge-blue':'badge-purple'}">${b.type}</span></td>
            <td><span class="badge ${b.status==='completed'?'badge-green':'badge-red'}">${b.status}</span></td>
            <td>
              <div style="display:flex;gap:4px">
                ${b.status==='completed'?`<button class="btn btn-success btn-sm" onclick="showToast('Downloading backup...','success')" style="display:inline-flex;align-items:center;gap:5px">${_ico('download')} Download</button>`:''}
                <button class="btn btn-danger btn-sm" onclick="showToast('Backup deleted','error')" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('trash')}</button>
              </div>
            </td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>
  </div>`;
}

function triggerBackup(type) {
  const box = document.getElementById('backupProgress');
  const fill = document.getElementById('bpFill');
  if (!box || !fill) return;
  box.style.display = 'block';
  let pct = 0;
  showToast(`Starting ${type} backup...`, '');
  const interval = setInterval(() => {
    pct += Math.random() * 12;
    if (pct >= 100) { pct = 100; clearInterval(interval); showToast('Backup completed successfully!', 'success'); setTimeout(() => { box.style.display = 'none'; fill.style.width = '0%'; }, 2000); }
    fill.style.width = pct + '%';
  }, 400);
}

// ===== NOTIFICATIONS =====
function renderNotifications() {
  setTimeout(loadNotificationsPage, 0);
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Notifications</span></div>
    <h1>Admin Notifications</h1>
    <p class="notif-page-hdr">Loading alerts…</p>
  </div>
  <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button class="btn btn-ghost btn-sm" onclick="markAllNotifsRead()">✓ Mark all read</button>
  </div>
  <div class="card" style="padding:0" id="notif-page-list">
    <div style="text-align:center;padding:40px;color:var(--text-3);font-size:13px">Loading notifications…</div>
  </div>`;
}

// ===== SETTINGS =====
function renderSettings() {
  return `
  <div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span>›</span><span>Settings</span></div>
    <h1>Admin Settings</h1>
    <p>Configure system-wide settings and admin preferences</p>
  </div>

  <div class="grid-2">
    <div>
      <div class="card" style="margin-bottom:16px;text-align:center">
        <div style="position:relative;width:70px;height:70px;margin:0 auto 14px">
          <div id="adminAvatarInitials" style="width:70px;height:70px;border-radius:18px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#fff;${_adminAvatarUrl ? 'display:none' : ''}"><?php echo $admin_initials; ?></div>
          <img id="adminAvatarImg" src="${_adminAvatarUrl}" alt="" style="${_adminAvatarUrl ? '' : 'display:none;'}width:70px;height:70px;border-radius:18px;object-fit:cover;position:absolute;top:0;left:0">
        </div>
        <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:700"><?php echo htmlspecialchars($admin_name); ?></div>
        <div style="font-size:12px;color:var(--text-2);margin-top:3px"><?php echo htmlspecialchars($admin_email); ?></div>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:10px">
          <span class="badge badge-purple">Super Admin</span>
          <span class="badge badge-green">Active</span>
        </div>
        <input type="file" id="adminPhotoInput" accept="image/*" style="display:none" onchange="uploadAdminPhoto(this)">
        <button class="btn btn-outline btn-sm btn-full" style="margin-top:14px" onclick="document.getElementById('adminPhotoInput').click()">📷 Change Photo</button>
      </div>

      <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Profile Information</h3>
        <div class="form-group"><label class="form-label">Full Name</label><input type="text" value="<?php echo htmlspecialchars($admin_name); ?>"></div>
        <div class="form-group"><label class="form-label">Email Address</label><input type="email" value="<?php echo htmlspecialchars($admin_email); ?>"></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="tel" value="+63 912 345 6789"></div>
        <button class="btn btn-primary btn-sm" onclick="showToast('Profile updated!','success')">💾 Save Changes</button>
      </div>

      <div class="card">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Account</h3>
        <button class="btn btn-danger btn-sm" onclick="doLogout()">🚪 Sign Out</button>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">Platform Settings</h3>
        <div class="form-group"><label class="form-label">Platform Name</label><input type="text" value="LearnFlow LMS"></div>
        <div class="form-group"><label class="form-label">Institution Name</label><input type="text" value="Pamantasan ng Lungsod ng Pasig"></div>
        <button class="btn btn-primary btn-sm" onclick="showToast('Platform settings saved!','success')">💾 Save Platform Settings</button>
      </div>

      <div class="card" style="margin-bottom:16px" id="themeCard">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:6px">🎨 Portal Theme</h3>
        <p style="font-size:12px;color:var(--text-2);margin-bottom:14px">Changes apply to <strong>all portals</strong> (admin, instructor, student). Pick a preset or build a custom color.</p>

        <!-- Preset swatches -->
        <div style="font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Presets</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px" id="themePresets">
          ${[
            {name:'Rose Pink',  p:'336 67% 52%', d:'336 67% 40%', l:'336 100% 97%', bg:'336 100% 97%', border:'336 60% 87%', text:'336 60% 10%', text2:'336 40% 47%', acc:'207 80% 60%'},
            {name:'Violet',     p:'262 80% 55%', d:'262 80% 42%', l:'262 100% 97%', bg:'262 100% 97%', border:'262 60% 85%', text:'262 60% 10%', text2:'262 40% 47%', acc:'207 80% 60%'},
            {name:'Ocean Blue', p:'207 80% 48%', d:'207 80% 36%', l:'207 100% 96%', bg:'207 100% 96%', border:'207 60% 84%', text:'207 60% 10%', text2:'207 40% 47%', acc:'336 67% 52%'},
            {name:'Forest',     p:'142 60% 38%', d:'142 60% 28%', l:'142 60% 96%', bg:'142 60% 96%', border:'142 40% 82%', text:'142 60% 8%',  text2:'142 35% 40%', acc:'207 80% 60%'},
            {name:'Amber',      p:'38 90% 48%',  d:'38 90% 36%',  l:'38 100% 96%', bg:'38 100% 96%',  border:'38 70% 83%',  text:'38 60% 10%',  text2:'38 40% 42%',  acc:'207 80% 60%'},
            {name:'Crimson',    p:'0 75% 50%',   d:'0 75% 38%',   l:'0 100% 97%',  bg:'0 100% 97%',   border:'0 60% 86%',   text:'0 60% 10%',   text2:'0 40% 47%',   acc:'207 80% 60%'},
          ].map(t => `<div style="display:flex;flex-direction:column;align-items:center;gap:4px;cursor:pointer" onclick="applyThemePreset(${JSON.stringify(JSON.stringify(t))})">
            <div class="theme-swatch" style="width:36px;height:36px;border-radius:50%;border:3px solid transparent;background:hsl(${t.p});box-shadow:0 2px 8px rgba(0,0,0,.2);transition:.2s"
              onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform=''"></div>
            <span style="font-size:10px;color:var(--text-3);font-weight:600">${t.name}</span>
          </div>`).join('')}
        </div>

        <!-- Custom color picker -->
        <details style="margin-bottom:14px">
          <summary style="font-size:12px;font-weight:600;color:var(--text-2);cursor:pointer;padding:6px 0">Custom Color Builder ▸</summary>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
            <div class="form-group">
              <label class="form-label">Primary Color</label>
              <div style="display:flex;align-items:center;gap:8px">
                <input type="color" id="themePickerPrimary" value="#CC3A72"
                  oninput="onThemeColorInput(this)"
                  style="width:40px;height:34px;border:1.5px solid var(--border);border-radius:6px;cursor:pointer;padding:2px;background:var(--surface)">
                <span id="themePickerPrimaryHsl" style="font-size:11px;color:var(--text-3);font-family:monospace">336 67% 52%</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Accent / Secondary</label>
              <div style="display:flex;align-items:center;gap:8px">
                <input type="color" id="themePickerAccent" value="#4AAEE8"
                  oninput="onThemeAccentInput(this)"
                  style="width:40px;height:34px;border:1.5px solid var(--border);border-radius:6px;cursor:pointer;padding:2px;background:var(--surface)">
                <span id="themePickerAccentHsl" style="font-size:11px;color:var(--text-3);font-family:monospace">207 80% 60%</span>
              </div>
            </div>
          </div>
          <button class="btn btn-outline btn-sm" onclick="buildAndPreviewCustomTheme()" style="margin-top:4px">👁 Preview Custom Theme</button>
        </details>

        <!-- Live preview bar -->
        <div style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;background:var(--surface-2)">
          <div style="font-size:11px;color:var(--text-3);margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Preview</div>
          <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
            <div id="previewSwatchPrimary"     style="width:22px;height:22px;border-radius:5px;background:var(--primary);border:1px solid rgba(0,0,0,.08)"></div>
            <div id="previewSwatchDark"        style="width:22px;height:22px;border-radius:5px;background:var(--primary-dark);border:1px solid rgba(0,0,0,.08)"></div>
            <div id="previewSwatchLight"       style="width:22px;height:22px;border-radius:5px;background:var(--primary-light);border:1px solid rgba(0,0,0,.08)"></div>
            <div id="previewSwatchAccent"      style="width:22px;height:22px;border-radius:5px;background:var(--secondary);border:1px solid rgba(0,0,0,.08)"></div>
            <span id="themeCurrentName" style="font-size:12px;font-weight:600;color:var(--text-2);margin-left:4px">Loading…</span>
          </div>
          <div style="display:flex;gap:6px">
            <div style="height:8px;flex:1;border-radius:4px;background:var(--primary)"></div>
            <div style="height:8px;flex:1;border-radius:4px;background:var(--secondary,#4AAEE8)"></div>
            <div style="height:8px;flex:1;border-radius:4px;background:var(--primary-light)"></div>
          </div>
        </div>

        <div id="themeSaveStatus" style="font-size:12px;min-height:16px;margin-bottom:8px"></div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-outline btn-sm" onclick="resetThemeToDefault()">↺ Reset to Default</button>
          <button class="btn btn-primary btn-sm" id="themeSaveBtn" onclick="saveThemeToDB()" style="display:inline-flex;align-items:center;gap:6px">
            💾 Save &amp; Apply to All Portals
          </button>
        </div>
      </div>

      <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);margin-bottom:14px">System Preferences</h3>
        ${[
          ['Dark Mode','Use dark theme for admin panel',true,'toggleDark()'],
          ['Email Notifications','Receive system alerts via email',true,''],
        ].map(([label,desc,checked,fn])=>`
          <div class="setting-row">
            <div class="setting-info"><h4>${label}</h4><p>${desc}</p></div>
            <label class="toggle"><input type="checkbox" ${checked?'checked':''} ${fn?`onchange="${fn}"`:''}><span class="toggle-slider"></span></label>
          </div>
        `).join('')}
      </div>

    </div>
  </div>`;
}

// ===== HELPERS =====
function _applyAvatarEverywhere(url) {
  _adminAvatarUrl = url;
  // Settings page large avatar
  const img    = document.getElementById('adminAvatarImg');
  const initEl = document.getElementById('adminAvatarInitials');
  if (img)    { img.src = url; img.style.display = 'block'; }
  if (initEl) { initEl.style.display = 'none'; }

  // Topbar avatar
  const topbar = document.getElementById('topbarAvatar');
  if (topbar) {
    if (topbar.tagName === 'IMG') {
      topbar.src = url;
    } else {
      const newImg = document.createElement('img');
      newImg.src = url;
      newImg.id = 'topbarAvatar';
      newImg.className = topbar.className;
      newImg.style.cssText = 'object-fit:cover;padding:0;cursor:pointer';
      newImg.onclick = topbar.onclick;
      newImg.alt = '';
      topbar.replaceWith(newImg);
    }
  }

  // Sidebar footer user-card avatar
  const sideAvatar = document.querySelector('.sidebar-footer .user-card .user-avatar');
  if (sideAvatar) {
    if (sideAvatar.tagName === 'IMG') {
      sideAvatar.src = url;
    } else {
      const newImg = document.createElement('img');
      newImg.src = url;
      newImg.className = 'user-avatar';
      newImg.style.cssText = 'object-fit:cover;padding:0';
      newImg.alt = '';
      sideAvatar.replaceWith(newImg);
    }
  }
}

async function uploadAdminPhoto(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  if (!file.type.startsWith('image/')) { showToast('Please select an image file', 'error'); return; }

  const fd = new FormData();
  fd.append('action', 'upload_admin_photo');
  fd.append('photo', file);

  try {
    showToast('Uploading photo…', 'info');
    const res  = await fetch('?action=upload_admin_photo', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
    const data = await res.json();
    if (data.success) {
      _applyAvatarEverywhere(data.avatar_url);
      showToast('Profile photo saved!', 'success');
    } else {
      showToast(data.message || 'Upload failed.', 'error');
    }
  } catch(e) {
    showToast('Network error. Please try again.', 'error');
  }
}

function switchTab(btn) {
  btn.closest('.tabbar').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

// ===== ANNOUNCEMENT USER PICKER =====
let _annAllUsers = [];
let _annSelectedIds = new Set();

async function loadAnnUsers() {
  try {
    const res = await fetch('api-announcements.php?action=get_users');
    const data = await res.json();
    if (data.success) {
      _annAllUsers = data.users || [];
      renderAnnUserList(_annAllUsers);
    }
  } catch(e) {
    document.getElementById('annUserList').innerHTML =
      '<div style="text-align:center;color:var(--danger);padding:16px;font-size:13px">Failed to load users</div>';
  }
}

function renderAnnUserList(users) {
  const list = document.getElementById('annUserList');
  if (!list) return;
  if (!users.length) {
    list.innerHTML = '<div style="text-align:center;color:var(--text-3);padding:16px;font-size:13px">No users found</div>';
    return;
  }
  list.innerHTML = users.map(u => {
    const checked = _annSelectedIds.has(u.id) ? 'checked' : '';
    const roleTag = u.role === 'student'
      ? '<span style="font-size:10px;background:rgba(74,174,232,.15);color:#1260a0;padding:1px 7px;border-radius:10px;font-weight:700">Student</span>'
      : '<span style="font-size:10px;background:rgba(204,58,114,.12);color:var(--primary-dark);padding:1px 7px;border-radius:10px;font-weight:700">Instructor</span>';
    return `<label style="display:flex;align-items:center;gap:9px;padding:7px 8px;border-radius:var(--radius-xs);cursor:pointer;transition:.15s" onmouseover="this.style.background='var(--surface-3)'" onmouseout="this.style.background='transparent'">
      <input type="checkbox" value="${u.id}" ${checked} style="accent-color:var(--primary);width:15px;height:15px;flex-shrink:0" onchange="toggleAnnUser(${u.id}, this.checked)">
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${u.name}</div>
        <div style="font-size:11px;color:var(--text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${u.email}</div>
      </div>
      ${roleTag}
    </label>`;
  }).join('');
}

function toggleAnnUser(id, checked) {
  if (checked) _annSelectedIds.add(id);
  else _annSelectedIds.delete(id);
  updateAnnSelectedCount();
}

function updateAnnSelectedCount() {
  const el = document.getElementById('annSelectedCount');
  if (!el) return;
  const n = _annSelectedIds.size;
  el.textContent = n === 0
    ? '0 users selected — will send to all users'
    : `${n} user${n > 1 ? 's' : ''} selected`;
  el.style.color = n > 0 ? 'var(--primary)' : 'var(--text-2)';
}

function filterAnnUsers() {
  const q = (document.getElementById('annUserSearch')?.value || '').toLowerCase();
  const filtered = _annAllUsers.filter(u =>
    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
  renderAnnUserList(filtered);
}

function annSelectAll() {
  _annAllUsers.forEach(u => _annSelectedIds.add(u.id));
  filterAnnUsers();
  updateAnnSelectedCount();
}

function annSelectByRole(role) {
  _annAllUsers.forEach(u => { if (u.role === role) _annSelectedIds.add(u.id); });
  filterAnnUsers();
  updateAnnSelectedCount();
}

function annClearAll() {
  _annSelectedIds.clear();
  filterAnnUsers();
  updateAnnSelectedCount();
}

function openAnnounceModal() {
  _annSelectedIds.clear();
  const searchEl = document.getElementById('annUserSearch');
  if (searchEl) searchEl.value = '';
  updateAnnSelectedCount();
  if (_annAllUsers.length === 0) {
    loadAnnUsers();
  } else {
    renderAnnUserList(_annAllUsers);
  }
  openModal('announceModal');
}

function sendAdminAnnouncement() {
  const title = document.getElementById('annModalTitle')?.value.trim();
  const body = document.getElementById('annModalBody')?.value.trim();
  const priority = document.getElementById('annModalPriority')?.value || 'normal';
  const scheduleVal = document.getElementById('annModalSchedule')?.value;
  if (!title) { showToast('Please enter a title', 'error'); return; }
  if (!body) { showToast('Please write a message', 'error'); return; }

  const userIds = _annSelectedIds.size > 0 ? [..._annSelectedIds].join(',') : '';

  const formData = new FormData();
  formData.append('title', title);
  formData.append('body', body);
  formData.append('priority', priority);
  formData.append('user_ids', userIds);
  if (scheduleVal) {
    formData.append('publish_at', new Date(scheduleVal).toISOString());
  }

  fetch('api-announcements.php?action=create', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      closeModal('announceModal');
      document.getElementById('annModalTitle').value = '';
      document.getElementById('annModalBody').value = '';
      document.getElementById('annModalPriority').value = 'normal';
      document.getElementById('annModalSchedule').value = '';
      _annSelectedIds.clear();
      if (typeof loadAnnouncements === 'function') loadAnnouncements();
      showToast('Announcement ' + (scheduleVal ? 'scheduled' : 'sent') + ' successfully!', 'success');
    } else {
      showToast(data.message || 'Failed to send announcement', 'error');
    }
  })
  .catch(err => {
    console.error('Error:', err);
    showToast('Failed to send announcement', 'error');
  });
}

// ===== THEME ENGINE =====

// Pending theme state (before save)
let _pendingTheme = null;

// Load current theme name from DB on settings page open
async function loadCurrentThemeName() {
  const el = document.getElementById('themeCurrentName');
  if (!el) return;
  try {
    const res  = await fetch('?action=get_theme', { headers:{'X-Requested-With':'XMLHttpRequest'} });
    const data = await res.json();
    if (data.success && data.theme) {
      el.textContent = data.theme.name || 'Custom';
      _pendingTheme  = data.theme;
      // ── Apply the saved DB theme immediately so the preview reflects reality ──
      _applyThemeVars({
        p:      data.theme.primary_color,
        d:      data.theme.primary_dark,
        l:      data.theme.primary_light,
        bg:     data.theme.bg_color,
        surface:data.theme.surface_color,
        border: data.theme.border_color,
        text:   data.theme.text_color,
        text2:  data.theme.text_secondary,
        acc:    data.theme.accent_color,
      });
    } else {
      el.textContent = 'Rose Pink (default)';
    }
  } catch(e) { el.textContent = 'Default'; }
}

// ── HSL helpers for dark-mode variant computation ──────────────────────────
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
    p:         _shiftL(t.p,  +8),
    d:         _shiftL(t.d,  +6),
    l:         _shiftL(t.p, -35),
    bg:        _shiftL(t.p, -47),
    surface:   _shiftL(t.p, -43),
    surface2:  _shiftL(t.p, -40),
    surface3:  _shiftL(t.p, -37),
    border:    _shiftL(t.p, -30),
    borderSt:  _shiftL(t.p, -25),
    text:      _shiftL(t.p, +48),
    text2:     _shiftL(t.p, +20),
    text3:     _shiftL(t.p, -15),
    acc:       t.acc,
  };
}

// Apply theme CSS variables live — covers BOTH light :root AND dark [data-theme="dark"]
function _applyThemeVars(t) {
  let style = document.getElementById('lf-theme-vars');
  if (!style) {
    style = document.createElement('style');
    style.id = 'lf-theme-vars';
    document.head.appendChild(style);
  }
  const dk   = _darkVariants(t);
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

// Apply a preset (passed as JSON string from onclick)
function applyThemePreset(jsonStr) {
  const t = JSON.parse(jsonStr);
  _pendingTheme = {
    name: t.name,
    primary_color:  t.p, primary_dark: t.d, primary_light: t.l,
    bg_color: t.bg, surface_color: '0 0% 100%', border_color: t.border,
    text_color: t.text, text_secondary: t.text2, accent_color: t.acc,
    is_dark: 0,
  };
  _applyThemeVars({ ...t, surface: '0 0% 100%' });
  const el = document.getElementById('themeCurrentName');
  if (el) el.textContent = t.name;
  showToast(`Theme preview: ${t.name} — click Save to apply to all portals.`, '');
}

// Hex → HSL conversion
function _hexToHsl(hex) {
  let r = parseInt(hex.slice(1,3),16)/255;
  let g = parseInt(hex.slice(3,5),16)/255;
  let b = parseInt(hex.slice(5,7),16)/255;
  const max = Math.max(r,g,b), min = Math.min(r,g,b);
  let h=0, s=0, l=(max+min)/2;
  if (max!==min) {
    const d = max - min;
    s = l > 0.5 ? d/(2-max-min) : d/(max+min);
    switch(max) {
      case r: h = ((g-b)/d + (g<b?6:0))/6; break;
      case g: h = ((b-r)/d + 2)/6; break;
      case b: h = ((r-g)/d + 4)/6; break;
    }
  }
  return `${Math.round(h*360)} ${Math.round(s*100)}% ${Math.round(l*100)}%`;
}

// Darken / lighten helpers (adjust lightness)
function _adjustHslL(hsl, deltaL) {
  const [h, s, l] = hsl.match(/[\d.]+/g).map(Number);
  return `${h} ${s}% ${Math.max(0,Math.min(100,l+deltaL))}%`;
}

function onThemeColorInput(input) {
  const hsl = _hexToHsl(input.value);
  document.getElementById('themePickerPrimaryHsl').textContent = hsl;
}

function onThemeAccentInput(input) {
  const hsl = _hexToHsl(input.value);
  document.getElementById('themePickerAccentHsl').textContent = hsl;
}

function buildAndPreviewCustomTheme() {
  const pHex = document.getElementById('themePickerPrimary').value;
  const aHex = document.getElementById('themePickerAccent').value;
  const p    = _hexToHsl(pHex);
  const acc  = _hexToHsl(aHex);
  const d    = _adjustHslL(p, -12);
  const l    = _adjustHslL(p, 45);
  const bg   = l;
  const border = _adjustHslL(p, 35);
  const text  = _adjustHslL(p, -40);
  const text2 = _adjustHslL(p, -5);
  const t = { p, d, l, bg, surface:'0 0% 100%', border, text, text2, acc };
  _pendingTheme = {
    name: 'Custom',
    primary_color: p, primary_dark: d, primary_light: l,
    bg_color: bg, surface_color: '0 0% 100%', border_color: border,
    text_color: text, text_secondary: text2, accent_color: acc, is_dark: 0,
  };
  _applyThemeVars(t);
  const el = document.getElementById('themeCurrentName');
  if (el) el.textContent = 'Custom';
  showToast('Custom theme preview applied — click Save to persist.', '');
}

async function saveThemeToDB() {
  if (!_pendingTheme) {
    showToast('Select or preview a theme first.', 'error');
    return;
  }
  const btn    = document.getElementById('themeSaveBtn');
  const status = document.getElementById('themeSaveStatus');
  btn.disabled = true;
  status.innerHTML = '<span style="color:var(--text-2)">⏳ Saving…</span>';

  const fd = new FormData();
  fd.append('action',        'save_theme');
  fd.append('name',          _pendingTheme.name          || 'Custom');
  fd.append('primary_color', _pendingTheme.primary_color || '336 67% 52%');
  fd.append('primary_dark',  _pendingTheme.primary_dark  || '336 67% 40%');
  fd.append('primary_light', _pendingTheme.primary_light || '336 100% 97%');
  fd.append('bg_color',      _pendingTheme.bg_color       || '336 100% 97%');
  fd.append('surface_color', _pendingTheme.surface_color  || '0 0% 100%');
  fd.append('border_color',  _pendingTheme.border_color   || '336 60% 87%');
  fd.append('text_color',    _pendingTheme.text_color     || '336 60% 10%');
  fd.append('text_secondary',_pendingTheme.text_secondary || '336 40% 47%');
  fd.append('accent_color',  _pendingTheme.accent_color   || '207 80% 60%');
  fd.append('is_dark',       _pendingTheme.is_dark        || 0);

  try {
    const res  = await fetch('?action=save_theme', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd });
    const data = await res.json();
    if (data.success) {
      showToast('✅ Theme saved and applied to all portals!', 'success');
      status.innerHTML = '<span style="color:var(--success)">✅ Saved — live-applied to all open portals.</span>';
      // Broadcast to all open tabs so they hot-reload without a page refresh
      const _bc_payload = JSON.stringify({
        p:   _pendingTheme.primary_color, d: _pendingTheme.primary_dark,
        l:   _pendingTheme.primary_light, bg: _pendingTheme.bg_color,
        surface: _pendingTheme.surface_color, border: _pendingTheme.border_color,
        text: _pendingTheme.text_color, text2: _pendingTheme.text_secondary,
        acc: _pendingTheme.accent_color,
      });
      try { const _bc = new BroadcastChannel('lf-theme'); _bc.postMessage({ type:'theme-update', theme:_bc_payload }); _bc.close(); } catch(_e) {}
      try { localStorage.setItem('lf-theme-data', _bc_payload); } catch(_e) {}
    } else {
      status.innerHTML = `<span style="color:var(--danger)">⚠ ${data.message}</span>`;
    }
  } catch(e) {
    status.innerHTML = '<span style="color:var(--danger)">⚠ Network error.</span>';
  }
  btn.disabled = false;
}

async function resetThemeToDefault() {
  const DEFAULT = {
    name:'Rose Pink', primary_color:'336 67% 52%', primary_dark:'336 67% 40%',
    primary_light:'336 100% 97%', bg_color:'336 100% 97%', surface_color:'0 0% 100%',
    border_color:'336 60% 87%', text_color:'336 60% 10%', text_secondary:'336 40% 47%',
    accent_color:'207 80% 60%', is_dark:0,
  };
  _pendingTheme = DEFAULT;
  _applyThemeVars({ p:DEFAULT.primary_color, d:DEFAULT.primary_dark, l:DEFAULT.primary_light,
    bg:DEFAULT.bg_color, surface:DEFAULT.surface_color, border:DEFAULT.border_color,
    text:DEFAULT.text_color, text2:DEFAULT.text_secondary, acc:DEFAULT.accent_color });
  const el = document.getElementById('themeCurrentName');
  if (el) el.textContent = 'Rose Pink (default)';
  showToast('Default theme previewed — click Save to apply.', '');
}

// Auto-load theme name when settings page opens
const _origRenderSettings = renderSettings;
// eslint-disable-next-line no-global-assign
renderSettings = function() {
  const html = _origRenderSettings();
  setTimeout(loadCurrentThemeName, 80);
  return html;
};

function doLogout() {
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

// ===== ANNOUNCEMENTS HELPERS =====
let _annFilter = 'all';

function filterAnn(status, btn) {
  _annFilter = status;
  if (btn) {
    btn.closest('.tabbar').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
  renderAnnRows();
}

function renderAnnRows() {
  loadAnnouncements();
}

async function loadAnnouncements() {
  const filtered = _annFilter === 'all' ? '' : _annFilter;
  try {
    const response = await fetch(`api-announcements.php?action=list&status=${filtered}`);
    const data = await response.json();
    
    if (!data.success) {
      showToast('Failed to load announcements', 'error');
      return;
    }
    
    const tbody = document.getElementById('annTbody');
    if (!tbody) return;
    
    const announcements = data.announcements || [];
    if (announcements.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-3)">No announcements found</td></tr>';
      return;
    }
    
    tbody.innerHTML = announcements.map(a => {
      const statusBadge = a.published_at 
        ? (new Date(a.published_at) > new Date() ? 'badge-blue' : 'badge-green')
        : 'badge-blue';
      const statusText = a.published_at 
        ? (new Date(a.published_at) > new Date() ? 'scheduled' : 'sent')
        : 'sent';
      const priorityBadge = a.priority === 'urgent' ? 'badge-red' 
        : a.priority === 'important' ? 'badge-amber' 
        : 'badge-blue';
      
      return `
    <tr id="annRow-${a.id}">
      <td style="font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${a.title}">${a.title}</td>
      <td><span class="badge badge-gray">${getScopeLabel(a.scope, a.target_count, a.target_names)}</span></td>
      <td><span class="badge ${priorityBadge}">${a.priority || 'normal'}</span></td>
      <td style="font-size:12px;color:var(--text-2)">${new Date(a.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</td>
      <td><span class="badge ${statusBadge}">${statusText}</span></td>
      <td>
        <div style="display:flex;gap:4px">
          <button class="btn btn-ghost btn-sm" onclick="viewAdminAnn(${a.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('eye')}</button>
          <button class="btn btn-danger btn-sm" onclick="deleteAdminAnn(${a.id})" style="padding:5px 7px;display:inline-flex;align-items:center;justify-content:center">${_ico('trash')}</button>
        </div>
      </td>
    </tr>`;
    }).join('');
  } catch (err) {
    console.error('Error loading announcements:', err);
    const tbody = document.getElementById('annTbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--danger)">Error loading announcements</td></tr>';
  }
}

function getScopeLabel(scope, targetCount, targetNames) {
  if (scope === 'platform' && !targetCount) return 'All Users';
  if (scope === 'user' || targetCount > 0) {
    if (targetCount === 1 && targetNames) return targetNames;
    if (targetCount > 1) return `${targetCount} users`;
    return targetNames || 'Specific Users';
  }
  return 'All Users';
}

function viewAdminAnn(id) {
  fetch(`api-announcements.php?action=get&id=${id}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        showToast('Failed to load announcement', 'error');
        return;
      }
      const a = data.announcement;
      const priorityBadge = a.priority === 'urgent' ? 'badge-red' 
        : a.priority === 'important' ? 'badge-amber' 
        : 'badge-blue';
      const statusText = a.published_at 
        ? (new Date(a.published_at) > new Date() ? 'scheduled' : 'sent')
        : 'sent';
      const statusBadge = a.published_at 
        ? (new Date(a.published_at) > new Date() ? 'badge-blue' : 'badge-green')
        : 'badge-green';
      
      document.getElementById('viewAnnContent').innerHTML = `
    <div style="margin-bottom:12px">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
        <span class="badge ${priorityBadge}">${a.priority || 'normal'}</span>
        <span class="badge ${statusBadge}">${statusText}</span>
        <span class="badge badge-gray">${getScopeLabel(a.scope, a.target_count, a.target_names)}</span>
      </div>
      <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:6px">${a.title}</div>
      <div style="font-size:12px;color:var(--text-3);margin-bottom:16px">📅 ${new Date(a.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}</div>
      <div style="padding:16px;background:var(--surface-2);border-radius:var(--radius-sm);font-size:14px;line-height:1.7;color:var(--text)">${a.body.replace(/\n/g, '<br>')}</div>
    </div>`;
      openModal('viewAnnModal');
    })
    .catch(err => {
      console.error('Error:', err);
      showToast('Failed to load announcement', 'error');
    });
}

function deleteAdminAnn(id) {
  if (!confirm('Delete this announcement?')) return;
  fetch(`api-announcements.php?action=delete&id=${id}`, {
    method: 'DELETE'
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (typeof loadAnnouncements === 'function') loadAnnouncements();
      showToast('Announcement deleted', 'success');
    } else {
      showToast(data.message || 'Failed to delete announcement', 'error');
    }
  })
  .catch(err => {
    console.error('Error:', err);
    showToast('Failed to delete announcement', 'error');
  });
}

</script>
</body>
</html>