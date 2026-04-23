<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$feedback_message = $_SESSION['admin_feedback'] ?? null;
$feedback_type = $_SESSION['admin_feedback_type'] ?? 'error';
unset($_SESSION['admin_feedback'], $_SESSION['admin_feedback_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Translator</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Menu</h2>
        <ul>
            <li><a href="add_doctor.php">Add New Doctor</a></li>
            <li><a href="add_nurse.php">Add New Nurse</a></li>
            <li><a href="add_secretary.php">Add New Secretary</a></li>
            <li><a href="add_translator.php">Add New Translator</a></li>
            <li><a href="manage_support_staff.php">Assign Support Staff</a></li>
            <li><a href="add_patient.php">Add New Patient</a></li>
            <li><a href="find_patient_doctors.php">List Patient's Doctors</a></li>
            <li><a href="view_all_patients_appointments.php">View All Patients & Appointments</a></li>
            <li><a href="manage_appointments.php">Manage Appointments</a></li>
            <li><a href="reports.php">View Reports</a></li>
            <li><a href="audit_logs.php">View Audit Logs</a></li>
            <li><a href="view_bills.php">View Bills</a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="header"><h1>Add New Translator</h1></div>
        <div class="content-section">
            <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>
            <form action="process_add_translator.php" method="POST">
                <?php echo csrf_input_field(); ?>
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" required>
                <label for="language">Language</label>
                <input type="text" id="language" name="language" placeholder="e.g. English, German, Arabic" required>
                <button type="submit">Add Translator</button>
            </form>
        </div>
    </div>
</body>
</html>
