-- Adds Translator.Clinic_ID safely for legacy databases where column is missing.
-- Run this script on the target database selected by your connection.

SET @db_name = DATABASE();

-- 1) Add Clinic_ID column if missing (nullable first for safe backfill)
SET @has_clinic_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'Translator'
      AND COLUMN_NAME = 'Clinic_ID'
);
SET @sql := IF(
    @has_clinic_id = 0,
    'ALTER TABLE `Translator` ADD COLUMN `Clinic_ID` INT NULL AFTER `Language`',
    'SELECT ''Translator.Clinic_ID already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Backfill from admin scope when admin has exactly one clinic
UPDATE `Translator` t
JOIN (
    SELECT `Admin_ID`, MIN(`Clinic_ID`) AS `Clinic_ID`
    FROM `Clinic`
    GROUP BY `Admin_ID`
    HAVING COUNT(*) = 1
) c1 ON c1.`Admin_ID` = t.`Admin_ID`
SET t.`Clinic_ID` = c1.`Clinic_ID`
WHERE t.`Clinic_ID` IS NULL;

-- 3) Backfill from existing support-staff assignments when translator maps to one clinic
UPDATE `Translator` t
JOIN (
    SELECT ass.`Staff_ID` AS `Translator_ID`, MIN(d.`Clinic_ID`) AS `Clinic_ID`
    FROM `Appointment_Support_Staff` ass
    JOIN `Appointment` a ON a.`Appointment_ID` = ass.`Appointment_ID`
    JOIN `Doctor` d ON d.`Doctor_ID` = a.`Doctor_ID`
    WHERE ass.`Staff_Type` = 'Translator'
    GROUP BY ass.`Staff_ID`
    HAVING COUNT(DISTINCT d.`Clinic_ID`) = 1
) m ON m.`Translator_ID` = t.`Translator_ID`
SET t.`Clinic_ID` = m.`Clinic_ID`
WHERE t.`Clinic_ID` IS NULL;

-- 4) Diagnostics for rows that still need manual clinic mapping
SELECT
    t.`Translator_ID`,
    t.`Translator_First_Name`,
    t.`Translator_Last_Name`,
    t.`Translator_Email`,
    t.`Admin_ID`
FROM `Translator` t
WHERE t.`Clinic_ID` IS NULL;

-- 5) Add index if missing
SET @has_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'Translator'
      AND INDEX_NAME = 'Clinic_ID'
);
SET @sql := IF(
    @has_idx = 0,
    'ALTER TABLE `Translator` ADD INDEX `Clinic_ID` (`Clinic_ID`)',
    'SELECT ''Translator.Clinic_ID index already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6) Add FK if missing
SET @has_fk := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'Translator'
      AND COLUMN_NAME = 'Clinic_ID'
      AND REFERENCED_TABLE_NAME = 'Clinic'
      AND REFERENCED_COLUMN_NAME = 'Clinic_ID'
);
SET @sql := IF(
    @has_fk = 0,
    'ALTER TABLE `Translator` ADD CONSTRAINT `translator_ibfk_2` FOREIGN KEY (`Clinic_ID`) REFERENCES `Clinic` (`Clinic_ID`)',
    'SELECT ''Translator.Clinic_ID foreign key already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7) Make Clinic_ID NOT NULL only when no unresolved rows remain
SET @null_count := (
    SELECT COUNT(*)
    FROM `Translator`
    WHERE `Clinic_ID` IS NULL
);
SET @sql := IF(
    @null_count = 0,
    'ALTER TABLE `Translator` MODIFY `Clinic_ID` INT NOT NULL',
    'SELECT ''Translator.Clinic_ID kept NULLABLE until unresolved mappings are fixed'' AS warning'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
