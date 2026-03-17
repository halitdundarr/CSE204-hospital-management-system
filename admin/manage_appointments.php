<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

$admin_user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';

// Feedback messages
$feedback_message = isset($_SESSION['admin_manage_appointment_feedback']) ? $_SESSION['admin_manage_appointment_feedback'] : null;
$feedback_type = isset($_SESSION['admin_manage_appointment_feedback_type']) ? $_SESSION['admin_manage_appointment_feedback_type'] : 'error';
unset($_SESSION['admin_manage_appointment_feedback']);
unset($_SESSION['admin_manage_appointment_feedback_type']);

// Fetch all appointments
$appointments = [];
$fetch_error = null;
$sql = "SELECT
            a.`Appointment_ID`, a.`Appointment_Date`, a.`Appointment_Time`, a.`Status`,
            p.`Patient_ID`, p.`Patient_First_Name`, p.`Patient_Last_Name`,
            d.`Doctor_ID`, d.`Doctor_First_Name`, d.`Doctor_Last_Name`
        FROM `APPOINTMENT` a
        JOIN `PATIENT` p ON a.`Patient_ID` = p.`Patient_ID`
        JOIN `DOCTOR` d ON a.`Doctor_ID` = d.`Doctor_ID`
        ORDER BY a.`Appointment_Date` DESC, a.`Appointment_Time` DESC";
$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { $appointments[] = $row; } }
else { $fetch_error = "Error fetching appointments: " . $conn->error; }
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Appointments</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Styles */
        .appointments-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em;}
        .appointments-table th, .appointments-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: middle;}
        .appointments-table th { background-color: #f2f2f2; font-weight: bold; }
        .appointments-table tr:nth-child(even) { background-color: #f9f9f9; }
        .appointments-table tr:hover { background-color: #f1f1f1; }
        .status-scheduled { color: #007bff; }
        .status-completed { color: #28a745; }
        .status-cancelled { color: #6c757d; text-decoration: line-through; }
        .no-appointments { color: #6c757d; font-style: italic;}
        .action-button { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.85em; display: inline-block; margin-right: 5px; color: white; }
        .action-button.edit { background-color: #ffc107; color: #333; }
        .action-button.edit:hover { background-color: #e0a800; }
        .action-button.delete { background-color: #dc3545; }
        .action-button.delete:hover { background-color: #c82333; }
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; font-size: 0.95em;}
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;}
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
        </ul>
         <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
             <h1>Manage All Appointments</h1>
        </div>

        <div class="content-section">
            <h2>Appointment List</h2>

             <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($appointments)): ?>
                <p class="no-appointments">No appointments found.</p>
            <?php else: ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt):
                            $status = $appt['Status'] ?? 'N/A';
                            // Admin can edit only scheduled appointments
                            $can_edit = ($status === 'Scheduled');
                            // *** GÜNCELLENDİ: Admin her zaman silebilir ***
                            $can_delete = true;
                            ?>
                            <tr>
                                <td><?php echo $appt['Appointment_ID']; ?></td>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($appt['Appointment_Date']))); ?></td>
                                <td><?php echo $appt['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appt['Appointment_Time']))) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($appt['Patient_First_Name'] . ' ' . $appt['Patient_Last_Name']); ?> (ID: <?php echo $appt['Patient_ID']; ?>)</td>
                                <td>Dr. <?php echo htmlspecialchars($appt['Doctor_First_Name'] . ' ' . $appt['Doctor_Last_Name']); ?> (ID: <?php echo $appt['Doctor_ID']; ?>)</td>
                                <td>
                                     <?php
                                        $status_class = 'status-' . strtolower(htmlspecialchars($status));
                                        echo '<span class="' . $status_class . '">' . htmlspecialchars($status) . '</span>';
                                    ?>
                                </td>
                                <td>
                                     <?php if ($can_edit): ?>
                                         <a href="edit_appointment.php?appointment_id=<?php echo $appt['Appointment_ID']; ?>" class="action-button edit">Edit Date/Time</a>
                                     <?php endif; ?>
                                     <?php // Delete butonu artık her zaman görünecek ?>
                                     <form action="process_delete_appointment.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to PERMANENTLY DELETE this appointment (ID: <?php echo $appt['Appointment_ID']; ?>)? This cannot be undone.');">
                                         <input type="hidden" name="appointment_id_to_delete" value="<?php echo $appt['Appointment_ID']; ?>">
                                         <button type="submit" class="action-button delete">Delete</button>
                                     </form>
                                     <?php if (!$can_edit && !$can_delete): // Bu koşul artık anlamsız ama kalsın ?>
                                         -
                                     <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div> </div> </body>
</html>