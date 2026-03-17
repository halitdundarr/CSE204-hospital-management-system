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

// Fetch test results for the patient's appointments
$tests_data = [];
$fetch_error = null;

// SQL query to get test details along with appointment date and doctor
// Using table names from the provided SQL dump (APPOINTMENT, Appointment_Test, TEST, DOCTOR)
$sql = "SELECT
            at.`Appointment_ID`,
            a.`Appointment_Date`,
            t.`Test_Name`,
            at.`Test_Result`,
            doc.`Doctor_First_Name`,
            doc.`Doctor_Last_Name`
        FROM `Appointment_Test` at -- Use correct table name from dump
        JOIN `APPOINTMENT` a ON at.`Appointment_ID` = a.`Appointment_ID`
        JOIN `TEST` t ON at.`Test_ID` = t.`Test_ID` -- Use correct table name from dump
        JOIN `DOCTOR` doc ON a.`Doctor_ID` = doc.`Doctor_ID`
        WHERE a.`Patient_ID` = ?
        ORDER BY a.`Appointment_Date` DESC, t.`Test_Name` ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $tests_data[] = $row; // Store each test result row
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching tests: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Test Results</title>
    <link rel="stylesheet" href="../css/style.css"> <style>
        /* Styles for the tests table */
        .tests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .tests-table th,
        .tests-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .tests-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .tests-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .tests-table tr:hover {
            background-color: #f1f1f1;
        }
        .no-tests { color: #6c757d; font-style: italic;}
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
             <h1>My Test Results</h1>
        </div>

        <div class="content-section">
            <h2>Your Test History</h2>

             <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($tests_data)): ?>
                <p class="no-tests">You do not have any test results recorded.</p>
            <?php else: ?>
                <table class="tests-table">
                    <thead>
                        <tr>
                            <th>Appointment Date</th>
                            <th>Test Name</th>
                            <th>Result</th>
                            <th>Related Doctor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests_data as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($test['Appointment_Date']))); ?></td>
                                <td><?php echo htmlspecialchars($test['Test_Name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($test['Test_Result'] ?? 'N/A')); ?></td> <td>Dr. <?php echo htmlspecialchars($test['Doctor_First_Name'] . ' ' . $test['Doctor_Last_Name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>