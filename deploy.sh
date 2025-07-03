#!/bin/bash

# Bus Booking System - Production Deployment Script
# Version: 2.0.0

set -e

echo "🚀 Starting Bus Booking System Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/bus-booking-system"
BACKUP_DIR="/var/backups/bus-booking"
DB_NAME="bus_booking"
WEB_USER="www-data"

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

check_requirements() {
    print_status "Checking system requirements..."
    
    # Check PHP version
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed"
    fi
    
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    if [[ $(echo "$PHP_VERSION >= 8.0" | bc -l) -eq 0 ]]; then
        print_error "PHP 8.0 or higher is required. Current version: $PHP_VERSION"
    fi
    
    # Check required PHP extensions
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "json" "mbstring" "openssl")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            print_error "Required PHP extension '$ext' is not installed"
        fi
    done
    
    # Check MySQL/MariaDB
    if ! command -v mysql &> /dev/null; then
        print_error "MySQL/MariaDB is not installed"
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed"
    fi
    
    print_success "All requirements met"
}

create_directories() {
    print_status "Creating application directories..."
    
    sudo mkdir -p $APP_DIR
    sudo mkdir -p $BACKUP_DIR
    sudo mkdir -p $APP_DIR/storage/logs
    sudo mkdir -p $APP_DIR/storage/sessions
    
    print_success "Directories created"
}

backup_existing() {
    if [ -d "$APP_DIR" ] && [ "$(ls -A $APP_DIR)" ]; then
        print_status "Creating backup of existing installation..."
        
        TIMESTAMP=$(date +%Y%m%d_%H%M%S)
        BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP.tar.gz"
        
        sudo tar -czf $BACKUP_FILE -C $APP_DIR .
        print_success "Backup created: $BACKUP_FILE"
    fi
}

install_application() {
    print_status "Installing application files..."
    
    # Copy files to application directory
    sudo cp -r . $APP_DIR/
    
    # Set ownership
    sudo chown -R $WEB_USER:$WEB_USER $APP_DIR
    
    # Set permissions
    sudo chmod -R 755 $APP_DIR
    sudo chmod -R 775 $APP_DIR/storage
    sudo chmod -R 775 $APP_DIR/assets
    
    print_success "Application files installed"
}

install_dependencies() {
    print_status "Installing PHP dependencies..."
    
    cd $APP_DIR
    sudo -u $WEB_USER composer install --no-dev --optimize-autoloader --no-interaction
    
    print_success "Dependencies installed"
}

setup_environment() {
    print_status "Setting up environment configuration..."
    
    if [ ! -f "$APP_DIR/.env" ]; then
        sudo -u $WEB_USER cp $APP_DIR/.env.example $APP_DIR/.env
        print_warning "Environment file created from template. Please configure database settings."
    fi
    
    print_success "Environment configuration ready"
}

setup_database() {
    print_status "Setting up database..."
    
    # Check if database exists
    if mysql -e "USE $DB_NAME;" 2>/dev/null; then
        print_warning "Database '$DB_NAME' already exists"
        read -p "Do you want to reset the database? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            mysql -e "DROP DATABASE $DB_NAME;"
            mysql -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
            mysql $DB_NAME < $APP_DIR/database/bus_booking.sql
            print_success "Database reset and imported"
        fi
    else
        mysql -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        mysql $DB_NAME < $APP_DIR/database/bus_booking.sql
        print_success "Database created and imported"
    fi
}

configure_webserver() {
    print_status "Configuring web server..."
    
    # Detect web server
    if command -v apache2 &> /dev/null; then
        configure_apache
    elif command -v nginx &> /dev/null; then
        configure_nginx
    else
        print_warning "No supported web server detected. Please configure manually."
    fi
}

configure_apache() {
    print_status "Configuring Apache..."
    
    SITE_CONFIG="/etc/apache2/sites-available/bus-booking.conf"
    
    sudo tee $SITE_CONFIG > /dev/null <<EOF
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot $APP_DIR/public
    
    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
        
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/bus-booking_error.log
    CustomLog \${APACHE_LOG_DIR}/bus-booking_access.log combined
</VirtualHost>
EOF

    sudo a2enmod rewrite
    sudo a2ensite bus-booking
    sudo systemctl reload apache2
    
    print_success "Apache configured"
}

configure_nginx() {
    print_status "Configuring Nginx..."
    
    SITE_CONFIG="/etc/nginx/sites-available/bus-booking"
    
    sudo tee $SITE_CONFIG > /dev/null <<EOF
server {
    listen 80;
    server_name your-domain.com;
    root $APP_DIR/public;
    index index.php;
    
    access_log /var/log/nginx/bus-booking_access.log;
    error_log /var/log/nginx/bus-booking_error.log;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
EOF

    sudo ln -sf $SITE_CONFIG /etc/nginx/sites-enabled/
    sudo nginx -t && sudo systemctl reload nginx
    
    print_success "Nginx configured"
}

setup_cron_jobs() {
    print_status "Setting up cron jobs..."
    
    # Add log rotation cron job
    CRON_JOB="0 0 * * * cd $APP_DIR && find storage/logs -name '*.log' -mtime +30 -delete"
    
    (sudo crontab -u $WEB_USER -l 2>/dev/null; echo "$CRON_JOB") | sudo crontab -u $WEB_USER -
    
    print_success "Cron jobs configured"
}

final_checks() {
    print_status "Performing final checks..."
    
    # Check if application is accessible
    if curl -f -s http://localhost > /dev/null; then
        print_success "Application is accessible"
    else
        print_warning "Application may not be accessible. Check web server configuration."
    fi
    
    # Check file permissions
    if [ -w "$APP_DIR/storage" ]; then
        print_success "Storage directory is writable"
    else
        print_error "Storage directory is not writable"
    fi
    
    print_success "Final checks completed"
}

show_completion_message() {
    echo
    echo "🎉 Deployment completed successfully!"
    echo
    echo "Next steps:"
    echo "1. Configure your domain name in web server configuration"
    echo "2. Update database credentials in .env file"
    echo "3. Set up SSL certificate for HTTPS"
    echo "4. Change default admin credentials (username: admin, password: admin123)"
    echo "5. Configure email settings for notifications"
    echo
    echo "Application location: $APP_DIR"
    echo "Backup location: $BACKUP_DIR"
    echo
    echo "Access your application at: http://your-domain.com"
    echo "Admin panel: http://your-domain.com/admin"
    echo
}

# Main execution
main() {
    echo "Bus Booking System - Production Deployment"
    echo "=========================================="
    echo
    
    check_requirements
    create_directories
    backup_existing
    install_application
    install_dependencies
    setup_environment
    setup_database
    configure_webserver
    setup_cron_jobs
    final_checks
    show_completion_message
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    print_error "This script must be run as root (use sudo)"
fi

# Run main function
main "$@"