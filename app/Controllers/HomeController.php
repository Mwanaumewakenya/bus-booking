<?php
/**
 * Home Controller
 * 
 * Handles public pages and booking functionality
 */

class HomeController extends BaseController 
{
    private $scheduleModel;
    private $locationModel;
    private $bookingModel;

    public function __construct() 
    {
        parent::__construct();
        $this->scheduleModel = new Schedule();
        $this->locationModel = new Location();
        $this->bookingModel = new Booking();
    }

    /**
     * Display homepage
     */
    public function index(): void 
    {
        $locations = $this->locationModel->getActiveLocations();
        $upcomingSchedules = $this->scheduleModel->getUpcomingSchedules(6);
        
        $this->view('home/index', [
            'locations' => $locations,
            'upcoming_schedules' => $upcomingSchedules,
            'page_title' => 'Bus Booking System'
        ]);
    }

    /**
     * Search for available buses
     */
    public function search(): void 
    {
        $fromLocation = $this->request->input('from_location');
        $toLocation = $this->request->input('to_location');
        $travelDate = $this->request->input('travel_date');

        $errors = [];

        // Validate search inputs
        if (empty($fromLocation)) {
            $errors['from_location'] = 'Please select departure location';
        }

        if (empty($toLocation)) {
            $errors['to_location'] = 'Please select destination location';
        }

        if (empty($travelDate)) {
            $errors['travel_date'] = 'Please select travel date';
        } elseif (strtotime($travelDate) < strtotime(date('Y-m-d'))) {
            $errors['travel_date'] = 'Travel date cannot be in the past';
        }

        if ($fromLocation === $toLocation) {
            $errors['to_location'] = 'Destination must be different from departure location';
        }

        if (!empty($errors)) {
            $_SESSION['search_errors'] = $errors;
            $this->back();
            return;
        }

        // Search for available schedules
        $schedules = $this->scheduleModel->searchSchedules(
            (int) $fromLocation,
            (int) $toLocation,
            $travelDate
        );

        $fromLocationData = $this->locationModel->find((int) $fromLocation);
        $toLocationData = $this->locationModel->find((int) $toLocation);

        $this->view('home/search_results', [
            'schedules' => $schedules,
            'from_location' => $fromLocationData,
            'to_location' => $toLocationData,
            'travel_date' => $travelDate,
            'page_title' => 'Search Results'
        ]);
    }

    /**
     * Show booking form
     */
    public function book(int $scheduleId): void 
    {
        $schedule = $this->scheduleModel->getScheduleWithDetails($scheduleId);
        
        if (!$schedule) {
            $this->setError('Schedule not found');
            $this->redirect(url());
            return;
        }

        $availableSeats = $this->scheduleModel->getAvailableSeats($scheduleId);
        
        if ($availableSeats <= 0) {
            $this->setError('No seats available for this schedule');
            $this->back();
            return;
        }

        $this->view('home/book', [
            'schedule' => $schedule,
            'available_seats' => $availableSeats,
            'page_title' => 'Book Tickets'
        ]);
    }

    /**
     * Process booking
     */
    public function processBook(): void 
    {
        if (!$this->validateCsrf()) {
            $this->setError('Invalid request. Please try again.');
            $this->back();
            return;
        }

        $scheduleId = $this->request->input('schedule_id');
        $name = trim($this->request->input('name'));
        $qty = (int) $this->request->input('qty');

        // Validate booking data
        $errors = $this->bookingModel->validateBookingData([
            'schedule_id' => $scheduleId,
            'name' => $name,
            'qty' => $qty
        ]);

        if (!empty($errors)) {
            $_SESSION['booking_errors'] = $errors;
            $this->back();
            return;
        }

        try {
            $this->bookingModel->beginTransaction();

            // Create booking
            $bookingId = $this->bookingModel->createBooking([
                'schedule_id' => $scheduleId,
                'name' => $name,
                'qty' => $qty,
                'status' => Booking::STATUS_UNPAID
            ]);

            $this->bookingModel->commit();

            // Get booking details
            $booking = $this->bookingModel->getBookingWithDetails($bookingId);

            // Send booking confirmation email
            try {
                $emailService = new \App\Services\EmailService();
                $bookingData = array_merge($booking, [
                    'payment_url' => url("payment/{$bookingId}"),
                    'booking_url' => url("booking/{$booking['ref_no']}")
                ]);
                $emailService->sendBookingConfirmation($bookingData);
            } catch (Exception $e) {
                $this->logger->error('Failed to send booking confirmation email', [
                    'booking_id' => $bookingId,
                    'error' => $e->getMessage()
                ]);
            }

            $this->setSuccess('Booking created successfully! Your reference number is: ' . $booking['ref_no']);
            
            // Redirect to payment if booking is unpaid
            if ($booking['status'] == Booking::STATUS_UNPAID) {
                $this->redirect(url("payment/{$bookingId}"));
            } else {
                $this->redirect(url("booking/{$booking['ref_no']}"));
            }

        } catch (Exception $e) {
            $this->bookingModel->rollback();
            error_log("Booking error: " . $e->getMessage());
            $this->setError('Failed to create booking. Please try again.');
            $this->back();
        }
    }

    /**
     * Show booking details
     */
    public function bookingDetails(string $refNo): void 
    {
        $booking = $this->bookingModel->getBookingDetailsByRefNo($refNo);
        
        if (!$booking) {
            $this->setError('Booking not found');
            $this->redirect(url());
            return;
        }

        $this->view('home/booking_details', [
            'booking' => $booking,
            'page_title' => 'Booking Details - ' . $refNo
        ]);
    }

    /**
     * Search booking by PNR
     */
    public function searchBooking(): void 
    {
        $this->view('home/search_booking', [
            'page_title' => 'Search Booking'
        ]);
    }

    /**
     * Process booking search
     */
    public function processSearchBooking(): void 
    {
        if (!$this->validateCsrf()) {
            $this->setError('Invalid request. Please try again.');
            $this->back();
            return;
        }

        $refNo = trim($this->request->input('ref_no'));
        
        if (empty($refNo)) {
            $this->setError('Please enter a valid reference number');
            $this->back();
            return;
        }

        $booking = $this->bookingModel->getBookingDetailsByRefNo($refNo);
        
        if (!$booking) {
            $this->setError('No booking found with this reference number');
            $this->back();
            return;
        }

        $this->redirect(url("booking/{$refNo}"));
    }

    /**
     * Get available seats for a schedule (AJAX)
     */
    public function getAvailableSeats(int $scheduleId): void 
    {
        $schedule = $this->scheduleModel->find($scheduleId);
        
        if (!$schedule) {
            $this->json(['error' => 'Schedule not found'], 404);
            return;
        }

        $availableSeats = $this->scheduleModel->getAvailableSeats($scheduleId);
        $bookedSeats = $this->scheduleModel->getBookedSeats($scheduleId);

        $this->json([
            'schedule_id' => $scheduleId,
            'total_seats' => $schedule['availability'],
            'available_seats' => $availableSeats,
            'booked_seats' => $bookedSeats,
            'price' => $schedule['price']
        ]);
    }

    /**
     * Print booking ticket
     */
    public function printTicket(string $refNo): void 
    {
        $booking = $this->bookingModel->getBookingDetailsByRefNo($refNo);
        
        if (!$booking) {
            $this->setError('Booking not found');
            $this->redirect(url());
            return;
        }

        $this->view('home/print_ticket', [
            'booking' => $booking,
            'page_title' => 'Print Ticket - ' . $refNo
        ]);
    }
}