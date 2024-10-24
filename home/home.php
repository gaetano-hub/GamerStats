<?php
session_start();

// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);

// Controlla se l'utente è loggato
if (isset($_SESSION['discord_user'])) {
    // echo "Benvenuto, " . htmlspecialchars($_SESSION['discord_user']['username']) . "! (Accesso tramite Discord)";
} elseif (isset($_SESSION['steam_user'])) {
    // echo "Benvenuto, " . htmlspecialchars($_SESSION['steam_user']['displayname']) . "! (Accesso tramite Steam)";
} elseif (isset($_SESSION['nickname'])) {
    // echo "Benvenuto, " . htmlspecialchars($_SESSION['nickname']) . "! (Accesso classico)";
} else {
    // echo "Devi effettuare il login.";
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

// Crea tabella per la classifica CS2 se non esiste
$createCs2Table = "CREATE TABLE IF NOT EXISTS cs2_classifica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(255) NOT NULL,
    steamID VARCHAR(255) NOT NULL UNIQUE,
    punteggio FLOAT NOT NULL
)";
if ($conn->query($createCs2Table) !== TRUE) {
    echo "Errore nella creazione della tabella cs2_classifica: " . $conn->error;
}

// Crea tabella per la classifica TF2 se non esiste
$createTf2Table = "CREATE TABLE IF NOT EXISTS tf2_classifica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(255) NOT NULL,
    steamID VARCHAR(255) NOT NULL UNIQUE,
    punteggio FLOAT NOT NULL
)";
if ($conn->query($createTf2Table) !== TRUE) {
    echo "Errore nella creazione della tabella tf2_classifica: " . $conn->error;
}

// Query per ottenere tutti i dati degli utenti
$query = "SELECT id, nickname, email, password, steamID, image FROM users WHERE steamID IS NOT NULL";
$result = $conn->query($query);

// API Key di Steam
$apiKey = '8A345C81E607D2E02274B11D4834675A';
$tf2GameId = 440; // Team Fortress 2
$cs2GameId = 730; // Counter-Strike 2

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

// Funzione per inserire i dati nelle tabelle di classifica
function insertIntoClassifica($conn, $tableName, $nickname, $steamID, $score)
{
    // Prepara la query per evitare SQL injection
    $stmt = $conn->prepare("INSERT INTO $tableName (nickname, steamID, punteggio) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE punteggio = ?");

    // Bind dei parametri
    $stmt->bind_param("ssdd", $nickname, $steamID, $score, $score); // 'ssdd' significa: string, string, double, double

    // Esegui la query
    if ($stmt->execute()) {
        // echo "Dati inseriti con successo nella tabella $tableName per $nickname.<br>";
    } else {
        echo "Errore durante l'inserimento dei dati: " . $stmt->error . "<br>";
    }

    // Chiudi la dichiarazione
    $stmt->close();
}

// Inizializza array per le statistiche
$tf2StatsArray = [];
$cs2StatsArray = [];
$userDetails = []; // Array per memorizzare dettagli utente

// Recupera gli Steam ID e altre informazioni dal database
if ($result->num_rows > 0) {
    // echo "<h2>Steam ID degli utenti nel database:</h2><ul>";

    while ($row = $result->fetch_assoc()) {
        $steamID = $row['steamID'];
        $nickname = htmlspecialchars($row['nickname']);
        $image = $row['image'];

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

        // Statistiche di Counter-Strike 2
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
        // echo "<li><strong>Nickname:</strong> {$nickname} - <strong>Steam ID:</strong> {$steamID}</li>";
    }
    echo "</ul>";
} else {
    echo "Nessun utente trovato nel database.";
}

// Funzione per generare la classifica di Team Fortress 2
function generaClassificaTF2($tf2Stats, &$userDetails)
{
    $userScores = [];
    $importantStats = ['iPointsScored'];
    $validClasses = ['Scout', 'Soldier', 'Pyro', 'Demoman', 'Heavy', 'Engineer', 'Medic', 'Sniper', 'Spy'];

    foreach ($tf2Stats as $steamID => $stats) {
        foreach ($stats as $stat) {
            foreach ($importantStats as $importantStat) {
                if (strpos($stat['name'], $importantStat) !== false) {
                    $parts = explode('.', $stat['name']);
                    if (count($parts) === 3) {
                        $className = $parts[0];
                        if (in_array($className, $validClasses)) {
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

    // Aggiorna i punteggi degli utenti
    foreach ($userScores as $steamID => $score) {
        if (isset($userDetails[$steamID])) {
            $userDetails[$steamID]['tf2Score'] = $score;
        }
    }

    arsort($userScores); // Ordina i punteggi degli utenti in modo decrescente
    return $userScores;
}

// Funzione per generare la classifica di Counter-Strike 2
function generaClassificaCSGO($gameStatsArray, &$userDetails)
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
            $classifica[$steamID] = [
                'nickname' => $userDetails[$steamID]['nickname'],
                'steamID' => $steamID,
                'win_percentage' => $winPercentage
            ];
        }
    }

    // Ordina la classifica in base alla percentuale di vittorie
    usort($classifica, function ($a, $b) {
        return $b['win_percentage'] <=> $a['win_percentage'];
    });

    return $classifica;
}

// Genera le classifiche per TF2 e CS2
$tf2Classifica = generaClassificaTF2($tf2StatsArray, $userDetails);
$cs2Classifica = generaClassificaCSGO($cs2StatsArray, $userDetails);

// Inserisci i dati nella tabella di classifica di Team Fortress 2
foreach ($tf2Classifica as $steamID => $score) {
    $nickname = htmlspecialchars($userDetails[$steamID]['nickname']);
    insertIntoClassifica($conn, 'tf2_classifica', $nickname, $steamID, $score);
}

// Inserisci i dati nella tabella di classifica di Counter-Strike 2
foreach ($cs2Classifica as $user) {
    $steamID = $user['steamID'];
    $nickname = htmlspecialchars($userDetails[$steamID]['nickname']);
    $winPercentage = $user['win_percentage'];
    insertIntoClassifica($conn, 'cs2_classifica', $nickname, $steamID, $winPercentage);
}

// Chiudi la connessione al database
$conn->close();
?>


<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="styleHome.css">
    <script src="scriptHome.js" defer></script>
    <script src="scriptYT.js" defer></script>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>Home</title>
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
                                <!-- TODO: aggiungere href per arrivare alle pagine dei giochi-->
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">Valorant</a></li>
                                <li><a class="dropdown-item" href="../Lol/lol.php" style="color: var(--brand_color);">League of Legends</a></li>
                                <!-- <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item" href="#">Something else here</a></li> 
                                 Possono sempre servire -->
                            </ul>
                        </li>
                        <li class="nav-item" style="margin-left: 7px; margin-top: 11px;">
                            <button id="ui-switch">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M480-120q-150 0-255-105T120-480q0-150 105-255t255-105q14 0 27.5 1t26.5 3q-41 29-65.5 75.5T444-660q0 90 63 153t153 63q55 0 101-24.5t75-65.5q2 13 3 26.5t1 27.5q0 150-105 255T480-120Z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M480-280q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480q0 83-58.5 141.5T480-280ZM200-440H40v-80h160v80Zm720 0H760v-80h160v80ZM440-760v-160h80v160h-80Zm0 720v-160h80v160h-80ZM256-650l-101-97 57-59 96 100-52 56Zm492 496-97-101 53-55 101 97-57 59Zm-98-550 97-101 59 57-100 96-56-52ZM154-212l101-97 55 53-97 101-59-57Z" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                    <!-- TODO: modificare href e vari dettagli del signup e login-->
                    <ul class="navbar-nav align-items-center mb-2 mb-lg-0">
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
        <div
            style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 70px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Game rankings</p>
        </div>
        <div class="d-flex justify-content-center" style="margin-top: 5rem; margin-bottom: 5rem; height: auto;">
            <div class="row">
                <div class="col">
                    <div class="card" style="width: 30rem; background-color: var(--object_color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <img src="../assets/valogo.webp" class="card-img-top" alt="valLogo" style="width: 50px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">Valorant Top Winners</h5>
                            </div>
                            <div class="text-center mt-3">
                                <h2 class="mb-4" style="color: var(--text_color)">Classifica</h2>
                                <ul class="list-group">
                                    <?php foreach ($tf2Classifica as $steamID => $totalScore): ?>
                                        <?php
                                        // Get the nickname from userDetails using the steamID
                                        $nickname = isset($userDetails[$steamID]) ? htmlspecialchars($userDetails[$steamID]['nickname']) : 'Sconosciuto';
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <strong><?php echo $nickname; ?> (Steam ID: <?php echo htmlspecialchars($steamID); ?>):</strong>
                                            <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($totalScore); ?> punti</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                <p class="card-text" style="color: var(--text_color)"></p>
                            </div>

                            <br>
                            <div class="d-flex justify-content-center">
                                <a href="#" class="btn btn-block"
                                    style="background-color: var(--button_col); color: var(--btnTxt_col);">Valorant Page</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 30rem; background-color: var(--object_color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <img src="../assets/lollogo.webp" class="card-img-top" alt="lolLogo" style="width: 50px; height: auto; margin-right: 10px;">
                                <h5 class="card-title text-center" style="color: var(--text_color)">LoL Top Winners</h5>
                            </div>
                            <div class="d-flex flex-column align-items-center">
                                <p class="card-text" style="color: var(--text_color); text-align: center;">
                                </p>
                                <ul class="list-group text-center" style="width: 100%; max-width: 400px; color: var(--text_color);">
                                    <?php
                                    // Check if cs2Classifica has elements and then iterate
                                    if (!empty($cs2Classifica)) {
                                        // Sort the leaderboard by percentage in descending order
                                        foreach ($cs2Classifica as $user) {
                                            // Extract nickname and win percentage from user
                                            $nickname = htmlspecialchars($user['nickname']);
                                            $winPercentage = htmlspecialchars($user['win_percentage']);

                                            // Print each user in the list
                                            echo "<li class='list-group-item d-flex justify-content-between align-items-center' style='background-color: rgba(255, 255, 255, 0.1);'>
                        <span>Nickname: {$nickname}</span>
                        <span>Steam ID: " . htmlspecialchars($user['steamID']) . "</span>
                        <span class='badge bg-primary rounded-pill'>{$winPercentage}%</span>
                      </li>";
                                        }
                                    } else {
                                        // If the leaderboard is empty, show a message
                                        echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>Nessun vincitore trovato</li>";
                                    }
                                    ?>
                                </ul>
                            </div>

                            <br>
                            <div class="d-flex justify-content-center">
                                <a href="#" class="btn btn-block"
                                    style="background-color: var(--button_col); color: var(--btnTxt_col);">LoL Page</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div style="background-color: var(--transparent_col); height: auto; padding: 1rem;">
            <p style="display: flex; justify-content: center; align-items: center; color: var(--text_color);">
                <b style="font-size: 2rem;">Game infos</b>
            </p>
            <p style="display: flex; justify-content: center; align-items: center; font-size: 1.5rem; color: var(--text_color);">
                <b>Valorant</b> <br>
            </p>
            <p style="font-size: 1.5rem; margin-left: 10px; margin-right: 10px; color: var(--text_color);">
                <b>What is Valorant? </b>
                Valorant is a free-to-play first-person shooter developed by Riot Games, the same creators of League of
                Legends.
                It was released on June 2, 2020, for PC.
                The game is a tactical <b>FPS</b> that combines classic FPS game mechanics with unique skill elements,
                taking cues from titles like Counter-Strike: Global Offensive and Overwatch.
                It requires strategy, good team coordination and a good mastery of the agents' abilities to win matches,
                so stay focused and collaborate with your teammates, even if you're in fill lobbies!
                Set in a futuristic world, it also has backstories relating to the agents and the maps that can be
                discovered via the agents' voicelines or objects present in the maps.
                <br>
                <b>Game modes: </b>
                Valorant offers various game modes, but the main one is the competitive <b>5v5 mode</b>, where two teams
                face each other: an attacking team and a defensive one.
                The attackers' goal is to plant a "spike" and make it explode, while the defenders must prevent this
                from happening or defuse the spike if it is planted.
                The match takes place over 25 rounds, and the first team to win 13 rounds wins the match.
                <br>
                <b>Agents: </b>
                In Valorant, players choose from various agents, each with unique abilities divided into four main
                categories:<br>
            <ul style="list-style-type: disc; margin-left: 40px; font-size: 1.5rem; margin-top: -10px; color: var(--text_color);">
                <li><b>Duelists:</b> focused on direct combat.</li>
                <li><b>Initiator:</b> set up the ground for the attack.</li>
                <li><b>Controller:</b> manipulate the space to block the enemy.</li>
                <li><b>Sentinel:</b> defenders and supports for the team.</li>
            </ul>
            </p>
            <p style="display: flex; justify-content: center; align-items: center; font-size: 1.5rem; color: var(--text_color);">
                <b>League of Legends</b> <br>
            </p>
            <p style="font-size: 1.5rem; margin-left: 10px; margin-right: 10px; color: var(--text_color);">
                <b>What is LoL? </b>
                League of Legends (LoL) is a <b>MOBA</b> (Multiplayer Online Battle Arena) developed and published by
                <b>Riot Games</b>.
                Launched in 2009, it is one of the most popular competitive games in the world, with a huge community
                and a high-level international esports circuit.
                In LoL, two teams of five players compete in an arena, aiming to destroy the enemy base, the Nexus,
                through strategies, skills, and teamwork.
                <br>
                <b>Game modes: </b>
                The main game mode of League of Legends is <b>Summoner's Rift</b>, a symmetrical map divided into three
                lanes (top, mid, bot) and a jungle.
                In each match, teams work to gain resources, such as gold and experience, by killing minions, neutral
                monsters, and enemy champions. The team that destroys the enemy Nexus wins.
                In addition to Summoner's Rift, there are other game modes like ARAM (All Random All Mid), which is
                faster-paced and takes place on a single lane.
                <br>
                <b>Champions: </b>
                In LoL, players choose from over 160 champions, each with unique abilities, specific roles, and distinct
                playstyles. Champions are divided into several categories:
            <ul style="list-style-type: disc; margin-left: 40px; font-size: 1.5rem; margin-top: -10px; color: var(--text_color);">
                <li><b>Assassins:</b> High burst damage, specialized in rapidly eliminating enemies (e.g. Zed,
                    Katarina).</li>
                <li><b>Mages:</b> Strong magical power, often focused on dealing damage from a distance and controlling
                    crowds (e.g. Lux, Syndra).</li>
                <li><b>Marksmen:</b> Physical damage from a distance, usually the primary target in teamfights (e.g.
                    Jinx, Caitlyn).</li>
                <li><b>Tanks:</b> High resistance, capable of protecting the team by absorbing damage (e.g. Malphite,
                    Sion).</li>
                <li><b>Supports:</b> Provide healing, shields, and crowd control, supporting their team members (e.g.
                    Thresh, Lulu).</li>
            </ul>
            <br>

            <p style="display: flex; justify-content: center; align-items: center; color: var(--text_color);">
                <b style="font-size: 2rem;">Tutorials</b>
            </p>

            <!-- Player per Youtube player API -->
            <div style="display: flex; justify-content: center; align-items: center; padding: 3rem;">
                <div id="player2" style="margin-right: 5rem;"></div>
                <div id="player1"></div>
            </div>
            </p>
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