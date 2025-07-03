<?php
/**
 * Bus Model
 * 
 * Handles bus-related database operations
 */

class Bus extends BaseModel 
{
    protected $table = 'bus';
    protected $fillable = [
        'name', 'bus_number', 'status'
    ];

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * Get active buses
     */
    public function getActiveBuses(): array 
    {
        return $this->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Check if bus number exists
     */
    public function busNumberExists(string $busNumber, int $excludeId = null): bool 
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE bus_number = :bus_number AND id != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':bus_number', $busNumber);
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return (int) $result['count'] > 0;
        }
        
        return $this->exists(['bus_number' => $busNumber]);
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(int $status): string 
    {
        return $status === self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    /**
     * Validate bus data
     */
    public function validateBusData(array $data, int $busId = null): array 
    {
        $errors = [];
        
        // Validate name
        if (empty($data['name'])) {
            $errors['name'] = 'Bus name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Bus name must be at least 2 characters';
        }
        
        // Validate bus number
        if (empty($data['bus_number'])) {
            $errors['bus_number'] = 'Bus number is required';
        } elseif ($this->busNumberExists($data['bus_number'], $busId)) {
            $errors['bus_number'] = 'Bus number already exists';
        }
        
        // Validate status
        if (!isset($data['status']) || !in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_INACTIVE])) {
            $errors['status'] = 'Invalid status';
        }
        
        return $errors;
    }

    /**
     * Get bus with schedules count
     */
    public function getBusesWithScheduleCount(): array 
    {
        $sql = "
            SELECT b.*, 
                   COUNT(s.id) as schedule_count,
                   COUNT(CASE WHEN s.status = 1 THEN 1 END) as active_schedule_count
            FROM {$this->table} b
            LEFT JOIN schedule_list s ON b.id = s.bus_id
            GROUP BY b.id
            ORDER BY b.name
        ";
        
        return $this->query($sql);
    }

    /**
     * Check if bus can be deleted
     */
    public function canDelete(int $busId): bool 
    {
        $scheduleModel = new Schedule();
        return !$scheduleModel->exists(['bus_id' => $busId]);
    }
}