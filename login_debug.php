<?php
session_start();
include 'connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to test database connection
function testDatabaseConnection($conn) {
    if ($conn->connect_error) {
        return "Connection failed: " . $conn->connect_error;
    }
    
    // Test a simple query
    $result = $conn->query("SELECT 1");
    if (!$result) {
        return "Query failed: " . $conn->error;
    }
    
    return "Database connection successful";
}

// Function to test user existence
function testUserExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id, email, password, user_type FROM users WHERE email = ?");
    if (!$stmt) {
        return "Prepare failed: " . $conn->error;
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        return "Execute failed: " . $stmt->error;
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return [
            'found' => true,
            'id' => $user['id'],
            'email' => $user['email'],
            'password_hash' => $user['password'],
            'user_type' => $user['user_type']
        ];
    } else {
        return ['found' => false];
    }
}

// Check if there's a test email provided
$testResult = "";
if (isset($_POST['test_email'])) {
    $testEmail = $_POST['test_email'];
    
    // Test database connection
    $connectionTest = testDatabaseConnection($conn);
    
    // Test if user exists
    $userTest = testUserExists($conn, $testEmail);
    
    $testResult = "Database Test: " . $connectionTest . "<br>";
    
    if (is_array($userTest) && isset($userTest['found'])) {
        if ($userTest['found']) {
            $testResult .= "User Test: User found<br>";
            $testResult .= "User ID: " . $userTest['id'] . "<br>";
            $testResult .= "Email: " . $userTest['email'] . "<br>";
            $testResult .= "User Type: " . ($userTest['user_type'] ?? 'not set') . "<br>";
            $testResult .= "Password Hash: " . substr($userTest['password_hash'], 0, 20) . "...<br>";
            
            // Test password verification if password is provided
            if (isset($_POST['test_password']) && !empty($_POST['test_password'])) {
                $testPassword = $_POST['test_password'];
                $passwordVerifies = password_verify($testPassword, $userTest['password_hash']);
                $testResult .= "Password Verification: " . ($passwordVerifies ? "Success" : "Failed") . "<br>";
                
                // If verification fails, show more details
                if (!$passwordVerifies) {
                    $testResult .= "Current password format: " . (strpos($userTest['password_hash'], '$2y$') === 0 ? "bcrypt (correct)" : "other format (issue)") . "<br>";
                    $testResult .= "Test hash of provided password: " . password_hash($testPassword, PASSWORD_DEFAULT) . "<br>";
                }
            }
        } else {
            $testResult .= "User Test: User not found with email " . htmlspecialchars($testEmail) . "<br>";
        }
    } else {
        $testResult .= "User Test Error: " . $userTest . "<br>";
    }
}

// Get database info
$dbInfo = "Database Host: " . DB_SERVER . "<br>";
$dbInfo .= "Database Name: " . DB_NAME . "<br>";
$dbInfo .= "Database User: " . DB_USERNAME . "<br>";

// Check users table structure
$tableStructure = "";
try {
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        $tableStructure = "<table class='table table-striped'><thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";
        while ($row = $result->fetch_assoc()) {
            $tableStructure .= "<tr>";
            $tableStructure .= "<td>" . $row['Field'] . "</td>";
            $tableStructure .= "<td>" . $row['Type'] . "</td>";
            $tableStructure .= "<td>" . $row['Null'] . "</td>";
            $tableStructure .= "<td>" . $row['Key'] . "</td>";
            $tableStructure .= "<td>" . $row['Default'] . "</td>";
            $tableStructure .= "<td>" . $row['Extra'] . "</td>";
            $tableStructure .= "</tr>";
        }
        $tableStructure .= "</tbody></table>";
    } else {
        $tableStructure = "Error querying table structure: " . $conn->error;
    }
} catch (Exception $e) {
    $tableStructure = "Exception: " . $e->getMessage();
}

// Check session settings
$sessionSettings = "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not active") . "<br>";
$sessionSettings .= "Session save path: " . session_save_path() . "<br>";
$sessionSettings .= "Session cookie parameters: <pre>" . print_r(session_get_cookie_params(), true) . "</pre>";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System Debugging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Login System Debugging</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Test User Credentials
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="test_email" class="form-label">Test Email</label>
                        <input type="text" class="form-control" id="test_email" name="test_email" 
                               value="<?php echo isset($_POST['test_email']) ? htmlspecialchars($_POST['test_email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="test_password" class="form-label">Test Password</label>
                        <input type="text" class="form-control" id="test_password" name="test_password"
                               value="<?php echo isset($_POST['test_password']) ? htmlspecialchars($_POST['test_password']) : ''; ?>">
                        <div class="form-text">Enter password to verify if it matches the hash in database</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Test</button>
                </form>
                
                <?php if (!empty($testResult)): ?>
                    <div class="alert alert-info mt-3">
                        <h5>Test Results:</h5>
                        <?php echo $testResult; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        Database Information
                    </div>
                    <div class="card-body">
                        <?php echo $dbInfo; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        Session Information
                    </div>
                    <div class="card-body">
                        <?php echo $sessionSettings; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary text-white">
                Users Table Structure
            </div>
            <div class="card-body">
                <?php echo $tableStructure; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="login.php" class="btn btn-outline-primary">Go to Login Page</a>
        </div>
    </div>
</body>
</html>