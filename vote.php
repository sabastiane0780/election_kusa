<?php
session_start();
include 'conn.php';

// Initialize message variables
$vote_submitted_message = "";
$vote_error_message = "";

$election_id = isset($_GET['election_id']) ? $_GET['election_id'] : null;
$voter_id = $_SESSION['voter_id'] ?? null;

if (!$election_id) {
    header("Location: voter_dashboard.php?error=invalid_election");
    exit();
}

if (!$voter_id) {
    header("Location: voter_dashboard.php?error=not_logged_in");
    exit();
}

// Fetch election details
$sql_election = "SELECT * FROM election_status WHERE id = ?";
$stmt_election = $conn->prepare($sql_election);
$stmt_election->bind_param("i", $election_id);
$stmt_election->execute();
$result_election = $stmt_election->get_result();

if ($result_election->num_rows == 0) {
    header("Location: voter_dashboard.php?error=election_not_found");
    exit();
}
$election = $result_election->fetch_assoc();

// Check if the voter has already voted in this election
$sql_check_voted_election = "SELECT * FROM votes WHERE voter_id = ? AND election_id = ?";
$stmt_check_voted_election = $conn->prepare($sql_check_voted_election);
$stmt_check_voted_election->bind_param("ii", $voter_id, $election_id);
$stmt_check_voted_election->execute();
$result_check_voted_election = $stmt_check_voted_election->get_result();

if ($result_check_voted_election->num_rows > 0) {
    header("Location: voter_dashboard.php?vote=already_voted");
    exit();
}

// Fetch positions for the current election
$sql_positions = "SELECT p.id AS position_id, p.position_name
                    FROM positions p
                    JOIN election_positions ep ON p.id = ep.position_id
                    WHERE ep.election_id = ?";
$stmt_positions = $conn->prepare($sql_positions);
$stmt_positions->bind_param("i", $election_id);
$stmt_positions->execute();
$result_positions = $stmt_positions->get_result();

$positions = [];
if ($result_positions && $result_positions->num_rows > 0) {
    while ($row = $result_positions->fetch_assoc()) {
        $positions[] = $row;
    }
}

// Handle voting submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    $vote_successful = true;
    $all_selections_made = true; // Flag to track if all required selections were made

    foreach ($positions as $position) {
        $position_id = $position['position_id'];

        // Fetch candidates for the current position AND the current election
        $sql_candidates_check = "SELECT candidate_id
                                    FROM candidates
                                    WHERE position_id = ?
                                    AND election_id = ?";
        $stmt_candidates_check = $conn->prepare($sql_candidates_check);
        $stmt_candidates_check->bind_param("ii", $position_id, $election_id);
        $stmt_candidates_check->execute();
        $result_candidates_check = $stmt_candidates_check->get_result();
        $num_candidates = $result_candidates_check->num_rows;

        if ($num_candidates > 0) {
            if (!isset($_POST['vote'][$position_id])) {
                $vote_error_message = "Please select a candidate for all available positions.";
                $vote_successful = false;
                $all_selections_made = false;
                break; // Exit the loop as an error occurred
            } else {
                $selected_candidate_id = $_POST['vote'][$position_id];

                // Record the vote
                $sql_insert_vote = "INSERT INTO votes (voter_id, election_id, position_id, candidate_id, vote_date) VALUES (?, ?, ?, ?, NOW())";
                $stmt_insert_vote = $conn->prepare($sql_insert_vote);
                $stmt_insert_vote->bind_param("iiii", $voter_id, $election_id, $position_id, $selected_candidate_id);
                if (!$stmt_insert_vote->execute()) {
                    $vote_error_message .= "Error recording vote for position " . $position_id . ": " . $stmt_insert_vote->error . "<br>";
                    $vote_successful = false;
                    break;
                }
            }
        }
    }

    if ($vote_successful && $all_selections_made) {
        $conn->commit();
        $_SESSION['vote_submitted'] = true; // Set a session flag
        header("Location: voter_dashboard.php?vote=success");
        exit();
    } else {
        $conn->rollback();
        if ($all_selections_made && !$vote_error_message) {
            $vote_error_message = "An error occurred during vote submission."; // General error if no specific DB error
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - <?php echo htmlspecialchars($election['election_name'] ?? 'Election'); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .navbar {
            background-color: #343a40;
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .container {
            margin-top: 30px;
            flex-grow: 1;
        }
        .candidate-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .candidate-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 15px;
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
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
        .btn-primary {
            background-color: #28a745;
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
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 5px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .alert-success, .alert-danger {
            border-radius: 5px;
            margin-top: 20px;
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

<nav class="navbar">
    <a class="navbar-brand" href="voter_dashboard.php">
        <img src="images/kusalogo.png" alt="KUSA Logo">
        KUSA Voting System
    </a>
</nav>

<div class="container mt-5">
    <p><a href="voter_dashboard.php" class="btn btn-secondary">Back to Dashboard</a></p>
    <h2>Vote - <?php echo htmlspecialchars($election['election_name'] ?? 'Election'); ?></h2>

    <?php if ($vote_submitted_message): ?>
        <div class="alert alert-success"><?php echo $vote_submitted_message; ?></div>
    <?php endif; ?>

    <?php if ($vote_error_message): ?>
        <div class="alert alert-danger"><?php echo $vote_error_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($positions)): ?>
        <form method="POST" action="">
            <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
            <?php foreach ($positions as $position): ?>
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo htmlspecialchars($position['position_name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch candidates with image, name, and position name for the current position AND election
                        $sql_candidates = "SELECT c.candidate_id, c.name, c.image, p.position_name
                                            FROM candidates c
                                            INNER JOIN positions p ON c.position_id = p.id
                                            WHERE c.position_id = ?
                                            AND c.election_id = ?";
                        $stmt_candidates = $conn->prepare($sql_candidates);
                        $stmt_candidates->bind_param("ii", $position['position_id'], $election_id);
                        $stmt_candidates->execute();
                        $result_candidates = $stmt_candidates->get_result();
                        $num_candidates = $result_candidates->num_rows;

                        if ($result_candidates && $result_candidates->num_rows > 0):
                            while ($candidate = $result_candidates->fetch_assoc()):
                        ?>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="vote[<?php echo $position['position_id']; ?>]" value="<?php echo $candidate['candidate_id']; ?>" <?php if ($num_candidates > 0) echo 'required'; ?>>
                                <label class="form-check-label candidate-info">
                                    <?php if ($candidate['image']): ?>
                                        <img src="<?php echo htmlspecialchars($candidate['image']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="candidate-image">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($candidate['name']); ?> (<?php echo htmlspecialchars($position['position_name']); ?>)
                                </label>
                            </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <p class="text-muted">No candidates available for this position.</p>
                            <input type="hidden" name="vote[<?php echo $position['position_id']; ?>]" value="0">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary btn-block">Submit Vote</button>
        </form>
    <?php else: ?>
        <p class="text-muted">No voting positions available for this election.</p>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="container text-center">
        <img src="images/kusalogo.png" alt="KUSA Logo" class="footer-logo">
        <span>Powered by SABZ MEDIA</span>
        <p class="mt-2">&copy; <?php echo date("Y"); ?> KUSA Voting System. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
    </div>
</footer>

</body>
</html>