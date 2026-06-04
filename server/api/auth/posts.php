<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../post_helpers.php';

setCorsHeaders();
startAppSession();

$currentUser = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$userId = (int) $currentUser['id'];

if ($method === 'POST') {
    [$content, $imageData] = validatePostInput(getJsonBody());

    $stmt = $db->prepare(
        'INSERT INTO user_posts (user_id, content, image_data) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $content, $imageData]);

    $postId = (int) $db->lastInsertId();
    $post = fetchUserPost($db, $postId, $userId);

    successResponse('貼文已發布', ['post' => $post], 201);
}

if ($method === 'PUT') {
    $postId = (int) ($_GET['id'] ?? 0);
    if ($postId <= 0) {
        errorResponse('無效的貼文 ID', 400);
    }

    if (!fetchUserPost($db, $postId, $userId)) {
        errorResponse('找不到貼文或無權限編輯', 404);
    }

    [$content, $imageData] = validatePostInput(getJsonBody());

    $stmt = $db->prepare(
        'UPDATE user_posts
         SET content = ?, image_data = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$content, $imageData, $postId, $userId]);

    $post = fetchUserPost($db, $postId, $userId);

    successResponse('貼文已更新', ['post' => $post]);
}

if ($method === 'DELETE') {
    $postId = (int) ($_GET['id'] ?? 0);
    if ($postId <= 0) {
        errorResponse('無效的貼文 ID', 400);
    }

    if (!fetchUserPost($db, $postId, $userId)) {
        errorResponse('找不到貼文或無權限刪除', 404);
    }

    $stmt = $db->prepare('DELETE FROM user_posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$postId, $userId]);

    successResponse('貼文已刪除');
}

errorResponse('Method not allowed', 405);
