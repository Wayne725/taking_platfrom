<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    errorResponse('Method not allowed', 405);
}

$currentUser = requireAuth();

$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId <= 0) {
    errorResponse('無效的任務 ID', 400);
}

$db = getDB();

$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    errorResponse('任務不存在', 404);
}

if ((int) $task['client_id'] !== $currentUser['id']) {
    errorResponse('無權限執行此操作', 403);
}

if ($task['status'] !== 'open') {
    errorResponse('任務進行中無法刪除');
}

// Check if there are any applicants
$stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM task_applications WHERE task_id = ?');
$stmt->execute([$taskId]);
$count = (int) $stmt->fetch()['cnt'];

if ($count > 0) {
    errorResponse('任務已有申請者，無法刪除');
}

$stmt = $db->prepare('DELETE FROM tasks WHERE id = ?');
$stmt->execute([$taskId]);

successResponse('任務刪除成功');
