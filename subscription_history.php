<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's subscriptions
$stmt = $conn->prepare("
    SELECT * FROM swatch_subscriptions
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$subscriptions = [];

while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .subscriptions-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .subscription-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .subscription-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .subscription-body {
            padding: 20px;
        }
        
        .subscription-footer {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .subscription-status {
            font-weight: 600;
        }
        
        .subscription-detail-btn {
            background-color: #1B365D;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .subscription-detail-btn:hover {
            background-color: #152c4d;
            color: white;
        }
        
        .no-subscriptions {
            text-align: center;
            padding: 30px;
        }
        
        .no-subscriptions i {
            font-size: 50px;
            color: #d3d3d3;
            margin-bottom: 20px;
        }
        
        .progress-container {
            background-color: #eee;
            border-radius: 4px;
            height: 10px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            background-color: #1B365D;
            height: 100%;
            border-radius: 4px;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
        }
        
        .delivery-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .delivery-info p {
            margin-bottom: 8px;
        }
        
        .delivery-info p:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 pt-5">
        <h2 class="mb-4">My Swatch Book Subscriptions</h2>
        
        <div class="subscriptions-container">
            <?php if (empty($subscriptions)): ?>
                <div class="no-subscriptions">
                    <i class="fas fa-book"></i>
                    <h4>You don't have any active subscriptions</h4>
                    <p class="text-muted">Subscribe to our swatch books to receive the latest fabric samples!</p>
                    <a href="swatch.php" class="btn btn-primary mt-3">Browse Swatch Books</a>
                </div>
            <?php else: ?>
                <?php foreach ($subscriptions as $subscription): ?>
                    <div class="subscription-card">
                        <div class="subscription-header d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h5 class="mb-1">Subscription #<?php echo $subscription['id']; ?></h5>
                                <p class="text-muted mb-0">Created on <?php echo date('F d, Y', strtotime($subscription['created_at'])); ?></p>
                            </div>
                            <div class="subscription-status">
                                <?php
                                    $status_class = '';
                                    switch($subscription['payment_status']) {
                                        case 'completed':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'pending':
                                            $status_class = 'bg-warning';
                                            break;
                                        case 'active':
                                            $status_class = 'bg-info';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'bg-danger';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                    }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($subscription['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="subscription-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Type:</strong> <?php echo ucfirst($subscription['swatch_type']); ?> Swatch Book</p>
                                    <p><strong>Duration:</strong> <?php echo $subscription['duration']; ?> <?php echo $subscription['duration'] == 1 ? 'month' : 'months'; ?></p>
                                    <p><strong>Total Amount:</strong> LKR <?php echo number_format($subscription['total_amount'], 2); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($subscription['start_date'])); ?></p>
                                    <p><strong>End Date:</strong> <?php echo date('F d, Y', strtotime($subscription['end_date'])); ?></p>
                                    <p>
                                        <strong>Payment Method:</strong> 
                                        <?php 
                                            switch($subscription['payment_method']) {
                                                case 'card':
                                                    echo 'Credit/Debit Card';
                                                    break;
                                                default:
                                                    echo ucfirst($subscription['payment_method']);
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($subscription['payment_status'] === 'active'): ?>
                                <?php
                                    // Calculate subscription progress
                                    $start_date = new DateTime($subscription['start_date']);
                                    $end_date = new DateTime($subscription['end_date']);
                                    $current_date = new DateTime();
                                    
                                    $total_days = $start_date->diff($end_date)->days;
                                    $days_elapsed = $start_date->diff($current_date)->days;
                                    
                                    $progress_percentage = 0;
                                    if ($total_days > 0) {
                                        $progress_percentage = min(100, max(0, ($days_elapsed / $total_days) * 100));
                                    }
                                    
                                    // Calculate how many deliveries have been made and are remaining
                                    $total_deliveries = $subscription['duration'];
                                    $months_elapsed = floor($days_elapsed / 30);
                                    $deliveries_made = min($total_deliveries, max(1, $months_elapsed + 1)); // +1 for initial delivery
                                    $deliveries_remaining = max(0, $total_deliveries - $deliveries_made);
                                    
                                    // Calculate next delivery date
                                    $last_delivery = clone $start_date;
                                    $last_delivery->modify('+'.($deliveries_made - 1).' months');
                                    $next_delivery = clone $last_delivery;
                                    $next_delivery->modify('+1 month');
                                ?>
                                
                                <div class="mt-4">
                                    <h5>Subscription Progress</h5>
                                    <div class="progress-container">
                                        <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></small>
                                        <small><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></small>
                                    </div>
                                </div>
                                
                                <div class="delivery-info">
                                    <h5 class="mb-3">Delivery Status</h5>
                                    <p><i class="fas fa-box-open me-2"></i> <strong>Deliveries made:</strong> <?php echo $deliveries_made; ?> of <?php echo $total_deliveries; ?></p>
                                    <?php if ($deliveries_remaining > 0): ?>
                                        <p><i class="fas fa-truck me-2"></i> <strong>Next delivery:</strong> <?php echo $next_delivery->format('F d, Y'); ?></p>
                                    <?php else: ?>
                                        <p><i class="fas fa-check-circle me-2"></i> <strong>All deliveries completed</strong></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="subscription-footer">
                            <span>
                                <?php if ($subscription['payment_status'] === 'active'): ?>
                                    <?php
                                        $days_left = max(0, $current_date->diff($end_date)->days);
                                        echo $days_left . ' days left';
                                    ?>
                                <?php elseif ($subscription['payment_status'] === 'pending'): ?>
                                    Awaiting payment confirmation
                                <?php endif; ?>
                            </span>
                            <a href="subscription_details.php?id=<?php echo $subscription['id']; ?>" class="subscription-detail-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>