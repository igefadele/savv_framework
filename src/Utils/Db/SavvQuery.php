<?php
namespace Savv\Utils\Db;

use Savv\Utils\Db\SavvCache;

/**
 * SavvQuery: The Builder
 * Handles eager loading logic.
 */
class SavvQuery {
    protected $db;
    protected $table;
    protected $with = [];
    protected $wheres = [];
    protected $params = []; 
    protected $columns = '*'; 
    protected $limit;
    protected $offset;
    protected $orderBy;
    protected $joins = [];
    protected $modelClass; // Store the class name explicitly


    public function __construct(SavvDb $db, $table) {
        $this->db = $db;
        $this->table = $table;
    }

    public function setModel($class) {
        $this->modelClass = $class;
        return $this;
    }

    public function getWithMeta($ids) {
        if (empty($ids)) return [];
        
        $items = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")", 
            $ids
        )->fetchAll();

        $rawMeta = $this->db->query(
            "SELECT * FROM {$this->table}_meta WHERE object_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")", 
            $ids
        )->fetchAll();

        foreach ($rawMeta as $row) {
            SavvCache::setMeta($row['object_id'], $row['meta_key'], $row['meta_value']);
        }

        return $items;
    }

    public function select($columns = '*') {
        $this->columns = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    public function where(string $column, $operator = '=', $value = 1) {
        $this->wheres[] = "$column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'DESC') {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    public function first() {
        $sql = $this->buildSelect() . " LIMIT 1";
        $data = $this->db->query($sql, $this->params)->fetch();
        $this->reset();
        return $data ? $data : null;
    }

    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($this->wheres) $sql .= " WHERE " . implode(' AND ', $this->wheres);
        $result = $this->db->query($sql, $this->params)->fetch();
        $this->reset();
        return (int)$result['total'];
    }

    public function exists() {
        return (clone $this)->count() > 0;
    }

    public function join($table, $first, $second, $type = 'INNER') {
        $this->joins[] = "$type JOIN $table ON $first = $second";
        return $this;
    }

    public function paginate($perPage = 15, $page = 1) {
        $total = (clone $this)->count();
        $offset = ($page - 1) * $perPage;
        
        $sql = $this->buildSelect() . " LIMIT $perPage OFFSET $offset";
        $items = $this->db->query($sql, $this->params)->fetchAll();
        $this->reset();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    // Update your buildSelect() to include the joins
    protected function buildSelect() {
        $sql = "SELECT {$this->columns} FROM {$this->table}";
        
        if ($this->joins) {
            $sql .= " " . implode(' ', $this->joins);
        }
        
        if ($this->wheres) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        if ($this->orderBy) {
            $sql .= " {$this->orderBy}";
        }
        
        return $sql;
    }

    protected function reset() {
        $this->with = [];
        $this->wheres = [];
        $this->params = [];
        $this->columns = '*';
        $this->limit = null;
        $this->offset = null;
        $this->orderBy = null;
        $this->joins = [];
        return $this;
    }

    /**
     * Define which relationships to eager load
     */
    public function with($relations) {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    public function get() {
        $sql = $this->buildSelect();
        $results = $this->db->query($sql, $this->params)->fetchAll();
        
        // POINT 1: MODEL HYDRATION
        // Use the explicitly set model class, or fallback to the convention
        $class = $this->modelClass ?: $this->getModelClassFromTable($this->table);
        
        $models = array_map(fn($attributes) => new $class($attributes), $results);

        if (!empty($models) && !empty($this->with)) {
            $this->loadRelationships($models);
        }

        $this->reset();
        return $models;
    }

    /**
     * The Eager Loading Engine
     * Refined to handle collection mapping and relationship types.
     */
    protected function loadRelationships(&$models) {
        if (empty($models)) return;

        foreach ($this->with as $relation) {
            // 1. Get relationship metadata from the first model instance
            $firstModel = $models[0];
            if (!method_exists($firstModel, $relation)) continue;

            // Call the relation method to get the config (blueprint)
            $relConfig = $firstModel->$relation();
            
            $query = $relConfig['query'];
            $foreignKey = $relConfig['foreignKey'];
            $localKey = $relConfig['localKey'] ?? 'id';
            $type = $relConfig['type'];

            $ids = array_map(fn($m) => $m->{$localKey}, $models);
            if (empty($ids)) {
                foreach ($models as $model) {
                    $model->setRelation($relation, $type === 'hasMany' || $type === 'hasManyThrough' ? [] : null);
                }
                continue;
            }

            // 2. Fetch all related records in ONE query
            $relatedModels = $query->whereIn($foreignKey, $ids)->get();

            // 3. Group related models by their lookup key for fast assignment
            $grouped = [];
            foreach ($relatedModels as $relModel) {
                $key = $relModel->{$foreignKey} ?? null;
                $grouped[$key][] = $relModel;
            }

            // 4. Associate back to parents
            foreach ($models as $model) {
                $lookupValue = $model->{$localKey};
                $matches = $grouped[$lookupValue] ?? [];

                if ($type === 'hasMany' || $type === 'hasManyThrough') {
                    $model->setRelation($relation, $matches);
                } else {
                    $model->setRelation($relation, $matches[0] ?? null);
                }
            }
        }
    }

    // Helper for WhereIn
    public function whereIn($column, $values) {
        if (empty($values)) {
            $this->wheres[] = '0 = 1';
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    protected function getModelClassFromTable($table) {
        // Simple convention: 'posts' -> 'App\Models\Post'
        return "App\\Models\\" . ucfirst(rtrim($table, 's'));
    }
}