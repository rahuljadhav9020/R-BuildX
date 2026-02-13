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
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total cost
    $sql = "SELECT SUM(total_cost) as total_cost 
            FROM vehicle_maintenance 
            $whereClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'records' => $records,
        'total_cost' => floatval($total['total_cost'] ?? 0)
    ]);

} catch (Exception $e) {
    error_log("Error in maintenance records: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error loading maintenance records']);
}
?> 