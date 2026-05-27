<?php

return new class {
    public function up(\PDO $db): void
    {
        $db->exec("
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
        ");
    }

    public function down(\PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `personal_access_tokens`;");
    }
};
