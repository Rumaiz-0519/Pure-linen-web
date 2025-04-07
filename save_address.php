<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Process only POST requests with JSON content
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    
    // Decode the JSON data
    $data = json_decode($json, true);
    
    // Validate the received data
    if ($data && 
        isset($data['firstName']) && 
        isset($data['lastName']) && 
        isset($data['email']) && 
        isset($data['phone']) && 
        isset($data['address']) && 
        isset($data['city']) && 
        isset($data['postcode']) && 
        isset($data['country'])) {
        
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
        
        // If user is logged in, update their profile in the database
        if (isset($_SESSION['user_id'])) {
            require_once 'config.php';
            
            try {
                $update_user = $conn->prepare("UPDATE users SET 
                    firstName = ?, 
                    lastName = ?, 
                    email = ?, 
                    phone = ?, 
                    address = ?, 
                    city = ?, 
                    postcode = ?, 
                    country = ? 
                    WHERE id = ?");
                
                $update_user->bind_param(
                    "ssssssssi", 
                    $data['firstName'], 
                    $data['lastName'], 
                    $data['email'], 
                    $data['phone'], 
                    $data['address'], 
                    $data['city'], 
                    $data['postcode'], 
                    $data['country'], 
                    $_SESSION['user_id']
                );
                
                $update_user->execute();
                
                if ($update_user->affected_rows > 0 || $update_user->error === '') {
                    $db_updated = true;
                } else {
                    $db_updated = false;
                    $db_error = $update_user->error;
                }
            } catch (Exception $e) {
                $db_updated = false;
                $db_error = $e->getMessage();
            }
        }
        
        // Send success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Address saved successfully',
            'db_updated' => $db_updated ?? false,
            'db_error' => $db_error ?? null
        ]);
        exit;
    } else {
        // Send error response for invalid data
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid address data'
        ]);
        exit;
    }
} else {
    // Send error response for invalid request method
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}
?>