<?php

namespace App\Controllers;

use App\Services\EmailService;
use App\Models\Booking;
use App\Models\Payment;
use Exception;

class EmailController extends BaseController
{
    private $emailService;
    private $bookingModel;
    private $paymentModel;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService();
        $this->bookingModel = new Booking();
        $this->paymentModel = new Payment();
    }

    /**
     * Email management dashboard
     */
    public function index()
    {
        $this->requireAuth();

        $data = [
            'title' => 'Email Management',
            'queue_stats' => $this->getQueueStats(),
            'email_stats' => $this->getEmailStats()
        ];

        $this->render('admin/email/index', $data);
    }

    /**
     * Send test email
     */
    public function testEmail()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $result = $this->emailService->testConnection();
                
                if ($result) {
                    $this->setSuccess('Test email sent successfully!');
                } else {
                    $this->setError('Failed to send test email. Check configuration.');
                }
            } catch (Exception $e) {
                $this->setError('Email test failed: ' . $e->getMessage());
            }
        }

        $this->redirect(url('admin/emails'));
    }

    /**
     * Send payment reminders
     */
    public function sendPaymentReminders()
    {
        $this->requireAuth();

        try {
            // Get unpaid bookings older than 1 hour
            $unpaidBookings = $this->bookingModel->getUnpaidBookings(1); // 1 hour
            
            $sent = 0;
            $failed = 0;

            foreach ($unpaidBookings as $booking) {
                try {
                    $bookingData = array_merge($booking, [
                        'payment_url' => url("payment/{$booking['id']}")
                    ]);
                    
                    $result = $this->emailService->sendPaymentReminder($bookingData);
                    
                    if ($result) {
                        $sent++;
                        // Update last reminder sent time
                        $this->bookingModel->updateLastReminderSent($booking['id']);
                    } else {
                        $failed++;
                    }
                } catch (Exception $e) {
                    $failed++;
                    $this->logger->error('Payment reminder failed', [
                        'booking_id' => $booking['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->setSuccess("Payment reminders sent: {$sent} successful, {$failed} failed");

        } catch (Exception $e) {
            $this->setError('Failed to send payment reminders: ' . $e->getMessage());
        }

        $this->redirect(url('admin/emails'));
    }

    /**
     * Process email queue
     */
    public function processQueue()
    {
        $this->requireAuth();

        try {
            $result = $this->emailService->processQueue(20); // Process 20 emails
            
            if ($result) {
                $this->setSuccess("Email queue processed: {$result['processed']} sent, {$result['remaining']} remaining");
            } else {
                $this->setInfo('Email queue is empty');
            }

        } catch (Exception $e) {
            $this->setError('Failed to process email queue: ' . $e->getMessage());
        }

        $this->redirect(url('admin/emails'));
    }

    /**
     * Email templates management
     */
    public function templates()
    {
        $this->requireAuth();

        $templates = [
            'booking_confirmation' => 'Booking Confirmation',
            'payment_confirmation' => 'Payment Confirmation',
            'payment_reminder' => 'Payment Reminder',
            'booking_cancellation' => 'Booking Cancellation',
            'admin_notification' => 'Admin Notification'
        ];

        $data = [
            'title' => 'Email Templates',
            'templates' => $templates
        ];

        $this->render('admin/email/templates', $data);
    }

    /**
     * Send custom email
     */
    public function sendCustom()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $recipient = $this->sanitize($_POST['recipient']);
            $subject = $this->sanitize($_POST['subject']);
            $message = $this->sanitize($_POST['message']);

            if (empty($recipient) || empty($subject) || empty($message)) {
                $this->setError('All fields are required');
                $this->redirect(url('admin/emails/custom'));
                return;
            }

            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $this->setError('Invalid email address');
                $this->redirect(url('admin/emails/custom'));
                return;
            }

            try {
                $result = $this->emailService->sendAdminNotification($subject, $message, [
                    'recipient' => $recipient,
                    'sender' => $_SESSION['user']['name'] ?? 'Admin'
                ]);

                if ($result) {
                    $this->setSuccess('Email sent successfully!');
                } else {
                    $this->setError('Failed to send email');
                }

            } catch (Exception $e) {
                $this->setError('Email sending failed: ' . $e->getMessage());
            }

            $this->redirect(url('admin/emails/custom'));
            return;
        }

        $data = [
            'title' => 'Send Custom Email'
        ];

        $this->render('admin/email/custom', $data);
    }

    /**
     * Bulk email sender
     */
    public function bulkEmail()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $recipient_type = $this->sanitize($_POST['recipient_type']);
            $subject = $this->sanitize($_POST['subject']);
            $template = $this->sanitize($_POST['template']);
            $message = $this->sanitize($_POST['message']);

            if (empty($recipient_type) || empty($subject) || empty($message)) {
                $this->setError('All fields are required');
                $this->redirect(url('admin/emails/bulk'));
                return;
            }

            try {
                $recipients = $this->getRecipients($recipient_type);
                
                if (empty($recipients)) {
                    $this->setError('No recipients found for selected type');
                    $this->redirect(url('admin/emails/bulk'));
                    return;
                }

                // Queue bulk emails for processing
                foreach ($recipients as $recipient) {
                    $this->emailService->queueEmail('custom', $recipient, [
                        'subject' => $subject,
                        'message' => $message,
                        'template' => $template
                    ]);
                }

                $this->setSuccess(count($recipients) . ' emails queued for sending');

            } catch (Exception $e) {
                $this->setError('Bulk email setup failed: ' . $e->getMessage());
            }

            $this->redirect(url('admin/emails/bulk'));
            return;
        }

        $data = [
            'title' => 'Bulk Email',
            'recipient_types' => [
                'all_customers' => 'All Customers',
                'recent_customers' => 'Recent Customers (Last 30 days)',
                'unpaid_bookings' => 'Customers with Unpaid Bookings'
            ]
        ];

        $this->render('admin/email/bulk', $data);
    }

    /**
     * Get email queue statistics
     */
    private function getQueueStats()
    {
        $queueFile = __DIR__ . '/../../storage/email_queue.json';
        
        if (!file_exists($queueFile)) {
            return ['total' => 0, 'pending' => 0, 'failed' => 0];
        }

        $queue = json_decode(file_get_contents($queueFile), true);
        
        $pending = 0;
        $failed = 0;

        foreach ($queue as $item) {
            if ($item['attempts'] >= 3) {
                $failed++;
            } else {
                $pending++;
            }
        }

        return [
            'total' => count($queue),
            'pending' => $pending,
            'failed' => $failed
        ];
    }

    /**
     * Get email sending statistics
     */
    private function getEmailStats()
    {
        // This would typically come from a database log table
        // For now, we'll return mock data
        return [
            'today' => 45,
            'this_week' => 312,
            'this_month' => 1247,
            'success_rate' => 98.5
        ];
    }

    /**
     * Get recipients based on type
     */
    private function getRecipients($type)
    {
        switch ($type) {
            case 'all_customers':
                return $this->bookingModel->getAllCustomerEmails();
            
            case 'recent_customers':
                return $this->bookingModel->getRecentCustomerEmails(30);
            
            case 'unpaid_bookings':
                return $this->bookingModel->getUnpaidCustomerEmails();
            
            default:
                return [];
        }
    }
}