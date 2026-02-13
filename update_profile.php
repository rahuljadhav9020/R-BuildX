<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'user_id' => $_SESSION['user_id']
        ];

        // Start with basic validation
        if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Validate phone number format
        if (!preg_match('/^[789]\d{9}$/', $data['phone'])) {
            throw new Exception("Mobile number must be 10 digits and start with 7, 8, or 9");
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $sql = "UPDATE users SET name = :name, email = :email, phone = :phone";
        $params = $data;

        // If password change is requested
        if (!empty($_POST['current_password'])) {
            // Verify current password is provided
            if (empty($_POST['new_password'])) {
                throw new Exception("New password is required when current password is provided");
            }

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Validate new password
            if (strlen($_POST['new_password']) < 6) {
                throw new Exception("New password must be at least 6 characters long");
            }

            $sql .= ", password = :password";
            $params['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['profile_success'] = "Profile updated successfully!";
    } catch(Exception $e) {
        error_log($e->getMessage());
        $_SESSION['profile_error'] = $e->getMessage();
    }
}

header('Location: profile.php');
exit;
?> 