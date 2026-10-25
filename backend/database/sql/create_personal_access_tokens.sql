-- =============================================================
-- personal_access_tokens — required by Laravel Sanctum v4.x
-- Database: field_management
-- =============================================================
-- INSTRUCTIONS:
--   1. Open MySQL Workbench and connect to your field_management database.
--   2. Select the field_management schema from the left panel.
--   3. Open a new SQL tab and paste this entire file.
--   4. Click the lightning-bolt "Execute" button (or press Ctrl+Shift+Enter).
--   5. You should see: "1 row(s) affected" or "0 row(s) affected - table already exists".
--   6. Verify the table exists by running: SHOW TABLES LIKE 'personal_access_tokens';
--
-- SAFETY: This script is idempotent (CREATE TABLE IF NOT EXISTS).
--         It will NOT modify any of the 16 existing tables.
--         It only creates the personal_access_tokens table if it does not already exist.
-- =============================================================

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tokenable_type` VARCHAR(255)    NOT NULL,
    `tokenable_id`   BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(255)    NOT NULL,
    `token`          VARCHAR(64)     NOT NULL,
    `abilities`      TEXT            NULL,
    `last_used_at`   TIMESTAMP       NULL DEFAULT NULL,
    `expires_at`     TIMESTAMP       NULL DEFAULT NULL,
    `created_at`     TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`     TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
    INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Verification query — run this after the CREATE to confirm success:
-- SHOW TABLES LIKE 'personal_access_tokens';
-- DESCRIBE personal_access_tokens;
