<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $to_email = $_POST['to_email'];
    $subject = $_POST['subject'];
    $message_text = $_POST['message_text'];
    $message_id = $_POST['message_id'];
    
    // Set email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Pure Linen <info@purelinen.com>" . "\r\n";
    $headers .= "Reply-To: info@purelinen.com" . "\r\n";
    
    // Format message as HTML
    $html_message = nl2br($message_text);
    
    // Try to send the email
    if (mail($to_email, $subject, $html_message, $headers)) {
        $success_message = "Reply sent successfully to $to_email";
        
        // Log this reply in the database
        $reply_table = isset($_POST['message_type']) && $_POST['message_type'] === 'bulk' ? 'bulk_message_replies' : 'message_replies';
        
        // Create table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS $reply_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id INT NOT NULL,
            reply_subject VARCHAR(255) NOT NULL,
            reply_text TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $conn->prepare("INSERT INTO $reply_table (message_id, reply_subject, reply_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $message_id, $subject, $message_text);
        $stmt->execute();
    } else {
        $error_message = "Failed to send email. Please check your server's mail configuration.";
    }
}

// Handle message deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $message_id = $_GET['delete'];
    $table = isset($_GET['type']) && $_GET['type'] === 'bulk' ? 'bulk_messages' : 'messages';
    
    $delete_stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $delete_stmt->bind_param("i", $message_id);
    
    if ($delete_stmt->execute()) {
        $success_message = "Message deleted successfully!";
    } else {
        $error_message = "Failed to delete message.";
    }
}

// Get message type filter
$message_type = isset($_GET['type']) ? $_GET['type'] : 'bulk';

// Get messages based on type
if ($message_type === 'bulk') {
    $stmt = $conn->prepare("SELECT * FROM bulk_messages ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM messages ORDER BY created_at DESC");
}

$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

// Get admin name for the header
$admin_name = $_SESSION['firstName'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
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
        
        .page-title-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
                        <a href="admin_messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
                    </li>
                    <li>
                        <a href="admin_admins.php"><i class="fas fa-user-shield"></i> Admins</a>
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
                <div class="page-title-box">
                    <h1 class="h3 mb-0">Message Management</h1>
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Message type filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="btn-group">
                            <a href="admin_messages.php?type=bulk" class="btn <?php echo $message_type === 'bulk' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-boxes me-2"></i>Bulk Orders
                            </a>
                            <a href="admin_messages.php?type=regular" class="btn <?php echo $message_type === 'regular' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="fas fa-envelope me-2"></i>Contact Messages
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-<?php echo $message_type === 'bulk' ? 'boxes' : 'envelope'; ?> me-2"></i>
                            <?php echo $message_type === 'bulk' ? 'Bulk Order' : 'Contact'; ?> Messages
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($messages)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Company</th>
                                            <th>Phone</th>
                                            <th>Country/City</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                            <tr>
                                                <td><?php echo $message['id']; ?></td>
                                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                <td><?php echo !empty($message['company_name']) ? htmlspecialchars($message['company_name']) : '-'; ?></td>
                                                <td><?php echo !empty($message['phone']) ? htmlspecialchars($message['phone']) : '-'; ?></td>
                                                <td>
                                                    <?php
                                                        $location = [];
                                                        if (!empty($message['country'])) $location[] = htmlspecialchars($message['country']);
                                                        if (!empty($message['city'])) $location[] = htmlspecialchars($message['city']);
                                                        echo !empty($location) ? implode(', ', $location) : '-';
                                                    ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal<?php echo $message['id']; ?>">
                                                            <i class="fas fa-reply"></i>
                                                        </button>
                                                        <a href="admin_messages.php?type=<?php echo $message_type; ?>&delete=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this message?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                    
                                                    <!-- Message Modal -->
                                                    <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Message Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($message['name']); ?></p>
                                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($message['email']); ?></p>
                                                                            <p><strong>Phone:</strong> <?php echo !empty($message['phone']) ? htmlspecialchars($message['phone']) : '-'; ?></p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <p><strong>Company:</strong> <?php echo !empty($message['company_name']) ? htmlspecialchars($message['company_name']) : '-'; ?></p>
                                                                            <p><strong>Industry:</strong> <?php echo !empty($message['industry']) ? htmlspecialchars($message['industry']) : '-'; ?></p>
                                                                            <p><strong>Location:</strong> 
                                                                                <?php
                                                                                    $location = [];
                                                                                    if (!empty($message['country'])) $location[] = htmlspecialchars($message['country']);
                                                                                    if (!empty($message['city'])) $location[] = htmlspecialchars($message['city']);
                                                                                    echo !empty($location) ? implode(', ', $location) : '-';
                                                                                ?>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <p><strong>Message:</strong></p>
                                                                        <div class="p-3 bg-light rounded">
                                                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                                                        </div>
                                                                    </div>
                                                                    <p class="text-muted"><small>Sent on: <?php echo date('F d, Y h:i A', strtotime($message['created_at'])); ?></small></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal<?php echo $message['id']; ?>" 
                                                                            data-bs-dismiss="modal">
                                                                        <i class="fas fa-reply me-2"></i>Reply
                                                                    </button>
                                                                    <a href="admin_messages.php?type=<?php echo $message_type; ?>&delete=<?php echo $message['id']; ?>" 
                                                                       class="btn btn-danger" 
                                                                       onclick="return confirm('Are you sure you want to delete this message?')">
                                                                        <i class="fas fa-trash me-2"></i>Delete
                                                                    </a>
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Reply Modal -->
                                                    <div class="modal fade" id="replyModal<?php echo $message['id']; ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reply to <?php echo htmlspecialchars($message['name']); ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="admin_messages.php?type=<?php echo $message_type; ?>" method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                                        <input type="hidden" name="to_email" value="<?php echo htmlspecialchars($message['email']); ?>">
                                                                        <input type="hidden" name="message_type" value="<?php echo $message_type; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="emailTo<?php echo $message['id']; ?>" class="form-label">To:</label>
                                                                            <input type="text" class="form-control" id="emailTo<?php echo $message['id']; ?>" 
                                                                                value="<?php echo htmlspecialchars($message['name']); ?> <<?php echo htmlspecialchars($message['email']); ?>>" readonly>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="subject<?php echo $message['id']; ?>" class="form-label">Subject:</label>
                                                                            <input type="text" class="form-control" id="subject<?php echo $message['id']; ?>" name="subject" 
                                                                                value="RE: Your inquiry at Pure Linen" required>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="message_text<?php echo $message['id']; ?>" class="form-label">Message:</label>
                                                                            <textarea class="form-control" id="message_text<?php echo $message['id']; ?>" name="message_text" 
                                                                                rows="10" required>Dear <?php echo htmlspecialchars($message['name']); ?>,

Thank you for contacting Pure Linen. Regarding your inquiry:

[Your response here]

Best regards,
Pure Linen Team
                                                                            </textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="send_reply" class="btn btn-primary">
                                                                            <i class="fas fa-paper-plane me-2"></i>Send Reply
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No <?php echo $message_type === 'bulk' ? 'bulk order' : 'contact'; ?> messages found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>