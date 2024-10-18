<?php

require '../conf/config.php'; 



$puuid = isset($_GET['puuid']) ? $_GET['puuid'] : '';

if (!$puuid) {
    echo json_encode(['error' => 'PUUID is required']);
    exit;
}


$url = "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/$puuid/ids?start=0&count=5";


$ch = curl_init();


curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Riot-Token: " . RIOT_API_KEY 
]);


$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Request error: ' . curl_error($ch)]);
} else {

    echo $response;
}


curl_close($ch);
?>
