<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

try {
    $vehicle = $_POST['vehicle'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    $params = [];
    $whereConditions = [];

    // Add vehicle filter
    if ($vehicle && $vehicle !== 'all') {
        $whereConditions[] = "vo.vehicle_type = ?";
        $params[] = $vehicle;
    }

    // Add date range filter
    if ($startDate && $endDate) {
        $whereConditions[] = "DATE(vo.order_date) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get orders
    $sql = "SELECT * FROM vehicle_orders vo $whereClause ORDER BY vo.order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $sql = "SELECT 
        SUM(CASE WHEN status = 'Completed' THEN rate ELSE 0 END) as total_revenue,
        COUNT(*) as total_orders
        FROM vehicle_orders vo
        $whereClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get maintenance costs with same filters
    $maintenanceConditions = [];
    $maintenanceParams = [];

    if ($vehicle && $vehicle !== 'all') {
        $maintenanceConditions[] = "vehicle_type = ?";
        $maintenanceParams[] = $vehicle;
    }

    if ($startDate && $endDate) {
        $maintenanceConditions[] = "DATE(maintenance_date) BETWEEN ? AND ?";
        $maintenanceParams[] = $startDate;
        $maintenanceParams[] = $endDate;
    }

    $maintenanceWhereClause = $maintenanceConditions ? 'WHERE ' . implode(' AND ', $maintenanceConditions) : '';
    
    $maintenanceSQL = "SELECT * FROM vehicle_maintenance $maintenanceWhereClause";
    $stmt = $pdo->prepare($maintenanceSQL);
    $stmt->execute($maintenanceParams);
    $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total maintenance cost
    $maintenanceCost = array_sum(array_column($maintenance, 'total_cost'));

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total_revenue' => floatval($stats['total_revenue'] ?? 0),
        'total_orders' => intval($stats['total_orders'] ?? 0),
        'maintenance_cost' => floatval($maintenanceCost),
        'maintenance' => $maintenance,
        'net_profit' => floatval(($stats['total_revenue'] ?? 0) - $maintenanceCost)
    ]);

} catch (Exception $e) {
    error_log("Error in report generation: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error generating report']);
}
?> 