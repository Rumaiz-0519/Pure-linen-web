<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON for response
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate required fields
if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'No data provided'
    ]);
    exit;
}

// Save payment method if provided
if (isset($data['method'])) {
    $method = $data['method'];
    
    // Validate payment method
    if ($method !== 'card' && $method !== 'cod') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid payment method'
        ]);
        exit;
    }
    
    // Save payment method to session
    $_SESSION['payment_method'] = $method;
    
    // If card payment, save card details
    if ($method === 'card' && 
        isset($data['card_name']) && 
        isset($data['card_number']) && 
        isset($data['card_expiry'])) {
            
        $_SESSION['card_info'] = [
            'name' => trim($data['card_name']),
            'number' => trim($data['card_number']),
            'expiry' => trim($data['card_expiry'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment method saved successfully'
    ]);
    exit;
}

// Save delivery option if provided
if (isset($data['delivery_option'])) {
    $delivery_option = $data['delivery_option'];
    
    // Validate the delivery option
    if ($delivery_option !== 'standard' && $delivery_option !== 'express') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid delivery option'
        ]);
        exit;
    }
    
    // Save delivery option to session
    $_SESSION['delivery_option'] = $delivery_option;
    
    // Return success with the delivery cost
    $delivery_cost = ($delivery_option === 'express') ? 600 : 250;
    
    echo json_encode([
        'success' => true,
        'message' => 'Delivery option saved successfully',
        'delivery_cost' => $delivery_cost
    ]);
    exit;
}

// If neither payment method nor delivery option is provided
echo json_encode([
    'success' => false,
    'message' => 'Invalid data format'
]);