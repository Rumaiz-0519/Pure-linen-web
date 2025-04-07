<?php
session_start();
include 'connect.php';

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$showForm = true;
$showResetForm = false;
$email = '';

// Handle the verification form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_user'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    
    
    $query = "SELECT * FROM users WHERE email = ? AND firstName = ? AND lastName = ? AND user_type = 'user'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $email, $firstName, $lastName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_id'] = $user['id'];
        
        $showForm = false;
        $showResetForm = true;
    } else {
        $error = "The information you provided doesn't match our records. Please try again.";
    }
    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_id'])) {
        $error = "Please verify your identity first.";
    } else {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
            $showForm = false;
            $showResetForm = true;
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $userId = $_SESSION['reset_user_id'];
            
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if ($stmt->execute()) {
                $success = "Your password has been updated successfully. You can now login with your new password.";
                
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                
                $showForm = false;
                $showResetForm = false;
            } else {
                $error = "Failed to update password. Please try again.";
                $showForm = false;
                $showResetForm = true;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Pure Linen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .container {
            width: 100%;
            max-width: 450px;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .title {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #1B365D;
            margin-bottom: 20px;
        }
        
        .description {
            text-align: center;
            margin-bottom: 30px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #6c757d;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            outline: none;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus {
            border-color: #1B365D;
        }
        
        .input-group label {
            position: absolute;
            top: -10px;
            left: 10px;
            background: #fff;
            padding: 0 5px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #1B365D;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #152a4a;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .links {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #1B365D;
            text-decoration: none;
            font-weight: 500;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-rules {
            margin-top: 5px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .password-rule {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .password-rule i {
            margin-right: 5px;
            font-size: 12px;
        }
        
        .valid {
            color: #28a745;
        }
        
        .invalid {
            color: #dc3545;
        }
        
        .password-match {
            font-size: 12px;
            margin-top: -20px;
            margin-bottom: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">Forgot Password</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="login.php" class="btn">Go to Login</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($showForm): ?>
            <p class="description">
                To reset your password, please confirm your account details below.
            </p>
            
            <form method="post" action="">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>" required>
                    <label for="email">Email Address</label>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required>
                    <label for="first_name">First Name</label>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required>
                    <label for="last_name">Last Name</label>
                </div>
                
                <button type="submit" name="verify_user" class="btn">Verify Account</button>
            </form>
        <?php endif; ?>
        
        <?php if ($showResetForm): ?>
            <p class="description">
                Your identity has been verified. Please create a new password.
            </p>
            
            <form method="post" action="" id="resetForm">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Enter new password" required>
                    <label for="password">New Password</label>
                </div>
                
                <div class="password-rules">
                    <div class="password-rule" id="length">
                        <i class="fas fa-times-circle"></i> At least 8 characters
                    </div>
                    <div class="password-rule" id="uppercase">
                        <i class="fas fa-times-circle"></i> At least one uppercase letter
                    </div>
                    <div class="password-rule" id="lowercase">
                        <i class="fas fa-times-circle"></i> At least one lowercase letter
                    </div>
                    <div class="password-rule" id="number">
                        <i class="fas fa-times-circle"></i> At least one number
                    </div>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" required>
                    <label for="confirmPassword">Confirm New Password</label>
                </div>
                <div class="password-match" id="passwordMatch"></div>
                
                <button type="submit" name="reset_password" class="btn" id="resetBtn" disabled>Reset Password</button>
            </form>
        <?php endif; ?>
        
        <div class="links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetForm = document.getElementById('resetForm');
            
            if (resetForm) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirmPassword');
                const resetBtn = document.getElementById('resetBtn');
                const passwordMatch = document.getElementById('passwordMatch');
                
                // Password rule elements
                const lengthRule = document.getElementById('length');
                const uppercaseRule = document.getElementById('uppercase');
                const lowercaseRule = document.getElementById('lowercase');
                const numberRule = document.getElementById('number');
                
                function checkPasswordStrength(password) {
                    let valid = true;
                    
                    // Check length
                    if (password.length >= 8) {
                        lengthRule.classList.add('valid');
                        lengthRule.classList.remove('invalid');
                        lengthRule.innerHTML = '<i class="fas fa-check-circle"></i> At least 8 characters';
                    } else {
                        lengthRule.classList.add('invalid');
                        lengthRule.classList.remove('valid');
                        lengthRule.innerHTML = '<i class="fas fa-times-circle"></i> At least 8 characters';
                        valid = false;
                    }
                    
                    // Check for uppercase letters
                    if (/[A-Z]/.test(password)) {
                        uppercaseRule.classList.add('valid');
                        uppercaseRule.classList.remove('invalid');
                        uppercaseRule.innerHTML = '<i class="fas fa-check-circle"></i> At least one uppercase letter';
                    } else {
                        uppercaseRule.classList.add('invalid');
                        uppercaseRule.classList.remove('valid');
                        uppercaseRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one uppercase letter';
                        valid = false;
                    }
                    
                    // Check for lowercase letters
                    if (/[a-z]/.test(password)) {
                        lowercaseRule.classList.add('valid');
                        lowercaseRule.classList.remove('invalid');
                        lowercaseRule.innerHTML = '<i class="fas fa-check-circle"></i> At least one lowercase letter';
                    } else {
                        lowercaseRule.classList.add('invalid');
                        lowercaseRule.classList.remove('valid');
                        lowercaseRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one lowercase letter';
                        valid = false;
                    }
                    
                    // Check for numbers
                    if (/\d/.test(password)) {
                        numberRule.classList.add('valid');
                        numberRule.classList.remove('invalid');
                        numberRule.innerHTML = '<i class="fas fa-check-circle"></i> At least one number';
                    } else {
                        numberRule.classList.add('invalid');
                        numberRule.classList.remove('valid');
                        numberRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one number';
                        valid = false;
                    }
                    
                    return valid;
                }
                
                function checkPasswordsMatch() {
                    if (confirmPassword.value === '') {
                        passwordMatch.innerHTML = '';
                        return false;
                    } else if (password.value === confirmPassword.value) {
                        passwordMatch.innerHTML = 'Passwords match';
                        passwordMatch.className = 'password-match valid';
                        return true;
                    } else {
                        passwordMatch.innerHTML = 'Passwords do not match';
                        passwordMatch.className = 'password-match invalid';
                        return false;
                    }
                }
                
                function validateForm() {
                    const passwordValid = checkPasswordStrength(password.value);
                    const passwordsMatch = checkPasswordsMatch();
                    
                    if (passwordValid && passwordsMatch) {
                        resetBtn.disabled = false;
                    } else {
                        resetBtn.disabled = true;
                    }
                }
                
                password.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    if (confirmPassword.value !== '') {
                        checkPasswordsMatch();
                    }
                    validateForm();
                });
                
                confirmPassword.addEventListener('input', function() {
                    checkPasswordsMatch();
                    validateForm();
                });
                
                // Initial validation
                if (password.value) {
                    checkPasswordStrength(password.value);
                }
                if (confirmPassword.value) {
                    checkPasswordsMatch();
                }
                validateForm();
            }
        });
    </script>
</body>
</html>