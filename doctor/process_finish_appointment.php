<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

$doctor_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['appointment_id'], $_POST['patient_id'])) {
    $_SESSION['manage_patient_feedback'] = "Invalid request.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}

if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['manage_patient_feedback'] = "Invalid form token. Please try again.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}

$appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
$patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);
if ($appointment_id === false || $patient_id === false || $appointment_id <= 0 || $patient_id <= 0) {
    $_SESSION['manage_patient_feedback'] = "Invalid appointment/patient input.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}

$check_sql = "SELECT `Status` FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Doctor_ID` = ? AND `Patient_ID` = ?";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    $_SESSION['manage_patient_feedback'] = "Database error while validating appointment.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}
$check_stmt->bind_param("iii", $appointment_id, $doctor_id, $patient_id);
$check_stmt->execute();
$appt = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$appt) {
    $_SESSION['manage_patient_feedback'] = "Appointment not found or permission denied.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    $conn->close();
    header("Location: view_appointments.php");
    exit;
}

$status = (string)$appt['Status'];
if ($status === 'Cancelled') {
    $_SESSION['manage_patient_feedback'] = "Cancelled appointments cannot be finalized.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    $conn->close();
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#finish-appointment");
    exit;
}
if ($status === 'Completed') {
    $_SESSION['manage_patient_feedback'] = "This appointment is already completed.";
    $_SESSION['manage_patient_feedback_type'] = "success";
    $conn->close();
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#finish-appointment");
    exit;
}

if (mark_appointment_completed_by_doctor($conn, $appointment_id, $doctor_id)) {
    audit_log_action(
        $conn,
        'doctor',
        $doctor_id,
        'COMPLETE_APPOINTMENT',
        'APPOINTMENT',
        $appointment_id,
        ['patient_id' => $patient_id]
    );
    $_SESSION['manage_patient_feedback'] = "Appointment finalized successfully.";
    $_SESSION['manage_patient_feedback_type'] = "success";
} else {
    $_SESSION['manage_patient_feedback'] = "Failed to finalize appointment.";
    $_SESSION['manage_patient_feedback_type'] = "error";
}

$conn->close();
header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#finish-appointment");
exit;
?>
