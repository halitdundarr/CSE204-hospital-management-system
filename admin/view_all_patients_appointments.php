<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

$admin_user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';

// Fetch all patients and their appointments within the last year
$patients_data = [];
$fetch_error = null;
$one_year_ago = date('Y-m-d', strtotime('-1 year')); // Calculate date one year ago

// SQL query to get all patients and join their recent appointments and doctors
$sql = "SELECT
            p.`Patient_ID`, p.`Patient_First_Name`, p.`Patient_Last_Name`, p.`Patient_Phone`,
            a.`Appointment_ID`, a.`Appointment_Date`, a.`Appointment_Time`, a.`Status`,
            d.`Doctor_First_Name`, d.`Doctor_Last_Name`
        FROM
            `PATIENT` p
        LEFT JOIN -- Include patients even if they have no recent appointments
            `APPOINTMENT` a ON p.`Patient_ID` = a.`Patient_ID` AND a.`Appointment_Date` >= ? -- Filter appointments
        LEFT JOIN -- Include appointment even if doctor is somehow missing
            `DOCTOR` d ON a.`Doctor_ID` = d.`Doctor_ID`
        ORDER BY
            p.`Patient_Last_Name` ASC, p.`Patient_First_Name` ASC, a.`Appointment_Date` DESC"; // Order for grouping

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $one_year_ago); // Bind the date string
    $stmt->execute();
    $result = $stmt->get_result();

    // Group results by Patient_ID
    while ($row = $result->fetch_assoc()) {
        $pid = $row['Patient_ID'];
        // Initialize patient details if first time seeing this patient
        if (!isset($patients_data[$pid])) {
            $patients_data[$pid] = [
                'details' => [
                    'Patient_First_Name' => $row['Patient_First_Name'],
                    'Patient_Last_Name' => $row['Patient_Last_Name'],
                    'Patient_Phone' => $row['Patient_Phone']
                ],
                'appointments' => [] // Initialize appointments array
            ];
        }
        // Add appointment details if an appointment exists for this row (due to LEFT JOIN)
        if ($row['Appointment_ID'] !== null) {
            $patients_data[$pid]['appointments'][] = [
                'Appointment_ID' => $row['Appointment_ID'],
                'Appointment_Date' => $row['Appointment_Date'],
                'Appointment_Time' => $row['Appointment_Time'],
                'Status' => $row['Status'],
                'Doctor_Name' => $row['Doctor_First_Name'] . ' ' . $row['Doctor_Last_Name']
            ];
        }
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching patient and appointment data: " . $conn->error;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Patients & Recent Appointments</title>
    <link rel="stylesheet" href="../css/style.css">
     <style>
        .patient-block {
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .patient-details {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-bottom: 1px solid #ccc;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .patient-details h3 { margin: 0; font-size: 1.2em; }
        .patient-details p { margin: 5px 0 0 0; font-size: 0.9em; color: #555;}
        .appointments-list { padding: 15px; }
        .appointments-list table { width: 100%; border-collapse: collapse; }
        .appointments-list th, .appointments-list td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9em; }
        .appointments-list th { background-color: #e2e2e2; }
        .no-appointments { font-style: italic; color: #6c757d; padding: 15px; }
        .no-patients { color: #6c757d; font-style: italic;}
        /* Status colors */
        .status-scheduled { color: #007bff; }
        .status-completed { color: #28a745; }
        .status-cancelled { color: #6c757d; text-decoration: line-through; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Menu</h2>
        <ul>
        <li><a href="add_doctor.php">Add New Doctor</a></li>
            <li><a href="add_nurse.php">Add New Nurse</a></li>
            <li><a href="add_patient.php">Add New Patient</a></li>
            <li><a href="find_patient_doctors.php">List Patient's Doctors</a></li>
            <li><a href="view_all_patients_appointments.php">View All Patients & Appointments</a></li>
            <li><a href="manage_appointments.php">Manage Appointments</a></li>
        </ul>
         <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
             <h1>All Patients and Appointments (Last Year)</h1>
        </div>

        <div class="content-section">
            <h2>Patient List</h2>

            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
            <?php elseif (empty($patients_data)): ?>
                <p class="no-patients">No patients found in the system.</p>
            <?php else: ?>
                <?php foreach ($patients_data as $patient_id => $data): ?>
                    <div class="patient-block">
                        <div class="patient-details">
                            <h3><?php echo htmlspecialchars($data['details']['Patient_First_Name'] . ' ' . $data['details']['Patient_Last_Name']); ?></h3>
                            <p>ID: <?php echo htmlspecialchars($patient_id); ?> | Phone: <?php echo htmlspecialchars($data['details']['Patient_Phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="appointments-list">
                            <?php if (!empty($data['appointments'])): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Doctor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['appointments'] as $appt):
                                             $status = $appt['Status'] ?? 'N/A';
                                             $status_class = 'status-' . strtolower(htmlspecialchars($status));
                                             ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date("d-m-Y", strtotime($appt['Appointment_Date']))); ?></td>
                                                <td><?php echo $appt['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appt['Appointment_Time']))) : 'N/A'; ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($appt['Doctor_Name'] ?? 'N/A'); ?></td>
                                                <td><span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-appointments">No appointments in the last year.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div> </div> </body>
</html>