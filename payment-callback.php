<?php
// payment-callback.php - Paystack Payment Callback Handler
session_start();
require_once 'includes/config.php';
require_once 'includes/paystack.php';

// Get reference from query string
$reference = isset($_GET['reference']) ? $_GET['reference'] : '';

if (empty($reference)) {
    header('Location: payment-failed.php?msg=no_reference');
    exit();
}

// Verify payment
$verification = paystack_verify_payment($reference);

if ($verification && $verification['status']) {
    $data = $verification['data'];
    $status = $data['status'];
    $amount = $data['amount'] / 100;
    $reference = $data['reference'];
    $metadata = $data['metadata'] ?? [];
    
    // Get payment record
    $payment = $conn->query("SELECT * FROM payments WHERE reference = '$reference'")->fetch_assoc();
    
    if ($payment) {
        // Update payment record
        $conn->query("
            UPDATE payments SET 
                status = '$status',
                gateway_response = '" . addslashes(json_encode($verification)) . "',
                payment_date = NOW()
            WHERE reference = '$reference'
        ");
        
        if ($status == 'success') {
            // Update employee training
            $conn->query("
                UPDATE employee_trainings 
                SET 
                    payment_status = 'paid',
                    payment_reference = '$reference',
                    payment_amount = $amount,
                    payment_date = NOW(),
                    payment_gateway = 'paystack'
                WHERE id = {$payment['enrollment_id']}
            ");
            
            // Log success
            logAction($payment['user_id'], 'payment_success', [
                'reference' => $reference,
                'amount' => $amount,
                'training_id' => $payment['training_id']
            ]);
            
            // Redirect to success page
            header('Location: payment-success.php?reference=' . $reference);
            exit();
        } else {
            // Payment failed
            header('Location: payment-failed.php?reference=' . $reference);
            exit();
        }
    } else {
        header('Location: payment-failed.php?msg=not_found');
        exit();
    }
} else {
    header('Location: payment-failed.php?msg=verification_failed');
    exit();
}
?>