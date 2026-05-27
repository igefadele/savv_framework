<?php

return new class {
    public function up(\PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `model_has_permissions` (
                `permission_id` BIGINT UNSIGNED NOT NULL,
                `model_type` VARCHAR(255) NOT NULL,
                `model_id` BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
                INDEX `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
                CONSTRAINT `model_has_permissions_permission_id_foreign`
                    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(\PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `model_has_permissions`;");
    }
};
