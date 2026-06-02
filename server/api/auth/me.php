<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$user = getCurrentUser();
if ($user === null) {
    errorResponse('尚未登入', 401);
}

// Re-fetch from DB to get fresh data
$db = getDB();
$stmt = $db->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$freshUser = $stmt->fetch();

if (!$freshUser) {
    // Session user no longer exists in DB
    session_destroy();
    errorResponse('使用者不存在', 401);
}

$freshUser['id'] = (int) $freshUser['id'];
successResponse('取得使用者資訊成功', ['user' => $freshUser]);
