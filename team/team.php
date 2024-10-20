<?php
/*
For the moment the "teams" table is in the same db of the newly created team table
*/


$servername = "localhost";
$dbusername = "root";
$password = "";
$dbname = "gamerstats";
$username = 'prova';
//debug
session_start();
$user_id=1;
$_SESSION['id'] = $user_id;

$conn = new mysqli($servername, $dbusername, $password, $dbname);
function generateUniqueId($mysqli) {
    $id = uniqid("TM", false);
    $query = "SELECT * FROM teams WHERE id = '$id'";
    $result = $mysqli->query($query);
    if ($result->num_rows > 0) {
      return generateUniqueId($mysqli);
    } else {
      return $id;
    }
  }
  

if (isset($_POST['teamName'])) {
    $teamName = $_POST['teamName'];

    if (isset($_SESSION['id'])) {
        $uniqueId = generateUniqueId($conn);
        $stmt = $conn->prepare("INSERT INTO teams (id, team_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $uniqueId, $teamName);
        if ($stmt->execute()) {
            echo "Team row inserted successfully";
        } else {
            echo "Error: " . $stmt->error;
        }    
        // Create the team table if it does not exist
        $sql = "CREATE TABLE IF NOT EXISTS $uniqueId (
        member_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        leader INT NOT NULL
        )";
        if ($conn->query($sql) !== TRUE) {
            echo "Error creating table: " . $conn->error;
        }

        $stmt = $conn->prepare("INSERT INTO $uniqueId (name, leader) VALUES (?, ?)");
        $leaderId = (int) $_SESSION['id'];
        $isLeader = 1;
        $stmt->bind_param("si", $leaderId, $isLeader); //string and integer
        if ($stmt->execute()) {
            echo "Team created successfully";
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: Session ID not set";
    }
    

}