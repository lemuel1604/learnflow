<?php
session_start();

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $map = [
        'admin'      => 'learnflow-admin.php',
        'instructor' => 'learnflow-instructor.php',
        'student'    => 'learnflow-student.php',
    ];
    if (isset($map[$_SESSION['role']])) {
        header('Location: ' . $map[$_SESSION['role']]);
        exit;
    }
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/config/theme.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_magic_link') {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !str_ends_with($email, '@plpasig.edu.ph')) {
        $error = 'Please use a valid @plpasig.edu.ph email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } else {
        $user = getUserByEmail($email);
        if (!$user) {
            $error = 'This email is not registered. Please contact your administrator.';
        } elseif ($user['status'] === 'suspended') {
            $error = 'This account has been suspended. Contact your administrator.';
        } else {
            $token      = generateToken();
            $token_hash = hashToken($token);
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            if (saveAuthToken($email, $token_hash, $expires_at)) {
                $user_name = $user['display_name']
                    ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
                    ?: $email;
                send_magic_link_email($email, $token, $user_name);
                $success = 'Magic link sent! Check your inbox — expires in 15 minutes.';
            }
        }
    }
}

$post_email = htmlspecialchars($_POST['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnFlow – Sign In</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<?= get_theme_css($conn) ?>
<style>
/* ── Login-page extras (not part of the theme system) ── */
:root {
  --danger:    #D84040;
  --success:   #0d9e70;
  --radius:    12px;
  --radius-sm: 8px;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

html, body {
  height: 100%;
  width: 100%;
  overflow: hidden;
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  display: flex;
  align-items: stretch;
  transition: background .3s, color .3s;
}

/* ── Theme toggle ── */
.theme-toggle {
  position: fixed; top: 20px; right: 20px;
  background: var(--surface); border: 1.5px solid var(--border);
  border-radius: 50px; padding: 6px 14px;
  font-size: 12px; font-weight: 600; color: var(--text-2);
  cursor: pointer; display: flex; align-items: center; gap: 6px;
  box-shadow: 0 2px 12px hsl(var(--primary-hsl) / .12); transition: .2s; z-index: 200;
}
.theme-toggle:hover { border-color: var(--primary); color: var(--primary); }
.theme-toggle svg { width:14px; height:14px; }

/* ══ LEFT PANEL ══ */
.left-panel {
  position: relative;
  width: 52%;
  height: 100vh;
  background: linear-gradient(145deg, var(--primary-dark) 0%, var(--primary) 55%, #e8608a 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  padding: 60px 56px 80px;
  overflow: hidden;
  flex-shrink: 0;
}

.flow-svg {
  position: absolute; inset: 0; width: 100%; height: 100%;
  pointer-events: none; z-index: 0;
}

.orb {
  position: absolute; border-radius: 50%;
  background: rgba(255,255,255,0.06);
  animation: floatOrb linear infinite;
  pointer-events: none;
}
.orb-1 { width:280px;height:280px; top:-80px; left:-80px; animation-duration:18s; }
.orb-2 { width:180px;height:180px; bottom:10%; right:-50px; animation-duration:14s; animation-delay:-7s; background:rgba(255,255,255,0.08); }
.orb-3 { width:120px;height:120px; top:40%; left:30%; animation-duration:22s; animation-delay:-3s; background:rgba(255,255,255,0.04); }
.orb-4 { width:60px;height:60px; bottom:25%; left:15%; animation-duration:10s; animation-delay:-5s; background:rgba(255,255,255,0.10); }

@keyframes floatOrb {
  0%,100% { transform:translateY(0) scale(1); }
  33%      { transform:translateY(-18px) scale(1.04); }
  66%      { transform:translateY(10px) scale(0.97); }
}

.flow-lines { position:absolute; inset:0; overflow:hidden; pointer-events:none; }
.flow-line {
  position: absolute; width: 2px;
  background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.25), transparent);
  border-radius: 4px;
  animation: flowDown linear infinite;
}
@keyframes flowDown {
  0%   { transform:translateY(-100%); opacity:0; }
  10%  { opacity:1; }
  90%  { opacity:1; }
  100% { transform:translateY(200%); opacity:0; }
}

.left-content { position:relative; z-index:2; }

.brand-lockup { display:flex; align-items:center; gap:14px; margin-bottom:48px; }
.brand-icon {
  width:52px; height:52px; border-radius:14px;
  background: rgba(255,255,255,0.15);
  border: 1.5px solid rgba(255,255,255,0.28);
  display:flex; align-items:center; justify-content:center;
  backdrop-filter:blur(8px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.15), inset 0 1px 0 rgba(255,255,255,0.25);
  overflow:hidden; flex-shrink:0;
}
.brand-name {
  font-family:'Syne',sans-serif; font-size:26px; font-weight:800; color:#fff; letter-spacing:-0.3px;
}
.brand-name span { opacity:0.65; }

.left-headline {
  font-family:'Syne',sans-serif;
  font-size: clamp(32px,4vw,48px);
  font-weight:800; line-height:1.1; color:#fff;
  margin-bottom:18px; letter-spacing:-1px;
}
.left-headline em {
  font-style:normal; position:relative; display:inline-block;
}
.left-headline em::after {
  content:''; position:absolute; left:0; bottom:2px;
  width:100%; height:3px;
  background:rgba(255,255,255,0.45); border-radius:2px;
}

.left-sub {
  color:rgba(255,255,255,0.72); font-size:15px; line-height:1.65;
  max-width:340px; margin-bottom:44px;
}

/* ── Feature pills (SVG icons) ── */
.feature-list { display:flex; flex-direction:column; gap:12px; }
.feature-pill {
  display:flex; align-items:center; gap:12px;
  background:rgba(255,255,255,0.10);
  border:1px solid rgba(255,255,255,0.18);
  border-radius:50px; padding:10px 18px;
  backdrop-filter:blur(8px); width:fit-content;
  animation:slideIn .5s ease both;
}
.feature-pill:nth-child(1) { animation-delay:.1s; }
.feature-pill:nth-child(2) { animation-delay:.2s; }
.feature-pill:nth-child(3) { animation-delay:.3s; }
@keyframes slideIn {
  from { opacity:0; transform:translateX(-20px); }
  to   { opacity:1; transform:translateX(0); }
}
.pill-icon {
  width:28px; height:28px; border-radius:50%;
  background:rgba(255,255,255,0.18);
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0;
}
.pill-icon svg { width:14px; height:14px; fill:none; stroke:#fff; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.pill-text { color:#fff; font-size:13px; font-weight:500; }

/* ── Marquee ── */
.marquee-strip {
  position:absolute; bottom:0; left:0; right:0; height:40px;
  background:rgba(0,0,0,0.15); display:flex; align-items:center;
  overflow:hidden; z-index:2;
}
.marquee-track {
  display:flex; white-space:nowrap;
  animation:marquee 22s linear infinite;
}
@keyframes marquee {
  from { transform:translateX(0); }
  to   { transform:translateX(-50%); }
}
.marquee-item { color:rgba(255,255,255,0.45); font-size:11px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; padding:0 28px; }
.marquee-dot  { color:rgba(255,255,255,0.25); }

/* ══ RIGHT PANEL ══ */
.right-panel {
  flex:1;
  height: 100vh;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  padding:48px 40px;
  background:var(--surface);
  position:relative;
  overflow-y: auto;
}
.right-panel::before {
  content:''; position:absolute; inset:0;
  background-image:
    linear-gradient(var(--border) 1px, transparent 1px),
    linear-gradient(90deg, var(--border) 1px, transparent 1px);
  background-size:32px 32px; opacity:0.3; pointer-events:none;
}

.form-container {
  position:relative; z-index:1; width:100%; max-width:380px;
  animation:fadeUp .5s .1s ease both;
}
@keyframes fadeUp {
  from { opacity:0; transform:translateY(20px); }
  to   { opacity:1; transform:translateY(0); }
}

.form-eyebrow { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.form-eyebrow-line { width:28px; height:2px; background:var(--primary); border-radius:2px; }
.form-eyebrow-text { font-size:11px; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:1.5px; }

.form-title {
  font-family:'Syne',sans-serif; font-size:28px; font-weight:800;
  color:var(--text); margin-bottom:4px; letter-spacing:-0.5px; line-height:1.15;
}
.form-sub { color:var(--text-2); font-size:13px; margin-bottom:28px; line-height:1.6; }
.form-sub strong { color:var(--text); }
.form-divider { height:1px; background:var(--border); margin:0 0 24px; }

/* Alerts */
.alert {
  padding:11px 14px; border-radius:var(--radius-sm);
  font-size:13px; margin-bottom:18px;
  display:flex; align-items:flex-start; gap:9px; line-height:1.5;
  animation:shake .3s ease;
}
.alert svg { width:16px; height:16px; flex-shrink:0; margin-top:1px; }
@keyframes shake {
  0%,100%{ transform:translateX(0); } 25%{ transform:translateX(-4px); } 75%{ transform:translateX(4px); }
}
.alert-error   { background:rgba(216,64,64,.08); border:1px solid rgba(216,64,64,.22); color:var(--danger); }
.alert-success { background:rgba(13,158,112,.08); border:1px solid rgba(13,158,112,.25); color:var(--success); }

/* Form fields */
.form-group { margin-bottom:18px; }
.form-label {
  display:block; font-size:11px; font-weight:700; color:var(--text-2);
  margin-bottom:7px; text-transform:uppercase; letter-spacing:.8px;
}
.input-wrap { position:relative; }
.input-icon {
  position:absolute; left:13px; top:50%; transform:translateY(-50%);
  pointer-events:none; opacity:.5; transition:opacity .2s;
  display:flex; align-items:center;
}
.input-icon svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; color:var(--text-2); }
.input-wrap:focus-within .input-icon { opacity:1; }
.input-wrap input {
  width:100%; padding:12px 100px 12px 40px;
  border-radius:var(--radius-sm); border:1.5px solid var(--border);
  background:var(--surface-2); color:var(--text);
  font-family:'DM Sans',sans-serif; font-size:14px;
  transition:.2s; outline:none;
}
.input-wrap input::placeholder { color:var(--text-3); }
.input-wrap input:focus {
  border-color:var(--primary); background:var(--surface);
  box-shadow:0 0 0 4px hsl(var(--primary-hsl) / .08);
}
.input-wrap input.invalid { border-color:var(--danger); box-shadow:0 0 0 3px rgba(216,64,64,.08); }

.domain-badge {
  position:absolute; right:10px; top:50%; transform:translateY(-50%);
  background:var(--primary-light); color:var(--primary);
  font-size:10px; font-weight:700; padding:3px 7px;
  border-radius:4px; letter-spacing:.3px; pointer-events:none;
  border:1px solid var(--border);
}

.email-hint { font-size:11px; color:var(--text-3); margin-top:5px; display:block; line-height:1.5; }

/* Submit button */
.btn-primary {
  width:100%; padding:14px; border-radius:var(--radius-sm); border:none;
  background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color:#fff; font-family:'Syne',sans-serif; font-size:14px; font-weight:700;
  letter-spacing:.3px; cursor:pointer;
  box-shadow:0 4px 20px hsl(var(--primary-hsl) / .38), inset 0 1px 0 rgba(255,255,255,.15);
  transition:.2s; display:flex; align-items:center; justify-content:center; gap:10px;
  margin-top:4px; position:relative; overflow:hidden;
}
.btn-primary::after {
  content:''; position:absolute; inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.12),transparent);
  opacity:0; transition:opacity .2s;
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 28px hsl(var(--primary-hsl) / .46), inset 0 1px 0 rgba(255,255,255,.15); }
.btn-primary:hover::after { opacity:1; }
.btn-primary:active { transform:translateY(0); }
.btn-primary:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.btn-primary svg { width:16px; height:16px; fill:none; stroke:#fff; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; transition:transform .2s; flex-shrink:0; }
.btn-primary:hover svg.arrow-icon { transform:translateX(4px); }
.btn-spinner {
  width:16px; height:16px; border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff; border-radius:50%;
  animation:spin .7s linear infinite; display:none; flex-shrink:0;
}
@keyframes spin { to { transform:rotate(360deg); } }

/* Info box */
.info-box {
  background:var(--surface-2); border:1px solid var(--border);
  border-left:3px solid var(--primary); border-radius:var(--radius-sm);
  padding:12px 14px; font-size:12px; color:var(--text-2);
  line-height:1.6; margin-top:16px; display:flex; gap:10px; align-items:flex-start;
}
.info-box svg { width:15px; height:15px; fill:none; stroke:var(--primary); stroke-width:2; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; margin-top:1px; }
.info-box strong { color:var(--text); }

/* Footer */
.form-footer { text-align:center; color:var(--text-3); font-size:11.5px; margin-top:28px; line-height:1.6; }
.form-footer a { color:var(--primary); text-decoration:none; font-weight:600; }
.form-footer a:hover { text-decoration:underline; }

/* Step dots */
.step-dots { display:flex; gap:6px; justify-content:center; margin-top:22px; }
.step-dot { width:6px; height:6px; border-radius:50%; background:var(--border); transition:.3s; }
.step-dot.active { background:var(--primary); width:20px; border-radius:3px; }

/* Toast */
.toast {
  position:fixed; bottom:28px; left:50%;
  transform:translateX(-50%) translateY(80px);
  background:var(--text); color:#fff;
  padding:11px 22px; border-radius:50px;
  font-size:13px; font-weight:600;
  box-shadow:0 8px 28px rgba(0,0,0,.2);
  transition:transform .3s ease, opacity .3s ease;
  opacity:0; pointer-events:none; white-space:nowrap; z-index:999;
}
.toast.show { transform:translateX(-50%) translateY(0); opacity:1; }
.toast.success { background:var(--primary); }

/* Responsive */
@media (max-width:768px) {
  html, body { overflow: hidden; height: 100%; }
  body { flex-direction: column; }
  .left-panel { width:100%; height: auto; min-height: auto; flex-shrink:0; padding:36px 24px 52px; }
  .left-headline { font-size:28px; }
  .left-sub { font-size:13px; margin-bottom:28px; }
  .right-panel { height: auto; flex:1; overflow-y:auto; padding:36px 24px; justify-content:flex-start; }
  .marquee-strip { display:none; }
  .feature-list { flex-direction:row; flex-wrap:wrap; gap:8px; }
  .brand-lockup { margin-bottom:28px; }
}
</style>
<script>
// Set data-theme BEFORE first paint to avoid flash of wrong theme
(function() {
  var t = localStorage.getItem('lf_theme') ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
</head>
<body>
<?php require_once __DIR__ . '/theme-preview-bar.php'; ?>

<!-- Theme toggle -->
<button class="theme-toggle" onclick="toggleTheme()" id="themeBtn">
  <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
  </svg>
  <span id="themeTxt">Dark</span>
</button>
<div class="toast" id="mainToast"></div>

<!-- ══ LEFT PANEL ══ -->
<div class="left-panel">

  <!-- Animated SVG flow background -->
  <svg class="flow-svg" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <filter id="goo">
        <feGaussianBlur in="SourceGraphic" stdDeviation="18" result="blur"/>
        <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 28 -10" result="goo"/>
      </filter>
      <filter id="blur-sm"><feGaussianBlur stdDeviation="6"/></filter>
    </defs>
    <g filter="url(#goo)">
      <ellipse cx="480" cy="150" rx="200" ry="180" fill="rgba(255,255,255,0.07)">
        <animateTransform attributeName="transform" type="rotate" from="0 480 150" to="360 480 150" dur="30s" repeatCount="indefinite"/>
        <animate attributeName="rx" values="200;230;200" dur="8s" repeatCount="indefinite"/>
        <animate attributeName="ry" values="180;150;180" dur="8s" repeatCount="indefinite"/>
      </ellipse>
      <ellipse cx="80" cy="600" rx="180" ry="220" fill="rgba(255,255,255,0.06)">
        <animateTransform attributeName="transform" type="rotate" from="360 80 600" to="0 80 600" dur="25s" repeatCount="indefinite"/>
      </ellipse>
      <circle cx="300" cy="400" r="120" fill="rgba(255,255,255,0.04)">
        <animate attributeName="r" values="120;150;120" dur="10s" repeatCount="indefinite"/>
        <animate attributeName="cx" values="300;330;300" dur="12s" repeatCount="indefinite"/>
      </circle>
    </g>
    <path d="M-60,300 Q100,180 200,300 T460,300 T700,300" stroke="rgba(255,255,255,0.12)" stroke-width="1.5" fill="none" filter="url(#blur-sm)">
      <animate attributeName="d" values="M-60,300 Q100,180 200,300 T460,300 T700,300;M-60,280 Q100,400 200,280 T460,280 T700,280;M-60,300 Q100,180 200,300 T460,300 T700,300" dur="8s" repeatCount="indefinite"/>
    </path>
    <path d="M-60,450 Q150,330 250,450 T520,450 T750,450" stroke="rgba(255,255,255,0.08)" stroke-width="1" fill="none" filter="url(#blur-sm)">
      <animate attributeName="d" values="M-60,450 Q150,330 250,450 T520,450 T750,450;M-60,430 Q150,550 250,430 T520,430 T750,430;M-60,450 Q150,330 250,450 T520,450 T750,450" dur="11s" repeatCount="indefinite"/>
    </path>
    <circle cx="120" cy="200" r="3" fill="rgba(255,255,255,0.3)">
      <animate attributeName="cy" values="200;180;200" dur="4s" repeatCount="indefinite"/>
      <animate attributeName="opacity" values="0.3;0.7;0.3" dur="4s" repeatCount="indefinite"/>
    </circle>
    <circle cx="420" cy="500" r="2" fill="rgba(255,255,255,0.25)">
      <animate attributeName="cy" values="500;475;500" dur="5.5s" repeatCount="indefinite"/>
    </circle>
    <circle cx="250" cy="650" r="4" fill="rgba(255,255,255,0.15)">
      <animate attributeName="cx" values="250;270;250" dur="7s" repeatCount="indefinite"/>
    </circle>
    <circle cx="500" cy="350" r="2.5" fill="rgba(255,255,255,0.2)">
      <animate attributeName="cy" values="350;320;350" dur="6s" repeatCount="indefinite"/>
    </circle>
  </svg>

  <div class="flow-lines" id="flowLines"></div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>
  <div class="orb orb-4"></div>

  <!-- Content -->
  <div class="left-content">
    <div class="brand-lockup">
      <!-- SVG placeholder logo — graduation cap + flow wave -->
      <div class="brand-icon">
        <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <polygon points="26,13 41,20.5 26,28 11,20.5" fill="white" opacity="0.95"/>
          <line x1="38" y1="20.5" x2="38" y2="31" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/>
          <circle cx="38" cy="33" r="2.2" fill="white" opacity="0.7"/>
          <path d="M13,36 Q18,32 23,36 Q28,40 33,36 Q38,32 43,36" stroke="white" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.75"/>
          <path d="M15,42 Q20,38 25,42 Q30,46 35,42 Q40,38 45,42" stroke="white" stroke-width="1.4" stroke-linecap="round" fill="none" opacity="0.4"/>
        </svg>
      </div>
      <div class="brand-name">Learn<span>Flow</span></div>
    </div>

    <h1 class="left-headline">
      Where<br>knowledge<br><em>flows freely</em>
    </h1>
    <p class="left-sub">
      Your all-in-one learning platform. Courses, progress tracking, and collaboration — all in one seamless flow.
    </p>

    <div class="feature-list">
      <!-- Pill 1: Magic link (lightning bolt) -->
      <div class="feature-pill">
        <div class="pill-icon">
          <svg viewBox="0 0 24 24"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <span class="pill-text">Passwordless magic link login</span>
      </div>
      <!-- Pill 2: Progress tracking (bar chart) -->
      <div class="feature-pill">
        <div class="pill-icon">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </div>
        <span class="pill-text">Live course progress tracking</span>
      </div>
      <!-- Pill 3: Secure (shield) -->
      <div class="feature-pill">
        <div class="pill-icon">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <span class="pill-text">Secure institutional access</span>
      </div>
    </div>
  </div>

  <!-- Marquee -->
  <div class="marquee-strip">
    <div class="marquee-track">
      <span class="marquee-item">Learn</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Flow</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Grow</span><span class="marquee-dot">·</span>
      <span class="marquee-item">PLP Pasig</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Students</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Instructors</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Courses</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Learn</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Flow</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Grow</span><span class="marquee-dot">·</span>
      <span class="marquee-item">PLP Pasig</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Students</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Instructors</span><span class="marquee-dot">·</span>
      <span class="marquee-item">Courses</span><span class="marquee-dot">·</span>
    </div>
  </div>
</div>

<!-- ══ RIGHT PANEL ══ -->
<div class="right-panel">
  <div class="form-container">

    <div class="form-eyebrow">
      <div class="form-eyebrow-line"></div>
      <span class="form-eyebrow-text">LearnFlow</span>
    </div>

    <h2 class="form-title">Welcome back!</h2>
    <p class="form-sub">
      Enter your <strong>@plpasig.edu.ph</strong> email and we'll send a secure magic link straight to your inbox.
    </p>

    <div class="form-divider"></div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">
      <svg viewBox="0 0 24 24" stroke="#0d9e70"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span><?php echo htmlspecialchars($success); ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm" novalidate>
      <input type="hidden" name="action" value="send_magic_link">

      <div class="form-group">
        <label class="form-label" for="loginEmail">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">
            <!-- Mail icon (SVG) -->
            <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </span>
          <input
            type="email" name="email" id="loginEmail"
            placeholder="surname_firstname"
            value="<?php echo $post_email; ?>"
            autocomplete="email" required>
          <span class="domain-badge">@plpasig.edu.ph</span>
        </div>
        <span class="email-hint">Format: <strong>surname_firstname</strong>@plpasig.edu.ph</span>
      </div>

      <button type="submit" class="btn-primary" id="loginBtn">
        <span class="btn-spinner" id="loginSpinner"></span>
        <span id="loginBtnText">Send Magic Link</span>
        <!-- Arrow icon (SVG) -->
        <svg class="arrow-icon" id="loginArrow" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <div class="info-box">
      <!-- Lock icon (SVG) -->
      <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      <span><strong>No password needed.</strong> We'll email you a secure link that signs you in instantly. Links expire in <strong>15 minutes</strong>.</span>
    </div>

    <div class="step-dots">
      <div class="step-dot active"></div>
      <div class="step-dot"></div>
      <div class="step-dot"></div>
    </div>

    <div class="form-footer">
      Don't have an account? <a href="mailto:duran_lemuel@plpasig.edu.ph">Contact your administrator</a><br>
    </div>
  </div>
</div>

<script>
// ── Dark / Light toggle ──
// The `data-theme` attribute is already set before paint (see <head> script).
// Here we just sync the button UI on load and handle the toggle.

function applyTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  const txt  = document.getElementById('themeTxt');
  const icon = document.getElementById('themeIcon');
  if (t === 'dark') {
    txt.textContent = 'Light';
    icon.innerHTML  = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
  } else {
    txt.textContent = 'Dark';
    icon.innerHTML  = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
  }
  localStorage.setItem('lf_theme', t);
}

function toggleTheme() {
  applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
}

// Sync button UI with whatever was set before paint
applyTheme(document.documentElement.getAttribute('data-theme') || 'light');

// ── Flow lines ──
(function() {
  const c = document.getElementById('flowLines');
  for (let i = 0; i < 8; i++) {
    const l = document.createElement('div');
    l.className = 'flow-line';
    l.style.cssText = `left:${5+(i/8)*90}%;height:${80+Math.random()*180}px;animation-duration:${5+Math.random()*8}s;animation-delay:${-Math.random()*13}s;opacity:${0.2+Math.random()*0.4};`;
    c.appendChild(l);
  }
})();

// ── Toast ──
function showToast(msg, ok) {
  const t = document.getElementById('mainToast');
  t.textContent = msg;
  t.className = 'toast' + (ok ? ' success' : '') + ' show';
  setTimeout(() => t.classList.remove('show'), 4500);
}

// ── Form ──
document.getElementById('loginForm').addEventListener('submit', function(e) {
  const inp = document.getElementById('loginEmail');
  let email = inp.value.trim();
  if (email && !email.includes('@')) { email = email + '@plpasig.edu.ph'; inp.value = email; }
  if (!email.endsWith('@plpasig.edu.ph')) {
    e.preventDefault();
    showToast('Please use a @plpasig.edu.ph email address');
    inp.classList.add('invalid'); return;
  }
  inp.classList.remove('invalid');
  localStorage.setItem('lf_last_email', email);
  document.getElementById('loginSpinner').style.display = 'block';
  document.getElementById('loginBtnText').textContent   = 'Sending…';
  document.getElementById('loginArrow').style.display   = 'none';
  document.getElementById('loginBtn').disabled          = true;
  const dots = document.querySelectorAll('.step-dot');
  dots[0].classList.remove('active'); dots[1].classList.add('active');
});

document.getElementById('loginEmail').addEventListener('input', function() {
  if (!this.value) { this.classList.remove('invalid'); return; }
  const full = this.value.includes('@') ? this.value : this.value + '@plpasig.edu.ph';
  this.classList.toggle('invalid', !full.endsWith('@plpasig.edu.ph'));
});

// Pre-fill
const saved = localStorage.getItem('lf_last_email');
const el    = document.getElementById('loginEmail');
if (saved?.endsWith('@plpasig.edu.ph') && !el.value) el.value = saved;
</script>
</body>
</html>