<?php
session_start();
require_once 'includes/db_connect.php'; // Assuming db_connect is in includes folder relative to this script

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Retrieve Data ---
    $patient_id_tc = trim($_POST['patient_id_tc']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password']; // *** Düz metin şifre ***
    $confirm_password = $_POST['confirm_password'];
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $blood_type = trim($_POST['blood_type']);
    $address = trim($_POST['address']);

    // Store submitted data in session for sticky form on error
    $_SESSION['register_form_data'] = $_POST;

    // --- Validation ---
    $errors = [];
    if (empty($patient_id_tc) || !ctype_digit($patient_id_tc) || strlen($patient_id_tc) !== 11) $errors[] = "TC Kimlik No must be exactly 11 digits.";
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($dob)) $errors[] = "Date of birth is required.";
    if (empty($blood_type)) $errors[] = "Blood type is required.";
    if (empty($address)) $errors[] = "Address is required.";

    // --- Uniqueness Checks ---
    if (empty($errors)) {
        // Check TC
        $check_id_sql = "SELECT `Patient_ID` FROM `PATIENT` WHERE `Patient_ID` = ?";
        $check_id_stmt = $conn->prepare($check_id_sql);
        $check_id_stmt->bind_param("s", $patient_id_tc);
        $check_id_stmt->execute();
        $check_id_result = $check_id_stmt->get_result();
        if ($check_id_result->num_rows > 0) { $errors[] = "This TC Kimlik No (Patient ID) is already registered."; }
        $check_id_stmt->close();

        // Check Phone
        $check_phone_sql = "SELECT `Patient_ID` FROM `PATIENT` WHERE `Patient_Phone` = ?";
        $check_phone_stmt = $conn->prepare($check_phone_sql);
        $check_phone_stmt->bind_param("s", $phone);
        $check_phone_stmt->execute();
        $check_phone_result = $check_phone_stmt->get_result();
        if ($check_phone_result->num_rows > 0) { $errors[] = "This phone number is already registered."; }
        $check_phone_stmt->close();
    }

    // --- Process Registration or Redirect with Errors ---
    if (!empty($errors)) {
        $_SESSION['register_feedback'] = $errors;
        $_SESSION['register_feedback_type'] = 'error';
        header("Location: register.php");
        exit;
    } else {
        // *** ŞİFRE HASHLEME KODU KALDIRILDI ***
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // if ($hashed_password === false) { ... }

        $insert_sql = "INSERT INTO `PATIENT` (
                            `Patient_ID`, `Patient_First_Name`, `Patient_Last_Name`,
                            `Patient_Gender`, `Patient_DOB`, `Patient_Blood_Type`,
                            `Patient_Phone`, `Patient_Address`, `Patient_Password`
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);

        if ($insert_stmt) {
            // Bind parameters - using plain $password
            $insert_stmt->bind_param("sssssssss",
                                     $patient_id_tc,
                                     $first_name,
                                     $last_name,
                                     $gender,
                                     $dob,
                                     $blood_type,
                                     $phone,
                                     $address,
                                     $password // *** Düz metin şifre ***
                                    );

            if ($insert_stmt->execute()) {
                // SUCCESS! Redirect to login page with success message
                unset($_SESSION['register_form_data']);
                $_SESSION['login_success_message'] = "Registration successful! You can now log in.";
                header("Location: index.php");
                exit;
            } else {
                // Database error
                $_SESSION['register_feedback'] = "Registration failed (DB): " . $insert_stmt->error;
                 $_SESSION['register_feedback_type'] = "error";
            }
            $insert_stmt->close();
        } else {
            // Prepare statement error
            $_SESSION['register_feedback'] = "Registration failed (Prepare): " . $conn->error;
             $_SESSION['register_feedback_type'] = "error";
        }
        $conn->close();
        header("Location: register.php"); // Redirect back to register on failure
        exit;
    }

} else {
    // Redirect if not POST
    header("Location: register.php");
    exit;
}
?>