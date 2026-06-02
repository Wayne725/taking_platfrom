<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    errorResponse('Method not allowed', 405);
}

$currentUser = requireAuth();

$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId <= 0) {
    errorResponse('無效的任務 ID', 400);
}

$db = getDB();

// Fetch task
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
    errorResponse('任務進行中無法修改');
}

$body = getJsonBody();

$title       = trim($body['title'] ?? $task['title']);
$description = trim($body['description'] ?? $task['description']);
$budget      = $body['budget'] ?? $task['budget'];
$deadline    = trim($body['deadline'] ?? $task['deadline']);
$category    = trim($body['category'] ?? $task['category']);

if ($title === '' || $description === '' || $deadline === '') {
    errorResponse('請填寫所有必填欄位');
}

if (!is_numeric($budget) || (float) $budget < 0) {
    errorResponse('預算不可為負數');
}

$today        = new DateTime('today');
$deadlineDate = DateTime::createFromFormat('Y-m-d', $deadline);
if (!$deadlineDate || $deadlineDate < $today) {
    errorResponse('截止日期不可為過去');
}

$validCategories = ['設計', '開發', '文案', '行銷', '其他'];
if (!in_array($category, $validCategories, true)) {
    $category = '其他';
}

$stmt = $db->prepare(
    'UPDATE tasks SET title = ?, description = ?, budget = ?, deadline = ?, category = ? WHERE id = ?'
);
$stmt->execute([$title, $description, (float) $budget, $deadline, $category, $taskId]);

logActivity($db, $taskId, $currentUser['id'], 'task_updated');

// Return updated task
$stmt = $db->prepare(
    'SELECT t.*, u.username AS client_name
     FROM tasks t
     JOIN users u ON t.client_id = u.id
     WHERE t.id = ?'
);
$stmt->execute([$taskId]);
$updatedTask = $stmt->fetch();
$updatedTask['id']        = (int) $updatedTask['id'];
$updatedTask['client_id'] = (int) $updatedTask['client_id'];
$updatedTask['budget']    = (float) $updatedTask['budget'];

successResponse('任務更新成功', ['task' => $updatedTask]);
