<?php
session_start();
// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);

if (isset($_GET['team']) && !empty($_GET['team'])) {
    // Preleviamo il valore del nickname dall'URL
    $visitingTeam = htmlspecialchars($_GET['team']);
}
// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);
// Controlla se l'utente è loggato con Discord o normalmente
if (!isset($_SESSION['discord_user']) && !isset($_SESSION['nickname'])) {
    header("Location: ../login/login.html");
}

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

// Preleva il nickname dalla sessione
$nickname = $_SESSION['nickname'];

// Prepara la query per ottenere i nomi dei team
$stmt = $conn->prepare("SELECT game, leader, member_one, member_two, member_three, member_four, member_five FROM teams WHERE team_name = ?");
$stmt->bind_param("s", $visitingTeam);
$stmt->execute();
$result = $stmt->get_result();

$game = "";
$teamData = array();
if ($row = $result->fetch_assoc()) {
    $teamData = $row;
    $game = $teamData['game'];
    unset($teamData['game']);
}



// Prepara e esegui la query per ottenere i membri della squadra
$stmt = $conn->prepare("SELECT game, leader, member_one, member_two, member_three, member_four, member_five FROM teams WHERE team_name = ?");
$stmt->bind_param("s", $visitingTeam);
$stmt->execute();
$result = $stmt->get_result();

// Controlla se ci sono risultati
$members = [];
if ($result->num_rows > 0) {
    // Inizializza un array per memorizzare i membri
    $membersList = [];

    while ($row = $result->fetch_assoc()) {
        $membersList[] = [
            'leader' => $row['leader'],
            'members' => [
                $row['member_one'],
                $row['member_two'],
                $row['member_three'],
                $row['member_four'],
                $row['member_five']
            ]
        ];
        // Aggiungi i membri all'array
        $members = array_merge($membersList, $row);
    }
} else {
    // echo "<p>Nessuna squadra trovata con il nome: {$visitingTeam}</p>";
}

$stmt = $conn->prepare("SELECT leader, member_one, member_two, member_three, member_four, member_five FROM teams WHERE team_name = ?");
$stmt->bind_param("s", $visitingTeam);
$stmt->execute();
$result = $stmt->get_result();
$membersD = [];
while ($row = $result->fetch_assoc()) {
    $membersD = array_merge($membersD, array_filter([$row['leader'], $row['member_one'], $row['member_two'], $row['member_three'], $row['member_four'], $row['member_five']]));
}

echo "<pre>";
print_r($membersD);
echo "</pre>";

// Cerca lo steamID per ciascun membro
$steamIDs = [];
foreach ($members as $member) {
    // Prepara la query per ottenere lo steamID
    $memberStmt = $conn->prepare("SELECT steamID FROM users WHERE nickname = ?");
    $memberStmt->bind_param("s", $member);
    $memberStmt->execute();

    // Ottieni il risultato
    $result = $memberStmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $steamIDs[] = $row['steamID'];
        }
    } else {
        // echo "Nessun steamID trovato per il membro: $member<br>"; 
    }

    // Chiudi la dichiarazione
    $memberStmt->close();
}

// Steam API key and game ID
$apiKey = '8A345C81E607D2E02274B11D4834675A';
$tf2GameId = 440; // Team Fortress 2

// Function to fetch game stats
function getGameStats($steamID, $apiKey, $gameId)
{
    $url = "https://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid={$gameId}&steamid={$steamID}&key={$apiKey}";
    $response = @file_get_contents($url);
    return $response !== false ? json_decode($response, true) : null;
}


// Initialize arrays for statistics
$userDetails = [];
$aggregateStats = [
    'kills' => 0,
    'damage' => 0,
    'killAssists' => 0,
    'pointsScored' => 0,
    'playTime' => 0,
    'buildingsDestroyed' => 0
];

// Fetch user details and stats only for steamIDs
foreach ($steamIDs as $steamID) {
    // Prepare the query to get user details
    $userStmt = $conn->prepare("SELECT nickname FROM users WHERE steamID = ?");
    $userStmt->bind_param("s", $steamID);
    $userStmt->execute();

    // Get the result
    $result = $userStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nickname = htmlspecialchars($row['nickname']);

        // Fetch TF2 stats
        $tf2Stats = getGameStats($steamID, $apiKey, $tf2GameId);
        if ($tf2Stats !== null) {
            // Initialize temporary variables for aggregating stats
            $kills = $damage = $killAssists = $pointsScored = $playTime = $buildingsDestroyed = 0;

            foreach ($tf2Stats['playerstats']['stats'] as $stat) {
                if (isset($stat['name']) && isset($stat['value'])) {
                    $value = is_numeric($stat['value']) ? $stat['value'] : 0; // Ensure it's a number
                    if (strpos($stat['name'], 'iNumberOfKills') !== false) {
                        $kills += $value;
                    } elseif (strpos($stat['name'], 'iDamageDealt') !== false) {
                        $damage += $value;
                    } elseif (strpos($stat['name'], 'iKillAssists') !== false) {
                        $killAssists += $value;
                    } elseif (strpos($stat['name'], 'iPointsScored') !== false) {
                        $pointsScored += $value;
                    } elseif (strpos($stat['name'], 'iPlayTime') !== false) {
                        $playTime += $value;
                    } elseif (strpos($stat['name'], 'iBuildingsDestroyed') !== false) {
                        $buildingsDestroyed += $value;
                    }
                }
            }


            // Store accumulated stats
            $userDetails[$steamID]['kills'] = $kills;
            $userDetails[$steamID]['damage'] = $damage;
            $userDetails[$steamID]['killAssists'] = $killAssists;
            $userDetails[$steamID]['pointsScored'] = $pointsScored;
            $userDetails[$steamID]['playTime'] = $playTime;
            $userDetails[$steamID]['buildingsDestroyed'] = $buildingsDestroyed;

            // Aggregate statistics (if needed in another part of your code)
            $aggregateStats['kills'] += $kills;
            $aggregateStats['damage'] += $damage;
            $aggregateStats['killAssists'] += $killAssists;
            $aggregateStats['pointsScored'] += $pointsScored;
            $aggregateStats['playTime'] += $playTime;
            $aggregateStats['buildingsDestroyed'] += $buildingsDestroyed;
        }
    }
    // Optionally handle the case when no user is found
    // else {
    //     echo "Nessun utente trovato con lo steamID: $steamID<br>";
    // }

    // Close the statement
    $userStmt->close();
}



?>


<!-- ================================ PARTE DI CSGO ================================ -->

<?php
// API Key di Steam
$apiKey = '8A345C81E607D2E02274B11D4834675A';
$cs2GameId = 730;

// Funzione per estrarre statistiche
function extractStats($stats)
{
    $extractedStats = [
        'totalWins' => 0,
        'totalMatches' => 0,
        'totalKills' => 0,
        'totalDeaths' => 0,
        'totalDamageDone' => 0,
    ];

    foreach ($stats as $stat) {
        switch ($stat['name']) {
            case 'total_matches_won':
                $extractedStats['totalWins'] = $stat['value'];
                break;
            case 'total_matches_played':
                $extractedStats['totalMatches'] = $stat['value'];
                break;
            case 'total_kills':
                $extractedStats['totalKills'] = $stat['value'];
                break;
            case 'total_deaths':
                $extractedStats['totalDeaths'] = $stat['value'];
                break;
            case 'total_damage_done':
                $extractedStats['totalDamageDone'] = $stat['value'];
                break;
        }
    }

    return $extractedStats;
}

// Inizializza array per le statistiche
$userDetails_csgo = [];

// Solo per gli steamID raccolti
foreach ($steamIDs as $steamID) {
    // Prepara la query per ottenere i dati dell'utente
    $userStmt = $conn->prepare("SELECT nickname, image FROM users WHERE steamID = ?");
    $userStmt->bind_param("s", $steamID);
    $userStmt->execute();

    // Ottieni il risultato
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $row = $userResult->fetch_assoc();
        $nickname = htmlspecialchars($row['nickname']);

        // Debugging statement for steamID
        // echo "Processing Steam ID: $steamID<br>";

        // Ottieni le statistiche per CS2
        $cs2Stats = getGameStats($steamID, $apiKey, $cs2GameId);

        if (isset($cs2Stats['playerstats']['stats'])) {
            $stats = extractStats($cs2Stats['playerstats']['stats']);
            $totalWins = $stats['totalWins'];
            $totalMatches = $stats['totalMatches'];
            $winPercentage = $totalMatches > 0 ? round(($totalWins / (($totalMatches - $totalWins) + 1)), 2) : 0;

            // Inizializza l'array per l'utente
            $userDetails_csgo[$steamID] = [
                'nickname' => $nickname,
                'steamID' => $steamID,
                'totalKills' => $stats['totalKills'],
                'totalDeaths' => $stats['totalDeaths'],
                'totalDamageDone' => $stats['totalDamageDone'],
                'cs2WinPercentage' => $winPercentage,
                'last_match' => getLastMatchStats($steamID, $apiKey, $cs2GameId),
            ];
        }
    }
    $userStmt->close(); // Chiudi la dichiarazione
}

// Funzione per generare la classifica
function generaClassificaCSGO($userDetails_csgo)
{
    $classifica = array_filter($userDetails_csgo, fn($details) => $details['cs2WinPercentage'] > 0);

    usort($classifica, fn($a, $b) => $b['cs2WinPercentage'] <=> $a['cs2WinPercentage']);

    return $classifica;
}

// Funzione per ottenere le statistiche dell'ultima partita
function getLastMatchStats($steamID, $apiKey, $gameId)
{
    $url = "https://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid={$gameId}&steamid={$steamID}&key={$apiKey}";
    $response = @file_get_contents($url);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return isset($data['playerstats']['stats']) ? array_reduce($data['playerstats']['stats'], function ($carry, $stat) {
        if (in_array($stat['name'], ['last_match_kills', 'last_match_deaths', 'last_match_mvps'])) {
            $carry[$stat['name']] = $stat['value'];
        }
        return $carry;
    }, []) : null;
}

// Chiama la funzione per generare la classifica
$cs2Classifica = generaClassificaCSGO($userDetails_csgo);

// Calcola le medie
$totalKills = 0;
$totalDeaths = 0;
$totalDamageDone = 0;
$totalWinPercentage = 0;
$totalUsers = count($userDetails_csgo);

foreach ($userDetails_csgo as $user) {
    $totalKills += $user['totalKills'];
    $totalDeaths += $user['totalDeaths'];
    $totalDamageDone += $user['totalDamageDone'];
    $totalWinPercentage += $user['cs2WinPercentage'];
}

$averageKills = $totalUsers > 0 ? round($totalKills / $totalUsers, 2) : 0;
$averageDeaths = $totalUsers > 0 ? round($totalDeaths / $totalUsers, 2) : 0;
$averageDamageDone = $totalUsers > 0 ? round($totalDamageDone  / $totalUsers, 2) : 0;
$averageWinPercentage = $totalUsers > 0 ? round($totalWinPercentage / $totalUsers, 2) : 0;


?>
<!-- ================================ PARTE DI DOTA 2================================ -->

<?php
function getPlayerAccountId($name, $conn)
{
    $stmt = $conn->prepare("SELECT steamID FROM users WHERE nickname = ?");
    $stmt->bind_param("s", $name); // $steamid should be defined or passed into the function
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (int)$row['steamID'] - 76561197960265728; //conversion to SteamID32
    } else {
        echo "The searched user does not have a Steam account associated.";
    }

    $stmt->close();
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

function getPlayerMatches($account_id)
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

function getPlayerName($conn, $player_id)
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE steamID = ?");
    $stmt->bind_param("s", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['nickname'] : null;
    $stmt->close();
}

function generateDota2LeaderboardWlr($members)
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";

    echo "<script>console.log('generaClassificaDota2: connecting to database');</script>";
    $conn = new mysqli($servername, $username, $password, $dbname);

    $leaderboardWlr = [];

    // Iterate through the users array and make a call with the dota2api php file
    foreach ($members as $user) {
        echo "<script>console.log('generaClassificaDota2: processing user: " . $user . "');</script>";
        $nickname = $user;
        $account_id = getPlayerAccountId($nickname, $conn);
        echo "<script>console.log('generaClassificaDota2: retrieved player id: " . $account_id . "');</script>";
        //updateDatabase($nickname, $account_id);
        //API CALL
        $wl = getPlayerWL($account_id);
        if (!($wl['win']==0 && $wl['lose']==0)) {
        echo "<script>console.log('generaClassificaDota2: retrieved wins and losses".$wl['win']."');</script>";
        //echo "<script>console.log(" . json_encode($wl) . ");</script>";
        //echo "<script>console.log(" . json_encode($nickname) . ");</script>";
        $wlr = $wl['win'] / ($wl['lose'] + 1);
        echo "<script>console.log('generaClassificaDota2: calculated wlr".$wl['win']."');</script>";
        $leaderboardWlr[] = [
            'nickname' => $nickname,
            'steamID' => $account_id,
            'totalWins' => $wl['win'],
            'totalLosses' => $wl['lose'],
            'wlr' => $wlr
        ];
    } else {
        echo "<script>console.log('generaClassificaDota2: no recent matches found');</script>";
    }
    }
    usort($leaderboardWlr, function ($a, $b) {
        return $b['wlr'] <=> $a['wlr'];
    });
    return $leaderboardWlr;
}

function generateDota2LeaderboardKdr($members)
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";

    echo "<script>console.log('generaClassificaDota2: connecting to database');</script>";
    $conn = new mysqli($servername, $username, $password, $dbname);

    $leaderboardkdr = [];

    // Iterate through the users array and make a call with the dota2api php file
    foreach ($members as $user) {
        echo "<script>console.log('generaClassificaDota2: processing user: " . $user . "');</script>";
        $nickname = $user;
        $account_id = getPlayerAccountId($nickname, $conn);;
        echo "<script>console.log('generaClassificaDota2: retrieved player id: " . $account_id . "');</script>";
        //updateDatabase($nickname, $account_id);

        //API CALL
        $recent_matches = getPlayerRecentMatches($account_id);
        if (!empty($recent_matches)) {
            echo "<script>console.log('generaClassificaDota2: retrieved recent matches');</script>";
            $kdr = 0;
            $total_kills = 0;
            $total_deaths = 0;

            foreach ($recent_matches as $match) {
                $total_kills += $match['kills'];
                $total_deaths += $match['deaths'];
            }
            echo "<script>console.log('generaClassificaDota2: calculated total kills and deaths:".$total_kills."');</script>";
            $kdr = $total_kills / ($total_deaths + 1);
            $leaderboardkdr[] = [
                'nickname' => $nickname,
                'steamID' => $account_id,
                'totalDeaths' => $total_deaths,
                'totalKills' => $total_kills,
                'kdr' => $kdr
            ];
        } else {
            echo "<script>console.log('generaClassificaDota2: no recent matches found');</script>";
        }
    }
    usort($leaderboardkdr, function ($a, $b) {
        return $b['kdr'] <=> $a['kdr'];
    });
    return $leaderboardkdr;
}

function generateDota2LeaderboardAvg($members)
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";

    echo "<script>console.log('generaClassificaDota2: connecting to database');</script>";
    $conn = new mysqli($servername, $username, $password, $dbname);

    $leaderboardAvg = [];
    $kdrL=[];
    $wrL=[];
    $totaltotalkills=0;
    $s=0;
    $avg_kdr=0;
    $avg_wr=0;

    // Iterate through the users array and make a call with the dota2api php file
    foreach ($members as $user) {
        $s++;
        echo "<script>console.log('generaClassificaDota2: processing user: " . $user . "');</script>";
        $nickname = $user;
        $account_id = getPlayerAccountId($nickname, $conn);;
        echo "<script>console.log('generaClassificaDota2: retrieved player id: " . $account_id . "');</script>";
        //updateDatabase($nickname, $account_id);
        $wl = getPlayerWL($account_id);
        //API CALL
        $total_kills = 0;
        $total_deaths = 0;
        $total_wins=0;
        $total_losses=0;
        $recent_matches = getPlayerRecentMatches($account_id);
        if (!empty($recent_matches)) {
            echo "<script>console.log('generaClassificaDota2: retrieved recent matches');</script>";
            $kdr = 0;

            foreach ($recent_matches as $match) {
                $total_kills += $match['kills'];
                $totaltotalkills += $match['kills'];
                $total_deaths += $match['deaths'];
            }
            $total_wins += $wl['win'];
            $total_losses += $wl['lose'];
            echo "<script>console.log('generaClassificaDota2: calculated total kills and deaths:".$total_kills."');</script>";
            $kdr = $total_kills / ($total_deaths + 1);
            $wr = $total_wins / ($total_losses + 1);
        } else {
            echo "<script>console.log('generaClassificaDota2: no recent matches found');</script>";
        }
        $kdrL[$s] = $kdr;
        $wrL[$s] = $wr;
        
    }
    foreach($kdrL as $k){
        $avg_kdr += $k;
    }
    foreach($wrL as $w){
        $avg_wr += $w;
    }
    $leaderboardAvg[] = [
        'avg_kills' => $totaltotalkills/$s,
        'avg_kdr' => $avg_kdr/$s,
        'avg_wr' => $avg_wr/$s
    ];
    return $leaderboardAvg;
}

$dota2kdr = generateDota2LeaderboardKdr($membersD);
$dota2wlr = generateDota2LeaderboardWlr($membersD);
$dota2avg = generateDota2LeaderboardAvg($membersD);

?>






<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../home/styleHome.css">
    <link rel="stylesheet" href="styleTeam.css">
    <script src="../home/scriptHome.js" defer></script>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>Team page</title>
</head>

<body>

    <!--
    <video class="video-background" autoplay muted loop>
        <source src="../assets/topographic.mp4" type="video/mp4">
    </video>

    <script>
        // Seleziona il video e imposta la velocità di riproduzione
        var video = document.getElementById('backgroundVideo');
        video.playbackRate = 0.2; // Imposta la velocità a 50% (rallenta il video)
    </script>
    -->

    <div class="content">
        <nav class="navbar fixed-top navbar-expand-lg" style="background-color: var(--object_color);">
            <div class="container-fluid" style="background-color: var(--object_color);">
                <a class="navbar-brand fs-3" href="#" style="color: var(--brand_color);">GamerStats</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item dropdown" style="margin-top: 6px;">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false" style="color: var(--navbar_textCol);">Games</a>
                            <ul class="dropdown-menu" style="background-color: var(--object_color);">

                                <li><a class="dropdown-item" href="../csgo/csgo.php"
                                        style="color: var(--brand_color);">Csgo</a></li>
                                <li><a class="dropdown-item" href="../team_fortress2/team_fortress2.php"
                                        style="color: var(--brand_color);">Team Fortress 2</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                Possono sempre servire
                            </ul>
                        </li>
                        <li class="nav-item" style="margin-left: 7px; margin-top: 11px;">
                            <a class="btn btn-outline-success" id="homeref" type="button" style=" background-color:var(--object_color);" href="../home/home.php">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item align-self-center">
                            <div style="position: relative;">
                                <form class="d-flex" role="search" id="searchForm" action="search.php" method="post" style="margin-top: 10px;">
                                    <input class="form-control me-2" name="searchString" id="searchInput" type="search" placeholder="Search" aria-label="Search"
                                        style="background-color:var(--object_color); color: var(--text_color); width: calc(100% - 40px);">
                                    <button class="btn" type="button" id="searchButton">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                            <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                                        </svg>
                                    </button>
                                </form>
                                <ul class="dropdown-menu" id="resultDropdown" style="background-color: var(--object_color); position: absolute; top: 100%; left: 0; width: 13.5rem; z-index: 1000; display: none;"></ul>
                            </div>
                        </li>
                        <script>
                            document.getElementById('searchButton').addEventListener('click', function() {
                                const searchString = document.getElementById('searchInput').value;

                                if (searchString.trim() === '') {
                                    document.getElementById('resultDropdown').style.display = 'none';
                                    return;
                                }

                                fetch('search.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: 'searchString=' + encodeURIComponent(searchString)
                                    })
                                    .then(response => response.text())
                                    .then(data => {
                                        const resultDropdown = document.getElementById('resultDropdown');
                                        resultDropdown.innerHTML = data;
                                        resultDropdown.style.display = 'block';
                                    })
                                    .catch(error => console.error('Error:', error));
                            });
                        </script>
                        <li class="separator" style="color: var(--separator_color);">|</li>
                        <?php if (isset($_SESSION['nickname'])): ?>

                            <li class="nav-item">
                                <a class="nav-link" href="../memberPage/myProfile.php" style="color: var(--brand_color); font-weight: bold;">
                                    <?php echo $_SESSION['nickname']; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../logout/logout.php" style="color: var(--brand_color);">Logout</a>
                            </li>
                        <?php else: ?>

                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../login/login.html" style="color: var(--brand_color);">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../signUp/signUp.html" style="color: var(--brand_color);">Sign Up</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
    <div
        style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">

        <img src="<?php echo ($game == "Csgo") ? "../assets/csgologo.png" : "../assets/tf2.png" ?>" class="card-img-top"
            alt="Logo" style="width: 50px; height: auto; margin-right: 10px;">
        <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);"><?php echo $visitingTeam ?></p>
    </div>
    <div class="container text-center"
        style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
        <p style="color: var(--text_color); margin-left: 2rem;">
            <b style="font-size: 2rem;">Team members</b>
        </p>
        <hr style="border-color: var(--separator_color); margin-left: 2rem; margin-right: 2rem; border-width: 5px;">
        <?php
        if (isset($_SESSION['nickname']) && $_SESSION['nickname'] == $teamData['leader']) {
            echo '<div class="text-center">
                    <div class="row">
                        <div class="col">
                            <form action="changeLeader.php" method="post">
                                <input type="hidden" name="teamName" value="' . $visitingTeam . '">
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: var(--object_color); color: var(--text_color); border-color: var(--text_color);">
                                    Change leader
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1" style="background-color: var(--object_color);">';
            if (!is_null($teamData['member_one'])) {
                echo '<li><button type="submit" class="dropdown-item" name="newLeader" value="' . $teamData['member_one'] . '" style="color: var(--text_color);">' . $teamData['member_one'] . '</button></li>';
            }
            if (!is_null($teamData['member_two'])) {
                echo '<li><button type="submit" class="dropdown-item" name="newLeader" value="' . $teamData['member_two'] . '" style="color: var(--text_color);">' . $teamData['member_two'] . '</button></li>';
            }
            if (!is_null($teamData['member_three'])) {
                echo '<li><button type="submit" class="dropdown-item" name="newLeader" value="' . $teamData['member_three'] . '" style="color: var(--text_color);">' . $teamData['member_three'] . '</button></li>';
            }
            if (!is_null($teamData['member_four'])) {
                echo '<li><button type="submit" class="dropdown-item" name="newLeader" value="' . $teamData['member_four'] . '" style="color: var(--text_color);">' . $teamData['member_four'] . '</button></li>';
            }
            if (!is_null($teamData['member_five'])) {
                echo '<li><button type="submit" class="dropdown-item" name="newLeader" value="' . $teamData['member_five'] . '" style="color: var(--text_color);">' . $teamData['member_five'] . '</button></li>';
            }
            echo '</ul>
                            </form>
                        </div>
                        <div class="col">
                            <button class="btn" style="background-color: var(--object_color); color: var(--text_color); border-color: var(--text_color);" onclick="
                                if (confirm(\'Are you sure you want to delete the team?\')) {
                                    window.location.href = \'deleteTeam.php?teamName=\' + encodeURIComponent(\'' . $visitingTeam . '\');
                                }
                            ">Delete team</button>
                        </div>;
                        <div class="col">
                            <button class="btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdit" aria-expanded="false" aria-controls="collapseEdit" style="background-color: var(--object_color); color: var(--text_color); border-color: var(--text_color); margin-bottom:10px;">
                                Edit team
                            </button>
                            <div class="collapse" id="collapseEdit">
                                <div class="card card-body" style="background-color: var(--object_color); color: var(--text_color); max-width: 300px; margin: 0 auto;">
                                    <h5>Edit your team here:</h5>

                                    <form action="editTeam.php" method="post">
                                        <input type="hidden" name="team_name" value="' . $visitingTeam . '">
                                        <div class="form-group" style="margin-bottom: 10px; position: relative;">
                                            <input type="text" name="memberOne" id="memberOne" class="form-control" value="' . $teamData['member_one'] . '" placeholder="Member 1" style="background-color: var(--object_color); color: var(--text_color);">
                                            <button type="button" class="btn-close" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background-color: red;" aria-label="Close" onclick="document.getElementById(\'memberOne\').value = \'\';"></button>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 10px; position: relative;">
                                            <input type="text" name="memberTwo" id="memberTwo" class="form-control" value="' . $teamData['member_two'] . '" placeholder="Member 2" style="background-color: var(--object_color); color: var(--text_color);">
                                            <button type="button" class="btn-close" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background-color: red;" aria-label="Close" onclick="document.getElementById(\'memberTwo\').value = \'\';"></button>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 10px; position: relative;">
                                            <input type="text" name="memberThree" id="memberThree" class="form-control" value="' . $teamData['member_three'] . '" placeholder="Member 3" style="background-color: var(--object_color); color: var(--text_color);">
                                            <button type="button" class="btn-close" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background-color: red;" aria-label="Close" onclick="document.getElementById(\'memberThree\').value = \'\';"></button>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 10px; position: relative;">
                                            <input type="text" name="memberFour" id="memberFour" class="form-control" value="' . $teamData['member_four'] . '" placeholder="Member 4" style="background-color: var(--object_color); color: var(--text_color);">
                                            <button type="button" class="btn-close" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background-color: red;" aria-label="Close" onclick="document.getElementById(\'memberFour\').value = \'\';"></button>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 10px; position: relative;">
                                            <input type="text" name="memberFive" id="memberFive" class="form-control" value="' . $teamData['member_five'] . '" placeholder="Member 5" style="background-color: var(--object_color); color: var(--text_color);">
                                            <button type="button" class="btn-close" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background-color: red;" aria-label="Close" onclick="document.getElementById(\'memberFive\').value = \'\';"></button>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <button type="submit" class="btn btn-success">Edit</button>
                                            <button type="button" class="btn btn-danger" onclick="
                                                    document.getElementById(\'memberOne\').value = \'' . $teamData['member_one'] . '\';
                                                    document.getElementById(\'memberTwo\').value = \'' . $teamData['member_two'] . '\';
                                                    document.getElementById(\'memberThree\').value = \'' . $teamData['member_three'] . '\';
                                                    document.getElementById(\'memberFour\').value = \'' . $teamData['member_four'] . '\';
                                                    document.getElementById(\'memberFive\').value = \'' . $teamData['member_five'] . '\';
                                                ">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
        }
        ?>
        <div style="width: 100%; margin-top: 2rem; margin-bottom: 2rem; height: auto;">
            <div class="row">
                <?php
                $row = [];
                foreach ($teamData as $member) {
                    if ($member != null) {
                        $stmt = $conn->prepare("SELECT image FROM users WHERE nickname = ?");
                        $stmt->bind_param("s", $member);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $image = $result->fetch_assoc()['image'];
                        $row[] = [$member, ($image == null) ? '../assets/profPicture.jpg' : $image];
                        if (count($row) == 3) {
                            foreach ($row as $member) {
                                echo '<div class="col">
                                        <div class="member-circle mx-auto" style="height: 100px; width: 100px; background-image: url(\'' . $member[1] . '\'); background-size: cover;"></div>
                                        <p style="color: var(--text_color); text-align: center;">' . $member[0] . '</p>
                                    </div>';
                            }
                            echo '</div><div class="row">';
                            $row = [];
                        }
                    }
                }
                if (count($row) > 0) {
                    foreach ($row as $member) {
                        echo '<div class="col">
                                <div class="member-circle mx-auto" style="height: 100px; width: 100px; background-image: url(\'' . $member[1] . '\'); background-size: cover;"></div>
                                <p style="color: var(--text_color); text-align: center;">' . $member[0] . '</p>
                            </div>';
                    }
                }
                ?>
            </div>
        </div>

    </div>
    <div class="container text-center"
        style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
        <div class="row">
            <div class="col-md-6">
                <?php
                // Print user statistics
                if (!empty($userDetails) && $game === "Team Fortress 2") {
                    echo "<h2>Statistiche di Team Fortress 2</h2>";
                    echo "uliiii" . $game;
                    echo '<div style="overflow-x:auto;">';
                    echo '<table class="table table-dark table-striped">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Nickname</th>';
                    echo '<th>Kills</th>';
                    echo '<th>Damage</th>';
                    echo '<th>Kill Assists</th>';
                    echo '<th>Punti Scavati</th>';
                    echo '<th>Tempo di Gioco</th>';
                    echo '<th>Edifici Distrutti</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    foreach ($userDetails as $steamID => $stats) {
                        echo '<tr>';
                        echo "<td>" . htmlspecialchars($stats['nickname']) . "</td>";
                        echo "<td>" . htmlspecialchars($stats['kills']) . "</td>";
                        echo "<td>" . htmlspecialchars($stats['damage']) . "</td>";
                        echo "<td>" . htmlspecialchars($stats['killAssists']) . "</td>";
                        echo "<td>" . htmlspecialchars($stats['pointsScored']) . "</td>";
                        echo "<td>" . htmlspecialchars($stats['playTime']) . "</td>";
                        echo "<td>" . htmlspecialchars($stats['buildingsDestroyed']) . "</td>";
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table></div>';

                    // Calculate and display average stats
                    $numUsers = count($userDetails);
                    if ($numUsers > 0) {
                        $averageStats = [
                            'kills' => $aggregateStats['kills'] / $numUsers,
                            'damage' => $aggregateStats['damage'] / $numUsers,
                            'killAssists' => $aggregateStats['killAssists'] / $numUsers,
                            'pointsScored' => $aggregateStats['pointsScored'] / $numUsers,
                            'playTime' => $aggregateStats['playTime'] / $numUsers,
                            'buildingsDestroyed' => $aggregateStats['buildingsDestroyed'] / $numUsers
                        ];
                    }
                    echo "<h3>Media Statistiche</h3>";
                    echo '<div style="overflow-x:auto;">';
                    echo '<table class="table table-dark table-striped">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Kills</th>';
                    echo '<th>Damage</th>';
                    echo '<th>Kill Assists</th>';
                    echo '<th>Punti Scavati</th>';
                    echo '<th>Tempo di Gioco</th>';
                    echo '<th>Edifici Distrutti</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    echo '<tr>
                        <td>' . number_format($averageStats['kills'], 2) . '</td>
                        <td>' . number_format($averageStats['damage'], 2) . '</td>
                        <td>' . number_format($averageStats['killAssists'], 2) . '</td>
                        <td>' . number_format($averageStats['pointsScored'], 2) . '</td>
                        <td>' . number_format($averageStats['playTime'], 2) . '</td>
                        <td>' . number_format($averageStats['buildingsDestroyed'], 2) . '</td>
                    </tr>';
                    echo '</tbody>';
                    echo '</table></div>';

                    // Prepare data for pie charts
                    $labels = [];
                    $killsData = [];
                    $damageData = [];
                    $killAssistsData = [];

                    foreach ($userDetails as $stats) {
                        $labels[] = htmlspecialchars($stats['nickname']);
                        $killsData[] = $stats['kills'];
                        $damageData[] = $stats['damage'];
                        $killAssistsData[] = $stats['killAssists'];
                    }


                    // Assuming $labels, $killsData, $damageData, and $killAssistsData are defined earlier in your PHP code
                
                    // Start outputting HTML with echo
                    echo '<div class="col-md-6">';
                    echo '    <h3>Grafici delle Statistiche</h3>';
                    echo '    <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 20px;">';
                    echo '        <canvas id="killsChart"></canvas>';
                    echo '        <canvas id="damageChart"></canvas>';
                    echo '        <canvas id="killAssistsChart"></canvas>';
                    echo '    </div>';
                    echo '    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
                    echo '    <script>';
                    echo '        function createChart(ctx, chartLabel, data, backgroundColors) {';
                    echo '            return new Chart(ctx, {';
                    echo '                type: "pie",';
                    echo '                data: {';
                    echo '                    labels: ' . json_encode($labels) . ',';
                    echo '                    datasets: [{';
                    echo '                        label: chartLabel,';
                    echo '                        data: data,';
                    echo '                        backgroundColor: backgroundColors,';
                    echo '                        borderColor: "rgba(255, 255, 255, 1)",';
                    echo '                        borderWidth: 1';
                    echo '                    }]';
                    echo '                },';
                    echo '                options: {';
                    echo '                    responsive: true,';
                    echo '                    plugins: {';
                    echo '                        legend: { position: "top" },';
                    echo '                        tooltip: {';
                    echo '                            callbacks: {';
                    echo '                                label: function(tooltipItem) {';
                    echo '                                    return tooltipItem.label + ": " + tooltipItem.raw;';
                    echo '                                }';
                    echo '                            }';
                    echo '                        }';
                    echo '                    }';
                    echo '                }';
                    echo '            });';
                    echo '        }';

                    echo '        const ctxKills = document.getElementById("killsChart").getContext("2d");';
                    echo '        const ctxDamage = document.getElementById("damageChart").getContext("2d");';
                    echo '        const ctxKillAssists = document.getElementById("killAssistsChart").getContext("2d");';

                    echo '        const backgroundColors = [';
                    echo '            "rgba(255, 99, 132, 0.6)",';
                    echo '            "rgba(54, 162, 235, 0.6)",';
                    echo '            "rgba(255, 206, 86, 0.6)",';
                    echo '            "rgba(75, 192, 192, 0.6)",';
                    echo '            "rgba(153, 102, 255, 0.6)",';
                    echo '            "rgba(255, 159, 64, 0.6)"';
                    echo '        ];';

                    echo '        createChart(ctxKills, "Kills", ' . json_encode($killsData) . ', backgroundColors);';
                    echo '        createChart(ctxDamage, "Damage", ' . json_encode($damageData) . ', backgroundColors);';
                    echo '        createChart(ctxKillAssists, "Kill Assists", ' . json_encode($killAssistsData) . ', backgroundColors);';

                    echo '    </script>';
                    echo '</div>';
                } else {
                    // echo "No stats available.<br>";
                }
                ?>
            </div>

            <div class="col-md-7">
                <?php
                // Stampa la classifica
                if (!empty($cs2Classifica) && $game === "Csgo") {
                    echo "<h2>Statistiche di CSGO 2</h2>";
                    echo '<div style="overflow-x:auto;">';
                    echo '<table class="table table-dark table-striped">';
                    echo "<thead>
        <tr>
            <th>Nickname</th>
            <th>Steam ID</th>
            <th>Win Percentage (%)</th>
            <th>Total Kills</th>
            <th>Total Deaths</th>
            <th>Total Damage Done</th>
            <th>Last Match Kills</th>
            <th>Last Match Deaths</th>
            <th>Last Match MVPs</th>
        </tr>
    </thead>";
                    echo "<tbody>"; // Add tbody for better structure
                
                    foreach ($cs2Classifica as $user) {
                        echo "<tr>
            <td>{$user['nickname']}</td>
            <td>{$user['steamID']}</td>
            <td>{$user['cs2WinPercentage']}</td>
            <td>{$user['totalKills']}</td>
            <td>{$user['totalDeaths']}</td>
            <td>{$user['totalDamageDone']}</td>
            <td>{$user['last_match']['last_match_kills']}</td>
            <td>{$user['last_match']['last_match_deaths']}</td>
            <td>{$user['last_match']['last_match_mvps']}</td>
        </tr>";
                    }

                    echo "</tbody>"; // Close tbody
                    echo "</table>";
                    echo "</div>"; // Close the leaderboard div
                } else {
                    echo "<div class='leaderboard'>";
                    // echo "<p>Nessun utente trovato con statistiche valide.</p>";
                    echo "</div>"; // Close the leaderboard div
                }

                echo "</tbody>"; // Close tbody
                echo "</table>";
                echo "</div>"; // Close the leaderboard div


                // Stampa le medie
                if ((isset($averageKills) || isset($averageDeaths) || isset($averageDamageDone) || isset($averageWinPercentage)) && $game === "Csgo") {
                    echo "<h2>Statistiche Medie</h2>";
                    echo '<div style="overflow-x:auto;">';
                    echo '<table class="table table-dark table-striped">';
                    echo "<thead>
        <tr>
            <th>Media Kills</th>
            <th>Media Deaths</th>
            <th>Media Danno Totale</th>
            <th>Media Win Percentage (%)</th>
        </tr>
    </thead>";
                    echo "<tbody>";
                    echo "<tr>";
                    echo "<td>" . (isset($averageKills) ? $averageKills : '-') . "</td>";
                    echo "<td>" . (isset($averageDeaths) ? $averageDeaths : '-') . "</td>";
                    echo "<td>" . (isset($averageDamageDone) ? $averageDamageDone : '-') . "</td>";
                    echo "<td>" . (isset($averageWinPercentage) ? $averageWinPercentage : '-') . "%</td>";
                    echo "</tr>";
                    echo "</tbody>"; // Close tbody
                    echo "</table>";
                    echo "</div>"; // Close the averages div

                    // Pie chart
                    echo "<canvas id='statisticsPieChart' width='400' height='400'></canvas>";
            ?>

                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const ctx = document.getElementById('statisticsPieChart').getContext('2d');

                        // Data for the pie chart
                        const labels = ['Total Kills', 'Total Deaths', 'Total Damage Done', 'Average Win Percentage'];
                        const data = [
                            <?php echo isset($averageKills) ? $averageKills : 0; ?>,
                            <?php echo isset($averageDeaths) ? $averageDeaths : 0; ?>,
                            <?php echo isset($averageDamageDone) ? $averageDamageDone : 0; ?>,
                            <?php echo isset($averageWinPercentage) ? $averageWinPercentage : 0; ?>
                        ];

                        const statisticsPieChart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Average Statistics',
                                    data: data,
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.2)',
                                        'rgba(54, 162, 235, 0.2)',
                                        'rgba(255, 206, 86, 0.2)',
                                        'rgba(75, 192, 192, 0.2)'
                                    ],
                                    borderColor: [
                                        'rgba(255, 99, 132, 1)',
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 206, 86, 1)',
                                        'rgba(75, 192, 192, 1)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    title: {
                                        display: true,
                                        text: 'Average Statistics Overview'
                                    }
                                }
                            }
                        }
                    );
                </script>
                <?php
                } else {
                    // echo "<tr><td colspan='4'>Nessun dato disponibile per le statistiche medie.</td></tr>";
                }
                ?>
                                <div class="col-md-8">
                    <h1>Statistiche di Dota 2</h1>
                    
                    <?php
                    // Print user statistics
                    if ($game === "Dota2") {
                        if (!empty($dota2wlr)) {
                            echo '<table class="table table-dark table-striped">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Nickname</th>';
                            echo '<th>wins</th>';
                            echo '<th>losses</th>';
                            echo '<th>Win/Loss Ratio</th>'; // Change to Danni Totali
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            foreach ($dota2wlr as $data) {
                               
                                $nickname = htmlspecialchars($data['nickname']);
                                $wlr = htmlspecialchars($data['wlr']); // Change to totalDamageDone
                                $wins = htmlspecialchars($data['totalWins']);
                                $losses = htmlspecialchars($data['totalLosses']);
                                echo '<tr>';
                                echo "<td>{$nickname}</td>";
                                echo "<td>{$wins}</td>";
                                echo "<td>{$losses}</td>";
                                echo "<td>{$wlr}</td>"; // Change to totalDamageDone
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>No stats available.</li>";
                        }
                        if (!empty($dota2kdr)) {
                            echo '<table class="table table-dark table-striped">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Nickname</th>';
                            echo '<th>Kills</th>';
                            echo '<th>Deaths</th>';
                            echo '<th>K/D</th>'; // Change to Danni Totali
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($dota2kdr as $data) {
                                $nickname = htmlspecialchars($data['nickname']);
                                $kills = htmlspecialchars($data['totalKills']);
                                $deaths = htmlspecialchars($data['totalDeaths']);
                                $kdr = htmlspecialchars($data['kdr']); // Change to totalDamageDone

                                echo '<tr>';
                                echo "<td>{$nickname}</td>";
                                echo "<td>{$kills}</td>";
                                echo "<td>{$deaths}</td>";
                                echo "<td>{$kdr}</td>"; // Change to totalDamageDone
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>No stats available.</li>";
                        }
                        
                        if (!empty($dota2avg)) {
                            echo '<table class="table table-dark table-striped">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Average kills</th>';
                            echo '<th>Average kdr</th>';
                            echo '<th>Average wr</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($dota2avg as $data) {
                                $avg_kills = htmlspecialchars($data['avg_kills']);
                                $avg_kdr = htmlspecialchars($data['avg_kdr']);
                                $avg_wr = htmlspecialchars($data['avg_wr']);

                                echo '<tr>';
                                echo "<td>{$avg_kills}</td>";
                                echo "<td>{$avg_kdr}</td>";
                                echo "<td>{$avg_wr}</td>";
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>No stats available.</li>";
                        }
                    } else {
                        // echo "No stats available.<br>";
                    }
                    ?>
                </div>
        </div>

    </div>

    <?php
    $stmt->close();
    $conn->close();
    ?>

    <script>
        // Trasferisci i dati di sessione dal PHP al JavaScript
        var sessionData = <?php echo $sessionData; ?>;

        // Stampa i dati di sessione nella console del browser
        console.log(sessionData);
    </script>
</body>

</html>