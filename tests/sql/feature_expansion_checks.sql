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

SELECT 'translator_has_clinic_id_column' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Translator'
  AND COLUMN_NAME = 'Clinic_ID';

SELECT 'translator_clinic_id_not_nullable' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Translator'
  AND COLUMN_NAME = 'Clinic_ID'
  AND IS_NULLABLE = 'NO';

SELECT 'translator_has_clinic_id_index' AS check_name,
       CASE WHEN COUNT(*) >= 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Translator'
  AND COLUMN_NAME = 'Clinic_ID';

SELECT 'translator_has_clinic_fk' AS check_name,
       CASE WHEN COUNT(*) = 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Translator'
  AND COLUMN_NAME = 'Clinic_ID'
  AND REFERENCED_TABLE_NAME = 'Clinic'
  AND REFERENCED_COLUMN_NAME = 'Clinic_ID';

SELECT 'translator_seed_has_clinic_id' AS check_name,
       CASE WHEN COUNT(*) >= 1 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Translator
WHERE Clinic_ID IS NOT NULL;

SELECT 'support_staff_translator_clinic_alignment' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Appointment_Support_Staff ass
JOIN Appointment a ON a.Appointment_ID = ass.Appointment_ID
JOIN Doctor d ON d.Doctor_ID = a.Doctor_ID
JOIN Translator t ON t.Translator_ID = ass.Staff_ID
WHERE ass.Staff_Type = 'Translator'
  AND t.Clinic_ID <> d.Clinic_ID;

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

SELECT 'hospital_unf_cleanup_drop_columns_applied' AS check_name,
       CASE
           WHEN (
               SELECT COUNT(*)
               FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'Hospital_UNF'
           ) = 0 THEN 'PASS'
           WHEN (
               SELECT COUNT(*)
               FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'Hospital_UNF'
                 AND COLUMN_NAME IN (
                     'Diagnosis_Name',
                     'Test_Name',
                     'Test_Result',
                     'Medical_Treatment',
                     'Medicine_Name',
                     'Dosage',
                     'Bill_Status'
                 )
           ) = 0 THEN 'PASS'
           ELSE 'FAIL'
       END AS status;

SELECT 'hospital_unf_has_diagnosis_name_removed' AS check_name,
       CASE
           WHEN (
               SELECT COUNT(*)
               FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'Hospital_UNF'
           ) = 0 THEN 'PASS'
           WHEN COUNT(*) = 0 THEN 'PASS'
           ELSE 'FAIL'
       END AS status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'Hospital_UNF'
  AND COLUMN_NAME = 'Diagnosis_Name';

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

START TRANSACTION;

SET @cascade_appt_id := (SELECT COALESCE(MAX(Appointment_ID), 0) + 500 FROM Appointment);
SET @cascade_patient_id := (SELECT Patient_ID FROM Patient ORDER BY Patient_ID LIMIT 1);
SET @cascade_doctor_id := (SELECT Doctor_ID FROM Doctor ORDER BY Doctor_ID LIMIT 1);
SET @cascade_test_id := (SELECT Test_ID FROM Test ORDER BY Test_ID LIMIT 1);

INSERT INTO Appointment
    (Appointment_ID, Patient_ID, Doctor_ID, Nurse_ID, Appointment_Date, Appointment_Time, Follow_Up_Appointment_ID, Status)
VALUES
    (@cascade_appt_id, @cascade_patient_id, @cascade_doctor_id, NULL, CURDATE(), '08:00:00', NULL, 'Scheduled');

INSERT INTO Appointment_Test (Appointment_ID, Test_ID, Test_Result)
VALUES (@cascade_appt_id, @cascade_test_id, 'Pending');

DELETE FROM Appointment
WHERE Appointment_ID = @cascade_appt_id;

SELECT 'appointment_delete_removes_parent_row' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Appointment
WHERE Appointment_ID = @cascade_appt_id;

SELECT 'appointment_delete_cascades_to_appointment_test' AS check_name,
       CASE WHEN COUNT(*) = 0 THEN 'PASS' ELSE 'FAIL' END AS status
FROM Appointment_Test
WHERE Appointment_ID = @cascade_appt_id;

ROLLBACK;
