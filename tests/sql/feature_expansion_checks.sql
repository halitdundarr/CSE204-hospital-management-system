-- Feature expansion validation checks
-- This script returns PASS/FAIL style rows for quick CI/manual verification.

SELECT 'bill_has_payment_method_column' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Bill'
  AND COLUMN_NAME = 'Payment_Method';

SELECT 'bill_has_paid_at_column' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Bill'
  AND COLUMN_NAME = 'Paid_At';

SELECT 'bill_has_payment_reference_column' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Bill'
  AND COLUMN_NAME = 'Payment_Reference';

SELECT 'bill_patient_id_removed' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Bill'
  AND COLUMN_NAME = 'Patient_ID';

SELECT 'staff_tables_exist' AS check_name,
       CASE WHEN COUNT(*) = 3 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('Secretary', 'Translator', 'Appointment_Support_Staff');

SELECT 'clinic_has_admin_id' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Clinic'
  AND COLUMN_NAME = 'Admin_ID';

SELECT 'doctor_admin_id_removed' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Doctor'
  AND COLUMN_NAME = 'Admin_ID';

SELECT 'nurse_admin_id_removed' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Nurse'
  AND COLUMN_NAME = 'Admin_ID';

SELECT 'bill_table_has_payment_data_seeded' AS check_name,
       CASE WHEN COUNT(*) >= 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Bill
WHERE Status = 'Paid'
  AND Payment_Method IS NOT NULL;

SELECT 'medication_modeled_in_medical_treatment' AS check_name,
       CASE WHEN COUNT(*) >= 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Medical_Treatment
WHERE Treatment_Type = 'Medication'
  AND Medication_Name IS NOT NULL;

SELECT 'appointment_medicine_table_removed' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Appointment_Medicine';

SELECT 'annual_patient_appointment_count_query_smoke' AS check_name,
       CASE WHEN COUNT(*) >= 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM (
    SELECT p.Patient_ID, COUNT(a.Appointment_ID) AS appt_count
    FROM Patient p
    LEFT JOIN Appointment a
      ON a.Patient_ID = p.Patient_ID
     AND a.Appointment_Date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY p.Patient_ID
) t;
