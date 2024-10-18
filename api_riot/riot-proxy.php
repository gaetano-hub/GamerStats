<?php

require '../conf/config.php';

$gameName = isset($_GET['gameName']) ? urlencode($_GET['gameName']) : '';
$tagline = isset($_GET['tagline']) ? urlencode($_GET['tagline']) : '';

if (!$gameName || !$tagline) {
    echo json_encode(['error' => 'Game Name and Tagline are required']);
    exit;
}


$url = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/$gameName/$tagline";


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

    $data = json_decode($response, true);


    if (isset($data['error'])) {
        echo json_encode(['error' => $data['error']]);
    } else {

        $puuid = isset($data['puuid']) ? $data['puuid'] : null;

        if ($puuid) {

            echo json_encode(['puuid' => $puuid]);
        } else {
            echo json_encode(['error' => 'Puuid not found']);
        }
    }
}




curl_close($ch);
?>
