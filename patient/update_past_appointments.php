<?php
require_once 'includes/db_connect.php'; // Veritabanı bağlantısını dahil et

echo "<h1>Updating Past Appointment Statuses</h1>";

// Hedef durum (Geçmiş randevular için ne yazılacak?)
$completed_status = 'Completed';
// Hangi durumdaki randevular güncellenecek?
$initial_status = 'Scheduled';

// SQL UPDATE sorgusu
// Appointment_Date'i bugünden (CURDATE()) küçük olan VE
// Status'u hala 'Scheduled' olan randevuların Status'unu 'Completed' yap.
$sql = "UPDATE Appointments
        SET Status = ?
        WHERE Appointment_Date < CURDATE()
        AND Status = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Parametreleri bağla (s = string)
    $stmt->bind_param("ss", $completed_status, $initial_status);

    // Sorguyu çalıştır
    if ($stmt->execute()) {
        // Kaç satırın etkilendiğini al
        $affected_rows = $stmt->affected_rows;
        echo "<p style='color: green;'>Update successful. " . $affected_rows . " appointment status(es) updated to '" . htmlspecialchars($completed_status) . "'.</p>";
    } else {
        // Çalıştırma hatası
        echo "<p style='color: red;'>Error executing update: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
} else {
    // Hazırlama hatası
    echo "<p style='color: red;'>Error preparing statement: " . htmlspecialchars($conn->error) . "</p>";
}

$conn->close();

echo "<p><a href='index.php'>Back to Login</a></p>"; // Veya başka bir sayfaya link
?>