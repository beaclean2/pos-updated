<?php
header('Content-Type: application/json');

// Database connection details (replace with your actual credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode([]); // Return empty array on error
} else {
    $sql = "SELECT * FROM transactions ORDER BY transaction_date DESC";
    $result = $conn->query($sql);
    $transactions = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    echo json_encode($transactions);
    $conn->close();
}
?>