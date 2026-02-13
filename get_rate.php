<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        if (empty($_POST['vehicle']) || empty($_POST['location']) || empty($_POST['purpose'])) {
            throw new Exception('Missing required parameters');
        }

        $stmt = $pdo->prepare("SELECT rate FROM rental_rates 
                              WHERE vehicle_type = :vehicle 
                              AND location = :location 
                              AND purpose = :purpose");
        
        $stmt->execute([
            ':vehicle' => $_POST['vehicle'],
            ':location' => $_POST['location'],
            ':purpose' => $_POST['purpose']
        ]);

        $rate = $stmt->fetchColumn();
        
        if ($rate !== false) {
            echo json_encode(['success' => true, 'rate' => $rate]);
        } else {
            // Check if the rate exists for this combination
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM rental_rates 
                                      WHERE vehicle_type = :vehicle 
                                      AND location = :location 
                                      AND purpose = :purpose");
            
            $checkStmt->execute([
                ':vehicle' => $_POST['vehicle'],
                ':location' => $_POST['location'],
                ':purpose' => $_POST['purpose']
            ]);
            
            $exists = $checkStmt->fetchColumn();
            
            if ($exists == 0) {
                echo json_encode(['success' => false, 'message' => 'Rate not available for this combination']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error retrieving rate']);
            }
        }
    } catch(PDOException $e) {
        error_log('Database Error: ' . $e->getMessage()); // Log the error
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch(Exception $e) {
        error_log('General Error: ' . $e->getMessage()); // Log the error
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 