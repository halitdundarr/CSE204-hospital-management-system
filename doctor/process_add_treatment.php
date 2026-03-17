<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php"); exit;
}
$doctor_id = $_SESSION['user_id'];

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['treatment_id'], $_POST['patient_id'])) {

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $treatment_id = filter_var($_POST['treatment_id'], FILTER_VALIDATE_INT); // Should be Medical_Treatment_ID
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);

    // Basic validation
    if ($appointment_id === false || $treatment_id === false || $patient_id === false || $treatment_id <= 0) { // Treatment ID 0 might be invalid
        $_SESSION['manage_patient_feedback'] = "Invalid input for treatment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        $redirect_url = $appointment_id && $patient_id ? "manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id" : "view_appointments.php";
        header("Location: " . $redirect_url . "#treatments");
        exit;
    }

    // Security Check: Verify appointment belongs to doctor
    $check_sql = "SELECT `Appointment_ID` FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Doctor_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 1) {
        // Doctor authorized

        // Check for duplicates
        $duplicate_sql = "SELECT COUNT(*) as count FROM `Appointment_Treatment` WHERE `Appointment_ID` = ? AND `Medical_Treatment_ID` = ?";
        $dup_stmt = $conn->prepare($duplicate_sql);
        $dup_stmt->bind_param("ii", $appointment_id, $treatment_id);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result()->fetch_assoc();
        $dup_stmt->close();

        if ($dup_result['count'] == 0) {
            // Insert new treatment association - use correct column name Medical_Treatment_ID
            $insert_sql = "INSERT INTO `Appointment_Treatment` (`Appointment_ID`, `Medical_Treatment_ID`) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if ($insert_stmt) {
                $insert_stmt->bind_param("ii", $appointment_id, $treatment_id);
                if ($insert_stmt->execute()) {
                    $_SESSION['manage_patient_feedback'] = "Treatment added successfully.";
                    $_SESSION['manage_patient_feedback_type'] = "success";
                } else {
                    $_SESSION['manage_patient_feedback'] = "Failed to add treatment: " . $insert_stmt->error;
                    $_SESSION['manage_patient_feedback_type'] = "error";
                }
                $insert_stmt->close();
            } else {
                $_SESSION['manage_patient_feedback'] = "Error preparing treatment insert statement.";
                $_SESSION['manage_patient_feedback_type'] = "error";
            }
        } else {
             $_SESSION['manage_patient_feedback'] = "This treatment has already been added to this appointment.";
             $_SESSION['manage_patient_feedback_type'] = "error";
        }
    } else {
        $_SESSION['manage_patient_feedback'] = "Permission denied to modify this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
    }
    $check_stmt->close();
    $conn->close();

    // Redirect back
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#treatments");
    exit;

} else {
    $_SESSION['manage_patient_feedback'] = "Invalid request.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}
?>