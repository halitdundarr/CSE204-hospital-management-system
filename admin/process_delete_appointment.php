<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id_to_delete'])) {

    $appointment_id = filter_var($_POST['appointment_id_to_delete'], FILTER_VALIDATE_INT);

    if ($appointment_id === false || $appointment_id <= 0) {
        $_SESSION['admin_manage_appointment_feedback'] = "Invalid Appointment ID for deletion.";
        $_SESSION['admin_manage_appointment_feedback_type'] = "error";
        header("Location: manage_appointments.php");
        exit;
    }

    // *** DURUM KONTROLÜ KALDIRILDI ***
    /*
    $required_status_for_delete = 'Cancelled';
    $status_check_sql = "SELECT `Status` FROM `APPOINTMENT` WHERE `Appointment_ID` = ?";
    $status_stmt = $conn->prepare($status_check_sql);
    $can_delete = false;
    if ($status_stmt) { ... check status ... }
    */
    $can_delete = true; // Admin her zaman silebilir (yeni kural)


    // Proceed with deletion only if allowed (now always true for admin)
    if ($can_delete) {
        // Prepare DELETE statement
        $sql = "DELETE FROM `APPOINTMENT` WHERE `Appointment_ID` = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $appointment_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['admin_manage_appointment_feedback'] = "Appointment ID: $appointment_id deleted successfully.";
                    $_SESSION['admin_manage_appointment_feedback_type'] = "success";
                } else {
                     $_SESSION['admin_manage_appointment_feedback'] = "Appointment ID: $appointment_id not found or could not be deleted.";
                     $_SESSION['admin_manage_appointment_feedback_type'] = "error";
                }
            } else {
                // Hata durumunda daha fazla detay loglanabilir veya gösterilebilir
                // error_log("Delete failed for Appt ID $appointment_id: " . $stmt->error);
                 $_SESSION['admin_manage_appointment_feedback'] = "Error deleting appointment. Please check dependencies or logs."; // Genel hata
                 $_SESSION['admin_manage_appointment_feedback_type'] = "error";
            }
            $stmt->close();
        } else {
             $_SESSION['admin_manage_appointment_feedback'] = "Error preparing delete statement: " . $conn->error;
             $_SESSION['admin_manage_appointment_feedback_type'] = "error";
        }
    } // End if ($can_delete) - Artık her zaman true

    $conn->close();

} else {
     $_SESSION['admin_manage_appointment_feedback'] = "Invalid request method.";
     $_SESSION['admin_manage_appointment_feedback_type'] = "error";
}

// Redirect back to the management page
header("Location: manage_appointments.php");
exit;
?>