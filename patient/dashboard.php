<?php
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    // If not logged in or not a patient, redirect to login page
    header("Location: ../index.php"); // Go back to the main directory's index
    exit;
}

// Get user name for display
$user_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Patient';

// Check for booking success message
$booking_success_message = null;
if (isset($_SESSION['booking_success'])) {
    $booking_success_message = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']); // Clear the message after retrieving it
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../css/style.css"> <style>
        /* Success message specific styles */
        .success-message {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            color: #155724; /* Dark green text */
            background-color: #d4edda; /* Light green background */
            border-color: #c3e6cb; /* Green border */
        }
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
             <h1>Welcome, <?php echo $user_name; ?>!</h1>
        </div>

         <?php if ($booking_success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($booking_success_message); ?>
            </div>
         <?php endif; ?>

        <div class="content-section">
            <h2>Dashboard Overview</h2>
            <p>Select an option from the menu on the left to manage your appointments and view your medical records.</p>
            </div>
    </div>
</body>
</html>