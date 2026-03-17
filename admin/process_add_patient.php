<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}

// Check POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve data
    $patient_id_tc = trim($_POST['tc_kimlik_no']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $blood_type = trim($_POST['blood_type']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password']; // *** Düz metin şifre ***

    // Validation
    $errors = [];
    if (empty($patient_id_tc) || !ctype_digit($patient_id_tc) || strlen($patient_id_tc) !== 11) $errors[] = "TC Kimlik No must be exactly 11 digits.";
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($dob)) $errors[] = "Date of birth is required.";
    if (empty($blood_type)) $errors[] = "Blood type is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($password)) $errors[] = "Password is required.";

    // Check uniqueness only if basic validation passed
    if (empty($errors)) {
        // Check if TC Kimlik No (Patient_ID) already exists
        $check_id_sql = "SELECT `Patient_ID` FROM `PATIENT` WHERE `Patient_ID` = ?";
        $check_id_stmt = $conn->prepare($check_id_sql);
        $check_id_stmt->bind_param("s", $patient_id_tc);
        $check_id_stmt->execute();
        $check_id_result = $check_id_stmt->get_result();
        if ($check_id_result->num_rows > 0) { $errors[] = "This TC Kimlik No (Patient ID) is already registered."; }
        $check_id_stmt->close();

        // Check if phone already exists
        $check_phone_sql = "SELECT `Patient_ID` FROM `PATIENT` WHERE `Patient_Phone` = ?";
        $check_phone_stmt = $conn->prepare($check_phone_sql);
        $check_phone_stmt->bind_param("s", $phone);
        $check_phone_stmt->execute();
        $check_phone_result = $check_phone_stmt->get_result();
        if ($check_phone_result->num_rows > 0) { $errors[] = "This phone number is already registered for another patient."; }
        $check_phone_stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['admin_feedback'] = implode("<br>", $errors);
        $_SESSION['admin_feedback_type'] = "error";
        header("Location: add_patient.php");
        exit;
    }

    // *** ŞİFRE HASHLEME KODU KALDIRILDI ***
    // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // if ($hashed_password === false) { ... } // Hata kontrolü de kaldırıldı

    // Prepare INSERT statement - Patient_ID ve düz metin şifre ile
    $insert_sql = "INSERT INTO `PATIENT` (`Patient_ID`, `Patient_First_Name`, `Patient_Last_Name`, `Patient_Gender`, `Patient_DOB`, `Patient_Blood_Type`, `Patient_Phone`, `Patient_Address`, `Patient_Password`)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);

    if ($insert_stmt) {
        // Bind parameters - Son parametre artık $hashed_password yerine $password
        $insert_stmt->bind_param("sssssssss", // Tipler aynı kalabilir (hepsi string)
                                 $patient_id_tc,
                                 $first_name,
                                 $last_name,
                                 $gender,
                                 $dob,
                                 $blood_type,
                                 $phone,
                                 $address,
                                 $password // *** Düz metin şifre bağlandı ***
                                );

        if ($insert_stmt->execute()) {
            $_SESSION['admin_feedback'] = "Patient added successfully with ID: " . htmlspecialchars($patient_id_tc);
            $_SESSION['admin_feedback_type'] = "success";
        } else {
            $_SESSION['admin_feedback'] = "Failed to add patient: " . $insert_stmt->error;
            $_SESSION['admin_feedback_type'] = "error";
        }
        $insert_stmt->close();
    } else {
        $_SESSION['admin_feedback'] = "Error preparing statement: " . $conn->error;
        $_SESSION['admin_feedback_type'] = "error";
    }

    $conn->close();
    header("Location: add_patient.php");
    exit;

} else {
    header("Location: add_patient.php");
    exit;
}
?>