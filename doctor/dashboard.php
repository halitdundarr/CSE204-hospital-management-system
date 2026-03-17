<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

$user_name = htmlspecialchars($_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Doctor Menu</h2>
        <ul>
            <li><a href="view_appointments.php">View Appointments</a></li>
        </ul>
         <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
             <h1>Welcome, Dr. <?php echo $user_name; ?>!</h1>
        </div>

         <div class="content-section">
            <h2>Dashboard Overview</h2>
            <p>Select an option from the menu on the left to manage your schedule and patient interactions.</p>
        </div>
    </div>
</body>
</html>