<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "pure_linen";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle admin deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $admin_id = $_GET['delete'];
    
    if ($admin_id == $_SESSION['user_id']) {
        $error_message = "You cannot delete your own account!";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND user_type = 'admin'");
        $delete_stmt->bind_param("i", $admin_id);
        
        if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
            $success_message = "Admin deleted successfully!";
        } else {
            $error_message = "Failed to delete admin: " . $conn->error;
        }
    }
}

// Handle new admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify current admin credentials before creating new admin
    $currentAdminEmail = $_SESSION['email'] ?? '';
    $currentAdminPassword = $_POST['currentAdminPassword'] ?? '';
    
    if (empty($currentAdminPassword)) {
        $error_message = "Current admin password is required for verification!";
    } else {
        // Verify the current admin's password
        $verify_stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND user_type = 'admin'");
        $verify_stmt->bind_param("i", $_SESSION['user_id']);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 1) {
            $admin_data = $verify_result->fetch_assoc();
            $stored_hash = $admin_data['password'];
        
            // Verify password
            if (password_verify($currentAdminPassword, $stored_hash)) {
                // Password verified, proceed with new admin creation
                $firstName = trim($_POST['firstName'] ?? '');
                $lastName = trim($_POST['lastName'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirmPassword'] ?? '';
                
                if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
                    $error_message = "All fields are required!";
                } elseif ($password !== $confirmPassword) {
                    $error_message = "Passwords do not match!";
                } elseif (strlen($password) < 8) {
                    $error_message = "Password must be at least 8 characters long!";
                } else {
                    // Check if email already exists
                    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $check_stmt->bind_param("s", $email);
                    $check_stmt->execute();
                    $check_stmt->bind_result($count);
                    $check_stmt->fetch();
                    $check_stmt->close();
                    
                    if ($count > 0) {
                        $error_message = "Email already exists!";
                    } else {
                        // Create new admin account
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $insert_stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, user_type) VALUES (?, ?, ?, ?, 'admin')");
                        $insert_stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);
                        
                        if ($insert_stmt->execute()) {
                            $success_message = "New admin account created successfully!";
                        } else {
                            $error_message = "Failed to create admin account: " . $conn->error;
                        }
                    }
                }
            } else {
                $error_message = "Invalid password. Please enter your correct password to create a new admin.";
            }
        } else {
            $error_message = "Failed to verify admin credentials.";
        }
    }
}

// Get all admins
$stmt = $conn->prepare("SELECT * FROM users WHERE user_type = 'admin' ORDER BY firstName, lastName");
$stmt->execute();
$result = $stmt->get_result();
$admins = [];

while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}

// Get admin name
$admin_name = $_SESSION['firstName'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admins - Admin Dashboard</title>
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
        
        .page-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: var(--primary-color);
        }
        
        .admin-table {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .admin-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 15px;
            font-weight: 500;
        }
        
        .admin-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .admin-table tr:nth-child(even) {
            background-color: rgba(0,0,0,0.02);
        }
        
        .admin-table tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        
        .admin-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .form-title {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f2f2f2;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.3s;
            color: white;
            text-decoration: none;
        }
        
        .edit-btn {
            background-color: var(--primary-color);
        }
        
        .delete-btn {
            background-color: var(--danger-color);
        }
        
        .action-btn:hover {
            opacity: 0.9;
            color: white;
        }
        
        .current-user {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .security-section {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        
        .security-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .password-feedback {
            margin-top: 5px;
            font-size: 0.875rem;
        }
        
        .password-match-status {
            margin-top: 5px;
            font-size: 0.875rem;
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
                        <a href="featured_products.php" class=""><i class="fas fa-star"></i> Featured Products</a>
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
                        <a href="admin_admins.php" class="active"><i class="fas fa-user-shield"></i> Admins</a>
                    </li>
                    <li>
                        <a href="admin_profile.php"><i class="fas fa-cog"></i> Profile</a>
                    </li>
                    <li>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <h1 class="page-title">Admin Accounts</h1>
                
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
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="admin-table table-responsive mb-4">
                            <table class="table table-borderless mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Created On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($admins)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No admin accounts found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($admins as $admin): ?>
                                            <tr class="<?php echo ($admin['id'] == $_SESSION['user_id']) ? 'current-user' : ''; ?>">
                                                <td><?php echo $admin['id']; ?></td>
                                                <td><?php echo htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']); ?></td>
                                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-info">Current User</span>
                                                    <?php else: ?>
                                                        <div class="d-flex gap-2">
                                                            <a href="admin_admins.php?delete=<?php echo $admin['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this admin account? This action cannot be undone.')" title="Delete Admin">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="admin-form">
                            <h3 class="form-title">Create New Admin</h3>
                            
                            <div class="security-section mb-4">
                                <div class="security-title">
                                    <i class="fas fa-shield-alt me-2"></i> Security Verification
                                </div>
                                <p class="text-muted small">For security purposes, you must verify your identity by entering your current password before creating a new admin account.</p>
                            </div>
                            
                            <form action="admin_admins.php" method="POST" id="createAdminForm">
                                <!-- Current Admin Verification -->
                                <div class="mb-4">
                                    <label for="currentAdminPassword" class="form-label">Your Current Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="currentAdminPassword" name="currentAdminPassword" required>
                                    <div class="form-text text-muted">Verify your identity to create a new admin</div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <!-- New Admin Information -->
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                    <div id="passwordFeedback" class="password-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" minlength="8" required>
                                    <div id="passwordMatchStatus" class="password-match-status"></div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="submitBtn">Create Admin</button>
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
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordFeedback = document.getElementById('passwordFeedback');
        const passwordMatchStatus = document.getElementById('passwordMatchStatus');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('createAdminForm');
        
        // Password strength validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
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
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (!confirmPassword) {
                passwordMatchStatus.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatchStatus.className = 'password-match-status text-success';
                passwordMatchStatus.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            } else {
                passwordMatchStatus.className = 'password-match-status text-danger';
                passwordMatchStatus.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            }
        }
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // Form validation before submit
        form.addEventListener('submit', function(event) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long.');
                passwordInput.focus();
                return false;
            }
            
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match.');
                confirmPasswordInput.focus();
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>