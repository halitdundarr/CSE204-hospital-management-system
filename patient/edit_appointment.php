<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role - PATIENT access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}
$patient_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Patient';

// Check if appointment ID is provided
if (!isset($_GET['appointment_id']) || !filter_var($_GET['appointment_id'], FILTER_VALIDATE_INT)) {
     $_SESSION['patient_edit_appointment_feedback'] = "Invalid or missing Appointment ID for editing.";
     $_SESSION['patient_edit_appointment_feedback_type'] = "error";
     header("Location: view_appointments.php");
     exit;
}
$appointment_id = intval($_GET['appointment_id']);

// Fetch current appointment details - Check ownership and if editable
$appointment = null;
$fetch_error = null;

$sql = "SELECT
            a.`Appointment_ID`, a.`Appointment_Date`, a.`Appointment_Time`, a.`Status`, a.`Doctor_ID`,
            p.`Patient_First_Name`, p.`Patient_Last_Name`,
            d.`Doctor_First_Name`, d.`Doctor_Last_Name`
        FROM `APPOINTMENT` a
        JOIN `PATIENT` p ON a.`Patient_ID` = p.`Patient_ID`
        JOIN `DOCTOR` d ON a.`Doctor_ID` = d.`Doctor_ID`
        WHERE a.`Appointment_ID` = ? AND a.`Patient_ID` = ?"; // Security Check
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
        // Check if editable by patient
        if (!($appointment['Status'] === 'Scheduled' && strtotime($appointment['Appointment_Date']) >= strtotime(date('Y-m-d')))) {
            $fetch_error = "This appointment cannot be edited.";
            $appointment = null;
        }
    } else {
        $fetch_error = "Appointment not found or you don't have permission to edit it.";
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching appointment details: " . $conn->error;
}
$conn->close();

// Feedback message from process script
$feedback_message = isset($_SESSION['patient_edit_appointment_feedback']) ? $_SESSION['patient_edit_appointment_feedback'] : null;
$feedback_type = isset($_SESSION['patient_edit_appointment_feedback_type']) ? $_SESSION['patient_edit_appointment_feedback_type'] : 'error';
unset($_SESSION['patient_edit_appointment_feedback']);
unset($_SESSION['patient_edit_appointment_feedback_type']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit My Appointment</title>
    <link rel="stylesheet" href="../css/style.css">
     <style>
        /* Using similar form/message styles */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="date"],
        .form-group select, /* Changed from input[type=time] */
        .form-group button { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group button { background-color: #28a745; color: white; cursor: pointer; font-size: 16px; border: none; padding: 10px 15px; }
        .form-group button:hover { background-color: #218838; }
        .current-info p { margin: 5px 0; }
        .current-info { background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6; margin-bottom: 15px;}
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; font-size: 0.95em;}
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;}
         #time-slot-message { margin-top: 5px; color: #555; font-style: italic; } /* For loading/error messages */
    </style>
</head>
<body>
     <div class="sidebar">
        <h2>Patient Menu</h2>
         <ul>
            <li><a href="book_appointment.php">Book New Appointment</a></li>
            <li><a href="view_appointments.php">View Appointments</a></li>
            <li><a href="#">Book Follow-up</a></li>
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
             <h1>Edit My Appointment Date/Time</h1>
        </div>
        <div class="content-section">
             <p><a href="view_appointments.php">&laquo; Back to My Appointments</a></p>

             <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
             <?php endif; ?>

             <?php if ($fetch_error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($fetch_error); ?></p>
             <?php elseif ($appointment): ?>
                 <div class="current-info">
                     <p><strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment['Appointment_ID']); ?></p>
                     <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment['Doctor_First_Name'] . ' ' . $appointment['Doctor_Last_Name']); ?></p>
                     <p><strong>Current Date:</strong> <?php echo htmlspecialchars(date("d-m-Y", strtotime($appointment['Appointment_Date']))); ?></p>
                     <p><strong>Current Time:</strong> <?php echo $appointment['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appointment['Appointment_Time']))) : 'N/A'; ?></p>
                 </div>

                 <form id="edit-appointment-form" action="process_edit_appointment.php" method="POST">
                     <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['Appointment_ID']); ?>">
                     <input type="hidden" id="doctor_id_hidden" name="doctor_id" value="<?php echo htmlspecialchars($appointment['Doctor_ID']); ?>"> <div class="form-group">
                         <label for="appointment_date">New Date:</label>
                         <input type="date" id="appointment_date" name="appointment_date" required
                                min="<?php echo date('Y-m-d'); // Allow today or future ?>"
                                value="<?php echo htmlspecialchars($appointment['Appointment_Date']); ?>">
                     </div>
                     <div class="form-group">
                         <label for="appointment_time">New Available Time:</label>
                         <select name="appointment_time" id="appointment_time" required disabled>
                             <option value='<?php echo htmlspecialchars($appointment['Appointment_Time']); ?>' selected>
                                 <?php echo htmlspecialchars(date("H:i", strtotime($appointment['Appointment_Time']))); ?> (Current)
                             </option>
                             <option value='' disabled>-- Select Date --</option>
                         </select>
                         <div id="time-slot-message"></div>
                     </div>
                     <div class="form-group">
                         <button type="submit">Update Appointment</button>
                     </div>
                 </form>
             <?php else: ?>
                 <p>Appointment details could not be loaded or editing is not allowed.</p>
             <?php endif; ?>
        </div>
    </div>

     <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements specific to this page
            const doctorIdInput = document.getElementById('doctor_id_hidden'); // Hidden input holds doctor ID
            const dateInput = document.getElementById('appointment_date');
            const timeSelect = document.getElementById('appointment_time');
            const timeSlotMessage = document.getElementById('time-slot-message');
            const currentSelectedTime = '<?php echo $appointment["Appointment_Time"] ?? ""; ?>'; // Get current time from PHP

            // Function to fetch and update time slots
            function fetchAndUpdateEditTimeSlots() {
                const doctorId = doctorIdInput.value; // Get doctor ID from hidden input
                const selectedDate = dateInput.value;

                // Basic check
                if (!doctorId || !selectedDate) {
                    timeSelect.disabled = true;
                    timeSelect.innerHTML = '<option value="" selected>-- Select Date --</option>';
                    timeSlotMessage.textContent = '';
                    return;
                }

                timeSelect.disabled = true;
                timeSelect.innerHTML = '<option value="" selected>Loading...</option>';
                timeSlotMessage.textContent = '';
                const fetchURL = `get_available_slots.php?doctor_id=${doctorId}&date=${selectedDate}`;

                fetch(fetchURL)
                    .then(response => {
                        if (!response.ok) { throw new Error('Network response was not ok'); }
                        return response.json();
                    })
                    .then(data => {
                        timeSelect.innerHTML = ''; // Clear existing options

                        if (data.error) {
                            timeSlotMessage.textContent = `Error: ${data.error}`;
                            timeSelect.innerHTML = '<option value="" selected>-- Error Loading Slots --</option>';
                        } else if (data.available_slots && data.available_slots.length > 0) {
                            timeSelect.innerHTML = '<option value="" selected disabled>-- Select a New Time Slot --</option>';
                             // Add the current time back as an option if the date hasn't changed,
                             // or always add it if you want to allow keeping the same time
                             let isCurrentTimeAvailable = false;
                             if (selectedDate === '<?php echo $appointment["Appointment_Date"] ?? ""; ?>') {
                                 const currentTimeOption = document.createElement('option');
                                 currentTimeOption.value = currentSelectedTime;
                                 currentTimeOption.textContent = currentSelectedTime.substring(0, 5) + " (Current)";
                                 // Check if current time is also in the available slots list from server
                                 if(data.available_slots.includes(currentSelectedTime)){
                                    isCurrentTimeAvailable = true; // Mark it so we don't add it again below
                                    // currentTimeOption.selected = true; // Optionally re-select it
                                 }
                                 timeSelect.appendChild(currentTimeOption);
                             }

                            // Add other available slots
                            data.available_slots.forEach(slot => {
                                // Don't add the current time again if it was already added and is available
                                if (selectedDate === '<?php echo $appointment["Appointment_Date"] ?? ""; ?>' && slot === currentSelectedTime && isCurrentTimeAvailable) {
                                     return;
                                }
                                const displayTime = slot.substring(0, 5);
                                const option = document.createElement('option');
                                option.value = slot; // Use HH:MM:SS
                                option.textContent = displayTime;
                                timeSelect.appendChild(option);
                            });
                            timeSelect.disabled = false;
                            timeSlotMessage.textContent = '';
                        } else {
                             // Check if current time needs to be added even if no *other* slots available
                             if (selectedDate === '<?php echo $appointment["Appointment_Date"] ?? ""; ?>') {
                                 const currentTimeOption = document.createElement('option');
                                 currentTimeOption.value = currentSelectedTime;
                                 currentTimeOption.textContent = currentSelectedTime.substring(0, 5) + " (Current)";
                                 timeSelect.appendChild(currentTimeOption);
                                 timeSelect.disabled = false;
                                 timeSlotMessage.textContent = 'Only the current time slot is available.';
                             } else {
                                 timeSelect.innerHTML = '<option value="" selected disabled>-- No Slots Available --</option>';
                                 timeSlotMessage.textContent = 'No available time slots for this date.';
                             }

                        }
                    })
                    .catch(error => {
                        console.error('Error fetching time slots:', error);
                        timeSlotMessage.textContent = 'Failed to load time slots. Please try again.';
                        timeSelect.innerHTML = '<option value="" selected disabled>-- Error --</option>';
                        timeSelect.disabled = true;
                    });
            }

            // Add event listener to date input
            if(dateInput) {
                 dateInput.addEventListener('change', fetchAndUpdateEditTimeSlots);
            }

            // Trigger initial fetch when the page loads with the current date
             if (dateInput.value && doctorIdInput.value) {
                 fetchAndUpdateEditTimeSlots();
            }

        });
    </script>

</body>
</html>