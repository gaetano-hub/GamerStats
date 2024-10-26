<?php
// Set response headers
header('Content-Type: text/plain');

// Logging function
function logMessage($message) {
    $logFile = 'debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logMessage("POST request received.");
        // Process the data as usual
        $dota2users = [];
        // Connessione al database
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "gamerstats";
    
        logMessage("Connecting to the database.");
        $conn = new mysqli($servername, $username, $password, $dbname);
    
        // Controlla la connessione
        if ($conn->connect_error) {
            logMessage("Connection failed: " . $conn->connect_error);
            die("Connection failed: " . $conn->connect_error);
        }
    
        // Query per ottenere tutti i dati degli utenti con SteamID
        $query2 = "SELECT nickname, steamID FROM users WHERE steamID NOT LIKE '' AND steamID IS NOT NULL";
        $result = $conn->query($query2);
    
        if ($result->num_rows > 0) {
            logMessage("Users found in the database.");
            while ($row = $result->fetch_assoc()) {
                $nickname = htmlspecialchars($row['nickname']);
                $steamID = htmlspecialchars($row['steamID']);
                $dota2users[] = [
                    'nickname' => $nickname,
                    'steamID' => $steamID
                ];
            }
        } else {
            logMessage("No users found in the database.");
        }
        foreach ($dota2users as $user) {
            $name = $user['nickname'];
            $account_id = (int)$user['steamID'] - 76561197960265728; // Convert SteamID to account_id
            logMessage("Updating database for user: $name with account_id: $account_id.");
            updateDatabase($name, $account_id);
        }
} else {
    // Handle invalid request methods
    http_response_code(405); // Method Not Allowed
    logMessage("Invalid request method. Only POST requests are allowed.");
    echo "Error: Only POST requests are allowed.";
}

function updateDatabase($name, $account_id) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        logMessage("Connection failed: " . $conn->connect_error);
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    $account_info = getAccountInfo($account_id);
    $wl = getPlayerWL($account_id);
    $recent_matchesD = getPlayerRecentMatches($account_id);

    $sql = "DROP TABLE IF EXISTS `$name`";
    if ($conn->query($sql) !== TRUE) {
        logMessage("Error dropping table for $name: " . $conn->error);
        echo json_encode(['error' => 'Error dropping table: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$name` (
        account_id BIGINT,
        personaname VARCHAR(255),
        avatar VARCHAR(255),
        rank_tier INTEGER,
        win INTEGER,
        lose INTEGER,
        match_id BIGINT,
        kills INTEGER,
        deaths INTEGER,
        assists INTEGER,
        average_rank INTEGER,
        radiant_score VARCHAR(255),
        dire_score VARCHAR(255)
    )";

    if ($conn->query($sql) !== TRUE) {
        logMessage("Error creating table for $name: " . $conn->error);
        echo json_encode(['error' => 'Error creating table: ' . $conn->error]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO `$name`
        (account_id, personaname, avatar, rank_tier, win, lose, match_id, kills, deaths, assists, average_rank, radiant_score, dire_score) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        logMessage("Error preparing statement for $name: " . $conn->error);
        echo json_encode(['error' => 'Error preparing statement: ' . $conn->error]);
        exit;
    }
    $account_id = $account_info['profile']['account_id'];
    $personaname = $account_info['profile']['personaname'];
    $avatar = $account_info['profile']['avatar'];
    $rank_tier = $account_info['rank_tier'];
    $win = $wl['win'];
    $lose = $wl['lose'];
    foreach ($recent_matchesD as $match) {
        $matchId = $match['match_id'];
        $kills = $match['kills'];
        $deaths = $match['deaths'];
        $assists = $match['assists'];
        $average_rank = $match['average_rank'];

        $match_info = getMatchInfo($match['match_id']);
        $radiant_score = $match_info['radiant_score'];
        $dire_score = $match_info['dire_score'];

        $stmt->bind_param("sssssssssssss", $account_id, $personaname, $avatar, $rank_tier, $win, $lose, $matchId, $kills, $deaths, $assists, $average_rank, $radiant_score, $dire_score);

        if (!$stmt->execute()) {
            logMessage("Error inserting data for user $name with account_id $account_id");
            echo json_encode(['error' => 'Error inserting data for participant: '. $account_id]);
            exit;
        }
    }

    $stmt->close();
    $conn->close();
    logMessage("Successfully updated database for $name.");
}
function getAccountInfo($account_id)
{
    $url = "https://api.opendota.com/api/players/" . $account_id;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data === null) {
        throw new Exception('Failed to get account info');
    }
    return $data;
}
function getPlayerWL($account_id)
{
    $url = "https://api.opendota.com/api/players/" . $account_id . "/wl";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data === null) {
        throw new Exception('Failed to get player win/lose');
    }
    return $data;
}
function getPlayerRecentMatches($account_id)
{
    $url = "https://api.opendota.com/api/players/" . $account_id . "/recentMatches";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data === null) {
        throw new Exception('Failed to get player recent matches');
    }
    return $data;
}
function getMatchInfo($match_id)
{
    $url = "https://api.opendota.com/api/matches/" . $match_id;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data === null) {
        throw new Exception('Failed to get match info');
    }
    return $data;
}
?>

