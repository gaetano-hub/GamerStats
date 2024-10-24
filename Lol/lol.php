<?php
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
    <title>LOL</title>
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
                        <li class="nav-item dropdown" style="margin-left: 5px;">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false" style="color: var(--navbar_textCol);">
                                Games
                            </a>
                            <ul class="dropdown-menu" style="background-color: var(--object_color);">
                                <!-- TODO: aggiungere href per arrivare alle pagine dei giochi-->
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">Valorant</a></li>
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">League of Legends</a></li>
                                <!-- <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item" href="#">Something else here</a></li> 
                                 Possono sempre servire -->
                            </ul>
                        </li>
                        <li class="nav-item" style="margin-left: 7px; margin-top: 11px;">
                            <a class="btn btn-outline-success" id="homeref" type="button" style=" background-color:var(--object_color);" href="../home/home.html">
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
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="../login/login.html" style="color: var(--brand_color);">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="../signUp/signUp.html" style="color: var(--brand_color);">Sign Up</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div
            style="background-color: var(--transparent_col); height: 5rem; display: flex; justify-content: center; align-items: center; margin-top: 68px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">LoL page</p>
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
                                <p class="text-center" style="color: white">Viewers: <?php echo $stream['viewer_count']; ?></p>
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
                                <p class="card-text" style="color: var(--text_color)">Boh qui non so come inserire i top winner ma lo scopriremo solo
                                    vivendo
                                    no?</p>
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
                                <p class="card-text" style="color: var(--text_color)">Boh qui non so come inserire i top winner ma lo scopriremo solo
                                    vivendo
                                    no?</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>
