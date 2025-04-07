<?php
session_start();
require_once 'config.php';

// Check if there's a subscription ID in the session
if (!isset($_SESSION['subscription_id'])) {
    header("Location: swatch.php");
    exit();
}

$subscription_id = $_SESSION['subscription_id'];

// Get subscription details
try {
    $stmt = $conn->prepare("
        SELECT s.*, u.firstName, u.lastName, u.email, u.phone
        FROM swatch_subscriptions s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $subscription_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Subscription not found");
    }

    $subscription = $result->fetch_assoc();
    
    // Get order ID if related order exists
    $order_id = null;
    $order_stmt = $conn->prepare("SELECT id FROM orders WHERE subscription_id = ? LIMIT 1");
    $order_stmt->bind_param("i", $subscription_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    if ($order_result->num_rows > 0) {
        $order = $order_result->fetch_assoc();
        $order_id = $order['id'];
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error retrieving subscription: " . $e->getMessage();
    header("Location: swatch.php");
    exit();
}

// Clear the subscription ID from session to prevent refreshing issues
// unset($_SESSION['subscription_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmation - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .confirmation-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-header i {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .subscription-details {
            margin-bottom: 30px;
        }
        
        .subscription-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 150px;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .subscription-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .continue-btn {
            background-color: #1B365D;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .continue-btn:hover {
            background-color: #152c4d;
            color: white;
        }
        
        .sub-info-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .sub-info-title {
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 pt-5">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h2>Thank You for Your Subscription!</h2>
                <p>Subscription #<?php echo $subscription_id; ?> has been placed successfully.</p>
                <p>A confirmation email will be sent to <?php echo htmlspecialchars($subscription['email'] ?? 'your email address'); ?></p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="subscription-details">
                        <h3 class="subscription-title">Subscription Details</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Subscription #:</div>
                            <div class="detail-value"><?php echo $subscription_id; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Date:</div>
                            <div class="detail-value"><?php echo date('F d, Y h:i A', strtotime($subscription['created_at'])); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Type:</div>
                            <div class="detail-value"><?php echo ucfirst($subscription['swatch_type']); ?> Swatch Book</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Duration:</div>
                            <div class="detail-value"><?php echo $subscription['duration']; ?> <?php echo $subscription['duration'] == 1 ? 'month' : 'months'; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Start Date:</div>
                            <div class="detail-value"><?php echo date('F d, Y', strtotime($subscription['start_date'])); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">End Date:</div>
                            <div class="detail-value"><?php echo date('F d, Y', strtotime($subscription['end_date'])); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Payment Method:</div>
                            <div class="detail-value">
                                <?php 
                                    switch($subscription['payment_method']) {
                                        case 'card':
                                            echo 'Credit/Debit Card';
                                            break;
                                        default:
                                            echo ucfirst($subscription['payment_method']);
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">
                                <?php
                                    switch($subscription['payment_status']) {
                                        case 'completed':
                                            echo '<span class="badge bg-success">Completed</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="badge bg-warning">Pending</span>';
                                            break;
                                        case 'active':
                                            echo '<span class="badge bg-info">Active</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">'.ucfirst($subscription['payment_status']).'</span>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="subscription-details">
                        <h3 class="subscription-title">Delivery Details</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Name:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars(($subscription['firstName'] ?? '') . ' ' . ($subscription['lastName'] ?? '')); ?>
                            </div>
                        </div>
                        
                        <?php if ($order_id): ?>
                            <div class="detail-row">
                                <div class="detail-label">Order ID:</div>
                                <div class="detail-value">#<?php echo $order_id; ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sub-info-box">
                            <h4 class="sub-info-title">What happens next?</h4>
                            <p><i class="fas fa-calendar-check me-2"></i> <strong>First Delivery:</strong> Your first swatch book will be dispatched within 2-3 business days.</p>
                            <p><i class="fas fa-sync-alt me-2"></i> <strong>Regular Updates:</strong> You'll receive fresh swatches every month during your subscription period.</p>
                            <p><i class="fas fa-bell me-2"></i> <strong>Notifications:</strong> We'll email you when each swatch book is dispatched.</p>
                            <p class="mb-0"><i class="fas fa-user-circle me-2"></i> <strong>Account Access:</strong> You can track all your subscription details in your account.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="subscription-summary mt-4">
                <h3 class="subscription-title">Payment Summary</h3>
                <div class="summary-total">
                    <span>Total</span>
                    <span>LKR <?php echo number_format($subscription['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="subscription_history.php" class="continue-btn me-3">View My Subscriptions</a>
                <?php endif; ?>
                <a href="swatch.php" class="continue-btn">Back to Swatch Books</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>