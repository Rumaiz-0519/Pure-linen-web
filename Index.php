<?php
session_start();
$pageTitle = 'Pure Linen - Home';
$currentPage = 'home';
require_once 'config.php';

// Get featured products from database
$featured_products = [];
try {
    // Check if featured_products table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'featured_products'");
    if ($check_table->num_rows > 0) {
        // Get featured products with product details
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.color, p.price, p.image_url, p.type
            FROM featured_products fp
            JOIN products p ON fp.product_id = p.id
            ORDER BY fp.position ASC
            LIMIT 4
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $featured_products[] = $row;
        }
    }
} catch (Exception $e) {
    // Log the error but continue with the page
    error_log("Error loading featured products: " . $e->getMessage());
}

// If no featured products, get default ones
if (empty($featured_products)) {
    try {
        $stmt = $conn->prepare("SELECT id, name, color, price, image_url, type FROM products ORDER BY id LIMIT 4");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $featured_products[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error loading default products: " . $e->getMessage());
    }
}

// Get cart count if user is logged in
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE session_id = ?");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cart_count = $row['count'];
        }
        
        // Store cart count in session for other pages
        $_SESSION['cart_count'] = $cart_count;
    } catch (Exception $e) {
        error_log("Error getting cart count: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css?v=<?php echo time(); ?>">
    <!-- Fallback inline styles in case the CSS file doesn't load -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');
        
        body {
            padding-top: 100px;
            color: #333333;
            font-family: "Poppins", sans-serif;
        }
        
        .hero {
            background-image: url("img/back.webp");
            height: 80vh;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .hero-content {
            max-width: 600px;
            position: relative;
            z-index: 1;
            padding-left: 40px;
        }
        
        .hero h1 {
            font-size: 38px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .hero h1 span {
            color: #1B365D;
        }
        
        .hero p {
            font-size: 16px;
            margin-bottom: 25px;
            color: #555;
        }
        
        .primary-btn {
            background-color: #1B365D;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            width: auto;
            min-width: 180px;
            display: inline-block;
            text-align: center;
        }
        
        .primary-btn:hover {
            background-color: #15296b;
        }
        
        .banner {
            background-image: url("img/banner2.avif");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 100px 0;
            position: relative;
            color: white;
        }
        
        .banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .banner-content {
            position: relative;
            z-index: 1;
            padding-left: 40px;
        }
        
        .banner h4 {
            color: white;
            margin-bottom: 10px;
            font-size: 18px;
            text-transform: uppercase;
        }
        
        .banner h1 {
            color: white !important;
            font-size: 36px;
            margin-bottom: 25px;
            line-height: 1.2;
        }
        
        .section-padding {
            padding: 40px 0;
        }
        
        .product-card {
            background-color: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .product-details {
            text-align: center;
        }
        
        .star {
            color: gold;
            margin-bottom: 10px;
        }
        
        .price {
            font-weight: 600;
            color: #1B365D;
            margin: 10px 0;
        }
        
        .buy-btn {
            background-color: #1B365D;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .fe-box {
            padding: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            margin: 10px;
            transition: all 0.4s ease;
            background-color: white;
            height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .fe-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .fe-box img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        .footer {
            background-color: #0c1d36;
            color: #d8d8d8;
            padding: 60px 0 0;
            margin-top: 60px;
        }
        
        .footer-section h3,
        .footer-section h4 {
            color: white;
            margin-bottom: 20px;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #d8d8d8;
            text-decoration: none;
        }
        
        .footer-bottom {
            padding: 20px 0;
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
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
                        <a class="nav-link active" href="Index.php">Home</a>
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
                    <a class="nav-link" href="search.php" id="searchToggle" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-cart-plus"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><p class="dropdown-item mb-0">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profile.php">Update Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1><span>Pure Luxury</span> in Every Fiber</h1>
                <p>Experience the timeless comfort and elegance <br>of natural linen fabrics</p>
                <button class="primary-btn" onclick="window.location.href='shop.php'">SHOP NOW</button>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-md-2 col-sm-6">
                    <div class="fe-box">
                        <img src="img/features/f7.png" alt="Free Shipping">
                        <h6>Express Delivery</h6>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="fe-box">
                        <img src="img/features/f2.png" alt="Online Order">
                        <h6>Online Order</h6>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="fe-box">
                        <img src="img/features/f3.png" alt="Save Money">
                        <h6>Save Money</h6>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="fe-box">
                        <img src="img/features/f4.png" alt="Promotions">
                        <h6>Promotions</h6>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="fe-box">
                        <img src="img/features/f5.png" alt="Happy Sell">
                        <h6>Happy Sell</h6>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="fe-box">
                        <img src="img/features/f6.png" alt="24/7 Support">
                        <h6>24/7 Support</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="products" class="section-padding">
        <div class="container">
            <div class="section-title text-center">
                <h3>Our Featured Products</h3>
                <hr class="mx-auto">
            </div>
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card">
                        <a href="sproduct.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                            <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'img/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid">
                        </a>
                        <div class="product-details">
                            <div class="star">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="price">LKR <?php echo number_format($product['price'], 2); ?></p>
                            <button class="buy-btn" onclick="window.location.href='sproduct.php?id=<?php echo htmlspecialchars($product['id']); ?>'">Buy Now</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Banner Section -->
    <section class="banner section-padding">
        <div class="container">
            <div class="banner-content">
                <h4>PREMIUM COLLECTION</h4>
                <h1 style="color: white !important;">Bulk Orders<br>UP TO 50% DISCOUNT</h1>
                <button class="primary-btn" onclick="window.location.href='bulk_inquiry.php'">ORDER NOW</button>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section-padding">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="about-image">
                        <img src="img/banner1.webp" alt="About Us" class="img-fluid">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="about-content">
                        <h2>About Us</h2>
                        <p>TOP TIZ International (PVT) Ltd, a leading Sri Lankan textile trading company, specializes in
                            premium linen and fabric solutions. It connects global manufacturers with quality-focused
                            clients through import and export operations.</p>
                        <div class="contact-info">
                            <div class="info-box">
                                <i class="fas fa-map-marker-alt"></i>
                                <h4>Visit Us</h4>
                                <p>1/2 crow island<br>sea breeze garden<br>colombo</p>
                            </div>
                            <div class="info-box">
                                <i class="fas fa-phone"></i>
                                <h4>Contact</h4>
                                <p>+94 777 123456<br>+94 777 123456<br>+94 777 123456</p>
                            </div>
                        </div>
                    </div>
                </div>
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
                            <li><a href="Index.php">HOME</a></li>
                            <li><a href="shop.php">SAMPLE FABRIC</a></li>
                            <li><a href="swatch.php">SWATCH BOOK</a></li>
                            <li><a href="orders.php">ORDERS</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Extra Links</h4>
                        <ul>
                            <li><a href="login.php">LOGIN</a></li>
                            <li><a href="login.php">SIGNUP</a></li>
                            <li><a href="bulk_inquiry.php">BULK ORDERS</a></li>
                            <li><a href="fabric_calculator.php">FABRIC CALCULATOR</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Contact Us</h4>
                        <p><i class="fas fa-phone"></i> +94 777 123456</p>
                        <p><i class="fas fa-phone"></i> +94 777 123456</p>
                        <p><i class="fas fa-envelope"></i> toprizinternational@gmail.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> 1/2 Crow Island, Colombo</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> Pure Linen. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchToggle = document.getElementById('searchToggle');
            
            if (searchToggle) {
                searchToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'search.php';
                });
            }
        });
    </script>
</body>
</html>