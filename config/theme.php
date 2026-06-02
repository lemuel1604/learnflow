<?php
/**
 * LearnFlow LMS — Theme Configuration
 *
 * Reads the active theme from the database and injects CSS variables.
 * Include this file in every portal BEFORE outputting any HTML.
 *
 * Usage:
 *   require_once __DIR__ . '/config/theme.php';
 *   // Then call get_theme_css() inside a <style> tag in the <head>.
 */

/* ── Internal self-contained connection for PDO-based portals ─────────────── */
  function _theme_get_conn(): mysqli {
      static $_tc = null;
      if ($_tc === null) {
          $_tc = new mysqli('localhost', 'root', '', 'learnflow_db');
          if ($_tc->connect_error) {
              $_tc = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'learnflow_db');
          }
          $_tc->set_charset('utf8mb4');
      }
      return $_tc;
  }

  if (!isset($conn) || !($conn instanceof mysqli)) {
      $conn = _theme_get_conn();
  }

/* ── Ensure the theme_settings table exists ───────────────────────────────── */
$conn->query("
    CREATE TABLE IF NOT EXISTS theme_settings (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(80)  NOT NULL DEFAULT 'Rose Pink',
        primary_color  VARCHAR(60) NOT NULL DEFAULT '336 67% 52%',
        primary_dark   VARCHAR(60) NOT NULL DEFAULT '336 67% 40%',
        primary_light  VARCHAR(60) NOT NULL DEFAULT '336 100% 97%',
        bg_color       VARCHAR(60) NOT NULL DEFAULT '336 100% 97%',
        surface_color  VARCHAR(60) NOT NULL DEFAULT '0 0% 100%',
        border_color   VARCHAR(60) NOT NULL DEFAULT '336 60% 87%',
        text_color     VARCHAR(60) NOT NULL DEFAULT '336 60% 10%',
        text_secondary VARCHAR(60) NOT NULL DEFAULT '336 40% 47%',
        accent_color   VARCHAR(60) DEFAULT '207 80% 60%',
        is_dark        TINYINT(1)  NOT NULL DEFAULT 0,
        updated_at     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ── Seed default theme if no row exists ──────────────────────────────────── */
$conn->query("
    INSERT IGNORE INTO theme_settings (id, name)
    VALUES (1, 'Rose Pink')
");

/* ── Fetch active theme ───────────────────────────────────────────────────── */
/**
 * Returns the theme the current viewer should see:
 *  - If an admin has an active preview in their session, return that.
 *  - Otherwise return the saved DB theme.
 *
 * The $conn parameter is still required (used as fallback and for DB writes),
 * but for session-preview callers the DB is never queried.
 */
function get_active_theme(?mysqli $conn = null): array {
    if ($conn === null) $conn = _theme_get_conn();
    /* Session must already be started by the calling portal */
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (!empty($_SESSION['theme_preview']) && is_array($_SESSION['theme_preview'])) {
        $p = $_SESSION['theme_preview'];
        /* Normalise: make sure every expected key exists */
        return [
            'id'             => 1,
            'name'           => $p['name']           ?? 'Preview',
            'primary_color'  => $p['primary_color']  ?? '336 67% 52%',
            'primary_dark'   => $p['primary_dark']   ?? '336 67% 40%',
            'primary_light'  => $p['primary_light']  ?? ($p['bg_color'] ?? '336 100% 97%'),
            'bg_color'       => $p['bg_color']        ?? '336 100% 97%',
            'surface_color'  => $p['surface_color']   ?? '0 0% 100%',
            'border_color'   => $p['border_color']    ?? '336 60% 87%',
            'text_color'     => $p['text_color']      ?? '336 60% 10%',
            'text_secondary' => $p['text_secondary']  ?? '336 40% 47%',
            'accent_color'   => $p['accent_color']    ?? '207 80% 60%',
            'is_dark'        => (int)($p['is_dark']   ?? 0),
            'updated_at'     => date('Y-m-d H:i:s'),
            '_is_preview'    => true,
        ];
    }

    $r = $conn->query("SELECT * FROM theme_settings WHERE id = 1 LIMIT 1");
    if (!$r || !($row = $r->fetch_assoc())) {
        return get_default_theme();
    }
    return $row;
}

/* ── Default theme (Rose Pink — the original LearnFlow brand) ─────────────── */
function get_default_theme(): array {
    return [
        'id'             => 1,
        'name'           => 'Rose Pink',
        'primary_color'  => '336 67% 52%',
        'primary_dark'   => '336 67% 40%',
        'primary_light'  => '336 20% 97%',
        'bg_color'       => '336 12% 98%',
        'surface_color'  => '0 0% 100%',
        'border_color'   => '336 12% 92%',
        'text_color'     => '336 20% 10%',
        'text_secondary' => '336 12% 44%',
        'accent_color'   => '207 80% 60%',
        'is_dark'        => 0,
    ];
}

/**
 * Returns the <style> block with the active theme's CSS custom properties.
 * Call this inside <head> in every portal file.
 *
 * @param  mysqli  $conn
 * @return string  A complete <style> tag ready to echo
 */
function get_theme_css(?mysqli $conn = null): string {
    if ($conn === null) $conn = _theme_get_conn();
    $t = get_active_theme($conn);

    $p   = htmlspecialchars($t['primary_color']);
    $pd  = htmlspecialchars($t['primary_dark']);
    $pl  = htmlspecialchars($t['primary_light'] ?? '336 20% 97%');
    $bg  = htmlspecialchars($t['bg_color']);
    $sf  = htmlspecialchars($t['surface_color']);
    $bd  = htmlspecialchars($t['border_color']);
    $tx  = htmlspecialchars($t['text_color']);
    $tx2 = htmlspecialchars($t['text_secondary'] ?? '336 12% 44%');
    $ac  = htmlspecialchars($t['accent_color'] ?? '207 80% 60%');

    /* Compute dark-mode complementary values automatically */
    if ($t['is_dark']) {
        $darkBg      = $bg;
        $darkSurface = $sf;
        $darkBorder  = $bd;
        $darkText    = $tx;
        $darkText2   = $tx2;
    } else {
        $primaryParts = explode(' ', trim($t['primary_color']));
        $hue = count($primaryParts) > 0 ? (int)$primaryParts[0] : 230;

        $darkBg      = "{$hue} 12% 8%";
        $darkSurface = "{$hue} 12% 12%";
        $darkBorder  = "{$hue} 12% 18%";
        $darkText    = "{$hue} 8% 92%";
        $darkText2   = "{$hue} 8% 70%";
    }

    return <<<CSS
<style id="lf-theme">
/* ═══════════════════════════════════════════════════════
   LearnFlow Dynamic Theme — generated by config/theme.php
   Active preset: {$t['name']}
   ═══════════════════════════════════════════════════════ */
:root {
  --primary:        hsl({$p});
  --primary-hsl:    {$p};
  --primary-dark:   hsl({$pd});
  --primary-light:  hsl({$pl});
  --primary-rgb:    var(--primary);

  --bg:             hsl({$bg});
  --surface:        hsl({$sf});
  --surface-2:      hsl({$sf} / .72);
  --border:         hsl({$bd});
  --text:           hsl({$tx});
  --text-2:         hsl({$tx2});
  --text-3:         hsl({$tx2} / .6);
  --accent:         hsl({$ac});

  --shadow-card:    0 4px 24px hsl({$p} / .15);
  --shadow-btn:     0 4px 20px hsl({$p} / .38);
}

[data-theme="dark"] {
  --bg:             hsl({$darkBg});
  --surface:        hsl({$darkSurface});
  --surface-2:      hsl({$darkSurface} / .72);
  --border:         hsl({$darkBorder});
  --text:           hsl({$darkText});
  --text-2:         hsl({$darkText2});
  --text-3:         hsl({$darkText2} / .6);
}

/* Re-map legacy variable names used across the portal files */
:root, [data-theme="dark"] {
  --primary-hex:    hsl(var(--primary-hsl));
  --primary-color:  var(--primary);
  --accent-color:   var(--accent);
}
</style>
CSS;
}

/**
 * All built-in presets that the admin can choose from.
 */
function get_theme_presets(): array {
    return [
        [
            'id'             => 'rose-pink',
            'name'           => 'Rose Pink',
            'description'    => 'The original LearnFlow brand — warm and welcoming',
            'primary_color'  => '336 67% 52%',
            'primary_dark'   => '336 67% 40%',
            'primary_light'  => '336 20% 97%',
            'bg_color'       => '336 12% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '336 12% 92%',
            'text_color'     => '336 20% 10%',
            'text_secondary' => '336 12% 44%',
            'accent_color'   => '207 80% 60%',
            'is_dark'        => 0,
            'preview'        => ['#CC3A72', '#FAF9FA', '#FFFFFF', '#4AAEE8'],
        ],
        [
            'id'             => 'ocean-blue',
            'name'           => 'Ocean Blue',
            'description'    => 'Deep ocean blue — professional and calm',
            'primary_color'  => '211 84% 52%',
            'primary_dark'   => '211 84% 40%',
            'primary_light'  => '211 20% 97%',
            'bg_color'       => '211 12% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '211 12% 92%',
            'text_color'     => '211 25% 10%',
            'text_secondary' => '211 12% 44%',
            'accent_color'   => '158 64% 52%',
            'is_dark'        => 0,
            'preview'        => ['#1A6FBF', '#F8FAFC', '#FFFFFF', '#2FC68A'],
        ],
        [
            'id'             => 'forest-green',
            'name'           => 'Forest Green',
            'description'    => 'Natural forest green — fresh and focused',
            'primary_color'  => '145 63% 42%',
            'primary_dark'   => '145 63% 30%',
            'primary_light'  => '145 20% 97%',
            'bg_color'       => '145 10% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '145 10% 92%',
            'text_color'     => '145 25% 10%',
            'text_secondary' => '145 12% 44%',
            'accent_color'   => '45 90% 58%',
            'is_dark'        => 0,
            'preview'        => ['#299453', '#F7FAF8', '#FFFFFF', '#F0B429'],
        ],
        [
            'id'             => 'royal-purple',
            'name'           => 'Royal Purple',
            'description'    => 'Rich royal purple — elegant and prestigious',
            'primary_color'  => '262 80% 58%',
            'primary_dark'   => '262 80% 46%',
            'primary_light'  => '262 20% 97%',
            'bg_color'       => '262 10% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '262 10% 92%',
            'text_color'     => '262 25% 10%',
            'text_secondary' => '262 12% 44%',
            'accent_color'   => '335 80% 58%',
            'is_dark'        => 0,
            'preview'        => ['#7C3AED', '#FAF8FC', '#FFFFFF', '#E8608A'],
        ],
        [
            'id'             => 'sunset-orange',
            'name'           => 'Sunset Orange',
            'description'    => 'Warm sunset orange — energetic and inspiring',
            'primary_color'  => '24 95% 53%',
            'primary_dark'   => '24 95% 41%',
            'primary_light'  => '24 20% 97%',
            'bg_color'       => '24 10% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '24 10% 92%',
            'text_color'     => '24 25% 10%',
            'text_secondary' => '24 12% 44%',
            'accent_color'   => '211 84% 52%',
            'is_dark'        => 0,
            'preview'        => ['#F25C19', '#FAF8F5', '#FFFFFF', '#1A6FBF'],
        ],
        [
            'id'             => 'crimson-red',
            'name'           => 'Crimson Red',
            'description'    => 'Bold crimson — confident and assertive',
            'primary_color'  => '0 72% 51%',
            'primary_dark'   => '0 72% 39%',
            'primary_light'  => '0 20% 97%',
            'bg_color'       => '0 8% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '0 8% 92%',
            'text_color'     => '0 20% 10%',
            'text_secondary' => '0 12% 44%',
            'accent_color'   => '196 80% 55%',
            'is_dark'        => 0,
            'preview'        => ['#DC2626', '#FAF5F5', '#FFFFFF', '#22D3EE'],
        ],
        [
            'id'             => 'midnight-dark',
            'name'           => 'Midnight Dark',
            'description'    => 'Deep dark theme — elegant and easy on the eyes',
            'primary_color'  => '336 80% 65%',
            'primary_dark'   => '336 80% 53%',
            'primary_light'  => '336 30% 20%',
            'bg_color'       => '230 15% 8%',
            'surface_color'  => '230 15% 12%',
            'border_color'   => '230 12% 18%',
            'text_color'     => '230 20% 92%',
            'text_secondary' => '230 12% 68%',
            'accent_color'   => '207 80% 65%',
            'is_dark'        => 1,
            'preview'        => ['#E8608A', '#101216', '#16191E', '#4AAEE8'],
        ],
        [
            'id'             => 'slate-dark',
            'name'           => 'Slate Dark',
            'description'    => 'Cool slate dark — modern and minimal',
            'primary_color'  => '211 84% 62%',
            'primary_dark'   => '211 84% 50%',
            'primary_light'  => '211 30% 20%',
            'bg_color'       => '215 20% 8%',
            'surface_color'  => '215 20% 12%',
            'border_color'   => '215 15% 18%',
            'text_color'     => '215 25% 92%',
            'text_secondary' => '215 15% 68%',
            'accent_color'   => '145 63% 52%',
            'is_dark'        => 1,
            'preview'        => ['#3B82F6', '#0F1219', '#151B26', '#22C55E'],
        ],
        [
            'id'             => 'emerald-dark',
            'name'           => 'Emerald Dark',
            'description'    => 'Rich emerald dark — vibrant and sophisticated',
            'primary_color'  => '145 63% 48%',
            'primary_dark'   => '145 63% 36%',
            'primary_light'  => '145 30% 20%',
            'bg_color'       => '160 20% 7%',
            'surface_color'  => '160 20% 11%',
            'border_color'   => '160 15% 17%',
            'text_color'     => '160 25% 92%',
            'text_secondary' => '160 15% 68%',
            'accent_color'   => '45 90% 58%',
            'is_dark'        => 1,
            'preview'        => ['#22C55E', '#0B0F0D', '#101713', '#FACC15'],
        ],
    ];
}
