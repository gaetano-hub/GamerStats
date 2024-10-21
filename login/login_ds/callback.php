<?php
session_start();

// Configura le informazioni dell'app
$client_id = '1297867349034270742'; // Sostituisci con il tuo Client ID
$client_secret = 'rdG5BBrw4gWzJRiLX3pdfrERgN3Bm5vm'; // Sostituisci con il tuo Client Secret
$redirect_uri = 'http://localhost/GamerStats/login/login_ds/callback.php'; // Sostituisci con l'URL del tuo sito

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Richiedi il token di accesso a Discord
    $token_url = 'https://discord.com/api/oauth2/token';
    $params = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($params),
        ],
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($token_url, false, $context);
    
    if ($response === false) {
        die("Errore nella richiesta del token di accesso.");
    }

    $token_info = json_decode($response, true);

    // Ottieni le informazioni dell'utente
    if (isset($token_info['access_token'])) {
        $access_token = $token_info['access_token'];
        $user_url = 'https://discord.com/api/users/@me';

        $user_options = [
            'http' => [
                'header'  => "Authorization: Bearer $access_token\r\n",
            ],
        ];

        $user_context  = stream_context_create($user_options);
        $user_response = @file_get_contents($user_url, false, $user_context);
        
        if ($user_response === false) {
            die("Errore nel recupero delle informazioni dell'utente.");
        }

        $user_info = json_decode($user_response, true);

        // Salva le informazioni dell'utente nella sessione
        $_SESSION['user_info'] = $user_info;
        $_SESSION['nickname'] = $user_info['username'];

        // Connessione al database
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

        // Prepara la query per verificare se il nickname esiste nel database
        $nickname = $_SESSION['nickname']; // Usa la variabile della sessione
        $stmt = $conn->prepare("SELECT * FROM users WHERE nickname = ?");
        $stmt->bind_param("s", $nickname);
        $stmt->execute();
        $stmt->store_result();

        // Verifica se il nickname esiste nel database
        if ($stmt->num_rows > 0) {
            // Nickname trovato, l'utente è già registrato
            echo "Benvenuto, " . $_SESSION['nickname'] . "!";
            // Puoi fare il reindirizzamento ad una pagina protetta
            header("Location: ../../home/home.php");
            exit();
        } else {
            // Nickname non trovato, l'utente non è registrato
            echo "Utente non trovato. Devi registrarti.";
            // Puoi reindirizzarlo ad una pagina di registrazione o registrarlo automaticamente
            header("Location: ../../signUp/signUp.html");
            exit();
        }
    } else {
        echo 'Errore durante il recupero del token di accesso.';
    }
} else {
    echo 'Nessun codice di autorizzazione ricevuto.';
}

// Chiudi la connessione
$stmt->close();
$conn->close();
?>
