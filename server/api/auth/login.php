<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

$body = getJsonBody();

$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if ($email === '' || $password === '') {
    errorResponse('請填寫所有必填欄位');
}

$db = getDB();

// ── Rate limiting ──────────────────────────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Purge expired attempts
$db->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)')
   ->execute([RATE_LIMIT_WINDOW]);

$stmt = $db->prepare(
    'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)'
);
$stmt->execute([$ip, RATE_LIMIT_WINDOW]);
if ((int) $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS) {
    errorResponse('登入嘗試次數過多，請 15 分鐘後再試', 429);
}

// ── Credential check ───────────────────────────────────────────────────────────
$stmt = $db->prepare('SELECT id, username, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Record failed attempt
    $db->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
    errorResponse('Email 或密碼錯誤', 401);
}

// Regenerate session ID on login to prevent session fixation
session_regenerate_id(true);

$userData = [
    'id'       => (int) $user['id'],
    'username' => $user['username'],
    'email'    => $user['email'],
    'role'     => $user['role'],
];

$_SESSION['user'] = $userData;

successResponse('登入成功', ['user' => $userData]);
