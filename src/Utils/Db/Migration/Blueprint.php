<?php

namespace Savv\Utils\Db\Migration;

class Blueprint
{
    protected array $columns = [];
    protected array $primaryKeys = [];
    protected array $indexes = [];
    protected array $uniqueIndexes = [];
    protected array $foreignKeys = [];

    public function __construct(protected string $table)
    {
    }

    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->addColumn($column, 'BIGINT UNSIGNED')->autoIncrement()->primary();
    }

    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($column, "VARCHAR({$length})");
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TEXT');
    }

    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'INT');
    }

    public function unsignedBigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'BIGINT UNSIGNED');
    }

    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TINYINT(1)');
    }

    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TIMESTAMP');
    }

    public function timestamps(bool $nullable = true): void
    {
        $createdAt = $this->timestamp('created_at');
        $updatedAt = $this->timestamp('updated_at');

        if ($nullable) {
            $createdAt->nullable();
            $updatedAt->nullable();
        }
    }

    public function primary(array|string $columns): void
    {
        $this->primaryKeys[] = (array) $columns;
    }

    public function index(array|string $columns, ?string $name = null): void
    {
        $columns = (array) $columns;
        $this->indexes[] = [$columns, $name ?: $this->defaultIndexName($columns, 'index')];
    }

    public function unique(array|string $columns, ?string $name = null): void
    {
        $columns = (array) $columns;
        $this->uniqueIndexes[] = [$columns, $name ?: $this->defaultIndexName($columns, 'unique')];
    }

    public function foreign(string $column, ?string $name = null): ForeignKeyDefinition
    {
        $foreignKey = new ForeignKeyDefinition($column, $name);
        $this->foreignKeys[] = $foreignKey;

        return $foreignKey;
    }

    public function toSql(): string
    {
        $definitions = array_map(
            fn (ColumnDefinition $column) => $column->toSql(),
            $this->columns
        );

        foreach ($this->primaryKeys as $columns) {
            $definitions[] = 'PRIMARY KEY (' . $this->columnList($columns) . ')';
        }

        foreach ($this->uniqueIndexes as [$columns, $name]) {
            $definitions[] = "UNIQUE KEY `{$name}` (" . $this->columnList($columns) . ')';
        }

        foreach ($this->indexes as [$columns, $name]) {
            $definitions[] = "INDEX `{$name}` (" . $this->columnList($columns) . ')';
        }

        foreach ($this->foreignKeys as $foreignKey) {
            $definitions[] = $foreignKey->toSql($this->table);
        }

        return "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n    "
            . implode(",\n    ", $definitions)
            . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }

    protected function addColumn(string $name, string $type): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type);
        $this->columns[] = $column;

        return $column;
    }

    protected function columnList(array $columns): string
    {
        return implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));
    }

    protected function defaultIndexName(array $columns, string $suffix): string
    {
        return $this->table . '_' . implode('_', $columns) . "_{$suffix}";
    }
}
