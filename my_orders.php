<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    try {
        $stmt = $pdo->prepare("UPDATE vehicle_orders SET status = 'Cancelled' WHERE order_id = ? AND status = 'Pending'");
        $stmt->execute([$order_id]);
        $success = "Order cancelled successfully!";
        header("Location: my_orders.php");
        exit();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle order clearing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_order'])) {
    $order_id = $_POST['order_id'];
    try {
        $stmt = $pdo->prepare("UPDATE vehicle_orders SET 
            visible_to_customer = 0 
            WHERE order_id = ? 
            AND status = 'Cancelled' 
            AND email = (SELECT email FROM users WHERE user_id = ?)");
        
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Order removed from history successfully.";
        }
        
        header("Location: my_orders.php");
        exit();
    } catch(Exception $e) {
        $error = "Error removing order: " . $e->getMessage();
    }
}

try {
    // Simplified query without payment status join
    $stmt = $pdo->prepare("
        SELECT * FROM vehicle_orders 
        WHERE email = (SELECT email FROM users WHERE user_id = ?)
        AND (visible_to_customer = 1 OR visible_to_customer IS NULL)
        ORDER BY order_date DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - R-BuildX</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .section-title {
            text-align: center;
            color: #512da8;
            margin-bottom: 30px;
            font-size: 2.5em;
            opacity: 0;
            animation: fadeDown 0.5s ease forwards;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.5s ease forwards;
            animation-delay: calc(var(--order) * 0.1s);
            border-left: 5px solid #512da8;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #512da8;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9em;
            background: #ffe0b2;
            color: #e65100;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-group {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .info-group:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .info-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .total-amount {
            font-size: 1.3em;
            color: #512da8;
            font-weight: bold;
        }

        .checkout-btn {
            background: #512da8;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(81,45,168,0.2);
            background: #4527a0;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            animation: fadeIn 0.5s ease;
        }

        .empty-state h3 {
            color: #512da8;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        @keyframes fadeDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .date-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-top: 5px;
            display: inline-block;
        }

        .status-badge.pending {
            background: #ffe0b2;
            color: #e65100;
        }

        .status-badge.approved {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-badge.completed {
            background: #bbdefb;
            color: #1565c0;
        }

        .order-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9em;
            background: #e8eaf6;
            color: #3f51b5;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .cancel-btn {
            background: #ff4444;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .cancel-btn:hover {
            background: #cc0000;
        }

        .status-badge.cancelled {
            background: #ffcdd2;
            color: #c62828;
        }

        .payment-btn {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .payment-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .completion-time {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .success-message {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .cancelled-message {
            background: #ffcdd2;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .order-message {
            text-align: right;
            flex-grow: 1;
        }

        .clear-form {
            margin-top: 10px;
        }

        .clear-btn {
            background: #ff5252;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            background: #ff1744;
        }

        .cancelled-message {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffebee;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="orders-container">
        <h1 class="section-title">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h3>No Orders Yet</h3>
                <p>Start browsing our vehicles to place your first order!</p>
                <a href="vehicles.php" class="checkout-btn">Browse Vehicles</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $index => $order): ?>
                <div class="order-card" style="--order: <?php echo $index; ?>">
                    <div class="order-header">
                        <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                        <span class="status-badge <?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div class="order-info">
                        <div class="info-group">
                            <div class="info-label">Vehicle Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['vehicle_type']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Location</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['location']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Purpose</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['purpose']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Order Date</div>
                            <div class="info-value">
                                <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="total-section">
                        <div class="total-amount">
                            Total Amount: ₹<?php echo number_format($order['rate'], 2); ?>/-
                        </div>
                        
                        <?php if ($order['status'] == 'Pending'): ?>
                            <div class="action-buttons">
                                <div class="order-message">
                                    Your order is being reviewed. We'll notify you once it's approved.
                                </div>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="cancel_order" class="cancel-btn">Cancel Order</button>
                                </form>
                            </div>
                        <?php elseif ($order['status'] == 'Approved'): ?>
                            <div class="action-buttons">
                                <div class="order-message">
                                    Your order has been approved! Please complete the payment to confirm your booking.
                                    <div class="completion-time">
                                        Scheduled for: <?php echo date('d M Y, h:i A', strtotime($order['completion_date'] . ' ' . $order['completion_time'])); ?>
                                    </div>
                                </div>
                                <a href="payment.php?order_id=<?php echo $order['order_id']; ?>" class="payment-btn">Make Payment</a>
                            </div>
                        <?php elseif ($order['status'] == 'Completed'): ?>
                            <div class="order-message success-message">
                                Order completed. Thank you for choosing R-BuildX!
                            </div>
                        <?php elseif ($order['status'] == 'Cancelled'): ?>
                            <div class="order-message cancelled-message">
                                Order cancelled.
                                <form method="POST" class="clear-form" onsubmit="return confirm('Are you sure you want to remove this order from your history?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="clear_order" class="clear-btn">
                                        <i class="fas fa-trash"></i> Clear
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 