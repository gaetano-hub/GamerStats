<?php
session_start();

// Configura le informazioni dell'app
$client_id = '1297867349034270742'; // Sostituisci con il tuo Client ID
$redirect_uri = 'http://localhost/GamerStats/login/login_ds/callback.php'; // Sostituisci con l'URL del tuo sito
$scope = 'identify email'; // Richiedi l'accesso all'ID e all'email dell'utente

// Reindirizza l'utente a Discord per il login
$auth_url = "https://discord.com/api/oauth2/authorize?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=" . urlencode($scope);
header('Location: ' . $auth_url);
exit;
?>
