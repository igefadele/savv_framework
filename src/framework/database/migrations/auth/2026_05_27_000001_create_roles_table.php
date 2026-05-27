<?php

use Savv\Utils\Db\Migration\Blueprint;
use Savv\Utils\Db\Migration\Schema;

return new class {
    public function up(\PDO $db): void
    {
        Schema::create($db, 'roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name'], 'roles_name_guard_unique');
        });
    }

    public function down(\PDO $db): void
    {
        Schema::dropIfExists($db, 'roles');
    }
};
