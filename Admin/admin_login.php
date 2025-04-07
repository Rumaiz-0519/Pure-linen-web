<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Create a standalone database connection instead of requiring connect.php
$host = "localhost";
$user = "root";
$pass = "";
$db = "pure_linen";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$error_message = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        // First try the admins table
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        if (!$stmt) {
            $error_message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                // For admins table, check if password matches directly (not hashed)
                if ($password === $admin['password']) {
                    // Set session variables
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['firstName'] = $admin['admin_name'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['user_type'] = 'admin';
                    
                    // Redirect to admin dashboard
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                // If not found in admins table, try the users table with admin type
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'admin'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // For users table, check with password verification if using hashed passwords
                    // Otherwise do a direct comparison
                    if (strlen($user['password']) > 20) {
                        // Likely a hashed password
                        $passwordMatches = password_verify($password, $user['password']);
                    } else {
                        // Likely a plain text password
                        $passwordMatches = ($password === $user['password']);
                    }
                    
                    if ($passwordMatches) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['firstName'] = $user['firstName'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = 'admin';
                        
                        // Redirect to admin dashboard
                        header("Location: admin_dashboard.php");
                        exit();
                    } else {
                        $error_message = "Invalid email or password.";
                    }
                } else {
                    $error_message = "Invalid email or password.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
            text-align: center;
        }
        
        .login-header {
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #1B365D;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn-login {
            background-color: #1B365D;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 15px;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #152c4d;
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #1B365D;
        }
        
        .alert {
            margin-bottom: 20px;
            padding: 10px 15px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Pure Linen Admin Login</h1>
            <p>Enter your credentials to access the admin panel</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to website
        </a>
    </div>
</body>
</html>