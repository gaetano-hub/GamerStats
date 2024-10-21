<?php
$client_id = '1297867349034270742'; // Sostituisci con il tuo Client ID
$redirect_uri = 'http://localhost/GamerStats/signUp/signUp_ds/callback.php'; // L'URI di reindirizzamento
$scope = 'identify'; // Lo scope per ottenere solo l'identità dell'utente

// Crea l'URL di autorizzazione
$auth_url = "https://discord.com/api/oauth2/authorize?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=" . urlencode($scope);

// Reindirizza l'utente alla pagina di autorizzazione di Discord
header('Location: ' . $auth_url);
exit;
?>
