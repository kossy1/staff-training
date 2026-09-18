<?php
// employee/pay-training.php - Pay for Training with Paystack
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/paystack.php';

// ============================================
// PAYMENT SESSION FUNCTIONS (Local fallback)
// ============================================

/**
 * Set payment session data
 */
function setPaymentSession($training_id, $enrollment_id, $amount, $reference) {
    $_SESSION['payment'] = [
        'training_id' => $training_id,
        'enrollment_id' => $enrollment_id,
        'amount' => $amount,
        'reference' => $reference,
        'timestamp' => time()
    ];
}

/**
 * Clear payment session
 */
function clearPaymentSession() {
    unset($_SESSION['payment']);
}

// ============================================
// MAIN PAYMENT PROCESSING
// ============================================

if (!isLoggedIn() || !isEmployee()) {
    header('Location: ../login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];
$user_id = $_SESSION['user_id'];
$training_id = isset($_GET['training_id']) ? (int)$_GET['training_id'] : 0;

if ($training_id <= 0) {
    header('Location: my-trainings.php');
    exit();
}

// Get training details
$training = $conn->query("
    SELECT tp.*, 
           et.id as enrollment_id, 
           et.payment_status, 
           et.payment_reference,
           et.status as enrollment_status
    FROM training_programs tp
    LEFT JOIN employee_trainings et ON tp.id = et.training_id AND et.employee_id = $employee_id
    WHERE tp.id = $training_id
")->fetch_assoc();

if (!$training) {
    header('Location: my-trainings.php');
    exit();
}

// Check if already paid - with proper null checking
$payment_status = $training['payment_status'] ?? null;
if ($payment_status == 'paid') {
    header('Location: my-trainings.php?msg=already_paid');
    exit();
}

// Get employee details for payment
$employee = $conn->query("SELECT * FROM employees WHERE id = $employee_id")->fetch_assoc();

if (!$employee) {
    header('Location: my-trainings.php?msg=employee_not_found');
    exit();
}

$errors = [];
$payment_url = '';
$reference = '';

// Initialize payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_now'])) {
    $amount = (float)$training['cost'];
    
    if ($amount <= 0) {
        $errors[] = "This training is free. No payment required.";
    } else {
        // Generate reference
        $reference = generate_payment_reference();
        
        // Create enrollment if not exists
        $enrollment_id = $training['enrollment_id'] ?? null;
        if (!$enrollment_id) {
            $stmt = $conn->prepare("
                INSERT INTO employee_trainings (
                    employee_id, training_id, enrollment_date, status, payment_status
                ) VALUES (?, ?, CURDATE(), 'enrolled', 'pending')
            ");
            $stmt->bind_param("ii", $employee_id, $training_id);
            $stmt->execute();
            $enrollment_id = $conn->insert_id;
        }
        
        // Prepare customer data with null checks
        $customer_name = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        $customer_email = $employee['email'] ?? '';
        $customer_phone = $employee['phone'] ?? '';
        $currency = PAYSTACK_CURRENCY; // Get the constant value
        
        // Create payment record using a simpler approach
        $sql = "INSERT INTO payments (
                    user_id, training_id, enrollment_id, reference, amount, 
                    currency, status, customer_name, customer_email, customer_phone
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, 'pending', ?, ?, ?
                )";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters - make sure all variables are defined
        $stmt->bind_param(
            "iiisdssss",  // i=integer, i=integer, i=integer, s=string, d=double, s=string, s=string, s=string, s=string
            $user_id,           // 1 - i
            $training_id,       // 2 - i
            $enrollment_id,     // 3 - i
            $reference,         // 4 - s
            $amount,            // 5 - d
            $currency,          // 6 - s
            $customer_name,     // 7 - s
            $customer_email,    // 8 - s
            $customer_phone     // 9 - s
        );
        
        if ($stmt->execute()) {
            $payment_id = $conn->insert_id;
            
            // Initialize Paystack payment
            $metadata = [
                'user_id' => $user_id,
                'training_id' => $training_id,
                'enrollment_id' => $enrollment_id,
                'payment_id' => $payment_id,
                'custom_fields' => [
                    [
                        'display_name' => "Training",
                        'variable_name' => "training",
                        'value' => $training['title']
                    ],
                    [
                        'display_name' => "Employee",
                        'variable_name' => "employee",
                        'value' => $customer_name
                    ]
                ]
            ];
            
            $payment = paystack_initialize_payment(
                $customer_email,
                $amount,
                $reference,
                $metadata
            );
            
            if ($payment && isset($payment['status']) && $payment['status']) {
                // Update payment reference in employee_trainings
                $conn->query("
                    UPDATE employee_trainings 
                    SET payment_reference = '$reference' 
                    WHERE id = $enrollment_id
                ");
                
                $payment_url = $payment['data']['authorization_url'];
                
                // Set payment session
                setPaymentSession($training_id, $enrollment_id, $amount, $reference);
                
            } else {
                $error_message = $payment['message'] ?? 'Unknown error';
                $errors[] = "Payment initialization failed: " . $error_message;
                
                // Log the error
                logAction($user_id, 'payment_init_failed', [
                    'training_id' => $training_id,
                    'error' => $error_message
                ]);
            }
        } else {
            $errors[] = "Failed to create payment record: " . $conn->error;
        }
    }
}

$page_title = 'Pay for Training';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-credit-card text-primary"></i> Pay for Training</h1>
            <p class="text-muted">Complete your payment to enroll in this training</p>
        </div>
        <div>
            <a href="my-trainings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Trainings
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($payment_url): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            You are being redirected to Paystack to complete your payment.
            <br><br>
            <a href="<?php echo $payment_url; ?>" class="btn btn-success btn-lg" target="_blank">
                <i class="fas fa-external-link-alt"></i> Proceed to Paystack
            </a>
            <br><br>
            <small class="text-muted">If you are not redirected automatically, click the button above.</small>
        </div>
        
        <script>
        // Auto redirect to Paystack after 3 seconds
        setTimeout(function() {
            window.location.href = '<?php echo $payment_url; ?>';
        }, 3000);
        </script>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Training Details</h5>
                </div>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($training['title']); ?></h4>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $training['type'])); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($training['category'] ?? 'Uncategorized'); ?></p>
                            <p><strong>Duration:</strong> <?php echo $training['duration_hours']; ?> hours</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> <?php echo formatDate($training['start_date']); ?></p>
                            <p><strong>End Date:</strong> <?php echo formatDate($training['end_date']); ?></p>
                            <p><strong>Trainer:</strong> <?php echo htmlspecialchars($training['trainer_name'] ?? 'TBD'); ?></p>
                        </div>
                    </div>
                    <?php if ($training['description']): ?>
                        <div class="mt-3">
                            <p><strong>Description:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($training['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Show enrollment status if already enrolled
                    $enrollment_status = $training['enrollment_status'] ?? null;
                    $payment_status = $training['payment_status'] ?? null;
                    if ($enrollment_status): 
                    ?>
                        <div class="mt-3 alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Enrollment Status:</strong> 
                            <?php echo ucfirst(str_replace('_', ' ', $enrollment_status)); ?>
                            <?php if ($payment_status == 'pending'): ?>
                                <span class="badge badge-warning">Payment Pending</span>
                            <?php elseif ($payment_status == 'paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Unpaid</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="payment-details">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Training Fee:</span>
                            <span><?php echo formatNaira($training['cost']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Processing Fee:</span>
                            <span class="text-success">Free</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>VAT (0%):</span>
                            <span>₦0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-success h4"><?php echo formatNaira($training['cost']); ?></strong>
                        </div>
                        
                        <div class="text-muted small mb-3">
                            <i class="fas fa-lock"></i> Secured by Paystack
                            <br>
                            <i class="fas fa-credit-card"></i> Cards, Bank Transfer, USSD, QR Code
                        </div>
                        
                        <form method="POST">
                            <?php if ($training['cost'] > 0): ?>
                                <?php if ($payment_status == 'paid'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> Already Paid!
                                    </div>
                                    <a href="my-trainings.php" class="btn btn-primary btn-block">
                                        <i class="fas fa-arrow-left"></i> Back to My Trainings
                                    </a>
                                <?php elseif ($payment_status == 'pending'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock"></i> Payment Pending
                                    </div>
                                    <button type="submit" name="pay_now" class="btn btn-success btn-block btn-lg">
                                        <i class="fas fa-credit-card"></i> Retry Payment
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="pay_now" class="btn btn-success btn-block btn-lg">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> This training is free!
                                </div>
                                <a href="enroll-free-training.php?training_id=<?php echo $training['id']; ?>" 
                                   class="btn btn-success btn-block btn-lg">
                                    <i class="fas fa-check"></i> Enroll Now
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Payment Security Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt text-success"></i> Secure Payment</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> 256-bit SSL Encryption</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> PCI DSS Compliant</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Paystack Secure Gateway</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Multiple Payment Options</li>
                        <li><i class="fas fa-check-circle text-success"></i> Instant Payment Confirmation</li>
                    </ul>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card"></i> Accepted Payments</h5>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-4">
                            <i class="fab fa-cc-visa fa-2x text-primary"></i>
                        </div>
                        <div class="col-4">
                            <i class="fab fa-cc-mastercard fa-2x text-danger"></i>
                        </div>
                        <div class="col-4">
                            <i class="fab fa-cc-amex fa-2x text-info"></i>
                        </div>
                        <div class="col-4 mt-2">
                            <i class="fas fa-university fa-2x text-success"></i>
                        </div>
                        <div class="col-4 mt-2">
                            <i class="fas fa-mobile-alt fa-2x text-warning"></i>
                        </div>
                        <div class="col-4 mt-2">
                            <i class="fas fa-qrcode fa-2x text-dark"></i>
                        </div>
                    </div>
                    <small class="text-muted">Cards, Bank Transfer, USSD, QR Code</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>