<?php
/**
 * api_entry.php
 * Single entry-point called by the frontend via ?_path=/...
 * Bridges query-string routing to the file-based router.
 *
 * Usage (from frontend fetch):
 *   ../server/api_entry.php?_path=/auth/login
 *   ../server/api_entry.php?_path=/tasks/5/apply
 */

$rawPath = $_GET['_path'] ?? '/';

// Validate path format to prevent path injection
if ($rawPath !== '/' && !preg_match('/^\/[a-z0-9\/_-]+$/i', $rawPath)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Invalid path', 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rewrite SERVER vars so router.php parses correctly
$_SERVER['REQUEST_URI'] = '/api' . $rawPath;
$_SERVER['SCRIPT_NAME'] = '/router.php';

// Forward any extra query params (e.g. ?status=open)
// They are already in $_GET, so router.php sub-files can read them normally.

require __DIR__ . '/router.php';
