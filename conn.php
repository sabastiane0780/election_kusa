<?php
// Load environment variables (if using .env in the future)
// require_once 'vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "election_kusa";

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Secure error handling
if ($conn->connect_error) {
    // Log the actual error to a file
    error_log("Database connection failed: " . $conn->connect_error, 3, "logs/db_errors.log");

    // Show a user-friendly message
    die("We are currently experiencing technical difficulties. Please try again later.");
}
?>
