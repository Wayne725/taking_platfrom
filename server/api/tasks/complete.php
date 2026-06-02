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

if ((int) $task['assigned_worker_id'] !== $currentUser['id']) {
    errorResponse('無權限執行此操作', 403);
}

if ($task['status'] !== 'in_progress') {
    errorResponse('任務狀態不符，無法標記完成');
}

$stmt = $db->prepare('UPDATE tasks SET status = ? WHERE id = ?');
$stmt->execute(['completed_pending_confirmation', $taskId]);

logActivity($db, $taskId, $currentUser['id'], 'marked_completed');

successResponse('已標記完成，等待發案者確認');
