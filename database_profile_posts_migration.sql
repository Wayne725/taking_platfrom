-- Profile avatar and posts migration for existing filess.io MySQL databases.
-- Run this once in the filess.io Web Client SQL editor.

SET @avatar_url_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'avatar_url'
);

SET @avatar_url_sql = IF(
    @avatar_url_exists = 0,
    'ALTER TABLE users ADD COLUMN avatar_url VARCHAR(2048) NULL AFTER bio',
    'SELECT 1'
);

PREPARE avatar_url_stmt FROM @avatar_url_sql;
EXECUTE avatar_url_stmt;
DEALLOCATE PREPARE avatar_url_stmt;

SET @avatar_data_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'avatar_data'
);

SET @avatar_data_sql = IF(
    @avatar_data_exists = 0,
    'ALTER TABLE users ADD COLUMN avatar_data LONGTEXT NULL AFTER avatar_url',
    'SELECT 1'
);

PREPARE avatar_data_stmt FROM @avatar_data_sql;
EXECUTE avatar_data_stmt;
DEALLOCATE PREPARE avatar_data_stmt;

CREATE TABLE IF NOT EXISTS user_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NULL,
    image_data LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_user_post (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
