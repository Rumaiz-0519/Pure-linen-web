<?php
session_start();
require_once 'config.php';

// Check if there's an order ID in the session
if (!isset($_SESSION['order_id'])) {
    header("Location: shop.php");
    exit();
}

$order_id = $_SESSION['order_id'];

// Get order details
try {
    $stmt = $conn->prepare("
        SELECT o.*, u.firstName, u.lastName, u.email, u.phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
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
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $order_items = [];

    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }

    // Use session email if not logged in
    if (empty($order['email']) && isset($_SESSION['order_email'])) {
        $order['email'] = $_SESSION['order_email'];
    }

    // Use session name if not logged in
    if (empty($order['firstName']) && isset($_SESSION['order_name'])) {
        $name_parts = explode(' ', $_SESSION['order_name'], 2);
        $order['firstName'] = $name_parts[0];
        $order['lastName'] = isset($name_parts[1]) ? $name_parts[1] : '';
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error retrieving order: " . $e->getMessage();
    header("Location: shop.php");
    exit();
}

// Clear the order ID from session to prevent refreshing
// unset($_SESSION['order_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Pure Linen</title>
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
        
        .order-details {
            margin-bottom: 30px;
        }
        
        .order-detail-title {
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
        
        .order-items {
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
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
    </style>
</head>
<body>
    <!--NAVIGATION-->
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-2 fixed-top">
        <div class="container">
            <img src="img/logo.png" alt="Pure Linen Logo" class="navbar-brand">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Linen Fabric</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="swatch.php">Swatch Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#search" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php" aria-label="Shopping Cart">
                            <i class="fas fa-cart-plus"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h2>Thank You for Your Order!</h2>
                <p>Order #<?php echo $order_id; ?> has been placed successfully.</p>
                <p>A confirmation email will be sent to <?php echo htmlspecialchars($order['email'] ?? 'your email address'); ?></p>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="order-details">
                        <h3 class="order-detail-title">Order Details</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Order Number:</div>
                            <div class="detail-value">#<?php echo $order_id; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div class="detail-value"><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></div>
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
                                Account Name: Pure Linen Ltd<br>
                                Reference: Order #<?php echo $order_id; ?></p>
                                <p>Please include your order number as reference when making the transfer.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="order-details">
                        <h3 class="order-detail-title">Shipping Details</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Name:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars(($order['firstName'] ?? '') . ' ' . ($order['lastName'] ?? '')); ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Address:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'] ?? '')); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">City:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['delivery_city'] ?? ''); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Country:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['delivery_country'] ?? ''); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Delivery Option:</div>
                            <div class="detail-value">
                                <?php 
                                    echo ($order['delivery_option'] ?? '') === 'express' ? 
                                        'Express Shipping (1-2 business days)' : 
                                        'Standard Shipping (5-7 business days)'; 
                                ?>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <p><i class="fas fa-truck me-2"></i> <strong>Estimated Delivery Date:</strong></p>
                            <p>
                                <?php
                                    $order_date = new DateTime($order['order_date']);
                                    if (($order['delivery_option'] ?? '') === 'express') {
                                        $delivery_days = 2; // For express
                                    } else {
                                        $delivery_days = 7; // For standard
                                    }
                                    $delivery_date = clone $order_date;
                                    $delivery_date->modify("+$delivery_days days");
                                    echo $delivery_date->format('F d, Y');
                                ?>
                            </p>
                            <p class="mb-0">We'll notify you when your order has been shipped.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="order-items mt-4">
                <h3 class="order-detail-title">Order Items</h3>
                
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
                
                <div class="order-summary mt-4">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>LKR <?php echo number_format($order['total_amount'] - ($order['delivery_cost'] ?? 0), 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>LKR <?php echo number_format($order['delivery_cost'] ?? 0, 2); ?></span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total</span>
                        <span>LKR <?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="shop.php" class="continue-btn">Continue Shopping</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>