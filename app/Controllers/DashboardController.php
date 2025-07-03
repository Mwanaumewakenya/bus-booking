<?php
/**
 * Dashboard Controller
 * 
 * Handles admin dashboard and overview
 */

class DashboardController extends BaseController 
{
    private $userModel;
    private $busModel;
    private $locationModel;
    private $scheduleModel;
    private $bookingModel;

    public function __construct() 
    {
        parent::__construct();
        $this->requireAdmin();
        
        $this->userModel = new User();
        $this->busModel = new Bus();
        $this->locationModel = new Location();
        $this->scheduleModel = new Schedule();
        $this->bookingModel = new Booking();
    }

    /**
     * Show dashboard
     */
    public function index(): void 
    {
        // Get statistics
        $stats = $this->getDashboardStats();
        
        // Get recent activities
        $recentBookings = $this->bookingModel->getRecentBookings(5);
        $upcomingSchedules = $this->scheduleModel->getUpcomingSchedules(5);
        
        $this->view('dashboard/index', [
            'stats' => $stats,
            'recent_bookings' => $recentBookings,
            'upcoming_schedules' => $upcomingSchedules,
            'page_title' => 'Dashboard'
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array 
    {
        $bookingStats = $this->bookingModel->getBookingStats();
        
        return [
            'total_users' => $this->userModel->count(['status' => User::STATUS_ACTIVE]),
            'total_buses' => $this->busModel->count(['status' => Bus::STATUS_ACTIVE]),
            'total_locations' => $this->locationModel->count(['status' => Location::STATUS_ACTIVE]),
            'total_schedules' => $this->scheduleModel->count(['status' => Schedule::STATUS_ACTIVE]),
            'total_bookings' => $bookingStats['total_bookings'],
            'paid_bookings' => $bookingStats['paid_bookings'],
            'unpaid_bookings' => $bookingStats['unpaid_bookings'],
            'total_revenue' => $bookingStats['total_revenue'],
            'tickets_sold' => $bookingStats['total_tickets_sold'],
            'today_revenue' => $this->bookingModel->getRevenueByDateRange(date('Y-m-d'), date('Y-m-d')),
            'month_revenue' => $this->bookingModel->getRevenueByDateRange(date('Y-m-01'), date('Y-m-t')),
        ];
    }

    /**
     * Get statistics for AJAX
     */
    public function getStats(): void 
    {
        $stats = $this->getDashboardStats();
        $this->json($stats);
    }

    /**
     * Show reports
     */
    public function reports(): void 
    {
        $this->view('dashboard/reports', [
            'page_title' => 'Reports'
        ]);
    }

    /**
     * Generate booking report
     */
    public function bookingReport(): void 
    {
        $startDate = $this->request->input('start_date', date('Y-m-01'));
        $endDate = $this->request->input('end_date', date('Y-m-d'));
        
        // Validate date range
        if (strtotime($startDate) > strtotime($endDate)) {
            $this->setError('Start date cannot be later than end date');
            $this->back();
            return;
        }

        $sql = "
            SELECT 
                DATE(b.date_updated) as booking_date,
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN b.status = 1 THEN 1 END) as paid_bookings,
                SUM(CASE WHEN b.status = 1 THEN b.qty ELSE 0 END) as tickets_sold,
                SUM(CASE WHEN b.status = 1 THEN (b.qty * s.price) ELSE 0 END) as revenue
            FROM booked b
            JOIN schedule_list s ON b.schedule_id = s.id
            WHERE DATE(b.date_updated) BETWEEN :start_date AND :end_date
            GROUP BY DATE(b.date_updated)
            ORDER BY booking_date DESC
        ";

        $reportData = $this->bookingModel->query($sql, [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        $this->view('dashboard/booking_report', [
            'report_data' => $reportData,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page_title' => 'Booking Report'
        ]);
    }

    /**
     * Export booking report as CSV
     */
    public function exportBookingReport(): void 
    {
        $startDate = $this->request->input('start_date', date('Y-m-01'));
        $endDate = $this->request->input('end_date', date('Y-m-d'));

        $bookings = $this->bookingModel->query("
            SELECT 
                b.ref_no,
                b.name,
                b.qty,
                b.status,
                b.date_updated,
                s.departure_time,
                s.price,
                (b.qty * s.price) as total_amount,
                bus.bus_number,
                fl.city as from_city,
                tl.city as to_city
            FROM booked b
            JOIN schedule_list s ON b.schedule_id = s.id
            JOIN bus ON s.bus_id = bus.id
            JOIN location fl ON s.from_location = fl.id
            JOIN location tl ON s.to_location = tl.id
            WHERE DATE(b.date_updated) BETWEEN :start_date AND :end_date
            ORDER BY b.date_updated DESC
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="booking_report_' . $startDate . '_to_' . $endDate . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'Reference No', 'Passenger Name', 'Tickets', 'Status', 'Booking Date',
            'Departure Time', 'Price', 'Total Amount', 'Bus Number', 'From', 'To'
        ]);

        // CSV data
        foreach ($bookings as $booking) {
            fputcsv($output, [
                $booking['ref_no'],
                $booking['name'],
                $booking['qty'],
                Booking::getStatusLabel($booking['status']),
                $booking['date_updated'],
                $booking['departure_time'],
                $booking['price'],
                $booking['total_amount'],
                $booking['bus_number'],
                $booking['from_city'],
                $booking['to_city']
            ]);
        }

        fclose($output);
        exit;
    }
}