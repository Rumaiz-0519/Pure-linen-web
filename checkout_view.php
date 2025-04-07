<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pure Linen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="./checkout.css">
</head>
<body>
    <!--NAVIGATION-->
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-2 fixed-top">
        <div class="container">
            <img src="img/logo.png" alt="Pure Linen Logo" class="navbar-brand">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Linen Fabric</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="swatch.php">Swatch Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#orders">Orders</a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#search" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php" aria-label="Shopping Cart">
                            <i class="fas fa-cart-plus"></i>
                            <?php if (!empty($cart_items)): ?>
                                <span class="cart-badge"><?php echo count($cart_items); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><p class="dropdown-item mb-0">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="profile.php">Update Profile</a></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a class="nav-link" href="login.php" aria-label="User Account">
                                <i class="fas fa-user"></i>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <h2 class="mb-4">PLACE YOUR ORDERS</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <form action="checkout.php" method="POST" id="checkout-form">
                    <!-- Shipping Information -->
                    <div class="checkout-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="checkout-title mb-0">Shipping Address</h3>
                            <button type="button" class="edit-btn" id="editAddressBtn">EDIT</button>
                        </div>
                        
                        <!-- Address Preview Section - Initially visible -->
                        <div id="addressPreview" class="address-preview">
                            <?php if (!empty($firstName) && !empty($address)): ?>
                                <p class="name"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></p>
                                <p><?php echo htmlspecialchars($email); ?></p>
                                <p><?php echo htmlspecialchars($phone); ?></p>
                                <p><?php echo htmlspecialchars($address); ?></p>
                                <p><?php echo htmlspecialchars($city . ', ' . $postcode); ?></p>
                                <p><?php echo htmlspecialchars($country); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No address information saved. Please click "Edit" to enter your shipping details.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div id="addressFormContainer" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required placeholder="Enter your first name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required placeholder="Enter your last name">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required placeholder="Enter your email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required placeholder="Enter your phone number">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required placeholder="Enter your full address"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required placeholder="Enter your city">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="postcode" class="form-label">Post Code *</label>
                                    <input type="text" class="form-control" id="postcode" name="postcode" value="<?php echo htmlspecialchars($postcode); ?>" required placeholder="Enter your post code">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="country" class="form-label">Country *</label>
                                    <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($country); ?>" required placeholder="Enter your country">
                                </div>
                            </div>
                            <button type="button" class="btn btn-success" id="saveAddressBtn">Save Address</button>
                        </div>
                    </div>
                    
                    <!-- Delivery Options -->
                    <div class="checkout-section mt-4">
                        <h3 class="checkout-title">Delivery Options</h3>
                        
                        <input type="hidden" name="delivery_option" id="deliveryOptionInput" value="<?php echo $delivery_option; ?>">
                        
                        <div class="delivery-option <?php echo ($delivery_option === 'express') ? 'selected' : ''; ?>" data-option="express" onclick="selectDeliveryOption('express')">
                            <div class="d-flex justify-content-between align-items-center p-2">
                                <div>
                                    <div class="shipping-title">Express Shipping</div>
                                    <div class="shipping-desc">Estimated delivery in 1-2 business days</div>
                                </div>
                                <div class="shipping-price">LKR 600</div>
                            </div>
                        </div>
                        
                        <div class="delivery-option <?php echo ($delivery_option === 'standard') ? 'selected' : ''; ?>" data-option="standard" onclick="selectDeliveryOption('standard')">
                            <div class="d-flex justify-content-between align-items-center p-2">
                                <div>
                                    <div class="shipping-title">Standard Shipping</div>
                                    <div class="shipping-desc">Estimated delivery in 5-7 business days</div>
                                </div>
                                <div class="shipping-price">LKR 250</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="checkout-title mb-0">Payment Method</h3>
                            <button type="button" class="edit-btn" id="editPaymentBtn">EDIT</button>
                        </div>
                        
                        <!-- Payment Method Preview Section - Initially visible -->
                        <div id="paymentPreview" class="payment-preview">
                            <?php if ($payment_method === 'card'): ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-credit-card me-2 fa-lg"></i>
                                    <div>
                                        <span class="fw-bold">Card Payment</span>
                                        <?php if (!empty($cardName)): ?>
                                            <p class="text-muted mb-0 small"><?php echo htmlspecialchars($cardName); ?> - <?php echo htmlspecialchars($cardNumber); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0 small">You'll enter your card details to complete payment</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-money-bill-wave me-2 fa-lg"></i>
                                    <div>
                                        <span class="fw-bold">Cash on Delivery</span>
                                        <p class="text-muted mb-0 small">You'll pay when your order is delivered</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div id="paymentMethodsContainer" style="display: none;">
                            <input type="hidden" name="payment_method" id="paymentMethodInput" value="<?php echo $payment_method; ?>">
                            
                            <div class="payment-method <?php echo ($payment_method === 'card') ? 'selected' : ''; ?>" data-method="card" onclick="selectPaymentMethod('card')">
                                <div class="d-flex align-items-center p-2">
                                    <i class="fas fa-credit-card me-2 fa-lg"></i>
                                    <span class="payment-title">Card Payment</span>
                                </div>
                                
                                <div id="cardPaymentForm" class="payment-form" <?php echo ($payment_method === 'card') ? 'style="display: block;"' : ''; ?>>
                                    <div class="mb-3">
                                        <input type="text" class="form-control" id="cardName" name="card_name" value="<?php echo htmlspecialchars($cardName); ?>" placeholder="Name on Card">
                                    </div>
                                    <div class="mb-3">
                                        <input type="text" class="form-control" id="cardNumber" name="card_number" value="<?php echo htmlspecialchars($cardNumber); ?>" placeholder="Card Number">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <input type="text" class="form-control" id="cardExpiry" name="card_expiry" value="<?php echo htmlspecialchars($cardExpiry); ?>" placeholder="MM/YY">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <input type="text" class="form-control" id="cardCvv" name="card_cvv" placeholder="CVV">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method <?php echo ($payment_method === 'cod') ? 'selected' : ''; ?>" data-method="cod" onclick="selectPaymentMethod('cod')">
                                <div class="d-flex align-items-center p-2">
                                    <i class="fas fa-money-bill-wave me-2 fa-lg"></i>
                                    <span class="payment-title">Cash on Delivery</span>
                                </div>
                                
                                <div id="codForm" class="payment-form" <?php echo ($payment_method === 'cod') ? 'style="display: block;"' : ''; ?>>
                                    <div class="alert alert-info">
                                        <p>You will pay for your order when it is delivered to your doorstep.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary mt-3" id="savePaymentBtn">SAVE PAYMENT METHOD</button>
                        </div>
                    </div>
                    
                    <button type="submit" form="checkout-form" class="place-order-btn mb-4">PLACE ORDER</button>
                </form>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary">
                    <h3 class="order-summary-title">Order Summary</h3>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div>
                                <?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['size']; ?>)
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars($item['color']); ?> × <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <div>
                                LKR <?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sub Total</span>
                        <span id="sub-total">LKR <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee</span>
                        <span id="shipping-cost">LKR <?php echo number_format($delivery_cost, 2); ?></span>
                    </div>
                    
                    <div class="order-total">
                        <span>Total</span>
                        <span id="order-total">LKR <?php echo number_format($order_total, 2); ?></span>
                    </div>
                    
                    <button type="submit" form="checkout-form" class="place-order-btn">PLACE ORDER</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    
    <script>
    function selectDeliveryOption(option) {
        // Update hidden input value
        document.getElementById('deliveryOptionInput').value = option;
        
        // Update selected class
        document.querySelector('.delivery-option[data-option="express"]').classList.toggle('selected', option === 'express');
        document.querySelector('.delivery-option[data-option="standard"]').classList.toggle('selected', option === 'standard');
        
        // Update order total
        const shippingCost = option === 'express' ? 600 : 250;
        const subtotalText = document.getElementById('sub-total').textContent.replace('LKR ', '').replace(/,/g, '');
        const subtotal = parseFloat(subtotalText);
        const total = subtotal + shippingCost;
        
        document.getElementById('shipping-cost').textContent = 'LKR ' + shippingCost.toFixed(2);
        document.getElementById('order-total').textContent = 'LKR ' + numberWithCommas(total.toFixed(2));
        
        // Save delivery option via AJAX
        fetch('save_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                delivery_option: option
            }),
        }).catch(error => console.error('Error saving delivery option:', error));
    }
    
    function selectPaymentMethod(method) {
        // Set the hidden input value
        document.getElementById('paymentMethodInput').value = method;
        
        // Update selected class
        document.querySelector('.payment-method[data-method="card"]').classList.toggle('selected', method === 'card');
        document.querySelector('.payment-method[data-method="cod"]').classList.toggle('selected', method === 'cod');
        
        // Show/hide payment forms
        document.getElementById('cardPaymentForm').style.display = method === 'card' ? 'block' : 'none';
        document.getElementById('codForm').style.display = method === 'cod' ? 'block' : 'none';
        
        // Save payment method via AJAX
        let paymentData = {
            method: method
        };
        
        // Add card details if card payment selected
        if (method === 'card') {
            const cardName = document.getElementById('cardName')?.value.trim() || '';
            const cardNumber = document.getElementById('cardNumber')?.value.trim() || '';
            const cardExpiry = document.getElementById('cardExpiry')?.value.trim() || '';
            
            paymentData.card_name = cardName;
            paymentData.card_number = cardNumber;
            paymentData.card_expiry = cardExpiry;
        }
        
        fetch('save_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paymentData),
        }).catch(error => console.error('Error saving payment method:', error));
    }
    
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Address and Payment edit buttons
        const editAddressBtn = document.getElementById('editAddressBtn');
        const editPaymentBtn = document.getElementById('editPaymentBtn');
        const addressFormContainer = document.getElementById('addressFormContainer');
        const addressPreview = document.getElementById('addressPreview');
        const paymentMethodsContainer = document.getElementById('paymentMethodsContainer');
        const paymentPreview = document.getElementById('paymentPreview');
        const saveAddressBtn = document.getElementById('saveAddressBtn');
        const savePaymentBtn = document.getElementById('savePaymentBtn');
        
        // Address edit button
        if (editAddressBtn && addressFormContainer && addressPreview) {
            editAddressBtn.addEventListener('click', function() {
                addressFormContainer.style.display = 'block';
                addressPreview.style.display = 'none';
            });
        }
        
        // Payment edit button
        if (editPaymentBtn && paymentMethodsContainer && paymentPreview) {
            editPaymentBtn.addEventListener('click', function() {
                paymentMethodsContainer.style.display = 'block';
                paymentPreview.style.display = 'none';
            });
        }
        
        // Save address button functionality
        if (saveAddressBtn) {
            saveAddressBtn.addEventListener('click', function() {
                const firstName = document.getElementById('firstName')?.value.trim() || '';
                const lastName = document.getElementById('lastName')?.value.trim() || '';
                const email = document.getElementById('email')?.value.trim() || '';
                const phone = document.getElementById('phone')?.value.trim() || '';
                const address = document.getElementById('address')?.value.trim() || '';
                const city = document.getElementById('city')?.value.trim() || '';
                const postcode = document.getElementById('postcode')?.value.trim() || '';
                const country = document.getElementById('country')?.value.trim() || '';
                
                // Basic validation
                if (!firstName || !lastName || !email || !phone || !address || !city || !postcode || !country) {
                    alert('Please fill in all address fields');
                    return;
                }
                
                // Save to session using AJAX
                fetch('save_address.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        firstName, lastName, email, phone, address, city, postcode, country
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update preview
                        if (addressPreview) {
                            addressPreview.innerHTML = `
                                <p class="name">${firstName} ${lastName}</p>
                                <p>${email}</p>
                                <p>${phone}</p>
                                <p>${address}</p>
                                <p>${city}, ${postcode}</p>
                                <p>${country}</p>
                            `;
                        }
                        
                        // Switch back to preview view
                        if (addressFormContainer && addressPreview) {
                            addressFormContainer.style.display = 'none';
                            addressPreview.style.display = 'block';
                        }
                    } else {
                        alert('Failed to save address: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Still update the UI even if server-side saving fails
                    if (addressPreview) {
                        addressPreview.innerHTML = `
                            <p class="name">${firstName} ${lastName}</p>
                            <p>${email}</p>
                            <p>${phone}</p>
                            <p>${address}</p>
                            <p>${city}, ${postcode}</p>
                            <p>${country}</p>
                        `;
                    }
                    
                    if (addressFormContainer && addressPreview) {
                        addressFormContainer.style.display = 'none';
                        addressPreview.style.display = 'block';
                    }
                });
            });
        }
        
        // Save payment method button functionality
        if (savePaymentBtn && paymentMethodsContainer && paymentPreview) {
            savePaymentBtn.addEventListener('click', function() {
                const selectedMethod = document.getElementById('paymentMethodInput').value;
                
                if (selectedMethod === 'card') {
                    // Validate card details
                    const cardName = document.getElementById('cardName')?.value.trim() || '';
                    const cardNumber = document.getElementById('cardNumber')?.value.trim() || '';
                    const cardExpiry = document.getElementById('cardExpiry')?.value.trim() || '';
                    const cardCvv = document.getElementById('cardCvv')?.value.trim() || '';
                    
                    if (!cardName || !cardNumber || !cardExpiry || !cardCvv) {
                        alert('Please fill in all card details');
                        return;
                    }
                    
                    // Update payment preview
                    if (paymentPreview) {
                        paymentPreview.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-credit-card me-2 fa-lg"></i>
                                <div>
                                    <span class="fw-bold">Card Payment</span>
                                    <p class="text-muted mb-0 small">${cardName} - ${cardNumber}</p>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    // Update payment preview for COD
                    if (paymentPreview) {
                        paymentPreview.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-money-bill-wave me-2 fa-lg"></i>
                                <div>
                                    <span class="fw-bold">Cash on Delivery</span>
                                    <p class="text-muted mb-0 small">You'll pay when your order is delivered</p>
                                </div>
                            </div>
                        `;
                    }
                }
                
                // Switch back to preview view
                paymentMethodsContainer.style.display = 'none';
                paymentPreview.style.display = 'block';
            });
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>