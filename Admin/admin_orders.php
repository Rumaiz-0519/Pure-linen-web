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

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Get admin name
$admin_name = $_SESSION['firstName'] ?? 'Admin';

$success_message = '';
$error_message = '';

// Handle order status update
if (isset($_POST['update_status']) && !empty($_POST['order_id']) && !empty($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $update_stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $status, $order_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status: " . $conn->error;
    }
}

// Handle order deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $order_id = $_GET['delete'];
    
    // Start transaction to ensure both orders and order_items are deleted properly
    $conn->begin_transaction();
    
    try {
        // Check if order_items table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'order_items'");
        if ($table_check->num_rows > 0) {
            // Delete order items first
            $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $delete_items_stmt->bind_param("i", $order_id);
            $delete_items_stmt->execute();
        }
        
        // Then delete the order
        $delete_order_stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $delete_order_stmt->bind_param("i", $order_id);
        $delete_order_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $success_message = "Order deleted successfully!";
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Retrieve orders with user information
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$orders = [];

try {
    // Check if orders and users tables exist
    $tables_exist = true;
    $orders_check = $conn->query("SHOW TABLES LIKE 'orders'");
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($orders_check->num_rows == 0 || $users_check->num_rows == 0) {
        $tables_exist = false;
        $error_message = "Required database tables do not exist. Please set up the database properly.";
    }
    
    if ($tables_exist) {
        if (!empty($status_filter)) {
            $stmt = $conn->prepare("
                SELECT o.*, u.firstName, u.lastName, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.payment_status = ?
                ORDER BY o.order_date DESC
            ");
            $stmt->bind_param("s", $status_filter);
        } else {
            $stmt = $conn->prepare("
                SELECT o.*, u.firstName, u.lastName, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                ORDER BY o.order_date DESC
            ");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error retrieving orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Panel</title>
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
        
        .page-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: var(--primary-color);
        }
        
        .admin-table {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .admin-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
                        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li>
                        <a href="product_management.php"><i class="fas fa-box"></i> Products</a>
                    </li>
                    <li>
                        <a href="admin_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a>
                    </li>
                    <li>
                        <a href="featured_products.php"><i class="fas fa-star"></i> Featured Products</a>
                    </li>
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
                <h1 class="admin-title mb-4">Orders</h1>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <!-- Status filter -->
                <div class="mb-4">
                    <div class="btn-group">
                        <a href="admin_orders.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">All Orders</a>
                        <a href="admin_orders.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pending</a>
                        <a href="admin_orders.php?status=processing" class="btn <?php echo $status_filter === 'processing' ? 'btn-primary' : 'btn-outline-primary'; ?>">Processing</a>
                        <a href="admin_orders.php?status=shipped" class="btn <?php echo $status_filter === 'shipped' ? 'btn-primary' : 'btn-outline-primary'; ?>">Shipped</a>
                        <a href="admin_orders.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Completed</a>
                        <a href="admin_orders.php?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-outline-primary'; ?>">Cancelled</a>
                    </div>
                </div>
                
                <div class="admin-table table-responsive">
                    <?php if (!empty($orders)): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['firstName'] . ' ' . $order['lastName']); ?></td>
                                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                                        <td>LKR <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo ($order['payment_status'] == 'completed') ? 'success' : 
                                                    (($order['payment_status'] == 'pending') ? 'warning' : 
                                                    (($order['payment_status'] == 'cancelled') ? 'danger' : 'info')); 
                                            ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="admin_order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="admin_orders.php?delete=<?php echo $order['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this order?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Status Update Modal -->
                                            <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Order Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="admin_orders.php" method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="status<?php echo $order['id']; ?>" class="form-label">Status</label>
                                                                    <select class="form-select" id="status<?php echo $order['id']; ?>" name="status">
                                                                        <option value="pending" <?php echo ($order['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="processing" <?php echo ($order['payment_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                                        <option value="shipped" <?php echo ($order['payment_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                                                        <option value="completed" <?php echo ($order['payment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                                        <option value="cancelled" <?php echo ($order['payment_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                                    </select>
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
                    <?php else: ?>
                        <div class="alert alert-info">No orders found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>