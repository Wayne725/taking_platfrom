<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$taskId = (int) ($_GET['id'] ?? 0);
if ($taskId <= 0) {
    errorResponse('無效的任務 ID', 400);
}

$db = getDB();

// Fetch task with related user names
$stmt = $db->prepare("
    SELECT
        t.id,
        t.title,
        t.description,
        t.budget,
        t.deadline,
        t.category,
        t.status,
        t.client_id,
        u_client.username  AS client_name,
        t.assigned_worker_id,
        u_worker.username  AS assigned_worker_name,
        COUNT(ta.id)       AS applicant_count,
        t.created_at,
        t.updated_at
    FROM tasks t
    JOIN users u_client ON t.client_id = u_client.id
    LEFT JOIN users u_worker ON t.assigned_worker_id = u_worker.id
    LEFT JOIN task_applications ta ON t.id = ta.task_id
    WHERE t.id = ?
    GROUP BY t.id
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    errorResponse('任務不存在', 404);
}

$task['id']                 = (int) $task['id'];
$task['client_id']          = (int) $task['client_id'];
$task['assigned_worker_id'] = $task['assigned_worker_id'] !== null ? (int) $task['assigned_worker_id'] : null;
$task['applicant_count']    = (int) $task['applicant_count'];
$task['budget']             = (float) $task['budget'];

// Fetch applicants (only visible to task owner)
$currentUser  = getCurrentUser();
$applications = [];

if ($currentUser !== null && $currentUser['id'] === $task['client_id']) {
    $stmt = $db->prepare("
        SELECT ta.id, ta.worker_id, u.username AS worker_name, ta.message, ta.applied_at
        FROM task_applications ta
        JOIN users u ON ta.worker_id = u.id
        WHERE ta.task_id = ?
        ORDER BY ta.applied_at ASC
    ");
    $stmt->execute([$taskId]);
    $applications = $stmt->fetchAll();
    foreach ($applications as &$app) {
        $app['id']        = (int) $app['id'];
        $app['worker_id'] = (int) $app['worker_id'];
    }
    unset($app);
}

// Check if current user has already applied
$hasApplied = false;
if ($currentUser !== null) {
    $stmt = $db->prepare('SELECT id FROM task_applications WHERE task_id = ? AND worker_id = ? LIMIT 1');
    $stmt->execute([$taskId, $currentUser['id']]);
    $hasApplied = (bool) $stmt->fetch();
}

$chat = [
    'can_chat'          => false,
    'participant_count' => 0,
];
if ($currentUser !== null) {
    $isTaskOwner = $currentUser['id'] === $task['client_id'];
    $chat['can_chat'] = ($isTaskOwner && $task['applicant_count'] > 0) || $hasApplied;
    $chat['participant_count'] = $task['applicant_count'] > 0 ? $task['applicant_count'] + 1 : 0;
}

// Fetch activity log
$stmt = $db->prepare('SELECT ta.action, ta.note, ta.created_at, u.username
                      FROM task_activities ta
                      JOIN users u ON ta.user_id = u.id
                      WHERE ta.task_id = ?
                      ORDER BY ta.created_at ASC');
$stmt->execute([$taskId]);
$activities = $stmt->fetchAll();

// Fetch review (if any)
$stmt = $db->prepare(
    'SELECT r.rating, r.comment, r.created_at,
            u.username AS reviewer_name
     FROM task_reviews r
     JOIN users u ON r.reviewer_id = u.id
     WHERE r.task_id = ? LIMIT 1'
);
$stmt->execute([$taskId]);
$review = $stmt->fetch() ?: null;
if ($review) $review['rating'] = (int) $review['rating'];

successResponse('取得任務詳情成功', [
    'task'         => $task,
    'applications' => $applications,
    'has_applied'  => $hasApplied,
    'chat'         => $chat,
    'activities'   => $activities,
    'review'       => $review,
]);
