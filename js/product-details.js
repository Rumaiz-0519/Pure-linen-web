document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');
    const product = productData[productId];

    if(product) {
        // Update product details
        document.getElementById('productImage').src = product.image;
        document.getElementById('productTitle').textContent = product.name;
        document.getElementById('productColor').textContent = `(${product.color})`;
        document.getElementById('productDescription').textContent = product.description;
        document.getElementById('priceDisplay').textContent = product.price;

        // Update specifications
        document.getElementById('specType').textContent = product.specs.type;
        document.getElementById('specProperties').textContent = product.specs.properties;
        document.getElementById('specComposition').textContent = product.specs.composition;
        document.getElementById('specColor').textContent = product.specs.color;

        // Setup price calculation with product-specific prices
        setupPriceCalculation(product);
    }
});

function setupPriceCalculation(product) {
    const sizeSelect = document.getElementById('sizeSelect');
    const quantityInput = document.getElementById('quantityInput');
    const priceDisplay = document.getElementById('priceDisplay');

    function updatePrice() {
        // Use product-specific prices
        const basePrice = sizeSelect.value === 'cm' ? product.halfPrice : product.price;
        const quantity = parseInt(quantityInput.value) || 1;
        const totalPrice = basePrice * quantity;
        priceDisplay.textContent = totalPrice;
    }

    sizeSelect.addEventListener('change', updatePrice);
    quantityInput.addEventListener('input', updatePrice);

    // Initial price calculation
    updatePrice();
}

// Search toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchToggle = document.getElementById('searchToggle');
    
    searchToggle.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'search.php';
    });
});
