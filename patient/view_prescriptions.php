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

// Fetch prescriptions for the patient - Updated Table/Column Names
$prescriptions = [];
$fetch_error = null;

$sql = "SELECT
            p.`Prescription_ID`,
            p.`Prescription_Date`,
            pm.`Dosage`,
            m.`Medicine_Name`,
            a.`Appointment_Date`,
            d.`Doctor_First_Name`,
            d.`Doctor_Last_Name`
        FROM `PRESCRIPTION` p
        JOIN `APPOINTMENT` a ON p.`Appointment_ID` = a.`Appointment_ID`
        JOIN `Prescription_Medicine` pm ON p.`Prescription_ID` = pm.`Prescription_ID` -- Use correct junction table name
        JOIN `MEDICINE` m ON pm.`Medicine_ID` = m.`Medicine_ID`
        JOIN `DOCTOR` d ON a.`Doctor_ID` = d.`Doctor_ID`
        WHERE a.`Patient_ID` = ?
        ORDER BY p.`Prescription_Date` DESC, p.`Prescription_ID` DESC, m.`Medicine_Name` ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group medicines by prescription ID
    while ($row = $result->fetch_assoc()) {
        $prescription_id = $row['Prescription_ID']; // Use correct column name
        if (!isset($prescriptions[$prescription_id])) {
            $prescriptions[$prescription_id] = [
                'Prescription_Date' => $row['Prescription_Date'], // Correct column name
                'Appointment_Date' => $row['Appointment_Date'], // Correct column name
                'Doctor_Name' => $row['Doctor_First_Name'] . ' ' . $row['Doctor_Last_Name'], // Correct column names
                'Medicines' => []
            ];
        }
        $prescriptions[$prescription_id]['Medicines'][] = [
            'Medicine_Name' => $row['Medicine_Name'], // Correct column name
            'Dosage' => $row['Dosage'] // Correct column name
        ];
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching prescriptions: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .prescription-list { margin-top: 20px; }
        .prescription-card {
            background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;
            padding: 15px; margin-bottom: 20px;
        }
        .prescription-card h3 {
            margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid #eee;
            padding-bottom: 5px; font-size: 1.1em;
        }
        .prescription-card p { margin: 5px 0; font-size: 0.95em; }
        .prescription-card ul { list-style: none; padding-left: 0; margin-top: 10px; }
        .prescription-card li {
            background-color: #fff; border: 1px solid #eee; padding: 8px 12px;
            margin-bottom: 5px; border-radius: 3px;
        }
         .prescription-card li strong { margin-right: 5px;}
        .no-prescriptions { color: #6c757d; font-style: italic; }
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
             <h1>My Prescriptions</h1>
        </div>

        <div class="content-section">
            <h2>Your Prescription History</h2>

             <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($prescriptions)): ?>
                <p class="no-prescriptions">You do not have any prescriptions recorded.</p>
            <?php else: ?>
                <div class="prescription-list">
                    <?php foreach ($prescriptions as $id => $prescription): ?>
                        <div class="prescription-card">
                            <h3>
                                Prescription Date: <?php echo htmlspecialchars(date("d-m-Y", strtotime($prescription['Prescription_Date']))); ?>
                            </h3>
                            <p><strong>Appointment Date:</strong> <?php echo htmlspecialchars(date("d-m-Y", strtotime($prescription['Appointment_Date']))); ?></p>
                            <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($prescription['Doctor_Name']); ?></p>
                            <p><strong>Medicines:</strong></p>
                            <ul>
                                <?php foreach ($prescription['Medicines'] as $medicine): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($medicine['Medicine_Name']); ?>:</strong>
                                        <?php echo htmlspecialchars($medicine['Dosage']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>