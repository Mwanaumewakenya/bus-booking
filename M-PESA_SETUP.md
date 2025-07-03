# M-Pesa Integration Setup Guide

This guide will help you set up M-Pesa payment integration for the Bus Booking System.

## Prerequisites

1. **Safaricom M-Pesa Developer Account**
   - Visit [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
   - Create an account and verify your identity
   - Access the M-Pesa API documentation

2. **Business Registration**
   - Your business must be registered with Safaricom
   - You need a Till Number or Paybill Number
   - Complete the KYC (Know Your Customer) process

## Step 1: Create M-Pesa App

1. **Login to Developer Portal**
   - Go to https://developer.safaricom.co.ke/
   - Sign in with your credentials

2. **Create New App**
   - Click "Create App"
   - Choose "Lipa Na M-Pesa Online"
   - Fill in app details:
     - App Name: "Bus Booking System"
     - Description: "Online bus ticket booking and payment"

3. **Get API Credentials**
   - Consumer Key
   - Consumer Secret
   - Passkey (for STK Push)

## Step 2: Configure Environment

Add the following to your `.env` file:

```bash
# M-Pesa Configuration
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here
MPESA_BUSINESS_SHORTCODE=174379
MPESA_PASSKEY=your_passkey_here
MPESA_CALLBACK_URL=https://yourdomain.com/api/mpesa/callback
```

### Environment Settings:

- **MPESA_ENVIRONMENT**: Set to `sandbox` for testing, `production` for live
- **MPESA_CONSUMER_KEY**: From your app in the developer portal
- **MPESA_CONSUMER_SECRET**: From your app in the developer portal
- **MPESA_BUSINESS_SHORTCODE**: Your Till/Paybill number (use 174379 for sandbox)
- **MPESA_PASSKEY**: Lipa Na M-Pesa Online Passkey
- **MPESA_CALLBACK_URL**: Your domain callback URL for payment notifications

## Step 3: Sandbox Testing

### Sandbox Credentials:
```bash
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=your_sandbox_consumer_key
MPESA_CONSUMER_SECRET=your_sandbox_consumer_secret
MPESA_BUSINESS_SHORTCODE=174379
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
```

### Test Phone Numbers:
- 254708374149
- 254711764200
- 254714901185

### Test PIN: 1234

## Step 4: Production Setup

1. **Go Live Process**
   - Complete business verification
   - Submit required documents
   - Get production credentials

2. **Production Configuration**
   ```bash
   MPESA_ENVIRONMENT=production
   MPESA_CONSUMER_KEY=your_production_consumer_key
   MPESA_CONSUMER_SECRET=your_production_consumer_secret
   MPESA_BUSINESS_SHORTCODE=your_actual_shortcode
   MPESA_PASSKEY=your_production_passkey
   ```

## Step 5: SSL Certificate (Required)

M-Pesa requires HTTPS for callback URLs:

1. **Get SSL Certificate**
   - Use Let's Encrypt (free)
   - Or purchase from SSL provider

2. **Configure Web Server**
   ```bash
   # For Apache
   sudo a2enmod ssl
   sudo systemctl restart apache2
   
   # For Nginx
   sudo nginx -t
   sudo systemctl restart nginx
   ```

## Step 6: Callback URL Setup

Your callback URL must be accessible from the internet:

1. **Firewall Configuration**
   ```bash
   # Allow HTTPS traffic
   sudo ufw allow 443
   sudo systemctl restart ufw
   ```

2. **Test Callback URL**
   ```bash
   curl -X POST https://yourdomain.com/api/mpesa/callback \
        -H "Content-Type: application/json" \
        -d '{"test": "callback"}'
   ```

## Step 7: Testing the Integration

1. **Create Test Booking**
   - Go to your booking system
   - Create a new booking
   - Proceed to payment

2. **Test M-Pesa Payment**
   - Enter test phone number
   - Check for STK push notification
   - Enter PIN: 1234
   - Verify payment completion

3. **Check Logs**
   ```bash
   tail -f storage/logs/app.log
   ```

## Troubleshooting

### Common Issues:

1. **Invalid Consumer Key/Secret**
   - Verify credentials in developer portal
   - Check environment configuration

2. **Callback URL Not Reachable**
   - Ensure HTTPS is configured
   - Check firewall settings
   - Verify domain DNS

3. **STK Push Not Received**
   - Check phone number format (+254...)
   - Verify business shortcode
   - Check passkey configuration

4. **Payment Status Not Updating**
   - Check callback URL response
   - Verify database permissions
   - Check application logs

### Debug Mode:

Enable debug logging in `.env`:
```bash
APP_DEBUG=true
LOG_LEVEL=debug
```

### Log Files:
- Application logs: `storage/logs/app.log`
- M-Pesa logs: `storage/logs/mpesa.log`
- Error logs: Web server error logs

## Security Considerations

1. **Secure Credentials**
   - Never commit credentials to version control
   - Use environment variables
   - Rotate keys regularly

2. **Callback Validation**
   - Validate callback authenticity
   - Check transaction amounts
   - Implement idempotency

3. **HTTPS Only**
   - Force HTTPS for all payment pages
   - Secure callback endpoints
   - Use strong SSL configuration

## Support

- **Safaricom Developer Support**: devsupport@safaricom.co.ke
- **M-Pesa API Documentation**: https://developer.safaricom.co.ke/docs
- **Community Forum**: https://developer.safaricom.co.ke/community

## API Reference

### STK Push Request:
```php
POST https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest
Authorization: Bearer {access_token}
Content-Type: application/json

{
    "BusinessShortCode": "174379",
    "Password": "{encoded_password}",
    "Timestamp": "20231201120000",
    "TransactionType": "CustomerPayBillOnline",
    "Amount": "1000",
    "PartyA": "254708374149",
    "PartyB": "174379",
    "PhoneNumber": "254708374149",
    "CallBackURL": "https://yourdomain.com/api/mpesa/callback",
    "AccountReference": "BUS001",
    "TransactionDesc": "Bus Ticket Payment"
}
```

### Callback Response:
```json
{
    "Body": {
        "stkCallback": {
            "MerchantRequestID": "29115-34620561-1",
            "CheckoutRequestID": "ws_CO_191220191020363925",
            "ResultCode": 0,
            "ResultDesc": "The service request is processed successfully.",
            "CallbackMetadata": {
                "Item": [
                    {
                        "Name": "Amount",
                        "Value": 1000
                    },
                    {
                        "Name": "MpesaReceiptNumber",
                        "Value": "NLJ7RT61SV"
                    }
                ]
            }
        }
    }
}
```