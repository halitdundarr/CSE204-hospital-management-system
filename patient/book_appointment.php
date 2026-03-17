<?php
session_start();
require_once '../includes/db_connect.php'; // Veritabanı bağlantısı

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../index.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Patient';

// --- Follow-up Handling ---
$follow_up_for_id = null;
$preselected_doctor_id = null;
$preselected_clinic_id = null;

if (isset($_GET['follow_up_for']) && filter_var($_GET['follow_up_for'], FILTER_VALIDATE_INT)) {
    $follow_up_for_id = intval($_GET['follow_up_for']);
}
if (isset($_GET['doctor_id']) && filter_var($_GET['doctor_id'], FILTER_VALIDATE_INT)) {
    $preselected_doctor_id = intval($_GET['doctor_id']);
}
if (isset($_GET['clinic_id']) && filter_var($_GET['clinic_id'], FILTER_VALIDATE_INT)) {
    $preselected_clinic_id = intval($_GET['clinic_id']);
}
// --- End Follow-up Handling ---


// Fetch clinics for the dropdown
$clinics_sql = "SELECT `Clinic_ID`, `Clinic_Name` FROM `CLINIC` ORDER BY `Clinic_Name`";
$clinics_result = $conn->query($clinics_sql);

// Initialize variables
// Use preselected clinic ID if available from follow-up link, otherwise check GET for normal selection
$selected_clinic_id = $preselected_clinic_id ?: (isset($_GET['clinic_id']) ? intval($_GET['clinic_id']) : null);

$doctors = []; // Array to hold doctors

// Fetch doctors if a clinic is selected
if ($selected_clinic_id !== null && $selected_clinic_id > 0) {
    $doctors_sql = "SELECT `Doctor_ID`, `Doctor_First_Name`, `Doctor_Last_Name`
                    FROM `DOCTOR`
                    WHERE `Clinic_ID` = ?
                    ORDER BY `Doctor_Last_Name`, `Doctor_First_Name`";
    $stmt = $conn->prepare($doctors_sql);
    if ($stmt) {
        $stmt->bind_param("i", $selected_clinic_id);
        $stmt->execute();
        $doctors_result = $stmt->get_result();
        while ($row = $doctors_result->fetch_assoc()) {
            $doctors[] = [
                'Doctor_ID' => $row['Doctor_ID'],
                'Doctor_First_Name' => $row['Doctor_First_Name'],
                'Doctor_Last_Name' => $row['Doctor_Last_Name']
             ];
        }
        $stmt->close();
    } else {
        echo "Error preparing doctors query: " . $conn->error;
    }
}

// Check for booking error messages
$booking_error_message = isset($_SESSION['booking_error']) ? $_SESSION['booking_error'] : null;
unset($_SESSION['booking_error']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo $follow_up_for_id ? 'Follow-up ' : ''; ?>Appointment</title> <link rel="stylesheet" href="../css/style.css">
     <style>
        /* Styles remain the same */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select,
        .form-group input[type="date"],
        .form-group button {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .form-group button {
             background-color: #28a745; color: white; cursor: pointer; font-size: 16px; border: none;
        }
        .form-group button:hover { background-color: #218838; }
        .form-group button.secondary {
            background-color: #007bff; margin-top: 10px;
        }
         .form-group button.secondary:hover {
             background-color: #0056b3;
        }
         #time-slot-message { margin-top: 5px; color: #555; font-style: italic; }
         .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
         .followup-info { background-color: #e2f3f5; border: 1px solid #b6e0e6; padding: 10px; margin-bottom: 15px; border-radius: 4px; color: #0c5460;}
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
             <h1>Book <?php echo $follow_up_for_id ? 'Follow-up ' : 'New '; ?>Appointment</h1>
        </div>

        <div class="content-section">
             <?php if ($booking_error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($booking_error_message); ?></div>
             <?php endif; ?>

             <?php if ($follow_up_for_id): ?>
                <div class="followup-info">Booking a follow-up for appointment ID: <?php echo $follow_up_for_id; ?>. Clinic and Doctor may be pre-selected.</div>
             <?php endif; ?>


            <form action="book_appointment.php" method="GET" id="clinic-select-form">
                 <?php if ($follow_up_for_id): ?> <input type="hidden" name="follow_up_for" value="<?php echo $follow_up_for_id; ?>"> <?php endif; ?>
                 <?php if ($preselected_doctor_id): ?> <input type="hidden" name="doctor_id" value="<?php echo $preselected_doctor_id; ?>"> <?php endif; ?>

                 <div class="form-group">
                    <label for="clinic_id">1. Select Clinic:</label>
                    <select name="clinic_id" id="clinic_id" required onchange="document.getElementById('clinic-select-form').submit()">
                        <option value="" disabled <?php echo ($selected_clinic_id === null) ? 'selected' : ''; ?>>-- Select a Clinic --</option>
                        <?php
                        if ($clinics_result && $clinics_result->num_rows > 0) {
                            while($row = $clinics_result->fetch_assoc()) {
                                $clinic_id_val = htmlspecialchars($row['Clinic_ID']);
                                $clinic_name_val = htmlspecialchars($row['Clinic_Name']);
                                // Select based on $selected_clinic_id (which includes preselection)
                                $is_selected = ($selected_clinic_id == $clinic_id_val) ? 'selected' : '';
                                echo "<option value=\"$clinic_id_val\" $is_selected>$clinic_name_val</option>";
                            }
                        }
                        ?>
                    </select>
                    <?php if ($preselected_clinic_id && $preselected_clinic_id != $selected_clinic_id) echo "<small style='color:orange;'>Follow-up clinic changed.</small>"; ?>
                </div>
                 <noscript><button type="submit" class="secondary">Find Doctors for Selected Clinic</button></noscript>
            </form>

            <?php if ($selected_clinic_id !== null): ?>
                 <hr style="margin: 20px 0;">
                 <form id="booking-form" action="process_booking.php" method="POST">
                    <?php if ($follow_up_for_id): ?>
                        <input type="hidden" name="follow_up_for_id" value="<?php echo htmlspecialchars($follow_up_for_id); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                         <label for="doctor_id">2. Select Doctor:</label>
                         <select name="doctor_id" id="doctor_id" required>
                             <option value="" disabled <?php echo ($preselected_doctor_id === null) ? 'selected' : ''; ?>>-- Select a Doctor --</option>
                             <?php
                             if (!empty($doctors)) {
                                 foreach ($doctors as $doctor) {
                                     $doc_id = htmlspecialchars($doctor['Doctor_ID']);
                                     $doc_name = "Dr. " . htmlspecialchars($doctor['Doctor_First_Name']) . " " . htmlspecialchars($doctor['Doctor_Last_Name']);
                                     // Pre-select based on GET parameter if clinic matches
                                     $is_doc_selected = ($preselected_doctor_id == $doc_id) ? 'selected' : '';
                                     echo "<option value=\"$doc_id\" $is_doc_selected>$doc_name</option>";
                                 }
                             } else {
                                 echo "<option value=\"\" disabled>No doctors found for this clinic.</option>";
                             }
                             ?>
                         </select>
                           <?php if ($preselected_doctor_id && $selected_clinic_id != $preselected_clinic_id) echo "<small style='color:orange;'>Original doctor not in this clinic.</small>"; ?>
                    </div>

                    <div class="form-group">
                        <label for="appointment_date">3. Select Date:</label>
                        <input type="date" id="appointment_date" name="appointment_date" required
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">4. Select Available Time:</label>
                        <select name="appointment_time" id="appointment_time" required disabled>
                            <option value='' selected>-- Select Date First --</option>
                        </select>
                        <div id="time-slot-message"></div>
                    </div>

                    <div class="form-group">
                        <button type="submit">Book <?php echo $follow_up_for_id ? 'Follow-up ' : ''; ?>Appointment</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Copy the JavaScript code from Turn #35 here
        document.addEventListener('DOMContentLoaded', function() {
            const doctorSelect = document.getElementById('doctor_id');
            const dateInput = document.getElementById('appointment_date');
            const timeSelect = document.getElementById('appointment_time');
            const timeSlotMessage = document.getElementById('time-slot-message');

            function fetchAndUpdateTimeSlots() {
                const doctorId = doctorSelect.value;
                const selectedDate = dateInput.value;

                // Check if doctor is selected before fetching
                 if (!doctorId) {
                     timeSelect.disabled = true;
                     timeSelect.innerHTML = '<option value="" selected>-- Select Doctor First --</option>';
                     timeSlotMessage.textContent = '';
                     return; // Stop if no doctor selected
                 }


                if (doctorId && selectedDate) {
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
                                timeSelect.innerHTML = '<option value="" selected disabled>-- Select a Time Slot --</option>';
                                data.available_slots.forEach(slot => {
                                    const displayTime = slot.substring(0, 5);
                                    const option = document.createElement('option');
                                    option.value = slot;
                                    option.textContent = displayTime;
                                    timeSelect.appendChild(option);
                                });
                                timeSelect.disabled = false;
                                timeSlotMessage.textContent = '';
                            } else {
                                timeSelect.innerHTML = '<option value="" selected disabled>-- No Slots Available --</option>';
                                timeSlotMessage.textContent = 'No available time slots for this date.';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching time slots:', error);
                            timeSlotMessage.textContent = 'Failed to load time slots. Please try again.';
                            timeSelect.innerHTML = '<option value="" selected disabled>-- Error --</option>';
                            timeSelect.disabled = true;
                        });
                } else {
                    timeSelect.disabled = true;
                    timeSelect.innerHTML = '<option value="" selected>-- Select Date First --</option>';
                    timeSlotMessage.textContent = '';
                }
            }

            // Trigger fetch when date changes or doctor changes
            if(dateInput) { dateInput.addEventListener('change', fetchAndUpdateTimeSlots); }
            if(doctorSelect) { doctorSelect.addEventListener('change', fetchAndUpdateTimeSlots); }

            // Trigger initial fetch if date and doctor are pre-selected (e.g., on follow-up load)
            if (dateInput.value && doctorSelect.value) {
                 fetchAndUpdateTimeSlots();
            }
        });
    </script>

</body>
</html>