<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    $_SESSION['login_error'] = "Please login.";
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['appointment_id_to_delete'])) {
    $_SESSION['deletion_error'] = "Invalid request method.";
    header("Location: view_appointments.php");
    exit;
}

if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['deletion_error'] = "Invalid form token. Please try again.";
    header("Location: view_appointments.php");
    exit;
}

$appointment_id = intval($_POST['appointment_id_to_delete']);
$patient_id = intval($_SESSION['user_id']);

if ($appointment_id <= 0) {
    $_SESSION['deletion_error'] = "Invalid appointment ID.";
    header("Location: view_appointments.php");
    exit;
}

$allowed_statuses = ['Scheduled', 'Cancelled'];
$today = date('Y-m-d');

$check_sql = "SELECT `Appointment_ID`, `Status`, `Appointment_Date`
              FROM `APPOINTMENT`
              WHERE `Appointment_ID` = ? AND `Patient_ID` = ?";
$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    $_SESSION['deletion_error'] = "Error preparing verification query.";
    header("Location: view_appointments.php");
    exit;
}

$check_stmt->bind_param("ii", $appointment_id, $patient_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows !== 1) {
    $check_stmt->close();
    $conn->close();
    $_SESSION['deletion_error'] = "Appointment not found or you do not have permission to delete it.";
    header("Location: view_appointments.php");
    exit;
}

$appointment = $result->fetch_assoc();
$check_stmt->close();

$is_allowed_status = in_array($appointment['Status'], $allowed_statuses, true);
$is_non_historical = strtotime($appointment['Appointment_Date']) >= strtotime($today);

if (!$is_allowed_status || !$is_non_historical) {
    $conn->close();
    $_SESSION['deletion_error'] = "Only upcoming appointments in Scheduled or Cancelled status can be permanently deleted.";
    header("Location: view_appointments.php");
    exit;
}

$delete_sql = "DELETE FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Patient_ID` = ?";
$delete_stmt = $conn->prepare($delete_sql);

if (!$delete_stmt) {
    $conn->close();
    $_SESSION['deletion_error'] = "Error preparing delete statement.";
    header("Location: view_appointments.php");
    exit;
}

$delete_stmt->bind_param("ii", $appointment_id, $patient_id);
if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
    audit_log_action(
        $conn,
        'patient',
        $patient_id,
        'DELETE_APPOINTMENT',
        'APPOINTMENT',
        $appointment_id,
        [
            'status' => $appointment['Status'],
            'appointment_date' => $appointment['Appointment_Date']
        ]
    );
    $_SESSION['deletion_success'] = "Appointment permanently deleted.";
} else {
    $_SESSION['deletion_error'] = "Appointment could not be deleted.";
}

$delete_stmt->close();
$conn->close();

header("Location: view_appointments.php");
exit;
?>
