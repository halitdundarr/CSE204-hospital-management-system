<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}
$admin_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_support_staff_feedback'] = "Invalid form token. Please try again.";
        $_SESSION['admin_support_staff_feedback_type'] = "error";
        header("Location: manage_support_staff.php");
        exit;
    }

    $appointment_id = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
    $staff_id = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);
    $staff_type = trim($_POST['staff_type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($notes === '') {
        $notes = null;
    }

    $errors = [];
    if ($appointment_id === false || $appointment_id <= 0) $errors[] = "Invalid appointment.";
    if ($staff_id === false || $staff_id <= 0) $errors[] = "Invalid staff member.";
    if (!in_array($staff_type, ['Secretary', 'Translator'], true)) $errors[] = "Invalid staff type.";

    $appointment_clinic_id = null;
    if (empty($errors)) {
        $check_appointment = $conn->prepare(
            "SELECT a.`Appointment_ID`, d.`Clinic_ID`
             FROM `Appointment` a
             JOIN `Doctor` d ON a.`Doctor_ID` = d.`Doctor_ID`
             JOIN `Clinic` c ON d.`Clinic_ID` = c.`Clinic_ID`
             WHERE a.`Appointment_ID` = ? AND c.`Admin_ID` = ?"
        );
        if ($check_appointment) {
            $check_appointment->bind_param("ii", $appointment_id, $admin_id);
            $check_appointment->execute();
            $appt_row = $check_appointment->get_result()->fetch_assoc();
            $check_appointment->close();
            if (!$appt_row) {
                $errors[] = "Appointment does not exist or is out of your clinic scope.";
            } else {
                $appointment_clinic_id = (int)$appt_row['Clinic_ID'];
            }
        }
    }

    if (empty($errors)) {
        if ($staff_type === 'Secretary') {
            $staff_check_sql = "SELECT `Secretary_ID` FROM `Secretary` WHERE `Secretary_ID` = ? AND `Admin_ID` = ? AND `Clinic_ID` = ?";
        } else {
            $staff_check_sql = "SELECT `Translator_ID` FROM `Translator` WHERE `Translator_ID` = ? AND `Admin_ID` = ? AND `Clinic_ID` = ?";
        }
        $staff_check_stmt = $conn->prepare($staff_check_sql);
        if ($staff_check_stmt) {
            $staff_check_stmt->bind_param("iii", $staff_id, $admin_id, $appointment_clinic_id);
            $staff_check_stmt->execute();
            $exists = $staff_check_stmt->get_result()->num_rows === 1;
            $staff_check_stmt->close();
            if (!$exists) $errors[] = "Selected staff record is invalid for this appointment clinic.";
        }
    }

    if (empty($errors)) {
        $dup_sql = "SELECT `Assignment_ID` FROM `Appointment_Support_Staff` WHERE `Appointment_ID` = ? AND `Staff_Type` = ? AND `Staff_ID` = ?";
        $dup_stmt = $conn->prepare($dup_sql);
        if ($dup_stmt) {
            $dup_stmt->bind_param("isi", $appointment_id, $staff_type, $staff_id);
            $dup_stmt->execute();
            if ($dup_stmt->get_result()->num_rows > 0) {
                $errors[] = "This staff member is already assigned to the selected appointment.";
            }
            $dup_stmt->close();
        }
    }

    if (!empty($errors)) {
        $_SESSION['admin_support_staff_feedback'] = implode(" ", $errors);
        $_SESSION['admin_support_staff_feedback_type'] = "error";
        $conn->close();
        header("Location: manage_support_staff.php");
        exit;
    }

    $insert_sql = "INSERT INTO `Appointment_Support_Staff` (`Appointment_ID`, `Staff_Type`, `Staff_ID`, `Notes`) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if ($insert_stmt) {
        $insert_stmt->bind_param("isis", $appointment_id, $staff_type, $staff_id, $notes);
        if ($insert_stmt->execute()) {
            $_SESSION['admin_support_staff_feedback'] = "Support staff assignment saved.";
            $_SESSION['admin_support_staff_feedback_type'] = "success";
        } else {
            $_SESSION['admin_support_staff_feedback'] = "Failed to assign support staff: " . $insert_stmt->error;
            $_SESSION['admin_support_staff_feedback_type'] = "error";
        }
        $insert_stmt->close();
    }

    $conn->close();
    header("Location: manage_support_staff.php");
    exit;
}

header("Location: manage_support_staff.php");
exit;
?>
