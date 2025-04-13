<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Create a standalone database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "pure_linen";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Instead of redirecting, show an error message
    $error_message = "You must be logged in as an admin to view this page.";
    $is_admin = false;
} else {
    $is_admin = true;
    $success_message = '';
    $error_message = '';

    // Check if subscription ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        $error_message = "Subscription ID is required.";
    } else {
        $subscription_id = $_GET['id'];

        // Get subscription details
        $stmt = $conn->prepare("
            SELECT s.*, u.firstName, u.lastName, u.email, u.phone, u.address 
            FROM swatch_subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $subscription_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error_message = "Subscription not found.";
        } else {
            $subscription = $result->fetch_assoc();

            // Get associated order details (if any)
            $order_stmt = $conn->prepare("
                SELECT o.* 
                FROM orders o
                WHERE o.subscription_id = ?
                LIMIT 1
            ");
            $order_stmt->bind_param("i", $subscription_id);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();
            $order = $order_result->fetch_assoc();

            // Create subscription_deliveries table if it doesn't exist
            $conn->query("
                CREATE TABLE IF NOT EXISTS subscription_deliveries (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    subscription_id INT NOT NULL,
                    delivery_number INT NOT NULL,
                    scheduled_date DATE NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    delivered_date DATE NULL,
                    notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (subscription_id) REFERENCES swatch_subscriptions(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_delivery (subscription_id, delivery_number)
                )
            ");

            // Handle status update
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
                $status = $_POST['status'];
                
                $update_stmt = $conn->prepare("UPDATE swatch_subscriptions SET payment_status = ? WHERE id = ?");
                $update_stmt->bind_param("si", $status, $subscription_id);
                
                if ($update_stmt->execute()) {
                    // Also update related order
                    if ($order) {
                        $update_order = $conn->prepare("UPDATE orders SET payment_status = ? WHERE subscription_id = ?");
                        $update_order->bind_param("si", $status, $subscription_id);
                        $update_order->execute();
                    }
                    
                    $success_message = "Subscription status updated successfully!";
                    
                    // Refresh subscription data
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $subscription = $result->fetch_assoc();
                } else {
                    $error_message = "Failed to update subscription status.";
                }
            }

            // Handle delivery status update
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'])) {
                $delivery_num = $_POST['delivery_number'];
                $delivery_date = date('Y-m-d'); // Today's date
                
                // Check if delivery record exists
                $check_stmt = $conn->prepare("
                    SELECT id FROM subscription_deliveries 
                    WHERE subscription_id = ? AND delivery_number = ?
                ");
                $check_stmt->bind_param("ii", $subscription_id, $delivery_num);
                $check_stmt->execute();
                $delivery_result = $check_stmt->get_result();
                
                if ($delivery_result->num_rows > 0) {
                    // Update existing delivery
                    $delivery_id = $delivery_result->fetch_assoc()['id'];
                    $update_delivery = $conn->prepare("
                        UPDATE subscription_deliveries 
                        SET status = 'delivered', delivered_date = ? 
                        WHERE id = ?
                    ");
                    $update_delivery->bind_param("si", $delivery_date, $delivery_id);
                    
                    if ($update_delivery->execute()) {
                        $success_message = "Delivery #$delivery_num has been marked as delivered.";
                    } else {
                        $error_message = "Error updating delivery status: " . $conn->error;
                    }
                } else {
                    // Create new delivey record
                    $scheduled_date = date('Y-m-d', strtotime($subscription['start_date'] . ' + ' . ($delivery_num - 1) . ' months'));
                    $insert_delivery = $conn->prepare("
                        INSERT INTO subscription_deliveries 
                        (subscription_id, delivery_number, scheduled_date, status, delivered_date) 
                        VALUES (?, ?, ?, 'delivered', ?)
                    ");
                    $insert_delivery->bind_param("iiss", $subscription_id, $delivery_num, $scheduled_date, $delivery_date);
                    
                    if ($insert_delivery->execute()) {
                        $success_message = "Delivery #$delivery_num has been marked as delivered.";
                    } else {
                        $error_message = "Error creating delivery record: " . $conn->error;
                    }
                }
            }

            // Calculate subscription progress
            $start_date = new DateTime($subscription['start_date']);
            $end_date = new DateTime($subscription['end_date']);
            $current_date = new DateTime();

            $total_days = $end_date->diff($start_date)->days;
            $days_elapsed = $current_date->diff($start_date)->days;
            $days_elapsed = min($days_elapsed, $total_days); // Cap at total days

            $progress_percentage = 0;
            if ($total_days > 0 && $subscription['payment_status'] === 'active') {
                $progress_percentage = min(100, max(0, ($days_elapsed / $total_days) * 100));
            }

            // Calculate delivery information
            $total_deliveries = $subscription['duration'];
            $months_elapsed = floor($days_elapsed / 30);
            $deliveries_made = min($total_deliveries, max(1, $months_elapsed + 1)); // +1 for initial delivery
            $deliveries_remaining = max(0, $total_deliveries - $deliveries_made);
            
            // Get delivery status from database
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
        }
    }
}

// Get admin name
$admin_name = $_SESSION['firstName'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Details - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #1B365D;
            --secondary-color: #708090;
            --dark-color: #0c1d36;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Arial', sans-serif;
            padding-top: 60px;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            min-height: calc(100vh - 60px);
            padding-top: 20px;
            color: white;
        }
        
        .sidebar-title {
            font-size: 20px;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px 15px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .detail-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .detail-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
        }
        
        .detail-card-body {
            padding: 20px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .status-badge {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 4px;
            margin-right: 10px;
            font-weight: 500;
        }
        
        .delivery-table {
            margin-top: 15px;
        }
        
        .delivery-table th {
            background-color: #f8f9fa;
        }
        
        .delivery-badge {
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Login Form Styles */
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            max-width: 100px;
            margin-bottom: 15px;
        }
        
        /* Seasonal section styles */
        .seasonal-card {
            border-left: 4px solid;
            margin-top: 20px;
            background-color: rgba(0,0,0,0.03);
        }
        
        .spring-card {
            border-left-color: #8bc34a;
        }
        
        .summer-card {
            border-left-color: #ffeb3b;
        }
        
        .autumn-card {
            border-left-color: #ff9800;
        }
        
        .winter-card {
            border-left-color: #03a9f4;
        }
        
        .next-delivery-alert {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="ms-2 fw-bold">Pure Linen Admin</span>
            </a>
            
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="admin_profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!$is_admin): ?>
        <!-- Login Form -->
        <div class="login-container">
            <div class="login-header">
                <h2>Admin Login</h2>
                <p>Login to access admin dashboard</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="admin_login.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100" name="login">Login</button>
            </form>
        </div>
    <?php elseif (!empty($error_message) && !isset($subscription)): ?>
        <div class="container mt-5">
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <a href="admin_subscriptions.php" class="btn btn-primary">Back to Subscriptions</a>
        </div>
    <?php else: ?>
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                    <div class="sidebar-title">Dashboard</div>
                    <ul class="sidebar-menu">
                        <li>
                            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
                        </li>
                        <li>
                            <a href="product_management.php"><i class="fas fa-box"></i> Products</a>
                        </li>
                        <li>
                            <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                        </li>
                        <li>
                            <a href="admin_subscriptions.php" class="active"><i class="fas fa-book"></i> Subscriptions</a>
                        </li>
                        <li>
                            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
                        </li>
                        <li>
                            <a href="admin_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                        </li>
                        <li>
                            <a href="admin_admins.php"><i class="fas fa-user-shield"></i> Admins</a>
                        </li>
                        <li>
                            <a href="admin_profile.php"><i class="fas fa-cog"></i> Profile</a>
                        </li>
                        <li>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    </ul>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Subscription Details</h1>
                        <a href="admin_subscriptions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Subscriptions
                        </a>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="fas fa-info-circle me-2"></i>Subscription Information
                                </div>
                                <div class="detail-card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Subscription ID</div>
                                        <div class="col-sm-8">#<?php echo $subscription_id; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Type</div>
                                        <div class="col-sm-8">Premium Linen Swatch Book - <?php echo ucfirst($subscription['swatch_type']); ?> Collection</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Duration</div>
                                        <div class="col-sm-8"><?php echo $subscription['duration']; ?> months</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Start Date</div>
                                        <div class="col-sm-8"><?php echo date('F d, Y', strtotime($subscription['start_date'])); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">End Date</div>
                                        <div class="col-sm-8"><?php echo date('F d, Y', strtotime($subscription['end_date'])); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Amount</div>
                                        <div class="col-sm-8">LKR <?php echo number_format($subscription['total_amount'], 2); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Payment Method</div>
                                        <div class="col-sm-8">
                                            <?php
                                                switch($subscription['payment_method']) {
                                                    case 'card':
                                                        echo 'Credit/Debit Card';
                                                        break;
                                                    case 'bank':
                                                        echo 'Bank Transfer';
                                                        break;
                                                    default:
                                                        echo ucfirst($subscription['payment_method']);
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Status</div>
                                        <div class="col-sm-8">
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
                                            <span class="badge <?php echo $status_class; ?> status-badge">
                                                <?php echo ucfirst($subscription['payment_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Created On</div>
                                        <div class="col-sm-8"><?php echo date('F d, Y h:i A', strtotime($subscription['created_at'])); ?></div>
                                    </div>
                                    
                                    <?php if ($subscription['payment_status'] === 'active'): ?>
                                        <div class="mt-4">
                                            <h5>Subscription Progress</h5>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%;" aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo round($progress_percentage); ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <?php 
                                                    $days_left = max(0, $end_date->diff($current_date)->days);
                                                    echo $days_left . ' days left';
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="fas fa-cog me-2"></i>Actions
                                </div>
                                <div class="detail-card-body">
                                    <form action="admin_subscription_details.php?id=<?php echo $subscription_id; ?>" method="post">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Update Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="pending" <?php echo ($subscription['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="completed" <?php echo ($subscription['payment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="active" <?php echo ($subscription['payment_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="cancelled" <?php echo ($subscription['payment_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-primary action-btn">
                                            <i class="fas fa-save me-2"></i>Update Status
                                        </button>
                                        
                                        <?php if ($order): ?>
                                            <a href="admin_order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-info action-btn">
                                                <i class="fas fa-eye me-2"></i>View Related Order
                                            </a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($order): ?>
                                <div class="detail-card">
                                    <div class="detail-card-header">
                                        <i class="fas fa-file-invoice me-2"></i>Order Details
                                    </div>
                                    <div class="detail-card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-4 detail-label">Order ID</div>
                                            <div class="col-sm-8">#<?php echo $order['id']; ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4 detail-label">Order Date</div>
                                            <div class="col-sm-8"><?php echo date('F d, Y', strtotime($order['order_date'])); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4 detail-label">Amount</div>
                                            <div class="col-sm-8">LKR <?php echo number_format($order['total_amount'], 2); ?></div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4 detail-label">Status</div>
                                            <div class="col-sm-8">
                                                <?php
                                                    $order_status_class = '';
                                                    switch($order['payment_status']) {
                                                        case 'completed':
                                                            $order_status_class = 'bg-success';
                                                            break;
                                                        case 'pending':
                                                            $order_status_class = 'bg-warning';
                                                            break;
                                                        case 'processing':
                                                            $order_status_class = 'bg-info';
                                                            break;
                                                        case 'cancelled':
                                                            $order_status_class = 'bg-danger';
                                                            break;
                                                        default:
                                                            $order_status_class = 'bg-secondary';
                                                    }
                                                ?>
                                                <span class="badge <?php echo $order_status_class; ?> status-badge">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($order['delivery_address'])): ?>
                                            <div class="mt-3">
                                                <h6>Delivery Address</h6>
                                                <p>
                                                    <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?><br>
                                                    <?php echo htmlspecialchars($order['delivery_city']); ?>, 
                                                    <?php echo htmlspecialchars($order['delivery_country']); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="fas fa-user me-2"></i>Customer Information
                                </div>
                                <div class="detail-card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Name</div><div class="col-sm-8"><?php echo htmlspecialchars($subscription['firstName'] . ' ' . $subscription['lastName']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Email</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($subscription['email']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Phone</div>
                                        <div class="col-sm-8"><?php echo !empty($subscription['phone']) ? htmlspecialchars($subscription['phone']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 detail-label">Address</div>
                                        <div class="col-sm-8"><?php echo !empty($subscription['address']) ? nl2br(htmlspecialchars($subscription['address'])) : 'N/A'; ?></div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="admin_user_details.php?id=<?php echo $subscription['user_id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-user me-2"></i>View Customer Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Enhanced Delivery Schedule Card -->
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <i class="fas fa-truck me-2"></i>Delivery Schedule
                                </div>
                                <div class="detail-card-body">
                                    <?php if ($subscription['payment_status'] === 'active' || $subscription['payment_status'] === 'completed'): ?>
                                        <div class="table-responsive delivery-table">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Delivery #</th>
                                                        <th>Scheduled Date</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
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
                                                            
                                                            // Generate the status badge HTML
                                                            switch($status) {
                                                                case 'delivered':
                                                                    $status_html = '<span class="badge bg-success">Delivered</span>';
                                                                    if (!empty($delivered_date)) {
                                                                        $status_html .= '<br><small>' . $delivered_date . '</small>';
                                                                    }
                                                                    break;
                                                                case 'processing':
                                                                    $status_html = '<span class="badge bg-info">Processing</span>';
                                                                    break;
                                                                default:
                                                                    $status_html = '<span class="badge bg-secondary">Pending</span>';
                                                            }
                                                            
                                                            // Generate action button HTML
                                                            if ($status === 'delivered') {
                                                                $action_html = '<button class="btn btn-sm btn-secondary" disabled>Delivered</button>';
                                                            } else {
                                                                $action_html = '
                                                                    <form method="post" action="admin_subscription_details.php?id=' . $subscription_id . '">
                                                                        <input type="hidden" name="delivery_number" value="' . $i . '">
                                                                        <button type="submit" name="mark_delivered" class="btn btn-sm btn-primary">
                                                                            Mark as Delivered
                                                                        </button>
                                                                    </form>
                                                                ';
                                                            }
                                                            
                                                            // Determine row highlight
                                                            $row_class = '';
                                                            if ($status === 'processing') {
                                                                $row_class = 'table-info';
                                                            } elseif ($status === 'delivered') {
                                                                $row_class = '';
                                                            }
                                                        ?>
                                                        <tr class="<?php echo $row_class; ?>">
                                                            <td><?php echo $i; ?></td>
                                                            <td><?php echo $delivery_date->format('M d, Y'); ?></td>
                                                            <td><?php echo $status_html; ?></td>
                                                            <td><?php echo $action_html; ?></td>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <?php
                                            // Calculate next delivery
                                            
                                            $next_delivery_number = 0;
                                            $next_delivery_date = null;
                                            
                                            for ($i = 1; $i <= $total_deliveries; $i++) {
                                                $is_delivered = isset($delivery_statuses[$i]) && $delivery_statuses[$i]['status'] === 'delivered';
                                                
                                                if (!$is_delivered) {
                                                    $next_delivery_number = $i;
                                                    $next_delivery_date = clone $start_date;
                                                    if ($i > 1) {
                                                        $next_delivery_date->modify("+" . ($i - 1) . " months");
                                                    }
                                                    break;
                                                }
                                            }
                                            
                                            if ($next_delivery_number > 0):
                                        ?>
                                            <div class="alert alert-info mt-3">
                                                <div class="d-flex">
                                                    <div class="me-3">
                                                        <i class="fas fa-info-circle fa-2x text-info"></i>
                                                    </div>
                                                    <div>
                                                        <h5>Next Delivery: <?php echo $next_delivery_date->format('M d, Y'); ?></h5>
                                                        <p class="mb-0">Delivery #<?php echo $next_delivery_number; ?> is scheduled next. Ensure the swatch book is prepared for shipment.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Delivery schedule will be available once the subscription is active.
                                        </div>
                                        
                                        <?php if ($subscription['payment_status'] === 'pending'): ?>
                                            <div class="alert alert-info mt-3">
                                                <strong>Action Required:</strong> Approve payment to activate this subscription and start the delivery schedule.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            
                            
                            <?php if ($subscription['payment_method'] === 'bank' && $subscription['payment_status'] === 'pending'): ?>
                                <div class="detail-card">
                                    <div class="detail-card-header">
                                         
                                        <i class="fas fa-university me-2"></i>Bank Transfer Information
                                    </div>
                                    <div class="detail-card-body">
                                        <div class="alert alert-info">
                                            <p>Please remind the customer to transfer LKR <?php echo number_format($subscription['total_amount'], 2); ?> to:</p>
                                            <p>Bank: Commercial Bank<br>
                                            Account Number: 1234567890<br>
                                            Account Name: Pure Linen Ltd<br>
                                            Reference: Subscription #<?php echo $subscription_id; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight the processing delivery row
        const processingRows = document.querySelectorAll('.badge.bg-info');
        processingRows.forEach(function(badge) {
            const row = badge.closest('tr');
            if (row) {
                row.style.backgroundColor = '#f0f9ff';
            }
        });
    });
    </script>
</body>
</html>