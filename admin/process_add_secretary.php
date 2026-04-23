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
        $_SESSION['admin_feedback'] = "Invalid form token. Please try again.";
        $_SESSION['admin_feedback_type'] = "error";
        header("Location: add_secretary.php");
        exit;
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $clinic_id = filter_var($_POST['clinic_id'] ?? null, FILTER_VALIDATE_INT);

    $errors = [];
    if ($first_name === '') $errors[] = "First name is required.";
    if ($last_name === '') $errors[] = "Last name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if ($phone === '') $errors[] = "Phone is required.";
    if ($clinic_id === false || $clinic_id <= 0) $errors[] = "Please select a valid clinic.";

    $dup_sql = "SELECT `Secretary_ID` FROM `Secretary` WHERE `Secretary_Email` = ? OR `Secretary_Phone` = ?";
    $dup_stmt = $conn->prepare($dup_sql);
    if ($dup_stmt) {
        $dup_stmt->bind_param("ss", $email, $phone);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result();
        if ($dup_result->num_rows > 0) {
            $errors[] = "This email or phone is already used by another secretary.";
        }
        $dup_stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['admin_feedback'] = implode(" ", $errors);
        $_SESSION['admin_feedback_type'] = "error";
        header("Location: add_secretary.php");
        exit;
    }

    $insert_sql = "INSERT INTO `Secretary`
        (`Secretary_First_Name`, `Secretary_Last_Name`, `Secretary_Email`, `Secretary_Phone`, `Clinic_ID`, `Admin_ID`)
        VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if ($insert_stmt) {
        $insert_stmt->bind_param("ssssii", $first_name, $last_name, $email, $phone, $clinic_id, $admin_id);
        if ($insert_stmt->execute()) {
            $_SESSION['admin_feedback'] = "Secretary added successfully.";
            $_SESSION['admin_feedback_type'] = "success";
        } else {
            $_SESSION['admin_feedback'] = "Failed to add secretary: " . $insert_stmt->error;
            $_SESSION['admin_feedback_type'] = "error";
        }
        $insert_stmt->close();
    } else {
        $_SESSION['admin_feedback'] = "Error preparing secretary insert.";
        $_SESSION['admin_feedback_type'] = "error";
    }

    $conn->close();
    header("Location: add_secretary.php");
    exit;
}

header("Location: add_secretary.php");
exit;
?>
