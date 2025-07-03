# Email Notification System Setup Guide

## 📧 Complete Email Integration

The bus booking system now includes a comprehensive email notification system with Gmail integration, automated notifications, and admin management tools.

## ✅ Features Implemented

### **1. Core Email Service**
- **EmailService Class**: Full-featured email handling with PHPMailer
- **Gmail Integration**: Pre-configured for Gmail SMTP
- **Template System**: Professional HTML email templates
- **Queue Management**: Email queuing for reliable delivery
- **Error Handling**: Comprehensive logging and error recovery

### **2. Automated Notifications**
- **Booking Confirmation**: Sent immediately after booking creation
- **Payment Confirmation**: Sent when M-Pesa payment completes
- **Payment Reminders**: Automated reminders for unpaid bookings
- **Admin Alerts**: System notifications for administrators

### **3. Email Templates**
- **Responsive Design**: Mobile-friendly HTML templates
- **Professional Styling**: Company branding and modern design
- **Dynamic Content**: Personalized with booking and payment details
- **Multi-format**: HTML and plain text versions

### **4. Admin Management**
- **Email Dashboard**: Queue statistics and sending metrics
- **Test Email**: Verify email configuration
- **Custom Emails**: Send personalized messages
- **Bulk Email**: Mass communication tools
- **Template Management**: Modify email templates

## 🔧 Gmail Setup Instructions

### **Step 1: Enable 2-Factor Authentication**
1. Go to [Google Account Settings](https://myaccount.google.com/)
2. Navigate to "Security"
3. Enable "2-Step Verification"

### **Step 2: Generate App Password**
1. In Google Account Settings → Security
2. Click "App passwords"
3. Select "Mail" and "Other (Custom name)"
4. Enter "Bus Booking System"
5. Copy the 16-character app password

### **Step 3: Configure Environment**
Update your `.env` file with:
```bash
# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=briankirui275@gmail.com
MAIL_PASSWORD=your_16_character_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=briankirui275@gmail.com
MAIL_FROM_NAME="Swift Bus Services"

# Company Information
COMPANY_NAME="Swift Bus Services"
SUPPORT_EMAIL=briankirui275@gmail.com
SUPPORT_PHONE="+254 700 123 456"
ADMIN_EMAILS=briankirui275@gmail.com
```

## 📋 Email Workflow

### **Booking Process:**
1. **Customer books ticket** → Booking confirmation email sent
2. **Customer pays** → Payment confirmation email sent
3. **Payment pending** → Reminder email sent after 1 hour
4. **Booking cancelled** → Cancellation email sent

### **Admin Notifications:**
- New booking alerts
- Payment failure notifications
- System error alerts
- Daily/weekly reports

## 🎨 Email Templates

### **1. Booking Confirmation**
- Booking details and reference number
- Payment instructions with M-Pesa button
- Important travel information
- Company contact details

### **2. Payment Confirmation**
- Transaction details and receipt
- Ticket information
- Journey instructions
- Download/print ticket links

### **3. Payment Reminder**
- Urgency messaging
- Outstanding amount
- Direct payment link
- Booking cancellation warning

## 🚀 Installation & Dependencies

### **Install PHPMailer:**
```bash
composer install
```

### **Create Required Directories:**
```bash
mkdir -p storage/logs
mkdir -p storage/sessions
mkdir -p resources/email_templates
```

### **Set Permissions:**
```bash
chmod 755 storage/
chmod 644 storage/logs/
```

## 🔄 Email Queue System

### **Automatic Processing:**
- Failed emails retry up to 3 times
- Queue processing via admin dashboard
- Background processing capability
- Email delivery tracking

### **Manual Management:**
- Process queue immediately
- View pending emails
- Retry failed emails
- Clear queue if needed

## 🎯 Admin Features

### **Email Dashboard** (`/admin/emails`)
- Queue statistics
- Sending metrics
- Test email functionality
- Quick actions

### **Bulk Email** (`/admin/emails/bulk`)
- Send to all customers
- Target recent customers
- Contact unpaid bookings
- Custom recipient lists

### **Custom Email** (`/admin/emails/custom`)
- Send personalized messages
- Professional templates
- Individual customer contact
- Support communications

## 📊 Email Analytics

### **Tracking Metrics:**
- Emails sent today/week/month
- Success rate percentage
- Queue status
- Failed delivery count

### **Reporting:**
- Email delivery reports
- Customer engagement metrics
- Template performance
- System health monitoring

## 🔧 Configuration Options

### **Email Settings:**
```bash
EMAIL_QUEUE_ENABLED=true          # Enable email queuing
EMAIL_BOOKING_CONFIRMATION=true   # Send booking confirmations
EMAIL_PAYMENT_CONFIRMATION=true   # Send payment confirmations
EMAIL_PAYMENT_REMINDER=true       # Send payment reminders
EMAIL_ADMIN_NOTIFICATIONS=true    # Send admin alerts
```

### **Company Branding:**
```bash
COMPANY_NAME="Your Bus Company"
COMPANY_LOGO_URL=https://domain.com/logo.png
EMAIL_THEME_COLOR=#007bff
```

## 🛠️ Troubleshooting

### **Common Issues:**

**1. Gmail Authentication Failed**
- Verify app password is correct
- Ensure 2FA is enabled
- Check username format

**2. Emails Not Sending**
- Check SMTP settings
- Verify firewall allows port 587
- Test email configuration

**3. Templates Not Loading**
- Check file permissions
- Verify template files exist
- Review template syntax

### **Debug Mode:**
```bash
APP_DEBUG=true
LOG_LEVEL=debug
```

### **Log Files:**
- Email logs: `storage/logs/app.log`
- Error logs: `storage/logs/error.log`
- Queue logs: `storage/email_queue.json`

## ✅ Testing the System

### **1. Test Email Configuration:**
- Go to Admin → Email Management
- Click "Send Test Email"
- Check your inbox

### **2. Test Booking Flow:**
- Create a test booking
- Check for booking confirmation email
- Complete payment
- Verify payment confirmation email

### **3. Test Reminders:**
- Create unpaid booking
- Wait 1+ hours or manually trigger
- Check reminder email delivery

## 🔐 Security Considerations

### **Email Security:**
- App passwords instead of account passwords
- TLS encryption for SMTP
- Input validation on all email fields
- Rate limiting for bulk emails

### **Data Protection:**
- No sensitive data in email logs
- Secure template rendering
- Email queue encryption
- GDPR compliance ready

## 📈 Production Ready

The email system is **fully production-ready** with:

- ✅ Professional email templates
- ✅ Gmail integration configured
- ✅ Automated notification workflows
- ✅ Admin management interface
- ✅ Queue system for reliability
- ✅ Error handling and logging
- ✅ Scalable architecture
- ✅ Security best practices

**Next Step**: Add your Gmail app password to complete the setup!