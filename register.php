<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'connect.php';

// Function to handle redirects with JavaScript
function redirectWithAlert($message, $location) {
    echo "<script>alert('" . $message . "'); window.location.href='" . $location . "';</script>";
    exit();
}

// Handle Sign Up form submission
if(isset($_POST['SignUp'])) {
    try { 
        $firstName = mysqli_real_escape_string($conn, $_POST['fname']);
        $lastName = mysqli_real_escape_string($conn, $_POST['lname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate passwords match
        if($password !== $confirmPassword) {
            redirectWithAlert('Passwords do not match!', 'login.php?form=signup');
        }

        // Check if email already exists
        $checkEmail = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            redirectWithAlert('Email already exists!', 'login.php?form=signup');
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with verified status (no email verification needed)
            $insertQuery = "INSERT INTO users (firstName, lastName, email, password, email_verified) VALUES (?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);
            
            if($stmt->execute()) {
                // Get the user ID
                $userId = $conn->insert_id;
                
                // Set session variables to log the user in immediately
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['firstName'] = $firstName;
                $_SESSION['user_type'] = 'user'; // Default user type
                
                // Redirect to homepage
                redirectWithAlert('Registration successful! Welcome to Pure Linen.', 'index.php');
            } else {
                // Log the error for debugging
                error_log("Database error: " . $stmt->error);
                redirectWithAlert('Registration failed. Please try again.', 'login.php?form=signup');
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        // Log the exception for debugging
        error_log("Exception in SignUp: " . $e->getMessage());
        redirectWithAlert('An error occurred. Please try again later.', 'login.php');
    }
}

// Handle Sign In form submission
if(isset($_POST['SignIn'])) { 
    try {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if(password_verify($password, $row['password'])) {
                // User is authenticated, set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['firstName'] = $row['firstName'];
                $_SESSION['user_type'] = $row['user_type'] ?? 'user';
                
                // Redirect to appropriate page based on user type
                if(isset($row['user_type']) && $row['user_type'] == 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                redirectWithAlert('Invalid password!', 'login.php');
            }
        } else {
            redirectWithAlert('Email not found!', 'login.php');
        }
        $stmt->close();
    } catch (Exception $e) {
        // Log the exception for debugging
        error_log("Exception in SignIn: " . $e->getMessage());
        redirectWithAlert('An error occurred. Please try again later.', 'login.php');
    }
}

// Handle password reset request
if(isset($_POST['ResetPassword'])) {
    try {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $sql = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiry = time() + 1800; // 30 minutes expiry
            
            // Store reset token in database
            $updateToken = "UPDATE users SET reset_token=?, token_expiry=? WHERE id=?";
            $stmt = $conn->prepare($updateToken);
            $stmt->bind_param("ssi", $resetToken, $expiry, $row['id']);
            $stmt->execute();
            
            // Generate the reset link
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $resetToken . "&email=" . urlencode($email);
            
            // In this simplified version, we display the link for the user
            echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; margin: 20px; border-radius: 5px;'>";
            echo "<h3>Password Reset</h3>";
            echo "<p>To reset your password, click the link below:</p>";
            echo "<p><a href='" . $resetLink . "'>" . $resetLink . "</a></p>";
            echo "<p>This link will expire in 30 minutes.</p>";
            echo "<p><a href='login.php'>Return to login page</a></p>";
            echo "</div>";
            exit();
        } else {
            // Don't reveal if email exists or not for security
            redirectWithAlert('If your email is registered, you will receive password reset instructions.', 'login.php');
        }
        $stmt->close();
    } catch (Exception $e) {
        // Log the exception for debugging
        error_log("Exception in ResetPassword: " . $e->getMessage());
        redirectWithAlert('An error occurred. Please try again later.', 'login.php');
    }
}

// Handle new password submission
if(isset($_POST['SaveNewPassword'])) {
    try {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $token = mysqli_real_escape_string($conn, $_POST['token']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if($password !== $confirmPassword) {
            redirectWithAlert('Passwords do not match!', "reset_password.php?token=$token&email=$email");
        }
        
        $sql = "SELECT * FROM users WHERE email=? AND reset_token=? AND token_expiry > ?";
        $stmt = $conn->prepare($sql);
        $currentTime = time();
        $stmt->bind_param("ssi", $email, $token, $currentTime);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updatePassword = "UPDATE users SET password=?, reset_token=NULL, token_expiry=NULL WHERE id=?";
            $stmt = $conn->prepare($updatePassword);
            $stmt->bind_param("si", $hashedPassword, $row['id']);
            
            if($stmt->execute()) {
                redirectWithAlert('Your password has been updated successfully.', 'login.php');
            } else {
                redirectWithAlert('Failed to update password. Please try again.', "reset_password.php?token=$token&email=$email");
            }
        } else {
            redirectWithAlert('Invalid or expired password reset link.', 'forgot_password.php');
        }
    } catch (Exception $e) {
        // Log the exception for debugging
        error_log("Exception in SaveNewPassword: " . $e->getMessage());
        redirectWithAlert('An error occurred. Please try again later.', 'login.php');
    }
    $stmt->close();
}

// Close the database connection
$conn->close();
?>