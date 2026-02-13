<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);

// Get pending orders count if user is logged in
$pendingOrders = 0;
if ($isLoggedIn) {
    try {
        require_once 'config/db.php';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_orders 
                              WHERE email = (SELECT email FROM users WHERE user_id = ?) 
                              AND status = 'Pending'");
        $stmt->execute([$_SESSION['user_id']]);
        $pendingOrders = $stmt->fetchColumn();
    } catch(Exception $e) {
        error_log($e->getMessage());
    }
}
?>

<head>
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<div class="nav-bar">
    <div class="logo">
        <?php if (!$isLoggedIn): ?>
            <a href="admin/login.php" class="logo">
                <img src="logo.png" alt="R-BuildX Logo">
            </a>
        <?php else: ?>
            <a href="home.php" class="logo">
                <img src="logo.png" alt="R-BuildX Logo">
            </a>
        <?php endif; ?>
    </div>
    <div class="nav-list">
        <ul>
            <li><a href="home.php">Home</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="vehicles.php">Vehicles</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li class="orders-menu">
                    <a href="my_orders.php">
                        Orders
                        <?php if ($pendingOrders > 0): ?>
                            <span class="order-badge"><?php echo $pendingOrders; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <li class="profile-menu">
                        <a href="profile.php">
                            <i class="fas fa-user-circle"></i>
                        </a>
                    </li>
            <?php else: ?>
                <li><a href="about.html">About</a></li>
                <li><a href="index.php">Login/Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<style>
.orders-menu {
    position: relative;
}

.order-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff4444;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Dropdown styles */
.orders-menu:hover .orders-dropdown {
    display: block;
}

.orders-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 10px 0;
    min-width: 200px;
    z-index: 1000;
}

.orders-dropdown a {
    display: block;
    padding: 8px 15px;
    color: #333;
    transition: background 0.3s;
}

.orders-dropdown a:hover {
    background: #f5f5f5;
}
</style> 