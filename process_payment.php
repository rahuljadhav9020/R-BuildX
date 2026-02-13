<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit();
}

$order_id = $_GET['order_id'];

// Fetch payment details
$stmt = $pdo->prepare("SELECT 
    vo.*,
    p.payment_date,
    p.base_amount,
    p.gst_amount,
    p.total_amount
    FROM vehicle_orders vo
    LEFT JOIN payments p ON vo.order_id = p.order_id
    WHERE vo.order_id = ? AND vo.status = 'Completed'");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: my_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - R-BuildX</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            color: #4CAF50;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .payment-details {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .print-btn {
            background: #512da8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="success-container">
        <div class="success-icon">✓</div>
        <h2>Payment Successful!</h2>
        <p>Your payment has been processed successfully.</p>

        <div class="payment-details">
            <div class="amount-row">
                <span>Order ID:</span>
                <span>#<?php echo $payment['order_id']; ?></span>
            </div>
            <div class="amount-row">
                <span>Base Amount:</span>
                <span>₹<?php echo number_format($payment['base_amount'], 2); ?>/-</span>
            </div>
            <div class="amount-row">
                <span>GST (18%):</span>
                <span>₹<?php echo number_format($payment['gst_amount'], 2); ?>/-</span>
            </div>
            <div class="amount-row">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($payment['total_amount'], 2); ?>/-</span>
            </div>
            <div class="amount-row">
                <span>Payment Date:</span>
                <span><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></span>
            </div>
        </div>

        <button onclick="window.print()" class="print-btn">Print Receipt</button>
        <p><a href="my_orders.php">Back to My Orders</a></p>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 