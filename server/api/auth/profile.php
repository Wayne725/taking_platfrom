<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

$currentUser = requireAuth();
$method      = $_SERVER['REQUEST_METHOD'];
$db          = getDB();

// ── GET /api/auth/profile ──────────────────────────────────────────────────────
if ($method === 'GET') {
    // Full user row
    $stmt = $db->prepare('SELECT id, username, email, bio, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        errorResponse('使用者不存在', 404);
    }

    // Tasks posted as client
    $stmt = $db->prepare('SELECT COUNT(*) FROM tasks WHERE client_id = ?');
    $stmt->execute([$currentUser['id']]);
    $tasksPosted = (int) $stmt->fetchColumn();

    // Tasks completed as worker
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_worker_id = ? AND status = 'completed'");
    $stmt->execute([$currentUser['id']]);
    $tasksCompleted = (int) $stmt->fetchColumn();

    // Total income
    $stmt = $db->prepare("SELECT COALESCE(SUM(budget), 0) FROM tasks WHERE assigned_worker_id = ? AND status = 'completed'");
    $stmt->execute([$currentUser['id']]);
    $totalIncome = (float) $stmt->fetchColumn();

    // Rating stats
    $stmt = $db->prepare('SELECT COUNT(*), COALESCE(AVG(rating), 0) FROM task_reviews WHERE reviewee_id = ?');
    $stmt->execute([$currentUser['id']]);
    $ratingRow   = $stmt->fetch(PDO::FETCH_NUM);
    $reviewCount = (int)   $ratingRow[0];
    $avgRating   = round((float) $ratingRow[1], 1);

    // Reviews received
    $stmt = $db->prepare(
        'SELECT r.rating, r.comment, r.created_at,
                u.username AS reviewer_name,
                t.title    AS task_title,
                t.id       AS task_id
         FROM task_reviews r
         JOIN users u ON r.reviewer_id = u.id
         JOIN tasks t ON r.task_id     = t.id
         WHERE r.reviewee_id = ?
         ORDER BY r.created_at DESC
         LIMIT 20'
    );
    $stmt->execute([$currentUser['id']]);
    $reviews = $stmt->fetchAll();
    foreach ($reviews as &$rev) {
        $rev['rating']  = (int) $rev['rating'];
        $rev['task_id'] = (int) $rev['task_id'];
    }
    unset($rev);

    successResponse('取得個人資料成功', [
        'user'            => $user,
        'tasks_posted'    => $tasksPosted,
        'tasks_completed' => $tasksCompleted,
        'total_income'    => $totalIncome,
        'avg_rating'      => $avgRating,
        'review_count'    => $reviewCount,
        'reviews'         => $reviews,
    ]);
}

// ── PUT /api/auth/profile ──────────────────────────────────────────────────────
elseif ($method === 'PUT') {
    $body = getJsonBody();

    // Determine what to update
    $updateUsername = isset($body['username']);
    $updatePassword = isset($body['current_password']) && isset($body['new_password']);
    $updateBio      = isset($body['bio']);

    if (!$updateUsername && !$updatePassword && !$updateBio) {
        errorResponse('請提供要更新的欄位');
    }

    if ($updateBio) {
        $bio = trim($body['bio']);
        if (mb_strlen($bio) > 1000) errorResponse('個人簡介不可超過 1000 字');
        $db->prepare('UPDATE users SET bio = ? WHERE id = ?')->execute([$bio ?: null, $currentUser['id']]);
    }

    if ($updateUsername) {
        $newUsername = trim($body['username']);
        if ($newUsername === '') {
            errorResponse('使用者名稱不可為空');
        }
        if (mb_strlen($newUsername) > MAX_USERNAME_LEN) {
            errorResponse('使用者名稱不可超過 ' . MAX_USERNAME_LEN . ' 個字元');
        }

        // Check uniqueness (excluding self)
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $stmt->execute([$newUsername, $currentUser['id']]);
        if ($stmt->fetch()) {
            errorResponse('此使用者名稱已被使用');
        }

        $stmt = $db->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$newUsername, $currentUser['id']]);

        // Update session
        $_SESSION['user']['username'] = $newUsername;
    }

    if ($updatePassword) {
        $currentPassword = $body['current_password'];
        $newPassword     = $body['new_password'];

        if (mb_strlen($newPassword) < 6) {
            errorResponse('新密碼至少需要 6 個字元');
        }
        if (mb_strlen($newPassword) > MAX_PASSWORD_LEN) {
            errorResponse('新密碼不可超過 ' . MAX_PASSWORD_LEN . ' 個字元');
        }

        // Fetch current hash
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$currentUser['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            errorResponse('目前密碼不正確', 403);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $currentUser['id']]);
    }

    successResponse('個人資料更新成功');
}

else {
    errorResponse('Method not allowed', 405);
}
