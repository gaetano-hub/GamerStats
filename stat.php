<?php
/* 
STATUS:WIP
TODO:
    -test statphp with statcalculatorL.js

*/


/* 
Function to get all rows from a table and return as a variable
it wirks by fetching all rows one by one 
(to avoid asking for too much data in the case of a big table)
from the table and returning them as an array
*/
function getTableData($pdo)
{
    try {
        // Query
        $stmt = $pdo->query("SELECT * FROM your_table");
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

/*
main code
*/

// Connection setup (TEMPORARY)
$host = 'localhost';
$dbname = 'your_database';
$user = 'your_username';
$pass = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Call the function and save the results in a variable
    $data = json_encode(getTableData($pdo));

    /* 

    INSERT CODE TO HANDLE $data

    */ 

    /* 
    DEBUG
    echo $data;
    */ 

} catch (PDOException $e) {
    // it returns 'error' and the error message associated with the exception.
    echo 'Connection failed: ' . $e->getMessage();
}
