<?php
session_start();
include 'conn.php'; // Database connection

// Initialize login attempt count in session if not already set
if (!isset($_SESSION['attempt_count'])) {
    $_SESSION['attempt_count'] = 0;
}

// Limit the number of attempts (e.g., 3 attempts)
if ($_SESSION['attempt_count'] >= 3) {
    $error_message = "Too many failed login attempts. Please try again later.";
} else {
    $error_message = ""; // Initialize error message
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check if the form fields are set
        if (isset($_POST['email']) && isset($_POST['password'])) {
            // Get input values
            $email = trim($_POST['email']); // Trim whitespace
            $password = $_POST['password'];

            // Prepare SQL query (using email instead of username)
            $sql = "SELECT voter_id, email, password, name FROM voters WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email); // Bind the email parameter
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $voter = $result->fetch_assoc();

                // Verify password
                if (password_verify($password, $voter['password'])) {
                    // Reset the attempt count on successful login
                    $_SESSION['attempt_count'] = 0;

                    // Store session data
                    $_SESSION['voter_id'] = $voter['voter_id'];
                    $_SESSION['voter_email'] = $voter['email'];
                    $_SESSION['voter_name'] = $voter['name'];

                    // Redirect to voter dashboard
                    header('Location: voter_dashboard.php');
                    exit();
                } else {
                    // Increment the attempt count
                    $_SESSION['attempt_count'] += 1;
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No user found with that email.";
            }
        } else {
            $error_message = "Please fill in both email and password.";
        }
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
            background-color: #f4f7fc;
            font-family: Arial, sans-serif;
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
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: auto;
        }
        .footer p {
            margin-bottom: 0;
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
            height: 100px;
        }
        .welcome-text {
            text-align: center;
            margin-bottom: 20px;
            color: #28a745; /* A friendly green color */
        }
        .login-links {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-container">
        <img src="images/kusalogo.png" alt="KUSA Logo" class="logo">
        <h2 class="text-center text-primary mb-2">Welcome to the KUSA Voting System</h2>
        <p class="welcome-text">Please log in to cast your vote.</p>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <div class="login-links">
            </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> KUSA Voting System. All Rights Reserved.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>