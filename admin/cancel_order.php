<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    try {
        // Get order details first
        $orderStmt = $pdo->prepare("SELECT user_id, customer_name FROM vehicle_orders WHERE order_id = ?");
        $orderStmt->execute([$_POST['order_id']]);
        $order = $orderStmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        // Update order status to cancelled and set cancelled_date
        $stmt = $pdo->prepare("UPDATE vehicle_orders 
            SET status = 'Cancelled', 
                cancelled_date = CURRENT_TIMESTAMP 
            WHERE order_id = ?");
        $stmt->execute([$_POST['order_id']]);

        // Add notification for user
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) 
            VALUES (?, ?, 'order_cancelled')");
        $notifStmt->execute([
            $order['user_id'],
            "Your order #" . $_POST['order_id'] . " has been cancelled by admin."
        ]);

        // Log the cancellation
        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) 
            VALUES (?, 'order_cancelled', ?)");
        $logStmt->execute([
            $_SESSION['admin_id'],
            "Cancelled order #" . $_POST['order_id'] . " for " . $order['customer_name']
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error cancelling order: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?> 