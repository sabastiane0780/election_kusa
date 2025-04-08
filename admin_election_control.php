<?php
session_start();
include 'conn.php';

$message = ""; // Initialize $message to prevent the "undefined variable" warning.

// Fetch the system voter_id (assumes the "system" voter exists)
$sqlVoter = "SELECT voter_id FROM voters WHERE username = 'system' LIMIT 1";
$resultVoter = $conn->query($sqlVoter);
$systemVoter = $resultVoter->fetch_assoc();
$voter_id = $systemVoter ? $systemVoter['voter_id'] : 0;  // Fallback to 0 if not found

// Fetch all available positions for general elections
$sqlPositions = "SELECT * FROM positions ORDER BY position_name";
$positionsResult = $conn->query($sqlPositions);

$positions = [];
$position_ids = []; // Array to store position IDs
if ($positionsResult && $positionsResult->num_rows > 0) {
    while ($row = $positionsResult->fetch_assoc()) {
        $positions[] = $row['position_name'];
        $position_ids[$row['position_name']] = $row['id']; // Mapping position name to position ID
    }
}

// Handle open election request for specific or all positions
if (isset($_POST['open_election'])) {
    $election_name = $_POST['election_name'];
    $selected_positions = $_POST['positions']; // Array of selected position names
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Insert the main election status record
    $sql = "INSERT INTO election_status (status, votes_cast, votes_remaining, voter_id, election_name, start_time, end_time) 
            VALUES ('open', 0, 0, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $voter_id, $election_name, $start_time, $end_time);
    
    if ($stmt->execute()) {
        $election_id = $stmt->insert_id; // Get the ID of the inserted election record
        
        // Check if election_id is valid (greater than 0)
        if ($election_id > 0) {
            // Now insert positions for this election (using position IDs from the positions table)
            foreach ($selected_positions as $position_name) {
                $position_id = $position_ids[$position_name]; // Get the position ID
                $sqlPosition = "INSERT INTO election_positions (election_id, position_id) VALUES (?, ?)";
                $stmtPosition = $conn->prepare($sqlPosition);
                $stmtPosition->bind_param("ii", $election_id, $position_id);
                $stmtPosition->execute();
            }

            $message = "Election for selected positions opened successfully!";
        } else {
            $message = "Error opening election: Invalid election ID.";
        }
    } else {
        $message = "Error opening election: " . $conn->error;
    }
}

// Handle close election request
if (isset($_POST['close_election'])) {
    $election_id = $_POST['election_id'];

    // Check if the election exists before attempting to close it
    $sqlCheck = "SELECT id FROM election_status WHERE id = ? LIMIT 1";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $election_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        // Election exists, proceed to close
        $sql = "UPDATE election_status SET status='closed' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $election_id);
        if ($stmt->execute()) {
            $message = "Election closed successfully!";
        } else {
            $message = "Error closing election.";
        }
    } else {
        // Election does not exist
        $message = "Election with ID $election_id not found.";
    }
}

// Handle delete election request
if (isset($_POST['delete_election'])) {
    $election_id = $_POST['election_id'];

    // Delete election
    $sql = "DELETE FROM election_status WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $election_id);
    if ($stmt->execute()) {
        $message = "Election deleted successfully!";
    } else {
        $message = "Error deleting election: " . $conn->error;
    }
}

// Fetch all elections
$elections = $conn->query("SELECT * FROM election_status ORDER BY id DESC");

if (!$elections) {
    $message = "Error fetching elections: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Elections</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this election? This action cannot be undone.');
        }
    </script>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="admin_dashboard.php">
        <img src="images/kusalogo.png" alt="Logo" style="height: 50px;"> KUSA Voting System
    </a>
</nav>

<div class="container mt-5">
    <h2 class="mb-4">Manage Elections</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Open a New Election Form -->
    <form method="POST" class="mb-4">
        <h4>Open a New Election for All or Specific Positions</h4>
        <input type="text" name="election_name" class="form-control mb-2" placeholder="Election Name" required>
        
        <!-- Checkboxes for selecting positions -->
        <label for="positions">Select Positions</label>
        <div class="form-check">
            <?php foreach ($positions as $position): ?>
                <input type="checkbox" class="form-check-input" name="positions[]" value="<?php echo $position; ?>" id="position_<?php echo $position; ?>">
                <label class="form-check-label" for="position_<?php echo $position; ?>"><?php echo $position; ?></label>
                <br>
            <?php endforeach; ?>
        </div>

        <input type="datetime-local" name="start_time" class="form-control mb-2" required>
        <input type="datetime-local" name="end_time" class="form-control mb-2" required>
        <button type="submit" name="open_election" class="btn btn-success">Open Election</button>
    </form>

    <!-- Display Existing Elections -->
    <h4>Existing Elections</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Election Name</th>
                <th>Positions</th>
                <th>Status</th>
                <th>Start</th>
                <th>End</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if ($elections && $elections->num_rows > 0):
            while ($row = $elections->fetch_assoc()):
                // Fetch positions for this election
                $election_id = $row['id'];
                $sqlPositions = "SELECT p.position_name FROM election_positions ep 
                                 JOIN positions p ON ep.position_id = p.id 
                                 WHERE ep.election_id = ?";
                $stmtPositions = $conn->prepare($sqlPositions);
                $stmtPositions->bind_param("i", $election_id);
                $stmtPositions->execute();
                $resultPositions = $stmtPositions->get_result();
        ?>
            <tr>
                <td><?php echo htmlspecialchars($row['election_name']); ?></td>
                <td>
                    <?php while ($position = $resultPositions->fetch_assoc()): ?>
                        <p><?php echo htmlspecialchars($position['position_name']); ?></p>
                    <?php endwhile; ?>
                </td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                <td>
                    <?php if ($row['status'] == 'open'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="election_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="close_election" class="btn btn-danger btn-sm">Close</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">Closed</span>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                        <input type="hidden" name="election_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_election" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No elections found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
