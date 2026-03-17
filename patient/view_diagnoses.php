<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Patient';

// Fetch diagnoses for the patient's appointments
$diagnoses_data = [];
$fetch_error = null;

// SQL query to get diagnosis details along with appointment date and doctor
$sql = "SELECT
            ad.`Appointment_ID`,
            a.`Appointment_Date`,
            d.`Diagnosis_Name`,
            doc.`Doctor_First_Name`,
            doc.`Doctor_Last_Name`
        FROM `Appointment_Diagnosis` ad
        JOIN `APPOINTMENT` a ON ad.`Appointment_ID` = a.`Appointment_ID`
        JOIN `DIAGNOSIS` d ON ad.`Diagnosis_ID` = d.`Diagnosis_ID`
        JOIN `DOCTOR` doc ON a.`Doctor_ID` = doc.`Doctor_ID`
        WHERE a.`Patient_ID` = ?
        ORDER BY a.`Appointment_Date` DESC, d.`Diagnosis_Name` ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $diagnoses_data[] = $row;
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching diagnoses: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Diagnoses</title>
    <link rel="stylesheet" href="../css/style.css"> <style>
        /* Styles for the diagnoses table */
        .diagnoses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .diagnoses-table th,
        .diagnoses-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .diagnoses-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .diagnoses-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .diagnoses-table tr:hover {
            background-color: #f1f1f1;
        }
        .no-diagnoses { color: #6c757d; font-style: italic;}
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Patient Menu</h2>
        <ul>
        <li><a href="book_appointment.php">Book New Appointment</a></li>
            <li><a href="view_appointments.php">View Appointments</a></li>
            <li><a href="view_prescriptions.php">View Prescriptions</a></li>
            <li><a href="view_diagnoses.php">View Diagnoses</a></li>
            <li><a href="view_tests.php">View Tests & Results</a></li>
        </ul>
        <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
             <h1>My Diagnoses</h1>
        </div>

        <div class="content-section">
            <h2>Your Diagnosis History</h2>

             <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($diagnoses_data)): ?>
                <p class="no-diagnoses">You do not have any diagnoses recorded.</p>
            <?php else: ?>
                <table class="diagnoses-table">
                    <thead>
                        <tr>
                            <th>Appointment Date</th>
                            <th>Diagnosis</th>
                            <th>Diagnosing Doctor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnoses_data as $diag): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($diag['Appointment_Date']))); ?></td>
                                <td><?php echo htmlspecialchars($diag['Diagnosis_Name']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($diag['Doctor_First_Name'] . ' ' . $diag['Doctor_Last_Name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>