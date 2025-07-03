<?php
/**
 * Location Model
 * 
 * Handles location/terminal-related database operations
 */

class Location extends BaseModel 
{
    protected $table = 'location';
    protected $fillable = [
        'terminal_name', 'city', 'state', 'status'
    ];

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * Get active locations
     */
    public function getActiveLocations(): array 
    {
        return $this->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(int $status): string 
    {
        return $status === self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    /**
     * Validate location data
     */
    public function validateLocationData(array $data): array 
    {
        $errors = [];
        
        // Validate terminal name
        if (empty($data['terminal_name'])) {
            $errors['terminal_name'] = 'Terminal name is required';
        } elseif (strlen($data['terminal_name']) < 3) {
            $errors['terminal_name'] = 'Terminal name must be at least 3 characters';
        }
        
        // Validate city
        if (empty($data['city'])) {
            $errors['city'] = 'City is required';
        } elseif (strlen($data['city']) < 2) {
            $errors['city'] = 'City must be at least 2 characters';
        }
        
        // Validate state
        if (empty($data['state'])) {
            $errors['state'] = 'State is required';
        } elseif (strlen($data['state']) < 2) {
            $errors['state'] = 'State must be at least 2 characters';
        }
        
        // Validate status
        if (!isset($data['status']) || !in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_INACTIVE])) {
            $errors['status'] = 'Invalid status';
        }
        
        return $errors;
    }

    /**
     * Get locations with schedule count
     */
    public function getLocationsWithScheduleCount(): array 
    {
        $sql = "
            SELECT l.*, 
                   COUNT(DISTINCT s1.id) + COUNT(DISTINCT s2.id) as schedule_count,
                   COUNT(DISTINCT CASE WHEN s1.status = 1 THEN s1.id END) + 
                   COUNT(DISTINCT CASE WHEN s2.status = 1 THEN s2.id END) as active_schedule_count
            FROM {$this->table} l
            LEFT JOIN schedule_list s1 ON l.id = s1.from_location
            LEFT JOIN schedule_list s2 ON l.id = s2.to_location
            GROUP BY l.id
            ORDER BY l.city, l.terminal_name
        ";
        
        return $this->query($sql);
    }

    /**
     * Check if location can be deleted
     */
    public function canDelete(int $locationId): bool 
    {
        $scheduleModel = new Schedule();
        return !$scheduleModel->exists(['from_location' => $locationId]) && 
               !$scheduleModel->exists(['to_location' => $locationId]);
    }

    /**
     * Get unique cities
     */
    public function getUniqueCities(): array 
    {
        $sql = "SELECT DISTINCT city FROM {$this->table} WHERE status = :status ORDER BY city";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', self::STATUS_ACTIVE, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_column($stmt->fetchAll(), 'city');
    }

    /**
     * Get unique states
     */
    public function getUniqueStates(): array 
    {
        $sql = "SELECT DISTINCT state FROM {$this->table} WHERE status = :status ORDER BY state";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', self::STATUS_ACTIVE, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_column($stmt->fetchAll(), 'state');
    }
}