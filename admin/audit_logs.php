<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

ensure_audit_log_table($conn);

$logs = [];
$sql = "SELECT
            `Audit_ID`, `Actor_Role`, `Actor_ID`, `Action_Type`,
            `Target_Table`, `Target_ID`, `Details`, `Created_At`
        FROM `AUDIT_LOG`
        ORDER BY `Created_At` DESC, `Audit_ID` DESC
        LIMIT 200";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Audit Logs</title>
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
            <h1>Audit Log</h1>
        </div>

        <div class="content-section">
            <h2>Recent Activity (Last 200 Events)</h2>
            <?php if (empty($logs)): ?>
                <p>No audit entries yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Target</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['Created_At']); ?></td>
                                <td><?php echo htmlspecialchars($log['Actor_Role'] . ' #' . $log['Actor_ID']); ?></td>
                                <td><?php echo htmlspecialchars($log['Action_Type']); ?></td>
                                <td><?php echo htmlspecialchars($log['Target_Table'] . ' #' . ($log['Target_ID'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($log['Details'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
