<?php
/**
 * LearnFlow LMS - Send Magic Link
 * POST /send-magic-link.php   body: email=user@plpasig.edu.ph
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

$email = trim($_POST['email'] ?? $_GET['email'] ?? '');

if (empty($email)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Email is required']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid email address']));
}

// Create token (also looks up the user)
$token_result = create_magic_link_token($conn, $email);

if (!$token_result['success']) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => $token_result['message']]));
}

// Get display name
$user = getUserByEmail($email);
$user_name = $user['display_name']
    ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
    ?: $email;

// Send e-mail (function defined in config/mail.php)
send_magic_link_email($email, $token_result['token'], $user_name);

http_response_code(200);
die(json_encode([
    'success' => true,
    'message' => 'Magic link sent to your email',
    'email'   => $email,
]));