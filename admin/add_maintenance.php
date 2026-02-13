<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare base data
        $data = [
            'vehicle_type' => $_POST['vehicle_type'],
            'maintenance_type' => $_POST['maintenance_type'],
            'maintenance_date' => $_POST['maintenance_date'],
            'total_cost' => $_POST['total_cost'],
            'parts_list' => $_POST['parts_list'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];

        // Special handling for fuel records
        if ($_POST['maintenance_type'] === 'Fuel') {
            if (empty($_POST['fuel_type']) || empty($_POST['fuel_quantity']) || empty($_POST['fuel_price'])) {
                throw new Exception("Please fill in all fuel details");
            }

            $data['notes'] = sprintf(
                "Fuel Type: %s Quantity: %.2f L Price per Liter: ₹%.2f",
                $_POST['fuel_type'],
                floatval($_POST['fuel_quantity']),
                floatval($_POST['fuel_price'])
            );
        }

        // Begin transaction
        $pdo->beginTransaction();

        if (!empty($_POST['record_id'])) {
            // Update existing record
            $sql = "UPDATE vehicle_maintenance SET 
                    vehicle_type = :vehicle_type,
                    maintenance_type = :maintenance_type,
                    maintenance_date = :maintenance_date,
                    parts_list = :parts_list,
                    total_cost = :total_cost,
                    notes = :notes
                    WHERE id = :id";
            $data['id'] = $_POST['record_id'];
        } else {
            // Insert new record
            $sql = "INSERT INTO vehicle_maintenance 
                    (vehicle_type, maintenance_type, maintenance_date, parts_list, total_cost, notes)
                    VALUES 
                    (:vehicle_type, :maintenance_type, :maintenance_date, :parts_list, :total_cost, :notes)";
        }

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($data);

        if (!$success) {
            throw new Exception("Failed to save maintenance record");
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = !empty($_POST['record_id']) ? 
            'Maintenance record updated successfully!' : 
            'Maintenance record added successfully!';
        
        header('Location: dashboard.php');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Maintenance record error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header('Location: dashboard.php');
        exit;
    }
}

// If not POST request, redirect to dashboard
header('Location: dashboard.php');
exit;
?> 