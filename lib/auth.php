<?php
/**
 * LearnFlow LMS — Auth Library  (lib/auth.php)
 *
 * Functions
 * ─────────
 *  generateToken()               → 64-char hex token
 *  hashToken(string $token)      → SHA-256 hex digest
 *  getUserByEmail(string $email) → user row or null
 *  saveAuthToken(string $email, string $token_hash, string $expires_at) → bool
 *  create_magic_link_token($conn, string $email)  → ['success'=>bool, ...]
 *  verify_magic_link_token($conn, string $token)  → ['success'=>bool, 'user'=>[...]]
 *  create_user_session(array $user)               → void  (sets $_SESSION)
 *  register_user($conn, array $data)              → ['success'=>bool, 'message'=>string]
 */

// ── Ensure $conn is available (included after config/db.php) ─────────────────
if (!isset($conn)) {
    require_once __DIR__ . '/../config/db.php';
}

// ─────────────────────────────────────────────────────────────────────────────
// Token helpers
// ─────────────────────────────────────────────────────────────────────────────

function generateToken(): string
{
    return bin2hex(random_bytes(32));   // 64 hex chars
}

function hashToken(string $token): string
{
    return hash('sha256', $token);
}

// ─────────────────────────────────────────────────────────────────────────────
// User lookup
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Fetch a user row (with profile) by e-mail.
 * Returns associative array or null if not found.
 */
function getUserByEmail(string $email): ?array
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT  u.id,
                u.email,
                u.role,
                u.status,
                u.email_verified,
                u.theme_pref,
                up.first_name,
                up.last_name,
                up.display_name
        FROM    users u
        LEFT JOIN user_profiles up ON up.user_id = u.id
        WHERE   u.email = ?
        LIMIT 1
    ");
    if (!$stmt) return null;

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

// ─────────────────────────────────────────────────────────────────────────────
// Token persistence (learnflow-login.php path)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Insert a magic-link token row.
 * token_type is 'password_reset' (the closest ENUM value we can reuse for
 * magic-link sign-in).  The `login_email` column stores the target address
 * so verify-magic-link.php can look up the user without a user_id beforehand.
 *
 * @param string $email      Recipient / lookup e-mail
 * @param string $token_hash SHA-256 of the raw token
 * @param string $expires_at 'Y-m-d H:i:s'
 */
function saveAuthToken(string $email, string $token_hash, string $expires_at): bool
{
    global $conn;

    // Look up the user_id — required by the FK
    $user = getUserByEmail($email);
    if (!$user) return false;

    $user_id    = $user['id'];
    $token_type = 'password_reset';   // reused as magic-link slot

    // Invalidate any previous unused tokens for this user (cleanup)
    $del = $conn->prepare("
        DELETE FROM auth_tokens
        WHERE  user_id    = ?
          AND  token_type = 'password_reset'
          AND  used_at    IS NULL
    ");
    if ($del) {
        $del->bind_param('i', $user_id);
        $del->execute();
        $del->close();
    }

    // Insert the new token, storing the e-mail in login_email
    $stmt = $conn->prepare("
        INSERT INTO auth_tokens (user_id, token_hash, token_type, expires_at, login_email)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) return false;

    $stmt->bind_param('issss', $user_id, $token_hash, $token_type, $expires_at, $email);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

// ─────────────────────────────────────────────────────────────────────────────
// Token creation (send-magic-link.php path)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Create a 15-minute magic-link token for the given e-mail.
 * Returns ['success'=>true, 'token'=>'<raw>', 'user_id'=>int]
 *      or ['success'=>false, 'message'=>'...']
 */
function create_magic_link_token(mysqli $conn, string $email): array
{
    $user = getUserByEmail($email);

    if (!$user) {
        return ['success' => false, 'message' => 'No account found for this e-mail address.'];
    }

    if ($user['status'] === 'suspended') {
        return ['success' => false, 'message' => 'This account has been suspended.'];
    }

    $token      = generateToken();
    $token_hash = hashToken($token);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $user_id    = $user['id'];
    $token_type = 'password_reset';   // reused slot

    // Clean up old unused tokens
    $del = $conn->prepare("
        DELETE FROM auth_tokens
        WHERE  user_id    = ?
          AND  token_type = 'password_reset'
          AND  used_at    IS NULL
    ");
    if ($del) {
        $del->bind_param('i', $user_id);
        $del->execute();
        $del->close();
    }

    $stmt = $conn->prepare("
        INSERT INTO auth_tokens (user_id, token_hash, token_type, expires_at, login_email)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error while creating token.'];
    }

    $stmt->bind_param('issss', $user_id, $token_hash, $token_type, $expires_at, $email);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'message' => 'Failed to save token.'];
    }

    return ['success' => true, 'token' => $token, 'user_id' => $user_id];
}

// ─────────────────────────────────────────────────────────────────────────────
// Token verification
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Verify a raw magic-link token.
 * On success marks it used, updates last_login_at, and returns full user row.
 */
function verify_magic_link_token(mysqli $conn, string $raw_token): array
{
    $token_hash = hashToken($raw_token);

    $stmt = $conn->prepare("
        SELECT  at.id        AS token_id,
                at.user_id,
                at.used_at,
                at.expires_at,
                at.login_email,
                u.email,
                u.role,
                u.status,
                u.email_verified,
                u.theme_pref,
                up.first_name,
                up.last_name,
                up.display_name
        FROM    auth_tokens at
        JOIN    users        u  ON u.id       = at.user_id
        LEFT JOIN user_profiles up ON up.user_id = at.user_id
        WHERE   at.token_hash = ?
          AND   at.token_type = 'password_reset'
        LIMIT 1
    ");

    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error.'];
    }

    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['success' => false, 'message' => 'Invalid or unrecognised link.'];
    }

    if ($row['used_at'] !== null) {
        return ['success' => false, 'message' => 'This link has already been used.'];
    }

    if (strtotime($row['expires_at']) < time()) {
        return ['success' => false, 'message' => 'This link has expired. Please request a new one.'];
    }

    if ($row['status'] === 'suspended') {
        return ['success' => false, 'message' => 'This account has been suspended.'];
    }

    // Mark token as used
    $upd = $conn->prepare("UPDATE auth_tokens SET used_at = NOW() WHERE id = ?");
    if ($upd) {
        $upd->bind_param('i', $row['token_id']);
        $upd->execute();
        $upd->close();
    }

    // Activate account if still pending (first magic-link login = email verified)
    if ($row['status'] === 'pending' || !$row['email_verified']) {
        $act = $conn->prepare("
            UPDATE users SET email_verified = 1, status = 'active', last_login_at = NOW()
            WHERE  id = ?
        ");
    } else {
        $act = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    }

    if ($act) {
        $act->bind_param('i', $row['user_id']);
        $act->execute();
        $act->close();
    }

    // Audit log
    $ip    = $_SERVER['REMOTE_ADDR']        ?? '0.0.0.0';
    $agent = $_SERVER['HTTP_USER_AGENT']    ?? '';
    $log   = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent)
        VALUES (?, 'magic_link_login', 'users', ?, ?, ?)
    ");
    if ($log) {
        $log->bind_param('iiss', $row['user_id'], $row['user_id'], $ip, $agent);
        $log->execute();
        $log->close();
    }

    return ['success' => true, 'user' => $row];
}

// ─────────────────────────────────────────────────────────────────────────────
// Session management
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Populate $_SESSION from a verified user row.
 * Dashboard pages check: $_SESSION['user'] and $_SESSION['role'].
 */
function create_user_session(array $user): void
{
    // Regenerate session ID to prevent fixation
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $display = $user['display_name']
        ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
        ?: $user['email'];

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['user']      = $display;
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['theme']     = $user['theme_pref'] ?? 'system';

    // Role-specific extras (loaded later by dashboards, but handy to have here)
    $_SESSION['first_name'] = $user['first_name'] ?? '';
    $_SESSION['last_name']  = $user['last_name']  ?? '';

    // Sync theme cookie so PHP dashboards can read it server-side
    setcookie('theme', $_SESSION['theme'], time() + 60 * 60 * 24 * 365, '/');
}

// ─────────────────────────────────────────────────────────────────────────────
// Account registration
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Register a new user via the stored procedure sp_register_user.
 * Password is hashed with bcrypt before calling the SP.
 *
 * @param mysqli $conn
 * @param array  $data  ['email','password','role','first_name','last_name']
 * @return array        ['success'=>bool, 'message'=>string, 'user_id'=>int|null]
 */
function register_user(mysqli $conn, array $data): array
{
    $email      = trim($data['email']      ?? '');
    $password   = trim($data['password']   ?? '');
    $role       = trim($data['role']       ?? 'student');
    $first_name = trim($data['first_name'] ?? '');
    $last_name  = trim($data['last_name']  ?? '');

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid e-mail address.', 'user_id' => null];
    }
    if (!in_array($role, ['admin', 'instructor', 'student'], true)) {
        $role = 'student';
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.', 'user_id' => null];
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Call the stored procedure
    $stmt = $conn->prepare("CALL sp_register_user(?, ?, ?, ?, ?, @p_user_id, @p_message)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error, 'user_id' => null];
    }

    $stmt->bind_param('sssss', $email, $password_hash, $role, $first_name, $last_name);
    $stmt->execute();
    $stmt->close();

    // Fetch OUT params
    $out = $conn->query("SELECT @p_user_id AS user_id, @p_message AS message")->fetch_assoc();

    $user_id = $out['user_id'] ? (int) $out['user_id'] : null;
    $message = $out['message'] ?? 'Unknown error.';

    return [
        'success' => $user_id !== null,
        'message' => $message,
        'user_id' => $user_id,
    ];
}