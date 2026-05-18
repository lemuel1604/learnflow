<?php
/**
 * LearnFlow LMS — Mail Configuration
 */

ini_set('SMTP', 'localhost');
ini_set('smtp_port', 1025);

define('MAIL_FROM',      'noreply@plpasig.edu.ph');
define('MAIL_FROM_NAME', 'LearnFlow LMS');
define('APP_BASE_URL',   'http://localhost/LearnFlowCaseStudy/LMS%20-%20updated/LMS-PHP%20(UPDATED)');

/**
 * Send a magic-link e-mail to the given address.
 */
function send_magic_link_email(string $to, string $token, string $user_name): bool
{
    $link    = APP_BASE_URL . '/verify-magic-link.php?token=' . urlencode($token);
    $subject = 'Your LearnFlow Magic Link';

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:'DM Sans',Arial,sans-serif;background:#FDF0F5;margin:0;padding:32px">
  <div style="max-width:480px;margin:auto;background:#fff;border-radius:16px;padding:40px;
              border:1px solid #F0C0D8;box-shadow:0 4px 24px rgba(204,58,114,.18)">
    <div style="font-family:Syne,Arial,sans-serif;font-size:22px;font-weight:800;
                color:#2a0e1c;margin-bottom:8px">
      Learn<span style="color:#CC3A72">Flow</span>
    </div>
    <h2 style="color:#CC3A72;font-size:20px;margin:0 0 16px">Magic Sign-In Link</h2>
    <p style="color:#7a3a58;margin:0 0 24px">
      Hi <strong>{$user_name}</strong>,<br><br>
      Click the button below to sign in to your LearnFlow account.
      This link is valid for <strong>15 minutes</strong> and can only be used once.
    </p>
    <a href="{$link}"
       style="display:inline-block;background:linear-gradient(135deg,#CC3A72,#a82860);
              color:#fff;padding:14px 28px;border-radius:10px;text-decoration:none;
              font-weight:700;font-size:15px;letter-spacing:.3px">
      Sign In to LearnFlow →
    </a>
    <p style="color:#c090a8;font-size:12px;margin:24px 0 0">
      If you didn't request this, you can safely ignore this email.<br>
      Magic link: <code>{$link}</code>
    </p>
  </div>
</body>
</html>
HTML;

    $plain = "Hi {$user_name},\n\nClick the link below to sign in:\n{$link}\n\nThis link expires in 15 minutes.\n\nLearnFlow LMS";

    $boundary = md5(uniqid());
    $headers  = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
        'X-Mailer: LearnFlow/1.0',
    ]);

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n";
    $body .= "--{$boundary}--";

    return mail($to, $subject, $body, $headers);
}
/**
 * Send a welcome / account-created e-mail to a newly added user.
 * Includes a one-time magic link so they can sign in immediately.
 */
function send_welcome_email(string $to, string $token, string $user_name, string $role = 'student'): bool
{
    $link       = APP_BASE_URL . '/verify-magic-link.php?token=' . urlencode($token);
    $subject    = 'Welcome to LearnFlow — Your account is ready';
    $role_label = ucfirst($role);
    $role_color = match($role) {
        'instructor' => '#1a6fbf',
        'admin'      => '#7c3aed',
        default      => '#CC3A72',
    };

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:'DM Sans',Arial,sans-serif;background:#FDF0F5;margin:0;padding:32px">
  <div style="max-width:500px;margin:auto;background:#fff;border-radius:16px;padding:40px;
              border:1px solid #F0C0D8;box-shadow:0 4px 24px rgba(204,58,114,.15)">

    <div style="font-family:Syne,Arial,sans-serif;font-size:22px;font-weight:800;
                color:#2a0e1c;margin-bottom:4px">
      Learn<span style="color:#CC3A72">Flow</span>
    </div>
    <div style="font-size:12px;color:#c090a8;margin-bottom:28px">Pamantasan ng Lungsod ng Pasig</div>

    <h2 style="color:#2a0e1c;font-size:20px;margin:0 0 6px">Your account is ready!</h2>
    <p style="color:#7a3a58;font-size:14px;margin:0 0 20px">
      Hi <strong>{$user_name}</strong>,<br><br>
      An administrator has created a <strong>LearnFlow LMS</strong> account for you.
      You can now access the platform using the button below.
    </p>

    <div style="background:#FDF0F5;border-radius:10px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#5a2a40">
      <div style="margin-bottom:4px"><strong>Account Email:</strong> {$to}</div>
      <div><strong>Role:</strong>
        <span style="background:{$role_color};color:#fff;padding:2px 10px;border-radius:10px;
                     font-size:11px;font-weight:700;margin-left:4px">{$role_label}</span>
      </div>
    </div>

    <p style="color:#7a3a58;font-size:13px;margin:0 0 18px">
      Click the button below to sign in. This link is valid for
      <strong>15 minutes</strong> and can only be used once.
    </p>

    <a href="{$link}"
       style="display:inline-block;background:linear-gradient(135deg,#CC3A72,#a82860);
              color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;
              font-weight:700;font-size:15px;letter-spacing:.3px">
      Sign In to LearnFlow &#8594;
    </a>

    <p style="color:#c090a8;font-size:11px;margin:28px 0 0;border-top:1px solid #F0C0D8;padding-top:16px">
      If you did not expect this email, please contact your administrator.<br>
      Direct link: <code style="word-break:break-all">{$link}</code>
    </p>
  </div>
</body>
</html>
HTML;

    $plain  = "Welcome to LearnFlow LMS!\n\n";
    $plain .= "Hi {$user_name},\n\n";
    $plain .= "An administrator has created a LearnFlow account for you.\n";
    $plain .= "Email : {$to}\nRole  : {$role_label}\n\n";
    $plain .= "Sign in here (link valid for 15 minutes):\n{$link}\n\n";
    $plain .= "If you did not expect this, contact your administrator.\n\nLearnFlow LMS";

    $boundary = md5(uniqid());
    $headers  = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
        'X-Mailer: LearnFlow/1.0',
    ]);

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n";
    $body .= "--{$boundary}--";

    return mail($to, $subject, $body, $headers);
}
