<?php
session_start();
require_once '../includes/db_connect.php';

// Check login and role - ADMIN access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Access denied.";
    header("Location: ../index.php");
    exit;
}
$admin_user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';

// Check if appointment ID is provided
if (!isset($_GET['appointment_id']) || !filter_var($_GET['appointment_id'], FILTER_VALIDATE_INT)) {
     $_SESSION['admin_manage_appointment_feedback'] = "Invalid or missing Appointment ID for editing.";
     $_SESSION['admin_manage_appointment_feedback_type'] = "error";
     header("Location: manage_appointments.php");
     exit;
}
$appointment_id = intval($_GET['appointment_id']);

// Fetch current appointment details
$appointment = null;
$fetch_error = null;

$sql = "SELECT
            a.`Appointment_ID`, a.`Appointment_Date`, a.`Appointment_Time`, a.`Status`, a.`Doctor_ID`,
            p.`Patient_First_Name`, p.`Patient_Last_Name`,
            d.`Doctor_First_Name`, d.`Doctor_Last_Name`
        FROM `APPOINTMENT` a
        JOIN `PATIENT` p ON a.`Patient_ID` = p.`Patient_ID`
        JOIN `DOCTOR` d ON a.`Doctor_ID` = d.`Doctor_ID`
        WHERE a.`Appointment_ID` = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
        // Admin can edit only 'Scheduled' appointments (can be changed if needed)
        if ($appointment['Status'] !== 'Scheduled') {
             $fetch_error = "Only 'Scheduled' appointments can be edited by admin.";
             $appointment = null; // Prevent form display
         }
    } else {
        $fetch_error = "Appointment not found.";
    }
    $stmt->close();
} else {
    $fetch_error = "Error fetching appointment details: " . $conn->error;
}
$conn->close(); // Close connection after fetching appointment details

// Feedback message from process script
$feedback_message = isset($_SESSION['admin_edit_appointment_feedback']) ? $_SESSION['admin_edit_appointment_feedback'] : null;
$feedback_type = isset($_SESSION['admin_edit_appointment_feedback_type']) ? $_SESSION['admin_edit_appointment_feedback_type'] : 'error';
unset($_SESSION['admin_edit_appointment_feedback']);
unset($_SESSION['admin_edit_appointment_feedback_type']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment (Admin)</title>
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
        #time-slot-message { margin-top: 5px; color: #555; font-style: italic; }
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
             <h1>Edit Appointment Date/Time (Admin)</h1>
        </div>
        <div class="content-section">
             <p><a href="manage_appointments.php">&laquo; Back to Appointment List</a></p>

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
                     <p><strong>Patient:</strong> <?php echo htmlspecialchars($appointment['Patient_First_Name'] . ' ' . $appointment['Patient_Last_Name']); ?></p>
                     <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment['Doctor_First_Name'] . ' ' . $appointment['Doctor_Last_Name']); ?></p>
                     <p><strong>Current Date:</strong> <?php echo htmlspecialchars(date("d-m-Y", strtotime($appointment['Appointment_Date']))); ?></p>
                     <p><strong>Current Time:</strong> <?php echo $appointment['Appointment_Time'] ? htmlspecialchars(date("H:i", strtotime($appointment['Appointment_Time']))) : 'N/A'; ?></p>
                     <p><strong>Status:</strong> <?php echo htmlspecialchars($appointment['Status'] ?? 'N/A'); ?></p>
                 </div>

                 <form id="edit-appointment-form-admin" action="process_edit_appointment.php" method="POST">
                     <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['Appointment_ID']); ?>">
                     <input type="hidden" id="doctor_id_hidden_admin" name="doctor_id" value="<?php echo htmlspecialchars($appointment['Doctor_ID']); ?>">

                     <div class="form-group">
                         <label for="appointment_date">New Date:</label>
                         <input type="date" id="appointment_date_admin" name="appointment_date" required
                                min="<?php echo date('Y-m-d'); // Allow today or future ?>"
                                value="<?php echo htmlspecialchars($appointment['Appointment_Date']); ?>">
                     </div>
                     <div class="form-group">
                         <label for="appointment_time">New Available Time:</label>
                         <select name="appointment_time" id="appointment_time_admin" required disabled>
                             <option value='<?php echo htmlspecialchars($appointment['Appointment_Time']); ?>' selected>
                                 <?php echo htmlspecialchars(date("H:i", strtotime($appointment['Appointment_Time']))); ?> (Current)
                             </option>
                             <option value='' disabled>-- Select Date --</option>
                         </select>
                         <div id="time-slot-message-admin"></div>
                     </div>
                     <div class="form-group">
                         <button type="submit">Update Appointment</button>
                     </div>
                 </form>
             <?php else: ?>
                 <p>Appointment details could not be loaded or editing is not allowed for this appointment status.</p>
             <?php endif; ?>
        </div>
    </div>

     <script>
        document.addEventListener('DOMContentLoaded', function() {
            const doctorIdInput = document.getElementById('doctor_id_hidden_admin');
            const dateInput = document.getElementById('appointment_date_admin');
            const timeSelect = document.getElementById('appointment_time_admin');
            const timeSlotMessage = document.getElementById('time-slot-message-admin');
            const currentSelectedTime = '<?php echo $appointment["Appointment_Time"] ?? ""; ?>';
            const currentSelectedDate = '<?php echo $appointment["Appointment_Date"] ?? ""; ?>';

            function fetchAndUpdateAdminEditTimeSlots() {
                const doctorId = doctorIdInput.value;
                const selectedDate = dateInput.value;

                if (!doctorId || !selectedDate) {
                    timeSelect.disabled = true;
                    timeSelect.innerHTML = '<option value="" selected>-- Select Date --</option>';
                    timeSlotMessage.textContent = '';
                    return;
                }

                timeSelect.disabled = true;
                timeSelect.innerHTML = '<option value="" selected>Loading...</option>';
                timeSlotMessage.textContent = '';
                // *** Reusing patient script ***
                const fetchURL = `../patient/get_available_slots.php?doctor_id=${doctorId}&date=${selectedDate}`;

                fetch(fetchURL)
                    .then(response => {
                        if (!response.ok) { throw new Error('Network response was not ok'); }
                        return response.json();
                    })
                    .then(data => {
                        timeSelect.innerHTML = ''; // Clear options
                        let isCurrentTimeAvailable = false;

                        // Add current time as first selectable option if date hasn't changed
                         if (selectedDate === currentSelectedDate && currentSelectedTime) {
                             const currentTimeOption = document.createElement('option');
                             currentTimeOption.value = currentSelectedTime;
                             currentTimeOption.textContent = currentSelectedTime.substring(0, 5) + " (Current)";
                             if(data.available_slots && data.available_slots.includes(currentSelectedTime)){
                                isCurrentTimeAvailable = true; // Mark it
                             }
                             timeSelect.appendChild(currentTimeOption);
                         }


                        if (data.error) {
                            timeSlotMessage.textContent = `Error: ${data.error}`;
                             if (selectedDate !== currentSelectedDate) timeSelect.innerHTML = '<option value="" selected>-- Error Loading Slots --</option>'; // Clear current if date changed
                        } else if (data.available_slots && data.available_slots.length > 0) {
                             if (timeSelect.options.length === 0 || selectedDate !== currentSelectedDate) {
                                // Add placeholder if no options yet or date changed
                                timeSelect.innerHTML = '<option value="" selected disabled>-- Select a New Time Slot --</option>';
                             }

                            data.available_slots.forEach(slot => {
                                if (isCurrentTimeAvailable && slot === currentSelectedTime) {
                                     return; // Don't add current time twice if it's in available list
                                }
                                const displayTime = slot.substring(0, 5);
                                const option = document.createElement('option');
                                option.value = slot; // Use HH:MM:SS
                                option.textContent = displayTime;
                                timeSelect.appendChild(option);
                            });
                            timeSelect.disabled = false;
                            timeSlotMessage.textContent = '';
                        } else { // No available slots found from server
                             if (timeSelect.options.length === 0) { // If current time wasn't added either
                                 timeSelect.innerHTML = '<option value="" selected disabled>-- No Slots Available --</option>';
                                 timeSlotMessage.textContent = 'No available time slots for this date.';
                             } else { // Only current time is available
                                 timeSlotMessage.textContent = 'Only the current time slot is available for this date.';
                                 timeSelect.disabled = false;
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

            if(dateInput) {
                 dateInput.addEventListener('change', fetchAndUpdateAdminEditTimeSlots);
                 // Trigger initial fetch on page load for the current date
                 if (dateInput.value && doctorIdInput.value) {
                     fetchAndUpdateAdminEditTimeSlots();
                 }
            }
        });
    </script>

</body>
</html>