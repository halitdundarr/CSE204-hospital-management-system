<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

$admin_user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';

$bills = [];
$fetch_error = null;

$sql = "SELECT
            b.`Bill_ID`,
            b.`Total_Amount`,
            b.`Issue_Date`,
            b.`Status` AS `Bill_Status`,
            p.`Patient_First_Name`,
            p.`Patient_Last_Name`,
            a.`Appointment_Date`,
            a.`Appointment_Time`,
            d.`Doctor_First_Name`,
            d.`Doctor_Last_Name`
        FROM `Bill` b
        JOIN `Appointment` a ON b.`Appointment_ID` = a.`Appointment_ID`
        JOIN `Patient` p ON b.`Patient_ID` = p.`Patient_ID`
        JOIN `Doctor` d ON a.`Doctor_ID` = d.`Doctor_ID`
        ORDER BY b.`Issue_Date` DESC, b.`Bill_ID` DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching bills: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bills</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .bills-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .bills-table th, .bills-table td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        .bills-table th { background-color: #f2f2f2; font-weight: bold; }
        .bills-table tr:nth-child(even) { background-color: #f9f9f9; }
        .bills-table tr:hover { background-color: #f1f1f1; }
        .status-paid { color: #28a745; font-weight: bold; }
        .status-unpaid { color: #dc3545; font-weight: bold; }
        .no-bills { color: #6c757d; font-style: italic; }
    </style>
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
            <li><a href="view_bills.php">View Bills</a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>All Bills</h1>
        </div>

        <div class="content-section">
            <h2>Billing Overview</h2>

            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($bills)): ?>
                <p class="no-bills">No bills have been created yet.</p>
            <?php else: ?>
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Issue Date</th>
                            <th>Patient</th>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>Doctor</th>
                            <th>Total Amount (₺)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <?php
                                $status = $bill['Bill_Status'] ?? 'Unpaid';
                                $status_class = 'status-' . strtolower($status);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['Bill_ID']); ?></td>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($bill['Issue_Date']))); ?></td>
                                <td><?php echo htmlspecialchars($bill['Patient_First_Name'] . ' ' . $bill['Patient_Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($bill['Appointment_Date']))); ?></td>
                                <td><?php echo $bill['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($bill['Appointment_Time']))) : 'N/A'; ?></td>
                                <td>Dr. <?php echo htmlspecialchars($bill['Doctor_First_Name'] . ' ' . $bill['Doctor_Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars(number_format((float)$bill['Total_Amount'], 2)); ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

