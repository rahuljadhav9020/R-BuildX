<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R-BuildX - Vehicles</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        .page-title {
            text-align: center;
            padding: 40px 0;
            background: linear-gradient(45deg,rgb(188, 60, 60),rgb(147, 83, 57), #512da8);
            color: white;
            margin-bottom: 50px;
        }

        .page-title h1 {
            color:rgb(140, 129, 254);
            font-size: 40px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-title p {
            font-size: 18px;
            opacity: 0.9;
        }

        .vehicles-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            color: #473cbc;
            text-align: center;
            font-size: 32px;
            margin: 50px 0 30px;
            text-shadow: 0 0 10px rgba(81,45,168,0.3);
            font-weight: 700;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }

        .section-title::after {
            content: '';
            display: block;
            width: 50%;
            height: 3px;
            background: #512da8;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .vehicle-card {
            position: relative;
            height: 350px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .vehicle-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.25);
        }

        .vehicle-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, 
                rgba(0,0,0,0) 0%,
                rgba(0,0,0,0.7) 75%,
                rgba(0,0,0,0.9) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .vehicle-card:hover::before {
            opacity: 1;
        }

        .vehicle-info {
            position: absolute;
            bottom: -100px;
            left: 0;
            width: 100%;
            padding: 20px;
            color: white;
            transition: bottom 0.3s ease;
        }

        .vehicle-card:hover .vehicle-info {
            bottom: 0;
        }

        .vehicle-info h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .vehicle-info p {
            font-size: 14px;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .order-btn {
            display: inline-block;
            background-color: #512da8;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .order-btn:hover {
            background-color: white;
            color: #512da8;
            border-color: #512da8;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="page-title slide-up">
        <h1>Our Construction Vehicles</h1>
        <p>Choose from our wide range of well-maintained construction vehicles</p>
    </div>

    <div class="vehicles-container">
        <div class="vehicles-section">
            <h2 class="section-title">Available Vehicles for Rent</h2>
            <div class="vehicles-grid">
                <div class="vehicle-card scale-in delay-1" style="background: url('tractor.jpg') center/cover;">
                    <div class="vehicle-info">
                        <h3>Tractor-Trolley</h3>
                        <p>Perfect for hauling construction materials and equipment</p>
                        <a href="tractor.php" class="order-btn btn-animate">Order Now</a>
                    </div>
                </div>

                <div class="vehicle-card scale-in delay-2 hover-lift" style="background: url('jcb.jpg') center/cover;">
                    <div class="vehicle-info">
                        <h3>JCB Excavator</h3>
                        <p>Ideal for digging, demolition, and heavy lifting tasks</p>
                        <a href="jcb.php" class="order-btn">Order Now</a>
                    </div>
                </div>

                <div class="vehicle-card scale-in delay-3 hover-lift" style="background: url('tanker.jpg') center/cover;">
                    <div class="vehicle-info">
                        <h3>Water Tanker</h3>
                        <p>Reliable water supply for your construction needs</p>
                        <a href="tanker.php" class="order-btn">Order Now</a>
                    </div>
                </div>

                <div class="vehicle-card1" style="box-shadow: none;">
                    <div class="vehicle-info">
                    </div>
                </div>
                <div class="vehicle-card scale-in delay-4 hover-lift" style="background: url('dumper.png') center/cover;">
                    <div class="vehicle-info">
                        <h3>Dumper</h3>
                        <p>Efficient material transport and waste removal</p>
                        <a href="dumper.php" class="order-btn">Order Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="foot_content"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $('#foot_content').load('footer.html');
    </script>
</body>

</html> 