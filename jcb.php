<?php
session_start();
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // First get the rate
        $rateStmt = $pdo->prepare("SELECT rate FROM rental_rates 
                                  WHERE vehicle_type = 'JCB' 
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

        // Then insert the order
        $stmt = $pdo->prepare("INSERT INTO vehicle_orders 
                              (vehicle_type, customer_name, email, mobile, location, purpose, rate, other_details) 
                              VALUES ('JCB', :name, :email, :mobile, :location, :purpose, :rate, :other)");
        
        $stmt->execute([
            ':name' => $_POST['c_name'],
            ':email' => $_POST['c_email'],
            ':mobile' => $_POST['c_mobile'],
            ':location' => $_POST['location'],
            ':purpose' => $_POST['work'],
            ':rate' => $rate,
            ':other' => $_POST['c_message'] ?? null
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
    <title>Order Excavator-JCB</title>
    <link rel="stylesheet" href="forms.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body style="background-image: url(jcb.jpg); background-position-y:-350px; background-size:cover;">
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
                    <label for="work">Order Machine For:</label>
                    <select name="work" id="work" required>
                        <option value="" selected disabled hidden>Select Location</option>
                        <option value="Construction">Construction</option>
                        <option value="Digging Pits">Digging Pits</option>
                        <option value="Plotting">Plotting</option>
                        <option value="In Stuck Situation">In Stuck Situation</option>
                        <option value="Pipe Line">Pipe Line</option>
                        <option value="Farming Use">Farming Use</option>
                        <option value="Other">Other</option>
                    </select>
                </div><br>
                <label for="other"><i><u style="margin-left: 35%; color:red">OR</u></i><br> Other Purpose:</label>
                <textarea id="message" name="c_message" placeholder="Enter Your Work"></textarea>

                <div class="field">
                    <p id="rate" style="font-size: 18px; color: #512da8; font-weight: bold; margin-top: 10px;"></p>
                </div>
                <b><i>*Note: Machine Running Charges will be extra that is- ₹200/- per KM.</i></b>
                <button type="submit" name="submit">SUBMIT</button><br>
            </form>
        </div>
    </div>
    <div id="foot_content"></div>
</body>

<script>
$(document).ready(function() {
    function updateRate() {
        var location = $('#location').val();
        var workType = $('#work').val();
        
        if(location && workType) {
            // Show loading message
            $('#rate').html('<span style="color: #512da8;">Loading rate...</span>');
            
            $.ajax({
                url: 'get_rate.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    vehicle: 'JCB',
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

</html>