<?php
session_start();

// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);

$access_token = 'swqwsxqrdbfu9snanpeqm2k2fjewkr';
$client_id = 'kdky4zjc7xuo41zu0v1bqf3y9hp41v';
$game_id = '21779';

// Fetch the top 10 live streams for the specified game
$api_url = 'https://api.twitch.tv/helix/streams?game_id=' . $game_id . '&first=10';

// Initialize cURL session
$ch = curl_init();

// Set the request URL and headers
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Client-ID: ' . $client_id
]);

// Execute cURL request and fetch response
$response = curl_exec($ch);
curl_close($ch);

// Decode the JSON response into a PHP array
$streams_data = json_decode($response, true);

if (isset($streams_data['data'])) {
    $streams = $streams_data['data'];
} else {
    echo "Error fetching streams.";
}

// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GamerStats";

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

// Query per ottenere tutti i dati degli utenti
$query = "SELECT id, nickname, email, password, steamID, image FROM users WHERE steamID IS NOT NULL";
$result = $conn->query($query);

// API Key di Steam
$apiKey = '8A345C81E607D2E02274B11D4834675A';
$cs2GameId = 730;

// Funzione per ottenere le statistiche di un gioco
function getGameStats($steamID, $apiKey, $gameId)
{
    $url = "https://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid={$gameId}&steamid={$steamID}&key={$apiKey}";
    $response = @file_get_contents($url);

    if ($response === false) {
        return null;
    }

    return json_decode($response, true);
}

// Funzione per inserire i dati nelle tabelle di classifica
function insertIntoClassifica($conn, $tableName, $nickname, $steamID, $score)
{
    $stmt = $conn->prepare("INSERT INTO $tableName (nickname, steamID, punteggio) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE punteggio = ?");

    $stmt->bind_param("ssdd", $nickname, $steamID, $score, $score);
    if ($stmt->execute()) {
    } else {
        echo "Errore durante l'inserimento dei dati: " . $stmt->error . "<br>";
    }

    $stmt->close();
}

// Inizializza array per le statistiche
$cs2StatsArray = [];
$userDetails = [];

if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
        $steamID = $row['steamID'];
        $nickname = htmlspecialchars($row['nickname']);
        $image = $row['image'];

        $userDetails[$steamID] = [
            'nickname' => $nickname,
            'tf2Score' => 0,
            'cs2WinPercentage' => 0
        ];

        $cs2Stats = getGameStats($steamID, $apiKey, $cs2GameId);
        if (isset($cs2Stats['playerstats']['stats'])) {
            $cs2StatsArray[$steamID] = $cs2Stats['playerstats']['stats'];
            $totalWins = 0;
            $totalMatches = 0;

            foreach ($cs2StatsArray[$steamID] as $stat) {
                if ($stat['name'] === 'total_matches_won') {
                    $totalWins = $stat['value'];
                }
                if ($stat['name'] === 'total_matches_played') {
                    $totalMatches = $stat['value'];
                }
            }
            if ($totalMatches > 0) {
                $userDetails[$steamID]['cs2WinPercentage'] = round(($totalWins / $totalMatches) * 100, 2);
            }
        }
    }
} else {
    echo "Nessun utente trovato nel database.";
}

function generaClassificaCSGO($gameStatsArray, &$userDetails, $apiKey, $gameId)
{
    $classifica = [];

    foreach ($gameStatsArray as $steamID => $stats) {
        $totalWins = 0;
        $totalMatches = 0;

        foreach ($stats as $stat) {
            if ($stat['name'] === 'total_matches_won') {
                $totalWins = $stat['value'];
            }
            if ($stat['name'] === 'total_matches_played') {
                $totalMatches = $stat['value'];
            }
        }

        if ($totalMatches > 0) {
            $winPercentage = round(($totalWins / $totalMatches) * 100, 2);
            $lastMatchStats = getLastMatchStats($steamID, $apiKey, $gameId);

            $classifica[$steamID] = [
                'nickname' => $userDetails[$steamID]['nickname'],
                'steamID' => $steamID,
                'win_percentage' => $winPercentage,
                'last_match' => $lastMatchStats
            ];
        }
    }

    usort($classifica, function ($a, $b) {
        return $b['win_percentage'] <=> $a['win_percentage'];
    });

    return $classifica;
}

$cs2Classifica = generaClassificaCSGO($cs2StatsArray, $userDetails, $apiKey, $cs2GameId);

function getLastMatchStats($steamID, $apiKey, $gameId)
{
    $url = "https://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid={$gameId}&steamid={$steamID}&key={$apiKey}";
    $response = @file_get_contents($url);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (isset($data['playerstats']['stats'])) {
        $lastMatchStats = [];

        foreach ($data['playerstats']['stats'] as $stat) {
            if ($stat['name'] === 'last_match_kills') {
                $lastMatchStats['kills'] = $stat['value'];
            }
            if ($stat['name'] === 'last_match_deaths') {
                $lastMatchStats['deaths'] = $stat['value'];
            }
            if ($stat['name'] === 'last_match_mvps') {
                $lastMatchStats['mvps'] = $stat['value'];
            }
        }

        return $lastMatchStats;
    }

    return null;
}








// Inizializza array per le statistiche
$cs2StatsArray = [];
$userDetails = [];

// Query per ottenere tutti i dati degli utenti
$query = "SELECT id, nickname, steamID, image FROM users WHERE steamID IS NOT NULL";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $steamID = $row['steamID'];
        $nickname = htmlspecialchars($row['nickname']);
        $userDetails[$steamID] = [
            'nickname' => $nickname,
            'totalKills' => 0,
            'totalDeaths' => 0,
            'totalDamageDone' => 0 // Change to totalDamageDone
        ];

        // Ottieni le statistiche per CS2
        $cs2Stats = getGameStats($steamID, $apiKey, $cs2GameId);
        if (isset($cs2Stats['playerstats']['stats'])) {
            foreach ($cs2Stats['playerstats']['stats'] as $stat) {
                if ($stat['name'] === 'total_kills') {
                    $userDetails[$steamID]['totalKills'] = $stat['value'];
                } elseif ($stat['name'] === 'total_deaths') {
                    $userDetails[$steamID]['totalDeaths'] = $stat['value'];
                } elseif ($stat['name'] === 'total_damage_done') { // Change to total_damage_done
                    $userDetails[$steamID]['totalDamageDone'] = $stat['value'];
                }
            }
        }
    }
} else {
    echo "Nessun utente trovato nel database.";
}



// Funzione per generare la classifica in base ai totali dei kills
function generaClassificaCSGO_kill($userDetails)
{
    // Inizializza un array per la classifica
    $leaderboard = [];

    foreach ($userDetails as $steamID => $details) {
        $leaderboard[] = [
            'nickname' => $details['nickname'],
            'steamID' => $steamID,
            'totalKills' => $details['totalKills'],
            'totalDeaths' => $details['totalDeaths'],
            'totalDamageDone' => $details['totalDamageDone'], // Change to totalDamageDone
        ];
    }

    // Ordina la classifica per total kills (decrescente)
    usort($leaderboard, function ($a, $b) {
        return $b['totalKills'] <=> $a['totalKills'];
    });

    return $leaderboard;
}

// Chiama la funzione per generare la classifica
$cs2Classifica_kill = generaClassificaCSGO_kill($userDetails);



// Chiudi la connessione al database
$conn->close();
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../home/styleHome.css">
    <script src="../home/scriptHome.js" defer></script>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>League of Legends</title>
</head>

<body>
    <div class="content">
        <nav class="navbar fixed-top navbar-expand-lg" style="background-color: var(--object_color);">
            <div class="container-fluid">
                <a class="navbar-brand fs-3" href="#" style="color: var(--brand_color);">GamerStats</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--navbar_textCol);">
                                Games
                            </a>
                            <ul class="dropdown-menu" style="background-color: var(--object_color);">
                                <li><a class="dropdown-item" href="../team_fortess2/team_fortess2.php" style="color: var(--brand_color);">Team Fortress 2</a></li>
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">League of Legends</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-success" id="homeref" type="button" style="background-color: var(--object_color);" href="../home/home.php">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item">
                            <form class="d-flex" role="search">
                                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" style="background-color: var(--object_color);">
                                <button class="btn btn-outline-success" id="search" type="submit" style="background-color: var(--object_color);">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                        <?php if (isset($_SESSION['nickname'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../memberPage/myProfile.php" style="color: var(--brand_color); font-weight: bold;">
                                    <?php echo htmlspecialchars($_SESSION['nickname']); ?>
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

        <div style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">LoL Page</p>
        </div>

        <h1 class="text-center" style="color: white">Live LoL Streams</h1>
        <div id="streams-carousel" class="carousel slide streams mx-auto" style="max-width: 50%;" data-bs-ride="false">
            <div class="carousel-inner">
                <?php if (!empty($streams)) : ?>
                    <?php foreach ($streams as $i => $stream) : ?>
                        <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                            <div class="stream">
                                <div style="display: flex; justify-content: center;">
                                    <a href="https://www.twitch.tv/<?php echo htmlspecialchars($stream['user_name']); ?>" target="_blank" style="color:white">
                                        <?php echo htmlspecialchars($stream['user_name']); ?>
                                    </a>
                                </div>
                                <img src="<?php echo str_replace('{width}x{height}', '640x360', $stream['thumbnail_url']); ?>" alt="Stream Thumbnail" class="d-block mx-auto">
                                <p class="text-center" style="color: white">Viewers: <?php echo htmlspecialchars($stream['viewer_count']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="carousel-item active text-center">
                        <p>No streams available.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#streams-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#streams-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        <div class="d-flex justify-content-center" style="margin-top: 5rem; margin-bottom: 5rem; height: auto;">
            <div class="row">
                <div class="col">
                    <div class="card" style="width: 30rem; background-color: var(--object_color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <img src="../assets/lollogo.webp" class="card-img-top" alt="lolLogo" style="width: 50px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Lol Top boh</h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
                                if (!empty($cs2Classifica_kill)) {
                                echo '<table class="table table-dark table-striped">';
                                    echo '<thead>';
                                        echo '<tr>';
                                            echo '<th>Nickname</th>';
                                            echo '<th>Kills</th>';
                                            echo '<th>Morti</th>';
                                            echo '<th>Danni Totali</th>'; // Change to Danni Totali
                                            echo '</tr>';
                                        echo '</thead>';
                                    echo '<tbody>';

                                        foreach ($cs2Classifica_kill as $data) {
                                        $nickname = htmlspecialchars($data['nickname']);
                                        $kills = htmlspecialchars($data['totalKills']);
                                        $deaths = htmlspecialchars($data['totalDeaths']);
                                        $totalDamageDone = htmlspecialchars($data['totalDamageDone']); // Change to totalDamageDone

                                        echo '<tr>';
                                            echo "<td>{$nickname}</td>";
                                            echo "<td>{$kills}</td>";
                                            echo "<td>{$deaths}</td>";
                                            echo "<td>{$totalDamageDone}</td>"; // Change to totalDamageDone
                                            echo '</tr>';
                                        }

                                        echo '</tbody>';
                                    echo '</table>';
                                } else {
                                echo "Nessuna classifica disponibile.<br>";
                                }

                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 30rem; background-color: var(--object_color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <img src="../assets/lollogo.webp" class="card-img-top" alt="lollLogo" style="width: 50px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">LoL Top Winners</h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
                                if (!empty($cs2Classifica)) {
                                    echo "<table class='table table-dark table-striped'>
            <thead>
                <tr>
                    <th>Nickname</th>
                    <th>Win Percentage</th>
                    <th>Last Match Stats</th>
                </tr>
            </thead>
            <tbody>";
                                    foreach ($cs2Classifica as $player) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($player['nickname']) . "</td>";
                                        echo "<td>" . htmlspecialchars($player['win_percentage']) . "%</td>";
                                        if ($player['last_match']) {
                                            echo "<td class='last-match'>Kills: " . htmlspecialchars($player['last_match']['kills']) .
                                                ", Deaths: " . htmlspecialchars($player['last_match']['deaths']) .
                                                ", MVPs: " . htmlspecialchars($player['last_match']['mvps']) . "</td>";
                                        } else {
                                            echo "<td class='last-match'>N/A</td>";
                                        }
                                        echo "</tr>";
                                    }
                                    echo "</tbody>
        </table>";
                                } else {
                                    echo "<h2>Nessuna classifica disponibile.</h2>";
                                }
                                ?>




                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Trasferisci i dati di sessione dal PHP al JavaScript
            var sessionData = <?php echo $sessionData; ?>;

            // Stampa i dati di sessione nella console del browser
            console.log(sessionData);
        </script>
</body>

</html>