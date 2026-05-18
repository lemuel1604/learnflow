<?php
/**
 * LearnFlow LMS — Announcements API
 *
 * Routes (resolved via ?action=):
 *   GET    ?action=get_users
 *   POST   ?action=create
 *   GET    ?action=list[&status=sent|scheduled]
 *   GET    ?action=get&id={id}
 *   DELETE ?action=delete&id={id}
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';

/* ── Auth guard ─────────────────────────────────────────────────────────── */
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Forbidden: admin access required']));
}

$admin_id = (int) $_SESSION['user_id'];

/* ── Ensure junction table exists ───────────────────────────────────────── */
$conn->query("
    CREATE TABLE IF NOT EXISTS announcement_targets (
        announcement_id INT NOT NULL,
        user_id         INT NOT NULL,
        PRIMARY KEY (announcement_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ── Route resolution ───────────────────────────────────────────────────── */
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE') {
    $method = 'DELETE';
}

/* ── Dispatch ───────────────────────────────────────────────────────────── */
switch ($action) {

    /* ── GET USERS ──────────────────────────────────────────────────────── */
    case 'get_users':
        $result = $conn->query("
            SELECT u.id,
                   COALESCE(NULLIF(TRIM(CONCAT(up.first_name,' ',up.last_name)),''), u.email) AS name,
                   u.email,
                   u.role
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.role IN ('student','instructor')
              AND u.status != 'suspended'
            ORDER BY u.role ASC, name ASC
        ");
        if (!$result) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'Query failed: '.$conn->error]));
        }
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id'    => (int) $row['id'],
                'name'  => $row['name'],
                'email' => $row['email'],
                'role'  => $row['role'],
            ];
        }
        die(json_encode(['success' => true, 'users' => $users]));

    /* ── CREATE ─────────────────────────────────────────────────────────── */
    case 'create':
        if ($method !== 'POST') {
            http_response_code(405);
            die(json_encode(['success'=>false,'message'=>'Method not allowed']));
        }

        $title        = trim($_POST['title']     ?? '');
        $body_raw     = trim($_POST['body']       ?? '');
        $priority     = trim($_POST['priority']   ?? 'normal');
        $publish_at   = trim($_POST['publish_at'] ?? '');
        $user_ids_raw = trim($_POST['user_ids']   ?? '');

        if ($title === '') {
            http_response_code(400);
            die(json_encode(['success'=>false,'message'=>'Title is required']));
        }
        if ($body_raw === '') {
            http_response_code(400);
            die(json_encode(['success'=>false,'message'=>'Message body is required']));
        }

        $allowed = ['normal','important','urgent'];
        $priority = in_array($priority, $allowed) ? $priority : 'normal';
        $body = "[PRIORITY:{$priority}]{$body_raw}";

        $pub_dt = null;
        if ($publish_at !== '') {
            $ts = strtotime($publish_at);
            if ($ts === false) {
                http_response_code(400);
                die(json_encode(['success'=>false,'message'=>'Invalid schedule date']));
            }
            $pub_dt = date('Y-m-d H:i:s', $ts);
        }

        // Determine scope: platform (no specific targets) or user (specific targets)
        $specific_ids = [];
        if ($user_ids_raw !== '' && $user_ids_raw !== 'all') {
            $specific_ids = array_values(array_filter(array_map('intval', explode(',', $user_ids_raw))));
        }
        $scope = empty($specific_ids) ? 'platform' : 'user';

        // Insert ONE announcement row
        $stmt = $conn->prepare(
            'INSERT INTO announcements (author_id, scope, scope_id, title, body, published_at)
             VALUES (?, ?, NULL, ?, ?, ?)'
        );
        if (!$stmt) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'DB prepare failed: '.$conn->error]));
        }
        $stmt->bind_param('issss', $admin_id, $scope, $title, $body, $pub_dt);

        if (!$stmt->execute()) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'Failed to save announcement: '.$stmt->error]));
        }
        $ann_id = (int) $conn->insert_id;
        $stmt->close();

        // Insert per-user target rows if targeting specific users
        if (!empty($specific_ids)) {
            $tgt = $conn->prepare(
                'INSERT IGNORE INTO announcement_targets (announcement_id, user_id) VALUES (?, ?)'
            );
            if ($tgt) {
                foreach ($specific_ids as $uid) {
                    $tgt->bind_param('ii', $ann_id, $uid);
                    $tgt->execute();
                }
                $tgt->close();
            }
        }

        die(json_encode(['success' => true, 'message' => 'Announcement saved successfully', 'id' => $ann_id]));

    /* ── LIST ───────────────────────────────────────────────────────────── */
    case 'list':
        $status = strtolower(trim($_GET['status'] ?? ''));

        $where = '';
        if ($status === 'sent')      $where = 'AND (a.published_at IS NULL OR a.published_at <= NOW())';
        if ($status === 'scheduled') $where = 'AND a.published_at IS NOT NULL AND a.published_at > NOW()';

        $sql = "
            SELECT
                a.id, a.title, a.body, a.scope, a.is_pinned, a.published_at, a.created_at,
                COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), ua.email) AS author_name,
                COUNT(at2.user_id) AS target_count,
                GROUP_CONCAT(
                    COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up2.first_name,''),' ',IFNULL(up2.last_name,''))),''), u2.email)
                    SEPARATOR ', '
                ) AS target_names
            FROM announcements a
            LEFT JOIN users         ua   ON ua.id        = a.author_id
            LEFT JOIN user_profiles up   ON up.user_id   = a.author_id
            LEFT JOIN announcement_targets at2 ON at2.announcement_id = a.id
            LEFT JOIN users         u2   ON u2.id        = at2.user_id
            LEFT JOIN user_profiles up2  ON up2.user_id  = at2.user_id
            WHERE 1=1 {$where}
            GROUP BY a.id, a.title, a.body, a.scope, a.is_pinned, a.published_at, a.created_at, author_name
            ORDER BY a.created_at DESC
        ";

        $result = $conn->query($sql);
        if (!$result) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'Query failed: '.$conn->error]));
        }

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $ann = format_announcement($row);
            $ann['target_count'] = (int) $row['target_count'];
            $ann['target_names'] = $row['target_names'] ?? null;
            $list[] = $ann;
        }

        die(json_encode(['success' => true, 'announcements' => $list]));

    /* ── GET single ─────────────────────────────────────────────────────── */
    case 'get':
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            die(json_encode(['success'=>false,'message'=>'Invalid ID']));
        }

        $stmt = $conn->prepare("
            SELECT
                a.id, a.title, a.body, a.scope, a.is_pinned, a.published_at, a.created_at,
                COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up.first_name,''),' ',IFNULL(up.last_name,''))),''), ua.email) AS author_name,
                COUNT(at2.user_id) AS target_count,
                GROUP_CONCAT(
                    COALESCE(NULLIF(TRIM(CONCAT(IFNULL(up2.first_name,''),' ',IFNULL(up2.last_name,''))),''), u2.email)
                    SEPARATOR ', '
                ) AS target_names
            FROM announcements a
            LEFT JOIN users         ua   ON ua.id       = a.author_id
            LEFT JOIN user_profiles up   ON up.user_id  = a.author_id
            LEFT JOIN announcement_targets at2 ON at2.announcement_id = a.id
            LEFT JOIN users         u2   ON u2.id       = at2.user_id
            LEFT JOIN user_profiles up2  ON up2.user_id = at2.user_id
            WHERE a.id = ?
            GROUP BY a.id, a.title, a.body, a.scope, a.is_pinned, a.published_at, a.created_at, author_name
        ");
        if (!$stmt) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'DB prepare failed']));
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            http_response_code(404);
            die(json_encode(['success'=>false,'message'=>'Announcement not found']));
        }

        $ann = format_announcement($row);
        $ann['target_count'] = (int) $row['target_count'];
        $ann['target_names'] = $row['target_names'] ?? null;
        die(json_encode(['success' => true, 'announcement' => $ann]));

    /* ── DELETE ─────────────────────────────────────────────────────────── */
    case 'delete':
        if (!in_array($method, ['DELETE','POST'])) {
            http_response_code(405);
            die(json_encode(['success'=>false,'message'=>'Method not allowed']));
        }

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            die(json_encode(['success'=>false,'message'=>'Invalid ID']));
        }

        foreach ([
            'DELETE FROM announcement_targets WHERE announcement_id = ?',
            'DELETE FROM announcement_reads   WHERE announcement_id = ?',
        ] as $q) {
            $s = $conn->prepare($q);
            if ($s) { $s->bind_param('i', $id); $s->execute(); $s->close(); }
        }

        $del_notif = $conn->prepare(
            "DELETE FROM notifications WHERE related_type = 'announcement' AND related_id = ?"
        );
        if ($del_notif) { $del_notif->bind_param('i', $id); $del_notif->execute(); $del_notif->close(); }

        $del = $conn->prepare('DELETE FROM announcements WHERE id = ?');
        if (!$del) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'DB prepare failed']));
        }
        $del->bind_param('i', $id);
        if (!$del->execute() || $del->affected_rows === 0) {
            http_response_code(404);
            die(json_encode(['success'=>false,'message'=>'Announcement not found or already deleted']));
        }
        $del->close();

        die(json_encode(['success' => true, 'message' => 'Announcement deleted']));

    default:
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Unknown action: '.htmlspecialchars($action)]));
}

/* ── Helper ─────────────────────────────────────────────────────────────── */
function format_announcement(array $row): array
{
    $body     = $row['body'] ?? '';
    $priority = 'normal';
    if (preg_match('/^\[PRIORITY:(normal|important|urgent)\]/', $body, $m)) {
        $priority = $m[1];
        $body     = substr($body, strlen($m[0]));
    }
    return [
        'id'          => (int)  $row['id'],
        'title'       => $row['title'],
        'body'        => $body,
        'priority'    => $priority,
        'scope'       => $row['scope'],
        'is_pinned'   => (bool) $row['is_pinned'],
        'published_at'=> $row['published_at'],
        'created_at'  => $row['created_at'],
        'author_name' => $row['author_name'] ?? 'Admin',
    ];
}
