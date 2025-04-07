<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Create direct database connection (same as admin_users.php)
$host = "localhost";
$user = "root";
$pass = "";
$db = "pure_linen";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';
$admin_id = $_SESSION['user_id'];

// Get admin info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    if (password_verify($currentPassword, $admin['password'])) {
        // Check if email is already in use by another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $admin_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email is already in use by another user.";
        } else {
            // Start preparing the update query
            $query = "UPDATE users SET firstName = ?, lastName = ?, email = ?";
            $params = array($firstName, $lastName, $email);
            $types = "sss";
            
            // If new password is provided, update it
            if (!empty($newPassword)) {
                // Check if new password and confirm password match
                if ($newPassword !== $confirmPassword) {
                    $error_message = "New password and confirm password do not match.";
                } elseif (strlen($newPassword) < 8) {
                    $error_message = "New password must be at least 8 characters long.";
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $query .= ", password = ?";
                    $params[] = $hashedPassword;
                    $types .= "s";
                }
            }
            
            // If no error has occurred, proceed with the update
            if (empty($error_message)) {
                $query .= " WHERE id = ?";
                $params[] = $admin_id;
                $types .= "i";
                
                $update_stmt = $conn->prepare($query);
                $update_stmt->bind_param($types, ...$params);
                
                if ($update_stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    
                    // Update session data
                    $_SESSION['firstName'] = $firstName;
                    $_SESSION['email'] = $email;
                    
                    // Refresh admin data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $admin_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $admin = $result->fetch_assoc();
                } else {
                    $error_message = "Failed to update profile: " . $conn->error;
                }
            }
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Get admin name for the header
$admin_name = $_SESSION['firstName'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #1B365D;
            --secondary-color: #708090;
            --dark-color: #0c1d36;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Arial', sans-serif;
            padding-top: 60px;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            min-height: calc(100vh - 60px);
            padding-top: 20px;
            color: white;
        }
        
        .sidebar-title {
            font-size: 20px;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px 15px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-title {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 25px;
        }
        
        .admin-container {
            max-width: 1200px;
        }
        
        .admin-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .password-feedback {
            margin-top: 5px;
            font-size: 0.875rem;
        }
        
        .password-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .password-section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="ms-2 fw-bold">Pure Linen Admin</span>
            </a>
            
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="admin_profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-title">Dashboard</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li>
                        <a href="product_management.php"><i class="fas fa-box"></i> Products</a>
                    </li>
                    <li>
                        <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                    </li>
                    <li>
                        <a href="featured_products.php"><i class="fas fa-star"></i> Featured Products</a>
                    </li>
                    <li>
                        <a href="admin_subscriptions.php"><i class="fas fa-book"></i> Subscriptions</a>
                    </li>
                    <li>
                        <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li>
                        <a href="admin_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                    </li>
                    <li>
                        <a href="admin_admins.php"><i class="fas fa-user-shield"></i> Admins</a>
                    </li>
                    <li>
                        <a href="admin_profile.php" class="active"><i class="fas fa-cog"></i> Profile</a>
                    </li>
                    <li>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <h1 class="admin-title">Update Profile</h1>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="admin-form">
                            <form action="admin_profile.php" method="POST" id="profileForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="firstName" class="form-label">First Name</label>
                                            <input type="text" id="firstName" name="firstName" class="form-control" value="<?php echo htmlspecialchars($admin['firstName']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="lastName" class="form-label">Last Name</label>
                                            <input type="text" id="lastName" name="lastName" class="form-control" value="<?php echo htmlspecialchars($admin['lastName']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <div class="password-section">
                                    <div class="password-section-title">
                                        <i class="fas fa-lock me-2"></i>Password Settings
                                    </div>
                                
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                                        <div class="form-text">Required to confirm any changes</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control">
                                        <div id="passwordFeedback" class="password-feedback"></div>
                                        <div class="form-text">Leave empty to keep current password</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                                        <div id="passwordMatchStatus" class="password-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordFeedback = document.getElementById('passwordFeedback');
        const passwordMatchStatus = document.getElementById('passwordMatchStatus');
        const profileForm = document.getElementById('profileForm');
        
        // Password strength validation
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                passwordFeedback.innerHTML = '';
                return;
            }
            
            if (password.length < 8) {
                passwordFeedback.className = 'password-feedback text-danger';
                passwordFeedback.innerHTML = '<i class="fas fa-times-circle"></i> Password should be at least 8 characters';
            } else if (!/[A-Z]/.test(password)) {
                passwordFeedback.className = 'password-feedback text-warning';
                passwordFeedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> Add an uppercase letter for stronger password';
            } else if (!/[0-9]/.test(password)) {
                passwordFeedback.className = 'password-feedback text-warning';
                passwordFeedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> Add a number for stronger password';
            } else if (!/[!@#$%^&*]/.test(password)) {
                passwordFeedback.className = 'password-feedback text-info';
                passwordFeedback.innerHTML = '<i class="fas fa-info-circle"></i> Add a special character for even stronger password';
            } else {
                passwordFeedback.className = 'password-feedback text-success';
                passwordFeedback.innerHTML = '<i class="fas fa-check-circle"></i> Password strength: Strong';
            }
            
            // Check password match if confirm password has a value
            if (confirmPasswordInput.value) {
                checkPasswordMatch();
            }
        });
        
        // Check password match
        function checkPasswordMatch() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (!confirmPassword || !password) {
                passwordMatchStatus.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatchStatus.className = 'password-feedback text-success';
                passwordMatchStatus.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            } else {
                passwordMatchStatus.className = 'password-feedback text-danger';
                passwordMatchStatus.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            }
        }
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // Form validation before submit
        profileForm.addEventListener('submit', function(event) {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Only validate passwords if new password is provided
            if (password) {
                if (password.length < 8) {
                    event.preventDefault();
                    alert('New password must be at least 8 characters long.');
                    newPasswordInput.focus();
                    return false;
                }
                
                if (password !== confirmPassword) {
                    event.preventDefault();
                    alert('New password and confirm password do not match.');
                    confirmPasswordInput.focus();
                    return false;
                }
            }
            
            return true;
        });
    });
    </script>
</body>
</html>