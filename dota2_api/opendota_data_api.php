<?php
//2,000 free calls per day at a rate limit of 60 requests/minute
//https://steamid.xyz/
//https://docs.opendota.com/
//107828036

/*
TODO:
    - get player recent matches by using matchid to more detailed match history
*/
$account_id='221959239';
$name='raddan';
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
    $url = "https://api.opendota.com/api/players/" . $account_id . "/matches";
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

function getPlayerTotals($account_id)
{
    $url = "https://api.opendota.com/api/players/" . $account_id . "/totals";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data === null) {
        throw new Exception('Failed to get player totals');
    }
    return $data;
}

$account_info = getAccountInfo($account_id);
$wl = getPlayerWL($account_id);
$matches = getPlayerRecentMatches($account_id);
$totals = getPlayerTotals($account_id);

/*
echo json_encode($account_info);
echo json_encode($wl);
echo json_encode($recent_matches);
echo json_encode($totals);
*/

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gamerstats";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$sql = "DROP TABLE IF EXISTS `$name`";
if ($conn->query($sql) !== TRUE) {
    echo json_encode(['error' => 'Error dropping table: ' . $conn->error]);
    $conn->close();
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS `$name` (
    account_id BIGINT,
    personaname VARCHAR(255),
    avatar VARCHAR(255),
    rank_tier INTEGER,
    leaderboard_rank INTEGER,
    win INTEGER,
    lose INTEGER,
    match_id BIGINT,
    kills INTEGER,
    deaths INTEGER,
    assists INTEGER,
    average_rank INTEGER
)";

if ($conn->query($sql) !== TRUE) {
    echo json_encode(['error' => 'Error creating table: ' . $conn->error]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO `$name`
    (account_id, personaname, avatar, rank_tier, leaderboard_rank, win, lose, match_id, kills, deaths, assists, average_rank) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt === false) {
    echo json_encode(['error' => 'Error preparing statement: ' . $conn->error]);
    exit;
}
$account_id = $account_info['profile']['account_id'];
$personaname = $account_info['profile']['personaname'];
$avatar = $account_info['profile']['avatar'];
$rank_tier = $account_info['rank_tier'];
$leaderboard_rank = $account_info['leaderboard_rank'];
$win = $wl['win'];
$lose = $wl['lose'];
foreach ($matches as $match) {
    $matchId = $match['match_id'];
    $kills = $match['kills'];
    $deaths = $match['deaths'];
    $assists = $match['assists'];
    $average_rank = $match['average_rank'];

    $stmt->bind_param("ssssssssssss", $account_id, $personaname, $avatar, $rank_tier, $leaderboard_rank, $win, $lose, $matchId, $kills, $deaths, $assists, $average_rank);

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Error inserting data for participant: '. $account_id]);
        exit;
    }
}


$stmt->close();
$conn->close();