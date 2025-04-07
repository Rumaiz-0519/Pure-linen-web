<?php
session_start();
require_once '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No product ID provided";
    header("Location: product_management.php");
    exit();
}

$product_id = $_GET['id'];

try {
    // Begin a transaction
    $conn->begin_transaction();
    
    // Get product data (to get image path for deletion)
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Product doesn't exist, redirect back
        $conn->rollback();
        $_SESSION['error_message'] = "Product not found";
        header("Location: product_management.php");
        exit();
    }

    $product = $result->fetch_assoc();
    $image_url = $product['image_url'];
    
    // Check if product is in any cart
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE product_id = ?");
    $check_stmt->bind_param("s", $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count > 0) {
        // First, delete all cart items that reference this product
        $delete_cart_stmt = $conn->prepare("DELETE FROM cart_items WHERE product_id = ?");
        $delete_cart_stmt->bind_param("s", $product_id);
        
        if (!$delete_cart_stmt->execute()) {
            throw new Exception("Failed to remove cart items: " . $conn->error);
        }
        
        // Log this action for admin reference
        error_log("Admin deleted product ID $product_id with $count items in cart");
    }

    // Now delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("s", $product_id);

    if ($stmt->execute()) {
        // Delete the image file if it exists
        if (!empty($image_url)) {
            $image_path = "../" . $image_url;
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Set success message in session
        $_SESSION['success_message'] = "Product deleted successfully.";
    } else {
        throw new Exception("Error deleting product: " . $conn->error);
    }
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    $_SESSION['error_message'] = "Exception occurred: " . $e->getMessage();
}

// Redirect back to product management page
header("Location: product_management.php");
exit();
?>