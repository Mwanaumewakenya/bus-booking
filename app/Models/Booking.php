<?php
/**
 * Booking Model
 * 
 * Handles booking-related database operations
 */

class Booking extends BaseModel 
{
    protected $table = 'booked';
    protected $fillable = [
        'schedule_id', 'ref_no', 'name', 'qty', 'status'
    ];

    const STATUS_UNPAID = 0;
    const STATUS_PAID = 1;

    /**
     * Create a new booking with reference number
     */
    public function createBooking(array $data): int 
    {
        // Generate unique reference number
        $data['ref_no'] = $this->generateRefNumber();
        
        return $this->create($data);
    }

    /**
     * Generate unique reference number
     */
    private function generateRefNumber(): string 
    {
        do {
            $refNo = date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->exists(['ref_no' => $refNo]));
        
        return $refNo;
    }

    /**
     * Get booking by reference number
     */
    public function getBookingByRefNo(string $refNo): ?array 
    {
        return $this->first(['ref_no' => $refNo]);
    }

    /**
     * Get booking with schedule details
     */
    public function getBookingWithDetails(int $bookingId): ?array 
    {
        $sql = "
            SELECT b.*, 
                   s.departure_time, 
                   s.eta, 
                   s.price,
                   bus.name as bus_name, 
                   bus.bus_number,
                   fl.terminal_name as from_terminal, 
                   fl.city as from_city,
                   tl.terminal_name as to_terminal, 
                   tl.city as to_city,
                   (b.qty * s.price) as total_amount
            FROM {$this->table} b
            JOIN schedule_list s ON b.schedule_id = s.id
            JOIN bus ON s.bus_id = bus.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            WHERE b.id = :id
        ";
        
        $result = $this->query($sql, [':id' => $bookingId]);
        return $result[0] ?? null;
    }

    /**
     * Get booking details by reference number
     */
    public function getBookingDetailsByRefNo(string $refNo): ?array 
    {
        $sql = "
            SELECT b.*, 
                   s.departure_time, 
                   s.eta, 
                   s.price,
                   bus.name as bus_name, 
                   bus.bus_number,
                   fl.terminal_name as from_terminal, 
                   fl.city as from_city, 
                   fl.state as from_state,
                   tl.terminal_name as to_terminal, 
                   tl.city as to_city, 
                   tl.state as to_state,
                   (b.qty * s.price) as total_amount
            FROM {$this->table} b
            JOIN schedule_list s ON b.schedule_id = s.id
            JOIN bus ON s.bus_id = bus.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            WHERE b.ref_no = :ref_no
        ";
        
        $result = $this->query($sql, [':ref_no' => $refNo]);
        return $result[0] ?? null;
    }

    /**
     * Get all bookings with details
     */
    public function getBookingsWithDetails(): array 
    {
        $sql = "
            SELECT b.*, 
                   s.departure_time, 
                   s.eta, 
                   s.price,
                   bus.name as bus_name, 
                   bus.bus_number,
                   fl.city as from_city,
                   tl.city as to_city,
                   (b.qty * s.price) as total_amount
            FROM {$this->table} b
            JOIN schedule_list s ON b.schedule_id = s.id
            JOIN bus ON s.bus_id = bus.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            ORDER BY b.date_updated DESC
        ";
        
        return $this->query($sql);
    }

    /**
     * Update booking status
     */
    public function updateStatus(int $bookingId, int $status): bool 
    {
        return $this->update($bookingId, ['status' => $status]);
    }

    /**
     * Get paid bookings for a schedule
     */
    public function getPaidBookingsForSchedule(int $scheduleId): array 
    {
        return $this->where([
            'schedule_id' => $scheduleId,
            'status' => self::STATUS_PAID
        ]);
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(): float 
    {
        $sql = "
            SELECT SUM(b.qty * s.price) as total_revenue
            FROM {$this->table} b
            JOIN schedule_list s ON b.schedule_id = s.id
            WHERE b.status = :status
        ";
        
        $result = $this->query($sql, [':status' => self::STATUS_PAID]);
        return (float) ($result[0]['total_revenue'] ?? 0);
    }

    /**
     * Get revenue by date range
     */
    public function getRevenueByDateRange(string $startDate, string $endDate): float 
    {
        $sql = "
            SELECT SUM(b.qty * s.price) as total_revenue
            FROM {$this->table} b
            JOIN schedule_list s ON b.schedule_id = s.id
            WHERE b.status = :status 
              AND DATE(b.date_updated) BETWEEN :start_date AND :end_date
        ";
        
        $result = $this->query($sql, [
            ':status' => self::STATUS_PAID,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return (float) ($result[0]['total_revenue'] ?? 0);
    }

    /**
     * Get booking statistics
     */
    public function getBookingStats(): array 
    {
        $sql = "
            SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = :paid THEN 1 END) as paid_bookings,
                COUNT(CASE WHEN status = :unpaid THEN 1 END) as unpaid_bookings,
                SUM(CASE WHEN status = :paid THEN qty ELSE 0 END) as total_tickets_sold,
                SUM(CASE WHEN status = :paid THEN (qty * (SELECT price FROM schedule_list WHERE id = schedule_id)) ELSE 0 END) as total_revenue
            FROM {$this->table}
        ";
        
        $result = $this->query($sql, [
            ':paid' => self::STATUS_PAID,
            ':unpaid' => self::STATUS_UNPAID
        ]);
        
        return $result[0] ?? [
            'total_bookings' => 0,
            'paid_bookings' => 0,
            'unpaid_bookings' => 0,
            'total_tickets_sold' => 0,
            'total_revenue' => 0
        ];
    }

    /**
     * Validate booking data
     */
    public function validateBookingData(array $data): array 
    {
        $errors = [];
        
        // Validate passenger name
        if (empty($data['name'])) {
            $errors['name'] = 'Passenger name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Passenger name must be at least 2 characters';
        }
        
        // Validate quantity
        if (!isset($data['qty']) || !is_numeric($data['qty']) || $data['qty'] < 1) {
            $errors['qty'] = 'Number of tickets must be at least 1';
        } elseif ($data['qty'] > 10) {
            $errors['qty'] = 'Cannot book more than 10 tickets at once';
        }
        
        // Validate schedule availability
        if (!empty($data['schedule_id']) && !empty($data['qty'])) {
            $scheduleModel = new Schedule();
            $availableSeats = $scheduleModel->getAvailableSeats($data['schedule_id']);
            
            if ($data['qty'] > $availableSeats) {
                $errors['qty'] = "Only {$availableSeats} seats available";
            }
        }
        
        return $errors;
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(int $status): string 
    {
        return $status === self::STATUS_PAID ? 'Paid' : 'Unpaid';
    }

    /**
     * Get recent bookings
     */
    public function getRecentBookings(int $limit = 10): array 
    {
        $sql = "
            SELECT b.*, 
                   s.departure_time,
                   bus.bus_number,
                   fl.city as from_city,
                   tl.city as to_city,
                   (b.qty * s.price) as total_amount
            FROM {$this->table} b
            JOIN schedule_list s ON b.schedule_id = s.id
            JOIN bus ON s.bus_id = bus.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            ORDER BY b.date_updated DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}