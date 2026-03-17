<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

$summary = [
    'total' => 0,
    'scheduled' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'today' => 0
];
$doctor_load = [];

$summary_sql = "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN `Status` = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled,
    SUM(CASE WHEN `Status` = 'Completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN `Status` = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
    SUM(CASE WHEN `Appointment_Date` = CURDATE() THEN 1 ELSE 0 END) AS today
FROM `APPOINTMENT`";

$summary_result = $conn->query($summary_sql);
if ($summary_result && $summary_result->num_rows === 1) {
    $summary = $summary_result->fetch_assoc();
}

$doctor_sql = "SELECT
    d.`Doctor_ID`,
    d.`Doctor_First_Name`,
    d.`Doctor_Last_Name`,
    COUNT(a.`Appointment_ID`) AS appointment_count
FROM `DOCTOR` d
LEFT JOIN `APPOINTMENT` a ON d.`Doctor_ID` = a.`Doctor_ID`
GROUP BY d.`Doctor_ID`, d.`Doctor_First_Name`, d.`Doctor_Last_Name`
ORDER BY appointment_count DESC, d.`Doctor_Last_Name` ASC
LIMIT 5";

$doctor_result = $conn->query($doctor_sql);
if ($doctor_result) {
    while ($row = $doctor_result->fetch_assoc()) {
        $doctor_load[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
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
            <li><a href="reports.php">View Reports</a></li>
            <li><a href="audit_logs.php">View Audit Logs</a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Admin Reports</h1>
        </div>

        <div class="content-section">
            <h2>Appointment Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Total</th>
                        <th>Scheduled</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Today</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo (int)$summary['total']; ?></td>
                        <td><?php echo (int)$summary['scheduled']; ?></td>
                        <td><?php echo (int)$summary['completed']; ?></td>
                        <td><?php echo (int)$summary['cancelled']; ?></td>
                        <td><?php echo (int)$summary['today']; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="content-section">
            <h2>Top 5 Doctors by Appointment Count</h2>
            <?php if (empty($doctor_load)): ?>
                <p>No doctor data available.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Doctor ID</th>
                            <th>Name</th>
                            <th>Appointment Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctor_load as $doctor): ?>
                            <tr>
                                <td><?php echo (int)$doctor['Doctor_ID']; ?></td>
                                <td>Dr. <?php echo htmlspecialchars($doctor['Doctor_First_Name'] . ' ' . $doctor['Doctor_Last_Name']); ?></td>
                                <td><?php echo (int)$doctor['appointment_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
