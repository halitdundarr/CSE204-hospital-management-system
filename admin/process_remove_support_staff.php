<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_support_staff_feedback'] = "Invalid form token. Please try again.";
        $_SESSION['admin_support_staff_feedback_type'] = "error";
        header("Location: manage_support_staff.php");
        exit;
    }

    $assignment_id = filter_var($_POST['assignment_id'] ?? null, FILTER_VALIDATE_INT);
    if ($assignment_id === false || $assignment_id <= 0) {
        $_SESSION['admin_support_staff_feedback'] = "Invalid assignment ID.";
        $_SESSION['admin_support_staff_feedback_type'] = "error";
        header("Location: manage_support_staff.php");
        exit;
    }

    $delete_sql = "DELETE FROM `Appointment_Support_Staff` WHERE `Assignment_ID` = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $assignment_id);
        if (!$delete_stmt->execute()) {
            $_SESSION['admin_support_staff_feedback'] = "Database error while removing assignment: " . $delete_stmt->error;
            $_SESSION['admin_support_staff_feedback_type'] = "error";
        } elseif ($delete_stmt->affected_rows > 0) {
            audit_log_action(
                $conn,
                'admin',
                (int)$_SESSION['user_id'],
                'ADMIN_REMOVE_SUPPORT_STAFF_ASSIGNMENT',
                'Appointment_Support_Staff',
                $assignment_id,
                ['assignment_id' => $assignment_id]
            );
            $_SESSION['admin_support_staff_feedback'] = "Support staff assignment removed.";
            $_SESSION['admin_support_staff_feedback_type'] = "success";
        } else {
            $_SESSION['admin_support_staff_feedback'] = "Assignment not found or already removed.";
            $_SESSION['admin_support_staff_feedback_type'] = "error";
        }
        $delete_stmt->close();
    } else {
        $_SESSION['admin_support_staff_feedback'] = "Error preparing remove query.";
        $_SESSION['admin_support_staff_feedback_type'] = "error";
    }

    $conn->close();
    header("Location: manage_support_staff.php");
    exit;
}

header("Location: manage_support_staff.php");
exit;
?>
