<?php

if (ob_get_level()) ob_end_clean();
@ini_set('output_buffering',      'off');
@ini_set('zlib.output_compression', 0);

header('Content-Type: text/event-stream');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Accel-Buffering: no');         
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); 

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/theme.php';

function sse_send(string $event, string $data): void {
    echo "event: {$event}\n";
    echo "data: {$data}\n\n";
    flush();
}

function theme_for_client(array $t): array {
    return [
        'id'             => (int) $t['id'],
        'name'           => $t['name'],
        'primary_color'  => $t['primary_color'],
        'primary_dark'   => $t['primary_dark'],
        'primary_light'  => $t['primary_light'] ?? $t['bg_color'],
        'bg_color'       => $t['bg_color'],
        'surface_color'  => $t['surface_color'],
        'border_color'   => $t['border_color'],
        'text_color'     => $t['text_color'],
        'text_secondary' => $t['text_secondary'] ?? '',
        'accent_color'   => $t['accent_color']   ?? '',
        'is_dark'        => (int) $t['is_dark'],
    ];
}

$current = get_active_theme($conn);
sse_send('theme_update', json_encode(theme_for_client($current)));

$last_updated = $current['updated_at'] ?? null;

$heartbeat_interval = 15;  
$poll_interval      = 3;  
$max_lifetime       = 90; 


$started    = time();
$last_beat  = time();

while (true) {

    if (connection_aborted()) break;

    if ((time() - $started) >= $max_lifetime) {
        sse_send('reconnect', '{"reason":"lifetime_exceeded"}');
        break;
    }

    $row = $conn->query("SELECT * FROM theme_settings WHERE id = 1 LIMIT 1");
    if ($row && ($t = $row->fetch_assoc())) {
        if ($t['updated_at'] !== $last_updated) {
            $last_updated = $t['updated_at'];
            sse_send('theme_update', json_encode(theme_for_client($t)));
        }
    }

    if ((time() - $last_beat) >= $heartbeat_interval) {
        sse_send('heartbeat', '"ping"');
        $last_beat = time();
    }

    sleep($poll_interval);
}
