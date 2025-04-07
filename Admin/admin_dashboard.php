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
    header("Location: admin_login.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['firstName'] ?? 'Admin';

// Get basic statistics for dashboard
$users_count = 0;
$products_count = 0;
$orders_count = 0;
$pending_orders = 0;
$subscriptions_count = 0;
$active_subscriptions = 0;

// Count users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'user'");
if ($result && $row = $result->fetch_assoc()) {
    $users_count = $row['count'];
}

// Count products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result && $row = $result->fetch_assoc()) {
    $products_count = $row['count'];
}

// Count orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result && $row = $result->fetch_assoc()) {
    $orders_count = $row['count'];
}

// Count pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE payment_status = 'pending'");
if ($result && $row = $result->fetch_assoc()) {
    $pending_orders = $row['count'];
}

// Count total subscriptions
$result = $conn->query("SELECT COUNT(*) as count FROM swatch_subscriptions");
if ($result && $row = $result->fetch_assoc()) {
    $subscriptions_count = $row['count'];
}

// Count active subscriptions
$result = $conn->query("SELECT COUNT(*) as count FROM swatch_subscriptions WHERE payment_status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $active_subscriptions = $row['count'];
}

// Get recent orders
$recent_orders = [];
$order_result = $conn->query("SELECT o.id, o.total_amount, o.payment_status, u.firstName, u.lastName 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id 
                             ORDER BY o.order_date DESC LIMIT 5");
if ($order_result) {
    while ($row = $order_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Get recent subscriptions
$recent_subscriptions = [];
$sub_result = $conn->query("SELECT s.id, s.swatch_type, s.duration, s.total_amount, s.payment_status, 
                            u.firstName, u.lastName 
                            FROM swatch_subscriptions s 
                            JOIN users u ON s.user_id = u.id 
                            ORDER BY s.created_at DESC LIMIT 5");
if ($sub_result) {
    while ($row = $sub_result->fetch_assoc()) {
        $recent_subscriptions[] = $row;
    }
}

// Get latest messages
$latest_messages = [];
$msg_result = $conn->query("SELECT name, email, created_at FROM messages ORDER BY created_at DESC LIMIT 5");
if ($msg_result) {
    while ($row = $msg_result->fetch_assoc()) {
        $latest_messages[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pure Linen</title>
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
        
        .dashboard-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: var(--primary-color);
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 7px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
        }
        
        .stat-card.primary .icon {
            background-color: var(--primary-color);
        }
        
        .stat-card.success .icon {
            background-color: var(--success-color);
        }
        
        .stat-card.warning .icon {
            background-color: var(--warning-color);
        }
        
        .stat-card.danger .icon {
            background-color: var(--danger-color);
        }
        
        .stat-card.info .icon {
            background-color: var(--info-color);
        }
        
        .stat-card .title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(27, 54, 93, 0.05);
        }
        
        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }
        
        .card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
        }
        
        .list-group-item {
            border-color: rgba(0,0,0,0.05);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
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

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-title">Dashboard</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
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
                    <li>
                        <a href="admin_subscriptions.php"><i class="fas fa-book"></i> Subscriptions</a>
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
                <h1 class="dashboard-title">Welcome to Admin Dashboard</h1>
                
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="title">Total Users</div>
                            <div class="value"><?php echo $users_count; ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card success">
                            <div class="icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="title">Products</div>
                            <div class="value"><?php echo $products_count; ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card warning">
                            <div class="icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="title">Total Orders</div>
                            <div class="value"><?php echo $orders_count; ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card danger">
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="title">Pending Orders</div>
                            <div class="value"><?php echo $pending_orders; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card info">
                            <div class="icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="title">Total Subscriptions</div>
                            <div class="value"><?php echo $subscriptions_count; ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card primary">
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="title">Active Subscriptions</div>
                            <div class="value"><?php echo $active_subscriptions; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Quick Actions</h5>
                                <div class="btn-group" role="group">
                                    <a href="product_management.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
                                    <a href="admin_orders.php" class="btn btn-info"><i class="fas fa-eye"></i> View Orders</a>
                                    <a href="admin_subscriptions.php" class="btn btn-warning"><i class="fas fa-book"></i> View Subscriptions</a>
                                    <a href="admin_users.php" class="btn btn-success"><i class="fas fa-user-plus"></i> View Users</a>
                                    <a href="../index.php" class="btn btn-secondary"><i class="fas fa-globe"></i> View Website</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Orders</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_orders)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No orders found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td><?php echo $order['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['firstName'] . ' ' . $order['lastName']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo ($order['payment_status'] == 'completed') ? 'success' : (($order['payment_status'] == 'pending') ? 'warning' : 'info'); ?>">
                                                                <?php echo ucfirst($order['payment_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>LKR <?php echo number_format($order['total_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="admin_orders.php" class="btn btn-sm btn-primary">View All Orders</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Subscriptions</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Customer</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_subscriptions)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No subscriptions found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_subscriptions as $sub): ?>
                                                    <tr>
                                                        <td><?php echo $sub['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($sub['firstName'] . ' ' . $sub['lastName']); ?></td>
                                                        <td><?php echo ucfirst($sub['swatch_type']); ?></td>
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
                                                        </td>
                                                        <td>LKR <?php echo number_format($sub['total_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="admin_subscriptions.php" class="btn btn-sm btn-primary">View All Subscriptions</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Latest Messages</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($latest_messages)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No messages found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($latest_messages as $message): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                                        <td>
                                                            <a href="admin_messages.php" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="admin_messages.php" class="btn btn-sm btn-primary">View All Messages</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>