<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitch API Token</title>
</head>
<body>
    <h1>Twitch API Access Token</h1>



    <?php
    // Twitch API Token URL
    $url = "https://id.twitch.tv/oauth2/token";

    // The POST fields required to get the token
    $data = [
        'client_id' => 'kdky4zjc7xuo41zu0v1bqf3y9hp41v',
        'client_secret' => 'tc4l6kh8sg1o7y2wszxh8c93gkxjcf',
        'grant_type' => 'client_credentials',
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set the URL
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set the method to POST
    curl_setopt($ch, CURLOPT_POST, true);

    // Attach the data
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    // To receive the response as a string instead of directly outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request and store the response
    $response = curl_exec($ch);

    // Check for errors in the cURL request
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
    } else {
        // Decode the JSON response
        $response_data = json_decode($response, true);
        
        // Check if decoding was successful
        if (json_last_error() === JSON_ERROR_NONE) {
            // Access token, scope, and other data are available in $response_data
            echo "Access Token: " . $response_data['access_token'] . PHP_EOL;
        } else {
            echo "Error decoding JSON response.";
        }
    }

    // Close the cURL session
    curl_close($ch);
    ?>

</body>
</html>