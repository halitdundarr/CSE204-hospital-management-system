<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Check POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['total_amount'], $_POST['status'], $_POST['patient_id'])) {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['manage_patient_feedback'] = "Invalid form token. Please try again.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: view_appointments.php");
        exit;
    }

    $appointment_id = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $patient_id = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);
    $status = trim($_POST['status']);
    $payment_method = trim($_POST['payment_method'] ?? '');
    $payment_reference_raw = trim($_POST['payment_reference'] ?? '');
    $total_amount_raw = trim($_POST['total_amount']);

    // Basic validation
    $errors = [];
    if ($appointment_id === false || $patient_id === false || $appointment_id <= 0 || $patient_id <= 0) {
        $errors[] = "Invalid appointment/patient input.";
    }
    if ($status !== 'Paid' && $status !== 'Unpaid') {
        $errors[] = "Invalid status selected.";
    }
    $allowed_methods = ['Cash', 'CreditCard', 'Bitcoin', 'Other'];
    if ($status === 'Paid') {
        if (!in_array($payment_method, $allowed_methods, true)) {
            $errors[] = "Please select a valid payment method for paid bills.";
        }
    } else {
        $payment_method = null;
    }
    if ($payment_reference_raw !== '' && strlen($payment_reference_raw) > 255) {
        $errors[] = "Payment reference cannot exceed 255 characters.";
    }
    if ($total_amount_raw === '' || !is_numeric($total_amount_raw) || (float)$total_amount_raw < 0) {
        $errors[] = "Please enter a valid total amount.";
    }

    if (!empty($errors)) {
        $_SESSION['manage_patient_feedback'] = implode("<br>", $errors);
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#billing");
        exit;
    }

    // Security check: Verify appointment belongs to this doctor (and patient)
    $check_sql = "SELECT `Appointment_ID` FROM `Appointment`
                  WHERE `Appointment_ID` = ? AND `Doctor_ID` = ? AND `Patient_ID` = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        $_SESSION['manage_patient_feedback'] = "Database error while verifying appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: view_appointments.php");
        exit;
    }
    $check_stmt->bind_param("iii", $appointment_id, $doctor_id, $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows !== 1) {
        $_SESSION['manage_patient_feedback'] = "Permission denied to bill this appointment.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        $check_stmt->close();
        header("Location: view_appointments.php");
        exit;
    }
    $check_stmt->close();

    // Create or update bill for this appointment (unique by Appointment_ID)
    $issue_date = date('Y-m-d');
    $paid_at = $status === 'Paid' ? date('Y-m-d H:i:s') : null;
    $payment_reference = $payment_reference_raw !== '' ? $payment_reference_raw : null;
    $total_amount = (float)$total_amount_raw;

    $sql_bill = "SELECT `Bill_ID` FROM `Bill` WHERE `Appointment_ID` = ? LIMIT 1";
    $stmt_bill = $conn->prepare($sql_bill);
    if (!$stmt_bill) {
        $_SESSION['manage_patient_feedback'] = "Database error while checking existing bill.";
        $_SESSION['manage_patient_feedback_type'] = "error";
        header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#billing");
        exit;
    }
    $stmt_bill->bind_param("i", $appointment_id);
    $stmt_bill->execute();
    $res_bill = $stmt_bill->get_result();
    $existing_bill = $res_bill ? $res_bill->fetch_assoc() : null;
    $stmt_bill->close();

    if ($existing_bill) {
        $sql_update = "UPDATE `Bill`
                        SET `Total_Amount` = ?, `Issue_Date` = ?, `Status` = ?, `Payment_Method` = ?, `Paid_At` = ?, `Payment_Reference` = ?
                        WHERE `Bill_ID` = ?";
        $stmt_update = $conn->prepare($sql_update);
        if (!$stmt_update) {
            $_SESSION['manage_patient_feedback'] = "Database error while preparing update.";
            $_SESSION['manage_patient_feedback_type'] = "error";
            header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#billing");
            exit;
        }
        $bill_id = (int)$existing_bill['Bill_ID'];
        $stmt_update->bind_param("dsssssi", $total_amount, $issue_date, $status, $payment_method, $paid_at, $payment_reference, $bill_id);

        if ($stmt_update->execute()) {
            audit_log_action(
                $conn,
                'doctor',
                $doctor_id,
                'UPDATE_BILL',
                'Bill',
                $bill_id,
                [
                    'appointment_id' => $appointment_id,
                    'total_amount' => $total_amount,
                    'status' => $status,
                    'payment_method' => $payment_method ?? 'Unknown/Legacy'
                ]
            );
            $_SESSION['manage_patient_feedback'] = "Bill updated successfully.";
            $_SESSION['manage_patient_feedback_type'] = "success";
        } else {
            $_SESSION['manage_patient_feedback'] = "Failed to update bill.";
            $_SESSION['manage_patient_feedback_type'] = "error";
        }
        $stmt_update->close();
    } else {
        $sql_insert = "INSERT INTO `Bill` (`Patient_ID`, `Appointment_ID`, `Total_Amount`, `Issue_Date`, `Status`, `Payment_Method`, `Paid_At`, `Payment_Reference`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            $_SESSION['manage_patient_feedback'] = "Database error while preparing insert.";
            $_SESSION['manage_patient_feedback_type'] = "error";
            header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#billing");
            exit;
        }
        $stmt_insert->bind_param("iidsssss", $patient_id, $appointment_id, $total_amount, $issue_date, $status, $payment_method, $paid_at, $payment_reference);

        if ($stmt_insert->execute()) {
            $new_bill_id = $conn->insert_id;
            audit_log_action(
                $conn,
                'doctor',
                $doctor_id,
                'CREATE_BILL',
                'Bill',
                $new_bill_id,
                [
                    'appointment_id' => $appointment_id,
                    'total_amount' => $total_amount,
                    'status' => $status,
                    'payment_method' => $payment_method ?? 'Unknown/Legacy'
                ]
            );
            $_SESSION['manage_patient_feedback'] = "Bill created successfully.";
            $_SESSION['manage_patient_feedback_type'] = "success";
        } else {
            $_SESSION['manage_patient_feedback'] = "Failed to create bill.";
            $_SESSION['manage_patient_feedback_type'] = "error";
        }
        $stmt_insert->close();
    }

    $conn->close();
    header("Location: manage_patient.php?appointment_id=$appointment_id&patient_id=$patient_id#billing");
    exit;
}

$_SESSION['manage_patient_feedback'] = "Invalid request.";
$_SESSION['manage_patient_feedback_type'] = "error";
header("Location: view_appointments.php");
exit;
?>

