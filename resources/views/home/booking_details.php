<?php ob_start(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Booking Details
                    </h5>
                    <span class="badge <?= $booking['status'] == 1 ? 'bg-success' : 'bg-warning' ?> fs-6">
                        <?= $booking['status'] == 1 ? 'PAID' : 'UNPAID' ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary">Booking Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Reference Number:</strong></td>
                                    <td class="h6 text-primary"><?= htmlspecialchars($booking['ref_no']) ?></td>
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
                                    <td><strong>Booking Date:</strong></td>
                                    <td><?= date('M d, Y H:i', strtotime($booking['date_updated'])) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Journey Information</h6>
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
                                    <td><strong>Estimated Arrival:</strong></td>
                                    <td><?= date('M d, Y - g:i A', strtotime($booking['eta'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary">Terminal Information</h6>
                            <div class="mb-3">
                                <strong>Departure Terminal:</strong><br>
                                <?= htmlspecialchars($booking['from_terminal']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($booking['from_city']) ?>, <?= htmlspecialchars($booking['from_state']) ?></small>
                            </div>
                            <div>
                                <strong>Arrival Terminal:</strong><br>
                                <?= htmlspecialchars($booking['to_terminal']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($booking['to_city']) ?>, <?= htmlspecialchars($booking['to_state']) ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Payment Summary</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Price per Ticket:</strong></td>
                                    <td>KSH <?= number_format($booking['price'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Number of Tickets:</strong></td>
                                    <td><?= $booking['qty'] ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>Total Amount:</strong></td>
                                    <td class="h5 text-success">KSH <?= number_format($booking['total_amount'], 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Actions -->
                    <?php if ($booking['status'] == 0): // Unpaid ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Payment Required</strong><br>
                            Your booking is confirmed but payment is still pending. Please complete the payment to secure your seats.
                        </div>
                        
                        <div class="text-center mb-4">
                            <a href="<?= url("payment/{$booking['id']}") ?>" class="btn btn-success btn-lg">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Pay with M-Pesa
                            </a>
                            <div class="mt-2">
                                <small class="text-muted">Secure payment via M-Pesa mobile money</small>
                            </div>
                        </div>
                    <?php else: // Paid ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Payment Completed</strong><br>
                            Your booking is fully paid and confirmed. Your seats are secured for this journey.
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <a href="<?= url() ?>" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>
                                Back to Home
                            </a>
                            <a href="<?= url('search-booking') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-search me-2"></i>
                                Search Another Booking
                            </a>
                        </div>
                        <div>
                            <?php if ($booking['status'] == 1): ?>
                                <a href="<?= url("print-ticket/{$booking['ref_no']}") ?>" 
                                   class="btn btn-primary" target="_blank">
                                    <i class="fas fa-print me-2"></i>
                                    Print Ticket
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Important Notes
                    </h6>
                    <ul class="mb-0">
                        <li>Please arrive at the departure terminal at least 30 minutes before departure time</li>
                        <li>Carry a valid ID for verification during boarding</li>
                        <li>Keep your reference number (<?= htmlspecialchars($booking['ref_no']) ?>) for easy identification</li>
                        <?php if ($booking['status'] == 0): ?>
                            <li class="text-warning"><strong>Payment must be completed to guarantee your seat</strong></li>
                        <?php endif; ?>
                        <li>Contact support if you need to modify or cancel your booking</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php'; 
?>