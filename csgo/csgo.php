<?php
session_start();

// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);

$access_token = "dqe8civavof6jfgaq0cbjekb4oqkyg";
$validate_url = "https://id.twitch.tv/oauth2/validate";

// Initialize cURL session
$ch = curl_init();

// Set the URL and headers
curl_setopt($ch, CURLOPT_URL, $validate_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: OAuth ' . $access_token
]);

// Execute cURL request
$response = curl_exec($ch);

// Check for errors or invalid token
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
} else {
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode == 200) {
        $response_data = json_decode($response, true);
    } else {
        echo "Token is invalid or expired.";
    }
}

$access_token = 'dqe8civavof6jfgaq0cbjekb4oqkyg';
$client_id = 'kdky4zjc7xuo41zu0v1bqf3y9hp41v';
$game_id = '32399';

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
    // echo "Error fetching streams.";
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
        // echo "Errore durante l'inserimento dei dati: " . $stmt->error . "<br>";
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

        

        $cs2Stats = getGameStats($steamID, $apiKey, $cs2GameId);
        if(empty($cs2Stats)){
            continue;
        }


        if(empty($steamID)){
            continue;  
        }

        $userDetails[$steamID] = [
            'nickname' => $nickname,
            'tf2Score' => 0,
            'cs2WinPercentage' => 0
        ];

        $cs2StatsArray[$steamID] = [
            'kills' => 0,
            'deaths' => 0,
            'mvps' => 0
        ];

        
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
    // echo "Nessun utente trovato nel database.";
}

function generaClassificaCSGO($gameStatsArray, &$userDetails, $apiKey, $gameId)
{
    $classifica = [];

    // Initialize classifica for all users in userDetails who have a Steam ID
    foreach ($userDetails as $steamID => $details) {
        if (!empty($steamID)) { // Only proceed if Steam ID is present
            $classifica[$steamID] = [
                'nickname' => $details['nickname'],
                'steamID' => $steamID,
                'win_percentage' => 0, // Default win percentage to 0
                'last_match' => null // Default last match stats to null
            ];
        }
    }

    foreach ($gameStatsArray as $steamID => $stats) {
        // Skip if Steam ID is empty or not in the initialized classifica
        if (empty($steamID) || !isset($classifica[$steamID])) {
            continue;
        }

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
            $losses = $totalMatches - $totalWins;
            $winPercentage = round(($totalWins / ($losses + 1)), 2); // Calcolo percentuale vittorie
            $lastMatchStats = getLastMatchStats($steamID, $apiKey, $gameId);

            
            

            // Update the corresponding entry in the classifica
            $classifica[$steamID]['win_percentage'] = $winPercentage;
            $classifica[$steamID]['last_match'] = $lastMatchStats;
        }
    }

    // Filter out any entries without a valid Steam ID or necessary data
    $classifica = array_filter($classifica, function ($entry) {
        return !empty($entry['steamID']);
    });

    // Sort the classifica by win percentage in descending order
    uasort($classifica, function ($a, $b) {
        return $b['win_percentage'] <=> $a['win_percentage'];
    });

    return array_values($classifica); // Return a zero-indexed list
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

        $cs2Stats = getGameStats($steamID, $apiKey, $cs2GameId);
        if (empty($cs2Stats)) {
            continue;
        }

        if(empty($steamID)){
            continue;
        }
        $userDetails[$steamID] = [
            'nickname' => $nickname,
            'totalKills' => 0,
            'totalDeaths' => 0,
            'totalDamageDone' => 0 // Change to totalDamageDone
        ];
        

        // Ottieni le statistiche per CS2
        
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
    // echo "Nessun utente trovato nel database.";
}



// Funzione per generare la classifica in base ai totali dei kills
function generaClassificaCSGO_kill($userDetails)
{
    // Check if there are any user details
    if (empty($userDetails)) {
        return []; // Return an empty array if there are no Steam IDs
    }
   
    
    // Initialize an array for the leaderboard
    $leaderboard = [];

    foreach ($userDetails as $steamID => $details) {
        // Check if the steamID is valid and not empty
        if (!empty($steamID)) {
            $leaderboard[] = [
                'nickname' => $details['nickname'],
                'steamID' => $steamID,
                'totalKills' => $details['totalKills'],
                'totalDeaths' => $details['totalDeaths'],
                'totalDamageDone' => $details['totalDamageDone'], // Change to totalDamageDone
            ];
        }
    }

    // Sort the leaderboard by total kills (descending)
    usort($leaderboard, function ($a, $b) {
        return $b['totalKills'] <=> $a['totalKills'];
    });

    return $leaderboard; // Return the sorted leaderboard
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
    <title>Csgo</title>
</head>

<body>
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
                                <!-- TODO: aggiungere href per arrivare alle pagine dei giochi-->
                                <li><a class="dropdown-item" href="../team_fortess2/team_fortess2.php" style="color: var(--brand_color);">Team Fortress 2</a></li>
                                <li><a class="dropdown-item" href="../csgo/csgo.php" style="color: var(--brand_color);">Csgo</a></li>
                                <li><a class="dropdown-item" href="../dota2/dota2.php" style="color: var(--brand_color);">Dota 2</a></li>
                                <!-- <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item" href="#">Something else here</a></li> 
                                 Possono sempre servire -->
                            </ul>
                        </li>
                        <li class="nav-item" style="margin-left: auto; margin-top: auto;">
                            <a class="btn btn-outline-success" id="homeref" type="button" style=" background-color:var(--object_color);" href="../home/home.php">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <!-- TODO: modificare href e vari dettagli del signup e login-->
                    <ul class="navbar-nav align-items-center mb-2 mb-lg-0">
                        <li class="nav-item align-self-center">
                            <div style="position: relative;">
                                <form class="d-flex" role="search" action="search.php" method="post" id="searchForm" style="margin-top: 10px;">
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

                                fetch('../home/search.php', {
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
                        <!-- Controllo se l'utente è loggato -->
                        <?php if (isset($_SESSION['nickname'])): ?>
                            <!-- L'utente è loggato, mostra Logout -->
                            <li class="nav-item">
                                <a class="nav-link" href="../memberPage/myProfile.php" style="color: var(--brand_color); font-weight: bold;">
                                    <?php echo $_SESSION['nickname']; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../logout/logout.php" style="color: var(--brand_color);">Logout</a>
                            </li>
                        <?php else: ?>
                            <!-- L'utente non è loggato, mostra Login e Sign Up -->
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

        <div style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Csgo Page</p>
        </div>

        <h1 class="text-center" style="color: white">Live Csgo Streams</h1>
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
                                <img src="../assets/csgologo.png" class="card-img-top" alt="csgoLogo" style="width: 70px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Csgo Top Kill</h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
                                if (!empty($cs2Classifica_kill)) {
                                    echo '<table class="table table-dark table-striped">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Nickname</th>';
                                    echo '<th>Kills</th>';
                                    echo '<th>Deaths</th>';
                                    echo '<th>Total Damage Done</th>'; // Change to Danni Totali
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
                                    echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>No stats available.</li>";
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
                                <img src="../assets/csgologo.png" class="card-img-top" alt="csgoLogo" style="width: 70px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Csgo Top Winners</h5>
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
                                        echo "<td><span class='badge bg-primary rounded-pill'>" . htmlspecialchars($player['win_percentage']) . "%</span></td>";

                                        if (!empty($player['last_match'])) {
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
                                    echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>No stats available.</li>";
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