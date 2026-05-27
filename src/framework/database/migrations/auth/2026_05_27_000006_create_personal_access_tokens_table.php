<?php

use Savv\Utils\Db\Migration\Blueprint;
use Savv\Utils\Db\Migration\Schema;

return new class {
    public function up(\PDO $db): void
    {
        Schema::create($db, 'personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['tokenable_id', 'tokenable_type'], 'tokens_tokenable_index');
        });
    }

    public function down(\PDO $db): void
    {
        Schema::dropIfExists($db, 'personal_access_tokens');
    }
};
