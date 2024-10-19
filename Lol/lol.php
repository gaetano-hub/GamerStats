<?php
$access_token = 'swqwsxqrdbfu9snanpeqm2k2fjewkr'; // Replace with your access token
$client_id = 'kdky4zjc7xuo41zu0v1bqf3y9hp41v'; // Replace with your client ID
$game_id = '21779'; // Fortnite's game ID, replace with the actual one you obtained

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
    <title>Top Twitch Streams for LOL</title>
</head>
<body>
    <h1>Live LoL Streams</h1>
    <div class="streams">
        <?php if (!empty($streams)) : ?>
            <?php foreach ($streams as $stream) : ?>
                <div class="stream">
                    <h3><?php echo htmlspecialchars($stream['user_name']); ?></h3>
                    <img src="<?php echo str_replace('{width}x{height}', '320x180', $stream['thumbnail_url']); ?>" alt="Stream Thumbnail">
                    <p>Viewers: <?php echo $stream['viewer_count']; ?></p>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No streams available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
