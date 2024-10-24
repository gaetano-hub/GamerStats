<?php
session_start();

// Imposta le variabili client
$client_id = '1297867349034270742'; // Sostituisci con il tuo Client ID
$client_secret = 'rdG5BBrw4gWzJRiLX3pdfrERgN3Bm5vm'; // Sostituisci con il tuo Client Secret
$redirect_uri = 'http://localhost/GamerStats/signUp/signUp_ds/callback.php'; // URI di reindirizzamento

// Controlla se c'è un "code" nella URL
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Passaggio 1: Scambia il code per un token di accesso
    $token_url = "https://discord.com/api/oauth2/token";

    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
    ];

    // Invia una richiesta POST per ottenere il token
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $token_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $token_data = json_decode($response, true);

    // Passaggio 2: Ottieni le informazioni dell'utente usando il token
    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // Richiedi i dettagli dell'utente a Discord
        $user_url = "https://discord.com/api/users/@me";

        $headers = [
            "Authorization: Bearer $access_token",
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $user_url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $user_info = json_decode(curl_exec($curl), true);
        curl_close($curl);

        // Qui riceviamo le informazioni dell'utente Discord
        if (isset($user_info['username']) && isset($user_info['email'])) {
            $_SESSION['nickname'] = $user_info['username'];
            $_SESSION['email'] = $user_info['email']; // Assicurati di avere accesso all'email se richiesta.

            // Collega le informazioni con il database (signup)
            $servername = "localhost";
            $db_username = "root";
            $db_password = "";
            $dbname = "GamerStats";

            $conn = new mysqli($servername, $db_username, $db_password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Controlla se l'utente esiste nel database
            $stmt = $conn->prepare("SELECT * FROM users WHERE nickname = ?");
            $stmt->bind_param("s", $_SESSION['nickname']);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                echo "Bentornato, " . $_SESSION['nickname'] . "!";
                // Puoi redirigere l'utente alla home se già registrato
                header("Location: ../../home/home.php");
                exit();
            } else {
                // L'utente non esiste, registriamo l'utente
                
                $stmt_insert = $conn->prepare("INSERT INTO users (nickname, email, password, steamID) VALUES (?, ?, ?, ?)");
                $password = ''; // Se non vuoi una password, puoi usare una stringa vuota
                $steamID = '';
                $stmt_insert->bind_param("ssss", $_SESSION['nickname'], $_SESSION['email'], $password, $steamID);

                if ($stmt_insert->execute()) {
                    echo "Registrazione completata, " . $_SESSION['nickname'];
                    // Puoi redirigere l'utente alla home dopo la registrazione
                    header("Location: ../../home/home.php");
                    exit();
                } else {
                    echo "Errore durante la registrazione.";
                }
                $stmt_insert->close();
            }
        } else {
            echo "Errore: Non è stato possibile ottenere i dettagli dell'utente.";
        }
    } else {
        echo "Errore: Non è stato possibile ottenere il token di accesso.";
    }
} else {
    echo "Errore: Nessun code OAuth2 fornito.";
}
$stmt->close();
$conn->close();
?>
