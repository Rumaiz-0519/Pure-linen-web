<?php
require_once 'config.php';
session_start();

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to shop page if no product ID is provided
    header("Location: shop.php");
    exit();
}

$product_id = $_GET['id'];

// Get product data from database
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("s", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// If product not found, redirect to shop
if ($result->num_rows === 0) {
    header("Location: shop.php");
    exit();
}

$product = $result->fetch_assoc();

// Get cart count from session if it exists
$cart_count = isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        /* Cart badge styling */
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
        
        /* Image zoom styling */
        .product-image {
            position: relative;
            cursor: zoom-in;
        }
        
        #productImage {
            transition: transform 0.2s;
            display: block;
            width: 100%;
        }
        
        /* Zoom modal styling */
        .zoom-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.9);
        }
        
        .zoom-modal-content {
            margin: auto;
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }
        
        .zoom-close {
            position: absolute;
            top: 15px;
            right: 25px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            z-index: 1001;
        }
        
        .zoom-close:hover,
        .zoom-close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
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
                        <a class="nav-link" href="#search"><i class="fas fa-search"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
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
                            <a class="nav-link" href="login.php"><i class="fas fa-user"></i></a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Image zoom modal -->
    <div id="imageZoomModal" class="zoom-modal">
        <span class="zoom-close">&times;</span>
        <img class="zoom-modal-content" id="zoomedImage">
    </div>

    <section class="product-details-section">
        <div class="container">
            <div class="row">
                <!-- Product Image -->
                <div class="col-md-6">
                    <div class="product-image">
                        <img id="productImage" src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'img/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-md-6">
                    <div class="product-info">
                        <h2 id="productTitle"><?php echo htmlspecialchars($product['name']); ?></h2>
                        <p id="productColor">(<?php echo htmlspecialchars($product['color']); ?>)</p>
                        
                        <div class="size-selection">
                            <select class="form-select" id="sizeSelect">
                                <option value="meter">Linear Meter</option>
                                <option value="cm">50 Centimeter</option>
                            </select>
                        </div>

                        <div class="quantity-price mt-4">
                            <div class="d-flex align-items-center">
                                <div class="quantity me-4">
                                    <label>Qty</label>
                                    <input type="number" id="quantityInput" value="1" min="1" class="form-control">
                                </div>
                                <div class="price">
                                    <h4>LKR <span id="priceDisplay"><?php echo number_format($product['price'], 2); ?></span></h4>
                                </div>
                            </div>
                        </div>

                        <button id="addToCartBtn" class="btn btn-primary w-100 mt-4">Add To Cart</button>
                        
                        <p class="bulk-note mt-2">
                            Quantity above 500 meters, please click <a href="bulk_inquiry.php" class="text-primary">here</a>
                        </p>

                        <div class="product-details mt-5">
                            <h3>Product Details</h3>
                            <p id="productDescription"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>

                        <div class="specifications mt-4">
                            <h3>Specification</h3>
                            <table class="table">
                                <tr>
                                    <td width="150">Type</td>
                                    <td id="specType"><?php echo htmlspecialchars($product['type']); ?></td>
                                </tr>
                                <tr>
                                    <td>Properties</td>
                                    <td id="specProperties"><?php echo htmlspecialchars($product['properties']); ?></td>
                                </tr>
                                <tr>
                                    <td>Composition</td>
                                    <td id="specComposition"><?php echo htmlspecialchars($product['composition']); ?></td>
                                </tr>
                                <tr>
                                    <td>Color</td>
                                    <td id="specColor"><?php echo htmlspecialchars($product['color']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer section -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h3>TOP RIZ INTERNATIONAL</h3>
                        <p>Creating elegant solutions in textile trade. Premium fabrics. Global reach. Trusted quality.</p>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Image zoom functionality
        const productImage = document.getElementById('productImage');
        const zoomModal = document.getElementById('imageZoomModal');
        const zoomedImage = document.getElementById('zoomedImage');
        const closeBtn = document.querySelector('.zoom-close');
        
        // Open zoom modal when clicking on product image
        productImage.addEventListener('click', function() {
            zoomModal.style.display = 'block';
            zoomedImage.src = this.src;
        });
        
        // Close zoom modal when clicking on close button
        closeBtn.addEventListener('click', function() {
            zoomModal.style.display = 'none';
        });
        
        // Close zoom modal when clicking outside the image
        zoomModal.addEventListener('click', function(e) {
            if (e.target === this) {
                zoomModal.style.display = 'none';
            }
        });
        
        // Close zoom modal when pressing ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                zoomModal.style.display = 'none';
            }
        });
        
        // Cart and product functionality
        const sizeSelect = document.getElementById('sizeSelect');
        const quantityInput = document.getElementById('quantityInput');
        const priceDisplay = document.getElementById('priceDisplay');
        const addToCartBtn = document.getElementById('addToCartBtn');
        
        // Product data from PHP
        const product = {
            id: '<?php echo $product_id; ?>',
            price: <?php echo $product['price']; ?>,
            halfPrice: <?php echo $product['half_price']; ?>
        };
        
        function updatePrice() {
            // Update price based on size selection and quantity
            const basePrice = sizeSelect.value === 'cm' ? product.halfPrice : product.price;
            const quantity = parseInt(quantityInput.value) || 1;
            const totalPrice = basePrice * quantity;
            priceDisplay.textContent = totalPrice.toFixed(2);
        }
        
        // Update price when size or quantity changes
        sizeSelect.addEventListener('change', updatePrice);
        quantityInput.addEventListener('input', updatePrice);
        
        // Add to cart functionality
        addToCartBtn.addEventListener('click', function() {
            const quantity = parseInt(quantityInput.value) || 1;
            const size = sizeSelect.value;
            
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${encodeURIComponent(product.id)}&quantity=${quantity}&size=${size}`
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
        });
        
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
        
        // Initialize price display
        updatePrice();
    });

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