<?php
$pdo = new PDO(
    'mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=ewards_pms',
    '48L8zGX7cyd23cd.root',
    '3p4UXPeKR9qMCkDw',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_CA => '',
    ]
);

echo "Creating Phase 1 tables...\n\n";

// 1. Projects
$pdo->exec("CREATE TABLE IF NOT EXISTS `projects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `owner_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('active','completed','on_hold') NOT NULL DEFAULT 'active',
    `start_date` DATE NULL DEFAULT NULL,
    `end_date` DATE NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `projects_owner_idx` (`owner_id`),
    KEY `projects_status_idx` (`status`),
    CONSTRAINT `projects_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `team_members` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "projects: OK\n";

// 2. Project Members (pivot)
$pdo->exec("CREATE TABLE IF NOT EXISTS `project_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pm_unique` (`project_id`, `user_id`),
    CONSTRAINT `pm_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `pm_user_fk` FOREIGN KEY (`user_id`) REFERENCES `team_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "project_members: OK\n";

// 3. Tasks
$pdo->exec("CREATE TABLE IF NOT EXISTS `tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status` ENUM('open','in_progress','blocked','done') NOT NULL DEFAULT 'open',
    `priority` ENUM('p0','p1','p2','p3') NOT NULL DEFAULT 'p2',
    `deadline` DATE NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tasks_project_idx` (`project_id`),
    KEY `tasks_assigned_idx` (`assigned_to`),
    KEY `tasks_status_idx` (`status`),
    KEY `tasks_deadline_idx` (`deadline`),
    CONSTRAINT `tasks_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tasks_assigned_fk` FOREIGN KEY (`assigned_to`) REFERENCES `team_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "tasks: OK\n";

// 4. Work Logs
$pdo->exec("CREATE TABLE IF NOT EXISTS `work_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `project_id` BIGINT UNSIGNED NOT NULL,
    `task_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `log_date` DATE NOT NULL,
    `hours_spent` DECIMAL(5,2) NOT NULL,
    `note` TEXT NULL DEFAULT NULL,
    `blocker` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `wl_user_date` (`user_id`, `log_date`),
    KEY `wl_project` (`project_id`),
    KEY `wl_task` (`task_id`),
    CONSTRAINT `wl_user_fk` FOREIGN KEY (`user_id`) REFERENCES `team_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `wl_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `wl_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "work_logs: OK\n";

// 5. Documents
$pdo->exec("CREATE TABLE IF NOT EXISTS `documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `documentable_type` VARCHAR(255) NOT NULL,
    `documentable_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED NULL DEFAULT NULL,
    `mime_type` VARCHAR(100) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `doc_entity_idx` (`documentable_type`, `documentable_id`),
    CONSTRAINT `doc_uploader_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `team_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "documents: OK\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "\nTotal tables: " . count($tables) . "\n";
