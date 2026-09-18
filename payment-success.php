<?php
// payment-success.php - Payment Success Page
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$reference = isset($_GET['reference']) ? $_GET['reference'] : '';

if (empty($reference)) {
    header('Location: my-trainings.php');
    exit();
}

// Get payment details
$payment = $conn->query("
    SELECT p.*, tp.title as training_title, tp.id as training_id,
           CONCAT(e.first_name, ' ', e.last_name) as employee_name
    FROM payments p
    JOIN training_programs tp ON p.training_id = tp.id
    JOIN employees e ON p.user_id = e.id
    WHERE p.reference = '$reference'
")->fetch_assoc();

$page_title = 'Payment Successful';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - <?php echo SITE_NAME; ?></title>
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
        .success-container {
            max-width: 600px;
            width: 100%;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #48bb78;
            margin-bottom: 20px;
        }
        .success-container h2 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .success-container .subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .payment-details {
            text-align: left;
            background: #f8f9fc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .payment-details .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .payment-details .detail-item:last-child {
            border-bottom: none;
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
            .success-container {
                margin: 15px;
                padding: 25px;
            }
            .success-icon {
                font-size: 3.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Payment Successful!</h2>
        <p class="subtitle">Your training enrollment has been confirmed</p>
        
        <?php if ($payment): ?>
            <div class="payment-details">
                <div class="detail-item">
                    <span><strong>Transaction Reference:</strong></span>
                    <span><?php echo htmlspecialchars($payment['reference']); ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Training:</strong></span>
                    <span><?php echo htmlspecialchars($payment['training_title']); ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Employee:</strong></span>
                    <span><?php echo htmlspecialchars($payment['employee_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Amount Paid:</strong></span>
                    <span class="text-success font-weight-bold"><?php echo formatNaira($payment['amount']); ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Payment Date:</strong></span>
                    <span><?php echo formatDateTime($payment['payment_date']); ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="employee/my-trainings.php" class="btn btn-primary btn-lg">
                <i class="fas fa-chalkboard-teacher"></i> View My Trainings
            </a>
        </div>
        <div class="mt-2">
            <a href="index.php" class="text-muted">
                <i class="fas fa-home"></i> Go to Homepage
            </a>
        </div>
    </div>
</body>
</html>