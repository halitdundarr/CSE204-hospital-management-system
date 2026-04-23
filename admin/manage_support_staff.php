<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$appointments = [];
$secretaries = [];
$translators = [];
$existing_assignments = [];

$appt_result = $conn->query("SELECT `Appointment_ID`, `Appointment_Date`, `Appointment_Time`, `Patient_ID` FROM `Appointment` ORDER BY `Appointment_Date` DESC, `Appointment_Time` DESC");
if ($appt_result) {
    while ($row = $appt_result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

$sec_result = $conn->query("SELECT `Secretary_ID`, `Secretary_First_Name`, `Secretary_Last_Name` FROM `Secretary` ORDER BY `Secretary_First_Name`, `Secretary_Last_Name`");
if ($sec_result) {
    while ($row = $sec_result->fetch_assoc()) {
        $secretaries[] = $row;
    }
}

$tr_result = $conn->query("SELECT `Translator_ID`, `Translator_First_Name`, `Translator_Last_Name`, `Language` FROM `Translator` ORDER BY `Translator_First_Name`, `Translator_Last_Name`");
if ($tr_result) {
    while ($row = $tr_result->fetch_assoc()) {
        $translators[] = $row;
    }
}

$assign_sql = "SELECT ass.`Assignment_ID`, ass.`Appointment_ID`, ass.`Staff_Type`, ass.`Staff_ID`, ass.`Notes`, ass.`Assigned_At`,
    s.`Secretary_First_Name`, s.`Secretary_Last_Name`,
    t.`Translator_First_Name`, t.`Translator_Last_Name`, t.`Language`
    FROM `Appointment_Support_Staff` ass
    LEFT JOIN `Secretary` s ON ass.`Staff_Type` = 'Secretary' AND ass.`Staff_ID` = s.`Secretary_ID`
    LEFT JOIN `Translator` t ON ass.`Staff_Type` = 'Translator' AND ass.`Staff_ID` = t.`Translator_ID`
    ORDER BY ass.`Assigned_At` DESC";
$assign_result = $conn->query($assign_sql);
if ($assign_result) {
    while ($row = $assign_result->fetch_assoc()) {
        $existing_assignments[] = $row;
    }
}

$feedback_message = $_SESSION['admin_support_staff_feedback'] ?? null;
$feedback_type = $_SESSION['admin_support_staff_feedback_type'] ?? 'error';
unset($_SESSION['admin_support_staff_feedback'], $_SESSION['admin_support_staff_feedback_type']);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Support Staff</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }
        .staff-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .staff-table th, .staff-table td { border: 1px solid #ddd; padding: 8px; }
        .staff-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Menu</h2>
        <ul>
            <li><a href="add_doctor.php">Add New Doctor</a></li>
            <li><a href="add_nurse.php">Add New Nurse</a></li>
            <li><a href="add_secretary.php">Add New Secretary</a></li>
            <li><a href="add_translator.php">Add New Translator</a></li>
            <li><a href="manage_support_staff.php">Assign Support Staff</a></li>
            <li><a href="manage_appointments.php">Manage Appointments</a></li>
        </ul>
        <div class="logout-link"><a href="../logout.php">Logout</a></div>
    </div>
    <div class="main-content">
        <div class="header"><h1>Assign Support Staff to Appointments</h1></div>
        <div class="content-section">
            <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>
            <form action="process_assign_support_staff.php" method="POST">
                <?php echo csrf_input_field(); ?>
                <div class="two-col">
                    <div>
                        <label for="appointment_id">Appointment</label>
                        <select id="appointment_id" name="appointment_id" required>
                            <option value="" selected disabled>-- Select Appointment --</option>
                            <?php foreach ($appointments as $appointment): ?>
                                <option value="<?php echo (int)$appointment['Appointment_ID']; ?>">
                                    #<?php echo (int)$appointment['Appointment_ID']; ?> | Patient: <?php echo htmlspecialchars($appointment['Patient_ID']); ?> | <?php echo htmlspecialchars($appointment['Appointment_Date']); ?> <?php echo htmlspecialchars(substr((string)$appointment['Appointment_Time'], 0, 5)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="staff_type">Staff Type</label>
                        <select id="staff_type" name="staff_type" required>
                            <option value="" selected disabled>-- Select Type --</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Translator">Translator</option>
                        </select>
                    </div>
                </div>
                <div class="two-col">
                    <div>
                        <label for="staff_id">Staff</label>
                        <select id="staff_id" name="staff_id" required></select>
                    </div>
                    <div>
                        <label for="notes">Notes (Optional)</label>
                        <input type="text" id="notes" name="notes" maxlength="255">
                    </div>
                </div>
                <button type="submit">Assign Staff</button>
            </form>

            <h2>Current Support Assignments</h2>
            <?php if (empty($existing_assignments)): ?>
                <p>No support staff assignments yet.</p>
            <?php else: ?>
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Appointment</th>
                            <th>Staff Type</th>
                            <th>Staff</th>
                            <th>Notes</th>
                            <th>Assigned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existing_assignments as $assignment): ?>
                            <?php
                            if ($assignment['Staff_Type'] === 'Secretary') {
                                $staff_name = trim(($assignment['Secretary_First_Name'] ?? '') . ' ' . ($assignment['Secretary_Last_Name'] ?? ''));
                            } else {
                                $name_part = trim(($assignment['Translator_First_Name'] ?? '') . ' ' . ($assignment['Translator_Last_Name'] ?? ''));
                                $staff_name = $name_part !== '' ? $name_part . ' (' . ($assignment['Language'] ?? 'N/A') . ')' : '';
                            }
                            if ($staff_name === '') {
                                $staff_name = 'Deleted or missing staff record';
                            }
                            ?>
                            <tr>
                                <td>#<?php echo (int)$assignment['Appointment_ID']; ?></td>
                                <td><?php echo htmlspecialchars($assignment['Staff_Type']); ?></td>
                                <td><?php echo htmlspecialchars($staff_name); ?></td>
                                <td><?php echo htmlspecialchars($assignment['Notes'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($assignment['Assigned_At']); ?></td>
                                <td>
                                    <form action="process_remove_support_staff.php" method="POST" onsubmit="return confirm('Remove this support staff assignment?');">
                                        <?php echo csrf_input_field(); ?>
                                        <input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['Assignment_ID']; ?>">
                                        <button type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <script>
        const secretaries = <?php echo json_encode($secretaries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const translators = <?php echo json_encode($translators, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const staffType = document.getElementById('staff_type');
        const staffSelect = document.getElementById('staff_id');

        function refreshStaffOptions() {
            const type = staffType.value;
            staffSelect.innerHTML = '<option value="" selected disabled>-- Select Staff --</option>';
            const data = type === 'Secretary' ? secretaries : translators;
            data.forEach((row) => {
                const option = document.createElement('option');
                const id = type === 'Secretary' ? row.Secretary_ID : row.Translator_ID;
                const first = type === 'Secretary' ? row.Secretary_First_Name : row.Translator_First_Name;
                const last = type === 'Secretary' ? row.Secretary_Last_Name : row.Translator_Last_Name;
                const language = type === 'Translator' ? ` (${row.Language})` : '';
                option.value = id;
                option.textContent = `${first} ${last}${language}`;
                staffSelect.appendChild(option);
            });
        }

        staffType.addEventListener('change', refreshStaffOptions);
    </script>
</body>
</html>
