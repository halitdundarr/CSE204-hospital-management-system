<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php"); exit;
}
$doctor_id = $_SESSION['user_id'];

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['medicine_id'], $_POST['dosage'], $_POST['patient_id'])) {

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $medicine_id = filter_var($_POST['medicine_id'], FILTER_VALIDATE_INT);
    $dosage = trim($_POST['dosage']);
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);
    // Check if a prescription already exists (passed from hidden input)
    $existing_prescription_id = isset($_POST['prescription_id_existing']) ? filter_var($_POST['prescription_id_existing'], FILTER_VALIDATE_INT) : null;

    // Validation
    if ($appointment_id === false || $medicine_id === false || $patient_id === false || empty($dosage) || $medicine_id <= 0) {
        $_SESSION['manage_patient_feedback'] = "Invalid input for prescription medicine.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        $redirect_url = $appointment_id && $patient_id ? "manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id" : "view_appointments.php";
        header("Location: " . $redirect_url . "#prescription");
        exit;
    }

    // Security Check: Verify appointment belongs to doctor
    $check_sql = "SELECT `Appointment_ID` FROM `APPOINTMENT` WHERE `Appointment_ID` = ? AND `Doctor_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $is_authorized = ($check_result->num_rows === 1);
    $check_stmt->close();

    if (!$is_authorized) {
        $_SESSION['manage_patient_feedback'] = "Permission denied to modify this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: view_appointments.php");
        exit;
    }

    // --- Get or Create Prescription ID ---
    $prescription_id = $existing_prescription_id; // Assume it exists first

    if (!$prescription_id) {
        // Check again if it exists for the appointment just in case
        $find_presc_sql = "SELECT `Prescription_ID` FROM `PRESCRIPTION` WHERE `Appointment_ID` = ?";
        $find_stmt = $conn->prepare($find_presc_sql);
        $find_stmt->bind_param("i", $appointment_id);
        $find_stmt->execute();
        $find_res = $find_stmt->get_result();
        if ($find_res->num_rows > 0) {
            $prescription_id = $find_res->fetch_assoc()['Prescription_ID'];
        } else {
            // Prescription doesn't exist, create it
            $create_presc_sql = "INSERT INTO `PRESCRIPTION` (`Appointment_ID`, `Prescription_Date`) VALUES (?, CURDATE())";
            $create_stmt = $conn->prepare($create_presc_sql);
            $create_stmt->bind_param("i", $appointment_id);
            if ($create_stmt->execute()) {
                $prescription_id = $conn->insert_id; // Get the newly created ID
                if (!$prescription_id) { // Check if insert_id worked
                     $_SESSION['manage_patient_feedback'] = "Failed to create prescription record (ID error).";
                     $_SESSION['manage_patient_feedback_type'] = "error";
                     $conn->close();
                     header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#prescription");
                     exit;
                 }
            } else {
                $_SESSION['manage_patient_feedback'] = "Failed to create prescription record: " . $create_stmt->error;
                 $_SESSION['manage_patient_feedback_type'] = "error";
                 $create_stmt->close();
                 $conn->close();
                 header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#prescription");
                 exit;
            }
            $create_stmt->close();
        }
        $find_stmt->close();
    }

    // --- Add Medicine to Prescription ---
    if ($prescription_id) {
         // Check for duplicate medicine in this prescription
         $dup_med_sql = "SELECT COUNT(*) as count FROM `Prescription_Medicine` WHERE `Prescription_ID` = ? AND `Medicine_ID` = ?";
         $dup_med_stmt = $conn->prepare($dup_med_sql);
         $dup_med_stmt->bind_param("ii", $prescription_id, $medicine_id);
         $dup_med_stmt->execute();
         $dup_med_res = $dup_med_stmt->get_result()->fetch_assoc();
         $dup_med_stmt->close();

         if ($dup_med_res['count'] == 0) {
             // Insert medicine into Prescription_Medicine junction table
             $insert_med_sql = "INSERT INTO `Prescription_Medicine` (`Prescription_ID`, `Medicine_ID`, `Dosage`) VALUES (?, ?, ?)";
             $insert_med_stmt = $conn->prepare($insert_med_sql);
             if ($insert_med_stmt) {
                 $insert_med_stmt->bind_param("iis", $prescription_id, $medicine_id, $dosage);
                 if ($insert_med_stmt->execute()) {
                      $_SESSION['manage_patient_feedback'] = "Medicine added to prescription successfully.";
                      $_SESSION['manage_patient_feedback_type'] = "success";
                 } else {
                     $_SESSION['manage_patient_feedback'] = "Failed to add medicine: " . $insert_med_stmt->error;
                     $_SESSION['manage_patient_feedback_type'] = "error";
                 }
                 $insert_med_stmt->close();
             } else {
                  $_SESSION['manage_patient_feedback'] = "Error preparing medicine insert statement.";
                  $_SESSION['manage_patient_feedback_type'] = "error";
             }
         } else {
              $_SESSION['manage_patient_feedback'] = "This medicine is already on the prescription.";
              $_SESSION['manage_patient_feedback_type'] = "error";
         }

    } // else: Prescription ID could not be obtained (error handled earlier)


    $conn->close();
    // Redirect back
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#prescription");
    exit;

} else {
    $_SESSION['manage_patient_feedback'] = "Invalid request.";
    $_SESSION['manage_patient_feedback_type'] = "error";
    header("Location: view_appointments.php");
    exit;
}
?>