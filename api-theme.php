<?php
/**
 * LearnFlow LMS — Theme Settings API
 *
 * GET  ?action=get           → returns the active theme
 * GET  ?action=presets       → returns all available presets
 * POST ?action=apply_preset  { preset_id }  → apply a built-in preset (admin only)
 * POST ?action=save_custom   { all fields } → save a fully custom theme (admin only)
 * POST ?action=reset         → reset to Rose Pink default (admin only)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/theme.php';

$action = $_GET['action'] ?? 'get';
$method = $_SERVER['REQUEST_METHOD'];

/* ── Helper: is current user an admin? ──────────────────────────────────── */
function is_admin(): bool {
    return !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

/* ── Helper: save theme row to DB ───────────────────────────────────────── */
function save_theme(mysqli $conn, array $fields): array {
    $allowed = ['name','primary_color','primary_dark','primary_light',
                'bg_color','surface_color','border_color','text_color',
                'text_secondary','accent_color','is_dark'];

    $set_parts = [];
    $types     = '';
    $values    = [];

    foreach ($allowed as $col) {
        if (array_key_exists($col, $fields)) {
            $set_parts[] = "`{$col}` = ?";
            $types      .= ($col === 'is_dark') ? 'i' : 's';
            $values[]    = $fields[$col];
        }
    }

    if (empty($set_parts)) {
        return ['success' => false, 'message' => 'No valid fields provided'];
    }

    /* Upsert: always keep id = 1 as the single active theme row */
    $sql  = "INSERT INTO theme_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE " . implode(', ', $set_parts);
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'DB prepare failed: ' . $conn->error];
    }

    $bind_values = array_merge([$types], $values);
    $refs        = [];
    foreach ($bind_values as $k => $v) {
        $refs[$k] = &$bind_values[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'DB execute failed: ' . $stmt->error];
    }
    $stmt->close();

    return ['success' => true];
}

/* ── Dispatch ───────────────────────────────────────────────────────────── */

/* GET actions — no auth required (all portals need to read the theme) */
if ($method === 'GET') {

    if ($action === 'get') {
        $theme = get_active_theme($conn);
        /* Remove internal DB fields the client doesn't need */
        unset($theme['updated_at']);
        die(json_encode(['success' => true, 'theme' => $theme]));
    }

    if ($action === 'presets') {
        die(json_encode(['success' => true, 'presets' => get_theme_presets()]));
    }

    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Unknown GET action']));
}

/* POST actions — admin only */
if ($method === 'POST') {

    if (!is_admin()) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Admin access required']));
    }

    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: $_POST;

    /* ── Apply a built-in preset ──────────────────────────────────────── */
    if ($action === 'apply_preset') {
        $preset_id = trim($payload['preset_id'] ?? '');
        if (!$preset_id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'preset_id is required']));
        }

        $presets   = get_theme_presets();
        $preset    = null;
        foreach ($presets as $p) {
            if ($p['id'] === $preset_id) { $preset = $p; break; }
        }

        if (!$preset) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Preset not found: ' . htmlspecialchars($preset_id)]));
        }

        $result = save_theme($conn, [
            'name'           => $preset['name'],
            'primary_color'  => $preset['primary_color'],
            'primary_dark'   => $preset['primary_dark'],
            'primary_light'  => $preset['primary_light'],
            'bg_color'       => $preset['bg_color'],
            'surface_color'  => $preset['surface_color'],
            'border_color'   => $preset['border_color'],
            'text_color'     => $preset['text_color'],
            'text_secondary' => $preset['text_secondary'],
            'accent_color'   => $preset['accent_color'],
            'is_dark'        => (int) $preset['is_dark'],
        ]);

        if (!$result['success']) {
            http_response_code(500);
            die(json_encode($result));
        }

        $theme = get_active_theme($conn);
        unset($theme['updated_at']);
        die(json_encode(['success' => true, 'message' => 'Preset applied: ' . $preset['name'], 'theme' => $theme]));
    }

    /* ── Save a fully custom theme ────────────────────────────────────── */
    if ($action === 'save_custom') {
        $required = ['name','primary_color','primary_dark','bg_color',
                     'surface_color','border_color','text_color'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                http_response_code(400);
                die(json_encode(['success' => false, 'message' => "Field '{$field}' is required"]));
            }
        }

        /* Sanitize: only accept HSL string values */
        $hsl_pattern = '/^\d{1,3}\s+\d{1,3}%\s+\d{1,3}%$/';
        foreach (['primary_color','primary_dark','primary_light',
                  'bg_color','surface_color','border_color',
                  'text_color','text_secondary','accent_color'] as $field) {
            if (!empty($payload[$field]) && !preg_match($hsl_pattern, trim($payload[$field]))) {
                http_response_code(400);
                die(json_encode(['success' => false,
                    'message' => "Invalid HSL value for '{$field}'. Use format: H S% L% (e.g. 336 67% 52%)"]));
            }
        }

        $result = save_theme($conn, [
            'name'           => substr(trim($payload['name']), 0, 80),
            'primary_color'  => trim($payload['primary_color']),
            'primary_dark'   => trim($payload['primary_dark']),
            'primary_light'  => trim($payload['primary_light']  ?? ''),
            'bg_color'       => trim($payload['bg_color']),
            'surface_color'  => trim($payload['surface_color']),
            'border_color'   => trim($payload['border_color']),
            'text_color'     => trim($payload['text_color']),
            'text_secondary' => trim($payload['text_secondary'] ?? ''),
            'accent_color'   => trim($payload['accent_color']   ?? ''),
            'is_dark'        => (int) !empty($payload['is_dark']),
        ]);

        if (!$result['success']) {
            http_response_code(500);
            die(json_encode($result));
        }

        $theme = get_active_theme($conn);
        unset($theme['updated_at']);
        die(json_encode(['success' => true, 'message' => 'Custom theme saved', 'theme' => $theme]));
    }

    /* ── Reset to default ─────────────────────────────────────────────── */
    if ($action === 'reset') {
        $default = get_default_theme();
        save_theme($conn, $default);
        $theme = get_active_theme($conn);
        unset($theme['updated_at']);
        die(json_encode(['success' => true, 'message' => 'Theme reset to default', 'theme' => $theme]));
    }

    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Unknown POST action']));
}

http_response_code(405);
die(json_encode(['success' => false, 'message' => 'Method not allowed']));
