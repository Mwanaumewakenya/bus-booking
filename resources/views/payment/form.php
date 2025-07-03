<?php ob_start(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Booking Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Booking Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Reference Number:</strong></td>
                                    <td><?= htmlspecialchars($booking['ref_no']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Passenger Name:</strong></td>
                                    <td><?= htmlspecialchars($booking['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Number of Tickets:</strong></td>
                                    <td><?= $booking['qty'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Price per Ticket:</strong></td>
                                    <td>KSH <?= number_format($booking['price'], 2) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Route:</strong></td>
                                    <td><?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Bus:</strong></td>
                                    <td><?= htmlspecialchars($booking['bus_name']) ?> (<?= htmlspecialchars($booking['bus_number']) ?>)</td>
                                </tr>
                                <tr>
                                    <td><strong>Departure:</strong></td>
                                    <td><?= date('M d, Y - g:i A', strtotime($booking['departure_time'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td class="h5 text-success">KSH <?= number_format($total_amount, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Pay with M-Pesa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg" 
                             alt="M-Pesa" height="40" class="me-3">
                        <div>
                            <h6 class="mb-0">M-Pesa Payment</h6>
                            <small class="text-muted">Fast, secure, and convenient mobile payment</small>
                        </div>
                    </div>

                    <form method="POST" action="<?= url('payment/mpesa') ?>" id="mpesa-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">M-Pesa Phone Number</label>
                                <input type="tel" class="form-control" name="phone_number" 
                                       placeholder="e.g., 0722000000" 
                                       value="<?= old('phone_number') ?>" required>
                                <small class="form-text text-muted">
                                    Enter your M-Pesa registered phone number
                                </small>
                                <?php if (isset($_SESSION['payment_errors']['phone_number'])): ?>
                                    <div class="text-danger small">
                                        <?= $_SESSION['payment_errors']['phone_number'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount to Pay</label>
                                <div class="input-group">
                                    <span class="input-group-text">KSH</span>
                                    <input type="text" class="form-control" 
                                           value="<?= number_format($total_amount, 2) ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Payment Instructions:</h6>
                            <ol class="mb-0">
                                <li>Enter your M-Pesa registered phone number</li>
                                <li>Click "Pay Now" button</li>
                                <li>Check your phone for STK push notification</li>
                                <li>Enter your M-Pesa PIN to complete payment</li>
                            </ol>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Pay Now
                            </button>
                            
                            <a href="<?= url("booking/{$booking['ref_no']}") ?>" 
                               class="btn btn-outline-secondary btn-lg ms-3">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Booking
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#mpesa-form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
    });
});
</script>

<?php 
$content = ob_get_clean();
unset($_SESSION['payment_errors']);
include __DIR__ . '/../layouts/app.php'; 
?>