<?php
require_once 'config/db.php';

function getOrders($userId) {
    global $pdo;
    
    try {
        // First get user's email
        $emailStmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
        $emailStmt->execute([$userId]);
        $userEmail = $emailStmt->fetchColumn();

        // Then get all orders for this user
        $stmt = $pdo->prepare("
            SELECT * FROM vehicle_orders 
            WHERE email = ? 
            ORDER BY order_date DESC
        ");
        
        $stmt->execute([$userEmail]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        return [];
    }
}

// Use in my_orders.php
if (isset($_SESSION['user_id'])) {
    $orders = getOrders($_SESSION['user_id']);
    return $orders;
} else {
    header("Location: index.php");
    exit();
}
?> 