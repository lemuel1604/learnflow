<?php


session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/theme.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'status');
$method = $_SERVER['REQUEST_METHOD'];

/* ── Helper ─────────────────────────────────────────────────────────────── */
function is_admin(): bool {
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

function save_theme_to_db(mysqli $conn, array $fields): bool {
    $allowed = ['name','primary_color','primary_dark','primary_light',
                'bg_color','surface_color','border_color','text_color',
                'text_secondary','accent_color','is_dark'];
    $set_parts = []; $types = ''; $values = [];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $fields)) {
            $set_parts[] = "`{$col}` = ?";
            $types      .= ($col === 'is_dark') ? 'i' : 's';
            $values[]    = $fields[$col];
        }
    }
    if (!$set_parts) return false;
    $sql  = "INSERT INTO theme_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE " . implode(', ', $set_parts);
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $bind = array_merge([$types], $values);
    $refs = [];
    foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/* ── GET: status ─────────────────────────────────────────────────────────── */
if ($method === 'GET' && $action === 'status') {
    $previewing = !empty($_SESSION['theme_preview']);
    $data = ['success' => true, 'previewing' => $previewing];
    if ($previewing) $data['theme'] = $_SESSION['theme_preview'];
    die(json_encode($data));
}

/* ── POST actions ────────────────────────────────────────────────────────── */
if ($method === 'POST') {
    if (!is_admin()) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Admin access required']));
    }

    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;
    $action  = $payload['action'] ?? $action;

    /* ── set_preview: store theme in session, return it ─────────────────── */
    if ($action === 'set_preview') {
        $required = ['primary_color','primary_dark','bg_color','surface_color',
                     'border_color','text_color'];
        foreach ($required as $f) {
            if (empty($payload[$f])) {
                http_response_code(400);
                die(json_encode(['success'=>false,'message'=>"Missing field: {$f}"]));
            }
        }

        $preview = [
            'name'           => substr(trim($payload['name']          ?? 'Preview'), 0, 80),
            'primary_color'  => trim($payload['primary_color']),
            'primary_dark'   => trim($payload['primary_dark']),
            'primary_light'  => trim($payload['primary_light']  ?? $payload['bg_color']),
            'bg_color'       => trim($payload['bg_color']),
            'surface_color'  => trim($payload['surface_color']),
            'border_color'   => trim($payload['border_color']),
            'text_color'     => trim($payload['text_color']),
            'text_secondary' => trim($payload['text_secondary'] ?? ''),
            'accent_color'   => trim($payload['accent_color']   ?? ''),
            'is_dark'        => (int)!empty($payload['is_dark']),
            '_preview_since' => time(),
        ];

        $_SESSION['theme_preview'] = $preview;
        die(json_encode(['success' => true, 'previewing' => true, 'theme' => $preview]));
    }

    /* ── commit_preview: write session theme to DB, clear session ──────── */
    if ($action === 'commit_preview') {
        $preview = $_SESSION['theme_preview'] ?? null;
        if (!$preview) {
            http_response_code(400);
            die(json_encode(['success'=>false,'message'=>'No active preview to commit']));
        }

        unset($preview['_preview_since']);
        $ok = save_theme_to_db($conn, $preview);

        if (!$ok) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]));
        }

        unset($_SESSION['theme_preview']);

        $theme = get_active_theme($conn);
        unset($theme['updated_at']);
        die(json_encode(['success'=>true,'message'=>'Theme applied to all portals','theme'=>$theme]));
    }

    /* ── cancel_preview: clear session, return active DB theme ─────────── */
    if ($action === 'cancel_preview') {
        unset($_SESSION['theme_preview']);
        $theme = get_active_theme($conn);
        unset($theme['updated_at']);
        die(json_encode(['success'=>true,'previewing'=>false,'theme'=>$theme]));
    }

    http_response_code(404);
    die(json_encode(['success'=>false,'message'=>'Unknown action']));
}

http_response_code(405);
die(json_encode(['success'=>false,'message'=>'Method not allowed']));
