<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$patient_id = (int)$_SESSION['user_id'];
$assignments = [];
$fetch_error = null;

$sql = "SELECT a.`Appointment_ID`, a.`Appointment_Date`, a.`Appointment_Time`, a.`Status` AS `Appointment_Status`,
               ass.`Staff_Type`, ass.`Notes`, ass.`Assigned_At`,
               s.`Secretary_First_Name`, s.`Secretary_Last_Name`,
               t.`Translator_First_Name`, t.`Translator_Last_Name`, t.`Language`
        FROM `Appointment_Support_Staff` ass
        JOIN `Appointment` a ON ass.`Appointment_ID` = a.`Appointment_ID`
        JOIN `Doctor` d ON a.`Doctor_ID` = d.`Doctor_ID`
        LEFT JOIN `Secretary` s ON ass.`Staff_Type` = 'Secretary' AND ass.`Staff_ID` = s.`Secretary_ID` AND s.`Clinic_ID` = d.`Clinic_ID`
        LEFT JOIN `Translator` t ON ass.`Staff_Type` = 'Translator' AND ass.`Staff_ID` = t.`Translator_ID` AND t.`Clinic_ID` = d.`Clinic_ID`
        WHERE a.`Patient_ID` = ?
        ORDER BY a.`Appointment_Date` DESC, a.`Appointment_Time` DESC, ass.`Assigned_At` DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching support staff assignments: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Support Staff</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .staff-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .staff-table th, .staff-table td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        .staff-table th { background-color: #f2f2f2; }
        .staff-table tr:nth-child(even) { background-color: #f9f9f9; }
        .no-data { color: #6c757d; font-style: italic; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Patient Menu</h2>
        <ul>
            <li><a href="book_appointment.php">Book New Appointment</a></li>
            <li><a href="view_appointments.php">View Appointments</a></li>
            <li><a href="view_prescriptions.php">View Prescriptions</a></li>
            <li><a href="view_diagnoses.php">View Diagnoses</a></li>
            <li><a href="view_tests.php">View Tests & Results</a></li>
            <li><a href="view_bills.php">View Bills</a></li>
            <li><a href="view_support_staff.php">View Support Staff</a></li>
        </ul>
        <div class="logout-link"><a href="../logout.php">Logout</a></div>
    </div>

    <div class="main-content">
        <div class="header"><h1>Assigned Support Staff</h1></div>
        <div class="content-section">
            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($assignments)): ?>
                <p class="no-data">No support staff assigned to your appointments yet.</p>
            <?php else: ?>
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Appointment</th>
                            <th>Staff Type</th>
                            <th>Assigned Staff</th>
                            <th>Notes</th>
                            <th>Assigned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $row): ?>
                            <?php
                            if ($row['Staff_Type'] === 'Secretary') {
                                $staff_name = trim(($row['Secretary_First_Name'] ?? '') . ' ' . ($row['Secretary_Last_Name'] ?? ''));
                            } else {
                                $name_part = trim(($row['Translator_First_Name'] ?? '') . ' ' . ($row['Translator_Last_Name'] ?? ''));
                                $staff_name = $name_part !== '' ? $name_part . ' (' . ($row['Language'] ?? 'N/A') . ')' : '';
                            }
                            if ($staff_name === '') $staff_name = 'Staff record not found';
                            ?>
                            <tr>
                                <td>
                                    #<?php echo (int)$row['Appointment_ID']; ?><br>
                                    <?php echo htmlspecialchars(date("d-m-Y", strtotime($row['Appointment_Date']))); ?>
                                    <?php echo $row['Appointment_Time'] ? htmlspecialchars(substr((string)$row['Appointment_Time'], 0, 5)) : ''; ?><br>
                                    <small>Status: <?php echo htmlspecialchars($row['Appointment_Status'] ?? 'N/A'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['Staff_Type']); ?></td>
                                <td><?php echo htmlspecialchars($staff_name); ?></td>
                                <td><?php echo htmlspecialchars($row['Notes'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['Assigned_At']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
