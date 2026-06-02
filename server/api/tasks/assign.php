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

if ((int) $task['client_id'] !== $currentUser['id']) {
    errorResponse('無權限執行此操作', 403);
}

if ($task['status'] !== 'open') {
    errorResponse('任務狀態不符，無法指派');
}

$body     = getJsonBody();
$workerId = (int) ($body['worker_id'] ?? 0);

if ($workerId <= 0) {
    errorResponse('請指定接案者');
}

// Verify the worker has applied for this task
$stmt = $db->prepare('SELECT id FROM task_applications WHERE task_id = ? AND worker_id = ? LIMIT 1');
$stmt->execute([$taskId, $workerId]);
if (!$stmt->fetch()) {
    errorResponse('該使用者未申請此任務');
}

// Fetch worker username for the activity note
$stmt = $db->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$workerId]);
$workerRow = $stmt->fetch();
$workerUsername = $workerRow ? $workerRow['username'] : (string) $workerId;

// Assign and update status
$stmt = $db->prepare(
    'UPDATE tasks SET assigned_worker_id = ?, status = ? WHERE id = ?'
);
$stmt->execute([$workerId, 'assigned', $taskId]);

logActivity($db, $taskId, $currentUser['id'], 'worker_assigned', $workerUsername);

successResponse('指派成功，任務狀態已更新為 assigned');
