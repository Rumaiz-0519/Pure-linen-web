<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'checkout_functions.php';

// Make sure users table has all necessary columns
try {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS country VARCHAR(100) AFTER postcode");
} catch (Exception $e) {
    error_log("Error updating users table: " . $e->getMessage());
}

// Check if cart is empty, redirect to cart page if it is
$cart_items = getCartItems();
if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Get cart totals
$cart_total = getCartTotal();
$delivery_fee = 250; // Default delivery fee

// Initialize variables for form data
$firstName = $lastName = $email = $phone = $address = $city = $postcode = $country = '';
$payment_method = isset($_SESSION['payment_method']) ? $_SESSION['payment_method'] : 'cod'; // Default to COD if not set
$delivery_option = isset($_SESSION['delivery_option']) ? $_SESSION['delivery_option'] : 'standard'; // Default delivery option
$delivery_cost = ($delivery_option === 'express') ? 600 : 250; // Default delivery cost
$order_total = $cart_total + $delivery_cost;

// Initialize card info variables
$cardName = isset($_SESSION['card_info']['name']) ? $_SESSION['card_info']['name'] : '';
$cardNumber = isset($_SESSION['card_info']['number']) ? $_SESSION['card_info']['number'] : '';
$cardExpiry = isset($_SESSION['card_info']['expiry']) ? $_SESSION['card_info']['expiry'] : '';

// Handle form submission
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $delivery_option = trim($_POST['delivery_option'] ?? 'standard');
    
    // Form validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($postcode)) $errors[] = "Post code is required";
    if (empty($country)) $errors[] = "Country is required";
    if (empty($payment_method)) $errors[] = "Please select a payment method";
    
    // Validate card details if card payment is selected
    if ($payment_method === 'card') {
        $cardName = trim($_POST['card_name'] ?? '');
        $cardNumber = trim($_POST['card_number'] ?? '');
        $cardExpiry = trim($_POST['card_expiry'] ?? '');
        $cardCvv = trim($_POST['card_cvv'] ?? '');
        
        if (empty($cardName)) $errors[] = "Card name is required";
        if (empty($cardNumber)) $errors[] = "Card number is required";
        if (empty($cardExpiry)) $errors[] = "Card expiry date is required";
        if (empty($cardCvv)) $errors[] = "CVV is required";
        
        // Save card info to session (except CVV for security)
        $_SESSION['card_info'] = [
            'name' => $cardName,
            'number' => $cardNumber,
            'expiry' => $cardExpiry
        ];
    }
    
    // Update delivery cost based on selected option
    if ($delivery_option === 'express') {
        $delivery_cost = 600;
    } else {
        $delivery_cost = 250;
    }
    $order_total = $cart_total + $delivery_cost;
    
    // Save address and payment preferences to session for all users (logged in or not)
    $_SESSION['shipping_address'] = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'postcode' => $postcode,
        'country' => $country
    ];
    
    // Save payment and delivery preferences
    $_SESSION['payment_method'] = $payment_method;
    $_SESSION['delivery_option'] = $delivery_option;
    
    // If no errors, process the order
    if (empty($errors)) {
        $order_id = processOrder($conn, $_SESSION['user_id'] ?? null, $order_total, $payment_method, $address, $city, $postcode, 
                                $country, $delivery_option, $delivery_cost, $cart_items);
        
        if ($order_id) {
            // Set a success message and redirect to order confirmation
            $_SESSION['order_id'] = $order_id;
            header("Location: order_confirmation.php");
            exit();
        } else {
            $errors[] = "Order processing failed. Please try again.";
        }
    }
}

// Try to get address info first from session (for any user)
if (isset($_SESSION['shipping_address'])) {
    $shipping = $_SESSION['shipping_address'];
    $firstName = $shipping['firstName'];
    $lastName = $shipping['lastName'];
    $email = $shipping['email'];
    $phone = $shipping['phone'];
    $address = $shipping['address'];
    $city = $shipping['city'];
    $postcode = $shipping['postcode'];
    $country = $shipping['country'];
}

// If logged in, override with user info from DB (which might be more current)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $firstName = $user['firstName'];
        $lastName = $user['lastName'];
        $email = $user['email'];
        $phone = $user['phone'] ?? '';
        $address = $user['address'] ?? '';
        $city = $user['city'] ?? '';
        $postcode = $user['postcode'] ?? '';
        $country = $user['country'] ?? '';
    }
}

// Include the view
include 'checkout_view.php';
?>