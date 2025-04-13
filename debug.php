<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create a nicely formatted output function
function debug_output($message, $data = null) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;'>";
    echo "<strong>$message</strong>";
    if ($data !== null) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    echo "</div>";
}

// Basic server info
debug_output("PHP Version", phpversion());
debug_output("Server Software", $_SERVER['SERVER_SOFTWARE']);

// Try to include config.php and check connection
debug_output("Testing database connection");
try {
    require_once 'config.php';
    debug_output("Config file loaded successfully");
    
    if (isset($conn) && $conn instanceof mysqli) {
        if ($conn->connect_error) {
            debug_output("Database connection FAILED", $conn->connect_error);
        } else {
            debug_output("Database connection SUCCESSFUL");
            
            // Check if the required tables exist
            $tables = ['admins', 'users', 'products', 'cart_items', 'bulk_messages'];
            $missing_tables = [];
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows == 0) {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                debug_output("All required tables exist");
            } else {
                debug_output("MISSING TABLES", $missing_tables);
            }
            
            // Check session data
            debug_output("Session status", session_status());
            debug_output("Session variables", $_SESSION);
        }
    } else {
        debug_output("Database connection variable not found or not a mysqli instance");
    }
} catch (Exception $e) {
    debug_output("Error loading config file", $e->getMessage());
}

// Check file permissions
$files_to_check = [
    'config.php', 
    'admin_login.php', 
    'admin/index.php'
];

debug_output("Checking file permissions and existence");
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        debug_output("File exists: $file", "Permissions: " . substr(sprintf('%o', fileperms($file)), -4));
    } else {
        debug_output("File DOES NOT EXIST: $file");
    }
}

// Check for syntax errors in index.php
debug_output("Checking for syntax errors in admin/index.php");
$output = [];
exec('php -l admin/index.php 2>&1', $output, $return_var);
debug_output("PHP Lint result", implode("\n", $output));

echo "<h2>Debugging Complete</h2>";
?>