<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit();
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM vehicle_orders WHERE order_id = ? AND status = 'Approved'");
$stmt->execute([$_GET['order_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'UPI';
        $transactionId = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : null;

        // Update order status
        $stmt = $pdo->prepare("UPDATE vehicle_orders SET 
            status = 'Completed',
            payment_status = 'Paid',
            payment_date = CURRENT_TIMESTAMP,
            payment_method = ?,
            transaction_id = ?
            WHERE order_id = ?");
        
        $stmt->execute([$paymentMethod, $transactionId, $order['order_id']]);

        // Record payment - Using correct column names
        $stmt = $pdo->prepare("INSERT INTO payments (
            order_id,
            rate,
            gst_amount,
            total_amount,
            payment_method,
            transaction_id,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, 'Success')");
        
        $stmt->execute([
            $order['order_id'],
            $order['rate'],
            ($order['rate'] * 0.18),
            ($order['rate'] * 1.18),
            $paymentMethod,
            $transactionId
        ]);

        // Add notification for admin
        $stmt = $pdo->prepare("INSERT INTO notifications (
            order_id,
            message,
            type,
            amount
        ) VALUES (?, ?, 'payment', ?)");
        
        $totalAmount = $order['rate'] * 1.18; // Calculate total with GST
        $message = "Payment of ₹" . number_format($totalAmount, 2) . "/- received for Order #{$order['order_id']} via {$paymentMethod}";
        $stmt->execute([$order['order_id'], $message, $totalAmount]);

        $pdo->commit();
        $_SESSION['payment_success'] = true;
        header("Location: payment_success.php?order_id=" . $order['order_id']);
        exit();

    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Payment processing failed: " . $e->getMessage();
    }
}

// Calculate amounts from order rate
$baseAmount = $order['rate'];
$gstAmount = $baseAmount * 0.18;
$totalAmount = $baseAmount + $gstAmount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - R-BuildX</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .payment-option {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .payment-summary {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .amount-row:last-child {
            border-bottom: none;
            font-weight: bold;
        }

        .qr-section {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code {
            max-width: 200px;
            margin: 20px auto;
        }

        .upi-details {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .upi-details p {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .upi-details strong {
            color: #512da8;
        }

        .payment-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .payment-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .payment-btn i {
            font-size: 20px;
        }

        .payment-form {
            margin-top: 20px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .transaction-input {
            position: relative;
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .transaction-input i {
            padding: 12px;
            color: #666;
            font-size: 18px;
        }

        .transaction-input input {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            font-size: 16px;
            outline: none;
            width: 100%;
        }

        .transaction-input:focus-within {
            border-color: #512da8;
            background: white;
        }

        .transaction-input:focus-within i {
            color: #512da8;
        }

        .input-help {
            display: block;
            margin-top: 6px;
            color: #666;
            font-size: 12px;
        }

        .transaction-input input:invalid {
            border-color: #ff5252;
        }

        .transaction-input input:valid {
            border-color: #4CAF50;
        }

        .copy-upi {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .copy-upi input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: #fff;
            color: #512da8;
            font-size: 14px;
            cursor: text;
        }

        .copy-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background: #512da8;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            background: #673ab7;
        }

        .copy-btn i {
            font-size: 14px;
        }

        .transaction-input input {
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .transaction-input input::placeholder {
            letter-spacing: normal;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="payment-container">
        <h2>Complete Your Payment</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="payment-summary">
            <h3>Order Summary</h3>
            <div class="amount-row">
                <span>Base Amount:</span>
                <span>₹<?php echo number_format($baseAmount, 2); ?>/-</span>
            </div>
            <div class="amount-row">
                <span>GST (18%):</span>
                <span>₹<?php echo number_format($gstAmount, 2); ?>/-</span>
            </div>
            <div class="amount-row">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($totalAmount, 2); ?>/-</span>
            </div>
        </div>

        <div class="payment-methods">
            <div class="payment-option">
                <h3>Pay via UPI</h3>
                <div class="qr-section">
                    <img src="qr.jpg?amount=<?php echo $totalAmount; ?>&order_id=<?php echo $order['order_id']; ?>" 
                         alt="UPI QR Code" class="qr-code">
                </div>
                <div class="upi-details">
                    <p><strong>UPI ID:</strong> rahulrajjadhav1816-2@okicici</p>
                    <p><strong>Name:</strong> Rahul Ananda Jadhav</p>
                </div>
                <form method="POST" onsubmit="return validatePayment()" class="payment-form">
                    <input type="hidden" name="payment_method" value="UPI">
                    <div class="input-group">
                        <label for="transaction_id">UPI ID</label>
                        <div class="transaction-input">
                            <i class="fas fa-receipt"></i>
                            <input type="text" 
                                   name="transaction_id" 
                                   id="transaction_id" 
                                   placeholder="e.g., UPI123XXX456YYY789"
                                   pattern="[A-Za-z0-9]+"
                                   minlength="12"
                                   maxlength="20"
                                   class="form-input"
                                   required
                                   style="text-transform: lowercase;">
                        </div>
                        <small class="input-help">Enter the UPI ID from your payment confirmation</small>
                    </div>
                    <button type="submit" class="payment-btn">
                        <i class="fas fa-check-circle"></i> Confirm Payment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function validatePayment() {
        const transactionId = document.getElementById('transaction_id').value.trim();
        
        if (!transactionId) {
            alert('Please enter the UPI Reference ID');
            return false;
        }
        
        if (!/^[A-Za-z0-9]{12,20}$/.test(transactionId)) {
            alert('Please enter a valid UPI Reference ID');
            return false;
        }
        
        return confirm('Confirm payment of ₹<?php echo number_format($totalAmount, 2); ?>/-?');
    }

    // Auto-format UPI Reference ID input
    document.getElementById('transaction_id').addEventListener('input', function(e) {
        // Remove special characters and spaces
        this.value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        
        // Limit length
        if (this.value.length > 20) {
            this.value = this.value.slice(0, 20);
        }
    });

    // Add copy UPI ID functionality
    document.querySelector('.upi-details').innerHTML += `
        <div class="copy-upi">
            <input type="text" value="rahulrajjadhav1816-2@okicici" id="upiId" readonly>
            <button onclick="copyUpiId()" class="copy-btn">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>`;

    function copyUpiId() {
        const upiId = document.getElementById('upiId');
        upiId.select();
        document.execCommand('copy');
        
        const copyBtn = document.querySelector('.copy-btn');
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        copyBtn.style.background = '#4CAF50';
        
        setTimeout(() => {
            copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
            copyBtn.style.background = '#512da8';
        }, 2000);
    }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html> 