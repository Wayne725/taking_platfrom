<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../chat_helpers.php';

setCorsHeaders();
startAppSession();

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    errorResponse('Method not allowed', 405);
}

$currentUser = requireAuth();

$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId <= 0) {
    errorResponse('無效的任務 ID', 400);
}

$db = getDB();

$stmt = $db->prepare('SELECT id, title, client_id FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    errorResponse('任務不存在', 404);
}

$clientId = (int) $task['client_id'];
$userId   = (int) $currentUser['id'];

$stmt = $db->prepare('SELECT COUNT(*) FROM task_applications WHERE task_id = ?');
$stmt->execute([$taskId]);
$applicationCount = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT 1 FROM task_applications WHERE task_id = ? AND worker_id = ? LIMIT 1');
$stmt->execute([$taskId, $userId]);
$hasApplied = (bool) $stmt->fetchColumn();

$isClient = $userId === $clientId;
if (!$isClient && !$hasApplied) {
    errorResponse('無權限使用此聊天室', 403);
}

if ($applicationCount === 0 && $isClient) {
    successResponse('此任務尚無聊天室', [
        'room'         => null,
        'participants' => [],
        'messages'     => [],
    ]);
}

$roomId = ensureTaskChatRoom($db, $taskId, $clientId);
syncTaskChatParticipants($db, $taskId, $roomId, $clientId);

if (!isTaskChatParticipant($db, $roomId, $userId)) {
    errorResponse('無權限使用此聊天室', 403);
}

if ($method === 'POST') {
    $body    = getJsonBody();
    $message = trim($body['message'] ?? '');

    if ($message === '') {
        errorResponse('請輸入訊息內容');
    }

    if (mb_strlen($message) > 2000) {
        errorResponse('訊息不可超過 2000 個字元');
    }

    $stmt = $db->prepare(
        'INSERT INTO task_chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)'
    );
    $stmt->execute([$roomId, $userId, $message]);
    $messageId = (int) $db->lastInsertId();

    $db->prepare('UPDATE task_chat_rooms SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')
       ->execute([$roomId]);

    $stmt = $db->prepare(
        'SELECT m.id, m.sender_id, u.username AS sender_name, m.message, m.created_at
         FROM task_chat_messages m
         JOIN users u ON m.sender_id = u.id
         WHERE m.id = ? AND m.room_id = ?
         LIMIT 1'
    );
    $stmt->execute([$messageId, $roomId]);
    $newMessage = $stmt->fetch();
    $newMessage['id']        = (int) $newMessage['id'];
    $newMessage['sender_id'] = (int) $newMessage['sender_id'];

    successResponse('訊息已送出', ['message' => $newMessage], 201);
}

$limit = max(1, min(100, (int) ($_GET['limit'] ?? 80)));
$afterId = max(0, (int) ($_GET['after_id'] ?? 0));

$participantsStmt = $db->prepare(
    'SELECT u.id, u.username, tcp.joined_at
     FROM task_chat_participants tcp
     JOIN users u ON tcp.user_id = u.id
     WHERE tcp.room_id = ?
     ORDER BY (u.id = ?) DESC, u.username ASC'
);
$participantsStmt->execute([$roomId, $clientId]);
$participants = $participantsStmt->fetchAll();
foreach ($participants as &$participant) {
    $participant['id'] = (int) $participant['id'];
}
unset($participant);

if ($afterId > 0) {
    $messagesStmt = $db->prepare(
        'SELECT m.id, m.sender_id, u.username AS sender_name, m.message, m.created_at
         FROM task_chat_messages m
         JOIN users u ON m.sender_id = u.id
         WHERE m.room_id = ? AND m.id > ?
         ORDER BY m.id ASC
         LIMIT ?'
    );
    $messagesStmt->bindValue(1, $roomId, PDO::PARAM_INT);
    $messagesStmt->bindValue(2, $afterId, PDO::PARAM_INT);
    $messagesStmt->bindValue(3, $limit, PDO::PARAM_INT);
    $messagesStmt->execute();
} else {
    $messagesStmt = $db->prepare(
        'SELECT *
         FROM (
             SELECT m.id, m.sender_id, u.username AS sender_name, m.message, m.created_at
             FROM task_chat_messages m
             JOIN users u ON m.sender_id = u.id
             WHERE m.room_id = ?
             ORDER BY m.id DESC
             LIMIT ?
         ) recent_messages
         ORDER BY id ASC'
    );
    $messagesStmt->bindValue(1, $roomId, PDO::PARAM_INT);
    $messagesStmt->bindValue(2, $limit, PDO::PARAM_INT);
    $messagesStmt->execute();
}

$messages = $messagesStmt->fetchAll();
foreach ($messages as &$message) {
    $message['id']        = (int) $message['id'];
    $message['sender_id'] = (int) $message['sender_id'];
}
unset($message);

successResponse('取得聊天室成功', [
    'room' => [
        'id'      => $roomId,
        'task_id' => $taskId,
        'title'   => $task['title'],
    ],
    'participants' => $participants,
    'messages'     => $messages,
]);
