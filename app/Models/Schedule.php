<?php
/**
 * Schedule Model
 * 
 * Handles schedule-related database operations
 */

class Schedule extends BaseModel 
{
    protected $table = 'schedule_list';
    protected $fillable = [
        'bus_id', 'from_location', 'to_location', 'departure_time', 'eta', 'status', 'availability', 'price'
    ];

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * Get active schedules
     */
    public function getActiveSchedules(): array 
    {
        return $this->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Get schedules with related data
     */
    public function getSchedulesWithDetails(): array 
    {
        $sql = "
            SELECT s.*, 
                   b.name as bus_name, 
                   b.bus_number,
                   fl.terminal_name as from_terminal, 
                   fl.city as from_city, 
                   fl.state as from_state,
                   tl.terminal_name as to_terminal, 
                   tl.city as to_city, 
                   tl.state as to_state
            FROM {$this->table} s
            JOIN bus b ON s.bus_id = b.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            ORDER BY s.departure_time DESC
        ";
        
        return $this->query($sql);
    }

    /**
     * Get schedule by ID with details
     */
    public function getScheduleWithDetails(int $scheduleId): ?array 
    {
        $sql = "
            SELECT s.*, 
                   b.name as bus_name, 
                   b.bus_number,
                   fl.terminal_name as from_terminal, 
                   fl.city as from_city, 
                   fl.state as from_state,
                   tl.terminal_name as to_terminal, 
                   tl.city as to_city, 
                   tl.state as to_state
            FROM {$this->table} s
            JOIN bus b ON s.bus_id = b.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            WHERE s.id = :id
        ";
        
        $result = $this->query($sql, [':id' => $scheduleId]);
        return $result[0] ?? null;
    }

    /**
     * Search schedules by route and date
     */
    public function searchSchedules(int $fromLocation, int $toLocation, string $date): array 
    {
        $sql = "
            SELECT s.*, 
                   b.name as bus_name, 
                   b.bus_number,
                   fl.terminal_name as from_terminal, 
                   fl.city as from_city,
                   tl.terminal_name as to_terminal, 
                   tl.city as to_city,
                   (s.availability - COALESCE(b_count.booked_qty, 0)) as available_seats
            FROM {$this->table} s
            JOIN bus b ON s.bus_id = b.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            LEFT JOIN (
                SELECT schedule_id, SUM(qty) as booked_qty 
                FROM booked 
                WHERE status = 1 
                GROUP BY schedule_id
            ) b_count ON s.id = b_count.schedule_id
            WHERE s.from_location = :from_location 
              AND s.to_location = :to_location 
              AND DATE(s.departure_time) = :date
              AND s.status = :status
              AND (s.availability - COALESCE(b_count.booked_qty, 0)) > 0
            ORDER BY s.departure_time
        ";
        
        return $this->query($sql, [
            ':from_location' => $fromLocation,
            ':to_location' => $toLocation,
            ':date' => $date,
            ':status' => self::STATUS_ACTIVE
        ]);
    }

    /**
     * Get available seats for a schedule
     */
    public function getAvailableSeats(int $scheduleId): int 
    {
        $sql = "
            SELECT (s.availability - COALESCE(SUM(b.qty), 0)) as available_seats
            FROM {$this->table} s
            LEFT JOIN booked b ON s.id = b.schedule_id AND b.status = 1
            WHERE s.id = :schedule_id
            GROUP BY s.id
        ";
        
        $result = $this->query($sql, [':schedule_id' => $scheduleId]);
        return (int) ($result[0]['available_seats'] ?? 0);
    }

    /**
     * Get booked seats for a schedule
     */
    public function getBookedSeats(int $scheduleId): int 
    {
        $sql = "
            SELECT COALESCE(SUM(qty), 0) as booked_seats
            FROM booked 
            WHERE schedule_id = :schedule_id AND status = 1
        ";
        
        $result = $this->query($sql, [':schedule_id' => $scheduleId]);
        return (int) ($result[0]['booked_seats'] ?? 0);
    }

    /**
     * Validate schedule data
     */
    public function validateScheduleData(array $data): array 
    {
        $errors = [];
        
        // Validate bus
        if (empty($data['bus_id'])) {
            $errors['bus_id'] = 'Bus is required';
        } else {
            $busModel = new Bus();
            if (!$busModel->find($data['bus_id'])) {
                $errors['bus_id'] = 'Invalid bus selected';
            }
        }
        
        // Validate from location
        if (empty($data['from_location'])) {
            $errors['from_location'] = 'From location is required';
        } else {
            $locationModel = new Location();
            if (!$locationModel->find($data['from_location'])) {
                $errors['from_location'] = 'Invalid from location selected';
            }
        }
        
        // Validate to location
        if (empty($data['to_location'])) {
            $errors['to_location'] = 'To location is required';
        } else {
            $locationModel = new Location();
            if (!$locationModel->find($data['to_location'])) {
                $errors['to_location'] = 'Invalid to location selected';
            }
            
            // Check if from and to locations are different
            if ($data['from_location'] == $data['to_location']) {
                $errors['to_location'] = 'To location must be different from from location';
            }
        }
        
        // Validate departure time
        if (empty($data['departure_time'])) {
            $errors['departure_time'] = 'Departure time is required';
        } elseif (strtotime($data['departure_time']) < time()) {
            $errors['departure_time'] = 'Departure time must be in the future';
        }
        
        // Validate ETA
        if (empty($data['eta'])) {
            $errors['eta'] = 'ETA is required';
        } elseif (!empty($data['departure_time']) && strtotime($data['eta']) <= strtotime($data['departure_time'])) {
            $errors['eta'] = 'ETA must be after departure time';
        }
        
        // Validate availability
        if (!isset($data['availability']) || !is_numeric($data['availability']) || $data['availability'] < 1) {
            $errors['availability'] = 'Availability must be a positive number';
        } elseif ($data['availability'] > 60) {
            $errors['availability'] = 'Availability cannot exceed 60 seats';
        }
        
        // Validate price
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors['price'] = 'Price must be a valid number';
        }
        
        return $errors;
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(int $status): string 
    {
        return $status === self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    /**
     * Check if schedule can be deleted
     */
    public function canDelete(int $scheduleId): bool 
    {
        $bookedModel = new Booking();
        return !$bookedModel->exists(['schedule_id' => $scheduleId]);
    }

    /**
     * Get upcoming schedules
     */
    public function getUpcomingSchedules(int $limit = 10): array 
    {
        $sql = "
            SELECT s.*, 
                   b.name as bus_name, 
                   b.bus_number,
                   fl.city as from_city,
                   tl.city as to_city
            FROM {$this->table} s
            JOIN bus b ON s.bus_id = b.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            WHERE s.departure_time > NOW() AND s.status = :status
            ORDER BY s.departure_time
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', self::STATUS_ACTIVE, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}