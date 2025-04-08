<?php
include 'conn.php';
session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: signup.php");
        exit();
    }

    $username = trim($_POST['name']); // Assuming the user enters the desired username in the 'name' field
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
    } else {
        // Check if the username already exists
        $checkSql = "SELECT * FROM admins WHERE username = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $_SESSION['error'] = "Admin username already exists!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Insert the new admin using the 'username' column
            $sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $username, $hashedPassword);

            if ($stmt->execute()) {
                $_SESSION['admin_id'] = $stmt->insert_id;
                $_SESSION['admin_name'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); // Sanitize before storing
                header('Location: admin_dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Signup</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .signup-box {
            max-width: 450px;
            margin: 100px auto;
            padding: 35px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .logo-img {
            max-width: 120px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="signup-box text-center">
    <img src="images/kusalogo.png" alt="KUSA Logo" class="logo-img">
    <h3>Admin Signup</h3>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="signup.php" method="POST" class="text-left mt-4" onsubmit="return validateForm()">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label for="name">Admin Username</label> <input type="text" class="form-control" name="name" required pattern="^[a-zA-Z0-9_]{3,20}$" title="3-20 characters, letters, numbers, and underscores only">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" class="form-control" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" class="form-control" name="confirm_password" required>
        </div>
        <button type="submit" name="register" class="btn btn-success btn-block">Register</button>
    </form>

    <p class="mt-3"><a href="admin_login.php">Already have an account? Login here.</a></p>
</div>

<script>
function validateForm() {
    const pw = document.getElementById("password").value;
    const cpw = document.getElementById("confirm_password").value;
    if (pw !== cpw) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}
</script>

</body>
</html>