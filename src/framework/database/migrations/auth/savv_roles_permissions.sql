
-- -----------------------------------------------------
-- 1. ROLES TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `guard_name` VARCHAR(255) NOT NULL, -- e.g., 'web', 'api'
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `roles_name_guard_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 2. PERMISSIONS TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `guard_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `permissions_name_guard_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 3. ROLE_HAS_PERMISSIONS (Pivot for RBAC)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`permission_id`, `role_id`),
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. MODEL_HAS_ROLES (Polymorphic: User -> Role)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `model_has_roles` (
    `role_id` BIGINT UNSIGNED NOT NULL,
    `model_type` VARCHAR(255) NOT NULL, -- e.g., 'App\Models\User'
    `model_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `model_id`, `model_type`),
    INDEX `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. MODEL_HAS_PERMISSIONS (Direct User -> Permission)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `model_type` VARCHAR(255) NOT NULL,
    `model_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
    INDEX `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 6. PERSONAL ACCESS TOKENS (For Token Driver / Sanctum-style)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tokenable_type` VARCHAR(255) NOT NULL,
    `tokenable_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `last_used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `tokens_tokenable_index` (`tokenable_id`, `tokenable_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;