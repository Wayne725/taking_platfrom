<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../db.php';

setCorsHeaders();
startAppSession();

$method = $_SERVER['REQUEST_METHOD'];

// ── GET /api/tasks ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $db = getDB();

    $conditions = [];
    $params     = [];

    // Filter by status
    $validStatuses = ['open', 'assigned', 'in_progress', 'completed_pending_confirmation', 'completed'];
    if (!empty($_GET['status']) && in_array($_GET['status'], $validStatuses, true)) {
        $conditions[] = 't.status = ?';
        $params[]     = $_GET['status'];
    }

    // Filter by category
    $validCategories = ['設計', '開發', '文案', '行銷', '其他'];
    if (!empty($_GET['category']) && in_array($_GET['category'], $validCategories, true)) {
        $conditions[] = 't.category = ?';
        $params[]     = $_GET['category'];
    }

    // Search by keyword
    if (!empty($_GET['search'])) {
        if (mb_strlen($_GET['search']) > 255) errorResponse('搜尋詞過長');
        $conditions[] = '(t.title LIKE ? OR t.description LIKE ?)';
        $keyword      = '%' . $_GET['search'] . '%';
        $params[]     = $keyword;
        $params[]     = $keyword;
    }

    $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Pagination
    $page  = max(1, (int) ($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int) ($_GET['limit'] ?? 9)));
    $offset = ($page - 1) * $limit;

    // Count total matching rows
    $countSql = "
        SELECT COUNT(DISTINCT t.id) AS total
        FROM tasks t
        JOIN users u_client ON t.client_id = u_client.id
        LEFT JOIN users u_worker ON t.assigned_worker_id = u_worker.id
        LEFT JOIN task_applications ta ON t.id = ta.task_id
        $where
    ";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $totalPages = (int) ceil($total / $limit);

    $sql = "
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
            t.created_at
        FROM tasks t
        JOIN users u_client ON t.client_id = u_client.id
        LEFT JOIN users u_worker ON t.assigned_worker_id = u_worker.id
        LEFT JOIN task_applications ta ON t.id = ta.task_id
        $where
        GROUP BY t.id
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $pagedParams = array_merge($params, [$limit, $offset]);
    $stmt = $db->prepare($sql);
    $stmt->execute($pagedParams);
    $tasks = $stmt->fetchAll();

    // Cast types
    foreach ($tasks as &$task) {
        $task['id']                 = (int) $task['id'];
        $task['client_id']          = (int) $task['client_id'];
        $task['assigned_worker_id'] = $task['assigned_worker_id'] !== null ? (int) $task['assigned_worker_id'] : null;
        $task['applicant_count']    = (int) $task['applicant_count'];
        $task['budget']             = (float) $task['budget'];
    }
    unset($task);

    successResponse('取得任務列表成功', [
        'tasks'      => $tasks,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => $totalPages,
        ],
    ]);
}

// ── POST /api/tasks ────────────────────────────────────────────────────────────
elseif ($method === 'POST') {
    $currentUser = requireAuth();

    $body = getJsonBody();

    $title       = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $budget      = $body['budget'] ?? null;
    $deadline    = trim($body['deadline'] ?? '');
    $category    = trim($body['category'] ?? '其他');

    if ($title === '' || $description === '' || $budget === null || $deadline === '') {
        errorResponse('請填寫所有必填欄位');
    }

    if (mb_strlen($title) > MAX_TITLE_LEN) {
        errorResponse('標題不可超過 ' . MAX_TITLE_LEN . ' 個字元');
    }
    if (mb_strlen($description) > MAX_DESC_LEN) {
        errorResponse('描述不可超過 ' . MAX_DESC_LEN . ' 個字元');
    }

    if (!is_numeric($budget) || (float) $budget < 0) {
        errorResponse('預算不可為負數');
    }

    // Validate deadline is not in the past
    $today        = new DateTime('today');
    $deadlineDate = DateTime::createFromFormat('Y-m-d', $deadline);
    if (!$deadlineDate || $deadlineDate < $today) {
        errorResponse('截止日期不可為過去');
    }

    $validCategories = ['設計', '開發', '文案', '行銷', '其他'];
    if (!in_array($category, $validCategories, true)) {
        $category = '其他';
    }

    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO tasks (title, description, budget, deadline, category, client_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $description, (float) $budget, $deadline, $category, $currentUser['id']]);
    $newId = (int) $db->lastInsertId();

    logActivity($db, $newId, $currentUser['id'], 'task_created');

    // Fetch created task
    $stmt = $db->prepare(
        'SELECT t.*, u.username AS client_name
         FROM tasks t
         JOIN users u ON t.client_id = u.id
         WHERE t.id = ?'
    );
    $stmt->execute([$newId]);
    $task = $stmt->fetch();
    $task['id']        = (int) $task['id'];
    $task['client_id'] = (int) $task['client_id'];
    $task['budget']    = (float) $task['budget'];

    successResponse('任務發布成功', ['task' => $task], 201);
}

else {
    errorResponse('Method not allowed', 405);
}
