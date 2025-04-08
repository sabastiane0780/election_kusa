<?php
include 'conn.php';

if (isset($_GET['voter_id'])) {
    $voter_id = intval($_GET['voter_id']);

    $sql = "DELETE FROM voters WHERE voter_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voter_id);

    if ($stmt->execute()) {
        header("Location: manage_voters.php?success=Voter deleted successfully");
        exit();
    } else {
        echo "Error deleting voter.";
    }
} else {
    echo "Invalid request.";
}
?>
