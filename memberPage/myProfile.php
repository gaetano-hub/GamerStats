<?php

//TODO PER ANDREA: RICORDATI DI FARE IL CONTROLLO SUI NICKNAME DEGLI UTENTI (VEDI TEAM.PHP)

session_start();
// $_SESSION['nickname'] = 'testuser';

// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);
// Controlla se l'utente è loggato con Discord o normalmente
if (!isset($_SESSION['discord_user']) && !isset($_SESSION['nickname'])) {
    header("Location: ../login/login.html");
}

$baseUrl = "http://localhost/GamerStats/memberPage/memberPage.php?user=";  // Modifica l'URL con il percorso della tua pagina
$link = $baseUrl . urlencode($_SESSION['nickname']);

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

// Crea la tabella 'teams' se non esiste
$createTeamsTable = "CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(255) NOT NULL,
    game VARCHAR(255) NOT NULL,
    member_one VARCHAR(255),
    member_two VARCHAR(255),
    member_three VARCHAR(255),
    member_four VARCHAR(255),
    member_five VARCHAR(255),
    leader VARCHAR(255) NOT NULL
)";

if ($conn->query($createTeamsTable) === TRUE) {
    echo "Tabella teams creata o già esistente.";
} else {
    echo "Errore nella creazione della tabella teams: " . $conn->error;
}

// Crea la tabella se non esiste
$sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nickname` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `steamID` VARCHAR(20) NOT NULL UNIQUE,
    `image` VARCHAR(255) DEFAULT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabella 'users' creata con successo o già esistente.";
} else {
    echo "Errore nella creazione della tabella: " . $conn->error;
}

// Prepara la query per ottenere i nomi dei team
$stmt = $conn->prepare("SELECT team_name FROM teams WHERE member_one = ? OR member_two = ? OR member_three = ? OR member_four = ? OR member_five = ? OR leader = ?");
$stmt->bind_param("ssssss", $nickname, $nickname, $nickname, $nickname, $nickname, $nickname);
$stmt->execute();
$result = $stmt->get_result();

$teamNames = array();
while ($row = $result->fetch_assoc()) {
    $teamNames[] = $row['team_name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../home/styleHome.css">
    <script src="../home/scriptHome.js" defer></script>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>My Profile Page</title>
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

        <div class="container text-center" style="margin-top: 100px; background-color: var(--transparent_col); padding: 15px;">
            <div class="row">
                <div class="col d-flex flex-column align-items-center">
                    <div class="container text-center" style="background-color: rgba(0,0,0,0);">
                        <div class="row">
                            <div style="display: flex; justify-content: center; align-items: center;">
                                <div class="row">
                                    <?php
                                    $query = "SELECT image FROM users WHERE nickname = ?";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("s", $_SESSION['nickname']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows > 0) {
                                        $row = $result->fetch_assoc();
                                        if (isset($row['image']) && file_exists($row['image'])) {
                                            echo '<img src="' . $row['image'] . '" class="img-thumbnail" alt="profilePicture" style="width: 200px; height: 200px; border-color: var(--object_color); position: relative;">';
                                        } else {
                                            echo '<img src=../assets/profPicture.jpg class="img-thumbnail" alt="profilePicture" style="width: 200px; height: 200px; position: relative;">';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="row">
                                    <button class="btn" id="edit" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdit" aria-expanded="false" aria-controls="collapseEdit" style="background-color: rgba(0,0,0,0); z-index: 2; margin-bottom: 12rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                                            <path d="M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                        </svg>
                                    </button>
                                    <div class="collapse" id="collapseEdit" style="position: absolute; z-index: 1; max-width: 20rem;">
                                        <div class="card card-body" style="background-color: var(--object_color); color: var(--text_color);">
                                            <h5>Edit your profile picture here:</h5>

                                            <form action="" method="post" enctype="multipart/form-data">
                                                <div class="form-group">
                                                    <input type="file" name="profileImage" id="profileImage" class="form-control" accept="image/*" required style="background-color: var(--object_color); color: var(--text_color);">
                                                </div>
                                                <button type="submit" class="btn btn-success mt-1">Upload</button>
                                            </form>
                                            <?php
                                            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profileImage'])) {
                                                $target_dir = "../assets/profileImages/";
                                                $target_file = $target_dir . basename($_FILES["profileImage"]["name"]);
                                                $uploadOk = 1;
                                                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                                                if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                                                    echo "Spiacente, solo i formati JPG, PNG e JPEG sono permessi.";
                                                    $uploadOk = 0;
                                                }

                                                if ($uploadOk == 0) {
                                                    echo "Spiacente, il file non è stato caricato.";
                                                } else {
                                                    if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file)) {
                                                        $sql = "UPDATE users SET image=? WHERE nickname=?";
                                                        $stmt = $conn->prepare($sql);
                                                        $stmt->bind_param("ss", $target_file, $_SESSION['nickname']);
                                                        if ($stmt->execute()) {
                                                            echo "Immagine profilo aggiornata con successo.";
                                                        } else {
                                                            echo "Errore durante l'aggiornamento dell'immagine: " . $conn->error;
                                                        }
                                                    } else {
                                                        echo "Spiacente, c'è stato un errore durante il caricamento del file.";
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="text" class="form-control-plaintext text-center" value="<?php echo isset($_SESSION['nickname']) ? $_SESSION['nickname'] : ''; ?>" readonly style="color: var(--text_color); margin-top: 10px; font-size: 2rem; font-weight: bold;">
                    <input type="text" class="form-control-plaintext text-center" value="<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>" readonly style="color: var(--text_color); margin-top: -10px; font-size: 1rem;">
                    <div class="row">
                        <!-- Il link personalizzato -->
                        <span id="memberLink" style="visibility: hidden;"><?php echo $link; ?></span>
                        <div id="liveAlertPlaceholder"></div>
                        <div class="col">
                            <a type="button" class="btn" style="background-color: var(--object_color); color: var(--text_color);"
                                href="link_steam/redirect_to_steam.php">Steam</a>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-success" id="liveAlertBtn" style="color: var(--text_color);">Share</button>
                            <script>
                                const alertPlaceholder = document.getElementById('liveAlertPlaceholder')
                                const appendAlert = (message1, message2, type) => {
                                    const wrapper = document.createElement('div')
                                    wrapper.innerHTML = [
                                        `<div class="alert alert-${type} alert-dismissible" role="alert">`,
                                        `   <div>${message1} <br> ${message2}</div>`,
                                        '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
                                        '</div>'
                                    ].join('')

                                    alertPlaceholder.append(wrapper)
                                }
                                const linkUser = document.getElementById('memberLink').textContent
                                const alertTrigger = document.getElementById('liveAlertBtn')
                                if (alertTrigger) {
                                    alertTrigger.addEventListener('click', () => {
                                        appendAlert('Copy this link:', linkUser, 'success')
                                    })
                                }
                            </script>
                        </div>
                    </div>
                </div>
                <div class="col d-flex flex-column align-items-center" style="max-width: 2px;">
                    <hr style="width: 2px; border-width:0; background-color: var(--text_color); height: 90%; max-height: 90%;">
                </div>
                <div class="col d-flex flex-column align-items-center">
                    <p style="color: var(--text_color); font-size: 2rem; font-weight: bold;">Teams</p>

                    <button class="btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample" style="background-color: var(--object_color); color: var(--text_color); border-color: var(--text_color); margin-bottom:10px;">
                        Create new team
                    </button>
                    <div class="collapse" id="collapseExample" style="margin-bottom: 20px;">
                        <div class="card card-body" id="cardTeam" style="background-color: var(--object_color); color: var(--text_color);">
                            <form action="../team/team.php" method="post">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="teamName" name="teamName" placeholder="teamName" style="height: 100%; margin-bottom: 5px; background-color: var(--object_color); color: var(--text_color);">
                                    <label for="teamName">Team Name</label>
                                </div>
                                <select class="form-select" aria-label="Default select example" name="game" style="margin-bottom: 5px; height: 3.5rem; background-color: var(--object_color); color: var(--text_color);">
                                    <option selected>Game</option>
                                    <option value="Csgo">Csgo</option>
                                    <option value="Team Fortress 2">Team Fortress 2</option>
                                    <option value="Dota2">Dota2</option>
                                </select>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="memberOne" name="memberOne" placeholder="memberOne" style="height: 30%; margin-bottom: 5px; background-color: var(--object_color); color: var(--text_color);">
                                            <label for="teamName">Member 1</label>
                                        </div>
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="memberThree" name="memberThree" placeholder="memberThree" style="height: 30%; margin-bottom: 5px; background-color: var(--object_color); color: var(--text_color);">
                                            <label for="teamName">Member 3</label>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="memberTwo" name="memberTwo" placeholder="memberTwo" style="height: 30%; margin-bottom: 5px; background-color: var(--object_color); color: var(--text_color);">
                                            <label for="teamName">Member 2</label>
                                        </div>
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="memberFour" name="memberFour" placeholder="memberFour" style="height: 30%; margin-bottom: 5px; background-color: var(--object_color); color: var(--text_color);">
                                            <label for="teamName">Member 4</label>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: center;">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="memberFive" name="memberFive" placeholder="memberFive" style="height: 30%; margin-bottom: 5px; background-color: var(--object_color); color: var(--text_color);">
                                        <label for="teamName">Member 5</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <button type="button" class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample" style="height: 3.5rem; width: 10rem;" onclick="
                                            document.getElementById('teamName').value = '';
                                            document.getElementById('memberOne').value = '';
                                            document.getElementById('memberTwo').value = '';
                                            document.getElementById('memberThree').value = '';
                                            document.getElementById('memberFour').value = '';
                                            document.getElementById('memberFive').value = '';
                                            document.querySelector('.form-select').selectedIndex = 0;">
                                            Cancel
                                        </button>
                                    </div>
                                    <div class="col">
                                        <button type="submit" class="btn btn-success" style="height: 3.5rem; width: 10rem;">Create</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="accordion" id="accordionExample" style="width: 100%;">
                        <?php foreach ($teamNames as $index => $teamName) { ?>
                            <div class="accordion-item" style="background-color: var(--transparent_col); color: var(--text_color);">
                                <h2 class="accordion-header" style="color: var(--text_color);">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="true" aria-controls="collapse<?php echo $index; ?>" style="background-color: rgba(0,0,0,0); color: var(--text_color); font-weight: bold;">
                                        <?php echo $teamName; ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionExample" style="color: var(--text_color);">
                                    <div class="accordion-body">
                                        <?php
                                        $stmt = $conn->prepare("SELECT member_one, member_two, member_three, member_four, member_five, leader FROM teams WHERE team_name = ?");
                                        $stmt->bind_param("s", $teamName);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $row = $result->fetch_assoc();
                                        ?>
                                        <p><b>Leader: </b><?php echo $row['leader']; ?></p>
                                        <p><?php echo $row['member_one']; ?></p>
                                        <p><?php echo $row['member_two']; ?></p>
                                        <p><?php echo $row['member_three']; ?></p>
                                        <p><?php echo $row['member_four']; ?></p>
                                        <p><?php echo $row['member_five']; ?></p>
                                        <a href="../team/teamPage.php?team=<?php echo urlencode($teamName); ?>" class="btn" style="background-color: var(--object_color); color: var(--text_color); border-color: var(--text_color);">Goto Team</a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Stats</p>
            <?php
            $steamID = $_SESSION['steamID'] ?? null; // Utilizza null coalescing per evitare errori
            $apiKey = '8A345C81E607D2E02274B11D4834675A'; // Inserisci la tua chiave API di Steam se necessario

            // Game IDs
            $cs2GameId = 730; // Counter-Strike 2
            $tf2GameId = 440; // Team Fortress 2

            // Funzione per ottenere le statistiche del gioco
            function getGameStats($steamID, $apiKey, $gameId)
            {
                $url = "https://api.steampowered.com/ISteamUserStats/GetUserStatsForGame/v0002/?appid={$gameId}&steamid={$steamID}&key={$apiKey}";
                $response = file_get_contents($url);
                return json_decode($response, true);
            }

            // Verifica se lo Steam ID è impostato
            if ($steamID) {
                // Ottieni statistiche per Counter-Strike 2
                $cs2Stats = getGameStats($steamID, $apiKey, $cs2GameId);

                // Prepara i dati per la visualizzazione
                $statsArray = [];
                if (isset($cs2Stats['playerstats']['stats'])) {
                    foreach ($cs2Stats['playerstats']['stats'] as $stat) {
                        $statsArray[$stat['name']] = $stat['value'];
                    }
                } else {
                    echo "<p>Statistiche non disponibili per Counter-Strike 2.</p>";
                    exit; // Esci per evitare ulteriori elaborazioni
                }

                // Statistiche totali
                $totalKills = $statsArray['total_kills'] ?? 0;
                $totalDeaths = $statsArray['total_deaths'] ?? 0;
                $totalWins = $statsArray['total_matches_won'] ?? 0;
                $totalMatchesPlayed = $statsArray['total_matches_played'] ?? 0;
                $totalRoundsPlayed = $statsArray['total_rounds_played'] ?? 1; // Prevenire divisione per zero

                // Statistiche ultima partita
                $lastMatchKills = $statsArray['last_match_kills'] ?? 0;
                $lastMatchDeaths = $statsArray['last_match_deaths'] ?? 0;
                $lastMatchWins = $statsArray['last_match_wins'] ?? 0;

                // Calcola i rapporti
                $killDeathRatio = ($totalDeaths > 0) ? ($totalKills / $totalDeaths) : 0;
                $winLossRatio = ($totalMatchesPlayed > 0) ? ($totalWins / $totalMatchesPlayed) : 0;

                // Calcola le statistiche per round
                $killsPerRound = $totalRoundsPlayed > 0 ? $totalKills / $totalRoundsPlayed : 0;
                $deathsPerRound = $totalRoundsPlayed > 0 ? $totalDeaths / $totalRoundsPlayed : 0;

                // Display statistics
            ?>
                <div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
                    <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Statistiche di Counter-Strike 2</p>

                    <!-- Grafici -->
                    <div class="charts-grid">
                        <canvas id="winRatioChart" width="300" height="150"></canvas>
                        <canvas id="killRatioChart" width="300" height="150"></canvas>
                    </div>

                    <!-- Statistiche dettagliate -->
                    <div class="stats-details" style="margin-top: 20px;">
                        <h3>Statistiche Totali</h3>
                        <div class="stats-grid">
                            <div class="stats-item"><strong>Total Kills:</strong> <span><?php echo $totalKills; ?></span></div>
                            <div class="stats-item"><strong>Total Deaths:</strong> <span><?php echo $totalDeaths; ?></span></div>
                            <div class="stats-item"><strong>Total Wins:</strong> <span><?php echo $totalWins; ?></span></div>
                            <div class="stats-item"><strong>Total Matches Played:</strong> <span><?php echo $totalMatchesPlayed; ?></span></div>
                            <div class="stats-item"><strong>Total Rounds Played:</strong> <span><?php echo $totalRoundsPlayed; ?></span></div>
                            <div class="stats-item"><strong>Kills Per Round:</strong> <span><?php echo number_format($killsPerRound, 2); ?></span></div>
                            <div class="stats-item"><strong>Deaths Per Round:</strong> <span><?php echo number_format($deathsPerRound, 2); ?></span></div>
                        </div>

                        <h3>Statistiche Ultima Partita</h3>
                        <div class="stats-grid">
                            <div class="stats-item"><strong>Last Match Kills:</strong> <span><?php echo $lastMatchKills; ?></span></div>
                            <div class="stats-item"><strong>Last Match Deaths:</strong> <span><?php echo $lastMatchDeaths; ?></span></div>
                            <div class="stats-item"><strong>Last Match Wins:</strong> <span><?php echo $lastMatchWins; ?></span></div>
                        </div>

                        <h3>Rapporti</h3>
                        <div class="stats-grid">
                            <div class="stats-item"><strong>Kill/Death Ratio:</strong> <span><?php echo number_format($killDeathRatio, 2); ?></span></div>
                            <div class="stats-item"><strong>Win/Loss Ratio:</strong> <span><?php echo number_format($winLossRatio, 2); ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Include Chart.js -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <!-- Script to generate charts -->
                <script>
                    // Preparing data for the charts
                    const totalWins = <?php echo $totalWins; ?>;
                    const totalMatchesPlayed = <?php echo $totalMatchesPlayed; ?>;
                    const totalKills = <?php echo $totalKills; ?>;
                    const totalDeaths = <?php echo $totalDeaths; ?>;

                    // Win Ratio Chart
                    const winRatioChart = new Chart(document.getElementById('winRatioChart').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Wins', 'Losses'],
                            datasets: [{
                                data: [totalWins, totalMatchesPlayed - totalWins],
                                backgroundColor: ['#4caf50', '#f44336'],
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(tooltipItem) {
                                            const value = tooltipItem.raw;
                                            const total = totalMatchesPlayed;
                                            const percentage = total ? ((value / total) * 100).toFixed(2) : 0;
                                            return tooltipItem.label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // Kill Ratio Chart
                    const killRatioChart = new Chart(document.getElementById('killRatioChart').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Kills', 'Deaths'],
                            datasets: [{
                                data: [totalKills, totalDeaths],
                                backgroundColor: ['#36a2eb', '#ff6384'],
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(tooltipItem) {
                                            const value = tooltipItem.raw;
                                            const total = totalKills + totalDeaths;
                                            const percentage = total ? ((value / total) * 100).toFixed(2) : 0;
                                            return tooltipItem.label + ': ' + value + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                </script>

                <!-- CSS Styling -->
                <style>
                    .charts-grid {
                        display: flex;
                        justify-content: space-around;
                        padding: 20px;
                    }

                    canvas {
                        max-width: 300px;
                        /* Riduci la larghezza del canvas */
                        max-height: 150px;
                        /* Riduci l'altezza del canvas */
                    }

                    .stats-details {
                        text-align: left;
                        margin-top: 20px;
                    }

                    .stats-details h3 {
                        margin-bottom: 10px;
                        font-size: 1.5rem;
                        color: var(--text_color);
                        border-bottom: 2px solid var(--text_color);
                        padding-bottom: 5px;
                    }

                    .stats-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                        gap: 15px;
                        margin-top: 10px;
                    }

                    .stats-item {
                        background-color: rgba(0, 0, 0, 0.1);
                        padding: 10px;
                        border-radius: 5px;
                    }

                    .stats-item strong {
                        color: var(--text_color);
                    }
                </style>
                <?php
                // Ottieni statistiche per Team Fortress 2
                $tf2Stats = getGameStats($steamID, $apiKey, $tf2GameId);



                if (isset($tf2Stats['playerstats']['stats'])) {
                    echo '<div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">';
                    echo '<p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Statistiche di Team Fortress 2</p>';

                    // Array per memorizzare le somme delle statistiche per ogni classe
                    $classStats = [];

                    // Classi specifiche da considerare
                    $validClasses = [
                        'Scout',
                        'Soldier',
                        'Pyro',
                        'Demoman',
                        'Heavy',
                        'Engineer',
                        'Medic',
                        'Sniper',
                        'Spy'
                    ];

                    // Array delle statistiche significative
                    $importantStats = [
                        'iNumberOfKills',
                        'iDamageDealt',
                        'iPlayTime',
                        'iPointCaptures',
                        'iKillAssists',
                        'iBuildingsDestroyed'
                    ];

                    // Loop attraverso le statistiche
                    foreach ($tf2Stats['playerstats']['stats'] as $stat) {
                        foreach ($importantStats as $importantStat) {
                            if (strpos($stat['name'], $importantStat) !== false) {
                                // Estrai il nome della classe (es. Scout, Soldier, ecc.)
                                $className = strtok($stat['name'], '.'); // Prende la parte prima del punto
                                // Controlla se la classe è una delle classi valide
                                if (in_array($className, $validClasses)) {
                                    // Assicurati che l'array della classe esista
                                    if (!isset($classStats[$className])) {
                                        $classStats[$className] = [
                                            'iNumberOfKills' => 0,
                                            'iDamageDealt' => 0,
                                            'iPlayTime' => 0,
                                            'iPointCaptures' => 0,
                                            'iKillAssists' => 0,
                                            'iBuildingsDestroyed' => 0,
                                        ];
                                    }
                                    // Somma i valori
                                    $classStats[$className][$importantStat] += $stat['value'];
                                }
                            }
                        }
                    }

                    // Mostra i risultati delle somme per ogni classe
                    echo '<div class="row" style="justify-content: center;">'; // Contenitore per le righe
                    foreach ($classStats as $class => $stats) {
                        echo '<div class="col-md-4" style="margin: 10px; padding: 15px; border: 1px solid #ccc; border-radius: 8px; background-color: #f9f9f9;">';
                        echo "<h4>{$class}</h4>";
                        echo '<ul style="list-style-type: none; padding: 0;">';
                        echo '<li><strong>Uccisioni:</strong> ' . $stats['iNumberOfKills'] . '</li>';
                        echo '<li><strong>Danno:</strong> ' . $stats['iDamageDealt'] . '</li>';
                        echo '<li><strong>Tempo di Gioco:</strong> ' . $stats['iPlayTime'] . ' minuti</li>';
                        echo '<li><strong>Catture Punti:</strong> ' . $stats['iPointCaptures'] . '</li>';
                        echo '<li><strong>Assistenza Uccisioni:</strong> ' . $stats['iKillAssists'] . '</li>';
                        echo '<li><strong>Edifici Distrutti:</strong> ' . $stats['iBuildingsDestroyed'] . '</li>';
                        echo '</ul>';

                        // Creare un canvas per il grafico
                        echo '<canvas id="chart_' . $class . '" style="width: 100%; height: 300px;"></canvas>';
                        echo '<script>';
                        echo 'var ctx = document.getElementById("chart_' . $class . '").getContext("2d");';
                        echo 'var chart = new Chart(ctx, {';
                        echo 'type: "bar",'; // Tipo di grafico
                        echo 'data: {';
                        echo 'labels: ["Uccisioni", "Danno", "Tempo di Gioco", "Catture Punti", "Assistenza Uccisioni", "Edifici Distrutti"],';
                        echo 'datasets: [{';
                        echo 'label: "Statistiche ' . $class . '",';
                        echo 'data: [' .
                            $stats['iNumberOfKills'] . ',' .
                            $stats['iDamageDealt'] . ',' .
                            $stats['iPlayTime'] . ',' .
                            $stats['iPointCaptures'] . ',' .
                            $stats['iKillAssists'] . ',' .
                            $stats['iBuildingsDestroyed'] .
                            '],';
                        echo 'backgroundColor: "rgba(75, 192, 192, 0.2)",'; // Colore di sfondo
                        echo 'borderColor: "rgba(75, 192, 192, 1)",'; // Colore bordo
                        echo 'borderWidth: 1';
                        echo '}],';
                        echo '},';
                        echo 'options: {';
                        echo 'scales: {';
                        echo 'y: {';
                        echo 'beginAtZero: true'; // Inizia l'asse y da zero
                        echo '}';
                        echo '}';
                        echo '}';
                        echo '});';
                        echo '</script>';
                        echo '</div>'; // Chiude il div della classe
                    }
                    echo '</div>'; // Chiude il contenitore delle righe

                    echo '</div>'; // Chiude il contenitore principale
                } else {
                    echo "<p>Statistiche non disponibili per Team Fortress 2.</p>";
                }
                ?>
                <!-- 
            DOTA 2 STATS
             -->

                <div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
                    <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">
                        Statistiche di Dota 2
                    <div class="charts-grid">
                        <canvas id="winRatioChartD" width="300" height="150"></canvas>
                        <canvas id="kdaChartD" width="300" height="150"></canvas>
                    </div>
                    <div class="stats-details" style="margin-top: 20px;">
                        <h3>Statistiche Totali</h3>
                        <div class="stats-grid">
                            <div class="stats-item"><strong>Total Kills:</strong> <span id="totalKillsDota2"></span></div>
                            <div class="stats-item"><strong>Total Deaths:</strong> <span id="totalDeathsDota2"></span></div>
                            <div class="stats-item"><strong>Total Wins:</strong> <span id="totalWinsDota2"></span></div>
                            <div class="stats-item"><strong>Total Losses:</strong> <span id="totalLossesDota2"></span></div>
                            <!-- <div class="stats-item"><strong>Total Matches Played:</strong> <span><?php echo $totalMatchesPlayed; ?></span></div>
                        <div class="stats-item"><strong>Total Rounds Played:</strong> <span><?php echo $totalRoundsPlayed; ?></span></div>
                        <div class="stats-item"><strong>Kills Per Round:</strong> <span><?php echo number_format($killsPerRound, 2); ?></span></div>
                        <div class="stats-item"><strong>Deaths Per Round:</strong> <span><?php echo number_format($deathsPerRound, 2); ?></span></div>
                        -->
                        </div>

                        <h3>Statistiche Ultime Partite</h3>
                        <div class="stats-grid" id="lastDota2">
                        </div>

                        <h3>Rapporti</h3>
                        <div class="stats-grid">
                            <div class="stats-item"><strong>Kill/Death Ratio:</strong> <span id="killDeathRatioDota2"></span></div>
                            <div class="stats-item"><strong>Win/Loss Ratio:</strong> <span id="winLossRatioDota2"></span></div>
                        </div>
                    </div>
                    <div id="results">
                    </div>
                    </p>
                </div>
                <style>
                    .charts-grid {
                        display: flex;
                        justify-content: space-around;
                        padding: 20px;
                    }

                    canvas {
                        max-width: 300px;
                        /* Riduci la larghezza del canvas */
                        max-height: 150px;
                        /* Riduci l'altezza del canvas */
                    }

                    .stats-details {
                        text-align: left;
                        margin-top: 20px;
                    }

                    .stats-details h3 {
                        margin-bottom: 10px;
                        font-size: 1.5rem;
                        color: var(--text_color);
                        border-bottom: 2px solid var(--text_color);
                        padding-bottom: 5px;
                    }

                    .stats-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                        gap: 15px;
                        margin-top: 10px;
                    }

                    .stats-item {
                        background-color: rgba(0, 0, 0, 0.1);
                        padding: 10px;
                        border-radius: 5px;
                    }

                    .stats-item strong {
                        color: var(--text_color);
                    }
                </style>
                <script>
                    var player_id = <?php echo json_encode($steamID); ?>;
                </script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js">
                    var steamID = "<?php echo $steamID; ?>";
                </script>
                <script src="../dota2/statcalculatorD.js"></script>


            <?php
            } else {
                echo "<li class='list-group-item text-center' style='background-color: rgba(255, 255, 255, 0.1);'>Fare accesso Steam</li>";
            }
            ?>



        </div>
    </div>

    <script>
        // Trasferisci i dati di sessione dal PHP al JavaScript
        var sessionData = <?php echo $sessionData; ?>;

        // Stampa i dati di sessione nella console del browser
        console.log(sessionData);
    </script>

    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</html>