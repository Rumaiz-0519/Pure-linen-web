<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Helper function to get subscription details
function getSubscriptionDetails($subscription_id, $conn) {
    $stmt = $conn->prepare("
        SELECT * FROM swatch_subscriptions 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $subscription_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

// Get user's orders including subscription orders
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(DISTINCT oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .orders-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .order-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-footer {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-status {
            font-weight: 600;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f2f2f2;
        }
        
        .order-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .order-item-image {
            width: 70px;
            height: 70px;
            border-radius: 5px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .order-detail-btn {
            background-color: #1B365D;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .order-detail-btn:hover {
            background-color: #152c4d;
            color: white;
        }
        
        .no-orders {
            text-align: center;
            padding: 30px;
        }
        
        .no-orders i {
            font-size: 50px;
            color: #d3d3d3;
            margin-bottom: 20px;
        }
        
        /* Subscription styles */
        .subscription-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-top: 15px;
        }
        
        .subscription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .subscription-header strong {
            margin: 0 10px;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background-color: #1B365D;
        }
        
        .subscription-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            background-color: #17a2b8;
            color: white;
        }
        
        .sub-detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }
        
        .sub-detail-row i {
            width: 20px;
            color: #1B365D;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!--NAVIGATION-->
<nav class="navbar navbar-expand-lg navbar-light bg-light py-2 fixed-top">
        <div class="container">
            <img src="img/logo.png" alt="Pure Linen Logo" class="navbar-brand">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="shop.php">Linen Fabric</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="swatch.php">Swatch Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">Orders</a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="search.php" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php" aria-label="Shopping Cart">
                            <i class="fas fa-cart-plus"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><p class="dropdown-item mb-0">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profile.php">Update Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <div class="container mt-5 pt-5">
        <h2 class="mb-4">My Orders</h2>
        
        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h4>You haven't placed any orders yet</h4>
                    <p class="text-muted">Browse our products and place your first order!</p>
                    <a href="shop.php" class="btn btn-primary mt-3">Start Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h5 class="mb-1">
                                    Order #<?php echo $order['id']; ?>
                                    <?php if ($order['order_type'] === 'subscription'): ?>
                                        <span class="badge bg-info">Subscription</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="text-muted mb-0">Placed on <?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="order-status">
                                <?php
                                    $status_class = '';
                                    switch($order['payment_status']) {
                                        case 'completed':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'pending':
                                            $status_class = 'bg-warning';
                                            break;
                                        case 'processing':
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
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Items:</strong> <?php echo $order['item_count']; ?></p>
                                    <p><strong>Total:</strong> LKR <?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p>
                                        <strong>Shipping:</strong> 
                                        <?php echo $order['delivery_option'] === 'express' ? 
                                            'Express Shipping (1-2 days)' : 
                                            'Standard Shipping (5-7 days)'; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>Payment Method:</strong> 
                                        <?php 
                                            switch($order['payment_method']) {
                                                case 'card':
                                                    echo 'Credit/Debit Card';
                                                    break;
                                                case 'bank':
                                                    echo 'Bank Transfer';
                                                    break;
                                                case 'cod':
                                                    echo 'Cash on Delivery';
                                                    break;
                                                default:
                                                    echo ucfirst($order['payment_method']);
                                            }
                                        ?>
                                    </p>
                                    <p><strong>Delivery to:</strong> <?php echo htmlspecialchars($order['delivery_city']); ?>, <?php echo htmlspecialchars($order['delivery_country']); ?></p>
                                </div>
                            </div>
                            
                            <?php
                            // Display subscription information if this is a subscription order
                            if ($order['order_type'] === 'subscription' && !empty($order['subscription_id'])) {
                                // Get subscription details
                                $subscription = getSubscriptionDetails($order['subscription_id'], $conn);
                                
                                if ($subscription) {
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
                                    
                                    // Calculate remaining time
                                    $days_left = max(0, $current_date->diff($end_date)->days);
                                    $months_left = ceil($days_left / 30);
                                    
                                    // Calculate how many deliveries have been made and are remaining
                                    $total_deliveries = $subscription['duration'];
                                    $months_elapsed = floor($days_elapsed / 30);
                                    $deliveries_made = min($total_deliveries, max(1, $months_elapsed + 1)); // +1 for initial delivery
                                    $deliveries_remaining = max(0, $total_deliveries - $deliveries_made);
                                    
                                    // Display subscription details
                                    ?>
                                    <div class="subscription-details">
                                        <div class="subscription-header">
                                            <span class="subscription-badge">Swatch Book Subscription</span>
                                            <strong>Premium Linen Swatch Book - <?php echo ucfirst($subscription['swatch_type']); ?> Collection</strong>
                                            <span><?php echo $subscription['duration']; ?> <?php echo $subscription['duration'] == 1 ? 'month' : 'months'; ?></span>
                                        </div>
                                        
                                        <div class="progress mt-2 mb-2">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%"></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-3">
                                            <small><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></small>
                                            <small><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="sub-detail-row">
                                                    <i class="fas fa-box-open"></i>
                                                    <span>Deliveries made: <?php echo $deliveries_made; ?> of <?php echo $total_deliveries; ?></span>
                                                </div>
                                                <?php if ($deliveries_remaining > 0): ?>
                                                    <div class="sub-detail-row">
                                                        <i class="fas fa-truck"></i>
                                                        <?php
                                                            $next_delivery = clone $start_date;
                                                            $next_delivery->modify('+'.($deliveries_made).' months');
                                                        ?>
                                                        <span>Next delivery: <?php echo $next_delivery->format('M d, Y'); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="sub-detail-row">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span>All deliveries completed</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="sub-detail-row">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <span><?php echo $days_left; ?> days remaining</span>
                                                </div>
                                                <div class="sub-detail-row">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    <span>
                                                        <?php 
                                                            if ($days_left > 0) {
                                                                echo $months_left . ' ' . ($months_left == 1 ? 'month' : 'months') . ' remaining';
                                                            } else {
                                                                echo 'Subscription completed';
                                                            }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        
                        <div class="order-footer">
                            <span>
                                <?php 
                                    if ($order['order_type'] === 'subscription' && isset($subscription) && !empty($subscription)) {
                                        // For subscription orders
                                        echo 'Subscription period: ' . date('M d, Y', strtotime($subscription['start_date'])) . 
                                             ' - ' . date('M d, Y', strtotime($subscription['end_date']));
                                    } else {
                                        // For regular orders
                                        // Calculate estimated delivery date
                                        $order_date = new DateTime($order['order_date']);
                                        if ($order['delivery_option'] === 'express') {
                                            $delivery_days = 2; // 1-2 days for express
                                        } else {
                                            $delivery_days = 7; // 5-7 days for standard
                                        }
                                        $delivery_date = clone $order_date;
                                        $delivery_date->modify("+$delivery_days days");
                                        
                                        // Only show for non-cancelled orders
                                        if ($order['payment_status'] !== 'cancelled') {
                                            echo 'Expected delivery by ' . $delivery_date->format('F d, Y');
                                        }
                                    }
                                ?>
                            </span>
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="order-detail-btn">View Order Details</a>
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