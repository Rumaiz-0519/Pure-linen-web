<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'];

// Get order details and verify it belongs to the user
$stmt = $conn->prepare("
    SELECT o.*, u.firstName, u.lastName, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name, p.color, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = [];

while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}

// Check if this is a subscription order
$subscription = null;
if ($order['order_type'] === 'subscription' && !empty($order['subscription_id'])) {
    // Get subscription details
    $sub_stmt = $conn->prepare("
        SELECT * FROM swatch_subscriptions 
        WHERE id = ?
    ");
    $sub_stmt->bind_param("i", $order['subscription_id']);
    $sub_stmt->execute();
    $sub_result = $sub_stmt->get_result();
    
    if ($sub_result->num_rows > 0) {
        $subscription = $sub_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .order-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .order-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f2f2f2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .order-status-badge {
            font-size: 14px;
            padding: 8px 12px;
        }
        
        .order-details {
            margin-bottom: 30px;
        }
        
        .order-details-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f2f2f2;
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
        
        .order-items {
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f2f2f2;
        }
        
        .order-item-image {
            width: 70px;
            height: 70px;
            border-radius: 5px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-price {
            text-align: right;
            font-weight: 600;
        }
        
        .order-summary {
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
            border-top: 1px solid #e0e0e0;
        }
        
        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background-color: #5a6268;
            color: white;
        }
        
        /* Subscription styles */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background-color: #1B365D;
        }
        
        .table-responsive {
            margin-bottom: 20px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .badge.bg-info {
            background-color: #17a2b8 !important;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
        }
        
        .card-header.bg-info {
            background-color: #1B365D !important;
            color: white;
        }
        
        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 10px 15px;
        }
        
        .tracking-progress {
            margin: 30px 0;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 30px;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 25%;
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            background-color: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        
        .step.active .step-icon {
            background-color: #28a745;
            color: white;
        }
        
        .step.completed .step-icon {
            background-color: #28a745;
            color: white;
        }
        
        .step-title {
            font-size: 14px;
            color: #6c757d;
        }
        
        .step.active .step-title {
            color: #28a745;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 pt-5">
        <div class="mb-4">
            <a href="orders.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Back to Orders</a>
        </div>
        
        <div class="order-container">
            <div class="order-header">
                <div>
                    <h2>
                        Order #<?php echo $order_id; ?>
                        <?php if ($order['order_type'] === 'subscription'): ?>
                            <span class="badge bg-info">Subscription</span>
                        <?php endif; ?>
                    </h2>
                    <p class="text-muted">Placed on <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></p>
                </div>
                <div>
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
                    <span class="badge <?php echo $status_class; ?> order-status-badge">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($order['payment_status'] !== 'cancelled'): ?>
            <div class="tracking-progress">
                <?php
                    // Determine order progress based on payment_status
                    $order_placed = true;
                    $payment_confirmed = in_array($order['payment_status'], ['completed', 'processing', 'shipped']);
                    $order_shipped = in_array($order['payment_status'], ['shipped', 'completed']); // Mark shipped for completed orders
                    $order_delivered = $order['payment_status'] === 'completed'; // Mark delivered when status is completed
                ?>
                <div class="progress-steps">
                    <div class="step <?php echo $order_placed ? 'completed' : ''; ?>">
                        <div class="step-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-title">Order Placed</div>
                    </div>
                    <div class="step <?php echo $payment_confirmed ? 'completed' : ($order_placed ? 'active' : ''); ?>">
                        <div class="step-icon">
                            <?php echo $payment_confirmed ? '<i class="fas fa-check"></i>' : '<i class="fas fa-dollar-sign"></i>'; ?>
                        </div>
                        <div class="step-title">Payment Confirmed</div>
                    </div>
                    <div class="step <?php echo $order_shipped ? 'completed' : ($payment_confirmed ? 'active' : ''); ?>">
                        <div class="step-icon">
                            <?php echo $order_shipped ? '<i class="fas fa-check"></i>' : '<i class="fas fa-truck"></i>'; ?>
                        </div>
                        <div class="step-title">Order Shipped</div>
                    </div>
                    <div class="step <?php echo $order_delivered ? 'completed' : ($order_shipped ? 'active' : ''); ?>">
                        <div class="step-icon">
                            <?php echo $order_delivered ? '<i class="fas fa-check"></i>' : '<i class="fas fa-home"></i>'; ?>
                        </div>
                        <div class="step-title">Order Delivered</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p><i class="fas fa-exclamation-triangle me-2"></i> <strong>Order Cancelled</strong></p>
                <p>This order has been cancelled. If you have any questions, please contact customer support.</p>
            </div>
        <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="order-details">
                        <h3 class="order-details-title">Order Details</h3>
                        <div class="detail-row">
                            <div class="detail-label">Order Number:</div>
                            <div class="detail-value">#<?php echo $order_id; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div class="detail-value"><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Order Type:</div>
                            <div class="detail-value">
                                <?php echo $order['order_type'] === 'subscription' ? 'Swatch Book Subscription' : 'Regular Purchase'; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Payment Method:</div>
                            <div class="detail-value">
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
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Payment Status:</div>
                            <div class="detail-value">
                                <?php
                                    switch($order['payment_status']) {
                                        case 'completed':
                                            echo '<span class="badge bg-success">Completed</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="badge bg-warning">Pending</span>';
                                            break;
                                        case 'processing':
                                            echo '<span class="badge bg-info">Processing</span>';
                                            break;
                                        case 'cancelled':
                                            echo '<span class="badge bg-danger">Cancelled</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">'.ucfirst($order['payment_status']).'</span>';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <?php if ($order['payment_method'] === 'bank' && $order['payment_status'] === 'pending'): ?>
                            <div class="alert alert-warning mt-3">
                                <p><strong>Bank Transfer Information:</strong></p>
                                <p>Bank: Commercial Bank<br>
                                Account Number: 1234567890<br>
                                Account Name: Top Riz <br>
                                Reference: Order #<?php echo $order_id; ?></p>
                                <p>Please include your order number as reference when making the transfer.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="order-details">
                        <h3 class="order-details-title">Shipping Details</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Name:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['firstName'] . ' ' . $order['lastName']); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Address:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">City:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['delivery_city']); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Country:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['delivery_country']); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Delivery Option:</div>
                            <div class="detail-value">
                                <?php 
                                    echo $order['delivery_option'] === 'express' ? 
                                        'Express Shipping (1-2 business days)' : 
                                        'Standard Shipping (5-7 business days)'; 
                                ?>
                            </div>
                        </div>
                        
                        <?php if ($order['payment_status'] !== 'cancelled'): ?>
                            <div class="alert alert-info mt-3">
                                <p><i class="fas fa-truck me-2"></i> <strong>Estimated Delivery Date:</strong></p>
                                <p>
                                    <?php
                                        $order_date = new DateTime($order['order_date']);
                                        if ($order['delivery_option'] === 'express') {
                                            $delivery_days = rand(1, 2); // Random 1-2 days for express
                                        } else {
                                            $delivery_days = rand(5, 7); // Random 5-7 days for standard
                                        }
                                        $delivery_date = clone $order_date;
                                        $delivery_date->modify("+$delivery_days weekdays"); // Add only weekdays
                                        echo $delivery_date->format('F d, Y');
                                    ?>
                                </p>
                                <p class="mb-0">We'll notify you when your order has been shipped.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Details Section (for subscription orders) -->
            <?php if ($subscription): ?>
            <div class="order-details mt-4">
                <h3 class="order-details-title">Subscription Details</h3>
                
                <div class="card">
                    <div class="card-header bg-info text-white d-flex justify-content-between">
                        <span>Premium Linen Swatch Book - <?php echo ucfirst($subscription['swatch_type']); ?> Collection</span>
                        <span class="badge bg-light text-dark">
                            <?php echo $subscription['duration']; ?> <?php echo $subscription['duration'] == 1 ? 'month' : 'months'; ?>
                        </span>
                    </div>
                    <div class="card-body">
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
                        
                        <h5 class="mb-3">Subscription Progress</h5>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <small><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></small>
                            <small><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></small>
                        </div>
                        
                        <h5 class="mb-3">Delivery Schedule</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Delivery</th>
                                        <th>Estimated Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Get delivery status from database
                                    $delivery_statuses = [];
                                    $delivery_stmt = $conn->prepare("
                                        SELECT * FROM subscription_deliveries 
                                        WHERE subscription_id = ? 
                                        ORDER BY delivery_number
                                    ");
                                    $delivery_stmt->bind_param("i", $subscription['id']);
                                    $delivery_stmt->execute();
                                    $delivery_result = $delivery_stmt->get_result();
                                    
                                    while ($delivery = $delivery_result->fetch_assoc()) {
                                        $delivery_statuses[$delivery['delivery_number']] = $delivery;
                                    }
                                    
                                    // Now display the deliveries with correct status
                                    for ($i = 0; $i < $total_deliveries; $i++): 
                                        $delivery_date = clone $start_date;
                                        $delivery_date->modify("+$i months");
                                        
                                        // Check if we have a status from the database
                                        $delivery_number = $i + 1;
                                        if (isset($delivery_statuses[$delivery_number]) && $delivery_statuses[$delivery_number]['status'] === 'delivered') {
                                            $status = '<span class="badge bg-success">Delivered</span>';
                                            if (!empty($delivery_statuses[$delivery_number]['delivered_date'])) {
                                                $delivered_date = new DateTime($delivery_statuses[$delivery_number]['delivered_date']);
                                                $status .= '<br><small>on ' . $delivered_date->format('M d, Y') . '</small>';
                                            }
                                        } elseif ($i < $deliveries_made - 1) {
                                            $status = '<span class="badge bg-success">Delivered</span>';
                                        } elseif ($i == $deliveries_made - 1) {
                                            $status = '<span class="badge bg-info">Current</span>';
                                        } else {
                                            $status = '<span class="badge bg-secondary">Scheduled</span>';
                                        }
                                    ?>
                                        <tr>
                                            <td>Delivery <?php echo $i + 1; ?></td>
                                            <td><?php echo $delivery_date->format('F d, Y'); ?></td>
                                            <td><?php echo $status; ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-info-circle fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading">Subscription Information</h5>
                                    <p class="mb-0">Your swatch book subscription allows you to receive the latest fabric samples on a regular basis. Each delivery contains a premium collection of swatches for your reference and design needs.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <span><?php echo max(0, $current_date->diff($end_date)->days); ?> days remaining</span>
                        <a href="subscription_details.php?id=<?php echo $subscription['id']; ?>" class="btn btn-sm btn-primary">View Full Subscription Details</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Order Items Section -->
            <div class="order-items mt-4">
                <h3 class="order-details-title">Order Items</h3>
                
                <?php if (!empty($order_items)): ?>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'img/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="order-item-image">
                            
                            <div class="order-item-details">
                                <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="text-muted">
                                    <?php echo htmlspecialchars($item['color']); ?><br>
                                    Size: <?php echo ucfirst($item['size']); ?><br>
                                    Quantity: <?php echo $item['quantity']; ?>
                                </p>
                            </div>
                            
                            <div class="order-item-price">
                                LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($subscription): ?>
                    <!-- For subscription orders without regular order items -->
                    <div class="order-item">
                        <img src="img/swatch_book.jpg" alt="Swatch Book" class="order-item-image">
                        
                        <div class="order-item-details">
                            <h5>Premium Linen Swatch Book - <?php echo ucfirst($subscription['swatch_type']); ?> Collection</h5>
                            <p class="text-muted">
                                Subscription: <?php echo $subscription['duration']; ?> <?php echo $subscription['duration'] == 1 ? 'month' : 'months'; ?><br>
                                Type: <?php echo ucfirst($subscription['swatch_type']); ?><br>
                                Quantity: 1
                            </p>
                        </div>
                        
                        <div class="order-item-price">
                            LKR <?php echo number_format($subscription['total_amount'], 2); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="order-summary mt-4">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>LKR <?php echo number_format($order['total_amount'] - $order['delivery_cost'], 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>LKR <?php echo number_format($order['delivery_cost'], 2); ?></span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total</span>
                        <span>LKR <?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>