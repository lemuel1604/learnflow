<?php

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['theme_preview'])) return; /* Nothing to show */

$preview      = $_SESSION['theme_preview'];
$preview_name = htmlspecialchars($preview['name'] ?? 'Preview');
$since        = isset($preview['_preview_since'])
    ? gmdate('H:i:s \U\T\C', $preview['_preview_since'])
    : '';
?>

<!-- ═══════════════════════════════════════════════════════════════
     THEME PREVIEW BAR — rendered only for admins in preview mode
     ═══════════════════════════════════════════════════════════════ -->
<div id="lf-preview-bar" style="
     position:fixed;bottom:0;left:0;right:0;z-index:99999;
     background:hsl(230 15% 12%);
     border-top:2px solid hsl(<?= htmlspecialchars($preview['primary_color']) ?>);
     padding:12px 20px;
     display:flex;align-items:center;justify-content:space-between;
     gap:16px;flex-wrap:wrap;
     box-shadow:0 -4px 24px rgba(0,0,0,.35);
     font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif">

  <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
    <!-- Animated pulsing dot -->
    <div style="position:relative;width:12px;height:12px;flex-shrink:0">
      <div style="position:absolute;inset:0;border-radius:50%;
                  background:hsl(<?= htmlspecialchars($preview['primary_color']) ?>);
                  animation:lf-pulse 1.6s ease-in-out infinite"></div>
      <div style="position:absolute;inset:0;border-radius:50%;
                  background:hsl(<?= htmlspecialchars($preview['primary_color']) ?>)"></div>
    </div>

    <div style="min-width:0">
      <span style="color:#fff;font-size:13px;font-weight:700">
        Previewing: <?= $preview_name ?>
      </span>
      <span style="color:rgba(255,255,255,.45);font-size:12px;margin-left:10px">
        Only you can see this. Other users see the current live theme.
        <?= $since ? "Started at {$since}." : '' ?>
      </span>
    </div>
  </div>

  <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
    <!-- Portal switcher links -->
    <span style="color:rgba(255,255,255,.45);font-size:11px;margin-right:6px">Preview in:</span>
    <a href="learnflow-admin.php"      style="color:rgba(255,255,255,.6);font-size:12px;text-decoration:none;padding:5px 10px;border-radius:6px;border:1px solid rgba(255,255,255,.15)"
       onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Admin</a>
    <a href="learnflow-instructor.php" style="color:rgba(255,255,255,.6);font-size:12px;text-decoration:none;padding:5px 10px;border-radius:6px;border:1px solid rgba(255,255,255,.15)"
       onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Instructor</a>
    <a href="learnflow-student.php"    style="color:rgba(255,255,255,.6);font-size:12px;text-decoration:none;padding:5px 10px;border-radius:6px;border:1px solid rgba(255,255,255,.15)"
       onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Student</a>

    <div style="width:1px;height:28px;background:rgba(255,255,255,.15);margin:0 6px"></div>

    <!-- Cancel button -->
    <button onclick="cancelPreview()" style="
        padding:8px 18px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;
        background:transparent;border:1.5px solid rgba(255,255,255,.3);color:rgba(255,255,255,.75);
        transition:.15s"
      onmouseover="this.style.background='rgba(255,255,255,.1)'"
      onmouseout="this.style.background='transparent'">
      Cancel
    </button>

    <!-- Apply to all button -->
    <button onclick="commitPreview()" style="
        padding:8px 20px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:700;
        background:hsl(<?= htmlspecialchars($preview['primary_color']) ?>);
        border:none;color:#fff;
        box-shadow:0 3px 12px hsl(<?= htmlspecialchars($preview['primary_color']) ?> / .5);
        transition:.15s"
      onmouseover="this.style.opacity='.88'"
      onmouseout="this.style.opacity='1'">
      ✓ Apply to All Portals
    </button>
  </div>

</div>

<!-- Push page content up so the bar doesn't overlap the bottom of the page -->
<style>
  body { padding-bottom: 70px !important; }
  @keyframes lf-pulse {
    0%, 100% { transform: scale(1);   opacity: .9; }
    50%       { transform: scale(2.2); opacity: 0;  }
  }
</style>

<script>
function cancelPreview() {
  fetch('api-theme-preview.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ action: 'cancel_preview' })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('lf-preview-bar').remove();
      document.body.style.paddingBottom = '';
      /* Restore the live theme */
      if (data.theme && window.applyThemeLive) applyThemeLive(data.theme);
      if (typeof showToast === 'function') showToast('Preview cancelled', 'info');
    }
  })
  .catch(() => location.reload());
}

function commitPreview() {
  fetch('api-theme-preview.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ action: 'commit_preview' })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('lf-preview-bar').remove();
      document.body.style.paddingBottom = '';
      if (typeof showToast === 'function') showToast('Theme applied to all portals!', 'success');
    } else {
      alert(data.message || 'Failed to apply theme');
    }
  })
  .catch(() => location.reload());
}
</script>
