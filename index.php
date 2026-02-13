<?php
session_start();

// Redirect to home if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

require_once 'config.php';
$conn = getDBConnection();

// Check if tables exist, if not create them
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($check_table) == 0) {
    // Read and execute the SQL from database.sql
    $sql = file_get_contents('database.sql');
    mysqli_multi_query($conn, $sql);
    while(mysqli_next_result($conn)){;}  // Clear out remaining results
}

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $number = mysqli_real_escape_string($conn, $_POST['number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email exists
    $check_email = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check_email, "s", $email);
    mysqli_stmt_execute($check_email);
    $result = mysqli_stmt_get_result($check_email);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Email ID has Already Taken');</script>";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $number, $password);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Registration Successful');</script>";
        } else {
            echo "<script>alert('Registration Failed: " . mysqli_error($conn) . "');</script>";
        }
    }
}

if (isset($_POST['sign_in'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT user_id, password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            header("Location: home.php");
            exit();
        } else {
            echo "<script>alert('Wrong Password');</script>";
        }
    } else {
        echo "<script>alert('User Not Found');</script>";
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="design.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>LoginwithID</title>

</head>

<body>
    <div class="container" id="container">
        <div class="form-container sign-up">
            <form name="registration" method="post" onsubmit="return validateSignUpForm()">
                <h1>Create Acconut</h1>
                <div class="social">
                    <a href="#" class="icon"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="icon"><i class="fa-solid fa-phone"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                </div>
                <span>or use your Email for Registration</span>
                <input type="text" placeholder="Name" name="name" required>
                <input type="email" placeholder="Email ID" name="email" required>
                <input type="number" placeholder="Mobile No." name="number" required>
                <input type="password" placeholder="Password" name="password" required>
                <input type="password" placeholder="Confirm Password" name="confirmpassword" required><br>
                <button type="submit" name="submit">Sign Up</button>


            </form>
        </div>
        <div class="form-container sign-in">
            <form name="login" method="post" onsubmit="return validateSignInForm()">
                <h1>Sign In</h1>
                <div class="social">
                    <a href="#" class="icon"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="icon"><i class="fa-solid fa-phone"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                </div>
                <span>or use your Email-ID And Password</span>

                <input type="email" placeholder="Email ID" name="email">
                <input type="password" placeholder="Password" name="password">
                <a href="#">Forget Your Password?</a>
                <button type="submit" name="sign_in">Sign in</button>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter Your Personal Details to use all of site Features</p>
                    <button class="hidden" id="login">Sign In</button>
                    </h1>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Sign up</h1>
                    <p>Register with Your Personal Details.</p>
                    <button class="hidden" id="register">Sign Up</button>
                    </h1>
                </div>
            </div>
        </div>
    </div>


    <script src="script.js"></script>
    <script>
    function validateSignUpForm() {
        const form = document.forms["registration"];
        const name = form["name"].value.trim();
        const email = form["email"].value.trim();
        const number = form["number"].value.trim();
        const password = form["password"].value;
        const confirmPassword = form["confirmpassword"].value;

        // Name validation
        if (name.length < 3) {
            showError("Name must be at least 3 characters long");
            return false;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError("Please enter a valid email address");
            return false;
        }

        // Phone number validation
        const phoneRegex = /^[789]\d{9}$/;
        if (!phoneRegex.test(number)) {
            showError("Mobile number must be 10 digits and start with 7, 8, or 9");
            return false;
        }

        // Password validation
        if (password.length < 6) {
            showError("Password must be at least 6 characters long");
            return false;
        }

        // Password strength check
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/;
        if (!passwordRegex.test(password)) {
            showError("Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character");
            return false;
        }

        // Confirm password
        if (password !== confirmPassword) {
            showError("Passwords do not match");
            return false;
        }

        return true;
    }

    function validateSignInForm() {
        const form = document.forms["login"];
        const email = form["email"].value.trim();
        const password = form["password"].value;

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError("Please enter a valid email address");
            return false;
        }

        // Password validation
        if (password.length < 6) {
            showError("Password must be at least 6 characters long");
            return false;
        }

        return true;
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        // Remove any existing error messages
        const existingError = document.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Add the new error message
        document.querySelector('.container').appendChild(errorDiv);
        
        // Remove the error message after 3 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 3000);
    }
    </script>

    <style>
    .error-message {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #ff4444;
        color: white;
        padding: 15px 25px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease-out;
        z-index: 1000;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>
</body>

</html>