<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if PHPMailer is already included, if not try to include it
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $phpmailerPath = __DIR__ . '/PHPMailer/src/';
    
    if (file_exists($phpmailerPath . 'Exception.php')) {
        require_once $phpmailerPath . 'Exception.php';
        require_once $phpmailerPath . 'PHPMailer.php';
        require_once $phpmailerPath . 'SMTP.php';
        
        // Log success
        error_log("PHPMailer files loaded successfully from: " . $phpmailerPath);
    } else {
        // Log error
        error_log("PHPMailer files not found at: " . $phpmailerPath);
        
        // Try alternative location
        $altPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
        if (file_exists($altPath . 'Exception.php')) {
            require_once $altPath . 'Exception.php';
            require_once $altPath . 'PHPMailer.php';
            require_once $altPath . 'SMTP.php';
            error_log("PHPMailer files loaded from alternative path: " . $altPath);
        } else {
            error_log("PHPMailer not found in alternative path: " . $altPath);
        }
    }
}

// Function to generate a verification code
if (!function_exists('generateVerificationCode')) {
    function generateVerificationCode() {
        // Generate a 6-digit verification code
        return rand(100000, 999999);
    }
}

// Function to send verification email
if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail($email, $firstName, $code) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // Enable verbose debug output (comment out in production)
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $debugOutput = "";
            
            // Custom debug output handler to capture output
            $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                $debugOutput .= "Debug level $level: $str\n";
                error_log("PHPMailer: $str");
            };

            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mrblackmaster0123@gmail.com';  // YOUR GMAIL
            $mail->Password   = 'bjmzksexswyukhwe';  // APP PASSWORD (not regular password)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Set timeout values
            $mail->Timeout = 60; // seconds
            $mail->SMTPKeepAlive = true; // maintain connection between multiple messages

            // Recipients
            $mail->setFrom('mrblackmaster0123@gmail.com', 'Pure Linen');
            $mail->addAddress($email, $firstName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Pure Linen - Email Verification';
            
            $message = "
            <html>
            <head>
                <title>Verify Your Email</title>
            </head>
            <body>
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0;'>
                    <div style='background-color: #1B365D; padding: 15px; text-align: center;'>
                        <h2 style='color: white; margin: 0;'>Pure Linen</h2>
                    </div>
                    <div style='padding: 20px;'>
                        <h3>Hello $firstName,</h3>
                        <p>Thank you for registering with Pure Linen. To complete your registration, please verify your email address by entering the verification code below:</p>
                        <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; font-weight: bold;'>
                            $code
                        </div>
                        <p>This code will expire in 30 minutes.</p>
                        <p>If you did not request this verification, please ignore this email.</p>
                        <p>Best regards,<br>Pure Linen Team</p>
                    </div>
                    <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px;'>
                        <p>&copy; 2025 Pure Linen. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $message;
            $mail->AltBody = "Hello $firstName, Your verification code is: $code";
            
            // Debug: Save email to a file for testing on localhost
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $debugFile = fopen("email_debug.html", "w");
                fwrite($debugFile, $message);
                fclose($debugFile);
                
                // Also save the verification code to a log file
                $logFile = fopen("verification_codes.log", "a");
                fwrite($logFile, date('[Y-m-d H:i:s] ') . "Email: $email, Code: $code\n");
                fclose($logFile);
            }
            
            // Send email
            $result = $mail->send();
            
            // Log success
            error_log("Verification email sent successfully to $email");
            error_log("SMTP Debug Output: \n" . $debugOutput);
            
            return true;
        } catch (Exception $e) {
            // Log error
            error_log("Failed to send verification email to $email: " . $mail->ErrorInfo);
            error_log("SMTP Debug Output: \n" . $debugOutput);
            
            // For development, save the error message
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $errorFile = fopen("email_error.log", "a");
                fwrite($errorFile, date('[Y-m-d H:i:s] ') . "Error sending to $email: " . $mail->ErrorInfo . "\n");
                fwrite($errorFile, "Debug output: \n" . $debugOutput . "\n");
                fclose($errorFile);
            }
            
            return false;
        }
    }
}

// Function to send password reset email
if (!function_exists('sendPasswordResetEmail')) {
    function sendPasswordResetEmail($email, $firstName, $resetToken) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // Enable verbose debug output (comment out in production)
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $debugOutput = "";
            
            // Custom debug output handler to capture output
            $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                $debugOutput .= "Debug level $level: $str\n";
                error_log("PHPMailer: $str");
            };

            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mrblackmaster0123@gmail.com';
            $mail->Password   = 'bjmzksexswyukhwe';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Set timeout values
            $mail->Timeout = 60; // seconds
            $mail->SMTPKeepAlive = true;

            // Recipients
            $mail->setFrom('mrblackmaster0123@gmail.com', 'Pure Linen');
            $mail->addAddress($email, $firstName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Pure Linen - Password Reset Request';
            
            // Create reset link
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $resetToken . "&email=" . urlencode($email);
            
            $message = "
            <html>
            <head>
                <title>Reset Your Password</title>
            </head>
            <body>
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0;'>
                    <div style='background-color: #1B365D; padding: 15px; text-align: center;'>
                        <h2 style='color: white; margin: 0;'>Pure Linen</h2>
                    </div>
                    <div style='padding: 20px;'>
                        <h3>Hello $firstName,</h3>
                        <p>We received a request to reset your password. If you didn't make this request, you can ignore this email.</p>
                        <p>To reset your password, click the button below:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$resetLink' style='background-color: #1B365D; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Reset Password</a>
                        </div>
                        <p>Or copy and paste the following link in your browser:</p>
                        <p style='word-break: break-all;'>$resetLink</p>
                        <p>This link will expire in 30 minutes.</p>
                        <p>Best regards,<br>Pure Linen Team</p>
                    </div>
                    <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px;'>
                        <p>&copy; 2025 Pure Linen. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $message;
            $mail->AltBody = "Hello $firstName, Reset your password at: $resetLink";
            
            // Debug: Save email to a file for testing
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $debugFile = fopen("reset_email_debug.html", "w");
                fwrite($debugFile, $message);
                fclose($debugFile);
            }
            
            // Send email
            $mail->send();
            error_log("Password reset email sent successfully to $email");
            
            return true;
        } catch (Exception $e) {
            // Log error
            error_log("Failed to send password reset email to $email: " . $mail->ErrorInfo);
            error_log("SMTP Debug Output: \n" . $debugOutput);
            
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $errorFile = fopen("email_error.log", "a");
                fwrite($errorFile, date('[Y-m-d H:i:s] ') . "Error sending reset email to $email: " . $mail->ErrorInfo . "\n");
                fwrite($errorFile, "Debug output: \n" . $debugOutput . "\n");
                fclose($errorFile);
            }
            
            return false;
        }
    }
}
?>