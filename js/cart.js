document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.buy-btn-1');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productCard = this.closest('.product-card-1');
            const link = productCard.closest('a');
            const url = new URL(link.href, window.location.origin);
            const productId = url.searchParams.get('id');

            if (productId) {
                addToCart(productId, 1, 'meter');
            }
        });
    });

    // Add to cart from product detail page
    const addToCartDetailBtn = document.querySelector('.btn-primary');
    if (addToCartDetailBtn) {
        addToCartDetailBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('id');
            const quantity = parseInt(document.getElementById('quantityInput')?.value || 1);
            const size = document.getElementById('sizeSelect')?.value || 'meter';

            if (productId) {
                addToCart(productId, quantity, size);
            }
        });
    }
});

function addToCart(productId, quantity, size) {
    console.log('Adding to cart:', { productId, quantity, size }); // Debug log

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${encodeURIComponent(productId)}&quantity=${quantity}&size=${size}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data); // Debug log
        
        if (data.success) {
            // Update cart badge
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.display = data.cart_count > 0 ? 'block' : 'none';
            }
            alert('Product added to cart successfully!');
        } else {
            alert(data.message || 'Failed to add product to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding to cart');
    });
}



