<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php"); exit;
}
$doctor_id = $_SESSION['user_id'];

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['test_id'], $_POST['patient_id'])) {

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $test_id = filter_var($_POST['test_id'], FILTER_VALIDATE_INT);
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT); // For redirection
    $default_test_result = 'Pending'; // Default result when assigning

    // Basic validation
    if ($appointment_id === false || $test_id === false || $patient_id === false || $test_id <= 0) { // Test ID 0 might be invalid ('No Test')
        $_SESSION['manage_patient_feedback'] = "Invalid input provided for test assignment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        // Redirect back to manage patient page if possible
        $redirect_url = $appointment_id && $patient_id ? "manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id" : "view_appointments.php";
        header("Location: " . $redirect_url . "#tests"); // Redirect back to tests section
        exit;
    }

    // Security Check: Verify appointment belongs to doctor
    $check_sql = "SELECT `Appointment_ID` FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Doctor_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $is_authorized = ($check_result->num_rows === 1);
    $check_stmt->close();


    if ($is_authorized) {
        // Check if this test already exists for this appointment
        // Use correct table name 'Appointment_Test' from dump
        $duplicate_sql = "SELECT COUNT(*) as count FROM `Appointment_Test` WHERE `Appointment_ID` = ? AND `Test_ID` = ?";
        $dup_stmt = $conn->prepare($duplicate_sql);
        $dup_stmt->bind_param("ii", $appointment_id, $test_id);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result()->fetch_assoc();
        $dup_stmt->close();

        if ($dup_result['count'] == 0) {
            // Insert the new test assignment with 'Pending' result
            // Use correct table name 'Appointment_Test' and column 'Test_Result'
            $insert_sql = "INSERT INTO `Appointment_Test` (`Appointment_ID`, `Test_ID`, `Test_Result`) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);

            if ($insert_stmt) {
                $insert_stmt->bind_param("iis", $appointment_id, $test_id, $default_test_result);
                if ($insert_stmt->execute()) {
                    $_SESSION['manage_patient_feedback'] = "Test assigned successfully (Result: Pending).";
                    $_SESSION['manage_patient_feedback_type'] = "success";
                } else {
                    $_SESSION['manage_patient_feedback'] = "Failed to assign test: " . $insert_stmt->error;
                    $_SESSION['manage_patient_feedback_type'] = "error";
                }
                $insert_stmt->close();
            } else {
                $_SESSION['manage_patient_feedback'] = "Error preparing test assignment statement: " . $conn->error;
                $_SESSION['manage_patient_feedback_type'] = "error";
            }
        } else {
             $_SESSION['manage_patient_feedback'] = "This test has already been assigned to this appointment.";
             $_SESSION['manage_patient_feedback_type'] = "error"; // Or 'warning'
        }

    } else {
        $_SESSION['manage_patient_feedback'] = "Permission denied to modify this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
    }

    $conn->close();

    // Redirect back to the manage patient page, focusing on the tests section
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#tests");
    exit;

} else {
    $_SESSION['manage_patient_feedback'] = "Invalid request.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php"); // Redirect to appointment list if accessed incorrectly
    exit;
}
?>