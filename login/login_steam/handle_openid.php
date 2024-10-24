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
    if (strpos($response, 'is_valid:true') === false) { // Cambia == false a !== false per autenticazione riuscita
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

            $_SESSION['nickname'] = $player['personaname'];

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

            $stmt = $conn->prepare("SELECT * FROM users WHERE steamID = ?");
            $stmt->bind_param("s", $steamID64);
            $stmt->execute();
            $result = $stmt->get_result();

            // Verifica se lo steamID64 esiste nel database
            if ($result->num_rows > 0) {
                // SteamID64 trovato, l'utente è già registrato
                // Memorizza lo steamID64 nella sessione
                $_SESSION['steamID'] = $steamID64;

                // Ottieni il nickname attuale dell'utente
                $row = $result->fetch_assoc();
                $_SESSION['nickname'] = $row['nickname'];

                // // Controlla se il nickname nella sessione è diverso da quello attuale
                // if ($currentNickname !== $_SESSION['nickname']) {
                //     // Aggiorna il nickname nel database
                //     $updateStmt = $conn->prepare("UPDATE users SET nickname = ? WHERE steamID = ?");
                //     $updateStmt->bind_param("ss", $_SESSION['nickname'], $steamID64);
                //     $updateStmt->execute();
                //     $updateStmt->close();
                // }

                echo "Benvenuto, utente con SteamID: " . htmlspecialchars($_SESSION['steamID64']) . "!";
                header("Location: ../../home/home.php");
                exit();
            } else {
                $email = '';
                $stmt_insert = $conn->prepare("INSERT INTO users (nickname, email, password, steamID) VALUES (?, ?, ?, ?)");
                $password = ''; // Se non vuoi una password, puoi usare una stringa vuota
                $stmt_insert->bind_param("ssss", $_SESSION['nickname'], $email, $password, $steamID64);

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



            echo "Login effettuato con successo. Nome: " . htmlspecialchars($player['personaname']) . "<br>";
            echo "Profilo URL: " . htmlspecialchars($player['profileurl']) . "<br>";
            echo "Immagine Profilo: <img src='" . htmlspecialchars($player['avatar']) . "' /><br>";

            // Reindirizza alla home page dopo 3 secondi
            header("Location: ../../home/home.php");
            exit;
        } else {
            echo "Nessun giocatore trovato con l'ID fornito.";
        }
    } else {
        echo "Autenticazione fallita. Verifica non riuscita. Identity: " . htmlspecialchars($_GET['openid_identity']);
    }
} else {
    echo "Nessuna richiesta di autenticazione ricevuta.";
}
