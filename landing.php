<?php
session_start();
include 'conn.php';

// Check if the form is submitted
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the user exists in the database
    $sql = "SELECT * FROM voters WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $voter = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $voter['password'])) {
            // Store voter info in session
            $_SESSION['voter_id'] = $voter['id'];
            $_SESSION['email'] = $voter['email'];
            header('Location: voter_dashboard.php'); // Redirect to the dashboard
            exit();
        } else {
            $error = 'Invalid credentials.';
        }
    } else {
        $error = 'No user found with this email.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - KUSA Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-primary {
            width: 100%;
            border-radius: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
            height: 100px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: auto; /* Push footer to the bottom */
        }
        .footer-logo {
            height: 30px;
            margin-right: 10px;
            vertical-align: middle;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .footer p {
            margin-bottom: 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-container">
        <img src="images/kusalogo.png" alt="KUSA Logo" class="logo">
        <h2 class="text-center text-primary mb-4">Voter Login</h2>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <img src="images/kusalogo_light.png" alt="KUSA Logo" class="footer-logo">
        <span>Powered by the Kenyatta University Students' Association</span>
        <p class="mt-2">&copy; <?php echo date('Y'); ?> KUSA Voting System. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>