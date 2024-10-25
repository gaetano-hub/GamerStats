<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gamerstats";
$name = ""; //get player name from db
$player_id = "";
function getTableData($pdo, $name)
{
    try {
        // Query
        $stmt = $pdo->query("SELECT * FROM `$name`"); // Replace with your table name
        $results = [];
        
        // Fetch rows one at a time and append them to the results array
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;

    } catch (PDOException $e) {
        // it returns 'error' and the error message associated with the exception.
        return ['error' => $e->getMessage()];
    }
}

function getPlayerName($pdo, $player_id){
    $stmt = $pdo->query("SELECT * FROM `users` WHERE `steamID` = '$player_id'");
     // Replace with your table name
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : null; // assuming 'name' is the column for player name}
};

try {
    
            $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $name=getPlayerName($pdo, $player_id);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Call the function and save the results in a variable
    $data = json_encode(getTableData($pdo, $name));

    //Send data to AJAX js request
    echo $data;
    

} catch (PDOException $e) {
    // it returns 'error' and the error message associated with the exception.
    echo 'Connection failed: ' . $e->getMessage();
}