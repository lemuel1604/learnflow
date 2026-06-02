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
    $ac  = htmlspecialchars($t['accent_color'] ?? '207 80% 60%');
    $bg  = htmlspecialchars($t['bg_color'] ?? ($t['is_dark'] ? '230 15% 8%' : '0 0% 100%'));
    $sf  = htmlspecialchars($t['surface_color'] ?? ($t['is_dark'] ? '230 15% 12%' : '0 0% 100%'));
    $bd  = htmlspecialchars($t['border_color'] ?? ($t['is_dark'] ? '230 12% 22%' : '336 12% 90%'));
    $tx  = htmlspecialchars($t['text_color'] ?? ($t['is_dark'] ? '0 0% 92%' : '336 20% 10%'));
    $tx2 = htmlspecialchars($t['text_secondary'] ?? ($t['is_dark'] ? '0 0% 65%' : '336 12% 44%'));

    $isDark = (bool)($t['is_dark'] ?? 0);
    // Derive dark-mode surface offsets from hue of primary
    $hue = explode(' ', $t['primary_color'])[0];
    $darkBg  = $isDark ? $bg  : "{$hue} 12% 8%";
    $darkSf  = $isDark ? $sf  : "{$hue} 12% 12%";
    $darkSf2 = $isDark ? htmlspecialchars($t['surface_color'] ?? '') : "{$hue} 12% 16%";
    $darkBd  = $isDark ? $bd  : "{$hue} 12% 22%";
    $darkTx  = $isDark ? $tx  : "{$hue} 8% 92%";
    $darkTx2 = $isDark ? $tx2 : "{$hue} 8% 68%";

    return <<<CSS
<style id="lf-theme">
/* ═══════════════════════════════════════════════════════
   LearnFlow Theme — generated by config/theme.php
   Active preset: {$t['name']}
   ═══════════════════════════════════════════════════════ */
:root {
  --primary:        hsl({$p});
  --primary-hsl:    {$p};
  --primary-dark:   hsl({$pd});
  --primary-light:  hsl({$pl});

  /* Neutral surfaces — NOT the primary colour */
  --bg:             hsl({$bg});
  --surface:        hsl({$sf});
  --surface-2:      hsl({$bg} / 0.55);
  --surface-3:      hsl({$bg} / 0.25);
  --border:         hsl({$bd});
  --border-strong:  hsl({$bd});

  --text:           hsl({$tx});
  --text-2:         hsl({$tx2});
  --text-3:         hsl({$tx2} / 0.65);
  --text-inverse:   #FFFFFF;
  --accent:         hsl({$ac});

  --shadow-card:    0 8px 32px rgba(0,0,0,0.10);
  --shadow-btn:     0 4px 20px hsl({$p} / .32);
}

[data-theme="dark"] {
  --bg:             hsl({$darkBg});
  --surface:        hsl({$darkSf});
  --surface-2:      hsl({$darkSf2});
  --border:         hsl({$darkBd});
  --text:           hsl({$darkTx});
  --text-2:         hsl({$darkTx2});
  --text-3:         hsl({$darkTx2} / 0.6);
  --text-inverse:   #FFFFFF;
}

/* Re-map legacy variable names */
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

        /* ── NEW PRESETS ───────────────────────────────────────────────── */

        [
            'id'             => 'neon-cyan',
            'name'           => 'Neon Cyan',
            'description'    => 'Electric cyan on deep dark — ultra-modern',
            'primary_color'  => '185 100% 50%',
            'primary_dark'   => '185 100% 38%',
            'primary_light'  => '185 40% 18%',
            'bg_color'       => '220 25% 6%',
            'surface_color'  => '220 25% 10%',
            'border_color'   => '185 40% 18%',
            'text_color'     => '185 20% 93%',
            'text_secondary' => '185 15% 62%',
            'accent_color'   => '290 100% 68%',
            'is_dark'        => 1,
            'preview'        => ['#00E5FF', '#090D12', '#0E1419', '#CC44FF'],
        ],
        [
            'id'             => 'neon-lime',
            'name'           => 'Neon Lime',
            'description'    => 'High-voltage lime green on charcoal — vivid',
            'primary_color'  => '80 100% 50%',
            'primary_dark'   => '80 100% 38%',
            'primary_light'  => '80 40% 18%',
            'bg_color'       => '215 22% 7%',
            'surface_color'  => '215 22% 11%',
            'border_color'   => '80 30% 18%',
            'text_color'     => '80 15% 93%',
            'text_secondary' => '80 12% 62%',
            'accent_color'   => '35 100% 55%',
            'is_dark'        => 1,
            'preview'        => ['#80FF00', '#0A0D10', '#10141A', '#FF9500'],
        ],
        [
            'id'             => 'pastel-lavender',
            'name'           => 'Pastel Lavender',
            'description'    => 'Soft lavender pastels — gentle and dreamy',
            'primary_color'  => '265 60% 65%',
            'primary_dark'   => '265 60% 52%',
            'primary_light'  => '265 80% 96%',
            'bg_color'       => '265 40% 97%',
            'surface_color'  => '265 20% 100%',
            'border_color'   => '265 30% 88%',
            'text_color'     => '265 30% 15%',
            'text_secondary' => '265 20% 48%',
            'accent_color'   => '325 70% 68%',
            'is_dark'        => 0,
            'preview'        => ['#9B72CF', '#F5F2FB', '#FFFFFF', '#E879A8'],
        ],
        [
            'id'             => 'pastel-peach',
            'name'           => 'Pastel Peach',
            'description'    => 'Warm peach pastels — soft, inviting, cozy',
            'primary_color'  => '20 85% 65%',
            'primary_dark'   => '20 85% 52%',
            'primary_light'  => '20 100% 96%',
            'bg_color'       => '20 50% 97%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '20 40% 88%',
            'text_color'     => '20 30% 15%',
            'text_secondary' => '20 20% 48%',
            'accent_color'   => '175 55% 48%',
            'is_dark'        => 0,
            'preview'        => ['#F4845F', '#FDF7F4', '#FFFFFF', '#2BBFA0'],
        ],
        [
            'id'             => 'earth-terracotta',
            'name'           => 'Earth & Terracotta',
            'description'    => 'Rich earthy tones — warm, grounded, organic',
            'primary_color'  => '15 65% 48%',
            'primary_dark'   => '15 65% 36%',
            'primary_light'  => '15 40% 95%',
            'bg_color'       => '30 20% 96%',
            'surface_color'  => '30 15% 100%',
            'border_color'   => '30 20% 86%',
            'text_color'     => '20 30% 12%',
            'text_secondary' => '20 18% 44%',
            'accent_color'   => '45 70% 52%',
            'is_dark'        => 0,
            'preview'        => ['#C0512A', '#F7F3F0', '#FFFFFF', '#D4A017'],
        ],
        [
            'id'             => 'nordic-frost',
            'name'           => 'Nordic Frost',
            'description'    => 'Icy blue-grey — clean, minimal, Scandinavian',
            'primary_color'  => '205 55% 48%',
            'primary_dark'   => '205 55% 36%',
            'primary_light'  => '205 35% 95%',
            'bg_color'       => '210 20% 96%',
            'surface_color'  => '210 10% 100%',
            'border_color'   => '210 20% 87%',
            'text_color'     => '210 25% 12%',
            'text_secondary' => '210 15% 44%',
            'accent_color'   => '155 45% 48%',
            'is_dark'        => 0,
            'preview'        => ['#3A7EAF', '#F3F6F9', '#FFFFFF', '#3AA87A'],
        ],
        [
            'id'             => 'gold-luxury',
            'name'           => 'Gold Luxury',
            'description'    => 'Deep navy + champagne gold — opulent prestige',
            'primary_color'  => '43 90% 52%',
            'primary_dark'   => '43 90% 38%',
            'primary_light'  => '43 50% 18%',
            'bg_color'       => '222 35% 8%',
            'surface_color'  => '222 35% 12%',
            'border_color'   => '43 30% 20%',
            'text_color'     => '43 20% 93%',
            'text_secondary' => '43 15% 62%',
            'accent_color'   => '222 70% 58%',
            'is_dark'        => 1,
            'preview'        => ['#E8B923', '#0A0C14', '#10131E', '#4A7EE8'],
        ],
        [
            'id'             => 'sakura',
            'name'           => 'Sakura',
            'description'    => 'Japanese cherry blossom — delicate and refined',
            'primary_color'  => '345 75% 68%',
            'primary_dark'   => '345 75% 54%',
            'primary_light'  => '345 80% 97%',
            'bg_color'       => '345 30% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '345 25% 89%',
            'text_color'     => '345 25% 14%',
            'text_secondary' => '345 15% 48%',
            'accent_color'   => '195 65% 52%',
            'is_dark'        => 0,
            'preview'        => ['#EE829A', '#FDF8F9', '#FFFFFF', '#2AA8C8'],
        ],
        [
            'id'             => 'obsidian-violet',
            'name'           => 'Obsidian Violet',
            'description'    => 'Deep obsidian with vivid violet — dramatic',
            'primary_color'  => '270 90% 65%',
            'primary_dark'   => '270 90% 52%',
            'primary_light'  => '270 40% 18%',
            'bg_color'       => '240 20% 6%',
            'surface_color'  => '240 20% 10%',
            'border_color'   => '270 30% 18%',
            'text_color'     => '270 15% 93%',
            'text_secondary' => '270 12% 62%',
            'accent_color'   => '160 70% 50%',
            'is_dark'        => 1,
            'preview'        => ['#9B3EFF', '#0A090F', '#100F18', '#1FBD80'],
        ],

        /* ── GRADIENT PRESETS ───────────────────────────────────────────── */
        [
            'id'             => 'grad-aurora',
            'name'           => 'Aurora',
            'description'    => 'Northern lights — teal to violet',
            'primary_color'  => '185 90% 52%',
            'primary_dark'   => '270 80% 55%',
            'primary_light'  => '185 40% 18%',
            'bg_color'       => '230 25% 7%',
            'surface_color'  => '230 25% 11%',
            'border_color'   => '200 30% 18%',
            'text_color'     => '200 15% 93%',
            'text_secondary' => '200 12% 65%',
            'accent_color'   => '145 70% 55%',
            'is_dark'        => 1,
            'gradient'       => 'linear-gradient(135deg, #00D4C8 0%, #4A5EFF 50%, #9B3EEF 100%)',
            'preview'        => ['#00D4C8', '#0A0D14', '#101520', '#6B3EEF'],
        ],
        [
            'id'             => 'grad-sunset',
            'name'           => 'Sunset Blaze',
            'description'    => 'Golden hour — amber to deep rose',
            'primary_color'  => '28 95% 58%',
            'primary_dark'   => '0 85% 55%',
            'primary_light'  => '28 100% 96%',
            'bg_color'       => '25 30% 97%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '25 20% 88%',
            'text_color'     => '20 30% 12%',
            'text_secondary' => '20 18% 44%',
            'accent_color'   => '350 80% 60%',
            'is_dark'        => 0,
            'gradient'       => 'linear-gradient(135deg, #FFBD3C 0%, #FF7A3C 50%, #FF3A6E 100%)',
            'preview'        => ['#FF9A3C', '#FDF7F2', '#FFFFFF', '#FF4A6E'],
        ],
        [
            'id'             => 'grad-ocean-depths',
            'name'           => 'Ocean Depths',
            'description'    => 'Deep sea — navy to emerald',
            'primary_color'  => '205 90% 55%',
            'primary_dark'   => '165 80% 42%',
            'primary_light'  => '205 40% 18%',
            'bg_color'       => '220 30% 7%',
            'surface_color'  => '220 30% 11%',
            'border_color'   => '210 25% 18%',
            'text_color'     => '210 15% 93%',
            'text_secondary' => '210 12% 65%',
            'accent_color'   => '165 70% 52%',
            'is_dark'        => 1,
            'gradient'       => 'linear-gradient(135deg, #0B4F8A 0%, #1A7FBF 40%, #1FC88A 100%)',
            'preview'        => ['#1A7FBF', '#090E14', '#0F1620', '#1FC88A'],
        ],
        [
            'id'             => 'grad-candy',
            'name'           => 'Candy Pop',
            'description'    => 'Sweet & playful — pink to sky',
            'primary_color'  => '320 80% 65%',
            'primary_dark'   => '200 75% 55%',
            'primary_light'  => '320 80% 96%',
            'bg_color'       => '300 30% 98%',
            'surface_color'  => '0 0% 100%',
            'border_color'   => '300 20% 88%',
            'text_color'     => '280 25% 15%',
            'text_secondary' => '280 15% 48%',
            'accent_color'   => '195 75% 55%',
            'is_dark'        => 0,
            'gradient'       => 'linear-gradient(135deg, #F060B0 0%, #A050E0 50%, #30C0F0 100%)',
            'preview'        => ['#F060B0', '#FDF5FB', '#FFFFFF', '#30C0F0'],
        ],
        [
            'id'             => 'grad-midnight-fire',
            'name'           => 'Midnight Fire',
            'description'    => 'Dark base — orange to crimson glow',
            'primary_color'  => '25 100% 60%',
            'primary_dark'   => '0 90% 52%',
            'primary_light'  => '25 50% 18%',
            'bg_color'       => '220 25% 6%',
            'surface_color'  => '220 25% 10%',
            'border_color'   => '15 30% 18%',
            'text_color'     => '20 15% 93%',
            'text_secondary' => '20 12% 62%',
            'accent_color'   => '45 95% 58%',
            'is_dark'        => 1,
            'gradient'       => 'linear-gradient(135deg, #FF8C00 0%, #FF4800 50%, #FF1A1A 100%)',
            'preview'        => ['#FF8C00', '#09090E', '#100F18', '#FF3838'],
        ],
        [
            'id'             => 'grad-galaxy',
            'name'           => 'Galaxy',
            'description'    => 'Cosmic purple — indigo to magenta',
            'primary_color'  => '260 85% 65%',
            'primary_dark'   => '300 80% 58%',
            'primary_light'  => '260 40% 18%',
            'bg_color'       => '245 25% 6%',
            'surface_color'  => '245 25% 10%',
            'border_color'   => '260 30% 18%',
            'text_color'     => '260 15% 93%',
            'text_secondary' => '260 12% 65%',
            'accent_color'   => '185 80% 55%',
            'is_dark'        => 1,
            'gradient'       => 'linear-gradient(135deg, #3B1FCC 0%, #7B2FEE 40%, #CC1FAA 100%)',
            'preview'        => ['#6633EE', '#08080F', '#100E1A', '#EE33AA'],
        ],
    ];
}