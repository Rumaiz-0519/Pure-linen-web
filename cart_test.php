<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include configuration
require_once 'config.php';

// Simple HTML structure
echo '<!DOCTYPE html>
<html>
<head>
    <title>Cart Test</title>
</head>
<body>
    <h1>Cart Test Page</h1>';

// Test database connection
try {
    $session_id = session_id();
    echo "<p>Session ID: $session_id</p>";
    
    // Simple query to test connection
    $test_query = "SELECT COUNT(*) as count FROM cart_items";
    $result = $conn->query($test_query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Total cart items in database: " . $row['count'] . "</p>";
    } else {
        echo "<p>Error executing query: " . $conn->error . "</p>";
    }
    
    // Try to get cart items
    echo "<p>Attempting to get cart items...</p>";
    
    $sql = "SELECT ci.*, p.name, p.color, p.price, p.image 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.session_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<p>Error preparing statement: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p>Found " . $result->num_rows . " items in cart</p>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<p>Item: " . htmlspecialchars($row['name']) . " - Quantity: " . $row['quantity'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo '</body></html>';
?>