<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../index.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Doctor';

// Validate GET parameters
if (!isset($_GET['appointment_id']) || !filter_var($_GET['appointment_id'], FILTER_VALIDATE_INT) ||
    !isset($_GET['patient_id']) || !filter_var($_GET['patient_id'], FILTER_VALIDATE_INT)) {
    header("Location: view_appointments.php");
    exit;
}

$appointment_id = intval($_GET['appointment_id']);
$patient_id = intval($_GET['patient_id']);

// --- Fetch Appointment/Patient Details ---
$appointment_details = null;
$patient_details = null;
$fetch_error = null;
$sql_appt = "SELECT a.*, p.`Patient_First_Name`, p.`Patient_Last_Name`, p.`Patient_DOB`, p.`Patient_Gender`, p.`Patient_Blood_Type`
             FROM `APPOINTMENT` a
             JOIN `PATIENT` p ON a.`Patient_ID` = p.`Patient_ID`
             WHERE a.`Appointment_ID` = ? AND a.`Doctor_ID` = ? AND a.`Patient_ID` = ?";
$stmt_appt = $conn->prepare($sql_appt);
if ($stmt_appt) {
    $stmt_appt->bind_param("iii", $appointment_id, $doctor_id, $patient_id);
    $stmt_appt->execute();
    $result_appt = $stmt_appt->get_result();
    if ($result_appt->num_rows === 1) {
        $appointment_details = $result_appt->fetch_assoc();
        $patient_details = [
             'Patient_ID' => $appointment_details['Patient_ID'], 'Patient_First_Name' => $appointment_details['Patient_First_Name'],
             'Patient_Last_Name' => $appointment_details['Patient_Last_Name'], 'Patient_DOB' => $appointment_details['Patient_DOB'],
             'Gender' => $appointment_details['Patient_Gender'], 'Blood_Type' => $appointment_details['Patient_Blood_Type'],
        ];
        if (!isset($patient_details['Gender']) && isset($appointment_details['Gender'])) $patient_details['Gender'] = $appointment_details['Gender'];
        if (!isset($patient_details['Blood_Type']) && isset($appointment_details['Blood_Type'])) $patient_details['Blood_Type'] = $appointment_details['Blood_Type'];
    } else { $fetch_error = "Appointment not found or you do not have permission to manage it."; }
    $stmt_appt->close();
} else { $fetch_error = "Database error preparing appointment details query."; }


// --- Fetch Existing Records & All Options ---
$existing_diagnoses = []; // Names only
$existing_tests = []; // Array of {Test_Name, Test_Result}
$existing_test_names = []; // Names only for dropdown filter
$existing_treatments = []; // Names only
$existing_prescription_medicines = []; // Array of {Medicine_Name, Dosage}
$prescription_details = null;
$all_diagnoses = [];
$all_tests = [];
$all_treatments = []; // *** YENİ: Tüm tedavileri tutacak dizi ***
$all_medicines = []; // *** YENİ: Tüm ilaçları tutacak dizi ***

if ($appointment_details) { // Only fetch if appointment is valid
    // Fetch Existing Diagnoses
    $sql_diag_exist = "SELECT d.`Diagnosis_Name` FROM `Appointment_Diagnosis` ad JOIN `DIAGNOSIS` d ON ad.`Diagnosis_ID` = d.`Diagnosis_ID` WHERE ad.`Appointment_ID` = ?";
    $stmt_diag_exist = $conn->prepare($sql_diag_exist);
    if($stmt_diag_exist){ $stmt_diag_exist->bind_param("i", $appointment_id); $stmt_diag_exist->execute(); $res_diag_exist = $stmt_diag_exist->get_result(); while($r = $res_diag_exist->fetch_assoc()){ $existing_diagnoses[] = $r['Diagnosis_Name'];} $stmt_diag_exist->close();}

    // Fetch All Diagnoses for dropdown
    $sql_all_diag = "SELECT `Diagnosis_ID`, `Diagnosis_Name` FROM `DIAGNOSIS` WHERE Diagnosis_ID != 0 ORDER BY `Diagnosis_Name` ASC";
    $res_all_diag = $conn->query($sql_all_diag);
    if($res_all_diag) { while($r = $res_all_diag->fetch_assoc()){ $all_diagnoses[] = $r; } }

    // Fetch Existing Tests
     $sql_test_exist = "SELECT t.`Test_Name`, at.`Test_Result` FROM `Appointment_Test` at JOIN `TEST` t ON at.`Test_ID` = t.`Test_ID` WHERE at.`Appointment_ID` = ?";
     $stmt_test_exist = $conn->prepare($sql_test_exist);
     if($stmt_test_exist){ $stmt_test_exist->bind_param("i", $appointment_id); $stmt_test_exist->execute(); $res_test_exist = $stmt_test_exist->get_result();
        while($r = $res_test_exist->fetch_assoc()){ $existing_tests[] = $r; $existing_test_names[] = $r['Test_Name']; }
     $stmt_test_exist->close();}

     // Fetch All Tests for dropdown
    $sql_all_test = "SELECT `Test_ID`, `Test_Name` FROM `TEST` WHERE Test_ID != 0 ORDER BY `Test_Name` ASC";
    $res_all_test = $conn->query($sql_all_test);
    if($res_all_test) { while($r = $res_all_test->fetch_assoc()){ $all_tests[] = $r; } }

    // Fetch Existing Treatments (Names only)
    $sql_treat_exist = "SELECT mt.`Medical_Treatment` FROM `Appointment_Treatment` atr JOIN `MEDICAL_TREATMENT` mt ON atr.`Medical_Treatment_ID` = mt.`Medical_Treatment_ID` WHERE atr.`Appointment_ID` = ?";
    $stmt_treat_exist = $conn->prepare($sql_treat_exist);
     if($stmt_treat_exist){ $stmt_treat_exist->bind_param("i", $appointment_id); $stmt_treat_exist->execute(); $res_treat_exist = $stmt_treat_exist->get_result(); while($r = $res_treat_exist->fetch_assoc()){ $existing_treatments[] = $r['Medical_Treatment'];} $stmt_treat_exist->close();}

    // *** YENİ: Fetch All Treatments for dropdown ***
    $sql_all_treat = "SELECT `Medical_Treatment_ID`, `Medical_Treatment` FROM `MEDICAL_TREATMENT` WHERE Medical_Treatment_ID != 0 ORDER BY `Medical_Treatment` ASC"; // Use correct column name
    $res_all_treat = $conn->query($sql_all_treat);
    if($res_all_treat) { while($r = $res_all_treat->fetch_assoc()){ $all_treatments[] = $r; } }

    // Fetch Existing Prescription Details
     $sql_presc = "SELECT p.`Prescription_ID`, p.`Prescription_Date`, m.`Medicine_Name`, pm.`Dosage` FROM `PRESCRIPTION` p JOIN `Prescription_Medicine` pm ON p.`Prescription_ID` = pm.`Prescription_ID` JOIN `MEDICINE` m ON pm.`Medicine_ID` = m.`Medicine_ID` WHERE p.`Appointment_ID` = ? ORDER BY m.`Medicine_Name`";
     $stmt_presc = $conn->prepare($sql_presc);
     if($stmt_presc){ $stmt_presc->bind_param("i", $appointment_id); $stmt_presc->execute(); $res_presc = $stmt_presc->get_result();
         if($res_presc->num_rows > 0) { while($r = $res_presc->fetch_assoc()){ if(!$prescription_details) { $prescription_details = ['Prescription_Date' => $r['Prescription_Date'], 'Prescription_ID' => $r['Prescription_ID']]; } $existing_prescription_medicines[] = ['Medicine_Name' => $r['Medicine_Name'], 'Dosage' => $r['Dosage']]; } }
     $stmt_presc->close();}

    // *** YENİ: Fetch All Medicines for dropdown ***
    $sql_all_med = "SELECT `Medicine_ID`, `Medicine_Name` FROM `MEDICINE` ORDER BY `Medicine_Name` ASC";
    $res_all_med = $conn->query($sql_all_med);
    if($res_all_med) { while($r = $res_all_med->fetch_assoc()){ $all_medicines[] = $r; } }

} // End if ($appointment_details)

// Feedback messages
$feedback_message = isset($_SESSION['manage_patient_feedback']) ? $_SESSION['manage_patient_feedback'] : null;
$feedback_type = isset($_SESSION['manage_patient_feedback_type']) ? $_SESSION['manage_patient_feedback_type'] : 'error';
unset($_SESSION['manage_patient_feedback']);
unset($_SESSION['manage_patient_feedback_type']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patient Appointment</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Styles remain the same */
        .patient-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .patient-info h3 { margin-top: 0; }
        .section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .section h3 { margin-top: 0; color: #007bff; }
        .section ul { list-style: disc; padding-left: 20px; margin-top: 10px;}
        .section li { margin-bottom: 5px; }
        .no-data { color: #6c757d; font-style: italic; }
        .action-form { margin-top: 15px; background-color: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6;}
        .action-form label { display: block; margin-bottom: 5px; font-weight: bold;}
        .action-form select, .action-form input[type="text"], .action-form button {
             width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .action-form button { background-color: #28a745; color: white; cursor: pointer; }
        .action-form button:hover { background-color: #218838; }
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; font-size: 0.95em;}
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;}
        .prescription-medicine-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;}
        .prescription-medicine-item span { flex-grow: 1; margin-right: 10px;}
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Doctor Menu</h2>
        <ul>
            <li><a href="view_appointments.php">View Appointments</a></li>
        </ul>
         <div class="logout-link">
             <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
             <h1>Manage Patient Appointment</h1>
        </div>

        <?php if ($feedback_message): ?>
            <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
         <?php endif; ?>

        <div class="content-section">
            <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
                 <a href="view_appointments.php">Back to Appointments</a>
            <?php elseif ($appointment_details && $patient_details): ?>
                <div class="patient-info">
                   <h3>Patient Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($patient_details['Patient_First_Name'] . ' ' . $patient_details['Patient_Last_Name']); ?></p>
                    <p><strong>DOB:</strong> <?php echo $patient_details['Patient_DOB'] ? htmlspecialchars(date("d-m-Y", strtotime($patient_details['Patient_DOB']))) : 'N/A'; ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient_details['Gender'] ?? 'N/A'); ?></p>
                    <p><strong>Blood Type:</strong> <?php echo htmlspecialchars($patient_details['Blood_Type'] ?? 'N/A'); ?></p>
                    <hr>
                    <p><strong>Appointment Date:</strong> <?php echo htmlspecialchars(date("d-m-Y", strtotime($appointment_details['Appointment_Date']))); ?></p>
                    <p><strong>Appointment Time:</strong> <?php echo $appointment_details['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appointment_details['Appointment_Time']))) : 'N/A'; ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment_details['Status'] ?? 'N/A'); ?></p>
                </div>

                 <div id="diagnoses" class="section">
                      <h3>Diagnoses</h3>
                     <?php if (!empty($existing_diagnoses)): ?>
                         <ul>
                             <?php foreach ($existing_diagnoses as $diag): echo '<li>' . htmlspecialchars($diag) . '</li>'; endforeach; ?>
                         </ul>
                     <?php else: ?>
                         <p class="no-data">No diagnoses recorded for this appointment yet.</p>
                     <?php endif; ?>
                     <div class="action-form">
                         <h4>Add New Diagnosis</h4>
                         <form action="process_add_diagnosis.php" method="POST">
                             <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                             <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                             <div class="form-group">
                                 <label for="diagnosis_id">Select Diagnosis:</label>
                                 <select name="diagnosis_id" id="diagnosis_id" required>
                                     <option value="" disabled selected>-- Choose Diagnosis --</option>
                                     <?php
                                     foreach ($all_diagnoses as $diag_option) {
                                         if (!in_array($diag_option['Diagnosis_Name'], $existing_diagnoses)) {
                                             echo "<option value=\"" . htmlspecialchars($diag_option['Diagnosis_ID']) . "\">" . htmlspecialchars($diag_option['Diagnosis_Name']) . "</option>";
                                         }
                                     }
                                     ?>
                                 </select>
                             </div>
                             <button type="submit">Add Diagnosis</button>
                         </form>
                     </div>
                 </div>

                 <div id="tests" class="section">
                     <h3>Tests</h3>
                     <?php if (!empty($existing_tests)): ?>
                         <ul>
                             <?php foreach ($existing_tests as $test):
                                 echo '<li><strong>' . htmlspecialchars($test['Test_Name']) . ':</strong> ' . nl2br(htmlspecialchars($test['Test_Result'] ?? 'Pending')) . '</li>';
                             endforeach; ?>
                         </ul>
                     <?php else: ?>
                         <p class="no-data">No tests assigned for this appointment yet.</p>
                     <?php endif; ?>
                     <div class="action-form">
                         <h4>Assign New Test</h4>
                          <form action="process_assign_test.php" method="POST">
                             <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                             <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                             <div class="form-group">
                                 <label for="test_id">Select Test:</label>
                                 <select name="test_id" id="test_id" required>
                                     <option value="" disabled selected>-- Choose Test --</option>
                                     <?php
                                     foreach ($all_tests as $test_option) {
                                         if (!in_array($test_option['Test_Name'], $existing_test_names)) {
                                             echo "<option value=\"" . htmlspecialchars($test_option['Test_ID']) . "\">" . htmlspecialchars($test_option['Test_Name']) . "</option>";
                                         }
                                     }
                                     ?>
                                 </select>
                             </div>
                             <button type="submit">Assign Test</button>
                         </form>
                     </div>
                 </div>

                 <div id="treatments" class="section">
                      <h3>Treatments</h3>
                      <?php if (!empty($existing_treatments)): ?>
                         <ul>
                             <?php foreach ($existing_treatments as $treat): echo '<li>' . htmlspecialchars($treat) . '</li>'; endforeach; ?>
                         </ul>
                     <?php else: ?>
                         <p class="no-data">No treatments recorded for this appointment yet.</p>
                     <?php endif; ?>
                     <div class="action-form">
                         <h4>Add New Treatment</h4>
                          <form action="process_add_treatment.php" method="POST">
                             <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                             <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                             <div class="form-group">
                                 <label for="treatment_id">Select Treatment:</label>
                                 <select name="treatment_id" id="treatment_id" required>
                                     <option value="" disabled selected>-- Choose Treatment --</option>
                                     <?php
                                     foreach ($all_treatments as $treat_option) {
                                         // Use correct column name from fetch ('Medical_Treatment')
                                         // Need to compare with $existing_treatments which also contains names
                                         if (!in_array($treat_option['Medical_Treatment'], $existing_treatments)) {
                                              // Use correct column name Medical_Treatment_ID for value
                                             echo "<option value=\"" . htmlspecialchars($treat_option['Medical_Treatment_ID']) . "\">" . htmlspecialchars($treat_option['Medical_Treatment']) . "</option>";
                                         }
                                     }
                                     ?>
                                 </select>
                             </div>
                             <button type="submit">Add Treatment</button>
                         </form>
                     </div>
                 </div>

                 <div id="prescription" class="section">
                     <h3>Prescription</h3>
                     <?php if ($prescription_details): ?>
                         <p><strong>Prescription Date:</strong> <?php echo htmlspecialchars(date("d-m-Y", strtotime($prescription_details['Prescription_Date']))); ?> (ID: <?php echo $prescription_details['Prescription_ID']; ?>)</p>
                         <?php if(!empty($existing_prescription_medicines)): ?>
                             <ul>
                                 <?php foreach ($existing_prescription_medicines as $med):
                                      echo '<li><strong>' . htmlspecialchars($med['Medicine_Name']) . ':</strong> ' . htmlspecialchars($med['Dosage']) . '</li>';
                                 endforeach; ?>
                             </ul>
                         <?php else: ?>
                              <p class="no-data">No medicines added to this prescription yet.</p>
                         <?php endif; ?>
                     <?php else: ?>
                         <p class="no-data">No prescription created for this appointment yet.</p>
                     <?php endif; ?>
                     <div class="action-form">
                         <h4>Add Medicine to Prescription</h4>
                         <form action="process_add_prescription_medicine.php" method="POST">
                             <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                             <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                             <?php if ($prescription_details): ?>
                                <input type="hidden" name="prescription_id_existing" value="<?php echo $prescription_details['Prescription_ID']; ?>">
                             <?php endif; ?>

                             <div class="form-group">
                                 <label for="medicine_id">Select Medicine:</label>
                                 <select name="medicine_id" id="medicine_id" required>
                                     <option value="" disabled selected>-- Choose Medicine --</option>
                                     <?php
                                     // Create a list of existing medicine names on this prescription for filtering
                                     $existing_med_names = array_column($existing_prescription_medicines, 'Medicine_Name');
                                     foreach ($all_medicines as $med_option) {
                                         if (!in_array($med_option['Medicine_Name'], $existing_med_names)) {
                                            echo "<option value=\"" . htmlspecialchars($med_option['Medicine_ID']) . "\">" . htmlspecialchars($med_option['Medicine_Name']) . "</option>";
                                         }
                                     }
                                     ?>
                                 </select>
                             </div>
                              <div class="form-group">
                                  <label for="dosage">Dosage:</label>
                                  <input type="text" name="dosage" id="dosage" placeholder="e.g., 1x1, 10mg daily" required>
                              </div>
                             <button type="submit">Add Medicine</button>
                         </form>
                     </div>
                 </div>

            <?php else: ?>
                <p>Appointment details could not be loaded.</p>
                 <a href="view_appointments.php">Back to Appointments</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>