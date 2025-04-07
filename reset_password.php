<?php
session_start();
include 'connect.php';

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if token and email are provided
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    echo "<script>alert('Invalid password reset link.'); window.location.href='login.php';</script>";
    exit();
}

$token = $_GET['token'];
$email = $_GET['email'];

// Verify token validity
$sql = "SELECT * FROM users WHERE email=? AND reset_token=? AND token_expiry > ?";
$stmt = $conn->prepare($sql);
$currentTime = time();
$stmt->bind_param("ssi", $email, $token, $currentTime);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Invalid or expired password reset link.'); window.location.href='login.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Pure Linen</title>
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
        
        .password-strength-meter {
            height: 4px;
            width: 100%;
            background-color: #e9ecef;
            margin-top: 5px;
            margin-bottom: 10px;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-meter-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background-color 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">Reset Password</h1>
        <p class="description">
            Create a new password for your account.
        </p>
        
        <form method="post" action="register.php" id="resetForm">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Enter new password" required>
                <label for="password">New Password</label>
            </div>
            
            <div class="password-strength-meter">
                <div class="password-strength-meter-bar" id="strength-meter-bar"></div>
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
            
            <button type="submit" class="btn" name="SaveNewPassword" id="savePasswordBtn" disabled>Save New Password</button>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const savePasswordBtn = document.getElementById('savePasswordBtn');
            const passwordMatch = document.getElementById('passwordMatch');
            const strengthMeterBar = document.getElementById('strength-meter-bar');
            
            // Password rule elements
            const lengthRule = document.getElementById('length');
            const uppercaseRule = document.getElementById('uppercase');
            const lowercaseRule = document.getElementById('lowercase');
            const numberRule = document.getElementById('number');
            
            // Colors for password strength
            const strengthColors = ['#dc3545', '#ffc107', '#28a745', '#20c997'];
            
            function checkPasswordStrength(password) {
                let strength = 0;
                let valid = true;
                
                // Check length
                if (password.length >= 8) {
                    lengthRule.classList.add('valid');
                    lengthRule.classList.remove('invalid');
                    lengthRule.innerHTML = '<i class="fas fa-check-circle"></i> At least 8 characters';
                    strength += 1;
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
                    strength += 1;
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
                    strength += 1;
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
                    strength += 1;
                } else {
                    numberRule.classList.add('invalid');
                    numberRule.classList.remove('valid');
                    numberRule.innerHTML = '<i class="fas fa-times-circle"></i> At least one number';
                    valid = false;
                }
                
                // Update strength meter
                strengthMeterBar.style.width = (strength * 25) + '%';
                strengthMeterBar.style.backgroundColor = strengthColors[strength - 1] || '#e9ecef';
                
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
                    savePasswordBtn.disabled = false;
                } else {
                    savePasswordBtn.disabled = true;
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
        });
    </script>
</body>
</html>