<?php

use Savv\Utils\Db\Migration\Blueprint;
use Savv\Utils\Db\Migration\Schema;

return new class {
    public function up(\PDO $db): void
    {
        Schema::create($db, 'model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign('role_id', 'model_has_roles_role_id_foreign')
                ->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    public function down(\PDO $db): void
    {
        Schema::dropIfExists($db, 'model_has_roles');
    }
};
