<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    $_SESSION['login_error'] = "Please login to book an appointment.";
    header("Location: ../index.php");
    exit;
}

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Retrieve data from the form
    $patient_id = $_SESSION['user_id'];
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : null;
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : null; // HH:MM:SS
    // *** YENİ: Takip edilen randevu ID'sini al ***
    $follow_up_for_id = isset($_POST['follow_up_for_id']) ? intval($_POST['follow_up_for_id']) : null;
    // clinic_id hata durumunda yönlendirme için gerekebilir
    $clinic_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : null;


    // Basic Validation
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $_SESSION['booking_error'] = "Please select a doctor, date, and time.";
        // Add follow_up_for_id back to URL if it existed
        $redirect_url = "book_appointment.php?";
        if ($clinic_id) $redirect_url .= "clinic_id=" . $clinic_id;
        if ($follow_up_for_id) $redirect_url .= ($clinic_id ? "&" : "") . "follow_up_for=" . $follow_up_for_id . "&doctor_id=" . $doctor_id; // Pass params back
        header("Location: " . $redirect_url);
        exit;
    }

    // Time format validation
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $appointment_time)) {
         $_SESSION['booking_error'] = "Invalid time format selected.";
         $redirect_url = "book_appointment.php?";
         if ($clinic_id) $redirect_url .= "clinic_id=" . $clinic_id;
         if ($follow_up_for_id) $redirect_url .= ($clinic_id ? "&" : "") . "follow_up_for=" . $follow_up_for_id . "&doctor_id=" . $doctor_id;
         header("Location: " . $redirect_url);
         exit;
    }


    // Prepare INSERT statement - Include Follow_Up_Appointment_ID
    // *** GÜNCELLENDİ: SQL Sorgusu ve bind_param ***
    $sql = "INSERT INTO `APPOINTMENT` (`Patient_ID`, `Doctor_ID`, `Appointment_Date`, `Appointment_Time`, `Follow_Up_Appointment_ID`, `Status`)
            VALUES (?, ?, ?, ?, ?, 'Scheduled')"; // 5 placeholders now

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters (i=integer, s=string, i=integer or null)
        // Use null if $follow_up_for_id is not set or is 0/invalid
        $follow_up_param = ($follow_up_for_id > 0) ? $follow_up_for_id : null;
        // Types: patient_id (i), doctor_id (i), date (s), time (s), followup_id (i)
        $stmt->bind_param("iissi",
                            $patient_id,
                            $doctor_id,
                            $appointment_date,
                            $appointment_time,
                            $follow_up_param // Pass the variable containing ID or NULL
                          );

        if ($stmt->execute()) {
            $_SESSION['booking_success'] = "Appointment booked successfully for " . htmlspecialchars($appointment_date) . " at " . htmlspecialchars(substr($appointment_time, 0, 5)) . ".";
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['booking_error'] = "Failed to book appointment. Database error. Please try again.";
            // error_log("Booking failed: " . $stmt->error);
            $redirect_url = "book_appointment.php?";
             if ($clinic_id) $redirect_url .= "clinic_id=" . $clinic_id;
             if ($follow_up_for_id) $redirect_url .= ($clinic_id ? "&" : "") . "follow_up_for=" . $follow_up_for_id . "&doctor_id=" . $doctor_id;
            header("Location: " . $redirect_url);
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['booking_error'] = "Failed to book appointment due to a server error. Please try again later.";
        // error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        $redirect_url = "book_appointment.php?";
         if ($clinic_id) $redirect_url .= "clinic_id=" . $clinic_id;
         if ($follow_up_for_id) $redirect_url .= ($clinic_id ? "&" : "") . "follow_up_for=" . $follow_up_for_id . "&doctor_id=" . $doctor_id;
        header("Location: " . $redirect_url);
        exit;
    }

    $conn->close();

} else {
    header("Location: book_appointment.php");
    exit;
}
?>