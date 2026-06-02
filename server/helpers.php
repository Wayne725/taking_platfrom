<?php
require_once __DIR__ . '/config.php';

// ── Session bootstrap ──────────────────────────────────────────────────────────
function startAppSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);

        // 支援從 Header 傳入 Session ID（跨站 Cookie 被瀏覽器擋時的替代方案）
        $headerSid = $_SERVER['HTTP_X_SESSION_ID'] ?? '';
        if ($headerSid && preg_match('/^[a-zA-Z0-9,\-]{22,256}$/', $headerSid)) {
            session_id($headerSid);
        }

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        session_start();
    }
}

// ── Response helpers ───────────────────────────────────────────────────────────
function jsonResponse(string $status, string $message, mixed $data = null, int $httpCode = 200): never {
    if (ob_get_level() > 0) ob_end_clean(); // 清除任何前置注入的輸出
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function successResponse(string $message, mixed $data = null, int $httpCode = 200): never {
    jsonResponse('success', $message, $data, $httpCode);
}

function errorResponse(string $message, int $httpCode = 400, mixed $data = null): never {
    jsonResponse('error', $message, $data, $httpCode);
}

// ── Auth helpers ───────────────────────────────────────────────────────────────
function getCurrentUser(): ?array {
    startAppSession();
    return $_SESSION['user'] ?? null;
}

function requireAuth(): array {
    $user = getCurrentUser();
    if ($user === null) {
        errorResponse('請先登入', 401);
    }
    return $user;
}

// ── CORS headers ──────────────────────────────────────────────────────────────
function setCorsHeaders(): void {
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = ALLOWED_ORIGINS;

    if ($origin && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    // CSRF: reject cross-origin mutating requests with a non-whitelisted Origin
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true) && $origin !== '') {
        if (!in_array($origin, $allowed, true)) {
            errorResponse('CSRF 驗證失敗', 403);
        }
    }
}

// ── Input helpers ──────────────────────────────────────────────────────────────
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function sanitizeString(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ── Activity log helper ────────────────────────────────────────────────────────
function logActivity(PDO $db, int $taskId, int $userId, string $action, ?string $note = null): void {
    try {
        $db->prepare('INSERT INTO task_activities (task_id, user_id, action, note) VALUES (?, ?, ?, ?)')
           ->execute([$taskId, $userId, $action, $note]);
    } catch (PDOException $e) {
        // Activity log failure is non-fatal; main operation already succeeded
    }
}
