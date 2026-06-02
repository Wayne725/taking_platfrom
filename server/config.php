<?php
define('DB_HOST',    'sql101.infinityfree.com');
define('DB_NAME',    'if0_42075175_task_system');
define('DB_USER',    'if0_42075175');
define('DB_PASS',    'BkSfTstODVi');
define('DB_CHARSET', 'utf8mb4');

// Session configuration
define('SESSION_NAME',     'task_system_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// App settings
define('APP_ENV', 'production');

// CORS — frontend origins
define('ALLOWED_ORIGINS', [
    'https://wayne725.github.io',
    'http://localhost',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:8080',
]);

// Rate limiting (login brute-force)
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW',  900);

// Input max lengths
define('MAX_USERNAME_LEN',  50);
define('MAX_EMAIL_LEN',    255);
define('MAX_PASSWORD_LEN', 128);
define('MAX_TITLE_LEN',    255);
define('MAX_DESC_LEN',    5000);
