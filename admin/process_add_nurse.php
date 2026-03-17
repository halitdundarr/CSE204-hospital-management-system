<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}
// Get the logged-in admin's ID to assign as the manager
$admin_id = $_SESSION['user_id'];

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve and sanitize basic data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = trim($_POST['gender']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $clinic_id = filter_var($_POST['clinic_id'], FILTER_VALIDATE_INT);

    // Basic Validation
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if ($clinic_id === false || $clinic_id <= 0) $errors[] = "Please select a valid clinic.";

    // Check if email or phone already exists
    $check_sql = "SELECT `Nurse_ID` FROM `NURSE` WHERE `Nurse_Email` = ? OR `Nurse_Phone` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $email, $phone);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $errors[] = "This email or phone number is already registered for another nurse.";
    }
    $check_stmt->close();

    if (!empty($errors)) {
        // If errors, store them and redirect back
        $_SESSION['admin_feedback'] = implode("<br>", $errors);
        $_SESSION['admin_feedback_type'] = "error";
        header("Location: add_nurse.php");
        exit;
    }

    // Prepare INSERT statement
    // Use correct table name `NURSE` and column names from dump
    $insert_sql = "INSERT INTO `NURSE` (`Nurse_First_Name`, `Nurse_Last_Name`, `Nurse_Gender`, `Nurse_Email`, `Nurse_Phone`, `Clinic_ID`, `Admin_ID`)
                   VALUES (?, ?, ?, ?, ?, ?, ?)"; // 7 placeholders
    $insert_stmt = $conn->prepare($insert_sql);

    if ($insert_stmt) {
        // Bind parameters (s=string, i=integer)
        $insert_stmt->bind_param("sssssii",
                                 $first_name,
                                 $last_name,
                                 $gender,
                                 $email,
                                 $phone,
                                 $clinic_id,
                                 $admin_id // Use logged-in admin ID
                                );

        if ($insert_stmt->execute()) {
            $_SESSION['admin_feedback'] = "Nurse added successfully!";
            $_SESSION['admin_feedback_type'] = "success";
        } else {
            $_SESSION['admin_feedback'] = "Failed to add nurse: " . $insert_stmt->error;
            $_SESSION['admin_feedback_type'] = "error";
        }
        $insert_stmt->close();
    } else {
        $_SESSION['admin_feedback'] = "Error preparing statement: " . $conn->error;
        $_SESSION['admin_feedback_type'] = "error";
    }

    $conn->close();
    header("Location: add_nurse.php"); // Redirect back to the form
    exit;

} else {
    // Redirect if not POST
    header("Location: add_nurse.php");
    exit;
}
?>