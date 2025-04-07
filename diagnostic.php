<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Diagnostic Tool</h1>";

// Check PHP version
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PHP SAPI: " . php_sapi_name() . "</p>";

// Check required extensions
echo "<h2>Required Extensions</h2>";
$requiredExtensions = ['mysqli', 'mbstring', 'openssl', 'PDO', 'pdo_mysql', 'json'];
foreach ($requiredExtensions as $ext) {
    echo "<p>Extension '{$ext}': " . (extension_loaded($ext) ? "<span style='color:green'>Loaded</span>" : "<span style='color:red'>Not Loaded</span>") . "</p>";
}

// Check include path
echo "<h2>Include Path</h2>";
echo "<p>" . get_include_path() . "</p>";

// Check file existence and permissions
echo "<h2>File Checks</h2>";
$files = [
    'connect.php',
    'config.php',
    'register.php',
    'send_verification.php',
    'PHPMailer/src/Exception.php',
    'PHPMailer/src/PHPMailer.php',
    'PHPMailer/src/SMTP.php'
];

foreach ($files as $file) {
    echo "<p>File '{$file}': ";
    if (file_exists($file)) {
        echo "<span style='color:green'>Exists</span>, ";
        echo "Readable: " . (is_readable($file) ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>") . ", ";
        echo "Writable: " . (is_writable($file) ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>");
    } else {
        echo "<span style='color:red'>Does not exist</span>";
    }
    echo "</p>";
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    // First try with config.php
    if (file_exists('config.php')) {
        echo "<p>Testing connection with config.php</p>";
        include_once 'config.php';
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>";
            } else {
                echo "<p style='color:green'>Database connection successful!</p>";
                
                // Check users table structure
                echo "<h3>Users Table Structure:</h3>";
                $result = $conn->query("DESCRIBE users");
                if ($result) {
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        foreach ($row as $key => $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color:red'>Error querying users table: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p style='color:orange'>No connection variable found in config.php</p>";
        }
    }
    
    // Then try with connect.php
    if (file_exists('connect.php')) {
        echo "<p>Testing connection with connect.php</p>";
        include_once 'connect.php';
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>";
            } else {
                echo "<p style='color:green'>Database connection successful!</p>";
            }
        } else {
            echo "<p style='color:orange'>No connection variable found in connect.php</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Exception: " . $e->getMessage() . "</p>";
}

// Check session configuration
echo "<h2>Session Configuration</h2>";
echo "<p>Session Status: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "Sessions are disabled";
        break;
    case PHP_SESSION_NONE:
        echo "Sessions are enabled, but no session has been started";
        break;
    case PHP_SESSION_ACTIVE:
        echo "Session is active";
        break;
}
echo "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

// Display contents of error_log if it exists
echo "<h2>Error Log (last 20 lines)</h2>";
$errorLogFile = ini_get('error_log');
if (!empty($errorLogFile) && file_exists($errorLogFile) && is_readable($errorLogFile)) {
    echo "<p>Error log file: " . $errorLogFile . "</p>";
    $errorLog = file($errorLogFile);
    $errorLog = array_slice($errorLog, -20); // Get last 20 lines
    echo "<pre>";
    foreach ($errorLog as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    // Try to look for error logs in the current directory
    $possibleLogs = ['error_log', 'php_error_log', 'error.log'];
    $logFound = false;
    
    foreach ($possibleLogs as $log) {
        if (file_exists($log) && is_readable($log)) {
            echo "<p>Found log file: " . $log . "</p>";
            $errorLog = file($log);
            $errorLog = array_slice($errorLog, -20); // Get last 20 lines
            echo "<pre>";
            foreach ($errorLog as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
            $logFound = true;
            break;
        }
    }
    
    if (!$logFound) {
        echo "<p>No error log found or log is not readable. Error log path from php.ini: " . (empty($errorLogFile) ? "Not configured" : $errorLogFile) . "</p>";
    }
}

// Function to test if register.php can be parsed
echo "<h2>Testing register.php for syntax errors</h2>";
$output = [];
$return_var = 0;
exec("php -l register.php 2>&1", $output, $return_var);
if ($return_var === 0) {
    echo "<p style='color:green'>No syntax errors detected in register.php</p>";
} else {
    echo "<p style='color:red'>Syntax errors found in register.php:</p>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
}

// Try to identify specific problems in send_verification.php
echo "<h2>Testing send_verification.php for syntax errors</h2>";
$output = [];
$return_var = 0;
exec("php -l send_verification.php 2>&1", $output, $return_var);
if ($return_var === 0) {
    echo "<p style='color:green'>No syntax errors detected in send_verification.php</p>";
} else {
    echo "<p style='color:red'>Syntax errors found in send_verification.php:</p>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
}

// Create a simplified test for function redefinition
echo "<h2>Function Redefinition Test</h2>";
echo "<p>Testing if any function is defined more than once:</p>";

// Test all functions from both files
$testFunctionsCode = '
<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

// Include files one by one
try {
    echo "Including config.php: ";
    include_once("config.php");
    echo "OK\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    echo "Including connect.php: ";
    include_once("connect.php");
    echo "OK\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    echo "Including send_verification.php: ";
    include_once("send_verification.php");
    echo "OK\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    echo "Including register.php: ";
    include_once("register.php");
    echo "OK\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
';

// Write the test code to a temporary file
$testFile = 'function_test.php';
file_put_contents($testFile, $testFunctionsCode);

// Execute the test
$output = [];
$return_var = 0;
exec("php {$testFile} 2>&1", $output, $return_var);
echo "<pre>" . implode("\n", $output) . "</pre>";

// Clean up temporary file
unlink($testFile);

echo "<h2>System Information</h2>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

// Suggested fixes based on common issues
echo "<h2>Possible Solutions</h2>";
echo "<ol>";
echo "<li>Check if PHPMailer library is properly installed. If not, download it from <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a>.</li>";
echo "<li>Make sure both config.php and connect.php aren't creating duplicate database connections with the same variable name.</li>";
echo "<li>Ensure there are no duplicate function definitions between files (especially generateVerificationCode).</li>";
echo "<li>Create a database backup before making changes.</li>";
echo "<li>Replace register.php and send_verification.php with the corrected versions.</li>";
echo "</ol>";

?>