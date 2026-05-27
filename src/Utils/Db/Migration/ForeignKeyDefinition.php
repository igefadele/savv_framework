<?php

namespace Savv\Utils\Db\Migration;

class ForeignKeyDefinition
{
    protected string $references = 'id';
    protected string $on = '';
    protected ?string $onDelete = null;

    public function __construct(
        protected string $column,
        protected ?string $name = null
    ) {
    }

    public function references(string $column): self
    {
        $this->references = $column;

        return $this;
    }

    public function on(string $table): self
    {
        $this->on = $table;

        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $this->onDelete = 'CASCADE';

        return $this;
    }

    public function toSql(string $table): string
    {
        $name = $this->name ?: "{$table}_{$this->column}_foreign";
        $sql = "CONSTRAINT `{$name}` FOREIGN KEY (`{$this->column}`) REFERENCES `{$this->on}`(`{$this->references}`)";

        if ($this->onDelete) {
            $sql .= " ON DELETE {$this->onDelete}";
        }

        return $sql;
    }
}
