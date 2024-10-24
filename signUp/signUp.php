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

// Crea la tabella se non esiste già
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) !== TRUE) {
    echo "Errore nella creazione della tabella: " . $conn->error;
}

// Controlla se il metodo della richiesta è POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera e valida i dati dal modulo
    $nickname = trim($_POST['nickname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validazione dell'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email non valida.");
    }

    // Validazione della password (es. lunghezza minima)
    if (strlen($password) < 6) {
        die("La password deve avere almeno 6 caratteri.");
    }

    // Controlla se il nickname o l'email esistono già
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR nickname = ?");
    $stmt->bind_param("ss", $email, $nickname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("Nickname o email già utilizzati.");
    }

    // Crittografia della password
    $passwordHashed = password_hash($password, PASSWORD_DEFAULT);

    // Prepara ed esegui la query per inserire i dati
    $steamID = '';
    $stmt = $conn->prepare("INSERT INTO users (nickname, email, password, steamID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nickname, $email, $passwordHashed, $steamID);
    
    if ($stmt->execute() === TRUE) {
        header("Location: ../login/login.html");
        echo "Registrazione avvenuta con successo!";
    } else {
        echo "Errore: " . $stmt->error;
        error_log("Errore: " . $stmt->error, 3, "errors.log"); // Log degli errori
    }

    $stmt->close(); // Chiude la dichiarazione
}

$conn->close();
?>
