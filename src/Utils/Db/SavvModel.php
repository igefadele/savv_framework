<?php
namespace Savv\Utils\Db;
use Savv\Utils\Db\SavvCache;

/**
 * SavvModel: The Entity Base
 * This is what the user's models (e.g., class Post extends SavvModel) will use.
 */
abstract class SavvModel {
    protected static $table;
    protected $attributes = [];
    protected $original = []; // Track changes
    protected $relations = [];

    public function __construct($attributes = []) {
        $this->attributes = $attributes;
        $this->original = $attributes;
    }

    public function setRelation($name, $value) {
        $this->relations[$name] = $value;
    }

    // Updated Magic Getter to check relations first
    public function __get($key) {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        return SavvCache::getMeta($this->attributes['id'] ?? null, $key);
    }

    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }

    public static function find($id) {
        $db = SavvDb::getInstance();
        $data = $db->query("SELECT * FROM " . static::$table . " WHERE id = ? LIMIT 1", [$id])->fetch();
        return $data ? new static($data) : null;
    }

    public function save() {
        $db = SavvDb::getInstance();
        $id = $this->attributes['id'] ?? null;

        if ($id) {
            // Update
            $diff = array_diff_assoc($this->attributes, $this->original);
            if (empty($diff)) return true;

            $fields = implode(' = ?, ', array_keys($diff)) . ' = ?';
            $db->query("UPDATE " . static::$table . " SET $fields WHERE id = ?", [...array_values($diff), $id]);
        } else {
            // Insert
            $columns = implode(', ', array_keys($this->attributes));
            $placeholders = implode(', ', array_fill(0, count($this->attributes), '?'));
            $db->query("INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)", array_values($this->attributes));
            $this->attributes['id'] = $db->lastInsertId();
        }
        $this->original = $this->attributes;
        return true;
    }

    public function delete() {
        if (isset($this->attributes['id'])) {
            $db = SavvDb::getInstance();
            return $db->query("DELETE FROM " . static::$table . " WHERE id = ?", [$this->attributes['id']]);
        }
        return false;
    }

    /**
     * One-to-One: A User has one Profile
     */
    protected function hasOne($relatedClass, $foreignKey, $localKey = 'id') {
        $instance = new $relatedClass();
        return [
            'type'       => 'hasOne',
            'query'      => savvQuery($instance::$table),
            'foreignKey' => $foreignKey,
            'localKey'   => $localKey
        ];
    }

    /**
     * SavvModel Relationship Blueprints
     * One-to-Many: A Post has many Comments
     * It return a descriptor if called in a query context
     */ 
    protected function hasMany($relatedClass, $foreignKey, $localKey = 'id') {
        $instance = new $relatedClass();
        return [
            'type'       => 'hasMany',
            'query'      => savvQuery($instance::$table),
            'foreignKey' => $foreignKey,
            'localKey'   => $localKey
        ];
    }

    /**
     * Inverse: A Comment belongs to a Post
     */
    protected function belongsTo($relatedClass, $foreignKey, $ownerKey = 'id') {
        $instance = new $relatedClass();
        return [
            'type'       => 'belongsTo',
            'query'      => savvQuery($instance::$table),
            'foreignKey' => $ownerKey, // We match the owner's ID
            'localKey'   => $foreignKey // against the child's FK
        ];
    } 
     
    /**
     * Has Many Through: Country -> Users -> Posts
     * Made to return a Blueprint for the Eager Loading engine.
     */
    protected function hasManyThrough($targetClass, $intermediateClass, $firstKey, $secondKey, $localKey = 'id') {
        $targetInstance = new $targetClass();
        $interInstance = new $intermediateClass();
        
        $targetTable = $targetInstance::$table;
        $interTable = $interInstance::$table;

        // We build a query that includes the JOIN logic
        $query = savvQuery($targetTable)
            ->select("{$targetTable}.*")
            ->join($interTable, "{$targetTable}.{$secondKey}", "{$interTable}.id");

        return [
            'type'        => 'hasManyThrough',
            'query'       => $query,
            'foreignKey'  => "{$interTable}.{$firstKey}", // The link back to the parent
            'localKey'    => $localKey
        ];
    }

    /**
     * Get a new query builder instance for the model.
     *
     * @return \Savv\Utils\Db\SavvQuery
     */
    public static function query() {
        return savvQuery(static::$table);
    }
}
