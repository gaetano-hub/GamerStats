<?php
/*
For the moment the "teams" table is in the same db of the newly created team table
*/


$servername = "localhost";
$dbusername = "root";
$password = "";
$dbname = "gamerstats";
$username = 'prova';
//debug, the session should already exist when logged in
session_start();
$user_nickname=1;
$_SESSION['nickname'] = $user_nickname;

$conn = new mysqli($servername, $dbusername, $password, $dbname);
  

if (isset($_POST['teamName']) && isset($_POST['game'])) {
    $teamName = $_POST['teamName'];
    $gameName = $_POST['game'];
    $stmt = $conn->prepare("INSERT INTO teams (team_name, game, member1, member2, member3, member4, member5, member6, leader) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $teamName, $gameName, $user_nickname, $member1_nickname, $member2_nickname, $member3_nickname, $member4_nickname, $member5_nickname, $user_nickname);
    if ($stmt->execute()) {
        echo "Team row inserted successfully";
    } else {
        echo "Error: " . $stmt->error;
    }    
}