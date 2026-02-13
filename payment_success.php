<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order and payment details
$stmt = $pdo->prepare("SELECT 
    vo.*,
    vo.rate as base_amount,
    (vo.rate * 0.18) as gst_amount,
    (vo.rate * 1.18) as total_amount,
    vo.payment_date,
    vo.payment_method,
    vo.transaction_id
    FROM vehicle_orders vo
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
    <link rel="icon" href="favicon.png">
    <style>
        .success-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 48px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .payment-details {
            margin-top: 30px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:nth-child(6) {
            text-transform: lowercase;
        }

        .detail-row:last-child {
            border-bottom: none;
            text-transform: capitalize;
        }

        .print-btn {
            background: #512da8;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            background: #673ab7;
        }

        @media print {
            .no-print {
                display: none;
            }
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
            <div class="detail-row">
                <span>Order ID:</span>
                <span>#<?php echo $payment['order_id']; ?></span>
            </div>
            <div class="detail-row">
                <span>Vehicle Type:</span>
                <span><?php echo htmlspecialchars($payment['vehicle_type']); ?></span>
            </div>
            <div class="detail-row">
                <span>Location:</span>
                <span><?php echo htmlspecialchars($payment['location']); ?></span>
            </div>
            <div class="detail-row">
                <span>Purpose:</span>
                <span><?php echo htmlspecialchars($payment['purpose']); ?></span>
            </div>
            <div class="detail-row">
                <span>Payment Method:</span>
                <span><?php echo htmlspecialchars($payment['payment_method']); ?></span>
            </div>
            <div class="detail-row">
                <span>Transaction ID:</span>
                <span><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
            </div>
            <div class="detail-row">
                <span>Base Amount:</span>
                <span>₹<?php echo number_format($payment['base_amount'], 2); ?>/-</span>
            </div>
            <div class="detail-row">
                <span>GST (18%):</span>
                <span>₹<?php echo number_format($payment['gst_amount'], 2); ?>/-</span>
            </div>
            <div class="detail-row">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($payment['total_amount'], 2); ?>/-</span>
            </div>
            <div class="detail-row">
                <span>Payment Date:</span>
                <span><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></span>
            </div>
        </div>

        <button onclick="window.print()" class="print-btn no-print">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <p class="no-print">
            <a href="my_orders.php" style="color: #512da8; text-decoration: none; margin-top: 20px; display: inline-block;">
                Back to My Orders
            </a>
        </p>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 