<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection - using the same connection parameters as admin_orders.php
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

// Helper function to determine text color based on background color for readability
function getContrastColor($hexColor) {
    // Remove the # if it exists
    $hexColor = ltrim($hexColor, '#');
    
    // If the color is not a valid hex, return black
    if (strlen($hexColor) !== 6 || !ctype_xdigit($hexColor)) {
        return '#000000';
    }
    
    // Convert to RGB
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
    
    // Calculate luminance
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    // Return black or white based on brightness
    return ($luminance > 0.5) ? '#000000' : '#FFFFFF';
}

// Check if error message should be displayed
$display_error = false;
$error_message = '';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_orders.php");
    exit();
}

$order_id = $_GET['id'];
$order = null;
$order_items = [];
$success_message = '';

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, u.firstName, u.lastName, u.email, u.phone, u.address, o.delivery_option
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }

    $order = $result->fetch_assoc();

    // Get order items
    $items_stmt = $conn->prepare("
        SELECT oi.*, p.name, p.color, p.image_url 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    
    if (!$items_stmt) {
        throw new Exception("Prepare failed for items: " . $conn->error);
    }
    
    $items_stmt->bind_param("i", $order_id);
    
    if (!$items_stmt->execute()) {
        throw new Exception("Execute failed for items: " . $items_stmt->error);
    }
    
    $items_result = $items_stmt->get_result();
    $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
    
    // After fetching order items, check if there might be issues with image paths
    // Debug the image URLs if needed
    foreach ($order_items as &$item) {
        // If image URL doesn't start with http or /, prepend the relative path
        if (!empty($item['image_url']) && strpos($item['image_url'], 'http') !== 0 && strpos($item['image_url'], '/') !== 0) {
            $item['image_url'] = '../' . $item['image_url'];
        }
    }
    
} catch (Exception $e) {
    $display_error = true;
    $error_message = "Error retrieving order data: " . $e->getMessage();
}

// Handle order status update
if (isset($_POST['update_status']) && !empty($_POST['status'])) {
    $status = $_POST['status'];
    
    try {
        $update_stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $update_stmt->bind_param("si", $status, $order_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Order status updated successfully!";
            // Update the order object to reflect the change
            $order['payment_status'] = $status;
        } else {
            throw new Exception("Failed to update: " . $update_stmt->error);
        }
    } catch (Exception $e) {
        $error_message = "Failed to update order status: " . $e->getMessage();
        $display_error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Panel</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="admin-title">Order #<?php echo $order_id; ?></h1>
                    <a href="admin_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Orders</a>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if ($display_error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($order): ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Order Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Order ID:</strong> <?php echo $order_id; ?></p>
                                <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></p>
                                <p>
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo ($order['payment_status'] == 'completed') ? 'success' : 
                                            (($order['payment_status'] == 'pending') ? 'warning' : 
                                            (($order['payment_status'] == 'cancelled') ? 'danger' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </p>
                                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                <p><strong>Delivery Option:</strong> <?php echo htmlspecialchars($order['delivery_option'] ?? 'Standard Delivery'); ?></p>
                                <p><strong>Total Amount:</strong> LKR <?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['firstName'] . ' ' . $order['lastName']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <?php if (!empty($order['phone'])): ?>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($order['address'])): ?>
                                    <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Order Actions</h5>
                            </div>
                            <div class="card-body">
                                <form action="admin_order_details.php?id=<?php echo $order_id; ?>" method="post">
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Update Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="pending" <?php echo ($order['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($order['payment_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo ($order['payment_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="completed" <?php echo ($order['payment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo ($order['payment_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                </form>
                                
                                <hr>
                                
                                <a href="admin_orders.php?delete=<?php echo $order_id; ?>" class="btn btn-danger w-100" 
                                   onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                                    <i class="fas fa-trash me-2"></i>Delete Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($order_items)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="80">Product</th>
                                        <th>Name</th>
                                        <th>Color</th>
                                        <th>Size</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($item['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail" style="max-width: 70px; max-height: 70px;">
                                                <?php else: ?>
                                                    <div class="bg-light text-center" style="width: 70px; height: 70px; line-height: 70px;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td>
                                                <?php 
                                                // Check if color is a valid hex color
                                                if (preg_match('/^#[a-f0-9]{6}$/i', $item['color'])) {
                                                    // It's a hex color, display as a colored badge
                                                    echo '<span class="badge rounded-pill" style="background-color: ' . htmlspecialchars($item['color']) . '; color: ' . getContrastColor($item['color']) . ';">';
                                                    echo htmlspecialchars($item['color']);
                                                    echo '</span>';
                                                } else {
                                                    // It's a named color, map to appropriate hex and display as badge
                                                    $colorMap = [
                                                        'red' => '#ff0000',
                                                        'blue' => '#0000ff',
                                                        'dark blue' => '#00008b',
                                                        'deep blue' => '#000080',
                                                        'light blue' => '#add8e6',
                                                        'green' => '#008000',
                                                        'sage green' => '#9caf88',
                                                        'yellow' => '#ffff00',
                                                        'black' => '#000000',
                                                        'white' => '#ffffff',
                                                        'pure white' => '#ffffff',
                                                        'gray' => '#808080',
                                                        'grey' => '#808080',
                                                        'purple' => '#800080',
                                                        'pink' => '#ffc0cb',
                                                        'orange' => '#ffa500',
                                                        'brown' => '#a52a2a',
                                                        'golden brown' => '#996515',
                                                        'beige' => '#f5f5dc',
                                                        'beige tan' => '#e8d0a9',
                                                        'tan' => '#d2b48c'
                                                    ];
                                                    
                                                    $lowerColor = strtolower(trim($item['color']));
                                                    $bgColor = isset($colorMap[$lowerColor]) ? $colorMap[$lowerColor] : '#e2e2e2'; // Default to light gray if not found
                                                    
                                                    if (isset($colorMap[$lowerColor])) {
                                                        $textColor = getContrastColor(ltrim($colorMap[$lowerColor], '#'));
                                                    } else {
                                                        $textColor = '#000000'; // Default to black text
                                                    }
                                                    
                                                    echo '<span class="badge rounded-pill" style="background-color: ' . $bgColor . '; color: ' . $textColor . '; min-width: 100px; font-weight: normal; padding: 5px 10px;">';
                                                    echo htmlspecialchars($item['color']);
                                                    echo '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo ucfirst($item['size']); ?></td>
                                            <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                                            <td><strong><?php echo $item['quantity']; ?></strong></td>
                                            <td>LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-info">No order items found for this order.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Order not found or could not be retrieved. Please go back to the orders list and try again.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>