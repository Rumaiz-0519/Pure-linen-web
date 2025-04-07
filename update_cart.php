<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itemId = (int)($_POST['id'] ?? 0);
    $session_id = session_id();

    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit;
    }

    switch ($action) {
        case 'update':
            $quantity = (int)($_POST['quantity'] ?? 1);
            if ($quantity < 1) $quantity = 1;

            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND session_id = ?");
            $stmt->bind_param("iis", $quantity, $itemId, $session_id);
            $success = $stmt->execute();

            if ($success) {
                // Calculate new cart total
                $stmt = $conn->prepare("
                    SELECT SUM(
                        CASE 
                            WHEN ci.size = 'cm' THEN p.half_price * ci.quantity
                            ELSE p.price * ci.quantity
                        END
                    ) as total 
                    FROM cart_items ci 
                    JOIN products p ON ci.product_id = p.id 
                    WHERE ci.session_id = ?
                ");
                $stmt->bind_param("s", $session_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $cartTotal = $row['total'] ?? 0;
                
                // Get cart count
                $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE session_id = ?");
                $stmt->bind_param("s", $session_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $cartCount = $row['count'] ?? 0;
                
                // Update session cart count
                $_SESSION['cart_count'] = $cartCount;

                echo json_encode([
                    'success' => true,
                    'cart_total' => $cartTotal,
                    'cart_count' => $cartCount
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update quantity: ' . $stmt->error
                ]);
            }
            break;

        case 'remove':
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND session_id = ?");
            $stmt->bind_param("is", $itemId, $session_id);
            $success = $stmt->execute();

            if ($success) {
                // Get updated cart count
                $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE session_id = ?");
                $stmt->bind_param("s", $session_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $cartCount = $row['count'] ?? 0;
                
                // Update session cart count
                $_SESSION['cart_count'] = $cartCount;

                echo json_encode([
                    'success' => true,
                    'cart_count' => $cartCount
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to remove item: ' . $stmt->error
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>