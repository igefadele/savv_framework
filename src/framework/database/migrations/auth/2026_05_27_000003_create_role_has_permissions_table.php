<?php

use Savv\Utils\Db\Migration\Blueprint;
use Savv\Utils\Db\Migration\Schema;

return new class {
    public function up(\PDO $db): void
    {
        Schema::create($db, 'role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
            $table->foreign('permission_id', 'role_has_permissions_permission_id_foreign')
                ->references('id')->on('permissions')->cascadeOnDelete();
            $table->foreign('role_id', 'role_has_permissions_role_id_foreign')
                ->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    public function down(\PDO $db): void
    {
        Schema::dropIfExists($db, 'role_has_permissions');
    }
};
