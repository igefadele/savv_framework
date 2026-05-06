<?php
namespace Savv\Utils\Db;
use Savv\Utils\Db\SavvCache;

/**
 * SavvModel: The Entity Base
 * This is what the user's models (e.g., class Post extends SavvModel) will use.
 */
abstract class SavvModel {
    protected static string $table;
    protected array $attributes = [];
    protected array $original = []; // Track changes
    protected array $relations = [];

    public function __construct(array $attributes = []) {
        $this->attributes = $attributes;
        $this->original = $attributes;
    }

    public function setRelation(string $name, mixed $value): void {
        $this->relations[$name] = $value;
    }

    // Updated Magic Getter to check relations first
    public function __get(string $key): mixed {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        return SavvCache::getMeta($this->attributes['id'] ?? null, $key);
    }

    public function __set(string $key, mixed $value): void {
        $this->attributes[$key] = $value;
    }

    public static function find(string|int $id): ?self {
        $db = SavvDb::getInstance();
        $data = $db->query("SELECT * FROM " . static::$table . " WHERE id = ? LIMIT 1", [$id])->fetch();
        return $data ? new static($data) : null;
    }

    public function save(): bool {
        $db = SavvDb::getInstance();
        $id = $this->attributes['id'] ?? null;
        $exists = !empty($this->attributes['id']);

        $before = $exists ? 'updating' : 'creating';
        if (SavvEvent::fire(static::class . "@$before", $this) === false) return false;

        if ($id) {
            $diff = array_diff_assoc($this->attributes, $this->original);
            if (empty($diff)) return true;

            $fields = implode(' = ?, ', array_keys($diff)) . ' = ?';
            $db->query("UPDATE " . static::$table . " SET $fields WHERE id = ?", [...array_values($diff), $id]);
        } else {
            $columns = implode(', ', array_keys($this->attributes));
            $placeholders = implode(', ', array_fill(0, count($this->attributes), '?'));
            $db->query("INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)", array_values($this->attributes));
            $this->attributes['id'] = $db->lastInsertId();
        }

        $this->original = $this->attributes; 

        $after = $exists ? 'updated' : 'created';
        SavvEvent::fire(static::class . "@$after", $this);
        return true;
    }

    /**
     * Get a new query builder instance for the model.
     *
     * @return \Savv\Utils\Db\SavvQuery
     */
    public static function query(): SavvQuery { 
        return savvQuery(static::$table)->setModel(static::class);
    }

    public function delete(): bool {
        if (empty($this->attributes['id'])) return false;
        if (SavvEvent::fire(static::class . "@deleting", $this) === false) return false;

        $db = SavvDb::getInstance();
        $db->query("DELETE FROM " . static::$table . " WHERE id = ?", [$this->attributes['id']]);

        SavvEvent::fire(static::class . "@deleted", $this);
        return true;
    }

    /**
     * One-to-One: A User has one Profile
     */
    protected function hasOne(string $relatedClass, string $foreignKey, string $localKey = 'id'): array {
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
    protected function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): array {
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
    protected function belongsTo(string $relatedClass, string $foreignKey, string $ownerKey = 'id'): array {
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
    protected function hasManyThrough(string $targetClass, string $intermediateClass, string $firstKey, string $secondKey, string $localKey = 'id'): array {
        $targetInstance = new $targetClass();
        $interInstance = new $intermediateClass();
        
        $targetTable = $targetInstance::$table;
        $interTable = $interInstance::$table;

        // We build a query that includes the JOIN logic
        $query = savvQuery($targetTable)
            ->select("{$targetTable}.*")
            ->join($interTable, "{$targetTable}.{$secondKey}", "{$interTable}.id");

        $query = savvQuery($targetTable)
            ->select("{$targetTable}.*, {$interTable}.{$firstKey} as __through_key")
            ->join($interTable, "{$targetTable}.{$secondKey}", "{$interTable}.id");

        return [
            'type'        => 'hasManyThrough',
            'query'       => $query,
            'foreignKey'  => '__through_key', // The link back to the parent through the intermediate table
            'localKey'    => $localKey
        ];
    }


    /** Fluent Event Registration 
     * Example: User::creating(fn($u) => $u->password = password_hash($u->password, PASSWORD_BCRYPT));
     * This will listen to the "creating" event for the User model and execute the callback before the 
     * record is created. The callback receives the model instance as a parameter, allowing you 
     * to modify it before it's saved to the database.
    */
    public static function creating(callable $cb): void { SavvEvent::listen(static::class."@creating", $cb); }
    public static function created(callable $cb): void  { SavvEvent::listen(static::class."@created", $cb); }
    public static function updating(callable $cb): void { SavvEvent::listen(static::class."@updating", $cb); }
    public static function updated(callable $cb): void  { SavvEvent::listen(static::class."@updated", $cb); }
    public static function deleting(callable $cb): void { SavvEvent::listen(static::class."@deleting", $cb); }
    public static function deleted(callable $cb): void  { SavvEvent::listen(static::class."@deleted", $cb); }

    public static function on(string $event, callable $cb): void { SavvEvent::listen(static::class."@{$event}", $cb); }
    
    public function trigger(string $event): mixed { return SavvEvent::fire(static::class."@{$event}", $this); }

    public function toArray(): array {
        return $this->attributes;
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}
