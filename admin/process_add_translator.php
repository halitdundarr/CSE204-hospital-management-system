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
        header("Location: add_translator.php");
        exit;
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $clinic_id = filter_var($_POST['clinic_id'] ?? null, FILTER_VALIDATE_INT);

    $errors = [];
    if ($first_name === '') $errors[] = "First name is required.";
    if ($last_name === '') $errors[] = "Last name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if ($phone === '') $errors[] = "Phone is required.";
    if ($language === '') $errors[] = "Language is required.";
    if ($clinic_id === false || $clinic_id <= 0) $errors[] = "Please select a valid clinic.";

    if (empty($errors)) {
        $clinic_check_sql = "SELECT `Clinic_ID` FROM `Clinic` WHERE `Clinic_ID` = ? AND `Admin_ID` = ?";
        $clinic_check_stmt = $conn->prepare($clinic_check_sql);
        if ($clinic_check_stmt) {
            $clinic_check_stmt->bind_param("ii", $clinic_id, $admin_id);
            $clinic_check_stmt->execute();
            if ($clinic_check_stmt->get_result()->num_rows !== 1) {
                $errors[] = "Selected clinic is not available for your admin account.";
            }
            $clinic_check_stmt->close();
        }
    }

    $dup_sql = "SELECT `Translator_ID` FROM `Translator` WHERE `Translator_Email` = ?";
    $dup_stmt = $conn->prepare($dup_sql);
    if ($dup_stmt) {
        $dup_stmt->bind_param("s", $email);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result();
        if ($dup_result->num_rows > 0) {
            $errors[] = "This email is already used by another translator.";
        }
        $dup_stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['admin_feedback'] = implode(" ", $errors);
        $_SESSION['admin_feedback_type'] = "error";
        header("Location: add_translator.php");
        exit;
    }

    $insert_sql = "INSERT INTO `Translator`
        (`Translator_First_Name`, `Translator_Last_Name`, `Translator_Email`, `Translator_Phone`, `Language`, `Clinic_ID`, `Admin_ID`)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if ($insert_stmt) {
        $insert_stmt->bind_param("sssssii", $first_name, $last_name, $email, $phone, $language, $clinic_id, $admin_id);
        if ($insert_stmt->execute()) {
            $_SESSION['admin_feedback'] = "Translator added successfully.";
            $_SESSION['admin_feedback_type'] = "success";
        } else {
            $_SESSION['admin_feedback'] = "Failed to add translator: " . $insert_stmt->error;
            $_SESSION['admin_feedback_type'] = "error";
        }
        $insert_stmt->close();
    } else {
        $_SESSION['admin_feedback'] = "Error preparing translator insert.";
        $_SESSION['admin_feedback_type'] = "error";
    }

    $conn->close();
    header("Location: add_translator.php");
    exit;
}

header("Location: add_translator.php");
exit;
?>
