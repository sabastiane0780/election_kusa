<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include 'conn.php';

// Fetch Admin Details using Prepared Statements
$admin_id = $_SESSION['admin_id'];
$sqlAdmin = "SELECT * FROM admins WHERE admin_id = ?";
$stmtAdmin = $conn->prepare($sqlAdmin);
$stmtAdmin->bind_param("i", $admin_id);
$stmtAdmin->execute();
$resultAdmin = $stmtAdmin->get_result();
$admin = ($resultAdmin && $resultAdmin->num_rows > 0) ? $resultAdmin->fetch_assoc() : ['username' => 'Admin'];

// Fetch Summary Data
$sqlVoters = "SELECT COUNT(*) AS total_voters FROM voters";
$voters = ($conn->query($sqlVoters))->fetch_assoc();

$sqlCandidates = "SELECT COUNT(*) AS total_candidates FROM candidates";
$candidates = ($conn->query($sqlCandidates))->fetch_assoc();

$sqlPositions = "SELECT COUNT(*) AS total_positions FROM positions";
$positions = ($conn->query($sqlPositions))->fetch_assoc();

$sqlElectionStatus = "SELECT * FROM election_status LIMIT 1";
$electionStatus = ($conn->query($sqlElectionStatus))->fetch_assoc() ?: ['status' => 'closed', 'votes_cast' => 0, 'votes_remaining' => 0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KUSA Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar-brand {
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .dashboard-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            font-weight: bold;
            background-color: #007bff;
            color: white;
        }
        .icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #007bff;
        }
        .footer {
            text-align: center;
            padding: 15px;
            background-color: #343a40;
            color: white;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">
            <img src="images/kusalogo.png" alt="Logo"> KUSA Voting System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_voters.php"><i class="fas fa-users"></i> Manage Voters</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_candidates.php"><i class="fas fa-user-tie"></i> Manage Candidates</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_positions.php"><i class="fas fa-list"></i> Manage Positions</a></li>
                <li class="nav-item"><a class="nav-link" href="view_results.php"><i class="fas fa-chart-bar"></i> View Results</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Dashboard Content -->
<div class="container mt-4">
    <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($admin['username']); ?>!</h2>
    
    <div class="row">
        <!-- Voters -->
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-users icon"></i>
                    <h5>Total Voters</h5>
                    <h3><?php echo $voters['total_voters']; ?></h3>
                    <a href="manage_voters.php" class="btn btn-primary btn-sm">Manage Voters</a>
                </div>
            </div>
        </div>

        <!-- Candidates -->
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-user-tie icon"></i>
                    <h5>Total Candidates</h5>
                    <h3><?php echo $candidates['total_candidates']; ?></h3>
                    <a href="manage_candidates.php" class="btn btn-primary btn-sm">Manage Candidates</a>
                </div>
            </div>
        </div>

        <!-- Positions -->
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-list icon"></i>
                    <h5>Total Positions</h5>
                    <h3><?php echo $positions['total_positions']; ?></h3>
                    <a href="manage_positions.php" class="btn btn-primary btn-sm">Manage Positions</a>
                </div>
            </div>
        </div>

        <!-- Election Status -->
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-poll icon"></i>
                    <h5>Election Status</h5>
                    <h3><?php echo htmlspecialchars($electionStatus['status']); ?></h3>
                    <p><strong>Votes Cast:</strong> <?php echo $electionStatus['votes_cast']; ?></p>
                    <p><strong>Votes Remaining:</strong> <?php echo $electionStatus['votes_remaining']; ?></p>
                    <a href="view_results.php" class="btn btn-info btn-sm">View Results</a>
                </div>
            </div>
        </div>

        <!-- Manage Elections -->
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-cogs icon"></i>
                    <h5>Manage Elections</h5>
                    <a href="admin_election_control.php" class="btn btn-warning btn-sm">Control Elections</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    &copy; <?php echo date("Y"); ?> Dg sabz@KUSA. All rights reserved.
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
