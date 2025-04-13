<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_name = $_SESSION['firstName'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Check for messages in session and then remove them
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Load featured products
$featured_products = [];
try {
    // Check if featured_products table exists, if not create it
    $check_table = $conn->query("SHOW TABLES LIKE 'featured_products'");
    if ($check_table->num_rows == 0) {
        // Create featured_products table
        $conn->query("CREATE TABLE featured_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id VARCHAR(50) NOT NULL,
            position INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
    }

    // Get featured products with product details
    $stmt = $conn->prepare("
        SELECT fp.id, fp.product_id, fp.position, p.name, p.color, p.price, p.image_url, p.type
        FROM featured_products fp
        JOIN products p ON fp.product_id = p.id
        ORDER BY fp.position ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
} catch (Exception $e) {
    $error_message = "Error loading featured products: " . $e->getMessage();
}

// Handle add featured product
if (isset($_POST['add_featured'])) {
    $product_id = trim($_POST['product_id']);
    $position = intval($_POST['position']);
    
    if (empty($product_id)) {
        $error_message = "Please select a product.";
    } else {
        try {
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM featured_products WHERE product_id = ?");
            $check_stmt->bind_param("s", $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $count = $check_result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "This product is already featured.";
            } else {
                $stmt = $conn->prepare("INSERT INTO featured_products (product_id, position) VALUES (?, ?)");
                $stmt->bind_param("si", $product_id, $position);
                
                if ($stmt->execute()) {
                    $success_message = "Product added to featured products successfully.";
                    
                    header("Location: featured_products.php");
                    exit();
                } else {
                    $error_message = "Error adding product to featured: " . $stmt->error;
                }
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $featured_id = intval($_GET['remove']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM featured_products WHERE id = ?");
        $stmt->bind_param("i", $featured_id);
        
        if ($stmt->execute()) {
            $success_message = "Product removed from featured successfully.";
            
            header("Location: featured_products.php");
            exit();
        } else {
            $error_message = "Error removing product from featured: " . $stmt->error;
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

if (isset($_POST['update_positions'])) {
    try {
        $conn->begin_transaction();
        
        foreach ($_POST['positions'] as $featured_id => $position) {
            $featured_id = intval($featured_id);
            $position = intval($position);
            
            $stmt = $conn->prepare("UPDATE featured_products SET position = ? WHERE id = ?");
            $stmt->bind_param("ii", $position, $featured_id);
            $stmt->execute();
        }
        
        $conn->commit();
        $success_message = "Positions updated successfully.";
        
        header("Location: featured_products.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating positions: " . $e->getMessage();
    }
}

$available_products = [];
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.color, p.type
        FROM products p
        WHERE p.id NOT IN (SELECT product_id FROM featured_products)
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $available_products[] = $row;
    }
} catch (Exception $e) {
    $error_message = "Error loading available products: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Products Management - Admin Panel</title>
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
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--dark-color);
            border-color: var(--dark-color);
        }
        
        .featured-product {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
        }
        
        .featured-product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .product-info {
            flex-grow: 1;
        }
        
        .product-actions {
            display: flex;
            gap: 5px;
        }
        
        .product-actions a {
            padding: 5px 10px;
        }
        
        .position-input {
            width: 60px;
            padding: 5px;
            text-align: center;
        }
        
        .featured-badge {
            background-color: var(--primary-color);
            color: white;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 3px;
            margin-left: 10px;
        }
        
        .drag-handle {
            cursor: move;
            color: #aaa;
            transition: color 0.3s;
        }
        
        .drag-handle:hover {
            color: var(--primary-color);
        }
        
        .no-featured {
            padding: 30px;
            text-align: center;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .no-featured i {
            font-size: 40px;
            color: #ddd;
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
                        <a href="featured_products.php" class="active"><i class="fas fa-star"></i> Featured Products</a>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Featured Products Management</h1>
                    <a href="../index.php" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-2"></i>View Homepage
                    </a>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2"></i>Add Featured Product</h5>
                            </div>
                            <div class="card-body">
                                <form action="featured_products.php" method="POST">
                                    <div class="mb-3">
                                        <label for="product_id" class="form-label">Select Product</label>
                                        <select class="form-select" id="product_id" name="product_id" required>
                                            <option value="">-- Select a product --</option>
                                            <?php foreach ($available_products as $product): ?>
                                                <option value="<?php echo htmlspecialchars($product['id']); ?>">
                                                    <?php echo htmlspecialchars($product['name'] . ' (' . $product['color'] . ') - ' . $product['type']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="position" class="form-label">Position</label>
                                        <input type="number" class="form-control" id="position" name="position" min="1" value="<?php echo count($featured_products) + 1; ?>" required>
                                        <div class="form-text">Position determines the order of display on the homepage.</div>
                                    </div>
                                    
                                    <button type="submit" name="add_featured" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Add to Featured
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Information</h5>
                            </div>
                            <div class="card-body">
                                <p>The featured products are displayed on the homepage in the "Featured Products" section.</p>
                                <p>You can:</p>
                                <ul>
                                    <li>Add new products to the featured section</li>
                                    <li>Change the order of display by updating positions</li>
                                    <li>Remove products from the featured section</li>
                                </ul>
                                <p>Note: Changes will be immediately visible on the homepage.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-star me-2"></i>Current Featured Products</h5>
                                    <?php if (!empty($featured_products)): ?>
                                        <button type="button" class="btn btn-sm btn-light" id="togglePositionEdit">
                                            <i class="fas fa-edit me-1"></i>Edit Positions
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($featured_products)): ?>
                                    <div class="no-featured">
                                        <i class="fas fa-star"></i>
                                        <h5>No Featured Products</h5>
                                        <p>Add products to feature them on the homepage.</p>
                                    </div>
                                <?php else: ?>
                                    <form action="featured_products.php" method="POST" id="positionForm" class="d-none">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Edit Positions</h6>
                                                <div>
                                                    <button type="submit" name="update_positions" class="btn btn-success btn-sm">
                                                        <i class="fas fa-save me-1"></i>Save Positions
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm ms-2" id="cancelPositionEdit">
                                                        <i class="fas fa-times me-1"></i>Cancel
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="position-list">
                                                <?php foreach ($featured_products as $product): ?>
                                                    <div class="featured-product">
                                                        <img src="<?php echo '../' . htmlspecialchars($product['image_url'] ?? 'img/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                        <div class="product-info">
                                                            <h6><?php echo htmlspecialchars($product['name']); ?> <span class="text-muted">(<?php echo htmlspecialchars($product['color']); ?>)</span></h6>
                                                            <p class="mb-0">
                                                                <small>Type: <?php echo htmlspecialchars($product['type']); ?></small><br>
                                                                <small>Price: LKR <?php echo number_format($product['price'], 2); ?></small>
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <input type="number" name="positions[<?php echo $product['id']; ?>]" value="<?php echo $product['position']; ?>" class="position-input" min="1">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div id="featuredList">
                                        <?php foreach ($featured_products as $product): ?>
                                            <div class="featured-product">
                                                <span class="drag-handle me-2"><i class="fas fa-grip-vertical"></i></span>
                                                <img src="<?php echo '../' . htmlspecialchars($product['image_url'] ?? 'img/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                <div class="product-info">
                                                    <h6><?php echo htmlspecialchars($product['name']); ?> <span class="text-muted">(<?php echo htmlspecialchars($product['color']); ?>)</span></h6>
                                                    <p class="mb-0">
                                                        <small>Type: <?php echo htmlspecialchars($product['type']); ?></small><br>
                                                        <small>Price: LKR <?php echo number_format($product['price'], 2); ?></small>
                                                    </p>
                                                </div>
                                                <div class="featured-badge">
                                                    Position: <?php echo $product['position']; ?>
                                                </div>
                                                <div class="product-actions ms-3">
                                                    <a href="edit_product.php?id=<?php echo urlencode($product['product_id']); ?>" class="btn btn-sm btn-primary" title="Edit Product">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="featured_products.php?remove=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this product from featured?')" title="Remove from Featured">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="../sproduct.php?id=<?php echo urlencode($product['product_id']); ?>" class="btn btn-sm btn-secondary" target="_blank" title="View on Site">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle position edit mode
            const togglePositionEdit = document.getElementById('togglePositionEdit');
            const positionForm = document.getElementById('positionForm');
            const featuredList = document.getElementById('featuredList');
            const cancelPositionEdit = document.getElementById('cancelPositionEdit');
            
            if (togglePositionEdit) {
                togglePositionEdit.addEventListener('click', function() {
                    positionForm.classList.remove('d-none');
                    featuredList.classList.add('d-none');
                });
            }
            
            if (cancelPositionEdit) {
                cancelPositionEdit.addEventListener('click', function() {
                    positionForm.classList.add('d-none');
                    featuredList.classList.remove('d-none');
                });
            }
        });
    </script>
</body>
</html>