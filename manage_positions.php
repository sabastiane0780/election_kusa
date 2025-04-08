<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include 'conn.php';

// Fetch positions data
$sql = "SELECT * FROM positions";
$result = $conn->query($sql);

// Add new position manually (using prepared statement)
if (isset($_POST['add_position'])) {
    $position_name = $_POST['position_name'];

    $sqlInsert = "INSERT INTO positions (position_name) VALUES (?)";
    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param("s", $position_name);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success' role='alert'>New position added successfully!</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Process document upload
if (isset($_POST['upload_document'])) {
    $file = $_FILES['positions_file'];

    // Check for file upload errors
    if ($file['error'] == 0) {
        // Get the file extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        if ($ext == 'csv') {
            // Process CSV file
            $filePath = 'uploads/' . $file['name'];
            move_uploaded_file($file['tmp_name'], $filePath);

            // Open the CSV file
            $handle = fopen($filePath, 'r');
            if ($handle) {
                // Skip the header row
                fgetcsv($handle);

                // Loop through the CSV rows
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $position_name = $data[0]; // Assuming position name is in the first column

                    // Insert position into database (using prepared statement)
                    $sqlInsert = "INSERT INTO positions (position_name) VALUES (?)";
                    $stmt = $conn->prepare($sqlInsert);
                    $stmt->bind_param("s", $position_name);
                    $stmt->execute();
                    $stmt->close(); // Close the statement in each iteration
                }

                fclose($handle);
                echo "<div class='alert alert-success' role='alert'>Positions have been added successfully!</div>";
            }
        } else {
            echo "<div class='alert alert-warning' role='alert'>Please upload a valid CSV file.</div>";
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error uploading file.</div>";
    }
}

// Edit position
if (isset($_POST['edit_position'])) {
    $position_id = $_POST['position_id'];
    $position_name = $_POST['position_name'];

    $sqlUpdate = "UPDATE positions SET position_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("si", $position_name, $position_id);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success' role='alert'>Position updated successfully!</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Delete position
if (isset($_POST['delete_position'])) {
    $position_id = $_POST['position_id'];

    $sqlDelete = "DELETE FROM positions WHERE id = ?";
    $stmt = $conn->prepare($sqlDelete);
    $stmt->bind_param("i", $position_id);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success' role='alert'>Position deleted successfully!</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions - KUSA Voting System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard {
            margin-top: 50px;
        }

        .card-header {
            background-color: #007bff;
            color: white;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .navbar {
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 30px;
        }
        footer a {
            color: #fff;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="admin_dashboard.php">
        <img src="images/kusalogo.png" alt="Logo" style="width: 40px; height: 40px; margin-right: 10px;">
        KUSA Voting System
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="manage_positions.php">Manage Positions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_candidates.php">Manage Candidates</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_voters.php">Manage Voters</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container dashboard">
    <h2>Manage Positions</h2>

    <div class="card mb-4">
        <div class="card-header">
            <h4>Manually Add Position</h4>
        </div>
        <div class="card-body">
            <form action="manage_positions.php" method="POST">
                <div class="form-group">
                    <label for="position_name">Position Name</label>
                    <input type="text" class="form-control" id="position_name" name="position_name" required>
                </div>
                <button type="submit" name="add_position" class="btn btn-primary">Add Position</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>Upload Positions (CSV)</h4>
        </div>
        <div class="card-body">
            <form action="manage_positions.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="positions_file">Upload Positions (CSV file)</label>
                    <input type="file" class="form-control" id="positions_file" name="positions_file" accept=".csv" required>
                </div>
                <button type="submit" name="upload_document" class="btn btn-primary">Upload Positions</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Existing Positions</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Position Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($position = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $position['id']; ?></td>
                            <td><?php echo $position['position_name']; ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal<?php echo $position['id']; ?>">Edit</button>

                                <form action="manage_positions.php" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                    <button type="submit" name="delete_position" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this position?');">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?php echo $position['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $position['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $position['id']; ?>">Edit Position</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form action="manage_positions.php" method="POST">
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="position_name">Position Name</label>
                                                <input type="text" class="form-control" id="position_name" name="position_name" value="<?php echo $position['position_name']; ?>" required>
                                            </div>
                                            <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="submit" name="edit_position" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="bg-dark text-white text-center py-3 mt-4">
    &copy; <?php echo date("Y"); ?> KUSA Voting System. All rights reserved.
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>