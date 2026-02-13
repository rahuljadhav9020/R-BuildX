<?php
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    // Get order statistics
    $orderStats = $pdo->query("SELECT 
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'Approved' AND payment_status = 'Pending' THEN 1 END) as awaiting_payment
    FROM vehicle_orders")->fetch(PDO::FETCH_ASSOC);

    // Get payment statistics
    $paymentStats = $pdo->query("SELECT 
        SUM(amount) as base_revenue,
        COUNT(*) as payment_count,
        SUM(CASE 
            WHEN amount >= 1000 THEN amount * 0.18 
            ELSE 0 
        END) as gst_collected
    FROM payments 
    WHERE payment_status = 'completed'")->fetch(PDO::FETCH_ASSOC);

    // Calculate total revenue (base + GST)
    $baseRevenue = floatval($paymentStats['base_revenue'] ?? 0);
    $gstCollected = floatval($paymentStats['gst_collected'] ?? 0);
    $totalRevenue = $baseRevenue + $gstCollected;

    // Get pending payments count
    $pendingPayments = $pdo->query("SELECT COUNT(*) as count 
        FROM vehicle_orders 
        WHERE status = 'Approved' 
        AND payment_status = 'Pending'")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'pending_count' => $orderStats['pending_count'] ?? 0,
        'completed_count' => $orderStats['completed_count'] ?? 0,
        'awaiting_payment' => $pendingPayments['count'] ?? 0,
        'base_revenue' => number_format($baseRevenue, 2),
        'gst_collected' => number_format($gstCollected, 2),
        'total_revenue' => number_format($totalRevenue, 2),
        'payment_count' => $paymentStats['payment_count'] ?? 0,
        'pending_payments' => $pendingPayments['count'] ?? 0
    ]);

} catch(Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Error fetching dashboard statistics',
        'pending_count' => 0,
        'completed_count' => 0,
        'awaiting_payment' => 0,
        'base_revenue' => '0.00',
        'gst_collected' => '0.00',
        'total_revenue' => '0.00',
        'payment_count' => 0,
        'pending_payments' => 0
    ]);
} 