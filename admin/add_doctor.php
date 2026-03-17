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

// Fetch Clinics for dropdown
$clinics = [];
$sql_clinics = "SELECT `Clinic_ID`, `Clinic_Name` FROM `CLINIC` ORDER BY `Clinic_Name`";
$result_clinics = $conn->query($sql_clinics);
if ($result_clinics) {
    while ($row = $result_clinics->fetch_assoc()) {
        $clinics[] = $row;
    }
}


$conn->close();

// Feedback messages
$feedback_message = isset($_SESSION['admin_feedback']) ? $_SESSION['admin_feedback'] : null;
$feedback_type = isset($_SESSION['admin_feedback_type']) ? $_SESSION['admin_feedback_type'] : 'error';
unset($_SESSION['admin_feedback']);
unset($_SESSION['admin_feedback_type']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor</title>
    <link rel="stylesheet" href="../css/style.css">
     <style>
        /* Styles remain the same */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 4px; box-sizing: border-box;
        }
        .form-group button {
             background-color: #007bff; color: white; cursor: pointer;
             font-size: 16px; border: none; padding: 10px 15px; border-radius: 4px;
        }
        .form-group button:hover { background-color: #0056b3; }
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; font-size: 0.95em;}
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;}
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
             <h1>Add New Doctor</h1>
        </div>

        <div class="content-section">
            <h2>Enter Doctor Details</h2>

            <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>

            <form action="process_add_doctor.php" method="POST">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                 <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled selected>-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                 <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                 <div class="form-group">
                    <label for="clinic_id">Clinic:</label>
                    <select id="clinic_id" name="clinic_id" required>
                        <option value="" disabled selected>-- Select Clinic --</option>
                        <?php foreach ($clinics as $clinic): ?>
                            <option value="<?php echo htmlspecialchars($clinic['Clinic_ID']); ?>">
                                <?php echo htmlspecialchars($clinic['Clinic_Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>