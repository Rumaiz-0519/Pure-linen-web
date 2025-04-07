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
    const checkoutForm = document.getElementById('checkout-form');
    
    // Check if elements exist before adding event listeners
    function safeAddEventListener(element, event, callback) {
        if (element) {
            element.addEventListener(event, callback);
        } else {
            console.warn(`Element not found for event: ${event}`);
        }
    }
    
    // Address edit button
    safeAddEventListener(editAddressBtn, 'click', function() {
        if (addressFormContainer && addressPreview) {
            addressFormContainer.style.display = 'block';
            addressPreview.style.display = 'none';
        }
    });
    
    // Payment edit button
    safeAddEventListener(editPaymentBtn, 'click', function() {
        if (paymentMethodsContainer && paymentPreview) {
            paymentMethodsContainer.style.display = 'block';
            paymentPreview.style.display = 'none';
        }
    });
    
    // Save address button functionality
    safeAddEventListener(saveAddressBtn, 'click', function() {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const address = document.getElementById('address').value.trim();
        const city = document.getElementById('city').value.trim();
        const postcode = document.getElementById('postcode').value.trim();
        const country = document.getElementById('country').value.trim();
        
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
                addressPreview.innerHTML = `
                    <p class="name">${firstName} ${lastName}</p>
                    <p>${email}</p>
                    <p>${phone}</p>
                    <p>${address}</p>
                    <p>${city}, ${postcode}</p>
                    <p>${country}</p>
                `;
                
                // Switch back to preview view
                addressFormContainer.style.display = 'none';
                addressPreview.style.display = 'block';
            } else {
                alert('Failed to save address: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Still update the UI even if server-side saving fails
            addressPreview.innerHTML = `
                <p class="name">${firstName} ${lastName}</p>
                <p>${email}</p>
                <p>${phone}</p>
                <p>${address}</p>
                <p>${city}, ${postcode}</p>
                <p>${country}</p>
            `;
            
            addressFormContainer.style.display = 'none';
            addressPreview.style.display = 'block';
        });
    });
    
    // Save payment method button functionality
    safeAddEventListener(savePaymentBtn, 'click', function() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        // Prepare the payment data
        let paymentData = {
            method: selectedMethod
        };
        
        // Add card details if card payment selected
        if (selectedMethod === 'card') {
            const cardName = document.getElementById('cardName').value.trim();
            const cardNumber = document.getElementById('cardNumber').value.trim();
            const cardExpiry = document.getElementById('cardExpiry').value.trim();
            const cardCvv = document.getElementById('cardCvv').value.trim();
            
            // Validate card details
            if (!cardName || !cardNumber || !cardExpiry || !cardCvv) {
                alert('Please fill in all card details');
                return;
            }
            
            paymentData.card_name = cardName;
            paymentData.card_number = cardNumber;
            paymentData.card_expiry = cardExpiry;
            // We don't store CVV in the session for security reasons
        }
        
        // Save to session using AJAX
        fetch('save_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paymentData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update payment preview based on selected method
                if (selectedMethod === 'card') {
                    const cardName = document.getElementById('cardName').value.trim();
                    const cardNumber = document.getElementById('cardNumber').value.trim();
                    
                    paymentPreview.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-credit-card me-2 fa-lg"></i>
                            <div>
                                <span class="fw-bold">Card Payment</span>
                                <p class="text-muted mb-0 small">${cardName} - ${cardNumber}</p>
                            </div>
                        </div>
                    `;
                } else {
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
                
                // Switch back to preview view
                paymentMethodsContainer.style.display = 'none';
                paymentPreview.style.display = 'block';
            } else {
                alert('Failed to save payment method: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Still update the UI even if server-side saving fails
            if (selectedMethod === 'card') {
                const cardName = document.getElementById('cardName').value.trim();
                const cardNumber = document.getElementById('cardNumber').value.trim();
                
                paymentPreview.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-credit-card me-2 fa-lg"></i>
                        <div>
                            <span class="fw-bold">Card Payment</span>
                            <p class="text-muted mb-0 small">${cardName} - ${cardNumber}</p>
                        </div>
                    </div>
                `;
            } else {
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
            
            paymentMethodsContainer.style.display = 'none';
            paymentPreview.style.display = 'block';
        });
    });
    
    // Form submission validation
    safeAddEventListener(checkoutForm, 'submit', function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'card') {
            const cardName = document.getElementById('cardName').value.trim();
            const cardNumber = document.getElementById('cardNumber').value.trim();
            const cardExpiry = document.getElementById('cardExpiry').value.trim();
            const cardCvv = document.getElementById('cardCvv').value.trim();
            
            if (!cardName || !cardNumber || !cardExpiry || !cardCvv) {
                e.preventDefault();
                alert('Please fill in all card details. Click the Edit button next to Payment Method to complete your card information.');
                editPaymentBtn.click();
                return false;
            }
        }
    });
    
    // Delivery options selection
    const deliveryOptions = document.querySelectorAll('.delivery-option');
    deliveryOptions.forEach(option => {
        safeAddEventListener(option, 'click', function() {
            // Update radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update selected class
            deliveryOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Update shipping cost and total
            updateOrderTotal();
            
            // Save delivery option preference via AJAX
            const selectedOption = radio.value;
            fetch('save_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    delivery_option: selectedOption
                }),
            }).catch(error => console.error('Error saving delivery option:', error));
        });
    });
    
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentForms = document.querySelectorAll('.payment-form');
    
    paymentMethods.forEach(method => {
        safeAddEventListener(method, 'click', function() {
            // Update radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update selected class
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            
            // Show selected payment form and hide others
            const selectedMethod = this.getAttribute('data-method');
            paymentForms.forEach(form => {
                form.style.display = 'none';
            });
            
            // Fix for payment form display
            if (selectedMethod === 'card') {
                document.getElementById('cardPaymentForm').style.display = 'block';
            } else if (selectedMethod === 'cod') {
                document.getElementById('codForm').style.display = 'block';
            }
        });
    });
    
    // Function to update order total when shipping method changes
    function updateOrderTotal() {
        const selectedDelivery = document.querySelector('input[name="delivery_option"]:checked').value;
        const shippingCost = selectedDelivery === 'express' ? 600 : 250;
        const subtotalElement = document.getElementById('order-total').parentElement.previousElementSibling.previousElementSibling.lastElementChild;
        const subtotalText = subtotalElement.textContent.replace('LKR ', '').replace(',', '');
        const subtotal = parseFloat(subtotalText);
        const total = subtotal + shippingCost;
        
        document.getElementById('shipping-cost').textContent = 'LKR ' + shippingCost.toFixed(2);
        document.getElementById('order-total').textContent = 'LKR ' + total.toFixed(2);
    }
});