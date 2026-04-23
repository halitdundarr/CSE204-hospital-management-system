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

SELECT 'staff_tables_exist' AS check_name,
       CASE WHEN COUNT(*) = 3 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('Secretary', 'Translator', 'Appointment_Support_Staff');

SELECT 'bill_table_has_payment_data_seeded' AS check_name,
       CASE WHEN COUNT(*) >= 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Bill
WHERE Status = 'Paid'
  AND Payment_Method IS NOT NULL;

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
