<?php
require_once '../config/db.php';
header('Content-Type: application/json');

try {
    $startDate = $_POST['startDate'] ?? date('Y-m-d');
    $endDate = $_POST['endDate'] ?? date('Y-m-d');
    $reportType = $_POST['reportType'] ?? 'all';
    $userFilter = $_POST['userFilter'] ?? '';

    // Base conditions
    $conditions = ["DATE(order_date) BETWEEN ? AND ?"];
    $params = [$startDate, $endDate];

    if ($reportType !== 'all') {
        $conditions[] = "vehicle_type = ?";
        $params[] = $reportType;
    }

    if ($userFilter) {
        $conditions[] = "customer_name = ?";
        $params[] = $userFilter;
    }

    $whereClause = implode(" AND ", $conditions);

    // Get revenue and order stats
    $revenueQuery = "SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(rate), 0) as total_revenue
        FROM vehicle_orders 
        WHERE $whereClause AND status = 'Completed'";
    
    $stmt = $pdo->prepare($revenueQuery);
    $stmt->execute($params);
    $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get maintenance stats - using only vehicle type and date conditions
    $maintenanceConditions = ["DATE(maintenance_date) BETWEEN ? AND ?"];
    $maintenanceParams = [$startDate, $endDate];
    
    if ($reportType !== 'all') {
        $maintenanceConditions[] = "vehicle_type = ?";
        $maintenanceParams[] = $reportType;
    }
    
    $maintenanceWhereClause = implode(" AND ", $maintenanceConditions);
    
    $maintenanceQuery = "SELECT 
        COALESCE(SUM(total_cost), 0) as maintenance_cost
        FROM vehicle_maintenance 
        WHERE $maintenanceWhereClause";
    
    $stmt = $pdo->prepare($maintenanceQuery);
    $stmt->execute($maintenanceParams);
    $maintenanceStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get detailed orders
    $ordersQuery = "SELECT 
        order_id, 
        order_date, 
        customer_name, 
        location, 
        purpose, 
        rate, 
        status,
        vehicle_type
        FROM vehicle_orders 
        WHERE $whereClause
        ORDER BY order_date DESC";

    $stmt = $pdo->prepare($ordersQuery);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get maintenance records
    $maintenanceRecordsQuery = "SELECT 
        maintenance_date,
        vehicle_type,
        maintenance_type,
        parts_list,
        total_cost,
        notes
        FROM vehicle_maintenance 
        WHERE $maintenanceWhereClause
        ORDER BY maintenance_date DESC";

    $stmt = $pdo->prepare($maintenanceRecordsQuery);
    $stmt->execute($maintenanceParams);
    $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate net profit
    $totalRevenue = $revenueStats['total_revenue'];
    $maintenanceCost = $maintenanceStats['maintenance_cost'];
    $netProfit = $totalRevenue - $maintenanceCost;

    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => $totalRevenue,
            'total_orders' => $revenueStats['total_orders'],
            'maintenance_cost' => $maintenanceCost,
            'net_profit' => $netProfit,
            'orders' => $orders,
            'maintenance' => $maintenance
        ]
    ]);

} catch(Exception $e) {
    error_log("Report generation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => "Error generating report. Please try again."
    ]);
}
?> 