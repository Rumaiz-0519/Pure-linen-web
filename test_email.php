<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer files exist
$phpmailerPath = 'PHPMailer/src/';
$files = [
    'Exception.php' => false,
    'PHPMailer.php' => false,
    'SMTP.php' => false
];

echo "<h1>PHPMailer SMTP Test</h1>";

// Check for PHPMailer files
echo "<h2>PHPMailer Files Check</h2>";
echo "<ul>";
foreach ($files as $file => $exists) {
    $path = $phpmailerPath . $file;
    if (file_exists($path)) {
        $files[$file] = true;
        echo "<li style='color:green'>✓ Found: $path</li>";
        require_once $path;
    } else {
        echo "<li style='color:red'>✗ Missing: $path</li>";
    }
}
echo "</ul>";

// If any file is missing, show download instructions
if (in_array(false, $files)) {
    echo "<div style='padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 15px 0;'>
        <h3>PHPMailer is missing or incomplete</h3>
        <p>Please download PHPMailer from <a href='https://github.com/PHPMailer/PHPMailer/releases' target='_blank'>GitHub</a> and extract it to your project directory.</p>
        <p>The directory structure should be:</p>
        <pre>
your_project/
└── PHPMailer/
    └── src/
        ├── Exception.php
        ├── PHPMailer.php
        └── SMTP.php
        </pre>
    </div>";
} else {
    echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 15px 0;'>
        <h3>PHPMailer files found!</h3>
        <p>All required PHPMailer files have been located.</p>
    </div>";
}

// Test form
echo "<h2>Send Test Email</h2>";
echo "<form method='post' action=''>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_email' style='display:block; margin-bottom:5px;'>Recipient Email:</label>";
echo "<input type='email' name='test_email' id='test_email' value='" . (isset($_POST['test_email']) ? htmlspecialchars($_POST['test_email']) : '') . "' style='padding:8px; width:100%; max-width:400px;' required>";
echo "</div>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='smtp_user' style='display:block; margin-bottom:5px;'>SMTP Username (Gmail):</label>";
echo "<input type='email' name='smtp_user' id='smtp_user' value='" . (isset($_POST['smtp_user']) ? htmlspecialchars($_POST['smtp_user']) : 'mrblackmaster0123@gmail.com') . "' style='padding:8px; width:100%; max-width:400px;' required>";
echo "</div>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='smtp_pass' style='display:block; margin-bottom:5px;'>SMTP Password (App Password):</label>";
echo "<input type='password' name='smtp_pass' id='smtp_pass' value='" . (isset($_POST['smtp_pass']) ? htmlspecialchars($_POST['smtp_pass']) : 'bjmzksexswyukhwe') . "' style='padding:8px; width:100%; max-width:400px;' required>";
echo "<p style='font-size:12px; color:#6c757d; margin-top:5px;'>Note: For Gmail, you need to use an App Password, not your regular password. <a href='https://support.google.com/accounts/answer/185833' target='_blank'>Learn how to create an App Password</a>.</p>";
echo "</div>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='debug_level' style='display:block; margin-bottom:5px;'>Debug Level:</label>";
echo "<select name='debug_level' id='debug_level' style='padding:8px; width:100%; max-width:400px;'>";
echo "<option value='0'" . (isset($_POST['debug_level']) && $_POST['debug_level'] == '0' ? ' selected' : '') . ">Off (0)</option>";
echo "<option value='1'" . (isset($_POST['debug_level']) && $_POST['debug_level'] == '1' ? ' selected' : '') . ">Client (1)</option>";
echo "<option value='2'" . (isset($_POST['debug_level']) && $_POST['debug_level'] == '2' ? ' selected' : ' selected') . ">Server and Client (2)</option>";
echo "<option value='3'" . (isset($_POST['debug_level']) && $_POST['debug_level'] == '3' ? ' selected' : '') . ">Connection (3)</option>";
echo "<option value='4'" . (isset($_POST['debug_level']) && $_POST['debug_level'] == '4' ? ' selected' : '') . ">Low Level (4)</option>";
echo "</select>";
echo "</div>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label style='display:block; margin-bottom:5px;'>Email Security Options:</label>";
echo "<label style='margin-right:15px;'><input type='radio' name='security' value='tls' " . (!isset($_POST['security']) || $_POST['security'] == 'tls' ? 'checked' : '') . "> TLS (Port 587)</label>";
echo "<label><input type='radio' name='security' value='ssl' " . (isset($_POST['security']) && $_POST['security'] == 'ssl' ? 'checked' : '') . "> SSL (Port 465)</label>";
echo "</div>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label><input type='checkbox' name='allow_insecure' value='1' " . (isset($_POST['allow_insecure']) ? 'checked' : '') . "> Allow insecure SSL connections (only use for testing)</label>";
echo "</div>";

echo "<button type='submit' name='send_test' style='padding:10px 20px; background-color:#1B365D; color:white; border:none; border-radius:5px; cursor:pointer;'>Send Test Email</button>";
echo "</form>";

// Process the test email
if (isset($_POST['send_test']) && !in_array(false, $files)) {
    echo "<h2>Test Results</h2>";
    echo "<div style='background-color:#f8f9fa; padding:15px; border-radius:5px; margin:15px 0;'>";
    echo "<pre style='margin:0; white-space:pre-wrap; word-break:break-all;'>";
    
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Set debug level
        $mail->SMTPDebug = (int)$_POST['debug_level'];
        $mail->Debugoutput = function($str, $level) {
            echo htmlspecialchars($str);
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_POST['smtp_user'];
        $mail->Password = $_POST['smtp_pass'];
        
        // Set the encryption type
        if ($_POST['security'] == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
        }
        
        // Allow insecure connections if requested
        if (isset($_POST['allow_insecure'])) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
        
        // Recipients
        $mail->setFrom($_POST['smtp_user'], 'SMTP Test');
        $mail->addAddress($_POST['test_email']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'PHPMailer SMTP Test';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0;">
                <div style="background-color: #1B365D; padding: 15px; text-align: center;">
                    <h2 style="color: white; margin: 0;">SMTP Test</h2>
                </div>
                <div style="padding: 20px;">
                    <h3>Success!</h3>
                    <p>This email confirms that your SMTP settings are working correctly.</p>
                    <p>Details:</p>
                    <ul>
                        <li>Server: smtp.gmail.com</li>
                        <li>Port: ' . ($mail->SMTPSecure == PHPMailer::ENCRYPTION_STARTTLS ? '587 (TLS)' : '465 (SSL)') . '</li>
                        <li>Time: ' . date('Y-m-d H:i:s') . '</li>
                    </ul>
                </div>
                <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px;">
                    <p>This is an automated test email. Please do not reply.</p>
                </div>
            </div>
        ';
        $mail->AltBody = 'This email confirms that your SMTP settings are working correctly.';
        
        // Send the email
        $mail->send();
        
        echo "\n\n<strong style='color:green;'>Message sent successfully!</strong>";
    } catch (Exception $e) {
        echo "\n\n<strong style='color:red;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</strong>";
    }
    
    echo "</pre>";
    echo "</div>";
    
    echo "<h3>Troubleshooting Tips</h3>";
    echo "<ul>";
    echo "<li>Make sure you're using an <a href='https://support.google.com/accounts/answer/185833' target='_blank'>App Password</a> for Gmail, not your regular password.</li>";
    echo "<li>Check if 'Less secure app access' is enabled in your Google account (although this is being deprecated).</li>";
    echo "<li>Try both TLS (port 587) and SSL (port 465) settings to see which works with your server.</li>";
    echo "<li>If you're on localhost, your ISP might be blocking outgoing SMTP traffic on standard ports.</li>";
    echo "<li>Some web hosts restrict outgoing email to their own SMTP servers. Check with your hosting provider.</li>";
    echo "</ul>";
}

// Check PHP mail() function (alternative)
echo "<h2>Test PHP mail() Function (Alternative)</h2>";
echo "<p>If PHPMailer doesn't work, you can try PHP's built-in mail() function:</p>";
echo "<form method='post' action=''>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='php_mail_to' style='display:block; margin-bottom:5px;'>Recipient Email:</label>";
echo "<input type='email' name='php_mail_to' id='php_mail_to' value='" . (isset($_POST['php_mail_to']) ? htmlspecialchars($_POST['php_mail_to']) : '') . "' style='padding:8px; width:100%; max-width:400px;' required>";
echo "</div>";
echo "<button type='submit' name='send_php_mail' style='padding:10px 20px; background-color:#6c757d; color:white; border:none; border-radius:5px; cursor:pointer;'>Send Using mail()</button>";
echo "</form>";

if (isset($_POST['send_php_mail'])) {
    echo "<div style='background-color:#f8f9fa; padding:15px; border-radius:5px; margin:15px 0;'>";
    $to = $_POST['php_mail_to'];
    $subject = 'PHP mail() Function Test';
    $message = "This is a test email sent using PHP's built-in mail() function.\n\nTime: " . date('Y-m-d H:i:s');
    $headers = 'From: webmaster@' . $_SERVER['SERVER_NAME'] . "\r\n" .
               'Reply-To: webmaster@' . $_SERVER['SERVER_NAME'] . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
               
    $success = mail($to, $subject, $message, $headers);
    
    if ($success) {
        echo "<p style='color:green;'>Mail sent successfully using PHP mail() function!</p>";
    } else {
        echo "<p style='color:red;'>Failed to send mail using PHP mail() function.</p>";
    }
    echo "</div>";
}

// Server information
echo "<h2>Server Information</h2>";
echo "<div style='background-color:#f8f9fa; padding:15px; border-radius:5px; margin:15px 0;'>";
echo "<table style='width:100%; border-collapse:collapse;'>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;'><strong>SMTP Extension</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . (extension_loaded('openssl') ? 'OpenSSL ✓' : 'OpenSSL ✗') . " | " . (extension_loaded('ssl') ? 'SSL ✓' : 'SSL ✗') . "</td></tr>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;'><strong>OpenSSL Version</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Not available') . "</td></tr>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;'><strong>allow_url_fopen</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled') . "</td></tr>";
echo "<tr><td style='padding:5px;'><strong>SMTP Config in php.ini</strong></td><td style='padding:5px;'>" . (ini_get('SMTP') ? ini_get('SMTP') . ':' . ini_get('smtp_port') : 'Not configured') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If the test was successful, update your <code>send_verification.php</code> with the correct settings.</li>";
echo "<li>If tests are failing, try alternatives:";
echo "<ul>";
echo "<li>Use a different SMTP provider like SendGrid, Mailgun, or Amazon SES.</li>";
echo "<li>Check with your hosting provider for their recommended email configuration.</li>";
echo "<li>For localhost development, consider using a service like Mailtrap.io for testing.</li>";
echo "</ul></li>";
echo "<li>For Gmail specifically:";
echo "<ul>";
echo "<li>Make sure you've set up a proper <a href='https://support.google.com/accounts/answer/185833' target='_blank'>App Password</a>.</li>";
echo "<li>Allow access for less secure apps in your Google account settings.</li>";
echo "<li>If neither works, try using an app-specific SMTP service.</li>";
echo "</ul></li>";
echo "</ol>";

echo "<h2>Sample Working Configuration</h2>";
echo "<pre style='background-color:#f8f9fa; padding:15px; border-radius:5px; margin:15px 0; overflow-x:auto;'>";
echo htmlspecialchars('<?php
// PHPMailer configuration that works in most environments
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require \'PHPMailer/src/Exception.php\';
require \'PHPMailer/src/PHPMailer.php\';
require \'PHPMailer/src/SMTP.php\';

function sendEmail($to, $toName, $subject, $htmlBody, $plainBody = \'\') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = \'smtp.gmail.com\';
        $mail->SMTPAuth   = true;
        $mail->Username   = \'your-email@gmail.com\';
        $mail->Password   = \'your-app-password\';  // Use an App Password, not your regular password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Optional for problematic connections
        $mail->SMTPOptions = array(
            \'ssl\' => array(
                \'verify_peer\' => false,
                \'verify_peer_name\' => false,
                \'allow_self_signed\' => true
            )
        );
        
        // Recipients
        $mail->setFrom(\'your-email@gmail.com\', \'Your Name\');
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(\'your-email@gmail.com\', \'Your Name\');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody ?: strip_tags($htmlBody);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: {$mail->ErrorInfo}");
        return false;
    }
}');
echo "</pre>";

echo "<p style='margin-top:20px;'><a href='https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting' target='_blank' style='color:#1B365D;'>View PHPMailer Troubleshooting Guide</a></p>";
?>'><strong>PHP Version</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . phpversion() . "</td></tr>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;'><strong>Server Software</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;'><strong>Server Name</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . $_SERVER['SERVER_NAME'] . "</td></tr>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;'><strong>PHP Mail Enabled</strong></td><td style='padding:5px; border-bottom:1px solid #dee2e6;'>" . (function_exists('mail') ? 'Yes' : 'No') . "</td></tr>";
echo "<tr><td style='padding:5px; border-bottom:1px solid #dee2e6;