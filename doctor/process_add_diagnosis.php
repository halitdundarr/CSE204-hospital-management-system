<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    // Redirect non-doctors
    header("Location: ../index.php");
    exit;
}
$doctor_id = $_SESSION['user_id'];

// Check if the form was submitted using POST and required fields are present
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['diagnosis_id'], $_POST['patient_id'])) {

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $diagnosis_id = filter_var($_POST['diagnosis_id'], FILTER_VALIDATE_INT);
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT); // Get patient_id for redirection

    // Basic validation
    if ($appointment_id === false || $diagnosis_id === false || $patient_id === false || $diagnosis_id <= 0) { // Diagnosis ID 0 might be invalid
        $_SESSION['manage_patient_feedback'] = "Invalid input provided.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        // Redirect back to manage patient page if possible
        $redirect_url = $appointment_id && $patient_id ? "manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id" : "view_appointments.php";
        header("Location: " . $redirect_url);
        exit;
    }

    // Security Check: Verify the appointment belongs to the logged-in doctor
    $check_sql = "SELECT `Appointment_ID` FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Doctor_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 1) {
        // Doctor is authorized, proceed to insert (checking for duplicates first is good practice)

        // Check if this diagnosis already exists for this appointment
        $duplicate_sql = "SELECT COUNT(*) as count FROM `Appointment_Diagnosis` WHERE `Appointment_ID` = ? AND `Diagnosis_ID` = ?";
        $dup_stmt = $conn->prepare($duplicate_sql);
        $dup_stmt->bind_param("ii", $appointment_id, $diagnosis_id);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result()->fetch_assoc();
        $dup_stmt->close();

        if ($dup_result['count'] == 0) {
            // Insert the new diagnosis association
            $insert_sql = "INSERT INTO `Appointment_Diagnosis` (`Appointment_ID`, `Diagnosis_ID`) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);

            if ($insert_stmt) {
                $insert_stmt->bind_param("ii", $appointment_id, $diagnosis_id);
                if ($insert_stmt->execute()) {
                    $_SESSION['manage_patient_feedback'] = "Diagnosis added successfully.";
                    $_SESSION['manage_patient_feedback_type'] = "success";
                } else {
                    $_SESSION['manage_patient_feedback'] = "Failed to add diagnosis: " . $insert_stmt->error;
                    $_SESSION['manage_patient_feedback_type'] = "error";
                }
                $insert_stmt->close();
            } else {
                $_SESSION['manage_patient_feedback'] = "Error preparing diagnosis insert statement.";
                $_SESSION['manage_patient_feedback_type'] = "error";
            }
        } else {
             $_SESSION['manage_patient_feedback'] = "This diagnosis has already been added to this appointment.";
             $_SESSION['manage_patient_feedback_type'] = "error"; // Or use 'warning'
        }

    } else {
         // Doctor not authorized for this appointment
        $_SESSION['manage_patient_feedback'] = "You do not have permission to modify this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
    }
    $check_stmt->close();
    $conn->close();

    // Redirect back to the manage patient page
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id");
    exit;

} else {
    // Redirect if accessed directly or without required POST data
    $_SESSION['manage_patient_feedback'] = "Invalid request.";
     $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php"); // Redirect to appointment list
    exit;
}
?>