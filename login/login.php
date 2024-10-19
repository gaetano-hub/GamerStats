<?php
$servername = "localhost";
$username = "root"; // Il nome utente predefinito di XAMPP è "root"
$password = ""; // Di solito la password è vuota
$dbname = "GamerStats";

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal modulo di login
    $nickname = trim($_POST['nickname']);
    $password = $_POST['password'];

    // Prepara la query per trovare l'utente
    $stmt = $conn->prepare("SELECT password FROM users WHERE nickname = ?");
    $stmt->bind_param("s", $nickname);
    $stmt->execute();
    $stmt->store_result();

    // echo "Numero di utenti trovati: " . $nickname . "<br>";
    // Controlla se l'utente esiste
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();

        // Verifica la password
        if (password_verify($password, $hashedPassword)) {
            echo "Accesso effettuato con successo!";
            // Qui puoi iniziare una sessione o reindirizzare l'utente
            session_start();
            $_SESSION['nickname'] = $nickname; // Memorizza il nickname nella sessione
            header("Location: ../home/home.html");
            exit();
        } else {
            echo "Password errata.";
        }
    } else {
        echo "Utente non trovato.";
    }

    $stmt->close(); // Chiude la dichiarazione
}

$conn->close();
?>
