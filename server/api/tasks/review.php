<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method not allowed', 405);
}

$currentUser = requireAuth();

$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId <= 0) errorResponse('無效的任務 ID', 400);

$db = getDB();

$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) errorResponse('任務不存在', 404);
if ((int) $task['client_id'] !== $currentUser['id']) errorResponse('只有發案者可以留下評價', 403);
if ($task['status'] !== 'completed') errorResponse('任務尚未完成，無法評價');
if (!$task['assigned_worker_id']) errorResponse('此任務沒有接案者');

// One review per task
$stmt = $db->prepare('SELECT id FROM task_reviews WHERE task_id = ? LIMIT 1');
$stmt->execute([$taskId]);
if ($stmt->fetch()) errorResponse('此任務已評價過');

$body    = getJsonBody();
$rating  = (int) ($body['rating'] ?? 0);
$comment = trim($body['comment'] ?? '');

if ($rating < 1 || $rating > 5) errorResponse('評分必須在 1 到 5 之間');
if (mb_strlen($comment) > 500)   errorResponse('評論不可超過 500 字');

$stmt = $db->prepare(
    'INSERT INTO task_reviews (task_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$taskId, $currentUser['id'], (int) $task['assigned_worker_id'], $rating, $comment ?: null]);

logActivity($db, $taskId, $currentUser['id'], 'review_submitted', (string) $rating);

successResponse('評價提交成功');
