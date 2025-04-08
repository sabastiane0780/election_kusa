<?php
session_start();

// Check if the voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: voter_login.php");
    exit();
}

include 'conn.php';

// Fetch voter details
$voter_id = $_SESSION['voter_id'];
$sql = "SELECT * FROM voters WHERE voter_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter_result = $stmt->get_result();

if ($voter_result->num_rows > 0) {
    $voter = $voter_result->fetch_assoc();
} else {
    header("Location: voter_login.php");
    exit();
}

// Fetch available elections and their results visibility
$sql_elections = "SELECT id, election_name, start_time, end_time, results_visible
                    FROM election_status
                    WHERE status = 'open'
                    ORDER BY start_time DESC"; // Changed 'start_date' to 'start_time'
$result_elections = $conn->query($sql_elections);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - KUSA Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Modern font */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .container {
            margin-top: 30px;
            flex-grow: 1; /* Allow container to take up remaining vertical space */
        }
        .dashboard-header {
            text-align: center;
            color: #007bff; /* Primary color */
            margin-bottom: 30px;
        }
        .card {
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px 8px 0 0;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .election-list {
            list-style: none;
            padding: 0;
        }
        .election-item {
            display: flex;
            justify-content-between ;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .election-item:last-child {
            border-bottom: none;
        }
        .election-info {
            flex-grow: 1;
        }
        .election-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .election-dates {
            color: #6c757d;
            font-size: 0.9em;
        }
        .btn-primary {
            background-color: #28a745; /* Success color for action */
            border-color: #28a745;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #1e7e34;
            border-color: #1c7430;
        }
        .btn-secondary {
            background-color: #6c757d; /* Secondary color for results */
            border-color: #6c757d;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .alert-success, .alert-info {
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 30px;
            border-top: 1px solid #555;
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
        .no-elections {
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="voter_dashboard.php">
        <img src="images/kusalogo.png" alt="KUSA Logo" style="height: 40px;">
        KUSA Voting System
    </a>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="voter_logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container">
    <h1 class="dashboard-header">Welcome, <?php echo htmlspecialchars($voter['username']); ?>!</h1>

    <?php if (isset($_GET['vote']) && $_GET['vote'] == 'success'): ?>
        <div class="alert alert-success">
            Your vote has been successfully submitted. Thank you for participating!
        </div>
    <?php elseif (isset($_GET['vote']) && $_GET['vote'] == 'already_voted'): ?>
        <div class="alert alert-info">
            You have already voted in this election. Thank you for participating!
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4>Current Elections</h4>
        </div>
        <div class="card-body">
            <?php if ($result_elections->num_rows > 0): ?>
                <ul class="election-list">
                    <?php while ($election = $result_elections->fetch_assoc()): ?>
                        <li class="election-item">
                            <div class="election-info">
                                <h5 class="election-name"><?php echo htmlspecialchars($election['election_name']); ?></h5>
                                <?php if ($election['start_time'] && $election['end_time']): ?>
                                    <p class="election-dates">
                                        Voting Period: <?php echo date('F j, Y H:i:s', strtotime($election['start_time'])); ?> - <?php echo date('F j, Y H:i:s', strtotime($election['end_time'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="vote.php?election_id=<?php echo $election['id']; ?>" class="btn btn-primary">Vote Now</a>
                                <?php if ($election['results_visible']): ?>
                                    <a href="results.php?election_id=<?php echo $election['id']; ?>" class="btn btn-secondary">View Results</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-elections">No active elections available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <img src="images/kusalogo.png" alt="KUSA Logo" class="footer-logo">
        <span>Powered by SABZ MEDIA</span>
        <p class="mt-2">&copy; <?php echo date('Y'); ?> KUSA Voting System. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>