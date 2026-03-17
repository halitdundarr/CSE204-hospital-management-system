<?php
require_once '../includes/db_connect.php'; // Veritabanı bağlantısı

header('Content-Type: application/json'); // Cevap tipi JSON

$response = ['available_slots' => []]; // Varsayılan cevap

if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = $_GET['date'];

    // Basit tarih formatı kontrolü
    if ($doctor_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {

        // Potansiyel zaman dilimleri (09:00 - 16:30 arası 30 dk)
        $start_time = strtotime('09:00:00');
        $end_time = strtotime('16:30:00');
        $slot_interval = 30 * 60;
        $potential_slots = [];
        for ($time = $start_time; $time <= $end_time; $time += $slot_interval) {
            $potential_slots[] = date('H:i:s', $time);
        }

        // Belirtilen doktor ve tarih için dolu slotları çek
        $booked_slots = [];
        // *** GÜNCELLENDİ: Tablo ve Sütun Adları ***
        $sql = "SELECT TIME_FORMAT(`Appointment_Time`, '%H:%i:%s') as booked_time
                FROM `APPOINTMENT`
                WHERE `Doctor_ID` = ? AND `Appointment_Date` = ? AND `Status` != 'Cancelled'"; // Status sütunu kullanılıyor
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("is", $doctor_id, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $booked_slots[] = $row['booked_time'];
            }
            $stmt->close();

            // Müsait slotları bul (potansiyel - dolu)
            $available_slots = array_diff($potential_slots, $booked_slots);
            $response['available_slots'] = array_values($available_slots); // JSON için yeniden indeksle

        } else {
             $response['error'] = 'Database query failed during slot check.'; // Hata mesajı
        }
        $conn->close();
    } else {
         $response['error'] = 'Invalid doctor ID or date format.'; // Hata mesajı
    }
} else {
    $response['error'] = 'Missing doctor_id or date parameter.'; // Hata mesajı
}

echo json_encode($response); // JSON olarak cevabı gönder
exit;
?>