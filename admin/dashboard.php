<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle order approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_order'])) {
    $order_id = $_POST['order_id'];
    $completion_date = $_POST['completion_date'];
    $completion_time = $_POST['completion_time'];

    try {
        $pdo->beginTransaction();

        // Update order status
        $stmt = $pdo->prepare("UPDATE vehicle_orders SET 
            status = 'Approved',
            completion_date = ?,
            completion_time = ?,
            payment_status = 'Pending'
            WHERE order_id = ?");
        $stmt->execute([$completion_date, $completion_time, $order_id]);

        // Get order details for notification
        $stmt = $pdo->prepare("SELECT * FROM vehicle_orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        // Add notification
        $stmt = $pdo->prepare("INSERT INTO notifications (
            order_id, 
            message, 
            type
        ) VALUES (?, ?, 'order_approved')");
        
        $message = "Order #$order_id has been approved. Scheduled for " . 
                  date('d M Y', strtotime($completion_date)) . " at " . 
                  date('h:i A', strtotime($completion_time));
        
        $stmt->execute([$order_id, $message]);

        $pdo->commit();
        $_SESSION['success'] = "Order approved successfully!";
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    try {
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE vehicle_orders SET 
            status = 'Cancelled',
            payment_status = 'Cancelled'
            WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Add notification
        $stmt = $pdo->prepare("INSERT INTO notifications (
            order_id,
            message,
            type
        ) VALUES (?, ?, 'order_cancelled')");
        
        $message = "Order #$order_id has been cancelled by admin";
        $stmt->execute([$order_id, $message]);
        
        $pdo->commit();
        $_SESSION['success'] = "Order cancelled successfully";
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch orders by status
$stmt = $pdo->query("SELECT * FROM vehicle_orders WHERE status = 'Pending' ORDER BY order_date DESC");
$pending_orders = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM vehicle_orders WHERE status = 'Approved' ORDER BY completion_date ASC, completion_time ASC");
$approved_orders = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM vehicle_orders WHERE status = 'Completed' ORDER BY completion_date DESC, completion_time DESC");
$completed_orders = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM vehicle_orders WHERE status = 'Cancelled' ORDER BY order_date DESC");
$cancelled_orders = $stmt->fetchAll();

// Fetch statistics
$stats = $pdo->query("SELECT 
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'Approved' AND payment_status = 'Pending' THEN 1 END) as awaiting_payment,
    SUM(CASE WHEN status = 'Completed' THEN rate ELSE 0 END) as total_revenue
FROM vehicle_orders")->fetch(PDO::FETCH_ASSOC);

// Initialize variables with default values
$pending_count = $stats['pending_count'] ?? 0;
$completed_count = $stats['completed_count'] ?? 0;
$awaiting_payment = $stats['awaiting_payment'] ?? 0;
$total_revenue = $stats['total_revenue'] ?? 0;

// Fetch maintenance statistics
$maintenanceStats = $pdo->query("SELECT 
    COUNT(*) as total_maintenance_records,
    SUM(total_cost) as maintenance_cost 
FROM vehicle_maintenance")->fetch(PDO::FETCH_ASSOC);

$maintenanceCost = $maintenanceStats['maintenance_cost'] ?? 0;
$totalMaintenanceRecords = $maintenanceStats['total_maintenance_records'] ?? 0;

// Calculate net profit
$netProfit = $total_revenue - $maintenanceCost;

// Fetch vehicle-wise statistics
$vehicleStats = [];
$vehicles = ['Tractor', 'JCB', 'Tanker', 'Dumper'];

foreach ($vehicles as $vehicle) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_orders,
        SUM(rate) as revenue
    FROM vehicle_orders 
    WHERE vehicle_type = ? AND status = 'Completed'");
    
    $stmt->execute([$vehicle]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $vehicleStats[$vehicle] = [
        'orders' => $stats['total_orders'] ?? 0,
        'revenue' => $stats['revenue'] ?? 0
    ];
}

// Fetch payment details
$stmt = $pdo->query("SELECT 
    vo.*,
    vo.rate as base_amount,
    (vo.rate * 0.18) as gst_amount,
    (vo.rate * 1.18) as total_amount
    FROM vehicle_orders vo
    WHERE vo.status = 'Completed'
    ORDER BY vo.completion_date DESC, vo.completion_time DESC");
$completed_payments = $stmt->fetchAll();

// Fetch notifications
$stmt = $pdo->query("SELECT * FROM notifications 
    WHERE is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 5");
$notifications = $stmt->fetchAll();

// Count unread notifications
$stmt = $pdo->query("SELECT COUNT(*) as unread_count FROM notifications WHERE is_read = 0");
$notification_count = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM vehicle_orders 
    WHERE status = 'Completed'  
    ORDER BY order_date DESC");
$paid_orders = $stmt->fetchAll();

// Update the query to fetch awaiting payments
$stmt = $pdo->query("SELECT 
    vo.*,
    (vo.rate * 0.18) as gst_amount,
    (vo.rate * 1.18) as total_amount
    FROM vehicle_orders vo
    WHERE vo.status = 'Approved'
    ORDER BY vo.completion_date ASC, vo.completion_time ASC");
$awaiting_payments = $stmt->fetchAll();

// Fetch reports
$stmt = $pdo->query("SELECT * FROM reports");
$reports = $stmt->fetchAll();

// Fetch vehicle reports
$stmt = $pdo->query("SELECT * FROM vehicle_reports");
$vehicleReports = $stmt->fetchAll();

// Fetch total revenue and orders
$stmt = $pdo->query("SELECT SUM(rate) as total_revenue, COUNT(*) as total_orders FROM vehicle_orders");
$revenueStats = $stmt->fetch();

// Fetch maintenance cost and history
$stmt = $pdo->query("SELECT SUM(total_cost) as maintenance_cost, COUNT(*) as total_maintenance_records FROM vehicle_maintenance");
$maintenanceHistory = $stmt->fetchAll();

// Calculate overall statistics
$stmt = $pdo->query("
    SELECT 
        SUM(rate) as total_revenue,
        COUNT(*) as total_orders
    FROM vehicle_orders 
    WHERE status = 'Completed'
");
$revenueStats = $stmt->fetch();

$stmt = $pdo->query("
    SELECT SUM(total_cost) as maintenance_cost
    FROM vehicle_maintenance
");
$maintenanceStats = $stmt->fetch();

$totalRevenue = $revenueStats['total_revenue'] ?? 0;
$totalOrders = $revenueStats['total_orders'] ?? 0;
$maintenanceCost = $maintenanceStats['maintenance_cost'] ?? 0;
$netProfit = $totalRevenue - $maintenanceCost;

// Fetch payment statistics
$paymentStats = $pdo->query("SELECT 
    SUM(CASE WHEN status = 'Completed' THEN rate ELSE 0 END) as base_revenue,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_payments,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_payments
    FROM vehicle_orders")->fetch(PDO::FETCH_ASSOC);

// Fetch recent payments
$recentPayments = $pdo->query("SELECT 
    vo.order_id,
    vo.customer_name,
    vo.vehicle_type,
    vo.rate,
    vo.order_date,
    vo.status
    FROM vehicle_orders vo
    WHERE vo.status IN ('Completed', 'Pending')
    ORDER BY vo.order_date DESC
    LIMIT 10")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="../favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - R-BuildX</title>
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .welcome-section {
            background: linear-gradient(45deg, #512da8, #673ab7);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(81,45,168,0.2);
        }

        .welcome-section h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #512da8;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
        }

        .tab-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .tab {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            background: #f0f2f5;
            color: #666;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab.active {
            background: #512da8;
            color: white;
        }

        .tab:hover {
            background: #673ab7;
            color: white;
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        .orders-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .payment-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .payment-status.paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .payment-status.pending {
            background: #fff3e0;
            color: #e65100;
        }

        .revenue-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .summary-card h4 {
            color: #512da8;
            margin-bottom: 10px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary-card p {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .notifications-panel {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item p {
            margin: 0 0 5px 0;
            color: #333;
        }

        .notification-item small {
            color: #888;
        }

        .approve-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .approve-form input[type="date"],
        .approve-form input[type="time"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .approve-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .approve-btn:hover {
            background: #45a049;
        }

        /* Add validation styles */
        .approve-form input:invalid {
            border-color: #ff5252;
        }

        .approve-form input:valid {
            border-color: #4CAF50;
        }

        .cancel-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            .revenue-summary {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }

        .tab-navigation {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            padding: 0 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #f0f2f5;
            border: none;
            border-radius: 12px;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
        }

        .tab-btn i {
            font-size: 18px;
        }

        .tab-btn.active {
            background: #512da8;
            color: white;
        }

        .tab-btn:hover:not(.active) {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .order-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .order-section h3 {
            color: #512da8;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-section h3 i {
            font-size: 24px;
        }

        .order-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-btn {
            padding: 8px 20px;
            background: #512da8;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:not(:hover) {
            background:rgb(68, 42, 238);
        }
        .filter-btn:hover {
            background:rgb(168, 45, 45);
        }
        .filter-btn:active {
            background: #673ab7;
        }

        /* Navigation Styles */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            padding: 0;
            list-style: none;
            border-bottom: 2px solid #eee;
        }

        .nav-tabs li {
            margin-bottom: -2px;
        }

        .nav-tabs button {
            padding: 12px 24px;
            border: none;
            background: none;
            color: #666;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .nav-tabs button.active {
            color: #512da8;
            border-bottom: 2px solid #512da8;
        }

        .nav-tabs button:hover {
            color: #512da8;
        }

        .nav-tabs .badge {
            background: #ff4444;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .urgent {
            background-color: #fff3e0;
        }

        .remind-btn {
            background: #ffa726;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .remind-btn:hover {
            background: #fb8c00;
        }

        /* Add animation for urgent rows */
        @keyframes pulse {
            0% { background-color: #fff3e0; }
            50% { background-color: #ffe0b2; }
            100% { background-color: #fff3e0; }
        }

        .urgent {
            animation: pulse 2s infinite;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #512da8;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        .warning {
            color: #ff5252;
            font-size: 12px;
            margin-top: 5px;
        }

        .report-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .report-item {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }

        .report-divider {
            width: 1px;
            height: 80px;
            background: #e0e0e0;
        }

        .report-item h3 {
            color: #512da8;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .report-item p {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .report-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .report-filters select,
        .report-filters input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #512da8;
            color: white;
        }

        .btn-primary:hover {
            background: #673ab7;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        /* Loading animation */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .report-details {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .report-details h3 {
            color: #512da8;
            margin-bottom: 20px;
        }

        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .tab-btn {
            padding: 8px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: #512da8;
            color: white;
        }

        .detail-content {
            display: none;
        }

        .detail-content.active {
            display: block;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .report-table th,
        .report-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .report-table th {
            background: #f5f5f5;
            font-weight: 600;
        }

        .report-table tr:hover {
            background: #f9f9f9;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        .payment-stats-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-header i {
            font-size: 24px;
            color: #512da8;
        }

        .stat-header h3 {
            color: #512da8;
            font-size: 16px;
            margin: 0;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .recent-payments {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .recent-payments h3 {
            color: #512da8;
            margin-bottom: 20px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table th,
        .payment-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .payment-table th {
            background: #f5f5f5;
            font-weight: 600;
        }

        .payment-table tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .payment-filters {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #512da8;
            color: white;
            border-color: #512da8;
        }

        .stat-subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .text-muted {
            color: #666;
        }

        .reference-id {
            font-family: monospace;
            font-size: 12px;
            background: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .payment-row.pending {
            background: #fff8e1;
        }

        .payment-row.completed {
            background: #f1f8e9;
        }

        .maintenance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .maintenance-actions {
            margin-bottom: 20px;
        }

        .maintenance-form-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .maintenance-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        #fuelDetails {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 10px 0;
        }

        .fuel-details {
            grid-column: 1 / -1;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }

        .fuel-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .fuel-section-header i {
            color: #512da8;
            font-size: 20px;
        }

        .fuel-section-header h4 {
            color: #512da8;
            margin: 0;
            font-size: 16px;
        }

        .fuel-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon .currency,
        .input-with-icon .unit {
            position: absolute;
            color: #666;
            font-size: 14px;
        }

        .input-with-icon .currency {
            left: 10px;
        }

        .input-with-icon .unit {
            right: 10px;
        }

        .input-with-icon input {
            padding-left: 25px !important;
            padding-right: 25px !important;
        }

        .maintenance-form .form-group input,
        .maintenance-form .form-group select,
        .maintenance-form .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .maintenance-form .form-group input:focus,
        .maintenance-form .form-group select:focus,
        .maintenance-form .form-group textarea:focus {
            border-color: #512da8;
            outline: none;
        }

        .maintenance-form .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }

        .form-header h4 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #512da8;
            font-size: 18px;
        }

        .close-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }

        .close-btn:hover {
            color: #dc3545;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .maintenance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .maintenance-table th {
            background: #f8f9fa;
            padding: 15px;
            font-weight: 600;
            color: #333;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        .maintenance-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .maintenance-table tr:last-child td {
            border-bottom: none;
        }

        .maintenance-table tr:hover {
            background: #f8f9fa;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .btn-primary, .btn-secondary {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #512da8;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #673ab7;
        }

        .btn-secondary {
            background: white;
            color: #666;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #f5f5f5;
        }

        .profit-amount {
            transition: color 0.3s ease;
        }

        .profit-amount.profit {
            color: #2e7d32;  /* Green color for profit */
        }

        .profit-amount.loss {
            color: #d32f2f;  /* Red color for loss */
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            color: #666;
            font-weight: 500;
        }

        #userFilter {
            min-width: 200px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }

        #userFilter:focus {
            border-color: #512da8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(81, 45, 168, 0.1);
        }

        .report-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: #512da8;
            font-weight: 600;
            font-size: 14px;
        }

        .date-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-inputs span {
            color: #666;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .report-actions {
            display: flex;
            gap: 10px;
        }

        .report-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        .report-table th {
            background: #f8f9fa;
            padding: 15px;
            font-weight: 600;
            color: #333;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        .report-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .report-table tr:hover {
            background: #f8f9fa;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s ease;
        }

        .cancel-btn:hover {
            background: #c82333;
        }

        .cancel-btn i {
            font-size: 12px;
        }

        .approve-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .approve-form input[type="date"],
        .approve-form input[type="time"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .approve-form input[type="date"]:focus,
        .approve-form input[type="time"]:focus {
            border-color: #512da8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(81, 45, 168, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .approve-btn, .cancel-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .approve-btn {
            background: #28a745;
            color: white;
        }

        .approve-btn:hover {
            background: #218838;
        }

        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .customer-name {
            font-weight: 500;
            color: #333;
        }

        .customer-email {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }

        /* Adjust the table cell padding to accommodate the extra line */
        .orders-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .payment-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            color: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background:rgb(75, 13, 218);
        }
        

        .filter-btn.active {
            background: #512da8;
            color: white;
            border-color: #512da8;
        }

        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }

        .payment-table tr.no-data td {
            text-align: center;
            padding: 20px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .cancel-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .edit-btn {
            background: #512da8;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: #673ab7;
        }

        .edit-btn i {
            font-size: 12px;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-card h4 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }

        .summary-card p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .profit-amount.profit {
            color: #28a745;
        }

        .profit-amount.loss {
            color: #dc3545;
        }

        .report-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .date-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .print-actions {
            margin-top: 20px;
            text-align: right;
        }

        .print-btn {
            padding: 10px 20px;
            background: #512da8;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .print-btn:hover {
            background: #673ab7;
        }

        .maintenance-breakdown {
            padding-bottom: 15px;
        }

        .maintenance-item {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .total-maintenance {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .detail-tabs {
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .detail-tab {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            background: none;
            cursor: pointer;
            color: #666;
            font-weight: 600;
            position: relative;
        }

        .detail-tab.active {
            color: #512da8;
        }

        .detail-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #512da8;
        }

        .text-right {
            text-align: right;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
        }

        .maintenance-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .form-header h4 {
            color: #512da8;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #512da8;
            outline: none;
        }

        button[type="submit"] {
            background: #512da8;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        button[type="submit"]:hover {
            background: #673ab7;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
        }

        .close-btn:hover {
            color: #333;
        }

        .date-group {
            margin-bottom: 15px;
        }

        .date-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .date-group input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .date-group input[type="date"]:focus {
            border-color: #512da8;
            outline: none;
        }

        .maintenance-records {
            margin-top: 20px;
            overflow-x: auto;
        }

        .maintenance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .maintenance-table th,
        .maintenance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .maintenance-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .maintenance-table tr:hover {
            background: #f5f5f5;
        }

        .maintenance-table td button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .maintenance-table td button:hover {
            transform: translateY(-1px);
        }

        .maintenance-table td button.edit-btn {
            background: #512da8;
            color: white;
        }

        .maintenance-table td button.edit-btn:hover {
            background: #673ab7;
        }
    </style>
</head>
<body>
    <?php if (isset($success)): ?>
        <div class="notification" id="successNotification"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="dashboard">
        <div class="welcome-section">
            <h2>Welcome to R-BuildX Admin Dashboard</h2>
            <p>Manage your orders, track payments, and monitor business performance</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="number"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Completed Orders</h3>
                <div class="number"><?php echo $completed_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number">₹<?php echo number_format($total_revenue, 2); ?>/-</div>
            </div>
            <div class="stat-card">
                <h3>Awaiting Payment</h3>
                <div class="number"><?php echo $awaiting_payment; ?></div>
            </div>
        </div>

        <?php if ($notification_count['unread_count'] > 0): ?>
        <div class="notifications-panel">
            <h3>New Notifications (<?php echo $notification_count['unread_count']; ?>)</h3>
            <?php foreach ($notifications as $notification): ?>
            <div class="notification-item">
                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                <small><?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tab-container">
            <!-- Navigation Tabs -->
            <ul class="nav-tabs">
                <li>
                    <button class="active" onclick="showTab('pending')">
                        <i class="fas fa-clock"></i> Pending
                        <?php if(count($pending_orders) > 0): ?>
                            <span class="badge"><?php echo count($pending_orders); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li>
                    <button onclick="showTab('approved')">
                        <i class="fas fa-check"></i> Approved
                        <?php if(count($approved_orders) > 0): ?>
                            <span class="badge"><?php echo count($approved_orders); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li>
                    <button onclick="showTab('completed')">
                        <i class="fas fa-flag-checkered"></i> Completed
                    </button>
                </li>
                <li>
                    <button onclick="showTab('payments')">
                        <i class="fas fa-rupee-sign"></i> Payments
                    </button>
                </li>
                <li>
                    <button onclick="showTab('cancelled')">
                        <i class="fas fa-ban"></i> Cancelled
                    </button>
                </li>
                <li>
                    <button onclick="showTab('awaiting_payments')">
                        <i class="fas fa-hourglass-half"></i> Awaiting Payments
                        <?php if(count($awaiting_payments) > 0): ?>
                            <span class="badge"><?php echo count($awaiting_payments); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li>
                    <button onclick="showTab('reports')" class="nav-btn">
                        <i class="fas fa-chart-bar"></i> Reports
                        <span class="badge"><?php echo count($reports); ?></span>
                    </button>
                </li>
                <li>
                    <button onclick="showTab('maintenance')" class="nav-btn">
                        <i class="fas fa-tools"></i> Maintenance
                    </button>
                </li>
            </ul>

            <!-- Tab Contents -->
            <div id="pending" class="tab-content active">
                <div class="orders-container">
                    <?php if (empty($pending_orders)): ?>
                        <p class="no-orders">No pending orders</p>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Location</th>
                                    <th>Purpose</th>
                                    <th>Rate</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td>
                                            <div class="customer-info">
                                                <span class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></span>
                                                <span class="customer-email"><?= htmlspecialchars($order['email']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($order['vehicle_type']) ?></td>
                                        <td><?= htmlspecialchars($order['location']) ?></td>
                                        <td><?= htmlspecialchars($order['purpose']) ?></td>
                                        <td>₹<?= number_format($order['rate'], 2) ?>/-</td>
                                        <td><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></td>
                                        <td class="action-buttons">
                                            <form method="POST" class="approve-form" onsubmit="return validateApproval(this)">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                <input type="date" name="completion_date" required>
                                                <input type="time" name="completion_time" required>
                                                <button type="submit" name="approve_order" class="approve-btn">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <button onclick="cancelOrder(<?= $order['order_id'] ?>)" class="cancel-btn">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div id="approved" class="tab-content">
                <h3>Approved Orders (Awaiting Payment)</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Location</th>
                            <th>Purpose</th>
                            <th>Rate</th>
                            <th>Scheduled For</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <?php echo htmlspecialchars($order['mobile']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['vehicle_type']); ?></td>
                            <td><?php echo htmlspecialchars($order['location']); ?></td>
                            <td><?php echo htmlspecialchars($order['purpose']); ?></td>
                            <td>₹<?php echo number_format($order['rate'], 2); ?>/-</td>
                            <td><?php echo date('d M Y, h:i A', strtotime($order['completion_date'] . ' ' . $order['completion_time'])); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" name="cancel_order" class="cancel-btn">Cancel Order</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="completed" class="tab-content">
                <h3>Completed Orders</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Location</th>
                            <th>Purpose</th>
                            <th>Rate</th>
                            <th>Completion Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed_payments as $payment): ?>
                        <tr>
                            <td>#<?php echo $payment['order_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                                <?php echo htmlspecialchars($payment['mobile']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($payment['vehicle_type']); ?></td>
                            <td><?php echo htmlspecialchars($payment['location']); ?></td>
                            <td><?php echo htmlspecialchars($payment['purpose']); ?></td>
                            <td>₹<?php echo number_format($payment['base_amount'], 2); ?>/-</td>
                            <td><?php echo date('d M Y, h:i A', strtotime($payment['completion_date'] . ' ' . $payment['completion_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="payments" class="tab-content">
                <h2>Payment Statistics</h2>
                
                <div class="payment-stats-container">
                    <div class="payment-stat-card">
                        <div class="stat-header">
                            <i class="fas fa-money-bill-wave"></i>
                            <h3>BASE REVENUE</h3>
                        </div>
                        <p class="stat-value">₹<?= number_format($paymentStats['base_revenue'] ?? 0, 2) ?>/-</p>
                        <p class="stat-subtitle"><?= $paymentStats['completed_payments'] ?> payments received</p>
                    </div>

                    <div class="payment-stat-card">
                        <div class="stat-header">
                            <i class="fas fa-percentage"></i>
                            <h3>GST COLLECTED</h3>
                        </div>
                        <p class="stat-value">₹<?= number_format(($paymentStats['base_revenue'] ?? 0) * 0.18, 2) ?>/-</p>
                        <p class="stat-subtitle">18% GST</p>
                    </div>

                    <div class="payment-stat-card">
                        <div class="stat-header">
                            <i class="fas fa-chart-line"></i>
                            <h3>TOTAL REVENUE</h3>
                        </div>
                        <p class="stat-value">₹<?= number_format(($paymentStats['base_revenue'] ?? 0) * 1.18, 2) ?>/-</p>
                        <p class="stat-subtitle"><?= $paymentStats['pending_payments'] ?> payments pending</p>
                    </div>
                </div>

                <div class="recent-payments">
                    <div class="section-header">
                        <h3>Recent Payments</h3>
                        <div class="payment-filters">
                            <button class="filter-btn active" onclick="filterPayments('all')">All</button>
                            <button class="filter-btn" onclick="filterPayments('Completed')">Completed</button>
                            <button class="filter-btn" onclick="filterPayments('Pending')">Pending</button>
                        </div>
                    </div>

                    <div class="payment-list">
                        <?php if (empty($recentPayments)): ?>
                            <p class="no-data">No payment records found</p>
                        <?php else: ?>
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <tr class="payment-row <?= strtolower($payment['status']) ?>">
                                            <td>#<?= $payment['order_id'] ?></td>
                                            <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                            <td><?= $payment['vehicle_type'] ?></td>
                                            <td>₹<?= number_format($payment['rate'], 2) ?>/-</td>
                                            <td><?= date('d M Y', strtotime($payment['order_date'])) ?></td>
                                            <td>
                                                <span class="status-badge <?= strtolower($payment['status']) ?>">
                                                    <?= $payment['status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="cancelled" class="tab-content">
                <div class="orders-container">
                    <?php if (empty($cancelled_orders)): ?>
                        <p class="no-orders">No cancelled orders</p>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Location</th>
                                    <th>Purpose</th>
                                    <th>Rate</th>
                                    <th>Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cancelled_orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td>
                                            <div class="customer-info">
                                                <span class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></span>
                                                <span class="customer-email"><?= htmlspecialchars($order['email']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($order['vehicle_type']) ?></td>
                                        <td><?= htmlspecialchars($order['location']) ?></td>
                                        <td><?= htmlspecialchars($order['purpose']) ?></td>
                                        <td>₹<?= number_format($order['rate'], 2) ?>/-</td>
                                        <td><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></td>
                                        
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div id="awaiting_payments" class="tab-content">
                <h3>Awaiting Payments</h3>
                <div class="revenue-summary">
                    <div class="summary-card">
                        <h4>Pending Amount</h4>
                        <p>₹<?php echo number_format(array_sum(array_column($awaiting_payments, 'total_amount')), 2); ?>/-</p>
                    </div>
                    <div class="summary-card">
                        <h4>Pending Orders</h4>
                        <p><?php echo count($awaiting_payments); ?></p>
                    </div>
                </div>

                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Base Amount</th>
                            <th>GST (18%)</th>
                            <th>Total Amount</th>
                            <th>Scheduled Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($awaiting_payments as $order): ?>
                        <tr class="<?php echo strtotime($order['completion_date']) <= strtotime('+24 hours') ? 'urgent' : ''; ?>">
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <?php echo htmlspecialchars($order['mobile']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['vehicle_type']); ?></td>
                            <td>₹<?php echo number_format($order['rate'], 2); ?>/-</td>
                            <td>₹<?php echo number_format($order['gst_amount'], 2); ?>/-</td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?>/-</td>
                            <td><?php echo date('d M Y, h:i A', strtotime($order['completion_date'] . ' ' . $order['completion_time'])); ?></td>
                            <td>
                                <span class="payment-status pending">Payment Pending</span>
                            </td>
                            <td>
                                <button class="remind-btn" onclick="sendReminder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-bell"></i> Send Reminder
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="reports" class="tab-content">
                <div class="reports-section">
                    <div class="reports-header">
                        <h3>Generate Report</h3>
                        <div class="report-filters">
                            <div class="filter-group">
                                <label for="vehicleFilter">Vehicle Type:</label>
                                <select id="vehicleFilter" onchange="generateReport()">
                                    <option value="all">All Vehicles</option>
                                    <option value="Tractor">Tractor</option>
                                    <option value="JCB">JCB</option>
                                    <option value="Tanker">Water Tanker</option>
                                    <option value="Dumper">Dumper</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Date Range:</label>
                                <div class="date-inputs">
                                    <input type="date" id="startDate" onchange="generateReport()">
                                    <span>to</span>
                                    <input type="date" id="endDate" onchange="generateReport()">
                                </div>
                            </div>
                            <div class="filter-group">
                                <button onclick="generateReport()" class="btn-primary">
                                    <i class="fas fa-sync"></i> Generate Report
                                </button>
                                <button onclick="printReport()" class="btn-secondary">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="report-summary">
                        <div class="summary-card">
                            <h4>TOTAL REVENUE</h4>
                            <p id="totalRevenue">₹0.00/-</p>
                        </div>
                        <div class="summary-card">
                            <h4>TOTAL ORDERS</h4>
                            <p id="totalOrders">0</p>
                        </div>
                        <div class="summary-card">
                            <h4>MAINTENANCE COST</h4>
                            <p id="maintenanceCost">₹0.00/-</p>
                        </div>
                        <div class="summary-card">
                            <h4>NET PROFIT</h4>
                            <p id="netProfit" class="profit-amount">₹0.00/-</p>
                        </div>
                    </div>

                    <div class="report-details">
                        <div id="ordersDetail" class="detail-content active">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Location</th>
                                        <th>Purpose</th>
                                        <th>Rate</th>
                                        <th>Status</th>
                                        <th>Order Date</th>
                                    </tr>
                                </thead>
                                <tbody id="orderTableBody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <div id="maintenanceDetail" class="detail-content">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Vehicle</th>
                                        <th>Type</th>
                                        <th>Parts</th>
                                        <th>Cost</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="maintenanceTableBody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total Maintenance Cost:</strong></td>
                                        <td colspan="2"><strong id="totalMaintenanceCost">₹0.00/-</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="maintenance" class="tab-content">
                <h3>Vehicle Maintenance</h3>
                
                <div class="maintenance-stats">
                    <div class="stat-card">
                        <h4>Total Maintenance Cost</h4>
                        <p>₹<?= number_format($maintenanceCost, 2) ?>/-</p>
                    </div>
                    <div class="stat-card">
                        <h4>Total Records</h4>
                        <p><?= $totalMaintenanceRecords ?></p>
                    </div>
                </div>

                <div class="maintenance-actions">
                    <button onclick="showMaintenanceForm()" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Maintenance Record
                    </button>
                </div>

                <!-- Add Maintenance Form -->
                <div id="maintenanceForm" class="maintenance-form-container" style="display: none;">
                    <div class="form-header">
                        <h4><i class="fas fa-tools"></i> Add New Maintenance Record</h4>
                        <button type="button" onclick="hideMaintenanceForm()" class="close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST" action="add_maintenance.php" class="maintenance-form" id="maintenanceForm" onsubmit="return validateMaintenanceForm()">
                        <input type="hidden" name="record_id">
                        <input type="hidden" name="maintenance_date" value="<?php echo date('Y-m-d'); ?>">
                        
                        <div class="form-group">
                            <label for="vehicle_type">Vehicle Type*</label>
                            <select name="vehicle_type" required>
                                <option value="">Select Vehicle</option>
                                <option value="Tractor">Tractor</option>
                                <option value="JCB">JCB</option>
                                <option value="Tanker">Water Tanker</option>
                                <option value="Dumper">Dumper</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="maintenance_type">Maintenance Type*</label>
                            <select name="maintenance_type" required onchange="toggleFuelDetails(this.value)">
                                <option value="">Select Type</option>
                                <option value="Fuel">Fuel</option>
                                <option value="Regular Service">Regular Service</option>
                                <option value="Repair">Repair</option>
                            </select>
                        </div>

                        <div id="fuelDetails" style="display: none;">
                            <div class="form-group">
                                <label for="fuel_type">Fuel Type*</label>
                                <select name="fuel_type">
                                    <option value="Diesel">Diesel</option>
                                    <option value="Petrol">Petrol</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fuel_quantity">Quantity (Liters)*</label>
                                <input type="number" step="0.01" name="fuel_quantity" onchange="calculateFuelCost()">
                            </div>
                            <div class="form-group">
                                <label for="fuel_price">Price per Liter*</label>
                                <input type="number" step="0.01" name="fuel_price" onchange="calculateFuelCost()">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="parts_list">Parts List</label>
                            <textarea name="parts_list" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="total_cost">Total Cost*</label>
                            <input type="number" step="0.01" name="total_cost" required>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" rows="3"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="save-btn">
                                <i class="fas fa-save"></i> Save Record
                            </button>
                            <button type="button" class="cancel-btn" onclick="cancelForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Maintenance Records Table -->
                <div class="maintenance-records">
                    <h4>Recent Maintenance Records</h4>
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Parts</th>
                                <th>Cost</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maintenanceRecords = $pdo->query("SELECT * FROM vehicle_maintenance 
                                ORDER BY maintenance_date DESC 
                                LIMIT 10")->fetchAll();

                            if (empty($maintenanceRecords)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">No maintenance records found</td>
                                </tr>
                            <?php else:
                                foreach ($maintenanceRecords as $record): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($record['maintenance_date'])) ?></td>
                                        <td><?= htmlspecialchars($record['vehicle_type']) ?></td>
                                        <td><?= htmlspecialchars($record['maintenance_type']) ?></td>
                                        <td><?= htmlspecialchars($record['parts_list'] ?: '-') ?></td>
                                        <td>₹<?= number_format($record['total_cost'], 2) ?>/-</td>
                                        <td><?= htmlspecialchars($record['notes'] ?: '-') ?></td>
                                        <td>
                                            <button class="edit-btn" onclick="editMaintenanceRecord(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- After the maintenance records table -->
                <div class="total-maintenance">
                    <strong>Total Maintenance Cost:</strong>
                    <?php
                    $stmt = $pdo->query("SELECT SUM(total_cost) as total FROM vehicle_maintenance");
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    echo '₹' . number_format($total, 2) . '/-';
                    ?>
                </div>
            </div>
        </div>

        <p><a href="logout.php" style="color: #512da8; text-decoration: none; margin-top: 20px; display: inline-block;">Logout</a></p>
    </div>

    <script>
    function showTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.nav-tabs button').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected tab
        document.getElementById(tabId).classList.add('active');
        document.querySelector(`button[onclick="showTab('${tabId}')"]`).classList.add('active');
    }

    // Show notification if exists
    const notification = document.getElementById('successNotification');
    if (notification) {
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 500);
        }, 3000);
    }

    function sendReminder(orderId) {
        // You can implement SMS/Email reminder functionality here
        alert('Reminder will be sent for Order #' + orderId);
    }

    // Highlight urgent payments (scheduled within 24 hours)
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        document.querySelectorAll('#awaiting_payments tr').forEach(row => {
            const scheduledDate = new Date(row.querySelector('td:nth-child(7)').textContent);
            const hoursDiff = (scheduledDate - now) / (1000 * 60 * 60);
            if(hoursDiff <= 24 && hoursDiff > 0) {
                row.classList.add('urgent');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const datePickers = document.querySelectorAll('input[type="date"]');
        const timePickers = document.querySelectorAll('input[type="time"]');
        
        function getMinTime() {
            const now = new Date();
            const hour = now.getHours();
            const nextHour = (hour + 1) % 24;
            return `${String(nextHour).padStart(2, '0')}:00`;
        }
        
        datePickers.forEach((datePicker, index) => {
            datePicker.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                selectedDate.setHours(0, 0, 0, 0);
                
                if (selectedDate.getTime() === today.getTime()) {
                    // If today is selected, set min time to next hour
                    const minTime = getMinTime();
                    timePickers[index].min = minTime;
                    
                    if (timePickers[index].value < minTime) {
                        timePickers[index].value = minTime;
                    }
                } else {
                    // If future date is selected, reset to 00:00
                    timePickers[index].min = '';
                    timePickers[index].value = '00:00';
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Update stats every 5 minutes
        setInterval(function() {
            fetch('get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update the stats display
                    document.querySelector('.pending-count').textContent = data.pending_count;
                    document.querySelector('.completed-count').textContent = data.completed_count;
                    document.querySelector('.total-revenue').textContent = '₹' + data.total_revenue.toFixed(2) + '/-';
                    document.querySelector('.awaiting-payment').textContent = data.awaiting_payment;
                });
        }, 300000); // 5 minutes
    });

    function showDetails(type) {
        // Update active tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Show selected content
        document.querySelectorAll('.detail-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(type + '-details').classList.add('active');
    }

    // Remove the duplicate generateReport() function and keep this updated version
    function generateReport() {
        const vehicleFilter = document.getElementById('vehicleFilter').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        // Validate dates
        if (!startDate || !endDate) {
            showNotification('Please select both start and end dates', 'error');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            showNotification('Start date cannot be after end date', 'error');
            return;
        }

        // Show loading state
        const reportContent = document.querySelector('.report-details');
        reportContent.style.opacity = '0.5';
        reportContent.style.pointerEvents = 'none';

        // Show loading indicator
        document.getElementById('totalRevenue').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        document.getElementById('totalOrders').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        document.getElementById('maintenanceCost').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        document.getElementById('netProfit').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('get_report_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `vehicle=${encodeURIComponent(vehicleFilter)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load report data');
            }

            // Update summary statistics
            document.getElementById('totalRevenue').textContent = '₹' + data.total_revenue.toFixed(2) + '/-';
            document.getElementById('totalOrders').textContent = data.total_orders;
            document.getElementById('maintenanceCost').textContent = '₹' + data.maintenance_cost.toFixed(2) + '/-';
            
            const netProfit = data.net_profit;
            const profitElement = document.getElementById('netProfit');
            profitElement.textContent = '₹' + Math.abs(netProfit).toFixed(2) + '/-';
            profitElement.className = 'profit-amount ' + (netProfit >= 0 ? 'profit' : 'loss');
            if (netProfit < 0) {
                profitElement.textContent = '-' + profitElement.textContent;
            }

            // Update tables
            updateOrdersTable(data.orders);
            updateMaintenanceTable(data.maintenance);

            // Restore normal state
            reportContent.style.opacity = '1';
            reportContent.style.pointerEvents = 'auto';
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(error.message || 'Error loading report data', 'error');
            reportContent.style.opacity = '1';
            reportContent.style.pointerEvents = 'auto';
        });
    }

    function updateOrdersTable(orders) {
        const tbody = document.getElementById('orderTableBody');
        tbody.innerHTML = '';

        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="no-data">No orders found for selected period</td></tr>';
            return;
        }

        orders.forEach(order => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>#${order.order_id}</td>
                <td>${escapeHtml(order.customer_name)}</td>
                <td>${escapeHtml(order.vehicle_type)}</td>
                <td>${escapeHtml(order.location)}</td>
                <td>${escapeHtml(order.purpose)}</td>
                <td>₹${parseFloat(order.rate).toFixed(2)}/-</td>
                <td>${escapeHtml(order.status)}</td>
                <td>${formatDate(order.order_date)}</td>
            `;
            tbody.appendChild(row);
        });
    }

    function updateMaintenanceTable(maintenance) {
        const tbody = document.getElementById('maintenanceTableBody');
        tbody.innerHTML = '';

        if (!maintenance || maintenance.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data">No maintenance records found for selected period</td></tr>';
            return;
        }

        let totalCost = 0;
        maintenance.forEach(record => {
            totalCost += parseFloat(record.total_cost);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${formatDate(record.maintenance_date)}</td>
                <td>${escapeHtml(record.vehicle_type)}</td>
                <td>${escapeHtml(record.maintenance_type)}</td>
                <td>${escapeHtml(record.parts_list || '-')}</td>
                <td>₹${parseFloat(record.total_cost).toFixed(2)}/-</td>
                <td>${escapeHtml(record.notes || '-')}</td>
            `;
            tbody.appendChild(row);
        });

        // Update total maintenance cost in footer if it exists
        const totalElement = document.getElementById('totalMaintenanceCost');
        if (totalElement) {
            totalElement.textContent = `₹${totalCost.toFixed(2)}/-`;
        }
    }

    // Helper functions
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }, 100);
    }

    function validateApproval(form) {
        const date = form.completion_date.value;
        const time = form.completion_time.value;
        
        if (!date || !time) {
            alert('Please select both date and time');
            return false;
        }

        const selectedDateTime = new Date(date + ' ' + time);
        const now = new Date();

        if (selectedDateTime < now) {
            alert('Please select a future date and time');
            return false;
        }

        return true;
    }

    function filterPayments(status) {
        // Update active button
        document.querySelectorAll('.payment-filters .filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
    
        // Filter payment rows
        const paymentRows = document.querySelectorAll('.payment-table tbody tr');
        paymentRows.forEach(row => {
            const paymentStatus = row.querySelector('.status-badge').textContent.trim();
            if (status === 'all' || paymentStatus === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    
        // Show "No payments" message if no visible rows
        const visibleRows = Array.from(paymentRows).filter(row => row.style.display !== 'none');
        const tbody = document.querySelector('.payment-table tbody');
        const noDataMessage = tbody.querySelector('.no-data');
    
        if (visibleRows.length === 0) {
            if (!noDataMessage) {
                const messageRow = document.createElement('tr');
                messageRow.className = 'no-data';
                messageRow.innerHTML = `<td colspan="6">No ${status.toLowerCase()} payments found</td>`;
                tbody.appendChild(messageRow);
            }
        } else if (noDataMessage) {
            noDataMessage.remove();
        }
    }

    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order?')) {
            // Show loading state
            const cancelBtn = event.target.closest('.cancel-btn');
            const originalText = cancelBtn.innerHTML;
            cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
            cancelBtn.disabled = true;

            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Move the order row to cancelled tab
                    const orderRow = cancelBtn.closest('tr');
                    const cancelledTab = document.querySelector('#cancelled .orders-table tbody');
                    if (cancelledTab) {
                        cancelledTab.prepend(orderRow);
                    }
                    // Update the pending count
                    const pendingCount = document.querySelector('.pending-count');
                    if (pendingCount) {
                        pendingCount.textContent = parseInt(pendingCount.textContent) - 1;
                    }
                    // Show success message
                    showNotification('Order cancelled successfully', 'success');
                } else {
                    showNotification(data.error || 'Error cancelling order', 'error');
                    // Reset button state
                    cancelBtn.innerHTML = originalText;
                    cancelBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error cancelling order', 'error');
                // Reset button state
                cancelBtn.innerHTML = originalText;
                cancelBtn.disabled = false;
            });
        }
    }

    function editMaintenanceRecord(id) {
        fetch(`get_maintenance_record.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const record = data.data;
                    const form = document.getElementById('maintenanceForm');
                    
                    // Fill form with record data
                    form.querySelector('input[name="record_id"]').value = record.id;
                    form.querySelector('select[name="vehicle_type"]').value = record.vehicle_type;
                    form.querySelector('select[name="maintenance_type"]').value = record.maintenance_type;
                    form.querySelector('input[name="maintenance_date"]').value = record.maintenance_date;
                    form.querySelector('textarea[name="parts_list"]').value = record.parts_list || '';
                    form.querySelector('input[name="total_cost"]').value = record.total_cost;
                    form.querySelector('textarea[name="notes"]').value = record.notes || '';

                    // Show form
                    showMaintenanceForm();
                    
                    // Update form title
                    document.querySelector('.form-title').textContent = 'Edit Maintenance Record';
                } else {
                    showNotification('Error loading record', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading record', 'error');
            });
    }

    function showMaintenanceForm() {
        document.querySelector('.maintenance-form').style.display = 'block';
        document.querySelector('.form-title').textContent = '+ Add New Maintenance Record';
        document.getElementById('maintenanceForm').reset();
    }

    function hideMaintenanceForm() {
        document.querySelector('.maintenance-form').style.display = 'none';
        document.getElementById('maintenanceForm').reset();
    }

    // Set default dates when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Set end date to today
        const today = new Date();
        const endDate = today.toISOString().split('T')[0];
        document.getElementById('endDate').value = endDate;
    
        // Set start date to 30 days ago
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 30);
        document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
    
        // Generate initial report
        generateReport();
    });

    function showDetailTab(tab, button) {
        // Hide all tabs
        document.querySelectorAll('.detail-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.detail-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tab + 'Detail').classList.add('active');
        button.classList.add('active');

        if (tab === 'maintenance') {
            loadMaintenanceRecords();
        }
    }

    function loadMaintenanceRecords() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const vehicleFilter = document.getElementById('vehicleFilter').value;

        // Show loading state
        const maintenanceTable = document.getElementById('maintenanceDetail');
        maintenanceTable.style.opacity = '0.5';
        maintenanceTable.style.pointerEvents = 'none';

        fetch('get_maintenance_records.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `start_date=${startDate}&end_date=${endDate}&vehicle=${vehicleFilter}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load maintenance records');
            }

            const tableBody = document.getElementById('maintenanceTableBody');
            tableBody.innerHTML = '';

            if (data.records.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="no-data">No maintenance records found</td>
                    </tr>`;
            } else {
                data.records.forEach(record => {
                    tableBody.innerHTML += `
                        <tr>
                            <td>${formatDate(record.maintenance_date)}</td>
                            <td>${record.vehicle_type}</td>
                            <td>${record.maintenance_type}</td>
                            <td>${record.parts_list || '-'}</td>
                            <td>₹${parseFloat(record.total_cost).toFixed(2)}/-</td>
                            <td>${record.notes || '-'}</td>
                            <td>
                                <button class="edit-btn" onclick="editMaintenanceRecord(<?php echo $record['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>`;
                });
            }

            // Update total maintenance cost
            document.getElementById('totalMaintenanceCost').textContent = 
                '₹' + data.total_cost.toFixed(2) + '/-';

            // Restore normal state
            maintenanceTable.style.opacity = '1';
            maintenanceTable.style.pointerEvents = 'auto';
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(error.message || 'Error loading maintenance records', 'error');
            maintenanceTable.style.opacity = '1';
            maintenanceTable.style.pointerEvents = 'auto';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('maintenance_date').value = today;
    });

    function closeMaintenanceForm() {
        const form = document.getElementById('maintenanceForm');
        form.style.display = 'none';
    }

    function validateMaintenanceForm() {
        const form = document.getElementById('maintenanceForm');
        const date = form.querySelector('[name="maintenance_date"]').value;
        
        if (!date) {
            showNotification('Please select a maintenance date', 'error');
            return false;
        }
        
        // Rest of your validation logic
        return true;
    }

    function toggleFuelDetails(type) {
        const fuelDetails = document.getElementById('fuelDetails');
        fuelDetails.style.display = type === 'Fuel' ? 'block' : 'none';
        
        if (type === 'Fuel') {
            document.querySelector('[name="fuel_type"]').required = true;
            document.querySelector('[name="fuel_quantity"]').required = true;
            document.querySelector('[name="fuel_price"]').required = true;
        } else {
            document.querySelector('[name="fuel_type"]').required = false;
            document.querySelector('[name="fuel_quantity"]').required = false;
            document.querySelector('[name="fuel_price"]').required = false;
        }
    }

    function calculateFuelCost() {
        const quantity = parseFloat(document.querySelector('[name="fuel_quantity"]').value) || 0;
        const price = parseFloat(document.querySelector('[name="fuel_price"]').value) || 0;
        document.querySelector('[name="total_cost"]').value = (quantity * price).toFixed(2);
    }

    function validateMaintenanceForm() {
        const form = document.getElementById('maintenanceForm');
        const maintenanceType = form.querySelector('[name="maintenance_type"]').value;
        
        if (maintenanceType === 'Fuel') {
            const quantity = form.querySelector('[name="fuel_quantity"]').value;
            const price = form.querySelector('[name="fuel_price"]').value;
            
            if (!quantity || !price) {
                showNotification('Please fill in all fuel details', 'error');
                return false;
            }
        }
        
        if (!form.querySelector('[name="total_cost"]').value) {
            showNotification('Please enter the total cost', 'error');
            return false;
        }
        
        return true;
    }

    function cancelForm() {
        const form = document.getElementById('maintenanceForm');
        form.reset();
        document.getElementById('fuelDetails').style.display = 'none';
    }
    </script>
</body>
</html> 