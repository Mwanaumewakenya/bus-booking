# Bus Booking System v2.0

A modern, production-ready bus booking and reservation system built with PHP, featuring a sophisticated MVC architecture, secure authentication, and comprehensive admin panel.

## 🚀 Features

### Customer Features
- **Modern Search Interface**: Easy-to-use search for bus routes by location and date
- **Real-time Availability**: Live seat availability checking
- **Online Booking**: Secure ticket booking with reference numbers
- **PNR Tracking**: Search and track bookings using reference numbers
- **Ticket Printing**: Printable ticket format with all journey details
- **Responsive Design**: Mobile-friendly interface

### Admin Features
- **Dashboard**: Comprehensive overview with statistics and analytics
- **User Management**: Complete CRUD operations for system users
- **Bus Management**: Manage bus fleet with detailed information
- **Location Management**: Manage terminals and cities
- **Schedule Management**: Create and manage bus schedules
- **Booking Management**: View, update, and manage all bookings
- **Reports**: Generate detailed booking and revenue reports
- **CSV Export**: Export data for external analysis

### Technical Features
- **MVC Architecture**: Clean, organized code structure
- **Secure Authentication**: Bcrypt password hashing and session management
- **CSRF Protection**: Cross-site request forgery protection
- **Input Validation**: Server-side and client-side validation
- **Database Abstraction**: PDO-based database layer with prepared statements
- **Error Handling**: Comprehensive error logging and user-friendly messages
- **Production Ready**: Optimized for production deployment

## 📋 Requirements

- **PHP**: >= 8.0
- **MySQL**: >= 5.7
- **Extensions**: PDO, JSON, mbstring, OpenSSL
- **Web Server**: Apache/Nginx with mod_rewrite
- **Storage**: 100MB minimum

## 🛠️ Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd bus-booking-system
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
# Edit .env file with your database and configuration settings
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE bus_booking;

# Import database structure
mysql -u root -p bus_booking < database/bus_booking.sql
```

### 5. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/bus-booking-system/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. Set Permissions
```bash
chmod -R 755 storage/
chmod -R 755 assets/
chown -R www-data:www-data storage/
```

## 🔧 Configuration

### Environment Variables (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bus_booking
DB_USERNAME=your_username
DB_PASSWORD=your_password

SESSION_LIFETIME=120
LOG_LEVEL=info
```

### Default Admin Account
- **Username**: admin
- **Password**: admin123
- **Note**: Change default credentials immediately after installation

## 📁 Directory Structure

```
bus-booking-system/
├── app/                    # Application core
│   ├── Controllers/        # Request handlers
│   ├── Models/            # Data models
│   ├── Middleware/        # Request middleware
│   ├── Services/          # Business logic
│   └── Core/              # Core framework classes
├── assets/                # Public assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── img/               # Images
├── bootstrap/             # Application bootstrap
├── config/                # Configuration files
├── database/              # Database files
├── public/                # Web root
├── resources/             # Views and templates
│   └── views/             # Template files
├── routes/                # Route definitions
├── storage/               # Storage directory
│   ├── logs/              # Log files
│   └── sessions/          # Session files
└── tests/                 # Test files
```

## 🚀 Deployment

### Production Deployment
1. **Optimize for Production**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Set Environment**:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Security Headers**: Ensure security headers are configured
4. **SSL Certificate**: Install SSL certificate for HTTPS
5. **Database Backup**: Set up regular database backups
6. **Log Rotation**: Configure log rotation for error logs

### Performance Optimization
- Enable OPcache
- Use Redis/Memcached for sessions
- Configure CDN for static assets
- Enable Gzip compression
- Set up database indexing

## 🔐 Security

### Built-in Security Features
- **Password Hashing**: Bcrypt with configurable rounds
- **CSRF Protection**: All forms protected
- **XSS Prevention**: Input sanitization and output escaping
- **SQL Injection Prevention**: Prepared statements
- **Session Security**: Secure session configuration
- **Input Validation**: Server-side validation
- **Security Headers**: X-Frame-Options, X-XSS-Protection, etc.

### Security Best Practices
1. Change default admin credentials
2. Use strong database passwords
3. Enable HTTPS
4. Regular security updates
5. Monitor error logs
6. Implement rate limiting

## 📊 API Endpoints

### Public API
- `GET /api/schedule/{id}/seats` - Get seat availability
- `POST /search` - Search bus routes
- `POST /book` - Create booking

### Admin API
- `GET /api/dashboard/stats` - Dashboard statistics
- `GET /api/auth/check` - Authentication status

## 🧪 Testing

### Run Tests
```bash
composer test
```

### Code Analysis
```bash
composer analyse
```

### Code Styling
```bash
composer lint
composer lint-fix
```

## 📈 Monitoring

### Log Files
- Application logs: `storage/logs/app.log`
- Error logs: Check web server error logs
- Access logs: Check web server access logs

### Key Metrics to Monitor
- Booking conversion rate
- Revenue trends
- System performance
- Error rates
- User activity

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Common Issues
1. **Database Connection Failed**: Check database credentials in `.env`
2. **Permission Denied**: Ensure proper file permissions
3. **404 Errors**: Check web server rewrite rules
4. **Session Issues**: Verify session configuration

### Getting Help
- Check error logs first
- Review configuration settings
- Ensure all requirements are met
- Verify database structure

## 🚀 Version History

### v2.0.0 (Current)
- Complete rewrite with MVC architecture
- Enhanced security features
- Modern UI/UX design
- Comprehensive admin panel
- Production-ready deployment

### v1.0.0
- Basic booking functionality
- Simple admin interface
- Legacy code structure

---

**Built with ❤️ for reliable bus travel management**