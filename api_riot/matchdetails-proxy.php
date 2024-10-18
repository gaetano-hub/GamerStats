<?php




require '../conf/config.php';


$matchId = isset($_GET['matchId']) ? $_GET['matchId'] : '';

if (!$matchId) {
    echo json_encode(['error' => 'Match ID is required']);
    exit;
}

$name = isset($_GET['gameName']) ? strval($_GET['gameName']) : '';

if (empty($name)) {
    echo json_encode(['error' => 'Game name is required']);
    exit;
}




$url = "https://europe.api.riotgames.com/lol/match/v5/matches/$matchId";


$ch = curl_init();


curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Riot-Token: " . RIOT_API_KEY
]);


$response = curl_exec($ch);
if ($response === false) {
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode != 200) {
    echo json_encode(['error' => 'Failed to fetch data from API. HTTP Code: ' . $httpCode]);
    exit;
}

$matchDetails = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Failed to parse JSON response.']);
    exit;
}


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "riot_games";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$info = $matchDetails['info'];
$participants = $info['participants'];

$puuid_cercato = NULL;







$stmt = $conn->prepare("SELECT * FROM users WHERE nickname = ?");
$stmt->bind_param("s", $name); // Usa $name
$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
        $puuid_cercato = $row["puuid"];

        echo "<script>console.log('Trovato PUUID: " . addslashes($puuid_cercato) . "');</script>";
    }
} else {

    echo "<script>console.log('Nessun risultato trovato per: $name');</script>";
}


$stmt->close();





$sql = "DROP TABLE IF EXISTS `$name`";
if ($conn->query($sql) !== TRUE) {
    echo json_encode(['error' => 'Error dropping table: ' . $conn->error]);
    $conn->close();
    exit;
}


sleep(1);


$sql = "CREATE TABLE IF NOT EXISTS `$name` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id VARCHAR(255) NOT NULL,
    puuid VARCHAR(255) NOT NULL,
    champion_name VARCHAR(255) NOT NULL,
    kills INT NOT NULL,
    deaths INT NOT NULL,
    assists INT NOT NULL,
    damage_dealt INT NOT NULL,
    gold_earned INT NOT NULL,
    team_id INT NOT NULL,
    game_duration INT NOT NULL,
    win BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";


if ($conn->query($sql) !== TRUE) {
    echo json_encode(['error' => 'Error creating table: ' . $conn->error]);
    exit;
}


$stmt = $conn->prepare("INSERT INTO `$name`
    (match_id, puuid, champion_name, kills, deaths, assists, damage_dealt, gold_earned, team_id, game_duration, win) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt === false) {
    echo json_encode(['error' => 'Error preparing statement: ' . $conn->error]);
    exit;
}


$successCount = 0;
foreach ($participants as $participant) {
    $puuid = $participant['puuid'];
    $championName = $participant['championName'];
    $kills = $participant['kills'];
    $deaths = $participant['deaths'];
    $assists = $participant['assists'];
    $damageDealt = $participant['totalDamageDealtToChampions'];
    $goldEarned = $participant['goldEarned'];
    $teamId = $participant['teamId'];
    $gameDuration = $info['gameDuration'];
    $win = $participant['win'] ? 1 : 0;


    if (($puuid_cercato == $puuid)) {
        $stmt->bind_param("sssiiiiiiii", $matchId, $puuid, $championName, $kills, $deaths, $assists, $damageDealt, $goldEarned, $teamId, $gameDuration, $win);
        if ($stmt->execute()) {
            $successCount++;
        } else {
            echo json_encode(['error' => 'Error inserting data for participant: ' . $puuid]);
            exit;
        }
    }
}


$stmt->close();
$conn->close();


echo json_encode([
    'success' => true,
    'message' => "$successCount participants data inserted",
    'matchDetails' => $matchDetails
]);
