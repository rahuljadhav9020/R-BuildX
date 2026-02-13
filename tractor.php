<?php
session_start();
require_once 'config/db.php';
require_once 'includes/mail_functions.php';

// Form validation function
function validateForm($data) {
    $errors = [];
    
    // Name validation
    if (empty($data['c_name'])) {
        $errors['name'] = "Name is required";
    } elseif (strlen($data['c_name']) < 2) {
        $errors['name'] = "Name must be at least 2 characters";
    }
    
    // Email validation
    if (empty($data['c_email'])) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($data['c_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    // Mobile validation
    if (empty($data['c_mobile'])) {
        $errors['mobile'] = "Mobile number is required";
    } elseif (!preg_match("/^[0-9]{10}$/", $data['c_mobile'])) {
        $errors['mobile'] = "Invalid mobile number (10 digits required)";
    }
    
    // Location validation
    if (empty($data['location'])) {
        $errors['location'] = "Please select a location";
    }
    
    // Work type validation
    if (empty($data['work'])) {
        $errors['work'] = "Please select work type";
    }
    
    return $errors;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate form
        $errors = validateForm($_POST);
        
        if (!empty($errors)) {
            throw new Exception("Please correct the following errors: " . implode(", ", $errors));
        }

        // First get the rate
        $rateStmt = $pdo->prepare("SELECT rate FROM rental_rates 
                                  WHERE vehicle_type = 'Tractor' 
                                  AND location = :location 
                                  AND purpose = :purpose");
        
        $rateStmt->execute([
            ':location' => $_POST['location'],
            ':purpose' => $_POST['work']
        ]);
        
        $rate = $rateStmt->fetchColumn();
        
        if (!$rate) {
            throw new Exception("Rate not found for selected location and purpose");
        }

        $pdo->beginTransaction();

        // Insert the order
        $stmt = $pdo->prepare("INSERT INTO vehicle_orders 
                              (vehicle_type, customer_name, email, mobile, location, purpose, rate, other_details, status) 
                              VALUES ('Tractor', :name, :email, :mobile, :location, :purpose, :rate, :other, 'Pending')");
        
        $stmt->execute([
            ':name' => $_POST['c_name'],
            ':email' => $_POST['c_email'],
            ':mobile' => $_POST['c_mobile'],
            ':location' => $_POST['location'],
            ':purpose' => $_POST['work'],
            ':rate' => $rate,
            ':other' => $_POST['c_message'] ?? null
        ]);

        $orderId = $pdo->lastInsertId();

        // Prepare order details for email
        $orderDetails = [
            'order_id' => $orderId,
            'customer_name' => $_POST['c_name'],
            'email' => $_POST['c_email'],
            'vehicle_type' => 'Tractor',
            'location' => $_POST['location'],
            'purpose' => $_POST['work'],
            'rate' => $rate
        ];

        // Send confirmation email
        $emailSent = sendOrderConfirmation($orderDetails);

        $pdo->commit();

        // Show success message and redirect
        $message = "Order placed successfully!";
        if (!$emailSent) {
            $message .= " (Email confirmation could not be sent)";
        }
        
        echo "<script>
            alert('$message Please check your orders page for status updates.');
            window.location.href='my_orders.php';
        </script>";
        exit();

    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tractor-Trolley</title>
    <link rel="stylesheet" href="forms.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-image: url(tractor.jpg); background-position:center; background-size:cover;">
    <div id="head_content"></div>
    <div class="burger">
        <div class="container">
            <h1 style="font-size:30px;">Fill the below form</h1>
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <label for="name">Name:</label>
                <input type="text" id="name" name="c_name" placeholder="Enter Your Name" required><br>
                <label for="email">Email:</label>
                <input type="email" id="email" name="c_email" placeholder="Enter Your Email" required><br>
                <label for="mobile">Mobile Number:</label>
                <input type="number" id="mobile" name="c_mobile" placeholder="Enter Your Mobile Number" required><br><br>
                <div class="field">
                    <label for="location">Location:</label>
                    <select name="location" id="location" required>
                        <option value="" selected disabled hidden>SELECT LOCATION</option>
                        <option value="Kordewadi">Kordewadi</option>
                        <option value="Morgiri">Morgiri</option>
                        <option value="Kokisare">Kokisare</option>
                        <option value="Vadikotavade">Vadikotavade</option>
                        <option value="Morewadi">Morewadi</option>
                        <option value="Gureghar">Gureghar</option>
                        <option value="Gokul">Gokul</option>
                        <option value="Shidrukwadi">Shidrukwadi</option>
                        <option value="Natoshi">Natoshi</option>
                        <option value="Dhawade">Dhawade</option>
                        <option value="Ambeghar">Ambeghar</option>
                        <option value="Ambrag">Ambrag</option>
                    </select>
                    <label for="work">Kind of Work:</label>
                    <select name="work" id="work" required>
                        <option value="" selected disabled hidden>SELECT WORK</option>
                        <option value="Construction">Construction</option>
                        <option value="Farming">Farming</option>
                        <option value="Woods">Woods</option>
                        <option value="Under Excavator">Under Excavator</option>
                    </select>
                </div><br>
                <div class="field">
                    <p id="rate" style="font-size: 18px; color: #512da8; font-weight: bold; margin-top: 10px;"></p>
                </div>
                <button type="submit" name="submit">SUBMIT</button><br>
        </div>
        </form>
    </div>
    </div>
   

    <script>
    $(document).ready(function() {
        function updateRate() {
            var location = $('#location').val();
            var workType = $('#work').val();
            
            if(location && workType) {
                $('#rate').html('<span style="color: #512da8;">Loading rate...</span>');
                
                $.ajax({
                    url: 'get_rate.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        vehicle: 'Tractor',
                        location: location,
                        purpose: workType
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#rate').html('Rate: ₹' + response.rate + '/- per hour');
                        } else {
                            $('#rate').html('<span style="color: #ff4444;">' + (response.message || 'Rate not available') + '</span>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Error:', error);
                        $('#rate').html('<span style="color: #ff4444;">Error fetching rate</span>');
                    }
                });
            } else {
                $('#rate').html('');
            }
        }

        // Update rate when location or work type changes
        $('#location, #work').change(updateRate);
    });
    </script>
</body>

</html>