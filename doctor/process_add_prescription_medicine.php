<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php"); exit;
}
$doctor_id = $_SESSION['user_id'];

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['medicine_id'], $_POST['dosage'], $_POST['patient_id'])) {

    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['manage_patient_feedback'] = "Invalid form token. Please try again.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: view_appointments.php");
        exit;
    }

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $treatment_id = filter_var($_POST['medicine_id'], FILTER_VALIDATE_INT);
    $dosage = trim($_POST['dosage']);
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);

    // Validation
    if ($appointment_id === false || $treatment_id === false || $patient_id === false || empty($dosage) || $treatment_id <= 0) {
        $_SESSION['manage_patient_feedback'] = "Invalid input for prescription medicine.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        $redirect_url = $appointment_id && $patient_id ? "manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id" : "view_appointments.php";
        header("Location: " . $redirect_url . "#prescription");
        exit;
    }

    // Security Check: Verify appointment belongs to doctor + patient
    $check_sql = "SELECT `Appointment_ID` FROM `Appointment` WHERE `Appointment_ID` = ? AND `Doctor_ID` = ? AND `Patient_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $appointment_id, $doctor_id, $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $is_authorized = ($check_result->num_rows === 1);
    $check_stmt->close();

    if (!$is_authorized) {
        $_SESSION['manage_patient_feedback'] = "Permission denied to modify this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: view_appointments.php");
        exit;
    }

    // Verify selected treatment is medication type
    $validate_treatment_sql = "SELECT `Medical_Treatment_ID`
                               FROM `Medical_Treatment`
                               WHERE `Medical_Treatment_ID` = ?
                                 AND `Treatment_Type` = 'Medication'";
    $validate_treatment_stmt = $conn->prepare($validate_treatment_sql);
    if (!$validate_treatment_stmt) {
        $_SESSION['manage_patient_feedback'] = "Could not validate medication type.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#prescription");
        exit;
    }
    $validate_treatment_stmt->bind_param("i", $treatment_id);
    $validate_treatment_stmt->execute();
    $validate_treatment_result = $validate_treatment_stmt->get_result();
    if ($validate_treatment_result->num_rows !== 1) {
        $validate_treatment_stmt->close();
        $_SESSION['manage_patient_feedback'] = "Selected treatment is not a medication.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#prescription");
        exit;
    }
    $validate_treatment_stmt->close();

    // Check for duplicate medication-type treatment in this appointment
    $dup_med_sql = "SELECT COUNT(*) as count FROM `Appointment_Treatment` WHERE `Appointment_ID` = ? AND `Medical_Treatment_ID` = ?";
    $dup_med_stmt = $conn->prepare($dup_med_sql);
    $dup_med_stmt->bind_param("ii", $appointment_id, $treatment_id);
    $dup_med_stmt->execute();
    $dup_med_res = $dup_med_stmt->get_result()->fetch_assoc();
    $dup_med_stmt->close();

    if ($dup_med_res['count'] == 0) {
        // Insert medication as treatment
        $insert_med_sql = "INSERT INTO `Appointment_Treatment` (`Appointment_ID`, `Medical_Treatment_ID`, `Dosage`) VALUES (?, ?, ?)";
        $insert_med_stmt = $conn->prepare($insert_med_sql);
        if ($insert_med_stmt) {
            $insert_med_stmt->bind_param("iis", $appointment_id, $treatment_id, $dosage);
            if ($insert_med_stmt->execute()) {
                $_SESSION['manage_patient_feedback'] = "Medicine added to prescription successfully.";
                $_SESSION['manage_patient_feedback_type'] = "success";
            } else {
                $_SESSION['manage_patient_feedback'] = "Failed to add medicine: " . $insert_med_stmt->error;
                $_SESSION['manage_patient_feedback_type'] = "error";
            }
            $insert_med_stmt->close();
        } else {
            $_SESSION['manage_patient_feedback'] = "Error preparing medicine insert statement.";
            $_SESSION['manage_patient_feedback_type'] = "error";
        }
    } else {
        $_SESSION['manage_patient_feedback'] = "This medicine is already added to this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
    }

    $conn->close();
    // Redirect back
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#prescription");
    exit;

} else {
    $_SESSION['manage_patient_feedback'] = "Invalid request.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}
?>