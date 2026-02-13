<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = getDBConnection();

if (isset($_POST['submit'])) {
    $c_name = mysqli_real_escape_string($conn, $_POST['c_name']);
    $c_email = mysqli_real_escape_string($conn, $_POST['c_email']);
    $c_number = mysqli_real_escape_string($conn, $_POST['c_number']);
    $c_message = mysqli_real_escape_string($conn, $_POST['c_message']);
    
    if($c_message != '') {
        // Create contact_info table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS contact_info (
            id INT PRIMARY KEY AUTO_INCREMENT,
            c_name VARCHAR(100) NOT NULL,
            c_email VARCHAR(100) NOT NULL,
            c_number VARCHAR(15) NOT NULL,
            c_message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        mysqli_query($conn, $create_table);

        $sql = "INSERT INTO contact_info(c_name, c_email, c_number, c_message) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $c_name, $c_email, $c_number, $c_message);
        
        if(mysqli_stmt_execute($stmt)) {
            echo "<script> alert('Message Sent Successfully'); </script>";
        } else {
            echo "<script> alert('Error sending message. Please try again.'); </script>";
        }
    } else {
        echo "<script> alert('Please Enter Your Message'); </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="icon" href="favicon.png">
    <link rel="stylesheet" href="forms.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="animations.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="burger">
    <div class="container fade-in">
        <h1 class="slide-up">Contact Us</h1>
        <form method="post" class="scale-in delay-1">
            <label for="name">Name:</label>
            <input type="text" id="name" name="c_name" placeholder="Enter Your Name" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="c_email" placeholder="Enter Your Email" required>
            <label for="mobile">Mobile Number:</label>
            <input type="number" id="mobile" name="c_number" placeholder="Enter Your Mobile Number" required>
            <label for="message">Message:</label>
            <textarea id="message" name="c_message" placeholder="Enter Your Message" required></textarea>
            <button type="submit" name="submit">SUBMIT</button><br><br>
        </form>
    </div>
    </div>
    <div id="foot_content"></div>
</body>
<script>
    $('#foot_content').load('footer.html');
</script>

</html>