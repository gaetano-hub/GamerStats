<?php
// Start the session
session_start();

// Check if 'user' is set in the URL
if (isset($_GET['user'])) {
    // Set the session variable
    $_SESSION['nameMember'] = htmlspecialchars(urldecode($_GET['user']));
}

// Redirect back to the desired page (memberPage.php in this case)
header("Location: ../memberPage/memberPage.php");
exit();
?>
