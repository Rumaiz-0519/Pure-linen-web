<?php
session_start();
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$response = ['success' => false, 'message' => ''];

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $requiredFields = ['product_id', 'field', 'value'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $response['message'] = "Missing required field: $field";
            echo json_encode($response);
            exit();
        }
    }

    $product_id = $_POST['product_id'];
    $field = $_POST['field'];
    $value = $_POST['value'];

    // Whitelist allowed fields to prevent SQL injection
    $allowedFields = ['name', 'color', 'price', 'half_price', 'description', 'type', 'properties', 'composition'];
    
    if (!in_array($field, $allowedFields)) {
        $response['message'] = "Invalid field: $field";
        echo json_encode($response);
        exit();
    }

    try {
        // Update product field
        $stmt = $conn->prepare("UPDATE products SET $field = ? WHERE id = ?");
        $stmt->bind_param("ss", $value, $product_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Product $field updated successfully";
        } else {
            $response['message'] = "Error updating product: " . $stmt->error;
        }
    } catch (Exception $e) {
        $response['message'] = "Exception occurred: " . $e->getMessage();
    }
}

echo json_encode($response);
exit();
?>