<?php
// handle_openid.php

session_start(); // Inizia la sessione

if (isset($_GET['openid_mode']) && $_GET['openid_mode'] == 'id_res') {
    // Prepara i parametri per la verifica della risposta
    $params = [
        'openid.ns'         => 'http://specs.openid.net/auth/2.0',
        'openid.mode'       => 'check_authentication',
        'openid.signed'     => $_GET['openid_signed'],
        'openid.sig'        => $_GET['openid_sig'],
        'openid.identity'   => $_GET['openid_identity'],
        'openid.return_to'  => $_GET['openid_return_to'],
        'openid.response_nonce' => $_GET['openid_response_nonce'],
        'openid.assoc_handle'   => $_GET['openid_assoc_handle'],
    ];

    // Aggiungi i parametri "signed" alla richiesta
    $signedParams = explode(',', $_GET['openid_signed']);
    foreach ($signedParams as $item) {
        $key = 'openid.' . $item;
        if (isset($_GET[$item])) {
            $params[$key] = $_GET[$item];
        }
    }

    // Invia una richiesta cURL a Steam per verificare la risposta
    $ch = curl_init('https://steamcommunity.com/openid/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $response = curl_exec($ch);
    curl_close($ch);

    // Verifica la risposta
    if (strpos($response, 'is_valid:true') === false) { // Cambiato == false a !== false per autenticazione riuscita
        // Autenticazione riuscita
        $steamID64 = str_replace('https://steamcommunity.com/openid/id/', '', $_GET['openid_identity']);

        // Ora otteniamo le informazioni del profilo
        $apiKey = '8A345C81E607D2E02274B11D4834675A'; // Inserisci qui la tua chiave API
        $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$apiKey&steamids=$steamID64";

        // Invia la richiesta per ottenere il profilo
        $profileResponse = file_get_contents($url);
        $profileData = json_decode($profileResponse, true);

        // Controlla se ci sono dati
        if (isset($profileData['response']['players']) && count($profileData['response']['players']) > 0) {
            $player = $profileData['response']['players'][0];

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

            // Crea la tabella se non esiste già
            $sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    steamID VARCHAR(255) NOT NULL
)";

            // Esegui la query
            if ($conn->query($sql) === TRUE) {
                echo "Tabella 'users' creata con successo.";
            } else {
                echo "Errore nella creazione della tabella: " . $conn->error;
            }

            // Prepara la query per verificare se lo steamID64 esiste nel database
            $stmt = $conn->prepare("SELECT * FROM users WHERE steamID = ?");
            $stmt->bind_param("s", $steamID64);
            $stmt->execute();
            $result = $stmt->get_result();

            // Verifica se lo steamID64 esiste nel database
            if ($result->num_rows > 0) {
                // SteamID64 trovato, l'utente è già registrato
                $user = $result->fetch_assoc(); // Recupera i dati dell'utente
                // $_SESSION['steamID64'] = $steamID64;
                // $_SESSION['nickname'] = $user['nickname']; // Associa il nickname
                echo "Benvenuto, " . htmlspecialchars($user['nickname']) . " con SteamID: " . $_SESSION['steamID64'] . "!";
                header("Location: ../myProfile.php");
                exit();
            } else {
                // SteamID64 non trovato, ora controlliamo se esiste già un nickname
                $nickname = $_SESSION['nickname'];

                // Prepara la query per cercare il nickname
                $stmt = $conn->prepare("SELECT * FROM users WHERE nickname = ?");
                $stmt->bind_param("s", $nickname);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Nickname trovato, aggiorniamo lo Steam ID
                    $updateStmt = $conn->prepare("UPDATE users SET steamID = ? WHERE nickname = ?");
                    $updateStmt->bind_param("ss", $steamID64, $nickname);
                    if ($updateStmt->execute()) {
                        $_SESSION['steamID'] = $steamID64;
                        echo "Steam ID aggiornato per l'utente: " . htmlspecialchars($nickname) . "!";
                        header("Location: ../myProfile.php");
                        exit();
                    } else {
                        echo "Errore nell'aggiornamento dello Steam ID.";
                    }
                } else {
                    // Nickname non trovato, reindirizza alla pagina di registrazione
                    echo "Utente non trovato. Devi registrarti.";
                    header("Location: ../myProfile.php");
                    exit();
                }
            }
        } else {
            echo "Nessun giocatore trovato con l'ID fornito.";
        }
    } else {
        echo "Autenticazione fallita. Verifica non riuscita. Identity: " . htmlspecialchars($_GET['openid_identity']);
    }
} else {
    echo "Nessuna richiesta di autenticazione ricevuta.";
}
