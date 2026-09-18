<?php
// includes/paystack.php - Paystack Payment Gateway Configuration

// Paystack API Keys (Get from your Paystack dashboard)
define('PAYSTACK_SECRET_KEY', 'sk_test_a454752eb6113ceee9e46cef0e7115255cc17f35'); // Replace with your secret key
define('PAYSTACK_PUBLIC_KEY', 'pk_test_85d47912a91313c7df0f5d96199be70e3c431df6'); // Replace with your public key
define('PAYSTACK_API_URL', 'https://api.paystack.co');

// Paystack payment settings
define('PAYSTACK_CURRENCY', 'NGN');
define('PAYSTACK_CALLBACK_URL', SITE_URL . 'payment-callback.php');
define('PAYSTACK_CANCEL_URL', SITE_URL . 'payment-cancel.php');

// Initialize Paystack API call
function paystack_initialize_payment($email, $amount, $reference, $metadata = []) {
    $url = PAYSTACK_API_URL . '/transaction/initialize';
    
    $data = [
        'email' => $email,
        'amount' => $amount * 100, // Paystack uses kobo (multiply by 100)
        'reference' => $reference,
        'callback_url' => PAYSTACK_CALLBACK_URL,
        'metadata' => $metadata,
        'currency' => PAYSTACK_CURRENCY
    ];
    
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
        'Cache-Control: no-cache'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        return ['success' => false, 'error' => $err];
    }
    
    return json_decode($response, true);
}

// Verify Paystack payment
function paystack_verify_payment($reference) {
    $url = PAYSTACK_API_URL . '/transaction/verify/' . $reference;
    
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
        'Cache-Control: no-cache'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        return ['success' => false, 'error' => $err];
    }
    
    return json_decode($response, true);
}

// Generate unique payment reference
function generate_payment_reference() {
    return 'TXN_' . date('Ymd') . '_' . uniqid() . '_' . rand(1000, 9999);
}

// Format amount for Paystack
function paystack_format_amount($amount) {
    return (int)($amount * 100);
}

// Format amount for display
function paystack_display_amount($amount) {
    return '₦' . number_format($amount / 100, 2);
}
?>