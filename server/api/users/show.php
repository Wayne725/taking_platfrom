<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) errorResponse('無效的使用者 ID', 400);

$db = getDB();

$stmt = $db->prepare('SELECT id, username, bio, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) errorResponse('使用者不存在', 404);

$user['id'] = (int) $user['id'];

// Stats
$stmt = $db->prepare('SELECT COUNT(*) FROM tasks WHERE client_id = ?');
$stmt->execute([$userId]);
$tasksPosted = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_worker_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$tasksCompleted = (int) $stmt->fetchColumn();

// Rating stats
$stmt = $db->prepare('SELECT COUNT(*), COALESCE(AVG(rating), 0) FROM task_reviews WHERE reviewee_id = ?');
$stmt->execute([$userId]);
$ratingRow    = $stmt->fetch(PDO::FETCH_NUM);
$reviewCount  = (int)   $ratingRow[0];
$avgRating    = round((float) $ratingRow[1], 1);

// Reviews received (newest first, max 20)
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
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

foreach ($reviews as &$rev) {
    $rev['rating']  = (int) $rev['rating'];
    $rev['task_id'] = (int) $rev['task_id'];
}
unset($rev);

successResponse('取得使用者資料成功', [
    'user'            => $user,
    'tasks_posted'    => $tasksPosted,
    'tasks_completed' => $tasksCompleted,
    'avg_rating'      => $avgRating,
    'review_count'    => $reviewCount,
    'reviews'         => $reviews,
]);
