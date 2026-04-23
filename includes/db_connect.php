<?php

require_once __DIR__ . '/env_loader.php';
load_env_file(dirname(__DIR__) . '/.env');

$servername = env_value('DB_HOST', 'localhost');
$username = env_value('DB_USER', 'root');
$password = env_value('DB_PASS', '');
$dbname = env_value('DB_NAME', 'Hospital_3NF');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("DB connect error: " . $conn->connect_error);
    die("A database error occurred. Please try again later.");
}

if (!$conn->set_charset("utf8")) {
}

?>