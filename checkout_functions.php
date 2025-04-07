<?php
// Function to safely get cart items
function getCartItems() {
    global $conn;
    $sessionId = session_id();
    $items = [];
    
    try {
        // Check if cart_items table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'cart_items'");
        if ($tableCheck->num_rows === 0) {
            return $items; // Return empty array if table doesn't exist
        }
        
        // Modified SQL query to match your database structure
        $sql = "SELECT ci.*, p.name, p.color, p.price, p.half_price, p.image_url as image_url 
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.session_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Calculate unit price based on size
            $row['unit_price'] = ($row['size'] === 'cm') ? $row['half_price'] : $row['price'];
            
            // Calculate subtotal
            $row['subtotal'] = $row['unit_price'] * $row['quantity'];
            
            $items[] = $row;
        }
    } catch (Exception $e) {
        // Log error but continue
        error_log("Error getting cart items: " . $e->getMessage());
    }
    
    return $items;
}

// Function to get cart total
function getCartTotal() {
    global $conn;
    $sessionId = session_id();
    $total = 0;
    
    try {
        // Check if cart_items table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'cart_items'");
        if ($tableCheck->num_rows === 0) {
            return $total; // Return 0 if table doesn't exist
        }
        
        $sql = "SELECT SUM(
                    CASE 
                        WHEN ci.size = 'cm' THEN p.half_price * ci.quantity
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
    } catch (Exception $e) {
        // Log error but continue
        error_log("Error calculating cart total: " . $e->getMessage());
        return 0;
    }
}

// Function to process an order
function processOrder($conn, $user_id, $order_total, $payment_method, $address, $city, $postcode, 
                      $country, $delivery_option, $delivery_cost, $cart_items) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get user ID if logged in, otherwise create/find user
        $user_id = $_SESSION['user_id'] ?? null;
        $firstName = $_SESSION['shipping_address']['firstName'];
        $lastName = $_SESSION['shipping_address']['lastName'];
        $email = $_SESSION['shipping_address']['email'];
        $phone = $_SESSION['shipping_address']['phone'];
        
        // If user is logged in, update their profile with the latest address info
        if ($user_id) {
            $update_user = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ?, address = ?, city = ?, postcode = ?, country = ? WHERE id = ?");
            $update_user->bind_param("ssssssssi", $firstName, $lastName, $email, $phone, $address, $city, $postcode, $country, $user_id);
            $update_user->execute();
        } else {
            // If user is not logged in, check if they exist with this email
            $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $user_stmt->bind_param("s", $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                // If user exists, use their ID and update their details
                $user_id = $user_result->fetch_assoc()['id'];
                $update_user = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, phone = ?, address = ?, city = ?, postcode = ?, country = ? WHERE id = ?");
                $update_user->bind_param("sssssssi", $firstName, $lastName, $phone, $address, $city, $postcode, $country, $user_id);
                $update_user->execute();
            } else {
                // Create a new user account
                $password = bin2hex(random_bytes(8)); // Generate a random password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Make sure all required tables exist
                $conn->query("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    firstName VARCHAR(100) NOT NULL,
                    lastName VARCHAR(100) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    phone VARCHAR(50),
                    address TEXT,
                    city VARCHAR(100),
                    postcode VARCHAR(20),
                    country VARCHAR(100),
                    user_type ENUM('user', 'admin') DEFAULT 'user',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                $insert_user = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, phone, address, city, postcode, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_user->bind_param("sssssssss", $firstName, $lastName, $email, $hashedPassword, $phone, $address, $city, $postcode, $country);
                $insert_user->execute();
                
                $user_id = $conn->insert_id;
            }
        }
        
        // Make sure orders table is up to date
        $conn->query("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            order_type VARCHAR(50) DEFAULT 'regular',
            subscription_id INT NULL,
            delivery_address TEXT NULL,
            delivery_city VARCHAR(100) NULL,
            delivery_postcode VARCHAR(20) NULL,
            delivery_country VARCHAR(100) NULL,
            delivery_option VARCHAR(50) DEFAULT 'standard',
            delivery_cost DECIMAL(10,2) DEFAULT 250.00
        )");
        
        // Make sure order_items table exists
        $conn->query("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            size VARCHAR(10) NOT NULL DEFAULT 'meter'
        )");
        
        // Create the order
        $insert_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, payment_status, delivery_address, delivery_city, delivery_postcode, delivery_country, delivery_option, delivery_cost) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)");
        $insert_order->bind_param("idssssssd", $user_id, $order_total, $payment_method, $address, $city, $postcode, $country, $delivery_option, $delivery_cost);
        $insert_order->execute();
        
        $order_id = $conn->insert_id;
        
        // Add order items
        $insert_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['unit_price'];
            $size = $item['size'];
            
            $insert_item->bind_param("isids", $order_id, $product_id, $quantity, $price, $size);
            $insert_item->execute();
        }
        
        // Clear the cart
        $clear_cart = $conn->prepare("DELETE FROM cart_items WHERE session_id = ?");
        $session_id = session_id();
        $clear_cart->bind_param("s", $session_id);
        $clear_cart->execute();
        
        // Reset session cart count
        $_SESSION['cart_count'] = 0;
        
        // Commit the transaction
        $conn->commit();
        
        return $order_id;
        
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        error_log("Order processing failed: " . $e->getMessage());
        return false;
    }
}

// Function to save shipping address to session (AJAX endpoint function)
function saveShippingAddress($data) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Validate required fields
    $required = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'postcode', 'country'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false,
                'message' => ucfirst($field) . ' is required'
            ];
        }
    }
    
    // Save to session
    $_SESSION['shipping_address'] = [
        'firstName' => $data['firstName'],
        'lastName' => $data['lastName'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'address' => $data['address'],
        'city' => $data['city'],
        'postcode' => $data['postcode'],
        'country' => $data['country']
    ];
    
    return [
        'success' => true,
        'message' => 'Shipping address saved successfully'
    ];
}

// Function to save payment method to session (AJAX endpoint function)
function savePaymentMethod($data) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Handle delivery option if provided
    if (isset($data['delivery_option'])) {
        $_SESSION['delivery_option'] = $data['delivery_option'];
        return [
            'success' => true,
            'message' => 'Delivery option saved successfully'
        ];
    }
    
    // Handle payment method
    if (!isset($data['method'])) {
        return [
            'success' => false,
            'message' => 'Payment method is required'
        ];
    }
    
    $_SESSION['payment_method'] = $data['method'];
    
    // Save card info if provided
    if ($data['method'] === 'card') {
        // Validate card details
        if (empty($data['card_name']) || empty($data['card_number']) || empty($data['card_expiry'])) {
            return [
                'success' => false,
                'message' => 'Card details are required'
            ];
        }
        
        $_SESSION['card_info'] = [
            'name' => $data['card_name'],
            'number' => $data['card_number'],
            'expiry' => $data['card_expiry']
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Payment method saved successfully'
    ];
}
?>