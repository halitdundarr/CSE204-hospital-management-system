<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Patient';

// Check for cancellation/edit messages
$feedback_message = null;
$feedback_type = 'error'; // Default type
if (isset($_SESSION['cancellation_success'])) {
    $feedback_message = $_SESSION['cancellation_success'];
    $feedback_type = 'success';
    unset($_SESSION['cancellation_success']);
} elseif (isset($_SESSION['cancellation_error'])) {
    $feedback_message = $_SESSION['cancellation_error'];
    unset($_SESSION['cancellation_error']);
} elseif (isset($_SESSION['patient_edit_appointment_feedback'])) { // Check for edit feedback
    $feedback_message = $_SESSION['patient_edit_appointment_feedback'];
    $feedback_type = isset($_SESSION['patient_edit_appointment_feedback_type']) ? $_SESSION['patient_edit_appointment_feedback_type'] : 'error';
    unset($_SESSION['patient_edit_appointment_feedback']);
    unset($_SESSION['patient_edit_appointment_feedback_type']);
}


// Fetch patient's appointments
$sql = "SELECT
            a.`Appointment_ID`, a.`Appointment_Date`, a.`Appointment_Time`, a.`Status`,
            d.`Doctor_ID`, d.`Doctor_First_Name`, d.`Doctor_Last_Name`, d.`Clinic_ID`,
            c.`Clinic_Name`
        FROM `APPOINTMENT` a
        JOIN `DOCTOR` d ON a.`Doctor_ID` = d.`Doctor_ID`
        JOIN `CLINIC` c ON d.`Clinic_ID` = c.`Clinic_ID`
        WHERE a.`Patient_ID` = ?
        ORDER BY CASE WHEN a.`Appointment_Date` >= CURDATE() THEN 0 ELSE 1 END, a.`Appointment_Date` ASC, a.`Appointment_Time` ASC";

$stmt = $conn->prepare($sql);
$appointments = [];
$fetch_error = null;
if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $appointments[] = $row; }
    $stmt->close();
} else { $fetch_error = "Error fetching appointments: " . $conn->error; }
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Styles remain the same as before */
        .appointments-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .appointments-table th, .appointments-table td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle;}
        .appointments-table th { background-color: #f2f2f2; font-weight: bold; }
        .appointments-table tr:nth-child(even) { background-color: #f9f9f9; }
        .appointments-table tr:hover { background-color: #f1f1f1; }
        .status-scheduled { color: #007bff; }
        .status-completed { color: #28a745; }
        .status-cancelled { color: #6c757d; text-decoration: line-through; }
        .no-appointments { color: #6c757d; font-style: italic;}
        .action-button { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.85em; display: inline-block; margin-right: 5px; color: white; }
        .action-button.edit { background-color: #ffc107; color: #333; } /* Yellow */
        .action-button.edit:hover { background-color: #e0a800; }
        .action-button.cancel { background-color: #dc3545; } /* Red */
        .action-button.cancel:hover { background-color: #c82333; }
        .action-button.followup { background-color: #17a2b8; } /* Teal */
        .action-button.followup:hover { background-color: #138496; }
        /* Feedback Message Styles */
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; font-size: 0.95em;}
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;}
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
        </ul>
        <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
             <h1>My Appointments</h1>
        </div>

        <div class="content-section">
            <h2>Your Appointment History</h2>

             <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php endif; ?>


            <?php if (empty($appointments) && !$fetch_error): ?>
                <p class="no-appointments">You currently have no appointments scheduled.</p>
            <?php elseif (!empty($appointments)): ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Clinic</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt):
                            $status = $appt['Status'] ?? 'Unknown';
                            $is_future_scheduled = (strtotime($appt['Appointment_Date']) >= strtotime(date('Y-m-d'))) && $status === 'Scheduled';
                            $is_past_or_completed = (strtotime($appt['Appointment_Date']) < strtotime(date('Y-m-d'))) || $status === 'Completed';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($appt['Appointment_Date']))); ?></td>
                                <td><?php echo $appt['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appt['Appointment_Time']))) : 'N/A'; ?></td>
                                <td>Dr. <?php echo htmlspecialchars($appt['Doctor_First_Name'] . ' ' . $appt['Doctor_Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['Clinic_Name']); ?></td>
                                <td>
                                    <?php
                                        $status_class = 'status-' . strtolower(htmlspecialchars($status));
                                        echo '<span class="' . $status_class . '">' . htmlspecialchars($status) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($is_future_scheduled): ?>
                                        <a href="edit_appointment.php?appointment_id=<?php echo $appt['Appointment_ID']; ?>" class="action-button edit">Edit</a>
                                        <form action="process_cancellation.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                            <input type="hidden" name="appointment_id_to_cancel" value="<?php echo $appt['Appointment_ID']; ?>">
                                            <button type="submit" class="action-button cancel">Cancel</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($is_past_or_completed && $status !== 'Cancelled'): ?>
                                        <a href="book_appointment.php?follow_up_for=<?php echo $appt['Appointment_ID']; ?>&doctor_id=<?php echo $appt['Doctor_ID']; ?>&clinic_id=<?php echo $appt['Clinic_ID']; ?>"
                                           class="action-button followup">Book Follow-up</a>
                                    <?php endif; ?>

                                    <?php if (!$is_future_scheduled && !($is_past_or_completed && $status !== 'Cancelled')): // Diğer durumlar ?>
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