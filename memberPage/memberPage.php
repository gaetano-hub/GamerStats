<?php
session_start();
if (isset($_GET['user']) && !empty($_GET['user'])) {
    // Preleviamo il valore del nickname dall'URL
    $visitingUser = htmlspecialchars($_GET['user']);
}
// Converti l'array di sessione in formato JSON
$sessionData = json_encode($_SESSION);
// Controlla se l'utente è loggato con Discord
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
$stmt = $conn->prepare("SELECT team_name FROM teams WHERE member_one = ? OR member_two = ? OR member_three = ? OR member_four = ? OR member_five = ? OR leader = ?");
$stmt->bind_param("ssssss", $visitingUser, $visitingUser, $visitingUser, $visitingUser, $visitingUser, $visitingUser);
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
    <link rel="stylesheet" href="styleTeam.css">
    <script src="../home/scriptHome.js" defer></script>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>Team page</title>
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
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">Valorant</a></li>
                                <li><a class="dropdown-item" href="../Lol/lol.php" style="color: var(--brand_color);">League of Legends</a></li>
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
                    <ul class="navbar-nav mb-2 mb-lg-0">
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
                        <div class="row justify-content-center text-center">
                            <?php
                            $query = "SELECT image FROM users WHERE nickname = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("s", $visitingUser);
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
                    </div>
                    <input type="text" class="form-control-plaintext text-center" value="<?php echo $visitingUser ?>" readonly style="color: var(--text_color); margin-top: 10px; font-size: 2rem; font-weight: bold;">
                </div>
                <div class="col d-flex flex-column align-items-center" style="max-width: 2px;">
                    <hr style="width: 2px; border-width:0; background-color: var(--text_color); height: 90%; max-height: 90%;">
                </div>
                <div class="col d-flex flex-column align-items-center">
                    <p style="color: var(--text_color); font-size: 2rem; font-weight: bold;">Teams</p>
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
                                        <a href="../team/teamPage.php?team=<?php echo $teamName; ?>" class="btn" style="background-color: var(--object_color); color: var(--text_color); border-color: var(--text_color);">Goto Team</a>
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
            <p style="font-weight: bold; color: var(--text_color);">Qui ci vanno le statistiche SIUUUUUUUUUU</p>
        </div>
    </div>
</body>

</html>