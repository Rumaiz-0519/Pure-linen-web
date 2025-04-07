<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: product_management.php");
    exit();
}

$product_id = $_GET['id'];

// Get product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("s", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: product_management.php");
    exit();
}

$product = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $color = $_POST['color'];
    $price = $_POST['price'];
    $half_price = $_POST['half_price'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $properties = $_POST['properties'];
    $composition = $_POST['composition'];
    
    $image_url = $product['image_url']; // Keep existing image by default
    
    // Handle image upload if a new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Create directory if it doesn't exist
        $type_dir = "../img/" . strtolower(str_replace(' ', '-', $type)) . "/";
        if (!is_dir($type_dir)) {
            if (!mkdir($type_dir, 0755, true)) {
                $error_message = "Failed to create directory: " . $type_dir;
            }
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $file_name = $product_id . '.' . $file_extension;
        $target_file = $type_dir . $file_name;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $error_message = "File is not an image.";
        } else {
            // Check file size (limit to 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $error_message = "File is too large. Max size is 5MB.";
            } else {
                // Check file type
                if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "webp") {
                    $error_message = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
                } else {
                    // Delete old image if exists and different from new path
                    $old_image_path = "../" . $product['image_url']; 
                    if (!empty($product['image_url']) && file_exists($old_image_path) && $old_image_path !== $target_file) {
                        @unlink($old_image_path);
                    }
                    
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
        // Error with file upload, but not because no file was selected
        $error_message = "Image upload error: " . $_FILES['image']['error'];
    }
    
    if (empty($error_message)) {
        try {
            // Update product
            $stmt = $conn->prepare("UPDATE products SET name = ?, color = ?, price = ?, half_price = ?, image_url = ?, description = ?, type = ?, properties = ?, composition = ? WHERE id = ?");
            $stmt->bind_param("ssddssssss", $name, $color, $price, $half_price, $image_url, $description, $type, $properties, $composition, $product_id);
            
            if ($stmt->execute()) {
                $success_message = "Product updated successfully!";
                
                // Refresh product data
                $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->bind_param("s", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
            } else {
                $error_message = "Error updating product: " . $stmt->error;
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            padding-top: 70px;
            background-color: #f8f9fa;
        }
        .admin-header {
            background-color: #1B365D;
            color: white;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
    </style>
</head>
<body>
    

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Edit Product</h2>
                    <a href="product_management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Products
                    </a>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success mt-3"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger mt-3"><?php echo $error_message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Edit Product Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="edit_product.php?id=<?php echo urlencode($product_id); ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="id" class="form-label">Product ID</label>
                                <input type="text" class="form-control" id="id" value="<?php echo htmlspecialchars($product['id']); ?>" readonly disabled>
                                <div class="form-text">Product IDs cannot be changed after creation.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" name="color" value="<?php echo htmlspecialchars($product['color']); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (LKR)</label>
                                        <input type="number" class="form-control" id="price" name="price" value="<?php echo $product['price']; ?>" required step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="half_price" class="form-label">Half Meter Price (LKR)</label>
                                        <input type="number" class="form-control" id="half_price" name="half_price" value="<?php echo $product['half_price']; ?>" required step="0.01">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Product Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="pure linen" <?php echo ($product['type'] == 'pure linen') ? 'selected' : ''; ?>>Pure Linen</option>
                                    <option value="cotton linen" <?php echo ($product['type'] == 'cotton linen') ? 'selected' : ''; ?>>Cotton Linen</option>
                                    <option value="blend linen" <?php echo ($product['type'] == 'blend linen') ? 'selected' : ''; ?>>Blend Linen</option>
                                    <option value="printed linen" <?php echo ($product['type'] == 'printed linen') ? 'selected' : ''; ?>>Printed Linen</option>
                                    <option value="dyed linen" <?php echo ($product['type'] == 'dyed linen') ? 'selected' : ''; ?>>Dyed Linen</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="properties" class="form-label">Properties</label>
                                <input type="text" class="form-control" id="properties" name="properties" value="<?php echo htmlspecialchars($product['properties']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="composition" class="form-label">Composition</label>
                                <input type="text" class="form-control" id="composition" name="composition" value="<?php echo htmlspecialchars($product['composition']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Leave empty to keep current image.</div>
                                
                                <?php if (!empty($product['image_url'])): ?>
                                    <div class="mt-2">
                                        <p>Current image:</p>
                                        <img src="<?php echo '../' . htmlspecialchars($product['image_url']); ?>" alt="Current Product Image" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Product</button>
                                <a href="product_management.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Product Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="product-preview text-center">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo '../' . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid mb-3" style="max-height: 250px;">
                            <?php else: ?>
                                <div class="bg-light p-5 mb-3">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                    <p class="mt-2 text-muted">No image available</p>
                                </div>
                            <?php endif; ?>
                            
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($product['color']); ?></p>
                            <p class="fw-bold">LKR <?php echo number_format($product['price'], 2); ?></p>
                            <a href="../sproduct.php?id=<?php echo urlencode($product['id']); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>View on Site
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>