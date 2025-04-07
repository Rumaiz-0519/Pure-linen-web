<?php
require_once 'config.php';
session_start();

// Get selected product type (category)
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'pure linen';

// Query to get products of the selected type
$stmt = $conn->prepare("SELECT * FROM products WHERE type = ? ORDER BY id");
$stmt->bind_param("s", $selected_type);
$stmt->execute();
$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Map type to display name (for page titles, etc.)
$category_display_names = [
    'pure linen' => 'Pure Linen',
    'cotton linen' => 'Cotton Linen',
    'blend linen' => 'Blend Linen',
    'printed linen' => 'Printed Linen',
    'dyed linen' => 'Dyed Linen'
];

// Get formatted title for page
$page_title = isset($category_display_names[$selected_type]) ? $category_display_names[$selected_type] : 'Products';

// Get cart count from session if it exists
$cart_count = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    
    <!-- Inline styles to fix spacing issue -->
    <style>
        /* Force top padding for spacing */
        .product-card-1 {
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: #fff;
}

.product-card-1:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-card-1 img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card-1:hover img {
    transform: scale(1.05);
}

.product-details-1 {
    padding: 15px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-details-1 h5 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    height: 40px; /* Fixed height for title (approx. 2 lines) */
    overflow: hidden;
    display: -webkit-box;
    /*-webkit-line-clamp: 2;*/
    -webkit-box-orient: vertical;
}

.product-details-1 .color-name {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
    height: 20px; /* Fixed height */
}

.product-details-1 .price {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
    margin-top: auto; /* Push to bottom of flex space */
}

.buy-btn-1 {
    width: 100%;
    padding: 10px 15px;
    background-color: #1B365D;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.buy-btn-1:hover {
    background-color: #152b4a;
}

/* Fix product links */
.product-link {
    text-decoration: none;
    color: inherit;
    display: block;
    height: 100%;
}

.product-link:hover {
    color: inherit;
    text-decoration: none;
}

/* Fix the row container to use flexbox for equal heights */
.product-group .row {
    display: flex;
    flex-wrap: wrap;
}

.product-group .col-lg-3,
.product-group .col-md-4,
.product-group .col-sm-6 {
    display: flex;
    margin-bottom: 20px;
}

/* Add cart badge */
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
                        <a class="nav-link active" href="shop.php">Linen Fabric</a>
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
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
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

    <!-- Add spacer div -->
    <div class="navbar-spacer"></div>

    <!-- Category Menu -->
    <div class="category-menu">
        <ul>
            <li <?php echo ($selected_type == 'pure linen') ? 'class="active"' : ''; ?> data-category="pure-linen">
                <a href="shop.php?type=pure linen">Pure Linen</a>
            </li>
            <li <?php echo ($selected_type == 'cotton linen') ? 'class="active"' : ''; ?> data-category="cotton-linen">
                <a href="shop.php?type=cotton linen">Cotton Linen</a>
            </li>
            <li <?php echo ($selected_type == 'blend linen') ? 'class="active"' : ''; ?> data-category="blend-linen">
                <a href="shop.php?type=blend linen">Blend Linen</a>
            </li>
            <li <?php echo ($selected_type == 'printed linen') ? 'class="active"' : ''; ?> data-category="linen-printed">
                <a href="shop.php?type=printed linen">Linen Printed</a>
            </li>
            <li <?php echo ($selected_type == 'dyed linen') ? 'class="active"' : ''; ?> data-category="dyed-linen">
                <a href="shop.php?type=dyed linen">Dyed Linen</a>
            </li>
        </ul>
    </div>

    <!-- Products Section -->
    <section id="products-1" class="section-padding1">
        <div class="container">
            <div class="product-group" data-category="pure-linen">
                <?php if (empty($products)): ?>
                    <div class="alert alert-info">
                        No products found in this category. Please check back later.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <a href="sproduct.php?id=<?php echo $product['id']; ?>" class="product-link">
                                    <div class="product-card-1">
                                        <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'img/placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="img-fluid">
                                        <div class="product-details-1">
                                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="color-name">(<?php echo htmlspecialchars($product['color']); ?>)</p>
                                            <p class="price">LKR <?php echo number_format($product['price'], 2); ?></p>
                                            <button class="buy-btn-1" data-product-id="<?php echo $product['id']; ?>" onclick="event.preventDefault(); event.stopPropagation(); addToCart('<?php echo $product['id']; ?>', 1, 'meter');">ADD TO CART</button>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
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
                            <li><a href="#home">HOME</a></li>
                            <li><a href="#products">SAMPLE FABRIC</a></li>
                            <li><a href="#swatch">SWATCH BOOK</a></li>
                            <li><a href="#orders">ORDERS</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Extra Links</h4>
                        <ul>
                            <li><a href="#login">LOGIN</a></li>
                            <li><a href="#signup">SIGNUP</a></li>
                            <li><a href="#cart">CART</a></li>
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
    function addToCart(productId, quantity, size) {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${encodeURIComponent(productId)}&quantity=${quantity}&size=${size}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart badge count
                updateCartBadge(data.cart_count);
                
                // Show success message
                alert('Product added to cart successfully!');
            } else {
                alert(data.message || 'Failed to add product to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding to cart');
        });
    }
    
    function updateCartBadge(count) {
        // Find the cart icon's parent element
        const cartLink = document.querySelector('a[href="cart.php"]');
        if (!cartLink) return;
        
        // Remove existing badge if present
        const existingBadge = cartLink.querySelector('.cart-badge');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // Add badge if count is greater than 0
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.textContent = count;
            cartLink.appendChild(badge);
        }
    }

    // Search toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchToggle = document.getElementById('searchToggle');
        
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'search.php';
        });
    });
    
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>