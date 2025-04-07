<?php
session_start();
require_once 'config.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert into messages table
        $stmt = $conn->prepare("INSERT INTO messages (name, email, phone, country, city, message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $country, $city, $message);
        
        if ($stmt->execute()) {
            $success_message = "Your message has been sent successfully. We'll get back to you soon!";
        } else {
            $error_message = "Failed to send message. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .contact-form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .contact-info {
            background-color: #1B365D;
            color: white;
            padding: 30px;
            border-radius: 8px;
        }
        
        .contact-info h4 {
            color: white;
            margin-bottom: 20px;
        }
        
        .contact-info p {
            margin-bottom: 15px;
        }
        
        .contact-info i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 pt-5">
        <h2 class="text-center mb-5">Contact Us</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-7">
                <div class="contact-form">
                    <h4 class="mb-4">Send us a message</h4>
                    <form action="contact.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Message</button>
                    </form>
                </div>
            </div>
            <div class="col-md-5">
                <div class="contact-info">
                    <h4>Contact Information</h4>
                    <p><i class="fas fa-map-marker-alt"></i> 1/2 Crow Island, Sea Breeze Garden, Colombo, Sri Lanka</p>
                    <p><i class="fas fa-phone"></i> +94 777 123456</p>
                    <p><i class="fas fa-phone"></i> +94 777 123456</p>
                    <p><i class="fas fa-envelope"></i> info@topriz.com</p>
                    
                    <h4 class="mt-5">Business Hours</h4>
                    <p><i class="far fa-clock"></i> Monday - Friday: 9:00 AM - 5:00 PM</p>
                    <p><i class="far fa-clock"></i> Saturday: 9:00 AM - 1:00 PM</p>
                    <p><i class="far fa-clock"></i> Sunday: Closed</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>