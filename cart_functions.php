<?php
require_once 'config.php';

function getCartItems() {
    global $conn;
    $sessionId = session_id();
    
    // Modified SQL query to match your database structure
    $sql = "SELECT ci.*, p.name, p.color, p.price, p.image as image_url 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.session_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function addToCart($productId, $quantity = 1, $size = 'meter') {
    global $conn;
    $sessionId = session_id();
    
    // Check if item already exists in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items 
                           WHERE product_id = ? AND session_id = ? AND size = ?");
    $stmt->bind_param("iss", $productId, $sessionId, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Update quantity if item exists
        $newQuantity = $row['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $newQuantity, $row['id']);
    } else {
        // Insert new item if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO cart_items (product_id, quantity, size, session_id) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $productId, $quantity, $size, $sessionId);
    }
    
    return $stmt->execute();
}

function removeFromCart($cartItemId) {
    global $conn;
    $sessionId = session_id();
    
    $stmt = $conn->prepare("DELETE FROM cart_items 
                           WHERE id = ? AND session_id = ?");
    $stmt->bind_param("is", $cartItemId, $sessionId);
    
    return $stmt->execute();
}

function updateCartQuantity($cartItemId, $quantity) {
    global $conn;
    $sessionId = session_id();
    
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? 
                           WHERE id = ? AND session_id = ?");
    $stmt->bind_param("iis", $quantity, $cartItemId, $sessionId);
    
    return $stmt->execute();
}

function getCartTotal() {
    global $conn;
    $sessionId = session_id();
    
    $sql = "SELECT SUM(
                CASE 
                    WHEN ci.size = 'cm' THEN p.price * 0.5 * ci.quantity
                    ELSE p.price * ci.quantity
                END
            ) as total 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.session_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ?? 0;
}

function getCartCount() {
    global $conn;
    $sessionId = session_id();
    
    $sql = "SELECT SUM(quantity) as count FROM cart_items WHERE session_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] ?? 0;
}
?>