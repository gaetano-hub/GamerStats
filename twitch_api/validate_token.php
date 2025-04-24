<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitch API Token validation</title>
</head>
<body>
    <h1>Twitch API Access Token validation</h1>


<?php
$access_token = "13jaekuvinrvt2ym42yardp2kcwi16";
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
        echo "Token is valid, here's the data: " . print_r($response_data, true);
    } else {
        echo "Token is invalid or expired.";
    }
}

// Close the cURL session
curl_close($ch);
?>
</body>
</html>