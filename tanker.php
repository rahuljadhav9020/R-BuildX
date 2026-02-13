<?php
session_start();
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // First get the rate
        $rateStmt = $pdo->prepare("SELECT rate FROM rental_rates 
                                  WHERE vehicle_type = 'Tanker' 
                                  AND location = :location 
                                  AND purpose = :purpose");
        
        $rateStmt->execute([
            ':location' => $_POST['location'],
            ':purpose' => $_POST['water']
        ]);
        
        $rate = $rateStmt->fetchColumn();
        
        if (!$rate) {
            throw new Exception("Rate not found for selected location and purpose");
        }

        // Then insert the order
        $stmt = $pdo->prepare("INSERT INTO vehicle_orders 
                              (vehicle_type, customer_name, email, mobile, location, purpose, rate) 
                              VALUES ('Tanker', :name, :email, :mobile, :location, :purpose, :rate)");
        
        $stmt->execute([
            ':name' => $_POST['c_name'],
            ':email' => $_POST['c_email'],
            ':mobile' => $_POST['c_mobile'],
            ':location' => $_POST['location'],
            ':purpose' => $_POST['water'],
            ':rate' => $rate
        ]);

        echo "<script>alert('Order placed successfully!'); window.location.href='vehicles.php';</script>";
    } catch(Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Water Tanker</title>
    <link rel="stylesheet" href="forms.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body style="background-image: url(tanker.jpg); background-position:center; background-size:cover;">
    <div id="head_content"></div>
    <div class="burger">
        <div class="container">
            <h1 style="font-size:30px;">Fill the below form</h1>
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <label for="name">Name:</label>
                <input type="text" id="name" name="c_name" placeholder="Enter Your Name" required><br>
                <label for="email">Email:</label>
                <input type="email" id="email" name="c_email" placeholder="Enter Your Email" required><br>
                <label for="mobile">Mobile Number:</label>
                <input type="number" id="mobile" name="c_mobile" placeholder="Enter Your Mobile Number"
                    required><br><br>
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
                    <label for="work">Order Water For:</label>
                    <select name="water" id="water" required>
                        <option value="" selected disabled hidden>SECECT USE</option>
                        <option value="Construction Sites">Construction Sites</option>
                        <option value="Drinking">Drinking</option>
                        <option value="Farming">Farming</option>
                        <option value="Daily Use">Daily Use</option>
                    </select>
                </div><br>
                <div class="field">
                    <p id="rate" style="font-size: 18px; color: #512da8; font-weight: bold; margin-top: 10px;"></p>
                </div>
                <button type="submit" name="submit">SUBMIT</button><br>
            </form>
        </div>
    </div>
    <div id="foot_content"></div>
    <script>
    $(document).ready(function() {
        function updateRate() {
            var location = $('#location').val();
            var workType = $('#water').val();
            
            if(location && workType) {
                // Show loading message
                $('#rate').html('<span style="color: #512da8;">Loading rate...</span>');
                
                $.ajax({
                    url: 'get_rate.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        vehicle: 'Tanker',
                        location: location,
                        purpose: workType
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#rate').html('Rate: ₹' + response.rate + '/- per trip');
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

        // Update rate when location or water type changes
        $('#location, #water').change(updateRate);
    });
    </script>
</body>

</html>