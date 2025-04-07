<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize payment method variables
$payment_method = isset($_SESSION['payment_method']) ? $_SESSION['payment_method'] : 'cod'; // Default to COD if not set

// Initialize card info variables
$cardName = isset($_SESSION['card_info']['name']) ? $_SESSION['card_info']['name'] : '';
$cardNumber = isset($_SESSION['card_info']['number']) ? $_SESSION['card_info']['number'] : '';
$cardExpiry = isset($_SESSION['card_info']['expiry']) ? $_SESSION['card_info']['expiry'] : '';
?>

<!-- Payment Method Selection -->
<div>
    <div class="payment-method <?php echo ($payment_method === 'card') ? 'selected' : ''; ?>" data-method="card">
        <div class="form-check d-flex align-items-center">
            <input class="form-check-input" type="radio" name="payment_method" id="cardPayment" value="card" <?php echo ($payment_method === 'card') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="cardPayment">
                <div class="d-flex align-items-center">
                    <i class="fas fa-credit-card me-2 fa-lg"></i>
                    <span>Card Payment</span>
                </div>
            </label>
        </div>
        
        <div id="cardPaymentForm" class="payment-form" <?php echo ($payment_method === 'card') ? 'style="display: block;"' : ''; ?>>
            <div class="mb-3">
                <label for="cardName" class="form-label">Name on Card *</label>
                <input type="text" class="form-control" id="cardName" name="card_name" value="<?php echo htmlspecialchars($cardName); ?>" placeholder="Enter name on card">
            </div>
            <div class="mb-3">
                <label for="cardNumber" class="form-label">Card Number *</label>
                <input type="text" class="form-control" id="cardNumber" name="card_number" value="<?php echo htmlspecialchars($cardNumber); ?>" placeholder="XXXX XXXX XXXX XXXX">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cardExpiry" class="form-label">Expiry Date *</label>
                    <input type="text" class="form-control" id="cardExpiry" name="card_expiry" value="<?php echo htmlspecialchars($cardExpiry); ?>" placeholder="MM/YY">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cardCvv" class="form-label">CVV *</label>
                    <input type="text" class="form-control" id="cardCvv" name="card_cvv" placeholder="XXX">
                </div>
            </div>
        </div>
    </div>
    
    <div class="payment-method <?php echo ($payment_method === 'cod') ? 'selected' : ''; ?>" data-method="cod">
        <div class="form-check d-flex align-items-center">
            <input class="form-check-input" type="radio" name="payment_method" id="cashOnDelivery" value="cod" <?php echo ($payment_method === 'cod') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="cashOnDelivery">
                <div class="d-flex align-items-center">
                    <i class="fas fa-money-bill-wave me-2 fa-lg"></i>
                    <span>Cash on Delivery</span>
                </div>
            </label>
        </div>
        
        <div id="codForm" class="payment-form" <?php echo ($payment_method === 'cod') ? 'style="display: block;"' : ''; ?>>
            <div class="alert alert-info">
                <p>You will pay for your order when it is delivered to your doorstep.</p>
            </div>
        </div>
    </div>
    
    <button type="button" class="btn btn-success mt-3 w-100" id="savePaymentBtn">Save Payment Method</button>
</div>

<script>
// Initialize payment method handling
document.addEventListener('DOMContentLoaded', function() {
    initializePaymentMethods();
});

function initializePaymentMethods() {
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Update radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update selected class
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            
            // Hide all payment forms
            document.querySelectorAll('.payment-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show selected payment form
            const selectedMethod = radio.value;
            if (selectedMethod === 'card') {
                document.getElementById('cardPaymentForm').style.display = 'block';
            } else if (selectedMethod === 'cod') {
                document.getElementById('codForm').style.display = 'block';
            }
        });
    });
    
    // Save payment method button functionality
    const savePaymentBtn = document.getElementById('savePaymentBtn');
    if (savePaymentBtn) {
        savePaymentBtn.addEventListener('click', function() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            // For card payment, validate fields
            if (selectedMethod === 'card') {
                const cardName = document.getElementById('cardName').value.trim();
                const cardNumber = document.getElementById('cardNumber').value.trim();
                const cardExpiry = document.getElementById('cardExpiry').value.trim();
                const cardCvv = document.getElementById('cardCvv').value.trim();
                
                if (!cardName || !cardNumber || !cardExpiry || !cardCvv) {
                    alert('Please fill in all card details');
                    return false;
                }
                
                // Save card details using fetch API
                fetch('save_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        method: selectedMethod,
                        card_name: cardName,
                        card_number: cardNumber,
                        card_expiry: cardExpiry
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // If parent window function exists, call it
                        if (typeof updatePaymentSummary === 'function') {
                            updatePaymentSummary();
                        }
                    } else {
                        alert('Error saving payment method: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            } else {
                // Save COD method using fetch API
                fetch('save_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        method: selectedMethod
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // If parent window function exists, call it
                        if (typeof updatePaymentSummary === 'function') {
                            updatePaymentSummary();
                        }
                    } else {
                        alert('Error saving payment method: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
            
            // Trigger the custom event
            const event = new CustomEvent('paymentMethodSaved', { 
                detail: { 
                    method: selectedMethod,
                    cardName: selectedMethod === 'card' ? document.getElementById('cardName').value.trim() : '',
                    cardNumber: selectedMethod === 'card' ? document.getElementById('cardNumber').value.trim() : '',
                    cardExpiry: selectedMethod === 'card' ? document.getElementById('cardExpiry').value.trim() : ''
                } 
            });
            document.dispatchEvent(event);
            
            // Return true to indicate successful save
            return true;
        });
    }
}
</script>