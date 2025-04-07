
<?php
require_once 'config.php';
$pageTitle = 'Swatch Book';
$currentPage = 'swatch';
include 'header.php';
?>

<head>
</head>

<body>
    
<!--NAVIGATION-->
<nav class="navbar navbar-expand-lg navbar-light bg-light py-2 fixed-top">
        <div class="container">
            <img src="img/logo.png" alt="Pure Linen Logo" class="navbar-brand">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="shop.php">Linen Fabric</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="swatch.php">Swatch Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
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
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><p class="dropdown-item mb-0">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profile.php">Update Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container mt-5 pt-5">


<div class="container swatch-container mt-5">
    <div class="swatch-card">
        <img src="img/swatch book.webp" alt="Dark Swatch" class="img-fluid">
        <div class="swatch-details">
            <h5>Premium Linen Swatch Book</h5>
            <p>(Plain)</p>
            <button class="subscription-btn" data-type="dark">
                Subscription
            </button>
        </div>
    </div>
</div>

<div class="container swatch-container mt-5">
    <div class="swatch-card">
        <img src="img/swatch book.webp" alt="Light Swatch" class="img-fluid">
        <div class="swatch-details">
            <h5>Premium Linen Swatch Book</h5>
            <p>(Printed)</p>
            <button class="subscription-btn" data-type="light">
                Subscription
            </button>
        </div>
    </div>
</div>
        

        

        
    </div>
</div>

<!-- Improved Subscription Modal -->
<div class="modal fade" id="subscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="swatchType" value="dark">
                <div class="subscription-options">
                    <div class="plan-option mb-3">
                        <button class="plan-btn" data-months="6" onclick="selectPlan(6)">
                            <div class="plan-heading">
                                <span class="duration">6 Months</span>
                                <span class="badge bg-primary">Best Value</span>
                            </div>
                            <div class="plan-details">
                                <div class="price">LKR 20,000</div>
                                <div class="savings">Save LKR 4,000</div>
                            </div>
                        </button>
                    </div>
                    <div class="plan-option mb-3">
                        <button class="plan-btn" data-months="3" onclick="selectPlan(3)">
                            <div class="plan-heading">
                                <span class="duration">3 Months</span>
                                <span class="badge bg-info">Popular</span>
                            </div>
                            <div class="plan-details">
                                <div class="price">LKR 12,000</div>
                                <div class="savings">Save LKR 1,000</div>
                            </div>
                        </button>
                    </div>
                    <div class="plan-option mb-3">
                        <button class="plan-btn" data-months="1" onclick="selectPlan(1)">
                            <div class="plan-heading">
                                <span class="duration">1 Month</span>
                            </div>
                            <div class="plan-details">
                                <div class="price">LKR 4,000</div>
                                <div class="savings">Single month trial</div>
                            </div>
                        </button>
                    </div>
                    <div class="benefits mt-4">
                        <h6><i class="fas fa-gift me-2"></i>Subscription Benefits</h6>
                        <ul class="benefit-list">
                            <li><i class="fas fa-check"></i> Premium Swatch Book Monthly Delivery</li>
                            <li><i class="fas fa-check"></i> Priority Access to New Collections</li> 
                            <li><i class="fas fa-check"></i> Exclusive Seasonal Catalogs</li>
                            <li><i class="fas fa-check"></i> Free Shipping on All Deliveries</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.plan-option {
    width: 100%;
}

.plan-btn {
    width: 100%;
    padding: 16px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    transition: all 0.3s ease;
    text-align: left;
    cursor: pointer;
}

.plan-btn:hover {
    background: #f8f9fa;
    border-color: #1B365D;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.plan-btn.selected {
    background: rgba(27, 54, 93, 0.05);
    border-color: #1B365D;
}

.plan-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.duration {
    font-weight: 600;
    font-size: 18px;
    color: #333;
}

.plan-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-weight: 700;
    font-size: 20px;
    color: #1B365D;
}

.savings {
    font-size: 14px;
    color: #28a745;
}

.benefits {
    padding: 18px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
}

.benefits h6 {
    color: #1B365D;
    margin-bottom: 12px;
    font-weight: 600;
}

.benefit-list {
    padding-left: 0;
    list-style: none;
    margin-bottom: 0;
}

.benefit-list li {
    padding: 6px 0;
    display: flex;
    align-items: center;
    color: #555;
}

.benefit-list li i {
    color: #28a745;
    margin-right: 10px;
    font-size: 14px;
}

.badge {
    font-weight: 500;
    padding: 5px 10px;
    font-size: 12px;
}

/* Add animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.plan-option:first-child .plan-btn {
    animation: pulse 2s infinite;
}
</style>

<script>
function openSubscriptionModal(type) {
    document.getElementById('swatchType').value = type;
    var modal = new bootstrap.Modal(document.getElementById('subscriptionModal'));
    modal.show();
}

function selectPlan(months) {
    // First highlight the selected button
    const buttons = document.querySelectorAll('.plan-btn');
    buttons.forEach(btn => {
        btn.classList.remove('selected');
        if (parseInt(btn.getAttribute('data-months')) === months) {
            btn.classList.add('selected');
        }
    });
    
    // Short delay before redirecting for better user experience
    setTimeout(() => {
        const swatchType = document.getElementById('swatchType').value;
        window.location.href = `swatch_subscription.php?type=${swatchType}&duration=${months}`;
    }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to "Subscription" buttons
    document.querySelectorAll('.subscription-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const swatchType = this.getAttribute('data-type');
            openSubscriptionModal(swatchType);
        });
    });
    
    // When a plan button is clicked, add selected class
    document.querySelectorAll('.plan-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.plan-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            this.classList.add('selected');
        });
    });
});
</script>

</body>

<style>
.swatch-container {
   display: flex;
   justify-content: center;
   flex-wrap: wrap;
   gap: 30px;
   max-width: 1000px;
   margin: 80px auto 0;
}

.swatch-card {
    width: 300px;
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    
}

.swatch-card:hover {
    transform: translateY(-5px);
}

.swatch-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}

.swatch-details {
    text-align: center;
}

.swatch-details h5 {
    color: #333;
    margin-bottom: 8px;
}

.swatch-details p {
    color: #666;
    margin-bottom: 15px;
}

.subscription-btn {
    background-color: #E57373;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 5px;
    width: 100%;
    transition: background-color 0.3s ease;
}

.subscription-btn:hover {
    background-color: #D32F2F;
}

/* Modal Styles */
.subscription-option-btn {
    width: 100%;
    padding: 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.subscription-option-btn:hover {
    background: #f8f9fa;
    border-color: #1B365D;
}

.subscription-option-btn.selected {
    background: #1B365D;
    color: white;
    border-color: #1B365D;
}

.benefits {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.text-sm {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #666;
}

.container.swatch-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    max-width: 1300px;
    margin: 80px auto 0;
    padding: 0 20px;
}
</style>

<script src="js/swatch.js"></script>

<?php include 'footer.php'; ?>