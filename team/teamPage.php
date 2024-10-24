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
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <!-- TODO: modificare href e vari dettagli del signup e login-->
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

        <img src="<?php echo ($game == "LoL") ? "../assets/lollogo.webp" : "../assets/valogo.webp" ?>" class="card-img-top" alt="lolLogo" style="width: 50px; height: auto; margin-right: 10px;">
        <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);"><?php echo $visitingTeam ?></p>
    </div>
    <div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
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
                        $row[] = $member;
                        if (count($row) == 3) {
                            foreach ($row as $member) {
                                echo '<div class="col">
                                        <div class="member-circle mx-auto" style="height: 100px; width: 100px;"></div>
                                        <p style="color: var(--text_color); text-align: center;">' . $member . '</p>
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
                                <div class="member-circle mx-auto" style="height: 100px; width: 100px;"></div>
                                <p style="color: var(--text_color); text-align: center;">' . $member . '</p>
                            </div>';
                    }
                }
                ?>
            </div>
        </div>

    </div>
    <div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
        <p style="color: var(--text_color); margin-left: 2rem;">
            <b style="font-size: 2rem;">Stats</b>
        </p>
        <div>
            <p style="color: var(--text_color); margin-left: 2rem;"> Qui inseriamo le statistiche ye</p>
        </div>
    </div>
    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>

</html>