<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session at the very beginning
session_start();

// Log the current session data to help with debugging
error_log("Session data in verify_email.php: " . print_r($_SESSION, true));

// Basic database connection with error handling
try {
    include 'connect.php';
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Check if user has temp data - if not, redirect to login
if (!isset($_SESSION['temp_user'])) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;'>
        Session data missing. Redirecting to login page...
    </div>";
    header("Refresh: 3; URL=login.php");
    exit();
}

$error = "";
$success = "";

// Handle form submission for verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
    $entered_code = $_POST['verification_code'];
    $stored_code = $_SESSION['temp_user']['verification_code'];
    $expiry_time = $_SESSION['temp_user']['code_expiry'];
    
    // Check if code is expired
    if (time() > $expiry_time) {
        $error = "Verification code has expired. Please request a new one.";
    } 
    // Check if code matches
    elseif ($entered_code == $stored_code) {
        // Check if user is already registered (pending verification)
        if (isset($_SESSION['temp_user']['id'])) {
            // Update existing user
            $updateUser = "UPDATE users SET email_verified=1, verification_code=NULL, code_expiry=NULL WHERE id=?";
            $stmt = $conn->prepare($updateUser);
            $stmt->bind_param("i", $_SESSION['temp_user']['id']);
            
            if ($stmt->execute()) {
                // Set session variables
                $_SESSION['user_id'] = $_SESSION['temp_user']['id'];
                $_SESSION['email'] = $_SESSION['temp_user']['email'];
                $_SESSION['firstName'] = $_SESSION['temp_user']['firstName'];
                
                // Clear temp data
                unset($_SESSION['temp_user']);
                
                $success = "Email verification successful! Redirecting to home page...";
                header("Refresh: 3; URL=index.php");
            } else {
                $error = "Failed to verify email. Database error: " . $stmt->error;
            }
        } else {
            $error = "User data not found. Please try registering again.";
        }
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}

// Handle resend verification code
if (isset($_POST['resend_code'])) {
    // Generate new verification code
    $newCode = rand(100000, 999999);
    $_SESSION['temp_user']['verification_code'] = $newCode;
    $_SESSION['temp_user']['code_expiry'] = time() + 1800; // 30 minutes
    
    // Update the database if user exists
    if (isset($_SESSION['temp_user']['id'])) {
        $updateCode = "UPDATE users SET verification_code=?, code_expiry=? WHERE id=?";
        $stmt = $conn->prepare($updateCode);
        $codeExpiry = $_SESSION['temp_user']['code_expiry'];
        $userId = $_SESSION['temp_user']['id'];
        $stmt->bind_param("sii", $newCode, $codeExpiry, $userId);
        $stmt->execute();
    }
    
    // For local development, show the code
    $success = "A new verification code has been generated.";
    if ($_SERVER['SERVER_NAME'] == 'localhost') {
        $success .= "<br><strong>Debug Mode - Verification Code: " . $newCode . "</strong>";
    }
}

// Debug - Display verification code on screen during development
$debug_info = "";
if (isset($_SESSION['temp_user']['verification_code'])) {
    $debug_info = "<div class='alert alert-info'>
        <strong>Debug Mode:</strong> Verification Code: " . $_SESSION['temp_user']['verification_code'] . "
    </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Pure Linen</title>
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
        
        .verification-container {
            width: 100%;
            max-width: 500px;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .verification-title {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            color: #1B365D;
            margin-bottom: 30px;
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
        
        .alert-info {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1B365D;
        }
        
        .verification-input {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 5px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .verification-input:focus {
            border-color: #1B365D;
            box-shadow: 0 0 0 3px rgba(27, 54, 93, 0.1);
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
        
        .btn-link {
            background: none;
            color: #1B365D;
            padding: 8px 12px;
            text-decoration: underline;
            cursor: pointer;
            border: none;
            font-weight: 500;
        }
        
        .btn-link:hover {
            text-decoration: none;
            background: none;
            color: #0d1b30;
        }
        
        .text-center {
            text-align: center;
        }
        
        .timer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .timer-countdown {
            font-weight: bold;
            color: #1B365D;
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #1B365D;
            border-radius: 4px;
            transition: width 1s linear;
        }
        
        .email-info {
            text-align: center;
            margin-bottom: 20px;
            color: #6c757d;
        }
        
        .email-address {
            font-weight: 600;
            color: #1B365D;
        }
        
        .debug-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .debug-data {
            font-family: monospace;
            background-color: #eee;
            padding: 10px;
            border-radius: 3px;
            overflow: auto;
            max-height: 200px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2 class="verification-title">Verify Your Email</h2>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php echo $debug_info; ?>
        
        <p class="email-info">
            We've sent a verification code to <br>
            <span class="email-address"><?php echo isset($_SESSION['temp_user']['email']) ? htmlspecialchars($_SESSION['temp_user']['email']) : 'your email address'; ?></span>
        </p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="verification_code" class="form-label">Enter Verification Code</label>
                <input type="text" class="verification-input" id="verification_code" name="verification_code" 
                       maxlength="6" autocomplete="off" inputmode="numeric" pattern="[0-9]*" required>
            </div>
            
            <button type="submit" name="verify_code" class="btn">Verify Email</button>
        </form>
        
        <div class="text-center" style="margin-top: 20px;">
            <p style="margin-bottom: 10px;">Didn't receive the code?</p>
            <form method="POST" action="">
                <button type="submit" name="resend_code" class="btn-link">Resend Code</button>
            </form>
        </div>
        
        <div class="timer">
            <p>Code expires in: <span id="timer" class="timer-countdown">30:00</span></p>
            <div class="progress-container">
                <div class="progress-bar" id="timer-progress" style="width: 100%;"></div>
            </div>
        </div>
        
        <div class="debug-section">
            <h3>Debug Information</h3>
            <div class="debug-data">
                <pre><?php
                    echo "Session Data:\n";
                    print_r($_SESSION);
                    
                    echo "\nPOST Data:\n";
                    print_r($_POST);
                    
                    echo "\nServer:\n";
                    echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
                    echo "PHP Version: " . phpversion() . "\n";
                ?></pre>
            </div>
        </div>
    </div>
    
    <script>
        // Timer for code expiration
        document.addEventListener('DOMContentLoaded', function() {
            // Default to 30 minutes if expiry is not set
            const defaultExpiry = <?php echo time() + 1800; ?>;
            const expiry = <?php echo isset($_SESSION['temp_user']['code_expiry']) ? $_SESSION['temp_user']['code_expiry'] : 'defaultExpiry'; ?>;
            const totalTime = 1800; // 30 minutes in seconds
            let timeLeft = expiry > 0 ? Math.max(0, expiry - <?php echo time(); ?>) : 0;
            
            const timerDisplay = document.getElementById('timer');
            const timerProgress = document.getElementById('timer-progress');
            
            // Focus on verification input
            const verificationInput = document.getElementById('verification_code');
            if (verificationInput) {
                verificationInput.focus();
            }
            
            const countdownTimer = setInterval(function() {
                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    timerDisplay.textContent = "Expired";
                    timerProgress.style.width = "0%";
                    return;
                }
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                timerDisplay.textContent = minutes.toString().padStart(2, '0') + ":" + seconds.toString().padStart(2, '0');
                
                // Update progress bar
                const progressPercentage = (timeLeft / totalTime) * 100;
                timerProgress.style.width = progressPercentage + "%";
                
                timeLeft--;
            }, 1000);
            
            // Format verification code input as user types
            if (verificationInput) {
                verificationInput.addEventListener('input', function(e) {
                    // Keep only numbers
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Auto-submit when 6 digits are entered
                    if (this.value.length === 6) {
                        // Give a small delay to allow user to see what they typed
                        setTimeout(() => {
                            const submitButton = document.querySelector('button[name="verify_code"]');
                            if (submitButton) {
                                submitButton.click();
                            }
                        }, 500);
                    }
                });
            }
        });
    </script>
</body>
</html>