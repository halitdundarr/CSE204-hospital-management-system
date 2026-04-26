<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Patient';

$bills = [];
$fetch_error = null;

$sql = "SELECT
            b.`Bill_ID`,
            b.`Total_Amount`,
            b.`Issue_Date`,
            b.`Status` AS `Bill_Status`,
            b.`Payment_Method`,
            b.`Paid_At`,
            b.`Payment_Reference`,
            a.`Appointment_Date`,
            a.`Appointment_Time`,
            d.`Doctor_First_Name`,
            d.`Doctor_Last_Name`
        FROM `Bill` b
        JOIN `Appointment` a ON b.`Appointment_ID` = a.`Appointment_ID`
        JOIN `Doctor` d ON a.`Doctor_ID` = d.`Doctor_ID`
        WHERE a.`Patient_ID` = ?
        ORDER BY b.`Issue_Date` DESC, b.`Bill_ID` DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $patient_id);
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
    <title>My Bills</title>
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
        <div class="logout-link">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>My Bills</h1>
        </div>

        <div class="content-section">
            <h2>Billing History</h2>

            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($bills)): ?>
                <p class="no-bills">You do not have any bills recorded.</p>
            <?php else: ?>
                <table class="bills-table">
                    <thead>
                        <tr>
                            <th>Issue Date</th>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>Doctor</th>
                            <th>Total Amount (₺)</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Paid At</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <?php
                                $status = $bill['Bill_Status'] ?? 'Unpaid';
                                $status_class = 'status-' . strtolower($status);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($bill['Issue_Date']))); ?></td>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($bill['Appointment_Date']))); ?></td>
                                <td><?php echo $bill['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($bill['Appointment_Time']))) : 'N/A'; ?></td>
                                <td>Dr. <?php echo htmlspecialchars($bill['Doctor_First_Name'] . ' ' . $bill['Doctor_Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars(number_format((float)$bill['Total_Amount'], 2)); ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                <td><?php echo htmlspecialchars($bill['Payment_Method'] ?? 'Unknown/Legacy'); ?></td>
                                <td><?php echo !empty($bill['Paid_At']) ? htmlspecialchars(date("d-m-Y H:i", strtotime($bill['Paid_At']))) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($bill['Payment_Reference'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

