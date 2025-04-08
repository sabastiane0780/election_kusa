<?php
// Include the database connection
include 'conn.php';

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KUSA Voting System</title>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .landing-page {
            max-width: 450px;
            margin: 100px auto;
            text-align: center;
            padding: 40px 30px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .logo-img {
            max-width: 130px;
            margin-bottom: 25px;
        }
        .btn-lg {
            padding: 12px 20px;
            font-size: 18px;
            border-radius: 8px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="landing-page">
    <!-- Logo -->
    <img src="images/kusalogo.png" alt="KUSA Logo" class="logo-img">
    <h3 class="mb-3">Welcome to the KUSA Voting System</h3>
    <p class="mb-4">Secure and simple voting for every student.</p>

    <!-- Navigation Buttons -->
    <a href="signup.php" class="btn btn-primary btn-lg btn-block mb-3">Sign Up</a>
    <a href="login.php" class="btn btn-outline-secondary btn-lg btn-block">Login</a>
</div>

<div class="footer">
    &copy; <?php echo date("Y"); ?> KUSA Voting System. All rights reserved.
</div>

<!-- Bootstrap & JS Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
