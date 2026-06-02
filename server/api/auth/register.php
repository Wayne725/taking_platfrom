<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

$body = getJsonBody();

$username = trim($body['username'] ?? '');
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

// Validation
if ($username === '' || $email === '' || $password === '') {
    errorResponse('請填寫所有必填欄位');
}

if (mb_strlen($username) < 3) {
    errorResponse('用戶名稱至少需要 3 個字元');
}
if (mb_strlen($username) > MAX_USERNAME_LEN) {
    errorResponse('用戶名稱不可超過 ' . MAX_USERNAME_LEN . ' 個字元');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorResponse('Email 格式不正確');
}
if (strlen($email) > MAX_EMAIL_LEN) {
    errorResponse('Email 過長');
}

if (mb_strlen($password) < 6) {
    errorResponse('密碼至少需要 6 個字元');
}
if (mb_strlen($password) > MAX_PASSWORD_LEN) {
    errorResponse('密碼不可超過 ' . MAX_PASSWORD_LEN . ' 個字元');
}

$db = getDB();

// Check duplicate email
$stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    errorResponse('此 Email 已被使用');
}

// Check duplicate username
$stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    errorResponse('此用戶名稱已被使用');
}

// Insert user
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare(
    'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
);
$stmt->execute([$username, $email, $passwordHash]);

$newId = (int) $db->lastInsertId();

successResponse('註冊成功', [
    'user' => [
        'id'       => $newId,
        'username' => $username,
        'email'    => $email,
    ]
], 201);
