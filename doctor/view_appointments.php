<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Doctor'; // Get doctor's name

// Fetch upcoming/today's appointments for the logged-in doctor
$appointments = [];
$fetch_error = null;
$today = date('Y-m-d'); // Get current date

// SQL query to get appointment details along with patient name
// Filter for appointments on or after today, ordered by date and time
$sql = "SELECT
            a.`Appointment_ID`,
            a.`Appointment_Date`,
            a.`Appointment_Time`,
            a.`Status`,
            p.`Patient_ID`,
            p.`Patient_First_Name`,
            p.`Patient_Last_Name`,
            a.`Follow_Up_Appointment_ID` -- Check if it's a follow-up
        FROM `APPOINTMENT` a
        JOIN `PATIENT` p ON a.`Patient_ID` = p.`Patient_ID`
        WHERE a.`Doctor_ID` = ? AND a.`Appointment_Date` >= ?
        ORDER BY a.`Appointment_Date` ASC, a.`Appointment_Time` ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("is", $doctor_id, $today); // Bind doctor ID and today's date
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching appointments: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link rel="stylesheet" href="../css/style.css"> <style>
        /* Reusing appointment table styles */
        .appointments-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .appointments-table th, .appointments-table td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        .appointments-table th { background-color: #f2f2f2; font-weight: bold; }
        .appointments-table tr:nth-child(even) { background-color: #f9f9f9; }
        .appointments-table tr:hover { background-color: #f1f1f1; }
        .status-scheduled { color: #007bff; font-weight: bold; }
        /* Add other status styles if needed (e.g., In Progress) */
        .status-cancelled { color: #6c757d; text-decoration: line-through; }
        .no-appointments { color: #6c757d; font-style: italic;}
        .action-link {
            background-color: #007bff; color: white; padding: 5px 10px; border-radius: 4px;
            text-decoration: none; font-size: 0.9em;
        }
         .action-link:hover { background-color: #0056b3; }
         .follow-up-indicator { font-size: 0.8em; color: #6c757d; display: block; margin-top: 2px;}
    </style>
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
            <h2>Today's & Upcoming Appointments</h2>

             <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($appointments)): ?>
                <p class="no-appointments">You have no upcoming appointments.</p>
                <?php else: ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt):
                            $status = $appt['Status'] ?? 'Scheduled'; // Default to Scheduled if NULL
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($appt['Appointment_Date']))); ?></td>
                                <td><?php echo $appt['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appt['Appointment_Time']))) : 'N/A'; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($appt['Patient_First_Name'] . ' ' . $appt['Patient_Last_Name']); ?>
                                    <?php if (!empty($appt['Follow_Up_Appointment_ID'])): ?>
                                        <span class="follow-up-indicator">(Follow-up)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $status_class = 'status-' . strtolower(htmlspecialchars($status));
                                        echo '<span class="' . $status_class . '">' . htmlspecialchars($status) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($status !== 'Cancelled'): ?>
                                         <a href="manage_patient.php?appointment_id=<?php echo $appt['Appointment_ID']; ?>&patient_id=<?php echo $appt['Patient_ID']; ?>" class="action-link">Manage</a>
                                    <?php else: ?>
                                        -
                                     <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>