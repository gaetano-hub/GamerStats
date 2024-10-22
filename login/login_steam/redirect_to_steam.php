<?php
// redirect_to_steam.php

$steamOpenIDURL = 'https://steamcommunity.com/openid/login';
$returnTo = 'http://localhost/GamerStats/login/login_steam/handle_openid.php'; // Cambia con il tuo URL di ritorno
$realm = 'http://localhost/GamerStats'; // Dominio

// Parametri di autenticazione OpenID
$params = [
    'openid.ns'         => 'http://specs.openid.net/auth/2.0',
    'openid.mode'       => 'checkid_setup',
    'openid.return_to'  => $returnTo,
    'openid.realm'      => $realm,
    'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
    'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
];

// Costruisci l'URL di reindirizzamento
$query = http_build_query($params);
$redirectURL = $steamOpenIDURL . '?' . $query;

// Reindirizza l'utente a Steam per il login
header("Location: $redirectURL");
exit();
