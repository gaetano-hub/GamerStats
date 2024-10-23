<?php
session_start();
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

$teamData = array();
if ($row = $result->fetch_assoc()) {
    $teamData = $row;
    $game = $teamData['game'];
    unset($teamData['game']);
}
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
                                <!-- TODO: aggiungere href per arrivare alle pagine dei giochi-->
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">Valorant</a></li>
                                <li><a class="dropdown-item" href="../Lol/lol.php" style="color: var(--brand_color);">League of Legends</a></li>
                                <!-- <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item" href="#">Something else here</a></li> 
                                 Possono sempre servire -->
                            </ul>
                        </li>
                        <li class="nav-item" style="margin-left: 7px; margin-top: 11px;">
                            <a class="btn btn-outline-success" id="homeref" type="button" style=" background-color:var(--object_color);" href="../home/home.php">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                            </a>
                        </li>
                    </ul>
                    <!-- TODO: modificare href e vari dettagli del signup e login-->
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item">
                            <form class="d-flex" role="search">
                                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" 
                                style=" background-color:var(--object_color);">
                                <button class="btn btn-outline-success" id="search" type="submit"
                                style=" background-color:var(--object_color);">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/></svg>
                                </button>
                            </form>
                        </li>
                        <li class="separator" style="color: var(--separator_color);">|</li>
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
    <div style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">
        <!--TODO: insersici logo del gioco del team e il nome del team-->
        <img src="<?php echo ($game== "LoL") ? "../assets/lollogo.webp" : "../assets/valogo.webp" ?>" class="card-img-top" alt="lolLogo" style="width: 50px; height: auto; margin-right: 10px;">
        <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);"><?php echo $visitingTeam ?></p>
    </div>
    <p style="color: var(--text_color); margin-left: 2rem;">
        <b style="font-size: 2rem;">Team members</b>
    </p>

    <!--TODO: sono 5 a scopo dimostrativo, ma aggiungere membri da script e corona al leader-->
    <div style="width: 100vw; margin-top: 2rem; margin-bottom: 2rem; height: auto;">
        <?php
        $row = [];
        foreach ($teamData as $member) {
            if ($member != null) {
                $row[] = $member;
                if (count($row) == 3) {
                    echo '<div class="row w-100">';
                    foreach ($row as $member) {
                        echo '<div class="col">
                                <div class="member-circle mx-auto" style="height: 100px; width: 100px;"></div>
                                <p style="color: var(--text_color); text-align: center;">' . $member . '</p>
                            </div>';
                    }
                    echo '</div>';
                    $row = [];
                }
            }
        }
        if (count($row) > 0) {
            echo '<div class="row w-100">';
            foreach ($row as $member) {
                echo '<div class="col">
                        <div class="member-circle mx-auto" style="height: 100px; width: 100px;"></div>
                        <p style="color: var(--text_color); text-align: center;">' . $member . '</p>
                    </div>';
            }
            echo '</div>';
        }
        ?>
    </div>
    <p style="color: var(--text_color); margin-left: 2rem;">
        <b style="font-size: 2rem;">Stats</b>
    </p>
    <div>
        <p style="color: var(--text_color); margin-left: 2rem;"> Qui inseriamo le statistiche ye</p>
    </div>
    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
