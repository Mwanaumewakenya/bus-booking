<?php
/**
 * Payment Controller
 * 
 * Handles M-Pesa payments, callbacks, and payment management
 */

class PaymentController extends BaseController 
{
    private $paymentModel;
    private $bookingModel;
    private $mpesaService;

    public function __construct() 
    {
        parent::__construct();
        $this->paymentModel = new Payment();
        $this->bookingModel = new Booking();
        $this->mpesaService = new MpesaService();
    }

    /**
     * Show payment form
     */
    public function showPaymentForm(int $bookingId): void 
    {
        $booking = $this->bookingModel->getBookingWithDetails($bookingId);
        
        if (!$booking) {
            $this->setError('Booking not found');
            $this->redirect(url());
            return;
        }

        // Check if booking is already paid
        if ($booking['status'] == Booking::STATUS_PAID) {
            $this->setInfo('This booking is already paid');
            $this->redirect(url("booking/{$booking['ref_no']}"));
            return;
        }

        $totalAmount = $booking['qty'] * $booking['price'];
        $existingPayments = $this->paymentModel->getPaymentsByBooking($bookingId);

        $this->view('payment/form', [
            'booking' => $booking,
            'total_amount' => $totalAmount,
            'existing_payments' => $existingPayments,
            'page_title' => 'Pay for Booking - ' . $booking['ref_no']
        ]);
    }

    /**
     * Process M-Pesa payment
     */
    public function processMpesaPayment(): void 
    {
        if (!$this->validateCsrf()) {
            $this->setError('Invalid request. Please try again.');
            $this->back();
            return;
        }

        $bookingId = $this->request->input('booking_id');
        $phoneNumber = trim($this->request->input('phone_number'));
        
        // Validate inputs
        $errors = $this->paymentModel->validatePaymentData([
            'booking_id' => $bookingId,
            'phone_number' => $phoneNumber,
            'payment_method' => Payment::METHOD_MPESA,
            'amount' => 100 // Placeholder, will be calculated
        ]);

        if (!empty($errors)) {
            $_SESSION['payment_errors'] = $errors;
            $this->back();
            return;
        }

        // Get booking details
        $booking = $this->bookingModel->getBookingWithDetails($bookingId);
        if (!$booking) {
            $this->setError('Booking not found');
            $this->back();
            return;
        }

        // Check if already paid
        if ($booking['status'] == Booking::STATUS_PAID) {
            $this->setError('This booking is already paid');
            $this->redirect(url("booking/{$booking['ref_no']}"));
            return;
        }

        $totalAmount = $booking['qty'] * $booking['price'];
        
        // Validate phone number
        $phoneValidation = $this->mpesaService->validatePhoneNumber($phoneNumber);
        if (!$phoneValidation['valid']) {
            $_SESSION['payment_errors'] = ['phone_number' => $phoneValidation['message']];
            $this->back();
            return;
        }

        $formattedPhone = $phoneValidation['formatted_number'];
        $amount = $this->mpesaService->formatAmount($totalAmount);
        $reference = $this->mpesaService->generateReference('BBS');

        try {
            // Initiate STK Push
            $stkResponse = $this->mpesaService->stkPush([
                'amount' => $amount,
                'phone_number' => $formattedPhone,
                'callback_url' => url('api/mpesa/callback'),
                'reference' => $reference,
                'description' => "Bus ticket payment for {$booking['ref_no']}"
            ]);

            if ($stkResponse['success']) {
                // Save payment record
                $paymentId = $this->paymentModel->createMpesaPayment([
                    'booking_id' => $bookingId,
                    'checkout_request_id' => $stkResponse['checkout_request_id'],
                    'merchant_request_id' => $stkResponse['merchant_request_id'],
                    'phone_number' => $formattedPhone,
                    'amount' => $amount,
                    'response_data' => $stkResponse
                ]);

                $this->logActivity('M-Pesa payment initiated', "Booking: {$booking['ref_no']}, Amount: KSH {$amount}");

                $this->setSuccess('Payment request sent to your phone. Please complete the payment on your M-Pesa menu.');
                $this->redirect(url("payment/status/{$paymentId}"));
            } else {
                $this->setError($stkResponse['message']);
                $this->back();
            }

        } catch (Exception $e) {
            error_log("M-Pesa payment error: " . $e->getMessage());
            $this->setError('Payment processing failed. Please try again.');
            $this->back();
        }
    }

    /**
     * Show payment status
     */
    public function showPaymentStatus(int $paymentId): void 
    {
        $payment = $this->paymentModel->getPaymentWithBooking($paymentId);
        
        if (!$payment) {
            $this->setError('Payment not found');
            $this->redirect(url());
            return;
        }

        $this->view('payment/status', [
            'payment' => $payment,
            'page_title' => 'Payment Status'
        ]);
    }

    /**
     * Check payment status via AJAX
     */
    public function checkPaymentStatus(int $paymentId): void 
    {
        $payment = $this->paymentModel->find($paymentId);
        
        if (!$payment) {
            $this->json(['error' => 'Payment not found'], 404);
            return;
        }

        // If still pending, query M-Pesa API
        if ($payment['status'] === Payment::STATUS_PENDING && $payment['checkout_request_id']) {
            $queryResponse = $this->mpesaService->stkQuery($payment['checkout_request_id']);
            
            if ($queryResponse && isset($queryResponse['ResultCode'])) {
                if ($queryResponse['ResultCode'] == '0') {
                    // Payment successful
                    $this->paymentModel->updatePaymentStatus(
                        $paymentId, 
                        Payment::STATUS_COMPLETED,
                        ['transaction_id' => $queryResponse['transactionId'] ?? null]
                    );
                    
                    // Update booking status
                    $this->bookingModel->updateStatus($payment['booking_id'], Booking::STATUS_PAID);
                    
                    $payment['status'] = Payment::STATUS_COMPLETED;
                } elseif (in_array($queryResponse['ResultCode'], ['1032', '1037'])) {
                    // Payment cancelled or timeout
                    $this->paymentModel->updatePaymentStatus(
                        $paymentId, 
                        Payment::STATUS_CANCELLED
                    );
                    
                    $payment['status'] = Payment::STATUS_CANCELLED;
                }
            }
        }

        $this->json([
            'status' => $payment['status'],
            'status_label' => Payment::getStatusLabel($payment['status']),
            'badge_class' => Payment::getStatusBadgeClass($payment['status'])
        ]);
    }

    /**
     * Handle M-Pesa callback
     */
    public function handleMpesaCallback(): void 
    {
        $input = file_get_contents('php://input');
        $callbackData = json_decode($input, true);
        
        // Log callback for debugging
        error_log("M-Pesa Callback: " . $input);
        
        if (!$callbackData) {
            http_response_code(400);
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
            return;
        }

        try {
            $result = $this->mpesaService->processCallback($callbackData);
            
            if (isset($result['checkout_request_id'])) {
                $payment = $this->paymentModel->getByCheckoutRequestId($result['checkout_request_id']);
                
                if ($payment) {
                    if ($result['success']) {
                        // Payment successful
                        $this->paymentModel->completePayment($payment['id'], $result);
                        
                        // Update booking status
                        $this->bookingModel->updateStatus($payment['booking_id'], Booking::STATUS_PAID);
                        
                        $this->logActivity('M-Pesa payment completed', 
                            "Transaction ID: {$result['transaction_id']}, Amount: {$result['amount']}");
                    } else {
                        // Payment failed
                        $this->paymentModel->failPayment($payment['id'], $result['result_desc']);
                        
                        $this->logActivity('M-Pesa payment failed', 
                            "Reason: {$result['result_desc']}, Code: {$result['result_code']}");
                    }
                }
            }
            
            // Acknowledge callback
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
            
        } catch (Exception $e) {
            error_log("M-Pesa callback processing error: " . $e->getMessage());
            
            http_response_code(500);
            echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Processing error']);
        }
    }

    /**
     * Admin: View all payments
     */
    public function index(): void 
    {
        $this->requireAdmin();
        
        $page = (int) $this->request->input('page', 1);
        $perPage = 20;
        
        $payments = $this->paymentModel->paginate($page, $perPage);
        $stats = $this->paymentModel->getPaymentStats();
        
        $this->view('admin/payments/index', [
            'payments' => $payments,
            'stats' => $stats,
            'page_title' => 'Payment Management'
        ]);
    }

    /**
     * Admin: View payment details
     */
    public function show(int $paymentId): void 
    {
        $this->requireAdmin();
        
        $payment = $this->paymentModel->getPaymentWithBooking($paymentId);
        
        if (!$payment) {
            $this->setError('Payment not found');
            $this->redirect(url('admin/payments'));
            return;
        }

        $this->view('admin/payments/show', [
            'payment' => $payment,
            'page_title' => 'Payment Details'
        ]);
    }

    /**
     * Admin: Payment reports
     */
    public function reports(): void 
    {
        $this->requireAdmin();
        
        $stats = $this->paymentModel->getPaymentStats();
        $dailySummary = $this->paymentModel->getDailyPaymentSummary(30);
        $methodStats = $this->paymentModel->getPaymentMethodStats();
        
        $this->view('admin/payments/reports', [
            'stats' => $stats,
            'daily_summary' => $dailySummary,
            'method_stats' => $methodStats,
            'page_title' => 'Payment Reports'
        ]);
    }

    /**
     * Admin: Retry failed payment
     */
    public function retryPayment(int $paymentId): void 
    {
        $this->requireAdmin();
        
        if (!$this->validateCsrf()) {
            $this->setError('Invalid request');
            $this->back();
            return;
        }

        $payment = $this->paymentModel->find($paymentId);
        
        if (!$payment || $payment['status'] !== Payment::STATUS_FAILED) {
            $this->setError('Cannot retry this payment');
            $this->back();
            return;
        }

        try {
            // Reset payment to pending and retry STK push
            $this->paymentModel->updatePaymentStatus($paymentId, Payment::STATUS_PENDING);
            
            $booking = $this->bookingModel->getBookingWithDetails($payment['booking_id']);
            $reference = $this->mpesaService->generateReference('BBS');
            
            $stkResponse = $this->mpesaService->stkPush([
                'amount' => $payment['amount'],
                'phone_number' => $payment['phone_number'],
                'callback_url' => url('api/mpesa/callback'),
                'reference' => $reference,
                'description' => "Bus ticket payment retry for {$booking['ref_no']}"
            ]);

            if ($stkResponse['success']) {
                $this->paymentModel->update($paymentId, [
                    'checkout_request_id' => $stkResponse['checkout_request_id'],
                    'merchant_request_id' => $stkResponse['merchant_request_id'],
                    'response_data' => json_encode($stkResponse)
                ]);
                
                $this->setSuccess('Payment retry initiated successfully');
            } else {
                $this->setError($stkResponse['message']);
            }
            
        } catch (Exception $e) {
            error_log("Payment retry error: " . $e->getMessage());
            $this->setError('Failed to retry payment');
        }
        
        $this->back();
    }
}