<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include database connection
try {
    include 'connect.php';
    echo "Database connection included successfully<br>";
} catch (Exception $e) {
    echo "Error including database connection: " . $e->getMessage() . "<br>";
    exit;
}

// Simple function to generate verification code
function generateVerificationCode() {
    return rand(100000, 999999);
}

// Simple function to simulate email sending for testing
function mockSendEmail($email, $firstName, $code) {
    // Just log the email instead of actually sending it
    error_log("Mock email to: $email, Name: $firstName, Code: $code");
    return true;
}

// Handle Sign Up form submission
if(isset($_POST['SignUp'])) { 
    try {
        echo "Processing signup form<br>";
        
        $firstName = mysqli_real_escape_string($conn, $_POST['fname'] ?? '');
        $lastName = mysqli_real_escape_string($conn, $_POST['lname'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        echo "Form data received: $firstName, $lastName, $email<br>";
        
        // Validate passwords match
        if($password !== $confirmPassword) {
            echo "Passwords do not match<br>";
            echo "<script>alert('Passwords do not match!'); window.location.href='login.php?form=signup';</script>";
            exit();
        }

        // Check if email already exists
        $checkEmail = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($checkEmail);
        if (!$stmt) {
            echo "Prepare statement failed: " . $conn->error . "<br>";
            exit;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            echo "Email already exists<br>";
            echo "<script>alert('Email already exists!'); window.location.href='login.php?form=signup';</script>";
        } else {
            // Generate and store verification code
            $verificationCode = generateVerificationCode();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            echo "Generated verification code: $verificationCode<br>";
            
            // Store user data in session for later use
            $_SESSION['temp_user'] = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'password' => $hashedPassword,
                'verification_code' => $verificationCode,
                'code_expiry' => time() + 1800 // 30 minutes expiry
            ];
            
            // Mock email sending for testing
            $emailSent = mockSendEmail($email, $firstName, $verificationCode);
            
            if($emailSent) {
                echo "Email would be sent (mock)<br>";
                echo "Redirecting to verify_email.php<br>";
                // Uncomment to enable redirect
                // header("Location: verify_email.php");
                // exit();
            } else {
                echo "Failed to send email<br>";
                echo "<script>alert('Failed to send verification email. Please try again.'); window.location.href='login.php?form=signup';</script>";
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        echo "Exception in signup processing: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Handle Sign In form submission (simplified)
if(isset($_POST['SignIn'])) { 
    try {
        echo "Processing signin form<br>";
        
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        echo "Attempting to log in with email: $email<br>";
        
        // Basic login functionality
        $sql = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if(password_verify($password, $row['password'])) {
                echo "Password verified successfully<br>";
                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['firstName'] = $row['firstName'];
                
                echo "Login successful, would redirect to index.php<br>";
                // Uncomment to enable redirect
                // header("Location: index.php");
                // exit();
            } else {
                echo "Invalid password<br>";
                echo "<script>alert('Invalid password!'); window.location.href='login.php';</script>";
            }
        } else {
            echo "Email not found<br>";
            echo "<script>alert('Email not found!'); window.location.href='login.php';</script>";
        }
        $stmt->close();
    } catch (Exception $e) {
        echo "Exception in signin processing: " . $e->getMessage() . "<br>";
    }
}

$conn->close();
echo "Script completed successfully";
?>