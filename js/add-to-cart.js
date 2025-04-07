class CartManager {
    constructor() {
        this.cart = this.getCart();
        this.updateCartBadge();
    }

    getCart() {
        const savedCart = localStorage.getItem('cart');
        return savedCart ? JSON.parse(savedCart) : [];
    }

    addItem(productId) {
        const product = productData[productId];
        if (!product) return;

        const existingItemIndex = this.cart.findIndex(item => item.productId === productId);

        if (existingItemIndex !== -1) {
            this.cart[existingItemIndex].quantity += 1;
        } else {
            this.cart.push({
                productId: productId,
                name: product.name,
                color: product.color,
                price: product.price,
                quantity: 1,
                image: product.image
            });
        }

        this.saveCart();
        this.updateCartBadge();
        this.renderCart();
    }

    removeItem(index) {
        this.cart.splice(index, 1);
        this.saveCart();
        this.updateCartBadge();
        this.renderCart();
    }

    updateQuantity(index, newQuantity) {
        if (newQuantity > 0) {
            this.cart[index].quantity = newQuantity;
            this.saveCart();
            this.renderCart();
        }
    }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.cart));
    }

    updateCartBadge() {
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartBadge = document.querySelector('.cart-badge');
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'block' : 'none';
        }
    }

    calculateTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    renderCart() {
        const cartContainer = document.getElementById('cartItems');
        const summaryItems = document.querySelector('.summary-items');
        const totalAmount = document.querySelector('.total-amount');
        
        if (!cartContainer || !summaryItems || !totalAmount) return;

        // Render cart items
        cartContainer.innerHTML = this.cart.map((item, index) => `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h3 class="cart-item-title">${item.name}</h3>
                    <p class="cart-item-color">${item.color}</p>
                    <p class="cart-item-price">LKR ${item.price}</p>
                </div>
                <div class="cart-item-quantity">
                    <input type="number" value="${item.quantity}" min="1" 
                           onchange="cartManager.updateQuantity(${index}, parseInt(this.value))">
                </div>
                <button class="cart-item-remove" onclick="cartManager.removeItem(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `).join('');

        // Render summary items
        summaryItems.innerHTML = this.cart.map(item => `
            <div class="summary-item">
                <span>${item.name} (${item.quantity}x)</span>
                <span>LKR ${item.price * item.quantity}</span>
            </div>
        `).join('');

        // Update total
        totalAmount.textContent = `LKR ${this.calculateTotal()}`;
    }
}

// Initialize cart manager
const cartManager = new CartManager();

// Add event listeners
document.addEventListener('DOMContentLoaded', () => {
    const addToCartButtons = document.querySelectorAll('.buy-btn-1, .add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            let productId;
            const card = button.closest('.product-card-1');
            if (card) {
                const link = card.closest('a');
                if (link) {
                    const url = new URL(link.href, window.location.origin);
                    productId = url.searchParams.get('id');
                }
            }
            
            if (productId) {
                cartManager.addItem(productId);
                // Show success message
                alert('Product added to cart!');
            }
        });
    });

    // Initial render of cart if on cart page
    if (document.getElementById('cartItems')) {
        cartManager.renderCart();
    }
});