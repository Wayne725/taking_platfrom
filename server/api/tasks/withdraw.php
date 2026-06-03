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

if ($task['status'] !== 'open') {
    errorResponse('任務已不在開放申請狀態，無法取消申請');
}

// Check that user has actually applied
$stmt = $db->prepare('SELECT id FROM task_applications WHERE task_id = ? AND worker_id = ? LIMIT 1');
$stmt->execute([$taskId, $currentUser['id']]);
if (!$stmt->fetch()) {
    errorResponse('您尚未申請此任務');
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('DELETE FROM task_applications WHERE task_id = ? AND worker_id = ?');
    $stmt->execute([$taskId, $currentUser['id']]);

    $roomId = findTaskChatRoomId($db, $taskId);
    if ($roomId !== null) {
        removeTaskChatParticipant($db, $roomId, $currentUser['id']);
    }

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    errorResponse('取消申請失敗，請稍後再試', 500);
}

logActivity($db, $taskId, $currentUser['id'], 'application_withdrawn');

successResponse('已取消申請');
