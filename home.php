<?php
session_start();
require_once 'config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to R-BuildX</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="block.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        /* Header Styles */
        .header {
            background-color: #333;
            padding: 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
        }

        .nav-menu a:hover {
            background-color: #555;
            border-radius: 4px;
        }

        .login-btn {
            background-color: #4CAF50;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .login-btn:hover {
            background-color: #45a049;
        }

        /* Hero Section */
        .hero {
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('construction-bg.jpg');
            height: 500px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            padding: 2rem;
        }

        /* Main Content */
        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .service-card {
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .protected-content {
            padding: 1rem;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: center;
        }

        .login-prompt {
            padding: 2rem;
            text-align: center;
            background:transparent;
        }

        .prompt-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
        }

        .prompt-left {
            font-size: 2rem;
            color: #333;
        }

        .prompt-right {
            text-align: left;
        }

        .prompt-btn {
            background-color: #512da8;
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .prompt-btn:hover {
            background-color: #5A52E3;
        }

        .prompt-underline {
                  
            height: 3px;
            background-color: #512da8;
            margin: 2rem auto 0;
            display: block;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <?php if (!$isLoggedIn): ?>
    <div class="login-prompt">
        <div class="prompt-box">
            <div class="prompt-left">
                <i class="fas fa-key" style="font-size: 48px;"></i>
            </div>
            <div class="prompt-right">
                <h2>Access More Features</h2>
                <p>Please login or register to access our full range of services</p>
                <br>
                <a href="index.php" class="prompt-btn">Login Now →</a>
            </div>
        </div>
        <div class="prompt-underline"></div>
    </div>
    <?php endif; ?>

    <div class="slogam slide-up">        
        <div class="main"><img src="main.jpg" alt="No Image Preview"></div>
        <div>
            <h2>need vehicle on rent ? <br>
                we can provide you <br><span id="vehicles"></span>
                <br><span id="material"></span>
            </h2>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
    <div class="content fade-in delay-1">
        <div><img src="contract.jpg" alt="No Image Preview"></div>
        <div class="text"><br><br>
            <h3> Exploring The vehicles for Work: R-BuildX-Vehicles</h3>
            <p>
                We have created R-BuildX (this website) to provide a simple, efficient platform for renting
                vehicles and ordering construction materials, completely free of charge. The website is designed to be
                user-friendly, with a clean layout that ensures smooth navigation on both phones and computers. We offer
                easy-to-understand content and straightforward options for users to rent vehicles like tankers, JCBs,
                and dumpers, as well as order construction materials.

                Our goal is to make your experience as seamless as possible. Whether you're here to rent a vehicle or
                purchase construction material, we aim to provide quick and convenient solutions. If you have any
                questions or encounter any issues, you've come to the right place. We're here to assist you and make
                sure all your needs are met effectively.

                Feel free to explore the website, and let us help you with all your vehicle rental and material ordering
                needs!
                <br>
            </p>
        </div>
    </div>
    <?php endif; ?>
    <div class="card">
        <div class="social-block">
            <h2 class="card-title">Wide Range of Equipment</h2>
            <p class="card-description">We offer a comprehensive selection of construction vehicles, from tractors, excavators, dumpers, to
                trolleys and tankers.</p>
        </div>

        <div class="social-block ">
            <h2 class="card-title">Quality Materials</h2>
            <p class="card-description">Access top-quality construction materials with ease, ensuring your project uses only the best.</p>
        </div>

        <div class="social-block">
            <h2 class="card-title">Easy-to-Use Platform</h2>
            <p class="card-description">Our user-friendly website simplifies the process of renting vehicles and ordering materials. Access
                everything with just a few clicks.</p>
        </div>

        <div class="social-block">
            <h2 class="card-title">24/7 Customer Support</h2>
            <p class="card-description">This website helps in developing good study skills & motivates for self-study. It also shows the way
                towards success.</p>
        </div>

        <div class="social-block">
            <h2 class="card-title">Flexible Rental Terms</h2>
            <p class="card-description">Rent our equipment, including trolleys and tankers, on a schedule that works for you, with short-term or
                long-term options available.</p>
        </div>

        <div class="social-block scale-in delay-6 hover-lift">
            <h2 class="card-title">Cost-Effective Solutions</h2>
            <p class="card-description">Our competitive pricing ensures you get the best value, whether you're renting machinery, trolleys,
                tankers, or purchasing materials.</p>
        </div>
    </div>
    

    <div id="foot_content"></div>

    <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        var typed = new Typed('#vehicles', {
            strings: ['<i>Tractor-Trolley</i>', '<i>excavator-jcb</i>', '<i>Water Tanker</i> ', '<i>Dumper</i>'],
            typeSpeed: 60,
            loop: true
        });

        var typed = new Typed('#material', {
            strings: [' ', '<i>At low cost</i>'],
            typeSpeed: 60,
            loop: true
        });

        $('#foot_content').load('footer.html');
    </script>
</body>

</html>