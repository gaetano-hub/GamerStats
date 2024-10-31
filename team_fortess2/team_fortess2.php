<?php
session_start();

// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);

$access_token = "swqwsxqrdbfu9snanpeqm2k2fjewkr";
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

$access_token = 'swqwsxqrdbfu9snanpeqm2k2fjewkr';
$client_id = 'kdky4zjc7xuo41zu0v1bqf3y9hp41v';
$game_id = '16676';

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
$username = "root"; // Il nome utente predefinito di XAMPP è "root"
$password = ""; // Di solito la password è vuota
$dbname = "GamerStats";

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

// Query per ottenere tutti i dati degli utenti
$query = "SELECT id, nickname, steamID FROM users WHERE steamID IS NOT NULL";
$result = $conn->query($query);

// API Key di Steam
$apiKey = '8A345C81E607D2E02274B11D4834675A';
$tf2GameId = 440; // Team Fortress 2

// Funzione per ottenere le statistiche di un gioco
function getGameStats($steamID, $apiKey, $gameId)
{
    $url = "https://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid={$gameId}&steamid={$steamID}&key={$apiKey}";
    $response = @file_get_contents($url);

    // Error handling
    if ($response === false) {
        return null; // Handle the error as needed
    }

    return json_decode($response, true);
}



// Inizializza array per le statistiche
$tf2StatsArray = [];
$userDetails = []; // Array per memorizzare dettagli utente

// Recupera gli Steam ID e altre informazioni dal database
if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
        $steamID = $row['steamID'];
        $nickname = htmlspecialchars($row['nickname']);

        // Memorizza i dettagli utente
        $userDetails[$steamID] = [
            'nickname' => $nickname,
            'tf2Score' => 0,
            'cs2WinPercentage' => 0
        ];

        // Statistiche di Team Fortress 2
        $tf2Stats = getGameStats($steamID, $apiKey, $tf2GameId);
        if (isset($tf2Stats['playerstats']['stats'])) {
            $tf2StatsArray[$steamID] = $tf2Stats['playerstats']['stats'];
            foreach ($tf2StatsArray[$steamID] as $stat) {
                if ($stat['name'] === 'iPointsScored') {
                    $userDetails[$steamID]['tf2Score'] = $stat['value'];
                }
            }
        }
        // echo "<li><strong>Nickname:</strong> {$nickname} - <strong>Steam ID:</strong> {$steamID}</li>";
    }
    echo "</ul>";
} else {
    // echo "Nessun utente trovato nel database.";
}

// Funzione per generare la classifica di Team Fortress 2
function generaClassificaTF2($tf2Stats, &$userDetails)
{
    $userScores = [];
    $importantStats = ['iPointsScored'];
    $validClasses = ['Scout', 'Soldier', 'Pyro', 'Demoman', 'Heavy', 'Engineer', 'Medic', 'Sniper', 'Spy'];



    // Controlla se gli array necessari non sono vuoti
if (!empty($tf2Stats) && !empty($importantStats) && !empty($validClasses)) {
    foreach ($tf2Stats as $steamID => $stats) {
        foreach ($stats as $stat) {
            foreach ($importantStats as $importantStat) {
                if (strpos($stat['name'], $importantStat) !== false) {
                    $parts = explode('.', $stat['name']);
                    if (count($parts) === 3) {
                        $className = $parts[0];
                        if (in_array($className, $validClasses)) {
                            // Inizializza il punteggio a 0 se non è stato già impostato
                            if (!isset($userScores[$steamID])) {
                                $userScores[$steamID] = 0;
                            }
                            $userScores[$steamID] += $stat['value'];
                        }
                    }
                }
            }
        }
    }

    // Aggiorna i punteggi degli utenti se $userScores non è vuoto
    if (!empty($userScores)) {
        foreach ($userScores as $steamID => $score) {
            if (isset($userDetails[$steamID])) {
                $userDetails[$steamID]['tf2Score'] = $score;
            }
        }
    }
} else {
    // Gestisci il caso in cui uno degli array sia vuoto, se necessario
    //echo "Uno o più degli array necessari sono vuoti.";
}

    arsort($userScores); // Ordina i punteggi degli utenti in modo decrescente
    return $userScores;
}




// Genera le classifiche per TF2 e CS2
$tf2Classifica = generaClassificaTF2($tf2StatsArray, $userDetails);

// // Inserisci i dati nella tabella di classifica di Team Fortress 2
// foreach ($tf2Classifica as $steamID => $score) {
//     $nickname = htmlspecialchars($userDetails[$steamID]['nickname']);
//     insertIntoClassifica($conn, 'tf2_classifica', $nickname, $steamID, $score);
// }




// Query to get all users
$query = "SELECT id, nickname, steamID FROM users WHERE steamID IS NOT NULL";
$result = $conn->query($query);

if ($result === false) {
    die("Error executing query: " . $conn->error);
} else {
    // echo "Query executed successfully. Found users: " . $result->num_rows . "<br>"; // Debugging output
}



// Initialize arrays for statistics
$userDetails = [];

// Fetch user details and stats
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $steamID = $row['steamID'];
        $nickname = htmlspecialchars($row['nickname']);

        $tf2Stats = getGameStats($steamID, $apiKey, $tf2GameId);
        if (empty($tf2Stats)) {
            continue; // Skip if no stats available
        }

        if (!empty($steamID)) {
            $userDetails[$steamID] = [
                'nickname' => $nickname,
                'kills' => 0,
                'damage' => 0,
                'killAssists' => 0
            ];

            // Fetch TF2 stats
            if (isset($tf2Stats['playerstats']['stats'])) {
                foreach ($tf2Stats['playerstats']['stats'] as $stat) {
                    // Check if the stat name contains specific substrings
                    if (strpos($stat['name'], 'iNumberOfKills') !== false) {
                        $userDetails[$steamID]['kills'] += $stat['value'];
                    } elseif (strpos($stat['name'], 'iDamageDealt') !== false) {
                        $userDetails[$steamID]['damage'] += $stat['value'];
                    } elseif (strpos($stat['name'], 'iKillAssists') !== false) {
                        $userDetails[$steamID]['killAssists'] += $stat['value'];
                    }
                }
            }

            // Remove users with default stats
            if ($userDetails[$steamID]['kills'] === 0 && 
                $userDetails[$steamID]['damage'] === 0 && 
                $userDetails[$steamID]['killAssists'] === 0) {
                unset($userDetails[$steamID]);
            }
        }
    }
}


// Create a ranking score for each user
$rankingScores = [];
if (empty($userDetails)) {
    echo "Nessun utente trovato.";
} else {
    foreach ($userDetails as $steamID => $stats) {
        // Adjust the scoring system as needed
        $score = $stats['kills'] * 2 + $stats['damage'] * 0.01 + $stats['killAssists']; // Example scoring system
        $rankingScores[$steamID] = [
            'nickname' => $stats['nickname'],
            'score' => $score,
            'kills' => $stats['kills'],
            'damage' => $stats['damage'],
            'killAssists' => $stats['killAssists']
        ];
    }
}


// Sort the ranking scores in descending order based on score
usort($rankingScores, function ($a, $b) {
    return $b['kills'] <=> $a['kills']; // Sort by kills in descending order
});




// Close the database connection
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
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>Team fortress 2</title>
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
            style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Team Fortress 2</p>
        </div>
        <h1 class="text-center" style="color: white">Live Team Fortress 2 Streams</h1>
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
                                <img src="../assets/tf2.png" class="card-img-top" alt="tf2Logo" style="width: 70px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Team Fortress 2 Top Score</h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
                                if (!empty($rankingScores)) {
                                    echo '<table class="table table-dark table-striped">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th>Nickname</th>';
                                    echo '<th>Kills</th>';
                                    echo '<th>Damage</th>';
                                    echo '<th>Kill Assists</th>';
                                    echo '<th>Total points</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';

                                    foreach ($rankingScores as $data) {
                                        // Use the nickname from the ranking data
                                        $nickname = htmlspecialchars($data['nickname']);
                                        $totalScore = htmlspecialchars($data['score']);
                                        $kills = htmlspecialchars($data['kills']);
                                        $damage = htmlspecialchars($data['damage']);
                                        $killAssists = htmlspecialchars($data['killAssists']);

                                        echo '<tr>';
                                        echo "<td>{$nickname}</td>";
                                        echo "<td>{$kills}</td>";
                                        echo "<td>{$damage}</td>";
                                        echo "<td>{$killAssists}</td>";
                                        echo '<td><span class="badge bg-primary rounded-pill">' . $totalScore . ' punti</span></td>';
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
                                <img src="../assets/tf2.png" class="card-img-top" alt="tf2Logo" style="width: 70px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Team Fortress 2 Top Game Points</h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
                                if (!empty($tf2Classifica)) {

                                    // Display the second table (if needed)
                                    echo '<table class="table table-dark table-striped">';
                                    echo '    <thead>';
                                    echo '        <tr>';
                                    echo '            <th>Nickname</th>';
                                    echo '            <th>Total game points</th>';
                                    echo '        </tr>';
                                    echo '    </thead>';
                                    echo '    <tbody>';

                                    // Iterate through the TF2 leaderboard data
                                    foreach ($tf2Classifica as $steamID => $totalScore) {
                                        // Get the nickname from userDetails using the steamID
                                        $nickname = isset($userDetails[$steamID]) ? htmlspecialchars($userDetails[$steamID]['nickname']) : 'Sconosciuto';

                                        echo '        <tr>';
                                        echo '            <td>' . $nickname . '</td>';
                                        echo '            <td>';
                                        echo '                <span class="badge bg-primary rounded-pill">' . htmlspecialchars($totalScore) . ' punti</span>';
                                        echo '            </td>';
                                        echo '        </tr>';
                                    }

                                    echo '    </tbody>';
                                    echo '</table>';
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