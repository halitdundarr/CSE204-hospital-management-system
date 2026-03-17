<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    $_SESSION['login_error'] = "Please login.";
    header("Location: ../index.php");
    exit;
}

// Check if the form was submitted using POST and appointment ID is present
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id_to_cancel'])) {

    $appointment_id = intval($_POST['appointment_id_to_cancel']);
    $patient_id = $_SESSION['user_id'];
    $cancelled_status = 'Cancelled';
    $required_status = 'Scheduled';

    // Security Check: Verify the appointment belongs to the logged-in patient
    // and is in a cancellable state (Scheduled and in the future/today)
    // *** GÜNCELLENDİ: Tablo ve Sütun Adları ***
    $check_sql = "SELECT `Appointment_ID`, `Status`, `Appointment_Date` FROM `APPOINTMENT`
                  WHERE `Appointment_ID` = ? AND `Patient_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        $_SESSION['cancellation_error'] = "Error preparing verification query.";
        header("Location: view_appointments.php");
        exit;
    }

    $check_stmt->bind_param("ii", $appointment_id, $patient_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();

        // Check status and date
        if ($appointment['Status'] === $required_status && strtotime($appointment['Appointment_Date']) >= strtotime(date('Y-m-d'))) {
            // Appointment is valid for cancellation, proceed with update
            $check_stmt->close(); // Close check statement

            // *** GÜNCELLENDİ: Tablo ve Sütun Adları ***
            $update_sql = "UPDATE `APPOINTMENT` SET `Status` = ? WHERE `Appointment_ID` = ?";
            $update_stmt = $conn->prepare($update_sql);

            if ($update_stmt) {
                $update_stmt->bind_param("si", $cancelled_status, $appointment_id);
                if ($update_stmt->execute()) {
                    if ($update_stmt->affected_rows > 0) {
                        $_SESSION['cancellation_success'] = "Appointment successfully cancelled.";
                    } else {
                        $_SESSION['cancellation_error'] = "Appointment status could not be updated or was already updated.";
                    }
                } else {
                    $_SESSION['cancellation_error'] = "Error cancelling appointment: " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                 $_SESSION['cancellation_error'] = "Error preparing update statement.";
            }
        } else {
             $_SESSION['cancellation_error'] = "This appointment cannot be cancelled (status not 'Scheduled' or date is in the past).";
             $check_stmt->close();
        }
    } else {
         $_SESSION['cancellation_error'] = "Appointment not found or you do not have permission to cancel it.";
         if ($check_stmt) $check_stmt->close();
    }

    $conn->close();
    header("Location: view_appointments.php");
    exit;

} else {
    $_SESSION['cancellation_error'] = "Invalid request method.";
    header("Location: view_appointments.php");
    exit;
}
?>