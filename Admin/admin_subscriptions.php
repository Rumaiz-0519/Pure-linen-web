<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "pure_linen";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in as admin - removing redirect to prevent loop
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Instead of redirecting, show an error message
    $error_message = "You must be logged in as an admin to view this page.";
} else {
    $success_message = '';
    $error_message = '';

    // Handle subscription status update
    if (isset($_POST['update_status']) && !empty($_POST['subscription_id']) && !empty($_POST['status'])) {
        $subscription_id = $_POST['subscription_id'];
        $status = $_POST['status'];
        
        $update_stmt = $conn->prepare("UPDATE swatch_subscriptions SET payment_status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $status, $subscription_id);
        
        if ($update_stmt->execute()) {
            // Also update related order
            $update_order = $conn->prepare("UPDATE orders SET payment_status = ? WHERE subscription_id = ?");
            $update_order->bind_param("si", $status, $subscription_id);
            $update_order->execute();
            
            $success_message = "Subscription status updated successfully!";
        } else {
            $error_message = "Failed to update subscription status.";
        }
    }

    // Get status filter
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    // Build the query with optional filters
    $query = "
        SELECT s.*, u.firstName, u.lastName, u.email, u.phone,
               o.id as order_id, o.payment_status as order_status
        FROM swatch_subscriptions s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN orders o ON s.id = o.subscription_id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if (!empty($status_filter)) {
        $query .= " AND s.payment_status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if (!empty($search_term)) {
        $search_term = "%$search_term%";
        $query .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.email LIKE ? OR CAST(s.id AS CHAR) LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ssss";
    }

    $query .= " ORDER BY s.created_at DESC";

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $subscriptions = [];

    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }

    // Get subscription counts by status
    $status_counts = [
        'all' => 0,
        'pending' => 0,
        'active' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];

    $count_stmt = $conn->query("
        SELECT payment_status, COUNT(*) as count 
        FROM swatch_subscriptions 
        GROUP BY payment_status
    ");

    while ($count = $count_stmt->fetch_assoc()) {
        $status = $count['payment_status'];
        $status_counts[$status] = $count['count'];
        $status_counts['all'] += $count['count'];
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
    <title>Swatch Subscriptions - Admin Dashboard</title>
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
        
        .status-filter {
            margin-bottom: 20px;
        }
        
        .status-filter .btn {
            position: relative;
            padding-right: 30px;
        }
        
        .status-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #dc3545;
            color: white;
        }
        
        .delivery-badge {
            font-size: 11px;
            padding: 3px 6px;
        }
        
        .progress-sm {
            height: 8px;
            margin-bottom: 5px;
        }
        
        .order-link {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .order-link:hover {
            text-decoration: underline;
        }
        
        .customer-info {
            line-height: 1.3;
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

    <?php if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin'): ?>
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
                        <a href="featured_products.php"><i class="fas fa-star"></i> Featured Products</a>
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
                        <h1 class="h2">Swatch Subscriptions</h1>
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
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
                    
                    <!-- Search and Filter Row -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form action="admin_subscriptions.php" method="get" class="d-flex">
                                <?php if (!empty($status_filter)): ?>
                                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                <?php endif; ?>
                                <input type="text" name="search" class="form-control me-2" placeholder="Search by name, email or ID" value="<?php echo htmlspecialchars($search_term ?? ''); ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                <?php if (!empty($search_term)): ?>
                                    <a href="admin_subscriptions.php<?php echo !empty($status_filter) ? '?status='.$status_filter : ''; ?>" class="btn btn-outline-secondary ms-2">Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="status-filter btn-group">
                                <a href="admin_subscriptions.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    All <span class="badge bg-secondary status-badge"><?php echo $status_counts['all'] ?? 0; ?></span>
                                </a>
                                <a href="admin_subscriptions.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                    Pending <span class="badge bg-warning text-dark status-badge"><?php echo $status_counts['pending'] ?? 0; ?></span>
                                </a>
                                <a href="admin_subscriptions.php?status=active" class="btn <?php echo $status_filter === 'active' ? 'btn-info' : 'btn-outline-info'; ?>">
                                    Active <span class="badge bg-info status-badge"><?php echo $status_counts['active'] ?? 0; ?></span>
                                </a>
                                <a href="admin_subscriptions.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">
                                    Completed <span class="badge bg-success status-badge"><?php echo $status_counts['completed'] ?? 0; ?></span>
                                </a>
                                <a href="admin_subscriptions.php?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                    Cancelled <span class="badge bg-danger status-badge"><?php echo $status_counts['cancelled'] ?? 0; ?></span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($subscriptions)): ?>
                                <div class="alert alert-info">No subscriptions found.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Customer</th>
                                                <th>Subscription</th>
                                                <th>Order</th>
                                                <th>Duration / Progress</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subscriptions as $sub): ?>
                                            <?php
                                                // Calculate subscription progress
                                                $start_date = new DateTime($sub['start_date']);
                                                $end_date = new DateTime($sub['end_date']);
                                                $current_date = new DateTime();
                                                
                                                $total_days = $start_date->diff($end_date)->days;
                                                $days_elapsed = $start_date->diff($current_date)->days;
                                                
                                                $progress_percentage = 0;
                                                if ($total_days > 0 && $sub['payment_status'] === 'active') {
                                                    $progress_percentage = min(100, max(0, ($days_elapsed / $total_days) * 100));
                                                }
                                                
                                                // Calculate delivery status
                                                $total_deliveries = $sub['duration'];
                                                $months_elapsed = floor($days_elapsed / 30);
                                                $deliveries_made = min($total_deliveries, max(1, $months_elapsed + 1)); // +1 for initial delivery
                                                $deliveries_remaining = max(0, $total_deliveries - $deliveries_made);
                                            ?>
                                            <tr>
                                                <td><?php echo $sub['id']; ?></td>
                                                <td>
                                                    <div class="customer-info">
                                                        <strong><?php echo htmlspecialchars($sub['firstName'] . ' ' . $sub['lastName']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($sub['email']); ?></small><br>
                                                        <?php if (!empty($sub['phone'])): ?>
                                                            <small><?php echo htmlspecialchars($sub['phone']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong>Premium Linen Swatch Book</strong><br>
                                                    <?php echo ucfirst($sub['swatch_type']); ?> Collection<br>
                                                    <small>Created: <?php echo date('M d, Y', strtotime($sub['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($sub['order_id'])): ?>
                                                        <a href="admin_order_details.php?id=<?php echo $sub['order_id']; ?>" class="order-link">
                                                            Order #<?php echo $sub['order_id']; ?>
                                                        </a>
                                                        <br>
                                                        <?php
                                                            $order_status_class = '';
                                                            switch($sub['order_status']) {
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
                                                        <span class="badge <?php echo $order_status_class; ?>">
                                                            <?php echo ucfirst($sub['order_status']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No order linked</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div><?php echo $sub['duration']; ?> months</div>
                                                    <div class="small text-muted">
                                                        <?php echo date('M d, Y', strtotime($sub['start_date'])); ?> - 
                                                        <?php echo date('M d, Y', strtotime($sub['end_date'])); ?>
                                                    </div>
                                                    
                                                    <?php if ($sub['payment_status'] === 'active'): ?>
                                                        <div class="progress progress-sm mt-2">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%" aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <small class="text-muted"><?php echo round($progress_percentage); ?>%</small>
                                                            <small class="text-muted">
                                                                <?php 
                                                                    $days_left = max(0, $end_date->diff($current_date)->days);
                                                                    echo $days_left . ' days left';
                                                                ?>
                                                            </small>
                                                        </div>
                                                        
                                                        <div class="mt-1">
                                                            <span class="badge bg-info delivery-badge">
                                                                <?php echo $deliveries_made; ?>/<?php echo $total_deliveries; ?> deliveries
                                                            </span>
                                                            <?php if ($deliveries_remaining > 0): ?>
                                                                <span class="badge bg-secondary delivery-badge">
                                                                    Next: <?php echo (clone $start_date)->modify('+'.($deliveries_made).' months')->format('M d'); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $status_class = '';
                                                        switch($sub['payment_status']) {
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
                                                        <?php echo ucfirst($sub['payment_status']); ?>
                                                    </span>
                                                    <div class="mt-2">
                                                        <strong>LKR <?php echo number_format($sub['total_amount'], 2); ?></strong><br>
                                                        <small>
                                                            <?php
                                                                switch($sub['payment_method']) {
                                                                    case 'card':
                                                                        echo '<i class="fas fa-credit-card me-1"></i> Card';
                                                                        break;
                                                                    case 'bank':
                                                                        echo '<i class="fas fa-university me-1"></i> Bank Transfer';
                                                                        break;
                                                                    default:
                                                                        echo ucfirst($sub['payment_method']);
                                                                }
                                                            ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $sub['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="admin_subscription_details.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                    
                                                    <!-- Status Update Modal -->
                                                    <div class="modal fade" id="statusModal<?php echo $sub['id']; ?>" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="statusModalLabel">Update Subscription Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="admin_subscriptions.php<?php echo !empty($status_filter) ? '?status='.$status_filter : ''; ?>" method="post">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="status<?php echo $sub['id']; ?>" class="form-label">Status</label>
                                                                            <select class="form-select" id="status<?php echo $sub['id']; ?>" name="status">
                                                                                <option value="pending" <?php echo ($sub['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                                <option value="completed" <?php echo ($sub['payment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                                                <option value="active" <?php echo ($sub['payment_status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                                                <option value="cancelled" <?php echo ($sub['payment_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="alert alert-info">
                                                                            <i class="fas fa-info-circle me-2"></i>
                                                                            Updating the subscription status will also update the associated order status.
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>