<?php
require_once 'config.php';
session_start();

// Set response content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get product details and session
    $product_id = $_POST['product_id'] ?? '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = $_POST['size'] ?? 'meter';
    $session_id = session_id();

    // Log received data for debugging
    error_log("Received product_id: " . $product_id);
    error_log("Received quantity: " . $quantity);
    error_log("Session ID: " . $session_id);

    // Validate inputs
    if (empty($product_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required'
        ]);
        exit;
    }

    if ($quantity < 1) {
        $quantity = 1;
    }

    // Check if product exists in database
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }

    // Product exists, check if it's already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE product_id = ? AND session_id = ? AND size = ?");
    $stmt->bind_param("sss", $product_id, $session_id, $size);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing cart item
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $row['id']);
    } else {
        // Add new cart item
        $stmt = $conn->prepare("INSERT INTO cart_items (product_id, quantity, size, session_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $product_id, $quantity, $size, $session_id);
    }

    if ($stmt->execute()) {
        // Get updated cart count
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE session_id = ?");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cart_count = $row['count'] ?? 0;

        // Update session cart count
        $_SESSION['cart_count'] = $cart_count;

        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update cart: ' . $stmt->error
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>