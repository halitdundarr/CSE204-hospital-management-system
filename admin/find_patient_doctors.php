<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied. Please login as admin.";
    header("Location: ../index.php");
    exit;
}

$admin_user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';

// Initialize variables
$searched_patient_id = '';
$patient_info = null;
$doctors = [];
$search_error = null;

// Check if a patient ID was submitted
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['patient_id_search'])) {
    $searched_patient_id = trim($_GET['patient_id_search']);

    // Validate (basic: must be 11 digits for TC Kimlik No)
    if (!empty($searched_patient_id) && ctype_digit($searched_patient_id) && strlen($searched_patient_id) === 11) {

        // 1. Verify patient exists and get name
        $stmt_pat = $conn->prepare("SELECT `Patient_First_Name`, `Patient_Last_Name` FROM `PATIENT` WHERE `Patient_ID` = ?");
        if ($stmt_pat) {
            $stmt_pat->bind_param("s", $searched_patient_id); // Bind as string just in case
            $stmt_pat->execute();
            $result_pat = $stmt_pat->get_result();
            if ($result_pat->num_rows === 1) {
                $patient_info = $result_pat->fetch_assoc();

                // 2. Fetch unique doctors for this patient
                $sql_docs = "SELECT DISTINCT d.`Doctor_ID`, d.`Doctor_First_Name`, d.`Doctor_Last_Name`, c.`Clinic_Name`
                             FROM `DOCTOR` d
                             JOIN `APPOINTMENT` a ON d.`Doctor_ID` = a.`Doctor_ID`
                             JOIN `CLINIC` c ON d.`Clinic_ID` = c.`Clinic_ID`
                             WHERE a.`Patient_ID` = ?
                             ORDER BY d.`Doctor_Last_Name`, d.`Doctor_First_Name`";
                $stmt_docs = $conn->prepare($sql_docs);
                if ($stmt_docs) {
                    $stmt_docs->bind_param("s", $searched_patient_id); // Bind patient ID
                    $stmt_docs->execute();
                    $result_docs = $stmt_docs->get_result();
                    while ($row = $result_docs->fetch_assoc()) {
                        $doctors[] = $row;
                    }
                    $stmt_docs->close();
                } else {
                    $search_error = "Error preparing doctor query: " . $conn->error;
                }

            } else {
                $search_error = "Patient with ID " . htmlspecialchars($searched_patient_id) . " not found.";
            }
            $stmt_pat->close();
        } else {
             $search_error = "Error preparing patient query: " . $conn->error;
        }

    } elseif (!empty($searched_patient_id)) {
        $search_error = "Invalid Patient ID format. Please enter 11 digits.";
    }
    // No error message if the page is loaded initially without search
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Patient's Doctors</title>
    <link rel="stylesheet" href="../css/style.css">
     <style>
        /* Using similar styles */
        .form-group { margin-bottom: 15px; display: flex; align-items: center; gap: 10px;}
        .form-group label { flex-shrink: 0; font-weight: bold; }
        .form-group input[type="number"] { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group button {
             background-color: #007bff; color: white; cursor: pointer;
             font-size: 14px; border: none; padding: 10px 15px; border-radius: 4px;
             flex-shrink: 0; /* Prevent button from shrinking */
        }
        .form-group button:hover { background-color: #0056b3; }
        .results-section { margin-top: 20px; }
        .results-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .results-table th, .results-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .results-table th { background-color: #f2f2f2; font-weight: bold; }
        .no-results { color: #6c757d; font-style: italic;}
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        /* Hide number input spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
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
             <h1>Find Doctors for a Patient</h1>
        </div>

        <div class="content-section">
            <h2>Search Patient</h2>
            <form action="find_patient_doctors.php" method="GET">
                <div class="form-group">
                    <label for="patient_id_search">Enter Patient ID (TC Kimlik No):</label>
                    <input type="number" id="patient_id_search" name="patient_id_search" value="<?php echo htmlspecialchars($searched_patient_id); ?>" required pattern="\d{11}" title="Enter 11 digits" maxlength="11" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);">
                    <button type="submit">Search</button>
                </div>
            </form>

            <div class="results-section">
                <?php if ($search_error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($search_error); ?></p>
                <?php elseif ($patient_info): ?>
                    <h3>Doctors for Patient: <?php echo htmlspecialchars($patient_info['Patient_First_Name'] . ' ' . $patient_info['Patient_Last_Name']); ?></h3>
                    <?php if (empty($doctors)): ?>
                        <p class="no-results">This patient has no recorded appointments with any doctor.</p>
                    <?php else: ?>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Doctor Name</th>
                                    <th>Clinic</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr>
                                        <td>Dr. <?php echo htmlspecialchars($doctor['Doctor_First_Name'] . ' ' . $doctor['Doctor_Last_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['Clinic_Name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div> </div> </div> </body>
</html>