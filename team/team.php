<?php

//TODO PER ANDREA: RICORDATI DI FARE IL CONTROLLO SUI NICKNAME DEGLI UTENTI

// Connessione al database
$servername = "localhost"; // Modifica con il tuo server DB
$username = "root"; // Modifica con il tuo username DB
$password = ""; // Modifica con la tua password DB
$dbname = "GamerStats"; // Modifica con il nome del tuo database

// Creare connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Crea la tabella se non esiste già
$sql = "CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL,
    game VARCHAR(255) NOT NULL,
    member_one VARCHAR(255),
    member_two VARCHAR(255),
    member_three VARCHAR(255),
    member_four VARCHAR(255),
    member_five VARCHAR(255),
    leader VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) !== TRUE) {
    echo "Errore nella creazione della tabella: " . $conn->error;
}

session_start();
// $_SESSION['nickname'] = 'testuser';

// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);

// Verifica se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ricevi dati dal form
    $teamName = $_POST['teamName'];
    $game = $_POST['game'];
    $memberone = $_POST['memberOne'];
    $membertwo = $_POST['memberTwo'];
    $memberthree = $_POST['memberThree'];
    $memberfour = $_POST['memberFour'];
    $memberfive = $_POST['memberFive'];
    $leader = $_SESSION['nickname'];

    $members = array($memberone, $membertwo, $memberthree, $memberfour, $memberfive, $leader);

    $sameLeader = array_filter($members, function ($member) use ($leader) {
        return $member == $leader;
    });

    if (count($sameLeader) > 1) {
        echo "<script>alert('The leader cannot also be a member of the team');  window.history.back();</script>";
        exit;
    }

    $notFoundMembers = [];
    foreach ($members as $member) {
        if (!empty($member)) {
            $stmt = $conn->prepare("SELECT nickname FROM users WHERE nickname = ?");
            $stmt->bind_param("s", $member);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $notFoundMembers[] = $member;
            }
        }
    }

    if (!empty($notFoundMembers)) {
        $message = "Users ";
        foreach ($notFoundMembers as $index => $member) {
            $message .= $member;
            if ($index < count($notFoundMembers) - 1) {
                $message .= ", ";
            } else {
                $message .= " are not registered on the site";
            }
        }
        echo '<script>alert("' . $message . '"); window.history.back();</script>';
        exit;
    }

    if ($game === "Game") {
        echo '<script>alert("Please choose a valid game"); window.history.back();</script>';
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM teams WHERE team_name = ?");
    $stmt->bind_param("s", $teamName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<script>alert("This team name is already used"); window.history.back();</script>';
        exit;
    }

    if (empty($teamName)) {
        echo '<script>alert("Please enter a valid team name"); window.history.back();</script>';
        exit;
    }
    

    // Inserisci i dati del team nella tabella team
    $sql = "INSERT INTO teams (team_name, game, member_one, member_two, member_three, member_four, member_five, leader) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $teamName, $game, $memberone, $membertwo, $memberthree, $memberfour, $memberfive, $leader);

    if ($stmt->execute() === TRUE) {
        header("Location: ../memberPage/myProfile.php");
    } else {
        echo "Error: " . $stmt->error;
        error_log("Error: " . $stmt->error, 3, "errors.log"); // Log degli errori
    }
}
// Chiudi la connessione
$stmt->close();
$conn->close();
