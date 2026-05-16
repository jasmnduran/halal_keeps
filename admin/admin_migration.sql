-- ============================================================
-- Admin Module Migration
-- Run this in phpMyAdmin or MySQL CLI against halal_system
-- ============================================================

-- 1. Add admin role (id=11)
INSERT IGNORE INTO `roles` (`id`, `role_name`, `role_category`, `description`, `requires_approval`, `created_at`)
VALUES (11, 'Administrator', 'hcb_employee', 'System administrator with full access', 1, NOW());

-- 2. Add is_admin flag and account_locked to users
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
    ADD COLUMN IF NOT EXISTS `account_locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_admin`,
    ADD COLUMN IF NOT EXISTS `failed_login_attempts` INT NOT NULL DEFAULT 0 AFTER `account_locked`,
    ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL AFTER `failed_login_attempts`;

-- 3. Login attempt log table
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `success`    TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_email` (`email`),
    KEY `idx_login_ip`    (`ip_address`),
    KEY `idx_login_time`  (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Admin activity log (separate from general activity_log)
CREATE TABLE IF NOT EXISTS `admin_activity_log` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `admin_id`    INT(11) NOT NULL,
    `action`      VARCHAR(255) NOT NULL,
    `target_type` VARCHAR(100) DEFAULT NULL,
    `target_id`   INT(11) DEFAULT NULL,
    `details`     TEXT DEFAULT NULL,
    `ip_address`  VARCHAR(45) DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_log_admin` (`admin_id`),
    KEY `idx_admin_log_time`  (`created_at`),
    CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Data classification table
CREATE TABLE IF NOT EXISTS `data_classifications` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `table_name`  VARCHAR(100) NOT NULL,
    `column_name` VARCHAR(100) NOT NULL,
    `classification` ENUM('public','internal','confidential','restricted') NOT NULL DEFAULT 'internal',
    `description` TEXT DEFAULT NULL,
    `updated_by`  INT(11) DEFAULT NULL,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_table_col` (`table_name`, `column_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Session tracking table (for session timeout enforcement)
CREATE TABLE IF NOT EXISTS `active_sessions` (
    `id`           INT(11) NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11) NOT NULL,
    `session_token` VARCHAR(128) NOT NULL,
    `ip_address`   VARCHAR(45) DEFAULT NULL,
    `user_agent`   VARCHAR(500) DEFAULT NULL,
    `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_session_token` (`session_token`),
    KEY `idx_session_user` (`user_id`),
    CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Seed default data classifications
INSERT IGNORE INTO `data_classifications` (`table_name`, `column_name`, `classification`, `description`) VALUES
('users',    'password',      'restricted',    'Hashed password — never expose'),
('users',    'email',         'confidential',  'User email address'),
('users',    'phone',         'confidential',  'User phone number'),
('users',    'address',       'confidential',  'User address'),
('users',    'google_id',     'restricted',    'Google OAuth identifier'),
('halal_certificates', 'certificate_number', 'confidential', 'Halal certificate number'),
('letter_of_intent',   'company_address',    'internal',     'Business address'),
('laboratory_requests','analysis_details',   'restricted',   'Lab analysis details'),
('payments', 'transaction_reference', 'restricted', 'Payment transaction reference');

-- 8. Create default admin user (password: Admin@1234)
INSERT IGNORE INTO `users`
    (`email`, `full_name`, `first_name`, `last_name`, `password`, `role_id`, `role_status`, `is_active`, `is_admin`, `email_verified`, `created_at`)
VALUES
    ('admin@halalkeeps.com', 'System Administrator', 'System', 'Administrator',
     '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiGc2L1GqGhHxkd0LHAkCOYz6TiG',
     11, 'approved', 1, 1, 1, NOW())
ON DUPLICATE KEY UPDATE `is_admin` = 1, `role_id` = 11, `role_status` = 'approved';
