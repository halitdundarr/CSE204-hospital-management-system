<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role - PATIENT access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}
$patient_id = $_SESSION['user_id']; // Logged in patient ID

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['appointment_date'], $_POST['appointment_time'], $_POST['doctor_id'])) {

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $new_date = trim($_POST['appointment_date']);
    $new_time = trim($_POST['appointment_time']);
    $doctor_id = filter_var($_POST['doctor_id'], FILTER_VALIDATE_INT); // Doctor ID for conflict check

    $feedback_redirect_url = "edit_appointment.php?appointment_id=" . $appointment_id; // Redirect back to edit page on error

    // Basic validation
    if ($appointment_id === false || $doctor_id === false || empty($new_date) || empty($new_time)) {
        $_SESSION['patient_edit_appointment_feedback'] = "Invalid input provided.";
        $_SESSION['patient_edit_appointment_feedback_type'] = "error";
        header("Location: " . $feedback_redirect_url);
        exit;
    }

     // Validate date/time format and ensure it's not in the past
     if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date) || strtotime($new_date) < strtotime(date('Y-m-d'))) {
         $_SESSION['patient_edit_appointment_feedback'] = "Invalid date format or date is in the past.";
         $_SESSION['patient_edit_appointment_feedback_type'] = "error";
         header("Location: " . $feedback_redirect_url);
         exit;
    }
     if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $new_time)) {
         $_SESSION['patient_edit_appointment_feedback'] = "Invalid time format.";
         $_SESSION['patient_edit_appointment_feedback_type'] = "error";
         header("Location: " . $feedback_redirect_url);
         exit;
    }
     $new_time_db = date('H:i:s', strtotime($new_time)); // Ensure HH:MM:SS

    // --- Security Check: Verify appointment belongs to this patient ---
    $verify_sql = "SELECT `Appointment_ID`, `Status` FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Patient_ID` = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $appointment_id, $patient_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows !== 1) {
         $_SESSION['patient_edit_appointment_feedback'] = "Appointment not found or you do not have permission to edit it.";
         $_SESSION['patient_edit_appointment_feedback_type'] = "error";
         $verify_stmt->close();
         $conn->close();
         header("Location: view_appointments.php"); // Redirect to list if not found
         exit;
    }
    $current_appointment = $verify_result->fetch_assoc();
    $verify_stmt->close();

    // Check if status allows editing (must be 'Scheduled')
     if($current_appointment['Status'] !== 'Scheduled') {
        $_SESSION['patient_edit_appointment_feedback'] = "Only scheduled appointments can be rescheduled.";
        $_SESSION['patient_edit_appointment_feedback_type'] = "error";
        header("Location: " . $feedback_redirect_url);
        exit;
     }


    // --- Conflict Check ---
    $conflict_sql = "SELECT `Appointment_ID` FROM `APPOINTMENT`
                     WHERE `Doctor_ID` = ? AND `Appointment_Date` = ? AND `Appointment_Time` = ?
                     AND `Appointment_ID` != ? AND `Status` != 'Cancelled'";
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("issi", $doctor_id, $new_date, $new_time_db, $appointment_id);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();

    if ($conflict_result->num_rows > 0) {
        // Conflict found
        $_SESSION['patient_edit_appointment_feedback'] = "Conflict: The doctor already has an appointment scheduled at this date and time. Please choose another time.";
        $_SESSION['patient_edit_appointment_feedback_type'] = "error";
        $conflict_stmt->close();
        $conn->close();
        header("Location: " . $feedback_redirect_url);
        exit;
    }
    $conflict_stmt->close();
    // --- End Conflict Check ---


    // Prepare UPDATE statement - Add Patient_ID to WHERE for extra check
    $update_sql = "UPDATE `APPOINTMENT` SET `Appointment_Date` = ?, `Appointment_Time` = ? WHERE `Appointment_ID` = ? AND `Patient_ID` = ?";
    $update_stmt = $conn->prepare($update_sql);

    if ($update_stmt) {
        $update_stmt->bind_param("ssii", $new_date, $new_time_db, $appointment_id, $patient_id);
        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                 $_SESSION['patient_edit_appointment_feedback'] = "Appointment updated successfully."; // Feedback for list page
                 $_SESSION['patient_edit_appointment_feedback_type'] = "success";
                 header("Location: view_appointments.php"); // Redirect to list on success
                 exit;
            } else {
                 $_SESSION['patient_edit_appointment_feedback'] = "No changes detected or update failed."; // Feedback for edit page
                 $_SESSION['patient_edit_appointment_feedback_type'] = "error";
            }
        } else {
            $_SESSION['patient_edit_appointment_feedback'] = "Error updating appointment: " . $update_stmt->error;
            $_SESSION['patient_edit_appointment_feedback_type'] = "error";
        }
        $update_stmt->close();
    } else {
         $_SESSION['patient_edit_appointment_feedback'] = "Error preparing update statement: " . $conn->error;
         $_SESSION['patient_edit_appointment_feedback_type'] = "error";
    }

    $conn->close();
    // Redirect back to edit page if update failed or no changes
    header("Location: " . $feedback_redirect_url);
    exit;

} else {
    $_SESSION['patient_edit_appointment_feedback'] = "Invalid request."; // Use correct session key
    $_SESSION['patient_edit_appointment_feedback_type'] = "error";
    header("Location: view_appointments.php"); // Redirect to list
    exit;
}
?>