<?php
session_start();
require_once 'config.php';

$session_id = session_id();
$cartItems = [];
$total = 0;

try {
    // Get cart items with product details
    $sql = "SELECT ci.*, p.name, p.color, p.price, p.half_price, p.image_url, p.id as product_id 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.session_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Calculate item price based on size (meter or cm)
        $itemPrice = ($row['size'] === 'cm') ? $row['half_price'] : $row['price'];
        $row['unit_price'] = $itemPrice;
        
        // Calculate subtotal
        $itemTotal = $itemPrice * $row['quantity'];
        $row['subtotal'] = $itemTotal;
        
        $cartItems[] = $row;
        $total += $itemTotal;
    }
    
    // Update session cart count
    $_SESSION['cart_count'] = count($cartItems) > 0 ? array_sum(array_column($cartItems, 'quantity')) : 0;
    
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #1B365D;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .nav-item {
            position: relative;
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
                        <a class="nav-link active" href="cart.php" aria-label="Shopping Cart">
                            <i class="fas fa-cart-plus"></i>
                            <?php if (!empty($cartItems)): ?>
                                <span class="cart-badge"><?php echo $_SESSION['cart_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><p class="dropdown-item mb-0">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="profile.php">Update Profile</a></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a class="nav-link" href="login.php" aria-label="User Account">
                                <i class="fas fa-user"></i>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-lg-8">
                <h2 class="mb-4">Shopping Cart</h2>
                
                <?php if (empty($cartItems)): ?>
                    <div class="alert alert-info">
                        Your cart is empty. <a href="shop.php" class="alert-link">Continue shopping</a>.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        You have <?php echo count($cartItems); ?> item(s) in your cart.
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item border p-3 mb-3 rounded">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="img-fluid rounded">
                                </div>
                                <div class="col-md-3">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($item['color']); ?></p>
                                    <p>Size: <?php echo htmlspecialchars(ucfirst($item['size'])); ?></p>
                                </div>
                                <div class="col-md-3 text-center">
                                    <p>LKR <?php echo number_format($item['unit_price'], 2); ?></p>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" 
                                        class="form-control" 
                                        value="<?php echo $item['quantity']; ?>" 
                                        min="1"
                                        onchange="updateQuantity(this, <?php echo $item['id']; ?>, <?php echo $item['unit_price']; ?>)">
                                </div>
                                <div class="col-md-1 text-center">
                                    <p>LKR <?php echo number_format($item['subtotal'], 2); ?></p>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button class="btn btn-sm btn-danger" onclick="removeItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h3 class="m-0">Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cartItems)): ?>
                            <p>Your cart is currently empty.</p>
                            <div class="text-center mt-4">
                                <a href="shop.php" class="btn btn-primary">Discover Products</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cartItems as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($item['name']); ?> (×<?php echo $item['quantity']; ?>)</span>
                                    <span>LKR <?php echo number_format($item['subtotal'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>LKR <?php echo number_format($total, 2); ?></strong>
                            </div>
                            
                            <div class="mt-4">
                            <div class="mt-4">
                                <a href="checkout.php" class="btn btn-success w-100">PROCEED TO CHECKOUT</a>
                            </div>
                                
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h3>TOP RIZ INTERNATIONAL</h3>
                        <p>Creating elegant solutions in textile trade. Premium fabrics. Global reach. Trusted quality.
                        </p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="index.php">HOME</a></li>
                            <li><a href="shop.php">SAMPLE FABRIC</a></li>
                            <li><a href="swatch.php">SWATCH BOOK</a></li>
                            <li><a href="#orders">ORDERS</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Extra Links</h4>
                        <ul>
                            <li><a href="login.php">LOGIN</a></li>
                            <li><a href="login.php">SIGNUP</a></li>
                            <li><a href="cart.php">CART</a></li>
                            <li><a href="fabric_calculator.php">FABRIC CALCULATOR</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Contact Us</h4>
                        <p><i class="fas fa-phone"></i> +94 777 123456</p>
                        <p><i class="fas fa-phone"></i> +94 777 123456</p>
                        <p><i class="fas fa-envelope"></i> info@topriz.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> 1/2 Crow Island, Colombo</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; 2024 Pure Linen. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    function updateQuantity(input, itemId, price) {
        const quantity = parseInt(input.value);
        if (quantity < 1) {
            input.value = 1;
            return;
        }

        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update&id=${itemId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the cart');
        });
    }

    function removeItem(itemId) {
        if (!confirm('Are you sure you want to remove this item?')) {
            return;
        }

        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove&id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to remove item');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the item');
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>