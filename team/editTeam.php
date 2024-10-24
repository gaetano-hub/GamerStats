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

// Recupera i dati dal form
$teamName = $_POST['team_name'];
$memberOne = $_POST['memberOne'];
$memberTwo = $_POST['memberTwo'];
$memberThree = $_POST['memberThree'];
$memberFour = $_POST['memberFour'];
$memberFive = $_POST['memberFive'];

// Prepara la query per modificare la riga della tabella teams
$stmt = $conn->prepare("UPDATE teams SET member_one = ?, member_two = ?, member_three = ?, member_four = ?, member_five = ? WHERE team_name = ?");
$stmt->bind_param("ssssss", $memberOne, $memberTwo, $memberThree, $memberFour, $memberFive, $teamName);

if ($stmt->execute() === TRUE) {
    header("Location: teamPage.php?team=" . $teamName);
} else {
    echo "Errore: " . $stmt->error;
    error_log("Errore: " . $stmt->error, 3, "errors.log"); // Log degli errori
}

// Chiudi la connessione
$conn->close();
?>
