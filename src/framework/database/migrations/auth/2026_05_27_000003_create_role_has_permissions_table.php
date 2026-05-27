<?php

return new class {
    public function up(\PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `role_has_permissions` (
                `permission_id` BIGINT UNSIGNED NOT NULL,
                `role_id` BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (`permission_id`, `role_id`),
                CONSTRAINT `role_has_permissions_permission_id_foreign`
                    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
                CONSTRAINT `role_has_permissions_role_id_foreign`
                    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(\PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `role_has_permissions`;");
    }
};
