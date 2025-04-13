<?php
session_start();
require_once 'config.php';

if (!isset($_GET['type']) || !isset($_GET['duration'])) {
    header("Location: swatch.php");
    exit();
}

$swatch_type = $_GET['type']; // 'dark' or 'light'
$duration = (int)$_GET['duration']; // 1, 3, or 6 months

// Validate parameters
if (!in_array($swatch_type, ['dark', 'light'])) {
    header("Location: swatch.php");
    exit();
}

if (!in_array($duration, [1, 3, 6])) {
    header("Location: swatch.php");
    exit();
}

// Set subscription prices
$prices = [
    1 => 4000,  // 1 month - LKR 4,000
    3 => 12000, // 3 months - LKR 12,000
    6 => 20000  // 6 months - LKR 20,000
];

$subscription_price = $prices[$duration];
$subscription_name = ucfirst($swatch_type) . " Swatch Book (" . $duration . " " . ($duration == 1 ? "month" : "months") . ")";

// User information (if logged in)
$firstName = $lastName = $email = $phone = $address = $city = $country = '';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $firstName = $user['firstName'];
        $lastName = $user['lastName'];
        $email = $user['email'];
        $phone = $user['phone'] ?? '';
        $address = $user['address'] ?? '';
    }
}

// Handle form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $payment_method = 'card'; // Only card payment is allowed
    
    // Form validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($country)) $errors[] = "Country is required";
    
    // Validate card information
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = trim($_POST['card_number'] ?? '');
    $card_expiry = trim($_POST['card_expiry'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');
    
    if (empty($card_name)) $errors[] = "Card name is required";
    if (empty($card_number)) $errors[] = "Card number is required";
    if (empty($card_expiry)) $errors[] = "Card expiry date is required";
    if (empty($card_cvv)) $errors[] = "Card CVV is required";
    
    // If no errors, process the subscription
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Get user ID if logged in, otherwise use null
            $user_id = $_SESSION['user_id'] ?? null;
            
            // If user is not logged in but provided email, check if they exist
            if (!$user_id) {
                $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $user_stmt->bind_param("s", $email);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                
                if ($user_result->num_rows > 0) {
                    // If user exists, use their ID
                    $user_id = $user_result->fetch_assoc()['id'];
                } else {
                    // Create a new user account
                    $password = bin2hex(random_bytes(8)); // Generate a random password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert_user = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert_user->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $phone, $address);
                    $insert_user->execute();
                    
                    $user_id = $conn->insert_id;
                    
                    // TODO: Send welcome email with temporary password
                }
            }
            
            // Check if swatch_subscriptions table exists, if not create it
            $table_check = $conn->query("SHOW TABLES LIKE 'swatch_subscriptions'");
            if ($table_check->num_rows == 0) {
                $conn->query("CREATE TABLE swatch_subscriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    swatch_type VARCHAR(50) NOT NULL,
                    duration INT NOT NULL,
                    start_date DATE NOT NULL,
                    end_date VARCHAR(50) NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )");
            }
            
            // Calculate subscription dates
            $start_date = date('Y-m-d');
            
            // Calculate end date properly
            $end_year = date('Y');
            $end_month = date('m') + $duration;
            $end_day = date('d');
            
            // Adjust for month overflow
            if ($end_month > 12) {
                $extra_years = floor($end_month / 12);
                $end_month = $end_month % 12;
                if ($end_month == 0) $end_month = 12;
                $end_year += $extra_years;
            }
            
            // Format properly
            $end_date = sprintf('%04d-%02d-%02d', $end_year, $end_month, $end_day);
            
            // Create the subscription (CHANGED STATUS FROM 'active' TO 'pending')
            $insert_sub = $conn->prepare("INSERT INTO swatch_subscriptions (user_id, swatch_type, duration, start_date, end_date, total_amount, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $insert_sub->bind_param("isisids", $user_id, $swatch_type, $duration, $start_date, $end_date, $subscription_price, $payment_method);
            $insert_sub->execute();
            
            $subscription_id = $conn->insert_id;
            
            // Create an order for the subscription (CHANGED STATUS FROM 'completed' TO 'pending')
            $insert_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, payment_status, order_type, subscription_id, delivery_address, delivery_city, delivery_country) VALUES (?, ?, ?, 'pending', 'subscription', ?, ?, ?, ?)");
            $insert_order->bind_param("idsisss", $user_id, $subscription_price, $payment_method, $subscription_id, $address, $city, $country);
            $insert_order->execute();
            
            $order_id = $conn->insert_id;
            
            // Commit the transaction
            $conn->commit();
            
            // Set session variables for confirmation page
            $_SESSION['subscription_id'] = $subscription_id;
            $_SESSION['order_id'] = $order_id;
            
            // Redirect to subscription confirmation page
            header("Location: subscription_confirmation.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $errors[] = "Subscription processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swatch Book Subscription - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .subscription-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .subscription-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .subscription-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .payment-method {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #1B365D;
            background-color: #f8f9fa;
        }
        
        .payment-method.selected {
            border-color: #1B365D;
            background-color: rgba(27, 54, 93, 0.05);
        }
        
        .subscribe-btn {
            background-color: #1B365D;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .subscribe-btn:hover {
            background-color: #152c4d;
        }
        
        .subscription-benefits {
            background-color: #e9f7ef;
            border: 1px solid #d4edda;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .benefits-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .benefits-list {
            list-style-type: none;
            padding: 0;
        }
        
        .benefits-list li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        
        .benefits-list li::before {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 2px;
            color: #28a745;
        }

        /* Ensure form labels are visible */
        label.form-label {
            display: block !important;
            font-weight: 500 !important;
            margin-bottom: 0.5rem !important;
            color: #333 !important;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 pt-5">
        <h2 class="mb-4"><?php echo $subscription_name; ?> Subscription</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="subscription-container">
                    <form action="swatch_subscription.php?type=<?php echo $swatch_type; ?>&duration=<?php echo $duration; ?>" method="POST" id="subscription-form">
                        <div class="subscription-details mb-4">
                            <h3 class="subscription-title">Subscription Details</h3>
                            
                            <div class="detail-row">
                                <span>Subscription Type</span>
                                <span><?php echo ucfirst($swatch_type); ?> Swatch Book</span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Duration</span>
                                <span><?php echo $duration; ?> <?php echo $duration == 1 ? 'month' : 'months'; ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Price</span>
                                <span>LKR <?php echo number_format($subscription_price, 2); ?></span>
                            </div>
                            
                            <div class="total-row">
                                <span>Total</span>
                                <span>LKR <?php echo number_format($subscription_price, 2); ?></span>
                            </div>
                        </div>
                        
                        <h3 class="subscription-title">Personal Information</h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required placeholder="Enter your first name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required placeholder="Enter your last name">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required placeholder="Enter your email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required placeholder="Enter your phone number">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required placeholder="Enter your full address"><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required placeholder="Enter your city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country *</label>
                                <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>" required placeholder="Enter your country">
                            </div>
                        </div>
                        
                        <h3 class="subscription-title mt-4">Payment Information</h3>
                        
                        <div class="payment-method selected">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-credit-card me-2 fa-lg"></i>
                                <span class="fw-bold">Credit/Debit Card</span>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cardName" class="form-label">Name on Card *</label>
                                <input type="text" class="form-control" id="cardName" name="card_name" placeholder="Enter name on card" required>
                            </div>
                            <div class="mb-3">
                                <label for="cardNumber" class="form-label">Card Number *</label>
                                <input type="text" class="form-control" id="cardNumber" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cardExpiry" class="form-label">Expiry Date *</label>
                                    <input type="text" class="form-control" id="cardExpiry" name="card_expiry" placeholder="MM/YY" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cardCvv" class="form-label">CVV *</label>
                                    <input type="text" class="form-control" id="cardCvv" name="card_cvv" placeholder="XXX" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="subscribe-btn">Subscribe Now</button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="subscription-benefits">
                    <h3 class="benefits-title">Subscription Benefits</h3>
                    <ul class="benefits-list">
                        <li>Premium swatch book delivered to your doorstep</li>
                        <li>Receive the latest color and texture trends</li>
                        <li>Priority access to new fabric collections</li>
                        <li>Exclusive seasonal fashion catalogs</li>
                        <li>Free shipping on all deliveries</li>
                        <li>Special discounts on fabric purchases</li>
                    </ul>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Subscription Details</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Type:</strong> <?php echo ucfirst($swatch_type); ?> Swatch Book</p>
                        <p><strong>Duration:</strong> <?php echo $duration; ?> <?php echo $duration == 1 ? 'month' : 'months'; ?></p>
                        <p><strong>Price:</strong> LKR <?php echo number_format($subscription_price, 2); ?></p>
                        
                        <p class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i> Your subscription will begin immediately after payment confirmation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Credit card formatting
        const cardNumberInput = document.getElementById('cardNumber');
        const cardExpiryInput = document.getElementById('cardExpiry');
        const cardCvvInput = document.getElementById('cardCvv');
        
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                e.target.value = formattedValue.slice(0, 19); // Limit to 16 digits + 3 spaces
            });
        }
        
        if (cardExpiryInput) {
            cardExpiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                let formattedValue = '';
                
                if (value.length > 0) {
                    formattedValue = value.slice(0, 2);
                    if (value.length > 2) {
                        formattedValue += '/' + value.slice(2, 4);
                    }
                }
                
                e.target.value = formattedValue;
            });
        }
        
        if (cardCvvInput) {
            cardCvvInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value.slice(0, 3);
            });
        }
    });
    </script>
</body>
</html>