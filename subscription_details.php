<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if subscription ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: subscription_history.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$subscription_id = $_GET['id'];

// Get subscription details and verify it belongs to the user
$stmt = $conn->prepare("
    SELECT s.*, u.firstName, u.lastName, u.email, u.phone
    FROM swatch_subscriptions s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ? AND s.user_id = ?
");
$stmt->bind_param("ii", $subscription_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: subscription_history.php");
    exit();
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

// Get delivery statuses from database
$delivery_statuses = [];
$delivery_stmt = $conn->prepare("
    SELECT * FROM subscription_deliveries 
    WHERE subscription_id = ? 
    ORDER BY delivery_number
");
$delivery_stmt->bind_param("i", $subscription_id);
$delivery_stmt->execute();
$delivery_result = $delivery_stmt->get_result();

while ($delivery = $delivery_result->fetch_assoc()) {
    $delivery_statuses[$delivery['delivery_number']] = $delivery;
}

// Calculate subscription progress
$start_date = new DateTime($subscription['start_date']);
$end_date = new DateTime($subscription['end_date']);
$current_date = new DateTime();

$total_days = $start_date->diff($end_date)->days;
$days_elapsed = $start_date->diff($current_date)->days;
$days_elapsed = min($days_elapsed, $total_days); // Cap at total days

$progress_percentage = 0;
if ($total_days > 0 && $subscription['payment_status'] === 'active') {
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Details - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .subscription-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .subscription-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f2f2f2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .subscription-status-badge {
            font-size: 14px;
            padding: 8px 12px;
        }
        
        .subscription-details {
            margin-bottom: 30px;
        }
        
        .subscription-details-title {
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
        <div class="mb-4">
            <a href="subscription_history.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Back to Subscriptions</a>
        </div>
        
        <div class="subscription-container">
            <div class="subscription-header">
                <div>
                    <h2>Subscription #<?php echo $subscription_id; ?></h2>
                    <p class="text-muted">Created on <?php echo date('F d, Y h:i A', strtotime($subscription['created_at'])); ?></p>
                </div>
                <div>
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
                    <span class="badge <?php echo $status_class; ?> subscription-status-badge">
                        <?php echo ucfirst($subscription['payment_status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="subscription-details">
                        <h3 class="subscription-details-title">Subscription Details</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Type:</div>
                            <div class="detail-value">Premium Linen Swatch Book - <?php echo ucfirst($subscription['swatch_type']); ?> Collection</div>
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
                            <div class="detail-label">Total Amount:</div>
                            <div class="detail-value">LKR <?php echo number_format($subscription['total_amount'], 2); ?></div>
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
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($subscription['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($order_id): ?>
                            <div class="detail-row">
                                <div class="detail-label">Order ID:</div>
                                <div class="detail-value">
                                    <a href="order_details.php?id=<?php echo $order_id; ?>">#<?php echo $order_id; ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="subscription-details">
                        <h3 class="subscription-details-title">Subscription Progress</h3>
                        
                        <?php if ($subscription['payment_status'] === 'active'): ?>
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_percentage; ?>%" aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <small><?php echo date('M d, Y', strtotime($subscription['start_date'])); ?></small>
                                <small><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></small>
                            </div>
                            
                            <div class="delivery-info">
                                <h5 class="mb-3">Delivery Status</h5>
                                <p><i class="fas fa-box-open me-2"></i> <strong>Deliveries made:</strong> <?php echo $deliveries_made; ?> of <?php echo $total_deliveries; ?></p>
                                <?php if ($deliveries_remaining > 0): ?>
                                    <p><i class="fas fa-truck me-2"></i> <strong>Next delivery:</strong> <?php echo $next_delivery->format('F d, Y'); ?></p>
                                <?php else: ?>
                                    <p><i class="fas fa-check-circle me-2"></i> <strong>All deliveries completed</strong></p>
                                <?php endif; ?>
                                
                                <p><i class="fas fa-calendar-alt me-2"></i> <strong>Days remaining:</strong> 
                                    <?php echo max(0, $current_date->diff($end_date)->days); ?> days
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-secondary">
                                <p><i class="fas fa-info-circle me-2"></i> 
                                    <?php if ($subscription['payment_status'] === 'completed'): ?>
                                        This subscription has been completed.
                                    <?php elseif ($subscription['payment_status'] === 'pending'): ?>
                                        This subscription is pending payment confirmation.
                                    <?php elseif ($subscription['payment_status'] === 'cancelled'): ?>
                                        This subscription has been cancelled.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="subscription-details mt-4">
                        <h3 class="subscription-details-title">Delivery Schedule</h3>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Delivery #</th>
                                        <th>Scheduled Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 1; $i <= $total_deliveries; $i++): ?>
                                        <?php
                                            $delivery_date = clone $start_date;
                                            if ($i > 1) {
                                                $delivery_date->modify("+" . ($i - 1) . " months");
                                            }
                                            
                                            // Check if we have delivery status from database
                                            if (isset($delivery_statuses[$i])) {
                                                $delivery_info = $delivery_statuses[$i];
                                                $status = $delivery_info['status'];
                                                $delivered_date = !empty($delivery_info['delivered_date']) ? date('M d, Y', strtotime($delivery_info['delivered_date'])) : '';
                                            } else {
                                                // Default status based on calculation
                                                if ($i < $deliveries_made) {
                                                    $status = 'delivered';
                                                    $delivered_date = '';
                                                } elseif ($i == $deliveries_made) {
                                                    $status = 'processing';
                                                    $delivered_date = '';
                                                } else {
                                                    $status = 'pending';
                                                    $delivered_date = '';
                                                }
                                            }
                                            
                                            // Display status badge
                                            switch($status) {
                                                case 'delivered':
                                                    $status_badge = '<span class="badge bg-success">Delivered</span>';
                                                    if (!empty($delivered_date)) {
                                                        $status_badge .= '<br><small>' . $delivered_date . '</small>';
                                                    }
                                                    break;
                                                case 'processing':
                                                    $status_badge = '<span class="badge bg-info">Processing</span>';
                                                    break;
                                                default:
                                                    $status_badge = '<span class="badge bg-secondary">Scheduled</span>';
                                            }
                                            
                                            // Determine row highlight
                                            $row_class = '';
                                            if ($status === 'processing') {
                                                $row_class = 'table-info';
                                            }
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td>Delivery <?php echo $i; ?></td>
                                            <td><?php echo $delivery_date->format('F d, Y'); ?></td>
                                            <td><?php echo $status_badge; ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>