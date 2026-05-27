<?php

namespace Savv\Utils\Db\Migration;

use Closure;
use PDO;

class Schema
{
    public static function create(PDO $db, string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $db->exec($blueprint->toSql());
    }

    public static function dropIfExists(PDO $db, string $table): void
    {
        $db->exec("DROP TABLE IF EXISTS `{$table}`;");
    }
}
