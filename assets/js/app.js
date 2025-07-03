/**
 * Main Application JavaScript
 */

class BusBookingApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupCSRFToken();
        this.setupFormValidation();
        this.setupAjaxLoading();
        this.setupConfirmDialogs();
        this.setupDatePickers();
        this.setupTooltips();
        this.setupAutoRefresh();
    }

    /**
     * Setup CSRF token for all AJAX requests
     */
    setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token.getAttribute('content')
                }
            });
        }
    }

    /**
     * Setup form validation
     */
    setupFormValidation() {
        // Real-time validation
        $('form input, form select, form textarea').on('blur', function() {
            const field = $(this);
            const value = field.val().trim();
            const required = field.prop('required');
            
            // Clear previous validation
            field.removeClass('is-invalid is-valid');
            field.siblings('.invalid-feedback').remove();
            
            // Required field validation
            if (required && !value) {
                this.showFieldError(field, 'This field is required');
                return;
            }
            
            // Email validation
            if (field.attr('type') === 'email' && value) {
                if (!this.isValidEmail(value)) {
                    this.showFieldError(field, 'Please enter a valid email address');
                    return;
                }
            }
            
            // Minimum length validation
            const minLength = field.attr('minlength');
            if (minLength && value.length < minLength) {
                this.showFieldError(field, `Minimum ${minLength} characters required`);
                return;
            }
            
            // Show valid state
            if (value) {
                field.addClass('is-valid');
            }
        }.bind(this));
    }

    /**
     * Show field validation error
     */
    showFieldError(field, message) {
        field.addClass('is-invalid');
        field.after(`<div class="invalid-feedback">${message}</div>`);
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Setup AJAX loading indicators
     */
    setupAjaxLoading() {
        $(document).ajaxStart(() => {
            this.showLoading();
        });

        $(document).ajaxStop(() => {
            this.hideLoading();
        });

        $(document).ajaxError((event, xhr, settings, error) => {
            this.hideLoading();
            
            if (xhr.status === 419) {
                this.showAlert('Session expired. Please refresh the page.', 'danger');
            } else if (xhr.status === 403) {
                this.showAlert('Access denied.', 'danger');
            } else if (xhr.status === 500) {
                this.showAlert('Server error. Please try again.', 'danger');
            } else {
                this.showAlert('An error occurred. Please try again.', 'danger');
            }
        });
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        if (!$('.loading').length) {
            $('body').append(`
                <div class="loading">
                    <div class="spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            `);
        }
        $('.loading').show();
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        $('.loading').fadeOut();
    }

    /**
     * Setup confirmation dialogs
     */
    setupConfirmDialogs() {
        $(document).on('click', '[data-confirm]', function(e) {
            e.preventDefault();
            
            const message = $(this).data('confirm') || 'Are you sure?';
            const href = $(this).attr('href') || $(this).data('url');
            
            if (confirm(message)) {
                if (href) {
                    window.location.href = href;
                } else {
                    $(this).closest('form').submit();
                }
            }
        });
    }

    /**
     * Setup date pickers
     */
    setupDatePickers() {
        // Set minimum date for travel date inputs
        $('input[type="date"][name="travel_date"]').attr('min', new Date().toISOString().split('T')[0]);
        
        // Set minimum date for departure time inputs
        $('input[type="datetime-local"]').each(function() {
            const now = new Date();
            now.setHours(now.getHours() + 1); // Minimum 1 hour from now
            $(this).attr('min', now.toISOString().slice(0, 16));
        });
    }

    /**
     * Setup tooltips
     */
    setupTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    /**
     * Setup auto-refresh for dashboard
     */
    setupAutoRefresh() {
        if (window.location.pathname.includes('dashboard')) {
            setInterval(() => {
                this.refreshDashboardStats();
            }, 30000); // Refresh every 30 seconds
        }
    }

    /**
     * Refresh dashboard statistics
     */
    refreshDashboardStats() {
        $.get('/api/dashboard/stats')
            .done((data) => {
                this.updateDashboardStats(data);
            })
            .fail(() => {
                console.warn('Failed to refresh dashboard stats');
            });
    }

    /**
     * Update dashboard statistics
     */
    updateDashboardStats(stats) {
        Object.keys(stats).forEach(key => {
            const element = $(`[data-stat="${key}"]`);
            if (element.length) {
                element.text(stats[key]);
            }
        });
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show flash-message" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.flash-message').remove();
        $('body').append(alertHtml);
        
        setTimeout(() => {
            $('.flash-message').fadeOut();
        }, 5000);
    }

    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(amount);
    }

    /**
     * Format date
     */
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    /**
     * Format datetime
     */
    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

/**
 * Booking-specific functionality
 */
class BookingManager {
    constructor() {
        this.setupBookingForm();
        this.setupSeatSelection();
    }

    setupBookingForm() {
        $('#booking-form').on('submit', function(e) {
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            
            submitBtn.prop('disabled', true).text('Processing...');
            
            // Re-enable button after 5 seconds (in case of error)
            setTimeout(() => {
                submitBtn.prop('disabled', false).text('Book Now');
            }, 5000);
        });
    }

    setupSeatSelection() {
        // Get available seats when schedule is selected
        $('select[name="schedule_id"]').on('change', function() {
            const scheduleId = $(this).val();
            if (scheduleId) {
                app.getAvailableSeats(scheduleId);
            }
        });
    }

    getAvailableSeats(scheduleId) {
        $.get(`/api/schedule/${scheduleId}/seats`)
            .done((data) => {
                this.updateSeatInfo(data);
            })
            .fail(() => {
                app.showAlert('Failed to get seat information', 'warning');
            });
    }

    updateSeatInfo(data) {
        const seatInfo = `
            <div class="seat-info mt-3">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Available Seats:</strong> ${data.available_seats}
                    </div>
                    <div class="col-md-4">
                        <strong>Price per Seat:</strong> ₹${data.price}
                    </div>
                    <div class="col-md-4">
                        <strong>Total Seats:</strong> ${data.total_seats}
                    </div>
                </div>
            </div>
        `;
        
        $('.seat-info').remove();
        $('select[name="schedule_id"]').closest('.form-group').after(seatInfo);
        
        // Update quantity max value
        $('select[name="qty"]').attr('max', data.available_seats);
    }
}

// Initialize application
let app, bookingManager;

$(document).ready(() => {
    app = new BusBookingApp();
    bookingManager = new BookingManager();
});

// Export for global access
window.app = app;
window.bookingManager = bookingManager;