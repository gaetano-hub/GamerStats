<?php
session_start(); // Inizializza la sessione
if (isset($_GET['team'])) {
    $_SESSION['nameTeam'] = $_GET['team']; // Imposta la variabile di sessione
}

// Reindirizza alla pagina di destinazione
header('Location: ../team/teamPage.php?team=' . $_SESSION['nameTeam']);
exit();
?>
