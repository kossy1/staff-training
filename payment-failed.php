<?php
// payment-failed.php - Payment Failed Page
session_start();
require_once 'includes/config.php';

$reference = isset($_GET['reference']) ? $_GET['reference'] : '';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';

$page_title = 'Payment Failed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .failed-container {
            max-width: 500px;
            width: 100%;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .failed-icon {
            font-size: 5rem;
            color: #e74a3b;
            margin-bottom: 20px;
        }
        .failed-container h2 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .failed-container .subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        @media (max-width: 576px) {
            .failed-container {
                margin: 15px;
                padding: 25px;
            }
            .failed-icon {
                font-size: 3.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="failed-container">
        <div class="failed-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h2>Payment Failed</h2>
        <p class="subtitle">We couldn't process your payment</p>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php 
            if ($message == 'no_reference') {
                echo "No payment reference found.";
            } elseif ($message == 'not_found') {
                echo "Payment record not found.";
            } elseif ($message == 'verification_failed') {
                echo "Payment verification failed. Please try again.";
            } else {
                echo "Your payment could not be processed. Please try again.";
            }
            ?>
        </div>
        
        <div class="mt-3">
            <a href="employee/apply-training.php" class="btn btn-primary btn-lg">
                <i class="fas fa-redo"></i> Try Again
            </a>
        </div>
        <div class="mt-2">
            <a href="employee/my-trainings.php" class="text-muted">
                <i class="fas fa-arrow-left"></i> Back to My Trainings
            </a>
        </div>
    </div>
</body>
</html>