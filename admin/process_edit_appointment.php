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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['appointment_date'], $_POST['appointment_time'], $_POST['doctor_id'])) {

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $new_date = trim($_POST['appointment_date']);
    $new_time = trim($_POST['appointment_time']);
    $doctor_id = filter_var($_POST['doctor_id'], FILTER_VALIDATE_INT);

    // Feedback redirect URL (back to edit page on error)
    $feedback_redirect_url = "edit_appointment.php?appointment_id=" . $appointment_id;

    // Validation
    if ($appointment_id === false || $doctor_id === false || empty($new_date) || empty($new_time)) {
        $_SESSION['admin_edit_appointment_feedback'] = "Invalid input provided.";
        $_SESSION['admin_edit_appointment_feedback_type'] = "error";
        header("Location: " . $feedback_redirect_url);
        exit;
    }
     if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) { /* ... date format error ... */ header("Location: " . $feedback_redirect_url); exit; }
     if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $new_time)) { /* ... time format error ... */ header("Location: " . $feedback_redirect_url); exit; }
     $new_time_db = date('H:i:s', strtotime($new_time));


    // --- Conflict Check ---
    $conflict_sql = "SELECT `Appointment_ID` FROM `APPOINTMENT`
                     WHERE `Doctor_ID` = ? AND `Appointment_Date` = ? AND `Appointment_Time` = ?
                     AND `Appointment_ID` != ? AND `Status` != 'Cancelled'";
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("issi", $doctor_id, $new_date, $new_time_db, $appointment_id);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();

    if ($conflict_result->num_rows > 0) {
        $_SESSION['admin_edit_appointment_feedback'] = "Conflict: The doctor already has an appointment scheduled at this date and time.";
        $_SESSION['admin_edit_appointment_feedback_type'] = "error";
        $conflict_stmt->close();
        $conn->close();
        header("Location: " . $feedback_redirect_url);
        exit;
    }
    $conflict_stmt->close();
    // --- End Conflict Check ---


    // Prepare UPDATE statement
    $update_sql = "UPDATE `APPOINTMENT` SET `Appointment_Date` = ?, `Appointment_Time` = ? WHERE `Appointment_ID` = ?";
    $update_stmt = $conn->prepare($update_sql);

    if ($update_stmt) {
        $update_stmt->bind_param("ssi", $new_date, $new_time_db, $appointment_id);
        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                 $_SESSION['admin_manage_appointment_feedback'] = "Appointment ID: $appointment_id updated successfully."; // Feedback for the list page
                 $_SESSION['admin_manage_appointment_feedback_type'] = "success";
                 header("Location: manage_appointments.php"); // Redirect to list on success
                 exit;
            } else {
                 $_SESSION['admin_edit_appointment_feedback'] = "No changes detected or update failed."; // Feedback for edit page
                 $_SESSION['admin_edit_appointment_feedback_type'] = "error";
            }
        } else {
            $_SESSION['admin_edit_appointment_feedback'] = "Error updating appointment: " . $update_stmt->error;
            $_SESSION['admin_edit_appointment_feedback_type'] = "error";
        }
        $update_stmt->close();
    } else {
         $_SESSION['admin_edit_appointment_feedback'] = "Error preparing update statement: " . $conn->error;
         $_SESSION['admin_edit_appointment_feedback_type'] = "error";
    }

    $conn->close();
    // Redirect back to edit page if update failed or no changes
    header("Location: " . $feedback_redirect_url);
    exit;

} else {
    $_SESSION['admin_manage_appointment_feedback'] = "Invalid request.";
    $_SESSION['admin_manage_appointment_feedback_type'] = "error";
    header("Location: manage_appointments.php"); // Redirect to list
    exit;
}
?>