<?php

return new class {
    public function up(\PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `model_has_roles` (
                `role_id` BIGINT UNSIGNED NOT NULL,
                `model_type` VARCHAR(255) NOT NULL,
                `model_id` BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (`role_id`, `model_id`, `model_type`),
                INDEX `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
                CONSTRAINT `model_has_roles_role_id_foreign`
                    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(\PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `model_has_roles`;");
    }
};
