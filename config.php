<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'construction_rental_db');

// Create connection with error handling
function getDBConnection() {
    try {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
        
        // Check if database exists, if not create it
        if (!mysqli_select_db($conn, DB_NAME)) {
            $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
            mysqli_query($conn, $sql);
            mysqli_select_db($conn, DB_NAME);
        }
        
        // Set charset to prevent injection
        mysqli_set_charset($conn, "utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("Sorry, there was a problem connecting to the database. Please try again later.");
    }
} 