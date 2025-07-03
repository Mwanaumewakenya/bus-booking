<?php ob_start(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Status
                    </h4>
                </div>
                <div class="card-body text-center">
                    <!-- Payment Status Display -->
                    <div id="payment-status" class="mb-4">
                        <span class="badge badge-lg <?= Payment::getStatusBadgeClass($payment['status']) ?>" 
                              style="font-size: 1.2rem; padding: 0.75rem 1.5rem;">
                            <?= Payment::getStatusLabel($payment['status']) ?>
                        </span>
                    </div>

                    <!-- Status Messages -->
                    <div id="status-message" class="mb-4">
                        <?php if ($payment['status'] === Payment::STATUS_PENDING): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Waiting for Payment</strong><br>
                                Please check your phone for the M-Pesa STK push notification and enter your PIN to complete the payment.
                            </div>
                            
                            <!-- Loading animation -->
                            <div class="d-flex justify-content-center mb-3">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Checking payment status...</span>
                                </div>
                            </div>
                            <p class="text-muted">Checking payment status automatically...</p>
                        <?php elseif ($payment['status'] === Payment::STATUS_COMPLETED): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Payment Successful!</strong><br>
                                Your payment has been processed successfully. Transaction ID: <?= htmlspecialchars($payment['transaction_id'] ?? 'N/A') ?>
                            </div>
                        <?php elseif ($payment['status'] === Payment::STATUS_FAILED): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Payment Failed</strong><br>
                                Your payment could not be processed. Please try again.
                            </div>
                        <?php elseif ($payment['status'] === Payment::STATUS_CANCELLED): ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-ban me-2"></i>
                                <strong>Payment Cancelled</strong><br>
                                The payment was cancelled or timed out.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Booking Details -->
                    <div class="booking-details mb-4">
                        <h6>Booking Details</h6>
                        <div class="row text-start">
                            <div class="col-md-6">
                                <small class="text-muted">Reference Number:</small><br>
                                <strong><?= htmlspecialchars($payment['ref_no']) ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Passenger Name:</small><br>
                                <strong><?= htmlspecialchars($payment['passenger_name']) ?></strong>
                            </div>
                        </div>
                        <div class="row text-start mt-2">
                            <div class="col-md-6">
                                <small class="text-muted">Route:</small><br>
                                <strong><?= htmlspecialchars($payment['from_city']) ?> → <?= htmlspecialchars($payment['to_city']) ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Amount Paid:</small><br>
                                <strong class="text-success">KSH <?= number_format($payment['amount'], 2) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($payment['status'] === Payment::STATUS_COMPLETED): ?>
                            <a href="<?= url("booking/{$payment['ref_no']}") ?>" class="btn btn-success btn-lg">
                                <i class="fas fa-ticket-alt me-2"></i>
                                View Ticket
                            </a>
                            <a href="<?= url("print-ticket/{$payment['ref_no']}") ?>" class="btn btn-outline-primary btn-lg ms-2" target="_blank">
                                <i class="fas fa-print me-2"></i>
                                Print Ticket
                            </a>
                        <?php elseif (in_array($payment['status'], [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED])): ?>
                            <a href="<?= url("payment/{$payment['booking_id']}") ?>" class="btn btn-warning btn-lg">
                                <i class="fas fa-redo me-2"></i>
                                Try Again
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?= url() ?>" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-home me-2"></i>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const paymentId = <?= $payment['id'] ?>;
    const currentStatus = '<?= $payment['status'] ?>';
    
    // Only auto-refresh if payment is pending
    if (currentStatus === 'pending') {
        const statusChecker = setInterval(function() {
            checkPaymentStatus();
        }, 3000); // Check every 3 seconds
        
        // Stop checking after 5 minutes
        setTimeout(function() {
            clearInterval(statusChecker);
            if (currentStatus === 'pending') {
                $('#status-message').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Payment Timeout</strong><br>
                        The payment is taking longer than expected. Please refresh the page or contact support if the issue persists.
                    </div>
                `);
            }
        }, 300000); // 5 minutes
    }
    
    function checkPaymentStatus() {
        $.get(`/api/payment/status/${paymentId}`)
            .done(function(response) {
                if (response.status !== currentStatus) {
                    location.reload(); // Reload page to show updated status
                }
            })
            .fail(function() {
                console.log('Failed to check payment status');
            });
    }
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php'; 
?>