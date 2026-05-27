<?php

namespace Savv\Utils\Db\Migration;

class ColumnDefinition
{
    protected bool $nullable = false;
    protected bool $primary = false;
    protected bool $unique = false;
    protected bool $autoIncrement = false;
    protected mixed $default = null;
    protected bool $hasDefault = false;

    public function __construct(
        protected string $name,
        protected string $type
    ) {
    }

    public function nullable(): self
    {
        $this->nullable = true;

        return $this;
    }

    public function primary(): self
    {
        $this->primary = true;

        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;

        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;

        return $this;
    }

    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        if (!$this->nullable) {
            $sql .= ' NOT NULL';
        } else {
            $sql .= ' NULL';
        }

        if ($this->hasDefault) {
            $sql .= ' DEFAULT ' . $this->formatDefault($this->default);
        }

        if ($this->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }

        if ($this->primary) {
            $sql .= ' PRIMARY KEY';
        }

        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        return $sql;
    }

    protected function formatDefault(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $upper = strtoupper((string) $value);
        if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'], true)) {
            return $upper;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }
}
