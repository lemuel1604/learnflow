# LearnFlow LMS - Comprehensive Security Audit

---

## 1. AUTHENTICATION & SESSION MANAGEMENT

### ✅ Implemented

#### **Magic Link Authentication** (Zero-Password Login)
- **Token Generation**: Uses `random_bytes(32)` → 64-character hex tokens
- **Token Hashing**: SHA-256 hash stored in database (not plaintext)
- **Token Expiration**: 15-minute validity with automatic cleanup
- **One-Time Use**: Tokens marked `used_at` after verification
- **Email Validation**: 
  - Domain restriction: `@plpasig.edu.ph` only
  - Format validation with `filter_var()`
- **Account Status Checks**: Suspended accounts are rejected
- **Location**: [lib/auth.php](lib/auth.php) | [learnflow-login.php](learnflow-login.php) | [verify-magic-link.php](verify-magic-link.php)

#### **Session Management**
```php
session_start();  // Secure session initialization
```
- Session variables: `$_SESSION['user_id']`, `$_SESSION['role']`
- Proper session destruction on logout
- Theme cookie cleared on logout

#### **Role-Based Access Control (RBAC)**
- **Admin**: Full system access
- **Instructor**: Course management only
- **Student**: Student portal only
- Enforced on every page with auth guard:
```php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: learnflow-login.php');
    exit;
}
```
- **Location**: [learnflow-admin.php](learnflow-admin.php#L5) | [learnflow-student.php](learnflow-student.php#L14) | [learnflow-instructor.php](learnflow-instructor.php#L18)

---

## 2. DATABASE SECURITY

### ✅ Implemented

#### **SQL Injection Prevention**
- **Prepared Statements**: 100% coverage across all files
- **Parameter Binding**: Type-safe binding (`i`, `s`, `ss`, etc.)

Examples:
```php
// ❌ NEVER like this - all files use prepared statements
// $sql = "SELECT * FROM users WHERE email = '$email'";

// ✅ Always like this
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
```

#### **Database Connection**
- **Location**: [config/db.php](config/db.php)
- UTF-8 charset enforced: `$conn->set_charset('utf8mb4')`
- Error handling for connection failures
- 503 Service Unavailable on DB failure

#### **Connection Pool**
- Uses MySQLi (object-oriented)
- Persistent connection (reused across requests)

### ⚠️ Security Gaps

**Hardcoded Credentials** (CRITICAL - Development Environment)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // 👈 No password!
define('DB_PASS', '');
define('DB_NAME', 'learnflow_db');
```

**Recommendations**:
- Use environment variables: `$_ENV['DB_USER']`
- Or `.env` file (with `.env.example` in repo)
- Different credentials for production

---

## 3. INPUT VALIDATION & SANITIZATION

### ✅ Implemented

#### **Email Validation**
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid email address']));
}
// Also domain restriction: @plpasig.edu.ph
if (!str_ends_with($email, '@plpasig.edu.ph')) {
    $error = 'Please use a valid @plpasig.edu.ph email address.';
}
```

#### **Type Casting**
```php
$id = (int) ($_GET['id'] ?? 0);           // Cast to int
$admin_id = (int) $_SESSION['user_id'];   // Int from session
```

#### **Whitelist Validation**
```php
$allowed = ['normal','important','urgent'];
$priority = in_array($priority, $allowed) ? $priority : 'normal';
```

#### **String Trimming & Null Safety**
```php
$email = trim($_POST['email'] ?? '');
$title = trim($_POST['title'] ?? '');
```

#### **Date/Time Validation**
```php
$ts = strtotime($publish_at);
if ($ts === false) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid schedule date']));
}
$pub_dt = date('Y-m-d H:i:s', $ts);
```

#### **Numeric Range Checks**
```php
if ($id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid ID']));
}
```

---

## 4. OUTPUT ENCODING & XSS PREVENTION

### ✅ Implemented

#### **JSON Content-Type Headers**
```php
header('Content-Type: application/json');  // All API files
header('Content-Type: text/event-stream'); // SSE file
```

#### **HTML Escaping**
```php
'message' => 'Unknown action: '.htmlspecialchars($action)
```

#### **URL Encoding**
```php
$link = APP_BASE_URL . '/verify-magic-link.php?token=' . urlencode($token);
```

#### **Safe Variable Output**
```php
$post_email = htmlspecialchars($_POST['email'] ?? '');  // Login page
```

### ⚠️ Security Gaps

**No Content Security Policy (CSP) Headers**
- Missing headers like:
  ```php
  header("Content-Security-Policy: default-src 'self'; script-src 'self'");
  ```

---

## 5. API SECURITY

### ✅ Implemented

#### **Authentication Guards** (api-announcements.php)
```php
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Forbidden']));
}
```

#### **HTTP Method Validation**
```php
if ($method !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}
```

#### **Proper HTTP Status Codes**
- 200 OK - Success
- 400 Bad Request - Invalid input
- 403 Forbidden - Authorization failed
- 404 Not Found - Resource not found
- 405 Method Not Allowed
- 500 Internal Server Error

#### **Cascading Deletes** (Data Integrity)
When deleting an announcement:
```php
// 1. Delete targets
DELETE FROM announcement_targets WHERE announcement_id = ?
// 2. Delete reads
DELETE FROM announcement_reads WHERE announcement_id = ?
// 3. Delete notifications
DELETE FROM notifications WHERE related_type = 'announcement' AND related_id = ?
// 4. Delete announcement
DELETE FROM announcements WHERE id = ?
```

### ⚠️ Security Gaps

**CSRF Protection Missing**
- No CSRF tokens on POST requests
- Should use tokens like: `$_SESSION['csrf_token']`
- Example fix:
  ```php
  // Generate token
  if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  
  // Validate on POST
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      http_response_code(403);
      die('CSRF token mismatch');
  }
  ```

---

## 6. MAIL SECURITY

### ✅ Implemented

#### **Safe Email Template Construction**
- **Location**: [config/mail.php](config/mail.php)
- HTML emails with proper MIME boundaries
- Plain text alternative included
- Email headers properly formatted

#### **URL Encoding in Emails**
```php
$link = APP_BASE_URL . '/verify-magic-link.php?token=' . urlencode($token);
```

#### **Display Name Safety**
```php
$user_name = $user['display_name'] ?? 
    trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $email;
```

### ⚠️ Security Gaps

**SMTP Over Plain HTTP**
```php
ini_set('SMTP', 'localhost');
ini_set('smtp_port', 1025);  // Mailpit (dev environment)
```
**Production Fix**: Use TLS/SSL SMTP
```php
// Use PHPMailer or SwiftMailer
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
```

---

## 7. LOGGING & MONITORING

### ❌ NOT Implemented

**Missing Audit Logs**:
- No logging of: login attempts, admin actions, announcements created/deleted
- No failed login tracking
- No IP address logging
- No rate limiting

**Recommendation**:
```php
function log_action($action, $user_id, $details) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (action, user_id, ip_address, details, created_at) 
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param('siss', $action, $user_id, $ip, $details);
    $stmt->execute();
}
```

---

## 8. CONFIGURATION & SECRETS

### ⚠️ Critical Issues

#### **Hardcoded Database Credentials**
```php
// ❌ NEVER in production
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
```

#### **Hardcoded Mail Configuration**
```php
define('MAIL_FROM',      'noreply@plpasig.edu.ph');
define('APP_BASE_URL',   'http://localhost/...');  // Development URL
```

#### **Missing `.env` Support**
Create `.env.example`:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=learnflow_db
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=your-app-password
APP_BASE_URL=https://learnflow.plpasig.edu.ph
```

---

## 9. ENCRYPTION & SECURE TRANSMISSION

### ⚠️ Critical Issues

**No HTTPS/TLS**
- Credentials sent over plain HTTP in development
- Production must use HTTPS

```php
// Add this to all pages in production
if (empty($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

**No Password Encryption** (Using Magic Links Instead)
- ✅ Magic links avoid storing passwords
- Better security model than traditional password hashing
- Still requires HTTPS in production

**Token Storage**:
- Tokens hashed with SHA-256 ✅
- Hash stored in DB, not raw token ✅

---

## 10. SECURITY HEADERS MISSING

### ❌ Not Implemented

Add to all response headers:

```php
// Security headers
header('X-Content-Type-Options: nosniff');                    // Block MIME-sniffing
header('X-Frame-Options: SAMEORIGIN');                       // Prevent clickjacking
header('X-XSS-Protection: 1; mode=block');                   // Enable XSS filter
header('Referrer-Policy: strict-origin-when-cross-origin');  // Control referrer
header('Permissions-Policy: camera=(), microphone=()');      // Restrict browser APIs
header('Strict-Transport-Security: max-age=31536000');       // Force HTTPS (production)
header('Content-Security-Policy: default-src \'self\'');     // XSS protection
```

---

## 11. FILE UPLOAD SECURITY

### ⚠️ Unknown Status

**Avatar Uploads** mentioned in file structure:
```
uploads/
  avatars/
  modules/
```

**Recommendations** (if uploads are handled):
```php
// ✅ Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
    die('Invalid file type');
}

// ✅ Validate size
if ($_FILES['avatar']['size'] > 5242880) {  // 5MB
    die('File too large');
}

// ✅ Rename uploaded file
$ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
$safe_name = uniqid() . '.' . $ext;
move_uploaded_file($_FILES['avatar']['tmp_name'], "uploads/avatars/{$safe_name}");

// ✅ Never execute uploaded files
// Ensure upload directory has: php_flag engine off
```

---

## 12. SUMMARY TABLE

| Category | Status | Details |
|----------|--------|---------|
| **SQL Injection** | ✅ SAFE | Prepared statements everywhere |
| **XSS Prevention** | ⚠️ PARTIAL | Escaping on output, no CSP headers |
| **Authentication** | ✅ GOOD | Magic links, no passwords stored |
| **Authorization** | ✅ GOOD | RBAC on all portals |
| **CSRF Protection** | ❌ MISSING | No CSRF tokens |
| **HTTPS/TLS** | ❌ MISSING | Development only uses HTTP |
| **Hardcoded Secrets** | ❌ CRITICAL | DB credentials in code |
| **Audit Logging** | ❌ MISSING | No action logging |
| **Rate Limiting** | ❌ MISSING | No brute-force protection |
| **Security Headers** | ❌ MISSING | No CSP, X-Frame-Options, etc. |
| **Password Hashing** | ✅ N/A | Uses magic links instead |
| **Input Validation** | ✅ GOOD | Email, type casting, whitelisting |

---

## 13. PRIORITY FIXES FOR PRODUCTION

### 🔴 CRITICAL (Fix Immediately)

1. **Use environment variables for secrets**
   - Move DB credentials to `.env`
   - Use `$_ENV` or `getenv()`

2. **Enable HTTPS/TLS**
   - Get SSL certificate
   - Redirect HTTP to HTTPS
   - Add HSTS header

3. **Add CSRF tokens to all forms**
   - Generate on GET
   - Validate on POST

### 🟠 HIGH (Fix Before Launch)

4. **Add security headers**
   - CSP, X-Frame-Options, X-Content-Type-Options

5. **Implement rate limiting**
   - Limit login attempts
   - Prevent brute-force attacks

6. **Add audit logging**
   - Log all admin actions
   - Track login attempts
   - Monitor API usage

### 🟡 MEDIUM (Fix Soon)

7. **Add SMTP authentication**
   - Use TLS/SSL for email
   - Store SMTP credentials securely

8. **Implement file upload validation**
   - Type checking
   - Size limits
   - Quarantine uploaded files

---

## 14. POSITIVE SECURITY OBSERVATIONS

✅ **No SQL Injection Vulnerabilities** - Excellent use of prepared statements
✅ **Strong Token Generation** - Using cryptographically secure `random_bytes()`
✅ **Password-less Authentication** - Avoids storing/hashing passwords
✅ **Proper HTTP Methods** - GET/POST validation enforced
✅ **Email Domain Restriction** - Only allows `@plpasig.edu.ph`
✅ **Token Expiration** - 15-minute time window
✅ **One-Time Use Tokens** - Can't be reused
✅ **Account Status Checks** - Suspended accounts rejected
✅ **Type Safety** - ID parameters cast to int
✅ **Input Trimming** - All inputs trimmed before use

---

## 15. SECURITY CHECKLIST FOR DEPLOYMENT

- [ ] Move credentials to `.env` file
- [ ] Implement HTTPS/TLS
- [ ] Add CSRF tokens to all POST requests
- [ ] Add security headers (CSP, X-Frame-Options, etc.)
- [ ] Implement rate limiting on login
- [ ] Set up audit logging
- [ ] Configure SMTP with TLS
- [ ] Test file upload security (if applicable)
- [ ] Set database user permissions (least privilege)
- [ ] Enable PHP error logging (disable display_errors in production)
- [ ] Set session.secure = true for HTTPS
- [ ] Set session.httponly = true (prevent JavaScript access)
- [ ] Set session.samesite = 'Strict' (CSRF prevention)
- [ ] Configure firewall rules
- [ ] Regular security updates for dependencies

---

**Generated**: May 23, 2026  
**Project**: LearnFlow LMS  
**Assessment**: Development Phase → Production Readiness
