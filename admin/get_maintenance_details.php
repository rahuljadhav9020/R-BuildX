<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

try {
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $vehicle = $_POST['vehicle'] ?? '';

    $params = [];
    $whereConditions = [];

    if ($startDate) {
        $whereConditions[] = "DATE(maintenance_date) >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $whereConditions[] = "DATE(maintenance_date) <= ?";
        $params[] = $endDate;
    }
    if ($vehicle) {
        $whereConditions[] = "vehicle_type = ?";
        $params[] = $vehicle;
    }

    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get maintenance records
    $sql = "SELECT * FROM vehicle_maintenance 
            $whereClause 
            ORDER BY maintenance_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get vehicle-specific statistics
    $statsSQL = "SELECT 
        (SELECT SUM(total_cost) 
         FROM vehicle_maintenance 
         $whereClause) as maintenance_cost,
        (SELECT SUM(rate) 
         FROM vehicle_orders 
         WHERE status = 'Completed' 
         " . ($vehicle ? "AND vehicle_type = ?" : "") . "
         " . ($startDate ? "AND DATE(completion_date) >= ?" : "") . "
         " . ($endDate ? "AND DATE(completion_date) <= ?" : "") . "
        ) as revenue";
    
    $statsParams = $vehicle ? array_merge([$vehicle], array_filter([$startDate, $endDate])) : array_filter([$startDate, $endDate]);
    $stmt = $pdo->prepare($statsSQL);
    $stmt->execute($statsParams);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'maintenance' => $maintenance,
        'vehicle_maintenance_cost' => floatval($stats['maintenance_cost'] ?? 0),
        'vehicle_revenue' => floatval($stats['revenue'] ?? 0)
    ]);

} catch (Exception $e) {
    error_log("Error in maintenance details: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error loading maintenance details']);
}
?> 