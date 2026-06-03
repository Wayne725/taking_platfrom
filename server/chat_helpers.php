<?php

function ensureTaskChatRoom(PDO $db, int $taskId, int $clientId): int {
    $stmt = $db->prepare(
        'INSERT INTO task_chat_rooms (task_id)
         VALUES (?)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $stmt->execute([$taskId]);

    $roomId = (int) $db->lastInsertId();
    if ($roomId <= 0) {
        $stmt = $db->prepare('SELECT id FROM task_chat_rooms WHERE task_id = ? LIMIT 1');
        $stmt->execute([$taskId]);
        $roomId = (int) $stmt->fetchColumn();
    }

    if ($roomId <= 0) {
        throw new RuntimeException('Unable to resolve task chat room');
    }

    addTaskChatParticipant($db, $roomId, $clientId);

    return $roomId;
}

function addTaskChatParticipant(PDO $db, int $roomId, int $userId): void {
    $stmt = $db->prepare(
        'INSERT IGNORE INTO task_chat_participants (room_id, user_id) VALUES (?, ?)'
    );
    $stmt->execute([$roomId, $userId]);
}

function removeTaskChatParticipant(PDO $db, int $roomId, int $userId): void {
    $stmt = $db->prepare(
        'DELETE FROM task_chat_participants WHERE room_id = ? AND user_id = ?'
    );
    $stmt->execute([$roomId, $userId]);
}

function syncTaskChatParticipants(PDO $db, int $taskId, int $roomId, int $clientId): void {
    addTaskChatParticipant($db, $roomId, $clientId);

    $stmt = $db->prepare('SELECT worker_id FROM task_applications WHERE task_id = ?');
    $stmt->execute([$taskId]);

    foreach ($stmt->fetchAll() as $row) {
        addTaskChatParticipant($db, $roomId, (int) $row['worker_id']);
    }

    $stmt = $db->prepare(
        'DELETE tcp
         FROM task_chat_participants tcp
         LEFT JOIN task_applications ta
           ON ta.task_id = ? AND ta.worker_id = tcp.user_id
         WHERE tcp.room_id = ?
           AND tcp.user_id <> ?
           AND ta.id IS NULL'
    );
    $stmt->execute([$taskId, $roomId, $clientId]);
}

function findTaskChatRoomId(PDO $db, int $taskId): ?int {
    $stmt = $db->prepare('SELECT id FROM task_chat_rooms WHERE task_id = ? LIMIT 1');
    $stmt->execute([$taskId]);
    $roomId = $stmt->fetchColumn();

    return $roomId === false ? null : (int) $roomId;
}

function isTaskChatParticipant(PDO $db, int $roomId, int $userId): bool {
    $stmt = $db->prepare(
        'SELECT 1 FROM task_chat_participants WHERE room_id = ? AND user_id = ? LIMIT 1'
    );
    $stmt->execute([$roomId, $userId]);

    return (bool) $stmt->fetchColumn();
}
