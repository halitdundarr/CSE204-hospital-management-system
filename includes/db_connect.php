<?php

$servername = "localhost"; 
$username = "root";        
$password = "mysql";       
$dbname = "Hospital_3NF"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Failed to connect to DB: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8")) {
}

?>