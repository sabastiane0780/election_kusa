<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include 'conn.php';

// Fetch existing candidates and their positions
$sqlCandidates = "SELECT c.candidate_id, c.name, p.position_name, c.image, c.election_id
                    FROM candidates c
                    INNER JOIN positions p ON c.position_id = p.id
                    ORDER BY c.candidate_id DESC";
$resultCandidates = $conn->query($sqlCandidates);

// Fetch available positions for adding a new candidate and for editing existing ones
$sqlPositions = "SELECT id, position_name FROM positions ORDER BY position_name";
$resultPositions = $conn->query($sqlPositions);

// Fetch available elections (open or pending)
$sqlElections = "SELECT id, election_name FROM election_status WHERE status = 'open' OR status = 'pending' ORDER BY start_time";
$resultElections = $conn->query($sqlElections);

// Function to display alert messages
function displayAlert($message, $type = 'info') {
    echo "<div class='alert alert-$type'>$message</div>";
}

// Add new candidate to the database
if (isset($_POST['add_candidate'])) {
    $name = $_POST['name'];
    $position_id = $_POST['position_id'];
    $image = $_FILES['image']['name'];
    $election_id = $_POST['election_id'];

    // Ensure uploads directory exists
    if (!is_dir('uploads/')) {
        mkdir('uploads/', 0777, true);
    }

    // Check for upload errors
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        displayAlert("File upload failed with error code: " . $_FILES['image']['error'], 'danger');
    } else {
        // Upload image to the server
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Insert the new candidate into the database
            $sqlInsert = "INSERT INTO candidates (name, position_id, image, election_id)
                                VALUES (?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("sisi", $name, $position_id, $target_file, $election_id);

            if ($stmtInsert->execute()) {
                displayAlert("New candidate added successfully!", 'success');
            } else {
                displayAlert("Error adding candidate: " . $stmtInsert->error, 'danger');
            }
            $stmtInsert->close();
        } else {
            displayAlert("Failed to upload image. Check folder permissions.", 'danger');
        }
    }
}

// Edit candidate details
if (isset($_POST['edit_candidate'])) {
    $candidate_id = $_POST['candidate_id'];
    $name = $_POST['name'];
    $position_id = $_POST['position_id'];
    $election_id = $_POST['election_id'];
    $image = $_FILES['image']['name'];
    $target_file = "";

    // Handle image update
    if ($image) {
        // Check for upload errors
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            displayAlert("File upload failed with error code: " . $_FILES['image']['error'], 'danger');
        } else {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($image);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Delete the old image
                $sqlOldImage = "SELECT image FROM candidates WHERE candidate_id = ?";
                $stmtOldImage = $conn->prepare($sqlOldImage);
                $stmtOldImage->bind_param("i", $candidate_id);
                $stmtOldImage->execute();
                $resultOldImage = $stmtOldImage->get_result();
                if ($rowOldImage = $resultOldImage->fetch_assoc()) {
                    if (file_exists($rowOldImage['image'])) {
                        unlink($rowOldImage['image']);
                    }
                }
                $stmtOldImage->close();
            } else {
                displayAlert("Failed to upload new image. Check folder permissions.", 'danger');
                $target_file = null; // Prevent updating with a failed upload
            }
        }
    } else {
        // Keep the old image
        $sqlOldImage = "SELECT image FROM candidates WHERE candidate_id = ?";
        $stmtOldImage = $conn->prepare($sqlOldImage);
        $stmtOldImage->bind_param("i", $candidate_id);
        $stmtOldImage->execute();
        $resultOldImage = $stmtOldImage->get_result();
        if ($rowOldImage = $resultOldImage->fetch_assoc()) {
            $target_file = $rowOldImage['image'];
        }
        $stmtOldImage->close();
    }

    // Update candidate details in the database if target_file is not null (or was fetched)
    if ($target_file !== null) {
        $sqlUpdate = "UPDATE candidates SET name = ?, position_id = ?, image = ?, election_id = ? WHERE candidate_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sisi", $name, $position_id, $target_file, $election_id, $candidate_id);

        if ($stmtUpdate->execute()) {
            displayAlert("Candidate updated successfully!", 'success');
        } else {
            displayAlert("Error updating candidate: " . $stmtUpdate->error, 'danger');
        }
        $stmtUpdate->close();
    }
}

// Handle candidate deletion
if (isset($_GET['delete_id'])) {
    $candidate_id = $_GET['delete_id'];

    // Fetch the candidate's image to delete it from the server
    $sqlDelete = "SELECT image FROM candidates WHERE candidate_id = ?";
    $stmt = $conn->prepare($sqlDelete);
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $candidate = $result->fetch_assoc();
    $stmt->close();

    if ($candidate) {
        // Delete the image from the server
        if (file_exists($candidate['image'])) {
            unlink($candidate['image']);
        }

        // Delete the candidate from the database
        $sqlDeleteCandidate = "DELETE FROM candidates WHERE candidate_id = ?";
        $stmtDelete = $conn->prepare($sqlDeleteCandidate);
        $stmtDelete->bind_param("i", $candidate_id);
        if ($stmtDelete->execute()) {
            displayAlert("Candidate deleted successfully!", 'success');
        } else {
            displayAlert("Error deleting candidate: " . $stmtDelete->error, 'danger');
        }
        $stmtDelete->close();
    } else {
        displayAlert("Candidate not found.", 'danger');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - KUSA Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            margin-bottom: 30px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background-color: #007bff;
            color: white;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-primary, .btn-success {
            border-radius: 20px;
        }

        .btn-danger:hover {
            background-color: #dc3545;
        }

        #imagePreview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            display: none;
            border-radius: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="admin_dashboard.php">
        <img src="images/kusalogo.png" alt="Logo" style="width: 40px; height: 40px; margin-right: 10px;">
        KUSA Voting System
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
            <li class="nav-item active"><a class="nav-link" href="manage_candidates.php">Manage Candidates</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_positions.php">Manage Positions</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_voters.php">Manage Voters</a></li>
            <li class="nav-item"><a class="nav-link" href="view_results.php">View Results</a></li>
            <li class="nav-item"><a class="nav-link btn btn-danger text-white ml-2" href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <h2 class="mb-4 text-primary">Manage Candidates</h2>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4>Existing Candidates</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Candidate Name</th>
                        <th>Position</th>
                        <th>Image</th>
                        <th>Election</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultCandidates->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['candidate_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['position_name']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Candidate Image" width="50" class="rounded"></td>
                            <td><?php
                                $electionNameSQL = "SELECT election_name FROM election_status WHERE id = ?";
                                $stmtElectionName = $conn->prepare($electionNameSQL);
                                if ($stmtElectionName) {
                                    $stmtElectionName->bind_param("i", $row['election_id']);
                                    $stmtElectionName->execute();
                                    $electionNameResult = $stmtElectionName->get_result();
                                    if ($electionNameResult && $electionNameResult->num_rows > 0) {
                                        echo htmlspecialchars($electionNameResult->fetch_assoc()['election_name']);
                                    } else {
                                        echo "Unknown";
                                    }
                                    $stmtElectionName->close();
                                } else {
                                    echo "Error preparing statement";
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal<?php echo $row['candidate_id']; ?>">Edit</button>

                                <div class="modal fade" id="editModal<?php echo $row['candidate_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel">Edit Candidate</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="manage_candidates.php" method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $row['candidate_id']; ?>">
                                                    <div class="form-group">
                                                        <label for="name">Candidate Name</label>
                                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="position_id">Position</label>
                                                        <select class="form-control" name="position_id" required>
                                                            <option value="">Select Position</option>
                                                            <?php
                                                            $resultPositions->data_seek(0); // Reset pointer
                                                            while ($position = $resultPositions->fetch_assoc()) { ?>
                                                                <option value="<?php echo $position['id']; ?>" <?php if ($position['id'] == $row['position_id']) echo 'selected'; ?>><?php echo htmlspecialchars($position['position_name']); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="election_id">Associate with Election</label>
                                                        <select class="form-control" name="election_id" required>
                                                            <option value="">Select Election</option>
                                                            <?php
                                                            $resultElections->data_seek(0); // Reset pointer
                                                            while ($election = $resultElections->fetch_assoc()) {
                                                                $selected = ($election['id'] == $row['election_id']) ? 'selected' : '';
                                                                echo '<option value="' . $election['id'] . '" ' . $selected . '>' . htmlspecialchars($election['election_name']) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="image">Candidate Image</label>
                                                        <input type="file" class="form-control-file" name="image" onchange="previewImage(event, 'imagePreview<?php echo $row['candidate_id']; ?>')">
                                                        <img id="imagePreview<?php echo $row['candidate_id']; ?>" src="<?php echo htmlspecialchars($row['image']); ?>" alt="Preview" style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px; margin-top: 10px;">
                                                    </div>
                                                    <button type="submit" name="edit_candidate" class="btn btn-success btn-block">Update Candidate</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <a href="manage_candidates.php?delete_id=<?php echo $row['candidate_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>Add New Candidate</h4>
        </div>
        <div class="card-body">
            <form action="manage_candidates.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="election_id">Associate with Election</label>
                    <select class="form-control" id="election_id" name="election_id" required>
                        <option value="">Select Election</option>
                        <?php
                        $resultElections->data_seek(0); // Reset pointer
                        while ($election = $resultElections->fetch_assoc()) { ?>
                            <option value="<?php echo $election['id']; ?>"><?php echo htmlspecialchars($election['election_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="name">Candidate Name</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="Enter candidate's full name">
                </div>

                <div class="form-group">
                    <label for="position_id">Position</label>
                    <select class="form-control" id="position_id" name="position_id" required>
                        <option value
                        <option value="">Select Position</option>
                        <?php
                        $resultPositions->data_seek(0); // Reset pointer
                        while ($position = $resultPositions->fetch_assoc()) { ?>
                            <option value="<?php echo $position['id']; ?>"><?php echo htmlspecialchars($position['position_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Candidate Image</label>
                    <input type="file" class="form-control-file" id="image" name="image" required onchange="previewImage(event)">
                    <img id="imagePreview" alt="Preview">
                </div>

                <button type="submit" name="add_candidate" class="btn btn-success btn-block">Add Candidate</button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    function previewImage(event) {
        let image = document.getElementById('imagePreview');
        image.src = URL.createObjectURL(event.target.files[0]);
        image.style.display = 'block';
    }
</script>

</body>
</html>