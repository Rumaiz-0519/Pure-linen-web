<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light py-2">
    <div class="container">
        <a href="admin_dashboard.php" class="navbar-brand">
            <span class="fw-bold text-dark">Admin</span><span class="fw-normal">Panel</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="adminNavbar">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'product_management.php' || $current_page == 'admin_add_product.php') ? 'active' : ''; ?>" href="admin_products.php">Product</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_orders.php') ? 'active' : ''; ?>" href="admin_orders.php">Orders</a>
                </li>
                <li>
                        <a href="featured_products.php"><i class="fas fa-star"></i> Featured Products</a>
                    </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_admins.php') ? 'active' : ''; ?>" href="admin_admins.php">Admins</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>" href="admin_users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_messages.php') ? 'active' : ''; ?>" href="admin_messages.php">Messages</a>
                </li>
            </ul>

            <div class="dropdown">
                <a class="btn btn-link dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle fa-lg"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><p class="dropdown-item">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="admin_profile.php">Update Profile</a></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>