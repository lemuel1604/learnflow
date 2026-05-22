<?php


session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = (int) $_SESSION['user_id'];
$action  = $_GET['action'] ?? 'mark_read';
$method  = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    /* ── MARK READ ───────────────────────────────────────────────────────── */
    case 'mark_read':
        if ($method !== 'POST') {
            http_response_code(405);
            die(json_encode(['success' => false, 'message' => 'POST required']));
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        $ann_id = (int) ($payload['announcement_id'] ?? $_POST['announcement_id'] ?? 0);

        if ($ann_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Invalid announcement_id']));
        }

        $stmt = $conn->prepare("
            INSERT IGNORE INTO announcement_reads (announcement_id, user_id, read_at)
            VALUES (?, ?, NOW())
        ");
        if (!$stmt) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'DB prepare failed: ' . $conn->error]));
        }
        $stmt->bind_param('ii', $ann_id, $user_id);
        $stmt->execute();
        $stmt->close();

        die(json_encode(['success' => true]));

    /* ── GET UNREAD COUNT ────────────────────────────────────────────────── */
    case 'get_unread':
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS cnt
            FROM announcements a
            LEFT JOIN announcement_reads ar
                   ON ar.announcement_id = a.id AND ar.user_id = ?
            WHERE (
                    (a.scope = 'platform'
                     AND NOT EXISTS (SELECT 1 FROM announcement_targets at2
                                     WHERE at2.announcement_id = a.id))
                 OR EXISTS (SELECT 1 FROM announcement_targets at2
                            WHERE at2.announcement_id = a.id AND at2.user_id = ?)
                  )
              AND ar.announcement_id IS NULL
              AND (a.published_at IS NULL OR a.published_at <= NOW())
        ");
        if (!$stmt) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'DB prepare failed']));
        }
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        die(json_encode(['success' => true, 'unread' => (int) $row['cnt']]));

    default:
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Unknown action']));
}
