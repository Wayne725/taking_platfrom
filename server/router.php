<?php
/**
 * Simple PHP Router
 *
 * Route all requests through this file via Apache .htaccess or nginx config.
 * URL format: /api/{resource}/{id?}/{action?}
 *
 * Examples:
 *   GET    /api/auth/me           → api/auth/me.php
 *   POST   /api/auth/login        → api/auth/login.php
 *   GET    /api/auth/profile      → api/auth/profile.php
 *   PUT    /api/auth/profile      → api/auth/profile.php
 *   GET    /api/tasks             → api/tasks/index.php
 *   GET    /api/tasks/5           → api/tasks/show.php?id=5
 *   PUT    /api/tasks/5           → api/tasks/update.php?id=5
 *   DELETE /api/tasks/5           → api/tasks/delete.php?id=5
 *   POST   /api/tasks/5/apply     → api/tasks/apply.php?id=5
 *   POST   /api/tasks/5/assign    → api/tasks/assign.php?id=5
 *   POST   /api/tasks/5/start     → api/tasks/start.php?id=5
 *   POST   /api/tasks/5/complete  → api/tasks/complete.php?id=5
 *   POST   /api/tasks/5/confirm   → api/tasks/confirm.php?id=5
 *   POST   /api/tasks/5/withdraw  → api/tasks/withdraw.php?id=5
 *   GET    /api/tasks/5/chat      → api/tasks/chat.php?id=5
 *   POST   /api/tasks/5/chat      → api/tasks/chat.php?id=5
 */

// Parse the request URI
$requestUri  = $_SERVER['REQUEST_URI'];
$scriptName  = $_SERVER['SCRIPT_NAME'];
$basePath    = dirname($scriptName);

// Strip base path and query string
$path = parse_url($requestUri, PHP_URL_PATH);
if ($basePath !== '/' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}
$path = '/' . ltrim($path, '/');
$path = rtrim($path, '/') ?: '/';

// Remove leading /api
if (str_starts_with($path, '/api')) {
    $path = substr($path, 4);
}
$path = '/' . ltrim($path, '/');

// Split into segments
$segments = array_values(array_filter(explode('/', $path)));
// e.g. ['auth', 'login'] or ['tasks', '5', 'apply']

$apiDir = __DIR__ . '/api';

if (count($segments) === 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not found', 'data' => null]);
    exit;
}

$resource = $segments[0]; // 'auth' or 'tasks'

// ── Auth routes ────────────────────────────────────────────────────────────────
if ($resource === 'auth') {
    $action = $segments[1] ?? '';
    $file   = $apiDir . '/auth/' . $action . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Route not found', 'data' => null]);
    }
    exit;
}

// ── Task routes ────────────────────────────────────────────────────────────────
if ($resource === 'tasks') {
    $idOrAction = $segments[1] ?? null;
    $action     = $segments[2] ?? null;

    // GET/POST /api/tasks
    if ($idOrAction === null) {
        require $apiDir . '/tasks/index.php';
        exit;
    }

    // /api/tasks/{id}/{action?}
    $id = (int) $idOrAction;
    if ($id > 0) {
        $_GET['id'] = $id;

        if ($action === null) {
            // GET → show, PUT → update, DELETE → delete
            $method = $_SERVER['REQUEST_METHOD'];
            if ($method === 'GET') {
                require $apiDir . '/tasks/show.php';
            } elseif ($method === 'PUT') {
                require $apiDir . '/tasks/update.php';
            } elseif ($method === 'DELETE') {
                require $apiDir . '/tasks/delete.php';
            } else {
                http_response_code(405);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => null]);
            }
            exit;
        }

        // Action routes
        $actionMap = ['apply', 'assign', 'start', 'complete', 'confirm', 'withdraw', 'review', 'chat'];
        if (in_array($action, $actionMap, true)) {
            require $apiDir . '/tasks/' . $action . '.php';
            exit;
        }
    }

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Route not found', 'data' => null]);
    exit;
}

// ── Users routes ──────────────────────────────────────────────────────────────
if ($resource === 'users') {
    $id = (int) ($segments[1] ?? 0);
    if ($id > 0) {
        $_GET['id'] = $id;
        require $apiDir . '/users/show.php';
        exit;
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Route not found', 'data' => null]);
    exit;
}

// ── Fallback ───────────────────────────────────────────────────────────────────
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Route not found', 'data' => null]);
