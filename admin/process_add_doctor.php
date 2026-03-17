<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}
$admin_id = $_SESSION['user_id'];

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = trim($_POST['gender']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $clinic_id = filter_var($_POST['clinic_id'], FILTER_VALIDATE_INT);
    $password = $_POST['password']; // Düz metin şifre

    // Validation
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if ($clinic_id === false || $clinic_id <= 0) $errors[] = "Please select a valid clinic.";
    if (empty($password)) $errors[] = "Password is required.";

    // Check uniqueness only if basic validation passed
    if (empty($errors)) {
        $check_sql = "SELECT `Doctor_ID` FROM `DOCTOR` WHERE `Doctor_Email` = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) { $errors[] = "This email address is already registered for another doctor."; }
        $check_stmt->close();
        // Optionally check phone uniqueness too
    }

    if (!empty($errors)) {
        $_SESSION['admin_feedback'] = implode("<br>", $errors);
        $_SESSION['admin_feedback_type'] = "error";
        header("Location: add_doctor.php");
        exit;
    }

    // Şifre hashleme yok (kullanıcının isteği üzerine)

    // Prepare INSERT statement
    $insert_sql = "INSERT INTO `DOCTOR` (`Doctor_First_Name`, `Doctor_Last_Name`, `Doctor_Gender`, `Doctor_Email`, `Doctor_Phone`, `Clinic_ID`, `Doctor_Password`, `Admin_ID`)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);

    if ($insert_stmt) {
        // *** GÜNCELLENDİ: bind_param tipleri düzeltildi ***
        $insert_stmt->bind_param("sssssisi", // Doğru tipler: 5 string, 1 integer, 1 string, 1 integer
                                 $first_name,
                                 $last_name,
                                 $gender,
                                 $email,
                                 $phone,
                                 $clinic_id,
                                 $password, // Düz metin şifre (string)
                                 $admin_id  // Session'dan gelen admin ID (integer)
                                );

        if ($insert_stmt->execute()) {
            $_SESSION['admin_feedback'] = "Doctor added successfully!";
            $_SESSION['admin_feedback_type'] = "success";
        } else {
            // Hata detayını loglamak faydalı olabilir
            // error_log("Doctor Insert Failed: " . $insert_stmt->error);
            $_SESSION['admin_feedback'] = "Failed to add doctor: Database error."; // Genel hata mesajı
            $_SESSION['admin_feedback_type'] = "error";
        }
        $insert_stmt->close();
    } else {
         // Hata detayını loglamak faydalı olabilir
         // error_log("Prepare Failed: " . $conn->error);
        $_SESSION['admin_feedback'] = "Error preparing statement."; // Genel hata mesajı
        $_SESSION['admin_feedback_type'] = "error";
    }

    $conn->close();
    header("Location: add_doctor.php"); // Redirect back
    exit;

} else {
    header("Location: add_doctor.php");
    exit;
}
?>