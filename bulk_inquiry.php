<?php
session_start();
require_once 'config.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert into bulk messages table
        $stmt = $conn->prepare("INSERT INTO bulk_messages (name, email, phone, company_name, industry, country, city, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $email, $phone, $company_name, $industry, $country, $city, $message);
        
        if ($stmt->execute()) {
            $success_message = "Your bulk order inquiry has been submitted successfully. Our team will contact you shortly to discuss your requirements.";
        } else {
            $error_message = "Failed to submit inquiry. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Inquiry - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <style>
        .bulk-form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .bulk-info {
            background-color: #1B365D;
            color: white;
            padding: 30px;
            border-radius: 8px;
        }
        
        .bulk-info h4 {
            color: white;
            margin-bottom: 20px;
        }
        
        .bulk-info ul {
            padding-left: 20px;
        }
        
        .bulk-info li {
            margin-bottom: 10px;
        }
        
        .form-control {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .btn-submit {
            background-color: #1B365D;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            width: 100%;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .btn-submit:hover {
            background-color: #152c4d;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 pt-5">
        <h2 class="text-center mb-5">Bulk Order Inquiry</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-7">
                <div class="bulk-form">
                    <h4 class="mb-4">Request a Bulk Order Quote</h4>
                    <form action="bulk_inquiry.php" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter your phone number">
                        </div>
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Enter your company name">
                        </div>
                        <div class="mb-3">
                            <label for="industry" class="form-label">Industry</label>
                            <select class="form-select form-control" id="industry" name="industry">
                                <option value="">Select your industry</option>
                                <option value="Apparel & Fashion">Apparel & Fashion</option>
                                <option value="Home Textiles">Home Textiles</option>
                                <option value="Hospitality">Hospitality</option>
                                <option value="Interior Design">Interior Design</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Retail">Retail</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" placeholder="Enter your country">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" placeholder="Enter your city">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Order Details *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required 
                                placeholder="Please provide details about your order requirements, including:
                                    - Type of fabric needed
                                    - Quantity (in meters or units)
                                    - Specific colors or finishes
                                    - Delivery timeline
                                    - Any other special requirements"></textarea>
                        </div>
                        <button type="submit" class="btn btn-submit">Submit Inquiry</button>
                    </form>
                </div>
            </div>
            <div class="col-md-5">
                <div class="bulk-info">
                    <h4>Why Order in Bulk?</h4>
                    <ul>
                        <li>Significant cost savings on premium fabrics</li>
                        <li>Customization options available for specific needs</li>
                        <li>Priority processing and dedicated support</li>
                        <li>Flexible payment terms for qualified businesses</li>
                        <li>International shipping available</li>
                    </ul>
                    
                    <h4 class="mt-5">Bulk Order Benefits</h4>
                    <p>Our bulk orders come with special discounts:</p>
                    <ul>
                        <li>50+ meters: 10% discount</li>
                        <li>100+ meters: 15% discount</li>
                        <li>500+ meters: 25% discount</li>
                        <li>1000+ meters: 30-50% discount (custom quote)</li>
                    </ul>
                    
                    <h4 class="mt-5">Contact Us Directly</h4>
                    <p><i class="fas fa-phone me-2"></i> +94 777 123456</p>
                    <p><i class="fas fa-envelope me-2"></i> bulk@topriz.com</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>