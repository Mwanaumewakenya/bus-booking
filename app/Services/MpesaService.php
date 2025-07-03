<?php
/**
 * M-Pesa Payment Service
 * 
 * Handles M-Pesa payments using Safaricom Daraja API
 */

class MpesaService 
{
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $environment;
    private $baseUrl;

    public function __construct() 
    {
        $this->consumerKey = $_ENV['MPESA_CONSUMER_KEY'] ?? '';
        $this->consumerSecret = $_ENV['MPESA_CONSUMER_SECRET'] ?? '';
        $this->passkey = $_ENV['MPESA_PASSKEY'] ?? '';
        $this->shortcode = $_ENV['MPESA_SHORTCODE'] ?? '';
        $this->environment = $_ENV['MPESA_ENVIRONMENT'] ?? 'sandbox';
        
        $this->baseUrl = $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Generate access token
     */
    private function generateAccessToken(): ?string 
    {
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ];

        $response = $this->makeRequest($url, null, $headers);
        
        if ($response && isset($response['access_token'])) {
            return $response['access_token'];
        }
        
        return null;
    }

    /**
     * Initiate STK Push payment
     */
    public function stkPush(array $paymentData): array 
    {
        $accessToken = $this->generateAccessToken();
        
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to generate access token'
            ];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        
        $requestData = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $paymentData['amount'],
            'PartyA' => $paymentData['phone_number'],
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $paymentData['phone_number'],
            'CallBackURL' => $paymentData['callback_url'],
            'AccountReference' => $paymentData['reference'],
            'TransactionDesc' => $paymentData['description']
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $response = $this->makeRequest($url, $requestData, $headers);
        
        if ($response && isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            return [
                'success' => true,
                'checkout_request_id' => $response['CheckoutRequestID'],
                'merchant_request_id' => $response['MerchantRequestID'],
                'message' => 'STK Push sent successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => $response['errorMessage'] ?? 'Payment request failed'
        ];
    }

    /**
     * Query STK Push status
     */
    public function stkQuery(string $checkoutRequestId): array 
    {
        $accessToken = $this->generateAccessToken();
        
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to generate access token'
            ];
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        
        $requestData = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $response = $this->makeRequest($url, $requestData, $headers);
        
        return $response ?: [
            'success' => false,
            'message' => 'Query request failed'
        ];
    }

    /**
     * Process M-Pesa callback
     */
    public function processCallback(array $callbackData): array 
    {
        if (!isset($callbackData['Body']['stkCallback'])) {
            return [
                'success' => false,
                'message' => 'Invalid callback data'
            ];
        }

        $stkCallback = $callbackData['Body']['stkCallback'];
        $resultCode = $stkCallback['ResultCode'];
        
        if ($resultCode == 0) {
            // Payment successful
            $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
            
            $paymentData = [];
            foreach ($callbackMetadata as $item) {
                $paymentData[$item['Name']] = $item['Value'] ?? null;
            }
            
            return [
                'success' => true,
                'transaction_id' => $paymentData['MpesaReceiptNumber'] ?? null,
                'amount' => $paymentData['Amount'] ?? null,
                'phone_number' => $paymentData['PhoneNumber'] ?? null,
                'transaction_date' => $paymentData['TransactionDate'] ?? null,
                'checkout_request_id' => $stkCallback['CheckoutRequestID'],
                'merchant_request_id' => $stkCallback['MerchantRequestID']
            ];
        } else {
            // Payment failed
            return [
                'success' => false,
                'result_code' => $resultCode,
                'result_desc' => $stkCallback['ResultDesc'],
                'checkout_request_id' => $stkCallback['CheckoutRequestID'],
                'merchant_request_id' => $stkCallback['MerchantRequestID']
            ];
        }
    }

    /**
     * Validate phone number for M-Pesa
     */
    public function validatePhoneNumber(string $phoneNumber): array 
    {
        // Remove any spaces or special characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check if it's a valid Kenyan number
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            // Convert 0XXXXXXXXX to 254XXXXXXXXX
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 9) {
            // Convert XXXXXXXXX to 254XXXXXXXXX
            $phone = '254' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            // Already in correct format
            $phone = $phone;
        } else {
            return [
                'valid' => false,
                'message' => 'Invalid phone number format'
            ];
        }
        
        // Validate Kenyan mobile prefixes
        $validPrefixes = ['254701', '254702', '254703', '254705', '254706', '254707', '254708', '254709', 
                         '254710', '254711', '254712', '254713', '254714', '254715', '254716', '254717', 
                         '254718', '254719', '254720', '254721', '254722', '254723', '254724', '254725', 
                         '254726', '254727', '254728', '254729', '254740', '254741', '254742', '254743', 
                         '254744', '254745', '254746', '254747', '254748', '254790', '254791', '254792', 
                         '254793', '254794', '254795', '254796', '254797', '254798', '254799'];
        
        $prefix = substr($phone, 0, 6);
        
        if (!in_array($prefix, $validPrefixes)) {
            return [
                'valid' => false,
                'message' => 'Invalid Kenyan mobile number'
            ];
        }
        
        return [
            'valid' => true,
            'formatted_number' => $phone
        ];
    }

    /**
     * Make HTTP request
     */
    private function makeRequest(string $url, ?array $data = null, array $headers = []): ?array 
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($data !== null) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        error_log("M-Pesa API Error: HTTP $httpCode - $response");
        return null;
    }

    /**
     * Format amount for M-Pesa (remove decimals)
     */
    public function formatAmount(float $amount): int 
    {
        return (int) round($amount);
    }

    /**
     * Generate transaction reference
     */
    public function generateReference(string $prefix = 'BBS'): string 
    {
        return $prefix . date('YmdHis') . rand(100, 999);
    }
}