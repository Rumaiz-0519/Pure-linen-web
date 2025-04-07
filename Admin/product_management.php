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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and validate form inputs
    $product_id = strtolower(trim(str_replace(' ', '-', $_POST['id'])));
    $name = trim($_POST['name']);
    $color = trim($_POST['color']);
    $price = floatval($_POST['price']);
    $half_price = floatval($_POST['half_price']);
    $description = trim($_POST['description']);
    $type = trim($_POST['type']);
    $properties = trim($_POST['properties']);
    $composition = trim($_POST['composition']);
    
    // Validate required fields
    if (empty($product_id) || empty($name) || empty($color) || $price <= 0 || $half_price <= 0 || 
        empty($description) || empty($type) || empty($properties) || empty($composition)) {
        $error_message = "All fields are required and prices must be greater than zero.";
    } else {
        // Handle image upload - using type-based directories
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Create directory if it doesn't exist
            $type_dir = "../img/" . strtolower(str_replace(' ', '-', $type)) . "/";
            if (!is_dir($type_dir)) {
                if (!mkdir($type_dir, 0755, true)) {
                    $error_message = "Failed to create directory: " . $type_dir;
                }
            }
            
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            
            if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "webp") {
                $error_message = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
            } else {
                // Check if file is an actual image
                $check = getimagesize($_FILES["image"]["tmp_name"]);
                if ($check === false) {
                    $error_message = "File is not an image.";
                } else {
                    // Check file size (limit to 5MB)
                    if ($_FILES["image"]["size"] > 5000000) {
                        $error_message = "File is too large. Max size is 5MB.";
                    } else {
                        $file_name = $product_id . '.' . $file_extension;
                        $target_file = $type_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                            // Store the relative path for the database
                            $image_url = "img/" . strtolower(str_replace(' ', '-', $type)) . "/" . $file_name;
                        } else {
                            $error_message = "Failed to upload image. Please check directory permissions.";
                        }
                    }
                }
            }
        } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Only show error if a file was attempted to be uploaded
            $error_message = "Image upload error: " . $_FILES['image']['error'];
        } else {
            $error_message = "Please select an image to upload.";
        }
        
        if (empty($error_message)) {
            try {
                // Check if product ID already exists
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
                $check_stmt->bind_param("s", $product_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_row()[0];
                
                if ($count > 0) {
                    $error_message = "Product ID already exists. Please use a different name.";
                } else {
                    // Insert product - FIX: Changed parameter types to correct the color issue
                    $stmt = $conn->prepare("INSERT INTO products (id, name, color, price, half_price, image_url, description, type, properties, composition) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssddsssss", $product_id, $name, $color, $price, $half_price, $image_url, $description, $type, $properties, $composition);
                    
                    if ($stmt->execute()) {
                        $success_message = "Product added successfully!";
                        
                        // Verify the data was inserted correctly
                        $verify_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                        $verify_stmt->bind_param("s", $product_id);
                        $verify_stmt->execute();
                        $verify_result = $verify_stmt->get_result();
                        $inserted_product = $verify_result->fetch_assoc();
                        
                        // Log the inserted data for debugging
                        error_log("Inserted product data: " . print_r($inserted_product, true));
                    } else {
                        $error_message = "Error adding product: " . $stmt->error;
                    }
                }
            } catch (Exception $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Retrieve all products for display
$products = [];
$stmt = $conn->prepare("SELECT * FROM products ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Panel</title>
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
        
        .table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-actions {
            display: flex;
            gap: 5px;
        }
        
        .page-title-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        /* Quick Edit styles */
        .editable {
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 3px;
            transition: all 0.2s;
            position: relative;
        }
        
        .editable:hover {
            background-color: #f1f1f1;
        }
        
        .editable:hover::after {
            content: '\f044';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 10px;
            position: absolute;
            top: 0;
            right: 0;
            color: var(--primary-color);
        }
        
        .edit-input {
            padding: 2px 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            width: 100%;
        }
        
        .edit-actions {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        
        .edit-actions button {
            padding: 2px 8px;
            font-size: 12px;
        }
        
        .tooltip-inner {
            max-width: 200px;
            background-color: var(--primary-color);
        }
        
        .toast-container {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 9999;
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
                        <a href="product_management.php" class="active"><i class="fas fa-box"></i> Products</a>
                    </li>
                    <li>
                        <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                    </li>
                    <li>
                        <a href="featured_products.php" class=""><i class="fas fa-star"></i> Featured Products</a>
                    </li>
                    <li>
                        <a href="admin_subscriptions.php" class=""><i class="fas fa-book"></i> Subscriptions</a>
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
                <div class="page-title-box">
                    <h1 class="h3 mb-0">Product Management</h1>
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                
                <div class="toast-container"></div>
                
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
                
                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
                            </div>
                            <div class="card-body">
                                <form action="product_management.php" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="id" class="form-label">Product ID</label>
                                        <input type="text" class="form-control" id="id" name="id" required placeholder="unique-product-id">
                                        <div class="form-text">A unique identifier for the product. Use lowercase with hyphens.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="color" class="form-label">Color</label>
                                        <input type="text" class="form-control" id="color" name="color" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="price" class="form-label">Price (LKR)</label>
                                                <input type="number" class="form-control" id="price" name="price" required step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="half_price" class="form-label">Half Meter Price (LKR)</label>
                                                <input type="number" class="form-control" id="half_price" name="half_price" required step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Product Type</label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="pure linen">Pure Linen</option>
                                            <option value="cotton linen">Cotton Linen</option>
                                            <option value="blend linen">Blend Linen</option>
                                            <option value="printed linen">Printed Linen</option>
                                            <option value="dyed linen">Dyed Linen</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="properties" class="form-label">Properties</label>
                                        <input type="text" class="form-control" id="properties" name="properties" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="composition" class="form-label">Composition</label>
                                        <input type="text" class="form-control" id="composition" name="composition" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Product Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp" required>
                                        <div class="form-text">Upload an image for the product (JPG, PNG, or WEBP format).</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Add Product
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Product List</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($products)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>No products found. Add your first product using the form.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Name</th>
                                                    <th>Color</th>
                                                    <th>Price</th>
                                                    <th>Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($products as $product): ?>
                                                    <tr data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                                                        <td>
                                                            <?php if (!empty($product['image_url'])): ?>
                                                                <img src="<?php echo '../' . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail">
                                                            <?php else: ?>
                                                                <div class="bg-light text-center" style="width: 60px; height: 60px; line-height: 60px; border-radius: 5px;">
                                                                    <i class="fas fa-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="editable" 
                                                                  data-field="name" 
                                                                  data-original="<?php echo htmlspecialchars($product['name']); ?>"
                                                                  data-bs-toggle="tooltip" 
                                                                  title="Click to edit">
                                                                <?php echo htmlspecialchars($product['name']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="editable" 
                                                                  data-field="color" 
                                                                  data-original="<?php echo htmlspecialchars($product['color']); ?>"
                                                                  data-bs-toggle="tooltip" 
                                                                  title="Click to edit">
                                                                <?php echo htmlspecialchars($product['color']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="editable" 
                                                                  data-field="price" 
                                                                  data-original="<?php echo $product['price']; ?>"
                                                                  data-bs-toggle="tooltip" 
                                                                  title="Click to edit">
                                                                LKR <?php echo number_format($product['price'], 2); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($product['type']); ?></span>
                                                        </td>
                                                        <td class="product-actions">
                                                            <a href="edit_product.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-sm btn-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="delete_product.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                            <a href="../sproduct.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-sm btn-secondary" target="_blank" title="View on Site">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="quick_view_product.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-sm btn-info" title="Quick View">
                                                                <i class="fas fa-search-plus"></i>
                                                            </a>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Quick edit functionality
            document.querySelectorAll('.editable').forEach(function(element) {
                element.addEventListener('click', function() {
                    // Hide the tooltip if it exists
                    if (bootstrap.Tooltip.getInstance(this)) {
                        bootstrap.Tooltip.getInstance(this).hide();
                    }
                    
                    const field = this.dataset.field;
                    const originalValue = this.dataset.original;
                    const productId = this.closest('tr').dataset.productId;
                    
                    // Create edit interface
                    const editContainer = document.createElement('div');
                    editContainer.className = 'edit-container';
                    
                    // Create appropriate input based on field type
                    let input;
                    if (field === 'price' || field === 'half_price') {
                        input = document.createElement('input');
                        input.type = 'number';
                        input.step = '0.01';
                        input.value = originalValue;
                    } else {
                        input = document.createElement('input');
                        input.type = 'text';
                        input.value = originalValue;
                    }
                    
                    input.className = 'edit-input';
                    editContainer.appendChild(input);
                    
                    // Create action buttons
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'edit-actions';
                    
                    const saveBtn = document.createElement('button');
                    saveBtn.className = 'btn btn-sm btn-success';
                    saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
                    
                    const cancelBtn = document.createElement('button');
                    cancelBtn.className = 'btn btn-sm btn-secondary';
                    cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
                    
                    actionsDiv.appendChild(saveBtn);
                    actionsDiv.appendChild(cancelBtn);
                    editContainer.appendChild(actionsDiv);
                    
                    // Replace the original element with edit interface
                    const originalElement = this;
                    originalElement.parentNode.replaceChild(editContainer, originalElement);
                    
                    // Focus on the input
                    input.focus();
                    
                    // Cancel button event
                    cancelBtn.addEventListener('click', function() {
                        editContainer.parentNode.replaceChild(originalElement, editContainer);
                    });
                    
                    // Save button event
                    saveBtn.addEventListener('click', function() {
                        const newValue = input.value.trim();
                        
                        // Validate input
                        if (!newValue) {
                            showToast('Please enter a value', 'error');
                            return;
                        }
                        
                        // Show loading state
                        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                        saveBtn.disabled = true;
                        
                        // Send data to server
                        fetch('quick_edit_product.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `product_id=${encodeURIComponent(productId)}&field=${encodeURIComponent(field)}&value=${encodeURIComponent(newValue)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update the original element with new value
                                originalElement.dataset.original = newValue;
                                
                                // Format display value based on field type
                                let displayValue = newValue;
                                if (field === 'price') {
                                    displayValue = 'LKR ' + parseFloat(newValue).toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                                
                                originalElement.textContent = displayValue;
                                
                                // Replace the edit interface with original element
                                editContainer.parentNode.replaceChild(originalElement, editContainer);
                                
                                // Show success message
                                showToast(data.message, 'success');
                            } else {
                                showToast(data.message, 'error');
                                saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
                                saveBtn.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('An error occurred while processing your request', 'error');
                            saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
                            saveBtn.disabled = false;
                        });
                    });
                    
                    // Handle Enter key press
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            saveBtn.click();
                        }
                    });
                    
                    // Handle Escape key press
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            cancelBtn.click();
                        }
                    });
                });
            });
            
            // Toast notification function
            function showToast(message, type = 'success') {
                const toastContainer = document.querySelector('.toast-container');
                
                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                
                const toastBody = document.createElement('div');
                toastBody.className = 'd-flex';
                
                const messageDiv = document.createElement('div');
                messageDiv.className = 'toast-body';
                messageDiv.textContent = message;
                
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close btn-close-white me-2 m-auto';
                closeButton.setAttribute('data-bs-dismiss', 'toast');
                closeButton.setAttribute('aria-label', 'Close');
                
                toastBody.appendChild(messageDiv);
                toastBody.appendChild(closeButton);
                toastEl.appendChild(toastBody);
                toastContainer.appendChild(toastEl);
                
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 3000,
                    autohide: true
                });
                
                toast.show();
                
                // Remove toast after it's hidden
                toastEl.addEventListener('hidden.bs.toast', function() {
                    toastContainer.removeChild(toastEl);
                });
            }
        });
    </script>
</body>
</html>