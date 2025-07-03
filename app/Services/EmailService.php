<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Core\Logger;

class EmailService
{
    private $mailer;
    private $logger;
    private $config;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->config = require __DIR__ . '/../../config/email.php';
        $this->initializeMailer();
    }

    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);

        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp']['username'];
            $this->mailer->Password = $this->config['smtp']['password'];
            $this->mailer->SMTPSecure = $this->config['smtp']['encryption'];
            $this->mailer->Port = $this->config['smtp']['port'];

            // Default sender
            $this->mailer->setFrom(
                $this->config['from']['address'],
                $this->config['from']['name']
            );

            // Enable HTML
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';

        } catch (Exception $e) {
            $this->logger->error('Email configuration failed: ' . $e->getMessage());
            throw new Exception('Email service initialization failed');
        }
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($booking)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['email'], $booking['name']);

            $this->mailer->Subject = 'Booking Confirmation - Reference: ' . $booking['ref_no'];
            
            $template = $this->loadTemplate('booking_confirmation', [
                'booking' => $booking,
                'company_name' => $this->config['company']['name'],
                'company_logo' => $this->config['company']['logo_url'],
                'support_email' => $this->config['company']['support_email'],
                'support_phone' => $this->config['company']['support_phone']
            ]);

            $this->mailer->Body = $template['html'];
            $this->mailer->AltBody = $template['text'];

            $this->mailer->send();
            $this->logger->info('Booking confirmation email sent', ['ref_no' => $booking['ref_no']]);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send booking confirmation email', [
                'ref_no' => $booking['ref_no'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($payment, $booking)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['email'], $booking['name']);

            $this->mailer->Subject = 'Payment Confirmation - ' . $booking['ref_no'];
            
            $template = $this->loadTemplate('payment_confirmation', [
                'payment' => $payment,
                'booking' => $booking,
                'company_name' => $this->config['company']['name'],
                'company_logo' => $this->config['company']['logo_url'],
                'support_email' => $this->config['company']['support_email']
            ]);

            $this->mailer->Body = $template['html'];
            $this->mailer->AltBody = $template['text'];

            $this->mailer->send();
            $this->logger->info('Payment confirmation email sent', [
                'ref_no' => $booking['ref_no'],
                'payment_id' => $payment['id']
            ]);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send payment confirmation email', [
                'ref_no' => $booking['ref_no'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send payment reminder email
     */
    public function sendPaymentReminder($booking)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['email'], $booking['name']);

            $this->mailer->Subject = 'Payment Reminder - ' . $booking['ref_no'];
            
            $template = $this->loadTemplate('payment_reminder', [
                'booking' => $booking,
                'payment_url' => url("payment/{$booking['id']}"),
                'company_name' => $this->config['company']['name'],
                'support_email' => $this->config['company']['support_email']
            ]);

            $this->mailer->Body = $template['html'];
            $this->mailer->AltBody = $template['text'];

            $this->mailer->send();
            $this->logger->info('Payment reminder email sent', ['ref_no' => $booking['ref_no']]);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send payment reminder email', [
                'ref_no' => $booking['ref_no'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send booking cancellation email
     */
    public function sendBookingCancellation($booking, $reason = '')
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['email'], $booking['name']);

            $this->mailer->Subject = 'Booking Cancelled - ' . $booking['ref_no'];
            
            $template = $this->loadTemplate('booking_cancellation', [
                'booking' => $booking,
                'reason' => $reason,
                'company_name' => $this->config['company']['name'],
                'support_email' => $this->config['company']['support_email']
            ]);

            $this->mailer->Body = $template['html'];
            $this->mailer->AltBody = $template['text'];

            $this->mailer->send();
            $this->logger->info('Booking cancellation email sent', ['ref_no' => $booking['ref_no']]);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send booking cancellation email', [
                'ref_no' => $booking['ref_no'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send ticket as email attachment
     */
    public function sendTicket($booking, $ticketPdf)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($booking['email'], $booking['name']);

            $this->mailer->Subject = 'Your Bus Ticket - ' . $booking['ref_no'];
            
            $template = $this->loadTemplate('ticket_email', [
                'booking' => $booking,
                'company_name' => $this->config['company']['name'],
                'support_email' => $this->config['company']['support_email']
            ]);

            $this->mailer->Body = $template['html'];
            $this->mailer->AltBody = $template['text'];

            // Add ticket as attachment
            $this->mailer->addStringAttachment(
                $ticketPdf,
                "ticket_{$booking['ref_no']}.pdf",
                'base64',
                'application/pdf'
            );

            $this->mailer->send();
            $this->logger->info('Ticket email sent', ['ref_no' => $booking['ref_no']]);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send ticket email', [
                'ref_no' => $booking['ref_no'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send admin notification
     */
    public function sendAdminNotification($subject, $message, $data = [])
    {
        try {
            $this->mailer->clearAddresses();
            
            $adminEmails = $this->config['admin']['emails'];
            foreach ($adminEmails as $email) {
                $this->mailer->addAddress($email);
            }

            $this->mailer->Subject = '[Bus Booking System] ' . $subject;
            
            $template = $this->loadTemplate('admin_notification', [
                'subject' => $subject,
                'message' => $message,
                'data' => $data,
                'timestamp' => date('Y-m-d H:i:s'),
                'company_name' => $this->config['company']['name']
            ]);

            $this->mailer->Body = $template['html'];
            $this->mailer->AltBody = $template['text'];

            $this->mailer->send();
            $this->logger->info('Admin notification sent', ['subject' => $subject]);
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to send admin notification', [
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send bulk email (for newsletters, promotions)
     */
    public function sendBulkEmail($recipients, $subject, $templateName, $data = [])
    {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($recipient['email'], $recipient['name'] ?? '');

                $this->mailer->Subject = $subject;
                
                $templateData = array_merge($data, [
                    'recipient' => $recipient,
                    'company_name' => $this->config['company']['name']
                ]);

                $template = $this->loadTemplate($templateName, $templateData);
                $this->mailer->Body = $template['html'];
                $this->mailer->AltBody = $template['text'];

                $this->mailer->send();
                $sent++;

                // Small delay to avoid overwhelming SMTP server
                usleep(100000); // 0.1 seconds

            } catch (Exception $e) {
                $failed++;
                $this->logger->error('Bulk email failed for recipient', [
                    'email' => $recipient['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Bulk email completed', [
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($recipients)
        ]);

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Load email template
     */
    private function loadTemplate($templateName, $data = [])
    {
        $templatePath = __DIR__ . "/../../resources/email_templates/{$templateName}";
        
        $htmlFile = $templatePath . '.html';
        $textFile = $templatePath . '.txt';

        if (!file_exists($htmlFile)) {
            throw new Exception("Email template not found: {$htmlFile}");
        }

        // Load HTML template
        $htmlContent = file_get_contents($htmlFile);
        
        // Load text template (fallback to strip HTML if not exists)
        $textContent = file_exists($textFile) 
            ? file_get_contents($textFile)
            : strip_tags($htmlContent);

        // Replace variables
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $htmlContent = str_replace("{{$key}}", $value, $htmlContent);
                $textContent = str_replace("{{$key}}", $value, $textContent);
            }
        }

        return [
            'html' => $htmlContent,
            'text' => $textContent
        ];
    }

    /**
     * Test email configuration
     */
    public function testConnection()
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($this->config['test']['email']);
            $this->mailer->Subject = 'Bus Booking System - Email Test';
            $this->mailer->Body = 'This is a test email to verify email configuration.';
            
            $this->mailer->send();
            return true;

        } catch (Exception $e) {
            $this->logger->error('Email test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue email for later sending (basic implementation)
     */
    public function queueEmail($type, $recipient, $data)
    {
        $queueData = [
            'type' => $type,
            'recipient' => $recipient,
            'data' => $data,
            'created_at' => date('Y-m-d H:i:s'),
            'attempts' => 0
        ];

        $queueFile = __DIR__ . '/../../storage/email_queue.json';
        $queue = file_exists($queueFile) ? json_decode(file_get_contents($queueFile), true) : [];
        
        $queue[] = $queueData;
        file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Process email queue
     */
    public function processQueue($limit = 10)
    {
        $queueFile = __DIR__ . '/../../storage/email_queue.json';
        
        if (!file_exists($queueFile)) {
            return;
        }

        $queue = json_decode(file_get_contents($queueFile), true);
        $processed = [];
        $remaining = [];
        $count = 0;

        foreach ($queue as $item) {
            if ($count >= $limit) {
                $remaining[] = $item;
                continue;
            }

            try {
                $success = false;
                
                switch ($item['type']) {
                    case 'booking_confirmation':
                        $success = $this->sendBookingConfirmation($item['data']);
                        break;
                    case 'payment_confirmation':
                        $success = $this->sendPaymentConfirmation($item['data']['payment'], $item['data']['booking']);
                        break;
                    case 'payment_reminder':
                        $success = $this->sendPaymentReminder($item['data']);
                        break;
                }

                if ($success) {
                    $processed[] = $item;
                    $count++;
                } else {
                    $item['attempts']++;
                    if ($item['attempts'] < 3) {
                        $remaining[] = $item;
                    }
                }

            } catch (Exception $e) {
                $item['attempts']++;
                if ($item['attempts'] < 3) {
                    $remaining[] = $item;
                }
            }
        }

        file_put_contents($queueFile, json_encode($remaining, JSON_PRETTY_PRINT));
        
        return [
            'processed' => count($processed),
            'remaining' => count($remaining)
        ];
    }
}