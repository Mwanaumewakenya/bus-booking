<?php
/**
 * Payment Model
 * 
 * Handles payment transactions and M-Pesa records
 */

class Payment extends BaseModel 
{
    protected $table = 'payments';
    protected $fillable = [
        'booking_id', 'transaction_id', 'checkout_request_id', 'merchant_request_id',
        'phone_number', 'amount', 'payment_method', 'status', 'response_data'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    const METHOD_MPESA = 'mpesa';
    const METHOD_CASH = 'cash';
    const METHOD_CARD = 'card';

    /**
     * Get payment with booking details
     */
    public function getPaymentWithBooking(int $paymentId): ?array 
    {
        $sql = "
            SELECT p.*, 
                   b.ref_no, 
                   b.name as passenger_name,
                   b.qty,
                   s.departure_time,
                   s.price,
                   bus.bus_number,
                   fl.city as from_city,
                   tl.city as to_city
            FROM {$this->table} p
            JOIN booked b ON p.booking_id = b.id
            JOIN schedule_list s ON b.schedule_id = s.id
            JOIN bus ON s.bus_id = bus.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            WHERE p.id = :payment_id
        ";
        
        $result = $this->query($sql, [':payment_id' => $paymentId]);
        return $result[0] ?? null;
    }

    /**
     * Get payment by checkout request ID
     */
    public function getByCheckoutRequestId(string $checkoutRequestId): ?array 
    {
        return $this->first(['checkout_request_id' => $checkoutRequestId]);
    }

    /**
     * Get payment by transaction ID
     */
    public function getByTransactionId(string $transactionId): ?array 
    {
        return $this->first(['transaction_id' => $transactionId]);
    }

    /**
     * Get payments for a booking
     */
    public function getPaymentsByBooking(int $bookingId): array 
    {
        return $this->where(['booking_id' => $bookingId]);
    }

    /**
     * Get successful payments
     */
    public function getSuccessfulPayments(): array 
    {
        return $this->where(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Get pending payments
     */
    public function getPendingPayments(): array 
    {
        return $this->where(['status' => self::STATUS_PENDING]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $paymentId, string $status, array $additionalData = []): bool 
    {
        $updateData = ['status' => $status];
        
        if (!empty($additionalData)) {
            $updateData = array_merge($updateData, $additionalData);
        }
        
        return $this->update($paymentId, $updateData);
    }

    /**
     * Create M-Pesa payment record
     */
    public function createMpesaPayment(array $data): int 
    {
        $paymentData = [
            'booking_id' => $data['booking_id'],
            'checkout_request_id' => $data['checkout_request_id'] ?? null,
            'merchant_request_id' => $data['merchant_request_id'] ?? null,
            'phone_number' => $data['phone_number'],
            'amount' => $data['amount'],
            'payment_method' => self::METHOD_MPESA,
            'status' => self::STATUS_PENDING,
            'response_data' => json_encode($data['response_data'] ?? [])
        ];
        
        return $this->create($paymentData);
    }

    /**
     * Complete payment with M-Pesa transaction details
     */
    public function completePayment(int $paymentId, array $mpesaData): bool 
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'transaction_id' => $mpesaData['transaction_id'],
            'response_data' => json_encode($mpesaData)
        ];
        
        return $this->update($paymentId, $updateData);
    }

    /**
     * Fail payment with reason
     */
    public function failPayment(int $paymentId, string $reason): bool 
    {
        $updateData = [
            'status' => self::STATUS_FAILED,
            'response_data' => json_encode(['failure_reason' => $reason])
        ];
        
        return $this->update($paymentId, $updateData);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(): array 
    {
        $sql = "
            SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = :completed THEN 1 END) as successful_payments,
                COUNT(CASE WHEN status = :pending THEN 1 END) as pending_payments,
                COUNT(CASE WHEN status = :failed THEN 1 END) as failed_payments,
                SUM(CASE WHEN status = :completed THEN amount ELSE 0 END) as total_revenue,
                COUNT(CASE WHEN payment_method = :mpesa AND status = :completed THEN 1 END) as mpesa_transactions
            FROM {$this->table}
        ";
        
        $result = $this->query($sql, [
            ':completed' => self::STATUS_COMPLETED,
            ':pending' => self::STATUS_PENDING,
            ':failed' => self::STATUS_FAILED,
            ':mpesa' => self::METHOD_MPESA
        ]);
        
        return $result[0] ?? [
            'total_payments' => 0,
            'successful_payments' => 0,
            'pending_payments' => 0,
            'failed_payments' => 0,
            'total_revenue' => 0,
            'mpesa_transactions' => 0
        ];
    }

    /**
     * Get daily payment summary
     */
    public function getDailyPaymentSummary(int $days = 30): array 
    {
        $sql = "
            SELECT 
                DATE(created_at) as payment_date,
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = :completed THEN 1 END) as successful_payments,
                SUM(CASE WHEN status = :completed THEN amount ELSE 0 END) as daily_revenue
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY payment_date DESC
        ";
        
        return $this->query($sql, [
            ':completed' => self::STATUS_COMPLETED,
            ':days' => $days
        ]);
    }

    /**
     * Get payment method distribution
     */
    public function getPaymentMethodStats(): array 
    {
        $sql = "
            SELECT 
                payment_method,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN status = :completed THEN amount ELSE 0 END) as total_amount
            FROM {$this->table}
            WHERE status = :completed
            GROUP BY payment_method
        ";
        
        return $this->query($sql, [':completed' => self::STATUS_COMPLETED]);
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(string $status): string 
    {
        switch ($status) {
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_COMPLETED:
                return 'Completed';
            case self::STATUS_FAILED:
                return 'Failed';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get status badge class
     */
    public static function getStatusBadgeClass(string $status): string 
    {
        switch ($status) {
            case self::STATUS_PENDING:
                return 'bg-warning';
            case self::STATUS_COMPLETED:
                return 'bg-success';
            case self::STATUS_FAILED:
                return 'bg-danger';
            case self::STATUS_CANCELLED:
                return 'bg-secondary';
            default:
                return 'bg-secondary';
        }
    }

    /**
     * Get method label
     */
    public static function getMethodLabel(string $method): string 
    {
        switch ($method) {
            case self::METHOD_MPESA:
                return 'M-Pesa';
            case self::METHOD_CASH:
                return 'Cash';
            case self::METHOD_CARD:
                return 'Card';
            default:
                return ucfirst($method);
        }
    }

    /**
     * Validate payment data
     */
    public function validatePaymentData(array $data): array 
    {
        $errors = [];
        
        // Validate booking ID
        if (empty($data['booking_id'])) {
            $errors['booking_id'] = 'Booking ID is required';
        } else {
            $bookingModel = new Booking();
            if (!$bookingModel->find($data['booking_id'])) {
                $errors['booking_id'] = 'Invalid booking ID';
            }
        }
        
        // Validate phone number for M-Pesa
        if (isset($data['payment_method']) && $data['payment_method'] === self::METHOD_MPESA) {
            if (empty($data['phone_number'])) {
                $errors['phone_number'] = 'Phone number is required for M-Pesa payment';
            } else {
                $mpesaService = new MpesaService();
                $phoneValidation = $mpesaService->validatePhoneNumber($data['phone_number']);
                if (!$phoneValidation['valid']) {
                    $errors['phone_number'] = $phoneValidation['message'];
                }
            }
        }
        
        // Validate amount
        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid amount is required';
        }
        
        return $errors;
    }
}