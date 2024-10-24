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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newLeader'])) {
    $newLeader = $_POST['newLeader'];
    $teamName = $_POST['teamName'];

    // Preleva il leader attuale
    $stmt = $conn->prepare("SELECT leader FROM teams WHERE team_name = ?");
    $stmt->bind_param("s", $teamName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $currentLeader = $row['leader'];

    // Scambia il leader con il nuovo membro
    $stmt = $conn->prepare("UPDATE teams SET leader = ?, member_one = CASE WHEN member_one = ? THEN ? ELSE member_one END, member_two = CASE WHEN member_two = ? THEN ? ELSE member_two END, member_three = CASE WHEN member_three = ? THEN ? ELSE member_three END, member_four = CASE WHEN member_four = ? THEN ? ELSE member_four END, member_five = CASE WHEN member_five = ? THEN ? ELSE member_five END WHERE team_name = ?");
    $stmt->bind_param("ssssssssssss", $newLeader, $newLeader, $currentLeader, $newLeader, $currentLeader, $newLeader, $currentLeader, $newLeader, $currentLeader, $newLeader, $currentLeader, $teamName);

    if ($stmt->execute() === TRUE) {
        header("Location: teamPage.php?team=" . urlencode($teamName));
    } else {
        echo "Errore: " . $stmt->error;
    }
}

// Chiudi la connessione
$conn->close();
?>
