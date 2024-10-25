<?php
// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GamerStats";

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$teamName = $_GET['teamName'];

$stmt = $conn->prepare("DELETE FROM teams WHERE team_name = ?");
$stmt->bind_param("s", $teamName);
$stmt->execute();
$conn->close();
header("Location: ../home/home.php");
?>
