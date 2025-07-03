<?php
/**
 * Web Routes
 * 
 * Define all web routes for the application
 */

// Get the app instance from the calling code
global $app;

// Public Routes
$app->get('/', [HomeController::class, 'index']);
$app->get('/home', [HomeController::class, 'index']);
$app->post('/search', [HomeController::class, 'search']);
$app->get('/book/{id}', [HomeController::class, 'book']);
$app->post('/book', [HomeController::class, 'processBook']);
$app->get('/booking/{refno}', [HomeController::class, 'bookingDetails']);
$app->get('/search-booking', [HomeController::class, 'searchBooking']);
$app->post('/search-booking', [HomeController::class, 'processSearchBooking']);
$app->get('/print-ticket/{refno}', [HomeController::class, 'printTicket']);

// AJAX Routes
$app->get('/api/schedule/{id}/seats', [HomeController::class, 'getAvailableSeats']);

// Authentication Routes
$app->get('/admin', [AuthController::class, 'showLogin']);
$app->post('/admin/login', [AuthController::class, 'login']);
$app->post('/logout', [AuthController::class, 'logout']);
$app->get('/api/auth/check', [AuthController::class, 'checkAuth']);

// Admin Dashboard Routes
$app->get('/dashboard', [DashboardController::class, 'index']);
$app->get('/reports', [DashboardController::class, 'reports']);
$app->get('/reports/bookings', [DashboardController::class, 'bookingReport']);
$app->get('/reports/bookings/export', [DashboardController::class, 'exportBookingReport']);
$app->get('/api/dashboard/stats', [DashboardController::class, 'getStats']);

// User Management Routes
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/create', [UserController::class, 'create']);
$app->post('/users', [UserController::class, 'store']);
$app->get('/users/{id}', [UserController::class, 'show']);
$app->get('/users/{id}/edit', [UserController::class, 'edit']);
$app->post('/users/{id}', [UserController::class, 'update']);
$app->post('/users/{id}/delete', [UserController::class, 'delete']);

// Bus Management Routes
$app->get('/buses', [BusController::class, 'index']);
$app->get('/buses/create', [BusController::class, 'create']);
$app->post('/buses', [BusController::class, 'store']);
$app->get('/buses/{id}', [BusController::class, 'show']);
$app->get('/buses/{id}/edit', [BusController::class, 'edit']);
$app->post('/buses/{id}', [BusController::class, 'update']);
$app->post('/buses/{id}/delete', [BusController::class, 'delete']);

// Location Management Routes
$app->get('/locations', [LocationController::class, 'index']);
$app->get('/locations/create', [LocationController::class, 'create']);
$app->post('/locations', [LocationController::class, 'store']);
$app->get('/locations/{id}', [LocationController::class, 'show']);
$app->get('/locations/{id}/edit', [LocationController::class, 'edit']);
$app->post('/locations/{id}', [LocationController::class, 'update']);
$app->post('/locations/{id}/delete', [LocationController::class, 'delete']);

// Schedule Management Routes
$app->get('/schedules', [ScheduleController::class, 'index']);
$app->get('/schedules/create', [ScheduleController::class, 'create']);
$app->post('/schedules', [ScheduleController::class, 'store']);
$app->get('/schedules/{id}', [ScheduleController::class, 'show']);
$app->get('/schedules/{id}/edit', [ScheduleController::class, 'edit']);
$app->post('/schedules/{id}', [ScheduleController::class, 'update']);
$app->post('/schedules/{id}/delete', [ScheduleController::class, 'delete']);

// Booking Management Routes
$app->get('/bookings', [BookingController::class, 'index']);
$app->get('/bookings/create', [BookingController::class, 'create']);
$app->post('/bookings', [BookingController::class, 'store']);
$app->get('/bookings/{id}', [BookingController::class, 'show']);
$app->get('/bookings/{id}/edit', [BookingController::class, 'edit']);
$app->post('/bookings/{id}', [BookingController::class, 'update']);
$app->post('/bookings/{id}/delete', [BookingController::class, 'delete']);
$app->post('/bookings/{id}/toggle-status', [BookingController::class, 'toggleStatus']);

// Account Management Routes
$app->get('/profile', [ProfileController::class, 'show']);
$app->post('/profile', [ProfileController::class, 'update']);
$app->get('/profile/password', [ProfileController::class, 'showChangePassword']);
$app->post('/profile/password', [ProfileController::class, 'updatePassword']);