<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id_to_cancel'])) {

    $appointment_id = filter_var($_POST['appointment_id_to_cancel'], FILTER_VALIDATE_INT);
    $cancelled_status = 'Cancelled';
    $required_status = 'Scheduled'; // Only cancel if currently scheduled

    if ($appointment_id === false || $appointment_id <= 0) {
        $_SESSION['admin_manage_appointment_feedback'] = "Invalid Appointment ID.";
        $_SESSION['admin_manage_appointment_feedback_type'] = "error";
        header("Location: manage_appointments.php");
        exit;
    }

    // Prepare UPDATE statement
    $sql = "UPDATE `APPOINTMENT` SET `Status` = ? WHERE `Appointment_ID` = ? AND `Status` = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sis", $cancelled_status, $appointment_id, $required_status);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['admin_manage_appointment_feedback'] = "Appointment ID: $appointment_id cancelled successfully.";
                $_SESSION['admin_manage_appointment_feedback_type'] = "success";
            } else {
                // Could be that the appointment wasn't found or wasn't 'Scheduled'
                 $_SESSION['admin_manage_appointment_feedback'] = "Appointment ID: $appointment_id could not be cancelled (maybe already cancelled or completed?).";
                 $_SESSION['admin_manage_appointment_feedback_type'] = "error";
            }
        } else {
            $_SESSION['admin_manage_appointment_feedback'] = "Error cancelling appointment: " . $stmt->error;
             $_SESSION['admin_manage_appointment_feedback_type'] = "error";
        }
        $stmt->close();
    } else {
         $_SESSION['admin_manage_appointment_feedback'] = "Error preparing cancellation statement: " . $conn->error;
         $_SESSION['admin_manage_appointment_feedback_type'] = "error";
    }
    $conn->close();

} else {
     $_SESSION['admin_manage_appointment_feedback'] = "Invalid request method.";
     $_SESSION['admin_manage_appointment_feedback_type'] = "error";
}

// Redirect back to the management page
header("Location: manage_appointments.php");
exit;
?>