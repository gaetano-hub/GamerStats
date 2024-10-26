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

    // Prepara la query per trovare l'utente e il suo steamID
    $stmt = $conn->prepare("SELECT password, email, steamID FROM users WHERE nickname = ?");
    $stmt->bind_param("s", $nickname);
    $stmt->execute();
    $stmt->store_result();

    // Controlla se l'utente esiste
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword, $email, $steamID);
        $stmt->fetch();

        // Verifica la password
        if (password_verify($password, $hashedPassword)) {
            echo "Accesso effettuato con successo!";
            // Qui puoi iniziare una sessione o reindirizzare l'utente
            session_start();
            $_SESSION['nickname'] = $nickname; // Memorizza il nickname nella sessione
            $_SESSION['email'] = $email; // Memorizza l'email nella sessione

            // Se steamID esiste, memorizzalo nella sessione
            if (!empty($steamID)) {
                $_SESSION['steamID'] = $steamID;
            }

            header("Location: ../home/home.php");
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
