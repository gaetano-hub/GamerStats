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
$game_id = '29595';

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

function getPlayerRecentMatches($account_id)
{
    $url = "https://api.opendota.com/api/players/" . $account_id . "/recentMatches";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($info['http_code'] != 200) {
        error_log("Failed to get player recent matches: HTTP status code " . $info['http_code']);
    }
    curl_close($ch);
    $data = json_decode($response, true);
    if ($data === null) {
        error_log("Failed to get player recent matches: JSON decode failed");
        throw new Exception('Failed to get player recent matches');
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

function generateDota2LeaderboardKdr()
{
    $leaderboardkdr = [];
    $dota2users = [];
    // Connessione al database
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";

    //echo "<script>console.log('generaClassificaDota2: connecting to database');</script>";
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Controlla la connessione
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query per ottenere tutti i dati degli utenti con SteamID
    $query = "SELECT nickname, steamID FROM users WHERE steamID NOT LIKE '' AND steamID IS NOT NULL";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $nickname = htmlspecialchars($row['nickname']);
            $steamID = htmlspecialchars($row['steamID']);
            $dota2users[] = [
                'nickname' => $nickname,
                'steamID' => $steamID
            ];
        }

    } else {
        //echo "<script>console.log('generaClassificaDota2: no users found');</script>";
        echo "Nessun utente trovato nel database.";
    }

    // Iterate through the users array and make a call with the dota2api php file
    foreach ($dota2users as $user) {
        $nickname = $user['nickname'];
        $player_id = $user['steamID'];
        $account_id = (int) $player_id - 76561197960265728;
        //updateDatabase($nickname, $account_id);

        //API CALL
        $recent_matches = getPlayerRecentMatches($account_id);
        if (!empty($recent_matches)) {
            $kdr = 0;
            $total_kills = 0;
            $total_deaths = 0;

            foreach ($recent_matches as $match) {
                $total_kills += $match['kills'];
                $total_deaths += $match['deaths'];
            }
            $kdr = $total_kills / ($total_deaths + 1);
            $leaderboardkdr[] = [
                'nickname' => $nickname,
                'steamID' => $player_id,
                'totalDeaths' => $total_deaths,
                'totalKills' => $total_kills,
                'kdr' => $kdr
            ];
        }
    }
    usort($leaderboardkdr, function ($a, $b) {
        return $b['kdr'] <=> $a['kdr'];
    });
    return $leaderboardkdr;
}
function generateDota2LeaderboardWlr()
{
    $leaderboardWlr = [];
    $dota2users = [];
    // Connessione al database
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";

    //echo "<script>console.log('generaClassificaDota2: connecting to database');</script>";
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Controlla la connessione
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query per ottenere tutti i dati degli utenti con SteamID
    $query2 = "SELECT nickname, steamID FROM users WHERE steamID NOT LIKE '' AND steamID IS NOT NULL";
    $result = $conn->query($query2);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $nickname = htmlspecialchars($row['nickname']);
            $steamID = htmlspecialchars($row['steamID']);
            //echo "<script>console.log(" . json_encode($nickname) . ");</script>";
            //echo "<script>console.log(" . json_encode($steamID) . ");</script>";
            $dota2users[] = [
                'nickname' => $nickname,
                'steamID' => $steamID
            ];
        }

    } else {
        //echo "<script>console.log('generaClassificaDota2: no users found');</script>";
        echo "Nessun utente trovato nel database.";
    }

    // Iterate through the users array and make a call with the dota2api php file
    foreach ($dota2users as $user) {
        $nickname = $user['nickname'];
        $player_id = $user['steamID'];
        $account_id = (int) $player_id - 76561197960265728;
        //updateDatabase($nickname, $account_id);
        //API CALL
        $wl = getPlayerWL($account_id);
        //echo "<script>console.log(" . json_encode($wl) . ");</script>";
        //echo "<script>console.log(" . json_encode($nickname) . ");</script>";
        $wlr = $wl['win'] / ($wl['lose'] + 1);
        $leaderboardWlr[] = [
            'nickname' => $nickname,
            'steamID' => $player_id,
            'totalWins' => $wl['win'],
            'totalLosses' => $wl['lose'],
            'wlr' => $wlr
        ];
    }
    usort($leaderboardWlr, function ($a, $b) {
        return $b['wlr'] <=> $a['wlr'];
    });
    return $leaderboardWlr;
}

$dota2kdr = generateDota2LeaderboardKdr();
$dota2wlr = generateDota2LeaderboardWlr();

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

function updateDatabase($name, $account_id)
    {
            
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gamerstats";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    //echo getPlayerAccountId($name, $conn);

    /*
    Tested with:
    */

    $account_info = getAccountInfo($account_id);
    $wl = getPlayerWL($account_id);
    //$matches = getPlayerMatches($account_id);
    //$totals = getPlayerTotals($account_id);
    $recent_matchesD = getPlayerRecentMatches($account_id);
    //echo json_encode($recent_matches);

    /*
    echo json_encode($account_info);
    echo json_encode($wl);
    echo json_encode($recent_matches);
    echo json_encode($totals);
    */

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
        echo json_encode(['error' => 'Error creating table: ' . $conn->error]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO `$name`
        (account_id, personaname, avatar, rank_tier, win, lose, match_id, kills, deaths, assists, average_rank, radiant_score, dire_score) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
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
            echo json_encode(['error' => 'Error inserting data for participant: '. $account_id]);
            //call code to take data from database
            exit;
        }
    }
    //echo json_encode($data);

    $stmt->close();
    $conn->close();
}

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
    <title>DOTA 2</title>
    <script>
        function sendData() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'dota2_ajax_handler.php', true);
            xhr.setRequestHeader('Content-Type', 'application/xml'); // Specify content type
            
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                // Process the response
                    console.log('Response received:', xhr.responseText);
                } else {
                    console.error('Request failed with status: ' + xhr.status);
                }
                    // Handle network errors
                xhr.onerror = function() {
                    console.error('Network error');
                };
            };

            xhr.send(); // Send the XML data
        }
    </script>
</head>

<body onload="sendData()">
    <div class="content">
        <nav class="navbar fixed-top navbar-expand-lg" style="background-color: var(--object_color);">
            <div class="container-fluid">
                <a class="navbar-brand fs-3" href="#" style="color: var(--brand_color);">GamerStats</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false" style="color: var(--navbar_textCol);">
                                Games
                            </a>
                            <ul class="dropdown-menu" style="background-color: var(--object_color);">
                                <li><a class="dropdown-item" href="../team_fortess2/team_fortess2.php"
                                        style="color: var(--brand_color);">Team Fortress 2</a></li>
                                <li><a class="dropdown-item" href="../csgo/csgo.php"
                                        style="color: var(--brand_color);">Csgo</a></li>
                                <li><a class="dropdown-item" href="../dota2/fota2.php"
                                        style="color: var(--brand_color);">Dota2</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-success" id="homeref" type="button"
                                style="background-color: var(--object_color);" href="../home/home.php">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                    width="24px" fill="#e8eaed">
                                    <path
                                        d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item">
                            <form class="d-flex" role="search">
                                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"
                                    style="background-color: var(--object_color);">
                                <button class="btn btn-outline-success" id="search" type="submit"
                                    style="background-color: var(--object_color);">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                        width="24px" fill="#e8eaed">
                                        <path
                                            d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                        <?php if (isset($_SESSION['nickname'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../memberPage/myProfile.php"
                                    style="color: var(--brand_color); font-weight: bold;">
                                    <?php echo htmlspecialchars($_SESSION['nickname']); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../logout/logout.php"
                                    style="color: var(--brand_color);">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../login/login.html"
                                    style="color: var(--brand_color);">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="../signUp/signUp.html"
                                    style="color: var(--brand_color);">Sign Up</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <div
            style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">DOTA2 Page</p>
        </div>

        <h1 class="text-center" style="color: white">Live Dota2 Streams</h1>
        <div id="streams-carousel" class="carousel slide streams mx-auto" style="max-width: 50%;" data-bs-ride="false">
            <div class="carousel-inner">
                <?php if (!empty($streams)): ?>
                    <?php foreach ($streams as $i => $stream): ?>
                        <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                            <div class="stream">
                                <div style="display: flex; justify-content: center;">
                                    <a href="https://www.twitch.tv/<?php echo htmlspecialchars($stream['user_name']); ?>"
                                        target="_blank" style="color:white">
                                        <?php echo htmlspecialchars($stream['user_name']); ?>
                                    </a>
                                </div>
                                <img src="<?php echo str_replace('{width}x{height}', '640x360', $stream['thumbnail_url']); ?>"
                                    alt="Stream Thumbnail" class="d-block mx-auto">
                                <p class="text-center" style="color: white">Viewers:
                                    <?php echo htmlspecialchars($stream['viewer_count']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
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
                                <img src="../assets/csgologo.png" class="card-img-top" alt="csgoLogo"
                                    style="width: 70px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Dota2 Top Kill</h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
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

                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 30rem; background-color: var(--object_color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <img src="../assets/csgologo.png" class="card-img-top" alt="csgoLogo"
                                    style="width: 70px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Dota 2 Top Winners
                                </h5>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
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