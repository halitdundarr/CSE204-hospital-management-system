<?php
declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/run_sql_checks.php <sql-file>\n");
    exit(1);
}

$sqlFile = $argv[1];
if (!is_file($sqlFile)) {
    fwrite(STDERR, "SQL file not found: {$sqlFile}\n");
    exit(1);
}

$host = getenv('DB_HOST') ?: 'db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'mysql';
$name = getenv('DB_NAME') ?: 'Hospital_3NF';

$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    fwrite(STDERR, "DB connection failed: {$conn->connect_error}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read SQL file.\n");
    exit(1);
}

if (!$conn->multi_query($sql)) {
    fwrite(STDERR, "Failed to execute checks: {$conn->error}\n");
    exit(1);
}

$hasFailure = false;
do {
    if ($result = $conn->store_result()) {
        while ($row = $result->fetch_assoc()) {
            $checkName = (string)($row['check_name'] ?? 'unknown_check');
            $status = (string)($row['status'] ?? 'UNKNOWN');
            echo "{$checkName}: {$status}\n";
            if ($status !== 'PASS') {
                $hasFailure = true;
            }
        }
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

$conn->close();
exit($hasFailure ? 1 : 0);
