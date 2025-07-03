<?php ob_start(); ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Book Your Bus Journey</h1>
                <p class="lead mb-4">Find and book bus tickets easily with our modern booking system. Safe, reliable, and convenient travel.</p>
                <a href="#search-form" class="btn btn-light btn-lg">
                    <i class="fas fa-search me-2"></i>Search Buses
                </a>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-bus" style="font-size: 8rem; opacity: 0.8;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Search Form -->
<section id="search-form" class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">
                            <i class="fas fa-route me-2 text-primary"></i>
                            Search Bus Routes
                        </h3>
                        
                        <form method="POST" action="<?= url('search') ?>">
                            <?= csrf_field() ?>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">From</label>
                                    <select class="form-select" name="from_location" required>
                                        <option value="">Select departure city</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $location['id'] ?>" <?= old('from_location') == $location['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($location['city'] . ' - ' . $location['terminal_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($_SESSION['search_errors']['from_location'])): ?>
                                        <div class="text-danger small"><?= $_SESSION['search_errors']['from_location'] ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">To</label>
                                    <select class="form-select" name="to_location" required>
                                        <option value="">Select destination city</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $location['id'] ?>" <?= old('to_location') == $location['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($location['city'] . ' - ' . $location['terminal_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($_SESSION['search_errors']['to_location'])): ?>
                                        <div class="text-danger small"><?= $_SESSION['search_errors']['to_location'] ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Travel Date</label>
                                    <input type="date" class="form-control" name="travel_date" 
                                           value="<?= old('travel_date', date('Y-m-d')) ?>" 
                                           min="<?= date('Y-m-d') ?>" required>
                                    <?php if (isset($_SESSION['search_errors']['travel_date'])): ?>
                                        <div class="text-danger small"><?= $_SESSION['search_errors']['travel_date'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-search me-2"></i>Search Buses
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Schedules -->
<?php if (!empty($upcoming_schedules)): ?>
<section class="py-5">
    <div class="container">
        <h3 class="text-center mb-5">
            <i class="fas fa-clock me-2 text-primary"></i>
            Upcoming Departures
        </h3>
        
        <div class="row">
            <?php foreach ($upcoming_schedules as $schedule): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title text-primary"><?= htmlspecialchars($schedule['bus_name']) ?></h5>
                                <span class="badge bg-secondary"><?= htmlspecialchars($schedule['bus_number']) ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-map-marker-alt text-success me-2"></i>
                                    <strong><?= htmlspecialchars($schedule['from_city']) ?></strong>
                                    <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                    <strong><?= htmlspecialchars($schedule['to_city']) ?></strong>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-info me-2"></i>
                                    <span><?= date('M d, Y - g:i A', strtotime($schedule['departure_time'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-success mb-0">₹<?= htmlspecialchars($schedule['price']) ?></span>
                                <a href="<?= url("book/{$schedule['id']}") ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-ticket-alt me-1"></i>Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h3 class="text-center mb-5">Why Choose Us?</h3>
        
        <div class="row">
            <div class="col-lg-4 text-center mb-4">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <i class="fas fa-shield-alt text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5>Safe & Secure</h5>
                        <p class="text-muted">Your bookings and payments are completely secure with our advanced security measures.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 text-center mb-4">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <i class="fas fa-clock text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5>24/7 Support</h5>
                        <p class="text-muted">Round-the-clock customer support to help you with any queries or issues.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 text-center mb-4">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <i class="fas fa-mobile-alt text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5>Easy Booking</h5>
                        <p class="text-muted">Simple and quick booking process that takes just a few minutes to complete.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
$content = ob_get_clean();
unset($_SESSION['search_errors']);
include __DIR__ . '/../layouts/app.php'; 
?>