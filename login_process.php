<?php
session_start();
require_once 'includes/db_connect.php'; // Veritabanı bağlantısı

// === Güvenlik Uyarısı: Gerçek uygulamada şifreleri hash'leyin! ===

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $identifier = trim($_POST['identifier']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($identifier) || empty($password) || empty($role)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: index.php");
        exit();
    }

    $sql = "";
    $user_id_col = "";
    $name_col = "";
    $password_col = "";
    $table_name = "";
    $identifier_col = "";
    $id_param_type = "";

    // Rol bazlı sorgu hazırlama (Yeni Tablo/Sütun Adları ile)
    switch ($role) {
        case 'patient':
            $table_name = "`PATIENT`"; // Büyük harf ve backtick
            $identifier_col = "`Patient_ID`"; // Veritabanındaki ad
            $user_id_col = "Patient_ID"; // Fetch için kullanılacak ad
            $name_col = "Patient_First_Name";
            $password_col = "Patient_Password";
            $id_param_type = "i"; // Patient_ID (BIGINT olsa da i ile bağlanabilir)
            if (!ctype_digit($identifier)) {
                 $_SESSION['login_error'] = "Invalid Patient ID format.";
                 header("Location: index.php");
                 exit();
            }
            break;
        case 'doctor':
            $table_name = "`DOCTOR`";
            $identifier_col = "`Doctor_Email`";
            $user_id_col = "Doctor_ID";
            $name_col = "Doctor_First_Name";
            $password_col = "Doctor_Password";
            $id_param_type = "s"; // Email is string
            break;
        case 'admin':
             $table_name = "`Admin`"; // Dökümdeki ad 'Admin'
            $identifier_col = "`Admin_Email`";
            $user_id_col = "Admin_ID";
            $name_col = "Admin_First_Name";
            $password_col = "Admin_Password";
            $id_param_type = "s"; // Email is string
            break;
        default:
            $_SESSION['login_error'] = "Invalid role selected.";
            header("Location: index.php");
            exit();
    }

    // SQL sorgusunu dinamik olarak oluştur
    $sql = "SELECT `$user_id_col`, `$name_col`, `$password_col` FROM $table_name WHERE $identifier_col = ?";

    // Prepared statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql); // Detaylı loglama
        $_SESSION['login_error'] = "An internal error occurred (DB Prepare). Please try again later.";
        header("Location: index.php");
        exit();
    }

    $stmt->bind_param($id_param_type, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Şifre kontrolü (Düz metin - GÜVENSİZ!)
        if ($password === $user[$password_col]) { // Use correct password column name

            // Session değişkenlerini ayarla
            $_SESSION['user_id'] = $user[$user_id_col];
            $_SESSION['user_name'] = $user[$name_col];
            $_SESSION['user_role'] = $role;

            // İlgili dashboard'a yönlendir
            switch ($role) {
                case 'patient': header("Location: patient/dashboard.php"); break;
                case 'doctor': header("Location: doctor/dashboard.php"); break;
                case 'admin': header("Location: admin/dashboard.php"); break;
            }
            exit();

        } else {
            $_SESSION['login_error'] = "Invalid User ID/Email or Password.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid User ID/Email or Password.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: index.php");
    exit();
}
?>