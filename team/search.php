<?php
// Connessione al database
$servername = "localhost"; // Sostituisci con i tuoi dettagli del server
$username = "root"; // Sostituisci con il tuo username del database
$password = ""; // Sostituisci con la tua password del database
$dbname = "gamerstats"; // Sostituisci con il nome del tuo database

$conn = new mysqli($servername, $username, $password, $dbname);

// Controllo della connessione
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verifica che la richiesta sia POST e che ci sia un valore per 'searchString'
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['searchString'])) {
    $searchString = $_POST['searchString'];

    // Query per cercare nickname che iniziano con il valore inserito
    $stmt = $conn->prepare("SELECT team_name FROM teams WHERE team_name LIKE ?");
    $searchParam = '%' . $searchString . '%';
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<li><a class='dropdown-item' style='color: var(--text_color);' href='teamPage.php?team=" . htmlspecialchars(urlencode($row['team_name'])) . "'>" . htmlspecialchars($row['team_name']) . "</a></li>";
        }
    } else {
        echo "<li><a class='dropdown-item' style='color: var(--text_color);'>No results found</a></li>";
    }

    $stmt->close();
}

$conn->close();
?>