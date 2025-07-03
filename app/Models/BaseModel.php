<?php
/**
 * Base Model Class
 * 
 * Provides common database operations for all models
 */

abstract class BaseModel 
{
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $dates = ['created_at', 'updated_at'];
    protected $db;

    public function __construct() 
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?array 
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find all records
     */
    public function all(): array 
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Find records with conditions
     */
    public function where(array $conditions): array 
    {
        $whereClause = $this->buildWhereClause($conditions);
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause['clause']}";
        
        $stmt = $this->db->prepare($sql);
        foreach ($whereClause['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Find first record matching conditions
     */
    public function first(array $conditions): ?array 
    {
        $results = $this->where($conditions);
        return $results[0] ?? null;
    }

    /**
     * Create a new record
     */
    public function create(array $data): int 
    {
        $data = $this->filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool 
    {
        $data = $this->filterFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $setClause = '';
        foreach (array_keys($data) as $column) {
            $setClause .= "$column = :$column, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool 
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int 
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions);
            $sql .= " WHERE {$whereClause['clause']}";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($conditions)) {
            foreach ($whereClause['params'] as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }

    /**
     * Check if record exists
     */
    public function exists(array $conditions): bool 
    {
        return $this->count($conditions) > 0;
    }

    /**
     * Paginate results
     */
    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array 
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = $this->buildWhereClause($conditions);
            $sql .= " WHERE {$whereClause['clause']}";
            $params = $whereClause['params'];
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll();
        $total = $this->count($conditions);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Build WHERE clause from conditions
     */
    private function buildWhereClause(array $conditions): array 
    {
        $clause = '';
        $params = [];
        $i = 0;
        
        foreach ($conditions as $column => $value) {
            if ($i > 0) {
                $clause .= ' AND ';
            }
            
            $paramKey = ":where_{$i}";
            $clause .= "{$column} = {$paramKey}";
            $params[$paramKey] = $value;
            $i++;
        }
        
        return [
            'clause' => $clause,
            'params' => $params,
        ];
    }

    /**
     * Filter data to only include fillable fields
     */
    private function filterFillable(array $data): array 
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Raw SQL query
     */
    public function query(string $sql, array $params = []): array 
    {
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool 
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool 
    {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool 
    {
        return $this->db->rollback();
    }
}