<?php

const MAX_POST_CONTENT_LEN = 1000;
const MAX_POST_IMAGE_BYTES = 2097152; // 2MB
const MAX_AVATAR_IMAGE_BYTES = 2097152; // 2MB

function normalizeAvatarUrl(mixed $value): ?string {
    $url = trim((string) ($value ?? ''));
    if ($url === '') {
        return null;
    }

    if (mb_strlen($url) > 2048) {
        errorResponse('頭貼網址不可超過 2048 字元');
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        errorResponse('請輸入有效的頭貼圖片網址');
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        errorResponse('頭貼網址僅支援 http 或 https');
    }

    return $url;
}

function validateAvatarImageData(mixed $value): ?string {
    $imageData = trim((string) ($value ?? ''));
    if ($imageData === '') {
        return null;
    }

    if (!preg_match('/^data:image\/(jpeg|png|webp);base64,([a-zA-Z0-9+\/=\r\n]+)$/', $imageData, $matches)) {
        errorResponse('頭貼圖片僅支援 jpeg、png 或 webp');
    }

    $decoded = base64_decode($matches[2], true);
    if ($decoded === false) {
        errorResponse('頭貼圖片格式錯誤');
    }

    if (strlen($decoded) > MAX_AVATAR_IMAGE_BYTES) {
        errorResponse('頭貼圖片不可超過 2MB');
    }

    return $imageData;
}

function validatePostInput(array $body): array {
    $content = trim((string) ($body['content'] ?? ''));
    $imageData = trim((string) ($body['image_data'] ?? ''));

    if (mb_strlen($content) > MAX_POST_CONTENT_LEN) {
        errorResponse('貼文內容不可超過 1000 字');
    }

    if ($imageData !== '') {
        $imageData = validatePostImageData($imageData);
    } else {
        $imageData = null;
    }

    if ($content === '' && $imageData === null) {
        errorResponse('貼文內容或圖片至少需要一項');
    }

    return [$content === '' ? null : $content, $imageData];
}

function validatePostImageData(string $imageData): string {
    if (!preg_match('/^data:image\/(jpeg|png|webp);base64,([a-zA-Z0-9+\/=\r\n]+)$/', $imageData, $matches)) {
        errorResponse('貼文圖片僅支援 jpeg、png 或 webp');
    }

    $decoded = base64_decode($matches[2], true);
    if ($decoded === false) {
        errorResponse('貼文圖片格式錯誤');
    }

    if (strlen($decoded) > MAX_POST_IMAGE_BYTES) {
        errorResponse('貼文圖片不可超過 2MB');
    }

    return $imageData;
}

function fetchUserPosts(PDO $db, int $userId, int $limit = 20): array {
    $limit = max(1, min(50, $limit));

    $stmt = $db->prepare(
        'SELECT id, user_id, content, image_data, created_at, updated_at
         FROM user_posts
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $posts = $stmt->fetchAll();
    foreach ($posts as &$post) {
        $post['id'] = (int) $post['id'];
        $post['user_id'] = (int) $post['user_id'];
    }
    unset($post);

    return $posts;
}

function fetchUserPost(PDO $db, int $postId, int $userId): ?array {
    $stmt = $db->prepare(
        'SELECT id, user_id, content, image_data, created_at, updated_at
         FROM user_posts
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$postId, $userId]);
    $post = $stmt->fetch();

    if (!$post) {
        return null;
    }

    $post['id'] = (int) $post['id'];
    $post['user_id'] = (int) $post['user_id'];

    return $post;
}
