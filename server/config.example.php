<?php
// 複製這個檔案為 config.php，再填入你的實際設定值
define('DB_HOST', 'YOUR_DB_HOST');       // InfinityFree 提供的 MySQL host
define('DB_NAME', 'YOUR_DB_NAME');       // 資料庫名稱
define('DB_USER', 'YOUR_DB_USER');       // 資料庫帳號
define('DB_PASS', 'YOUR_DB_PASS');       // 資料庫密碼
define('DB_CHARSET', 'utf8mb4');

// Session configuration
define('SESSION_NAME', 'task_system_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// App settings
define('APP_ENV', 'production'); // 上線後改為 'production'

// CORS — 填入你的 GitHub Pages 網址
define('ALLOWED_ORIGINS', [
    'https://YOUR_GITHUB_USERNAME.github.io',  // GitHub Pages
    'http://localhost',                          // 本地開發
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:8080',
]);

// Rate limiting (login brute-force)
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900);

// Input max lengths
define('MAX_USERNAME_LEN',    50);
define('MAX_EMAIL_LEN',      255);
define('MAX_PASSWORD_LEN',   128);
define('MAX_TITLE_LEN',      255);
define('MAX_DESC_LEN',      5000);
