<?php
/**
 * LearnFlow LMS - Verify Magic Link
 * GET /verify-magic-link.php?token=xxxxx
 *
 * Verifies token and creates session, then redirects to login page.
 * Login page will auto-detect session and show dashboard.
 */

session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/auth.php';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error = 'No token provided. Please request a new magic link.';
    $success = false;
} else {
    $verification = verify_magic_link_token($conn, $token);

    if (!$verification['success']) {
        $error = $verification['message'];
        $success = false;
    } else {
        // ── Valid token: create session ──────
        create_user_session($verification['user']);
        
        // Get user's dashboard
        $dashboard_map = [
            'admin'      => 'learnflow-admin.php',
            'instructor' => 'learnflow-instructor.php',
            'student'    => 'learnflow-student.php',
        ];
        
        $role = $verification['user']['role'] ?? 'student';
        $dashboard = $dashboard_map[$role] ?? 'learnflow-student.php';
        
        // Success - redirect directly to dashboard
        header('Location: ' . $dashboard);
        exit;
    }
}

// ── Error page ────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnFlow – Verification Failed</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --primary: #CC3A72;
    --primary-dark: #a82860;
    --bg: #FDF0F5;
    --surface: #FFFFFF;
    --surface-2: #F8E4EF;
    --border: #F0C0D8;
    --text: #2a0e1c;
    --text-2: #7a3a58;
    --danger: #D84040;
    --shadow-md: 0 4px 24px rgba(204,58,114,0.18);
  }
  *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: radial-gradient(ellipse at 12% 88%, rgba(204,58,114,.18) 0%, transparent 50%),
                radial-gradient(ellipse at 88% 12%, rgba(74,174,232,.13) 0%, transparent 50%),
                var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 32px 20px;
  }
  .card {
    background: var(--surface);
    border-radius: 24px;
    padding: 48px 40px;
    width: 100%; max-width: 440px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
    text-align: center;
    animation: fadeUp .45s ease;
  }
  @keyframes fadeUp {
    from { opacity:0; transform:translateY(22px); }
    to   { opacity:1; transform:translateY(0); }
  }
  .brand {
    font-family: 'Syne', sans-serif;
    font-size: 20px; font-weight: 800;
    color: var(--text); margin-bottom: 28px;
  }
  .brand span { color: var(--primary); }
  .icon { font-size: 58px; margin-bottom: 18px; }
  .title {
    font-family: 'Syne', sans-serif;
    font-size: 21px; font-weight: 700; margin-bottom: 10px;
  }
  .subtitle {
    color: var(--text-2); font-size: 14px;
    line-height: 1.6; margin-bottom: 20px;
  }
  .error-detail {
    background: var(--surface-2);
    border-left: 4px solid var(--danger);
    padding: 12px 14px; border-radius: 6px;
    font-size: 13px; color: var(--text-2);
    margin-bottom: 20px; text-align: left;
  }
  .hint {
    font-size: 13px; color: var(--text-2);
    line-height: 1.65; margin-bottom: 24px;
  }
  .btn {
    display: inline-flex; align-items: center;
    justify-content: center; width: 100%;
    padding: 13px 18px; border-radius: 8px;
    font-size: 14px; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; font-family: 'DM Sans', sans-serif;
    box-shadow: 0 4px 14px rgba(204,58,114,.35);
    transition: transform .2s, box-shadow .2s;
  }
  .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(204,58,114,.44); }
</style>
</head>
<body>

<div class="card">
  <div class="brand">Learn<span>Flow</span></div>
  <div class="icon">❌</div>
  <h1 class="title">Link Verification Failed</h1>
  <p class="subtitle">We couldn't sign you in. Here's what went wrong:</p>

  <div class="error-detail">
    <?php echo htmlspecialchars($error ?? 'Unknown error occurred.'); ?>
  </div>

  <p class="hint">
    Magic links expire after <strong>15 minutes</strong> and can only be used <strong>once</strong>.<br>
    Request a fresh link from the login page.
  </p>

  <a href="learnflow-login.php" class="btn">← Back to Login</a>
</div>

</body>
</html>
<?php exit; ?>