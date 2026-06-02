<?php
ob_start(); // 攔截 InfinityFree 可能注入的任何前置輸出

// ── CORS：最優先設定 headers ───────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && (str_contains($origin, 'localhost') || str_contains($origin, '127.0.0.1'))) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://wayne725.github.io');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-ID');
header('Vary: Origin');

// OPTIONS preflight — 清空 buffer 後直接回傳
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit;
}

// ── Path 驗證 ─────────────────────────────────────────────────────────────────
$rawPath = $_GET['_path'] ?? '/';

if ($rawPath !== '/' && !preg_match('/^\/[a-z0-9\/_-]+$/i', $rawPath)) {
    ob_end_clean();
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Invalid path', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rewrite SERVER vars so router.php parses correctly
$_SERVER['REQUEST_URI'] = '/api' . $rawPath;
$_SERVER['SCRIPT_NAME'] = '/router.php';

require __DIR__ . '/router.php';
