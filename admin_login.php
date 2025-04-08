<?php
include 'conn.php';
session_start();

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid request token.";
        header("Location: admin_login.php");
        exit();
    }

    $name = trim($_POST['name']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid password!';
        }
    } else {
        $_SESSION['error'] = 'Admin not found!';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .login-box {
            width: 400px; margin: 100px auto; text-align: center; padding: 30px;
            background-color: #fff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .logo-img { max-width: 150px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="login-box">
    <img src="images/kusalogo.png" alt="KUSA Logo" class="logo-img">
    <h3>Admin Login</h3>

    <!-- Display error message -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="admin_login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group text-left">
            <label for="name">Admin Name</label>
            <input type="text" class="form-control" id="name" name="name" required pattern="^[a-zA-Z0-9_]{3,20}$" title="3-20 characters, letters, numbers, and underscores only">
        </div>
        <div class="form-group text-left">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="6">
        </div>
        <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
    </form>

    <p class="mt-2"><a href="signup.php">Don't have an account? Sign up here.</a></p>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/js/bootstrap.bundle.min.js"></script>

</body>
</html>
