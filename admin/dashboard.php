<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
     <div class="sidebar">
        <h2>Admin Menu</h2>
        <ul>
            <li><a href="add_doctor.php">Add New Doctor</a></li>
            <li><a href="add_nurse.php">Add New Nurse</a></li>
            <li><a href="add_patient.php">Add New Patient</a></li>
            <li><a href="find_patient_doctors.php">List Patient's Doctors</a></li>
            <li><a href="view_all_patients_appointments.php">View All Patients & Appointments</a></li>
            <li><a href="manage_appointments.php">Manage Appointments</a></li>
            </ul>
         <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
         <div class="header">
             <h1>Welcome, Admin <?php echo $user_name; ?>!</h1>
        </div>

         <div class="content-section">
            <h2>Admin Control Panel</h2>
            <p>Select an option from the menu on the left to manage the system.</p>
         </div>
    </div>
</body>
</html>