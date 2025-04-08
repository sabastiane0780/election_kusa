<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include 'conn.php';

// Fetch general election status
$sqlElectionStatus = "SELECT * FROM election_status ORDER BY start_time DESC LIMIT 1";
$resultElectionStatus = $conn->query($sqlElectionStatus);
$electionStatus = $resultElectionStatus->fetch_assoc();

// Fetch the results visibility status
$resultsVisible = isset($electionStatus['results_visible']) ? (bool) $electionStatus['results_visible'] : false;

// Fetch election results (only if results are visible or admin is logged in)
if ($resultsVisible || isset($_SESSION['admin_id'])) {
    $sqlResults = "SELECT candidates.candidate_id, candidates.name, positions.position_name, candidates.image, COUNT(votes.candidate_id) AS total_votes
                    FROM candidates
                    LEFT JOIN votes ON candidates.candidate_id = votes.candidate_id
                    LEFT JOIN positions ON candidates.position_id = positions.id
                    GROUP BY candidates.candidate_id";
    $resultResults = $conn->query($sqlResults);

    $candidates = [];
    $votes = [];
    $positions = [];
    $images = [];

    while ($row = $resultResults->fetch_assoc()) {
        $candidates[] = $row['name'];
        $votes[] = $row['total_votes'];
        $positions[] = $row['position_name'];
        $images[] = $row['image'];
    }

    $candidates_json = json_encode($candidates);
    $votes_json = json_encode($votes);
    $positions_json = json_encode($positions);
    $images_json = json_encode($images);
} else {
    $candidates_json = json_encode([]);
    $votes_json = json_encode([]);
    $positions_json = json_encode([]);
    $images_json = json_encode([]);
}

// Fetch total registered voters
$sqlTotalVoters = "SELECT COUNT(*) AS total_voters FROM voters";
$resultTotalVoters = $conn->query($sqlTotalVoters);
$totalVoters = $resultTotalVoters->fetch_assoc()['total_voters'];

// Fetch total votes casted
$sqlTotalVotesCast = "SELECT COUNT(*) AS total_votes_cast FROM votes";
$resultTotalVotesCast = $conn->query($sqlTotalVotesCast);
$totalVotesCast = $resultTotalVotesCast->fetch_assoc()['total_votes_cast'];

// Fetch positions count
$sqlPositionsCount = "SELECT count(*) as positions_count from positions";
$resultPositionsCount = $conn->query($sqlPositionsCount);
$positionsCount = $resultPositionsCount->fetch_assoc()['positions_count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - KUSA Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <style>
        /* ... (Your existing styles) ... */
        .navbar-brand {
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .dashboard {
            margin-top: 30px;
        }
        .candidate-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        #results-section {
            display: <?php echo ($resultsVisible || isset($_SESSION['admin_id'])) ? 'block' : 'none'; ?>;
        }
        #reveal-results-btn {
            display: <?php echo (!$resultsVisible && isset($_SESSION['admin_id'])) ? 'block' : 'none'; ?>;
            margin-top: 20px;
        }
        #results-hidden-message {
            display: <?php echo (!$resultsVisible && !isset($_SESSION['admin_id'])) ? 'block' : 'none'; ?>;
            margin-top: 20px;
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
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
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="admin_dashboard.php">
        <img src="images/kusalogo_light.png" alt="KUSA Logo">
        KUSA Voting System
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_candidates.php">Manage Candidates</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_positions.php">Manage Positions</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_voters.php">Manage Voters</a></li>
            <li class="nav-item active"><a class="nav-link" href="view_results.php">View Results</a></li>
            <li class="nav-item"><a class="nav-link btn btn-danger text-white ml-2" href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container dashboard">
    <h2>Election Overview</h2>

    <div class="card mb-4">
        <div class="card-header">General Election Status</div>
        <div class="card-body">
            <p><strong>Election Name:</strong> <?php echo htmlspecialchars($electionStatus['election_name']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($electionStatus['status']); ?></p>
            <p><strong>Start Time:</strong> <?php echo htmlspecialchars($electionStatus['start_time']); ?></p>
            <p><strong>End Time:</strong> <?php echo htmlspecialchars($electionStatus['end_time']); ?></p>
            <p><strong>Total Registered Voters:</strong> <?php echo $totalVoters; ?></p>
            <p><strong>Total Votes Cast:</strong> <?php echo $totalVotesCast; ?></p>
            <p><strong>Voter Turnout:</strong> <?php echo ($totalVoters > 0) ? round(($totalVotesCast / $totalVoters) * 100, 2) . '%' : '0%'; ?></p>
            <p><strong>Total Positions Being Contested:</strong> <?php echo $positionsCount; ?></p>
        </div>
    </div>

    <div id="results-hidden-message">
        Results will be made available after the admin reveals them.
    </div>

    <button id="reveal-results-btn" class="btn btn-success" onclick="revealResults()">Reveal Results</button>

    <div id="results-section">
        <h2>Candidate Results</h2>

        <div class="card">
            <div class="card-header">Graphical Representation</div>
            <div class="card-body">
                <canvas id="resultsChart"></canvas>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Election Results</div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Candidate Image</th>
                            <th>Candidate</th>
                            <th>Position</th>
                            <th>Total Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $index => $name) { ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($images[$index]); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="candidate-image">
                                </td>
                                <td><?php echo htmlspecialchars($name); ?></td>
                                <td><?php echo htmlspecialchars($positions[$index]); ?></td>
                                <td><?php echo $votes[$index]; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="downloadPDF()">Download Overall Results (PDF)</button>
                </div>
            </div>
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

<script>
    let candidates = <?php echo $candidates_json; ?>;
    let votes = <?php echo $votes_json; ?>;

    const ctx = document.getElementById('resultsChart').getContext('2d');
    let resultsChart;
    if (candidates.length > 0) {
        resultsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: candidates,
                datasets: [{
                    label: 'Votes Count',
                    data: votes,
                    backgroundColor: ['#007bff', '#dc3545', '#28a745', '#ffc107', '#17a2b8'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function downloadPDF() {
        let pdf = new jsPDF();
        pdf.text(20, 20, 'Overall Election Results');
        pdf.text(20, 30, 'Candidate Name - Votes');

        let startY = 40;
        candidates.forEach((name, index) => {
            pdf.text(20, startY, `${name}: ${votes[index]} votes`);
            startY += 10;
        });

        pdf.save('Overall_Election_Results.pdf');
    }

    function revealResults() {
        document.getElementById('results-section').style.display = 'block';
        document.getElementById('reveal-results-btn').style.display = 'none';
        document.getElementById('results-hidden-message').style.display = 'none';
    }
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>