<?php
/**
 * LearnFlow LMS — Theme Live-Push via Server-Sent Events (SSE)
 *
 * How it works:
 *   1. Every portal page opens a persistent connection to this endpoint.
 *   2. This script polls the theme_settings table every 3 seconds.
 *   3. When it detects that updated_at has changed, it pushes the new theme
 *      to all connected clients in real time.
 *   4. The portal JS receives the event and hot-swaps the CSS variables
 *      with no page reload.
 *
 * ─── Integration (add to the bottom of every portal's <body>) ────────────
 *
 *   <script>
 *     if (!!window.EventSource) {
 *       const es = new EventSource('api-theme-sse.php');
 *       es.addEventListener('theme_update', e => {
 *         try { applyThemeLive(JSON.parse(e.data)); } catch(err) {}
 *       });
 *       es.addEventListener('heartbeat', () => {}); // keep-alive
 *       es.onerror = () => { es.close(); };         // reconnect handled by browser
 *     }
 *   </script>
 *
 * ─── Also add applyThemeLive() to every portal ───────────────────────────
 *   (see theme-client.js — just include that file in each portal)
 */

/* ── Disable output buffering fully ──────────────────────────────────────── */
if (ob_get_level()) ob_end_clean();
@ini_set('output_buffering',      'off');
@ini_set('zlib.output_compression', 0);

/* ── SSE response headers ────────────────────────────────────────────────── */
header('Content-Type: text/event-stream');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Accel-Buffering: no');          // nginx: disable proxy buffering
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *'); // allow cross-origin portals on same domain

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/theme.php';

/* ── Helper: send a named SSE event ──────────────────────────────────────── */
function sse_send(string $event, string $data): void {
    echo "event: {$event}\n";
    echo "data: {$data}\n\n";
    flush();
}

/* ── Helper: format theme row as the JS-friendly shape ───────────────────── */
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

/* ── Initial theme push: send current theme immediately on connect ────────── */
$current = get_active_theme($conn);
sse_send('theme_update', json_encode(theme_for_client($current)));

$last_updated = $current['updated_at'] ?? null;

/* ── Streaming loop ──────────────────────────────────────────────────────── */
$heartbeat_interval = 15;  // send a heartbeat every 15 seconds
$poll_interval      = 3;   // poll DB every 3 seconds
$max_lifetime       = 90;  // close after 90 s so the browser auto-reconnects (prevents zombie connections)

$started    = time();
$last_beat  = time();

while (true) {
    /* Stop if the client disconnected */
    if (connection_aborted()) break;

    /* Stop after max_lifetime so connections don't live forever */
    if ((time() - $started) >= $max_lifetime) {
        sse_send('reconnect', '{"reason":"lifetime_exceeded"}');
        break;
    }

    /* ── Poll DB for a changed updated_at ──────────────────────────────── */
    $row = $conn->query("SELECT * FROM theme_settings WHERE id = 1 LIMIT 1");
    if ($row && ($t = $row->fetch_assoc())) {
        if ($t['updated_at'] !== $last_updated) {
            $last_updated = $t['updated_at'];
            sse_send('theme_update', json_encode(theme_for_client($t)));
        }
    }

    /* ── Heartbeat to keep the connection alive ─────────────────────────── */
    if ((time() - $last_beat) >= $heartbeat_interval) {
        sse_send('heartbeat', '"ping"');
        $last_beat = time();
    }

    sleep($poll_interval);
}
