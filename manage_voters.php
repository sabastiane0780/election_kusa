<?php
// Include database connection
include 'conn.php';
session_start();

// Include the PHPMailer autoloader
require 'vendor/autoload.php'; // Path to your Composer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate a strong random password
function generateStrongPassword($length = 12) {
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+=-`~[]{};:,.<>/?';

    $allChars = $lowercase . $uppercase . $numbers . $symbols;
    $allCharsLength = strlen($allChars);
    $password = '';

    // Ensure at least one character from each set
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    $password .= $symbols[rand(0, strlen($symbols) - 1)];

    // Fill the rest of the password with random characters from all sets
    $remainingLength = $length - 4;
    for ($i = 0; $i < $remainingLength; $i++) {
        $password .= $allChars[rand(0, $allCharsLength - 1)];
    }

    // Shuffle the password to make the order less predictable
    return str_shuffle($password);
}

// Handle adding a new voter
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['email'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_plain = generateStrongPassword(); // Generate a strong random password
    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

    // Insert the new voter into the database
    $sql = "INSERT INTO voters (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password_hashed);

    if ($stmt->execute()) {
        // Send login credentials email to the voter using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings (replace with your actual settings)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sabastinejalekonyungi@gmail.com';
            $mail->Password = 'kctv ulig qhby bxla';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients (replace with your actual sender email)
            $mail->setFrom('your-email@gmail.com', 'KUSA Voting System');
            $mail->addAddress($email, $username);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Voting Login Credentials';
            $mail->Body     = "Hello $username,<br><br>Your login credentials for the KUSA Voting System are:<br><br>"
                                . "Username: $username<br>Email: $email<br>Password: $password_plain<br><br>"
                                . "Please log in with the details in the email.<br><br>"
                                . "Best Regards,<br>SABZ MEDIA@KUSA Voting System Team";

            // Send the email
            $mail->send();
            // Redirect back to manage_voters.php with success message
            header("Location: manage_voters.php?success=Voter registered successfully. A strong password has been generated and sent to the voter’s email. Please advise them to change it upon login.");
            exit();
        } catch (Exception $e) {
            $error_message = "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $error_message = "Failed to register the voter.";
    }
}

// Fetch all voters from the database
$sql = "SELECT voter_id, username, email FROM voters";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters - KUSA Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 30px;
        }
        .table th, .table td {
            text-align: center;
        }
        .navbar {
            background-color: #007bff;
        }
        .navbar .navbar-brand img {
            width: 40px;
        }
        .navbar .nav-link {
            color: #fff !important;
        }
        footer {
            background-color: #007bff;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <a class="navbar-brand" href="admin_dashboard.php">
        <img src="images/kusalogo.png" alt="KUSA Logo"> KUSA Voting System
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_candidates.php">Manage Candidates</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_voters.php">Manage Voters</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_results.php">Review Results</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_position.php">Manage Position</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container">
    <h2 class="mt-4">Manage Voters</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="manage_voters.php">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Enter voter username" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="Enter voter email" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Voter</button>
    </form>

    <h3 class="mt-5">Registered Voters</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($voter = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($voter['username']); ?></td>
                        <td><?php echo htmlspecialchars($voter['email']); ?></td>
                        <td>
                            <a href="delete_voter.php?voter_id=<?php echo intval($voter['voter_id']); ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No voters found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer>
    <p>© 2025 KUSA Voting System. All Rights Reserved.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>