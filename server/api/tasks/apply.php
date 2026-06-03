<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../chat_helpers.php';

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

if ((int) $task['client_id'] === $currentUser['id']) {
    errorResponse('不可申請自己發布的任務');
}

if ($task['status'] !== 'open') {
    errorResponse('此任務已不開放申請');
}

// Check if already applied
$stmt = $db->prepare('SELECT id FROM task_applications WHERE task_id = ? AND worker_id = ? LIMIT 1');
$stmt->execute([$taskId, $currentUser['id']]);
if ($stmt->fetch()) {
    errorResponse('您已申請過此任務');
}

$body    = getJsonBody();
$message = trim($body['message'] ?? '');

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'INSERT INTO task_applications (task_id, worker_id, message) VALUES (?, ?, ?)'
    );
    $stmt->execute([$taskId, $currentUser['id'], $message ?: null]);

    $roomId = ensureTaskChatRoom($db, $taskId, (int) $task['client_id']);
    addTaskChatParticipant($db, $roomId, $currentUser['id']);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($e instanceof PDOException && $e->getCode() === '23000') {
        errorResponse('您已申請過此任務');
    }

    errorResponse('申請失敗，請稍後再試', 500);
}

logActivity($db, $taskId, $currentUser['id'], 'applied');

successResponse('申請成功');
