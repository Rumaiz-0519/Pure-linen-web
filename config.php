<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');  
define('DB_PASSWORD', '');      
define('DB_NAME', 'pure_linen');

// Create database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}