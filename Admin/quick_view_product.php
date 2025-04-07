<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_name = $_SESSION['firstName'] ?? 'Admin';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No product ID provided";
    exit();
}

$product_id = $_GET['id'];

// Get product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("s", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Product not found";
    exit();
}

$product = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Edit - <?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .product-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            overflow: hidden;
        }
        
        .product-image {
            text-align: center;
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .product-image img {
            max-height: 250px;
            max-width: 100%;
            object-fit: contain;
        }
        
        .product-details {
            padding: 20px;
        }
        
        .property-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .property-name {
            font-weight: 600;
            min-width: 150px;
        }
        
        .quick-edit-btn {
            cursor: pointer;
            color: #1B365D;
            margin-left: 10px;
            opacity: 0.5;
            transition: opacity 0.3s;
        }
        
        .quick-edit-btn:hover {
            opacity: 1;
        }
        
        .property-value.editing {
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .edit-input {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .edit-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="toast-container"></div>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Quick Edit: <?php echo htmlspecialchars($product['name']); ?></h2>
                    <a href="product_management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Products
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-5">
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image fa-3x text-muted"></i>
                                <p class="mt-2 text-muted">No image available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-details">
                        <h4>Product Details</h4>
                        
                        <div class="property-row">
                            <div class="property-name">ID:</div>
                            <div class="property-value"><?php echo htmlspecialchars($product['id']); ?></div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Name:</div>
                            <div class="property-value" id="name-value"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="quick-edit-btn" data-field="name" data-original="<?php echo htmlspecialchars($product['name']); ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Color:</div>
                            <div class="property-value" id="color-value"><?php echo htmlspecialchars($product['color']); ?></div>
                            <div class="quick-edit-btn" data-field="color" data-original="<?php echo htmlspecialchars($product['color']); ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Price:</div>
                            <div class="property-value" id="price-value">LKR <?php echo number_format($product['price'], 2); ?></div>
                            <div class="quick-edit-btn" data-field="price" data-original="<?php echo $product['price']; ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Half Price:</div>
                            <div class="property-value" id="half_price-value">LKR <?php echo number_format($product['half_price'], 2); ?></div>
                            <div class="quick-edit-btn" data-field="half_price" data-original="<?php echo $product['half_price']; ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Type:</div>
                            <div class="property-value" id="type-value"><?php echo htmlspecialchars($product['type']); ?></div>
                            <div class="quick-edit-btn" data-field="type" data-original="<?php echo htmlspecialchars($product['type']); ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Properties:</div>
                            <div class="property-value" id="properties-value"><?php echo htmlspecialchars($product['properties']); ?></div>
                            <div class="quick-edit-btn" data-field="properties" data-original="<?php echo htmlspecialchars($product['properties']); ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                        
                        <div class="property-row">
                            <div class="property-name">Composition:</div>
                            <div class="property-value" id="composition-value"><?php echo htmlspecialchars($product['composition']); ?></div>
                            <div class="quick-edit-btn" data-field="composition" data-original="<?php echo htmlspecialchars($product['composition']); ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="product-card">
                    <div class="product-details">
                        <h4>Description</h4>
                        <div class="property-row" style="border-bottom: none;">
                            <div class="property-value" id="description-value"><?php echo htmlspecialchars($product['description']); ?></div>
                            <div class="quick-edit-btn" data-field="description" data-original="<?php echo htmlspecialchars($product['description']); ?>">
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="d-flex justify-content-between">
                        <a href="edit_product.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Full Edit
                        </a>
                        <a href="../sproduct.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-info" target="_blank">
                            <i class="fas fa-eye me-2"></i>View on Website
                        </a>
                        <a href="delete_product.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                            <i class="fas fa-trash-alt me-2"></i>Delete Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quick edit functionality
            document.querySelectorAll('.quick-edit-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const field = this.dataset.field;
                    const originalValue = this.dataset.original;
                    const productId = '<?php echo $product_id; ?>';
                    const valueContainer = document.getElementById(`${field}-value`);
                    
                    // Save current value display in a variable
                    const currentDisplay = valueContainer.innerHTML;
                    
                    // Add editing class to property value
                    valueContainer.classList.add('editing');
                    
                    // Create appropriate input based on field type
                    let input;
                    if (field === 'price' || field === 'half_price') {
                        input = document.createElement('input');
                        input.type = 'number';
                        input.step = '0.01';
                        input.value = originalValue;
                        input.className = 'edit-input';
                    } else if (field === 'type') {
                        input = document.createElement('select');
                        input.className = 'edit-input';
                        
                        const options = [
                            'pure linen',
                            'cotton linen',
                            'blend linen',
                            'printed linen',
                            'dyed linen'
                        ];
                        
                        options.forEach(option => {
                            const optElement = document.createElement('option');
                            optElement.value = option;
                            optElement.textContent = option.charAt(0).toUpperCase() + option.slice(1);
                            if (option === originalValue) {
                                optElement.selected = true;
                            }
                            input.appendChild(optElement);
                        });
                    } else if (field === 'description') {
                        input = document.createElement('textarea');
                        input.rows = 5;
                        input.value = originalValue;
                        input.className = 'edit-input';
                    } else {
                        input = document.createElement('input');
                        input.type = 'text';
                        input.value = originalValue;
                        input.className = 'edit-input';
                    }
                    
                    // Create action buttons
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'edit-actions';
                    
                    const saveBtn = document.createElement('button');
                    saveBtn.className = 'btn btn-sm btn-success';
                    saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
                    
                    const cancelBtn = document.createElement('button');
                    cancelBtn.className = 'btn btn-sm btn-secondary';
                    cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
                    
                    actionsDiv.appendChild(saveBtn);
                    actionsDiv.appendChild(cancelBtn);
                    
                    // Clear the value container and add the edit interface
                    valueContainer.innerHTML = '';
                    valueContainer.appendChild(input);
                    valueContainer.appendChild(actionsDiv);
                    
                    // Focus on the input
                    input.focus();
                    
                    // Hide the edit button while editing
                    this.style.display = 'none';
                    
                    // Cancel button event
                    cancelBtn.addEventListener('click', function() {
                        valueContainer.innerHTML = currentDisplay;
                        valueContainer.classList.remove('editing');
                        button.style.display = 'block';
                    });
                    
                    // Save button event
                    saveBtn.addEventListener('click', function() {
                        const newValue = input.value.trim();
                        
                        // Validate input
                        if (!newValue) {
                            showToast('Please enter a value', 'error');
                            return;
                        }
                        
                        // Show loading state
                        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                        saveBtn.disabled = true;
                        
                        // Send data to server
                        fetch('quick_edit_product.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `product_id=${encodeURIComponent(productId)}&field=${encodeURIComponent(field)}&value=${encodeURIComponent(newValue)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update the button's original value
                                button.dataset.original = newValue;
                                
                                // Format display value based on field type
                                let displayValue = newValue;
                                if (field === 'price' || field === 'half_price') {
                                    displayValue = 'LKR ' + parseFloat(newValue).toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                                
                                // Restore the value container to normal display
                                valueContainer.innerHTML = displayValue;
                                valueContainer.classList.remove('editing');
                                button.style.display = 'block';
                                
                                // Show success message
                                showToast(data.message, 'success');
                            } else {
                                showToast(data.message, 'error');
                                saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
                                saveBtn.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('An error occurred while processing your request', 'error');
                            saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
                            saveBtn.disabled = false;
                        });
                    });
                    
                    // Handle Enter key press
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter' && field !== 'description') {
                            saveBtn.click();
                        }
                    });
                    
                    // Handle Escape key press
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            cancelBtn.click();
                        }
                    });
                });
            });
            
            // Toast notification function
            function showToast(message, type = 'success') {
                const toastContainer = document.querySelector('.toast-container');
                
                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                
                const toastBody = document.createElement('div');
                toastBody.className = 'd-flex';
                
                const messageDiv = document.createElement('div');
                messageDiv.className = 'toast-body';
                messageDiv.textContent = message;
                
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close btn-close-white me-2 m-auto';
                closeButton.setAttribute('data-bs-dismiss', 'toast');
                closeButton.setAttribute('aria-label', 'Close');
                
                toastBody.appendChild(messageDiv);
                toastBody.appendChild(closeButton);
                toastEl.appendChild(toastBody);
                toastContainer.appendChild(toastEl);
                
                const toast = new bootstrap.Toast(toastEl, {
                    delay: 3000,
                    autohide: true
                });
                
                toast.show();
                
                // Remove toast after it's hidden
                toastEl.addEventListener('hidden.bs.toast', function() {
                    toastContainer.removeChild(toastEl);
                });
            }
        });
    </script>
</body>
</html>