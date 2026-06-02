<?php
define('DB_HOST',    'xghvc8.h.filess.io');
define('DB_PORT',    61002);
define('DB_NAME',    'task_flamecould');
define('DB_USER',    'task_flamecould');
define('DB_PASS',    '91771e103194a88d4cddb1c1b4f79c2a98bf94bc');
define('DB_CHARSET', 'utf8mb4');

// Session configuration
define('SESSION_NAME',     'task_system_session');
define('SESSION_LIFETIME', 86400);

define('APP_ENV', 'production');

define('ALLOWED_ORIGINS', [
    'https://wayne725.github.io',
    'http://localhost',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:8080',
]);

define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW',  900);

define('MAX_USERNAME_LEN',  50);
define('MAX_EMAIL_LEN',    255);
define('MAX_PASSWORD_LEN', 128);
define('MAX_TITLE_LEN',    255);
define('MAX_DESC_LEN',    5000);
