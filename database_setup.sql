-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 02, 2025 at 11:09 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Final`
--

-- --------------------------------------------------------

--
-- Table structure for table `Admin`
--

CREATE TABLE `Admin` (
  `Admin_ID` int(11) NOT NULL,
  `Admin_First_Name` varchar(100) NOT NULL,
  `Admin_Last_Name` varchar(100) NOT NULL,
  `Admin_Email` varchar(255) NOT NULL,
  `Admin_Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Admin`
--

INSERT INTO `Admin` (`Admin_ID`, `Admin_First_Name`, `Admin_Last_Name`, `Admin_Email`, `Admin_Password`) VALUES
(1, 'admin', 'admin', 'admin@email.com', 'admin123'),
(2, 'system', 'admin', 'systemadmin@email.com', 'system123');

-- --------------------------------------------------------

--
-- Table structure for table `Appointment`
--

CREATE TABLE `Appointment` (
  `Appointment_ID` int(11) NOT NULL,
  `Patient_ID` bigint(20) NOT NULL,
  `Doctor_ID` int(11) NOT NULL,
  `Nurse_ID` int(11) DEFAULT NULL,
  `Appointment_Date` date NOT NULL,
  `Appointment_Time` time NOT NULL,
  `Follow_Up_Appointment_ID` int(11) DEFAULT NULL,
  `Status` varchar(50) DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Appointment`
--

INSERT INTO `Appointment` (`Appointment_ID`, `Patient_ID`, `Doctor_ID`, `Nurse_ID`, `Appointment_Date`, `Appointment_Time`, `Follow_Up_Appointment_ID`, `Status`) VALUES
(1, 43543543565, 1, 1, '2024-03-02', '09:00:00', NULL, 'Completed'),
(2, 43543543565, 2, 2, '2024-05-03', '15:00:00', NULL, 'Completed'),
(3, 23423423477, 1, 3, '2024-02-12', '14:30:00', NULL, 'Completed'),
(4, 23423423477, 3, 4, '2024-05-03', '10:30:00', NULL, 'Completed'),
(5, 23423423477, 4, 5, '2024-04-17', '12:30:00', NULL, 'Completed'),
(6, 56756756734, 3, 4, '2024-01-26', '10:00:00', NULL, 'Completed'),
(7, 56756756734, 2, 6, '2024-09-09', '10:00:00', NULL, 'Completed'),
(8, 67867867823, 4, 5, '2024-06-01', '15:30:00', NULL, 'Completed'),
(9, 67867867823, 1, 3, '2024-03-02', '11:00:00', NULL, 'Completed'),
(10, 65656565923, 5, 8, '2024-06-17', '12:00:00', NULL, 'Completed'),
(11, 65656565923, 5, 8, '2024-05-04', '15:00:00', NULL, 'Completed'),
(12, 65656565923, 6, 7, '2024-04-20', '09:00:00', NULL, 'Completed'),
(13, 65656565923, 4, 5, '2024-12-22', '12:30:00', NULL, 'Completed'),
(14, 21721721795, 3, 7, '2024-05-03', '11:00:00', NULL, 'Completed'),
(15, 21721721795, 3, 7, '2024-06-09', '15:00:00', 14, 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `Appointment_Diagnosis`
--

CREATE TABLE `Appointment_Diagnosis` (
  `Appointment_ID` int(11) NOT NULL,
  `Diagnosis_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Appointment_Diagnosis`
--

INSERT INTO `Appointment_Diagnosis` (`Appointment_ID`, `Diagnosis_ID`) VALUES
(1, 1),
(2, 2),
(3, 1),
(4, 3),
(5, 4),
(6, 5),
(7, 0),
(8, 0),
(9, 1),
(10, 6),
(11, 7),
(12, 5),
(13, 8),
(14, 9),
(15, 9);

-- --------------------------------------------------------

--
-- Table structure for table `Appointment_Test`
--

CREATE TABLE `Appointment_Test` (
  `Appointment_ID` int(11) NOT NULL,
  `Test_ID` int(11) NOT NULL,
  `Test_Result` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Appointment_Test`
--

INSERT INTO `Appointment_Test` (`Appointment_ID`, `Test_ID`, `Test_Result`) VALUES
(1, 1, 'Positive'),
(2, 2, 'Abnormal'),
(3, 3, 'Positive'),
(4, 4, 'Enlarged ventricles'),
(5, 5, 'High'),
(6, 6, 'Mass in frontal lobe'),
(7, 2, 'Normal'),
(8, 5, 'Normal'),
(9, 3, 'Positive'),
(10, 7, 'High eye pressure'),
(11, 8, 'Clouded eye lens'),
(12, 6, 'Mass in frontal lobe'),
(13, 9, 'High'),
(14, 10, 'Abnormal'),
(15, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `Appointment_Treatment`
--

CREATE TABLE `Appointment_Treatment` (
  `Appointment_ID` int(11) NOT NULL,
  `Medical_Treatment_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Appointment_Treatment`
--

INSERT INTO `Appointment_Treatment` (`Appointment_ID`, `Medical_Treatment_ID`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 2),
(5, 1),
(6, 3),
(7, 0),
(8, 0),
(9, 1),
(10, 1),
(11, 4),
(12, 3),
(13, 1),
(14, 1),
(15, 0);

-- --------------------------------------------------------

--
-- Table structure for table `Clinic`
--

CREATE TABLE `Clinic` (
  `Clinic_ID` int(11) NOT NULL,
  `Clinic_Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Clinic`
--

INSERT INTO `Clinic` (`Clinic_ID`, `Clinic_Name`) VALUES
(1, 'Dermatology'),
(2, 'Cardiology'),
(3, 'Neurosurgery'),
(4, 'Internal Medicine'),
(5, 'Ophthalmology');

-- --------------------------------------------------------

--
-- Table structure for table `Diagnosis`
--

CREATE TABLE `Diagnosis` (
  `Diagnosis_ID` int(11) NOT NULL,
  `Diagnosis_Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Diagnosis`
--

INSERT INTO `Diagnosis` (`Diagnosis_ID`, `Diagnosis_Name`) VALUES
(0, 'No Diagnosis'),
(1, 'Eczema'),
(2, 'Heart Failure'),
(3, 'Hydrocephalus'),
(4, 'Type 1 Diabetes'),
(5, 'Brain Tumor'),
(6, 'Glaucoma'),
(7, 'Cataract'),
(8, 'Hypertension'),
(9, 'Epilepsy');

-- --------------------------------------------------------

--
-- Table structure for table `Doctor`
--

CREATE TABLE `Doctor` (
  `Doctor_ID` int(11) NOT NULL,
  `Doctor_First_Name` varchar(100) NOT NULL,
  `Doctor_Last_Name` varchar(100) NOT NULL,
  `Doctor_Gender` varchar(10) DEFAULT NULL,
  `Doctor_Email` varchar(255) NOT NULL,
  `Doctor_Phone` varchar(20) DEFAULT NULL,
  `Clinic_ID` int(11) DEFAULT NULL,
  `Doctor_Password` varchar(255) NOT NULL,
  `Admin_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Doctor`
--

INSERT INTO `Doctor` (`Doctor_ID`, `Doctor_First_Name`, `Doctor_Last_Name`, `Doctor_Gender`, `Doctor_Email`, `Doctor_Phone`, `Clinic_ID`, `Doctor_Password`, `Admin_ID`) VALUES
(1, 'Halit', 'Dündar', 'Male', 'halit@email.com', NULL, 1, 'halit123', 1),
(2, 'Seyhan', 'Günay', 'Male', 'seyhan@email.com', NULL, 2, 'seyhan123', 2),
(3, 'Tolunay', 'Tunca', 'Male', 'tolunay@email.com', NULL, 3, 'tolunay123', 1),
(4, 'Yusuf', 'Fidan', 'Male', 'yusuf@email.com', NULL, 4, 'yusuf123', 2),
(5, 'Anıl', 'Yeşil', 'Male', 'anıl@email.com', NULL, 5, 'anıl123', 2),
(6, 'Yiğit', 'Ertaş', 'Male', 'yigit@email.com', NULL, 3, 'yigit123', 1);

-- --------------------------------------------------------

--
-- Table structure for table `Medical_Treatment`
--

CREATE TABLE `Medical_Treatment` (
  `Medical_Treatment_ID` int(11) NOT NULL,
  `Medical_Treatment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Medical_Treatment`
--

INSERT INTO `Medical_Treatment` (`Medical_Treatment_ID`, `Medical_Treatment`) VALUES
(0, 'No Treatment'),
(1, 'Medicine Treatment'),
(2, 'VP Shunt Surgery'),
(3, 'Surgery'),
(4, 'Lens Replacement');

-- --------------------------------------------------------

--
-- Table structure for table `Medicine`
--

CREATE TABLE `Medicine` (
  `Medicine_ID` int(11) NOT NULL,
  `Medicine_Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Medicine`
--

INSERT INTO `Medicine` (`Medicine_ID`, `Medicine_Name`) VALUES
(1, 'Prednol'),
(2, 'Fusidic Acid'),
(3, 'Furosemide'),
(4, 'Cetirizine'),
(5, 'Acetaminophen'),
(6, 'Ibuprofen'),
(7, 'Insulin Glargine'),
(8, 'Cefazolin'),
(9, 'Loratadine'),
(10, 'Timolol'),
(11, 'Bimatoprost'),
(12, 'Lisinopril'),
(13, 'Hydrochlorothiazide'),
(14, 'Valproate');

-- --------------------------------------------------------

--
-- Table structure for table `Nurse`
--

CREATE TABLE `Nurse` (
  `Nurse_ID` int(11) NOT NULL,
  `Nurse_First_Name` varchar(100) NOT NULL,
  `Nurse_Last_Name` varchar(100) NOT NULL,
  `Nurse_Gender` varchar(10) DEFAULT NULL,
  `Nurse_Email` varchar(255) NOT NULL,
  `Nurse_Phone` varchar(20) DEFAULT NULL,
  `Clinic_ID` int(11) DEFAULT NULL,
  `Admin_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Nurse`
--

INSERT INTO `Nurse` (`Nurse_ID`, `Nurse_First_Name`, `Nurse_Last_Name`, `Nurse_Gender`, `Nurse_Email`, `Nurse_Phone`, `Clinic_ID`, `Admin_ID`) VALUES
(1, 'Meryem', 'Şeker', 'Female', 'meryem@email.com', NULL, 1, 1),
(2, 'Tunahan', 'Aktaş', 'Male', 'tuna@email.com', NULL, 2, 1),
(3, 'Derya', 'Işık', 'Female', 'derya@email.com', NULL, 1, 2),
(4, 'Büşra', 'Tekin', 'Female', 'büsra@email.com', NULL, 3, 1),
(5, 'Kaan', 'Çevik', 'Male', 'kaan@email.com', NULL, 4, 1),
(6, 'Meryem', 'Bozdağ', 'Female', 'meryembozdag@email.com', NULL, 2, 2),
(7, 'Enes', 'Öztürk', 'Male', 'enes@email.com', NULL, 3, 2),
(8, 'Gamze', 'Can', 'Female', 'gamze@email.com', NULL, 5, 2);

-- --------------------------------------------------------

--
-- Table structure for table `Patient`
--

CREATE TABLE `Patient` (
  `Patient_ID` bigint(20) NOT NULL,
  `Patient_First_Name` varchar(100) NOT NULL,
  `Patient_Last_Name` varchar(100) NOT NULL,
  `Patient_Gender` varchar(10) DEFAULT NULL,
  `Patient_DOB` date DEFAULT NULL,
  `Patient_Blood_Type` varchar(5) DEFAULT NULL,
  `Patient_Phone` varchar(20) DEFAULT NULL,
  `Patient_Address` text DEFAULT NULL,
  `Patient_Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Patient`
--

INSERT INTO `Patient` (`Patient_ID`, `Patient_First_Name`, `Patient_Last_Name`, `Patient_Gender`, `Patient_DOB`, `Patient_Blood_Type`, `Patient_Phone`, `Patient_Address`, `Patient_Password`) VALUES
(21721721795, 'Mehmet', 'Baş', 'Male', '1976-08-15', '0 -', '5355112233', 'Kepez', 'mehmet123'),
(23423423477, 'Melike', 'Özal', 'Female', '1985-03-10', 'A +', '5451234563', 'Konyaaltı', 'melike123'),
(43543543565, 'Murat', 'Yüce', 'Male', '1990-01-01', 'A -', '5551234569', 'Muratpaşa', 'murat123'),
(56756756734, 'Dilara', 'Tekin', 'Female', '1999-12-25', 'B +', '5355643322', 'Kepez', 'dilara123'),
(65656565923, 'Betül', 'Yekin', 'Female', '1994-05-12', 'AB -', '5466454321', 'Muratpaşa', 'betül123'),
(67867867823, 'Kerim', 'Küçük', 'Male', '2004-12-25', 'A -', '5312458414', 'Aksu', 'kerim123');

-- --------------------------------------------------------

--
-- Table structure for table `Prescription`
--

CREATE TABLE `Prescription` (
  `Prescription_ID` int(11) NOT NULL,
  `Appointment_ID` int(11) NOT NULL,
  `Prescription_Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Prescription`
--

INSERT INTO `Prescription` (`Prescription_ID`, `Appointment_ID`, `Prescription_Date`) VALUES
(1, 1, '2024-03-02'),
(2, 2, '2024-05-03'),
(3, 3, '2024-02-12'),
(4, 4, '2024-05-03'),
(5, 5, '2024-04-17'),
(6, 6, '2024-01-26'),
(7, 9, '2024-03-02'),
(8, 10, '2024-06-17'),
(9, 11, '2024-05-04'),
(10, 12, '2024-04-20'),
(11, 13, '2024-12-22'),
(12, 14, '2024-05-03');

-- --------------------------------------------------------

--
-- Table structure for table `Prescription_medicine`
--

CREATE TABLE `Prescription_medicine` (
  `Prescription_ID` int(11) NOT NULL,
  `Medicine_ID` int(11) NOT NULL,
  `Dosage` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Prescription_medicine`
--

INSERT INTO `Prescription_medicine` (`Prescription_ID`, `Medicine_ID`, `Dosage`) VALUES
(1, 1, '2x1'),
(1, 2, '3x1'),
(2, 3, '1x1'),
(3, 4, '1x1'),
(4, 5, '2x1'),
(4, 6, '1x2'),
(5, 7, '1x1'),
(6, 8, '1x1'),
(7, 9, '1x1'),
(8, 10, '1x2'),
(9, 11, '1x1'),
(10, 8, '1x1'),
(11, 12, '1x1'),
(11, 13, '1x1'),
(12, 14, '1x2');

-- --------------------------------------------------------

--
-- Table structure for table `Test`
--

CREATE TABLE `Test` (
  `Test_ID` int(11) NOT NULL,
  `Test_Name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Test`
--

INSERT INTO `Test` (`Test_ID`, `Test_Name`) VALUES
(0, 'No Test'),
(1, 'Skin Biopsy'),
(2, 'ECG'),
(3, 'Allergy Test'),
(4, 'MRI'),
(5, 'Blood Sugar Test'),
(6, 'CT Scan'),
(7, 'Tonometry'),
(8, 'Slit-Lamp Exam'),
(9, 'Blood Pressure Measurement'),
(10, 'EEG');

-- --------------------------------------------------------
--
-- Table structure for table `Bill`
--

CREATE TABLE `Bill` (
  `Bill_ID` int(11) NOT NULL,
  `Patient_ID` bigint(20) NOT NULL,
  `Appointment_ID` int(11) NOT NULL,
  `Total_Amount` decimal(10,2) NOT NULL,
  `Issue_Date` date NOT NULL,
  `Status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Bill`
--

INSERT INTO `Bill` (`Bill_ID`, `Patient_ID`, `Appointment_ID`, `Total_Amount`, `Issue_Date`, `Status`) VALUES
(1, 43543543565, 1, 100.00, '2024-03-02', 'Unpaid'),
(2, 43543543565, 2, 250.50, '2024-05-03', 'Paid'),
(3, 23423423477, 3, 75.25, '2024-02-12', 'Unpaid');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Admin`
--
ALTER TABLE `Admin`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD UNIQUE KEY `Admin_Email` (`Admin_Email`);

--
-- Indexes for table `Appointment`
--
ALTER TABLE `Appointment`
  ADD PRIMARY KEY (`Appointment_ID`),
  ADD KEY `Patient_ID` (`Patient_ID`),
  ADD KEY `Doctor_ID` (`Doctor_ID`),
  ADD KEY `Nurse_ID` (`Nurse_ID`),
  ADD KEY `Follow_Up_Appointment_ID` (`Follow_Up_Appointment_ID`);

--
-- Indexes for table `Appointment_Diagnosis`
--
ALTER TABLE `Appointment_Diagnosis`
  ADD PRIMARY KEY (`Appointment_ID`,`Diagnosis_ID`),
  ADD KEY `Diagnosis_ID` (`Diagnosis_ID`);

--
-- Indexes for table `Appointment_Test`
--
ALTER TABLE `Appointment_Test`
  ADD PRIMARY KEY (`Appointment_ID`,`Test_ID`),
  ADD KEY `Test_ID` (`Test_ID`);

--
-- Indexes for table `Appointment_Treatment`
--
ALTER TABLE `Appointment_Treatment`
  ADD PRIMARY KEY (`Appointment_ID`,`Medical_Treatment_ID`),
  ADD KEY `Medical_Treatment_ID` (`Medical_Treatment_ID`);

--
-- Indexes for table `Clinic`
--
ALTER TABLE `Clinic`
  ADD PRIMARY KEY (`Clinic_ID`);

--
-- Indexes for table `Diagnosis`
--
ALTER TABLE `Diagnosis`
  ADD PRIMARY KEY (`Diagnosis_ID`);

--
-- Indexes for table `Doctor`
--
ALTER TABLE `Doctor`
  ADD PRIMARY KEY (`Doctor_ID`),
  ADD UNIQUE KEY `Doctor_Email` (`Doctor_Email`),
  ADD KEY `Clinic_ID` (`Clinic_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `Medical_Treatment`
--
ALTER TABLE `Medical_Treatment`
  ADD PRIMARY KEY (`Medical_Treatment_ID`);

--
-- Indexes for table `Medicine`
--
ALTER TABLE `Medicine`
  ADD PRIMARY KEY (`Medicine_ID`);

--
-- Indexes for table `Nurse`
--
ALTER TABLE `Nurse`
  ADD PRIMARY KEY (`Nurse_ID`),
  ADD UNIQUE KEY `Nurse_Email` (`Nurse_Email`),
  ADD KEY `Clinic_ID` (`Clinic_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `Patient`
--
ALTER TABLE `Patient`
  ADD PRIMARY KEY (`Patient_ID`);

--
-- Indexes for table `Prescription`
--
ALTER TABLE `Prescription`
  ADD PRIMARY KEY (`Prescription_ID`),
  ADD KEY `Appointment_ID` (`Appointment_ID`);

--
-- Indexes for table `Prescription_medicine`
--
ALTER TABLE `Prescription_medicine`
  ADD PRIMARY KEY (`Prescription_ID`,`Medicine_ID`),
  ADD KEY `Medicine_ID` (`Medicine_ID`);

--
-- Indexes for table `Test`
--
ALTER TABLE `Test`
  ADD PRIMARY KEY (`Test_ID`);

--
-- Indexes for table `Bill`
--
ALTER TABLE `Bill`
  ADD PRIMARY KEY (`Bill_ID`),
  ADD KEY `Patient_ID` (`Patient_ID`),
  ADD UNIQUE KEY `uniq_bill_appointment` (`Appointment_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Admin`
--
ALTER TABLE `Admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `Appointment`
--
ALTER TABLE `Appointment`
  MODIFY `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `Clinic`
--
ALTER TABLE `Clinic`
  MODIFY `Clinic_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Diagnosis`
--
ALTER TABLE `Diagnosis`
  MODIFY `Diagnosis_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `Doctor`
--
ALTER TABLE `Doctor`
  MODIFY `Doctor_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `Medical_Treatment`
--
ALTER TABLE `Medical_Treatment`
  MODIFY `Medical_Treatment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `Medicine`
--
ALTER TABLE `Medicine`
  MODIFY `Medicine_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `Nurse`
--
ALTER TABLE `Nurse`
  MODIFY `Nurse_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `Prescription`
--
ALTER TABLE `Prescription`
  MODIFY `Prescription_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `Test`
--
ALTER TABLE `Test`
  MODIFY `Test_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `Bill`
--
ALTER TABLE `Bill`
  MODIFY `Bill_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Appointment`
--
ALTER TABLE `Appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`Patient_ID`) REFERENCES `Patient` (`Patient_ID`),
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`Doctor_ID`) REFERENCES `Doctor` (`Doctor_ID`),
  ADD CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`Nurse_ID`) REFERENCES `Nurse` (`Nurse_ID`),
  ADD CONSTRAINT `appointment_ibfk_4` FOREIGN KEY (`Follow_Up_Appointment_ID`) REFERENCES `Appointment` (`Appointment_ID`) ON DELETE SET NULL;

--
-- Constraints for table `Appointment_Diagnosis`
--
ALTER TABLE `Appointment_Diagnosis`
  ADD CONSTRAINT `appointment_diagnosis_ibfk_1` FOREIGN KEY (`Appointment_ID`) REFERENCES `Appointment` (`Appointment_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_diagnosis_ibfk_2` FOREIGN KEY (`Diagnosis_ID`) REFERENCES `Diagnosis` (`Diagnosis_ID`);

--
-- Constraints for table `Appointment_Test`
--
ALTER TABLE `Appointment_Test`
  ADD CONSTRAINT `appointment_test_ibfk_1` FOREIGN KEY (`Appointment_ID`) REFERENCES `Appointment` (`Appointment_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_test_ibfk_2` FOREIGN KEY (`Test_ID`) REFERENCES `Test` (`Test_ID`);

--
-- Constraints for table `Appointment_Treatment`
--
ALTER TABLE `Appointment_Treatment`
  ADD CONSTRAINT `appointment_treatment_ibfk_1` FOREIGN KEY (`Appointment_ID`) REFERENCES `Appointment` (`Appointment_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_treatment_ibfk_2` FOREIGN KEY (`Medical_Treatment_ID`) REFERENCES `Medical_Treatment` (`Medical_Treatment_ID`);

--
-- Constraints for table `Doctor`
--
ALTER TABLE `Doctor`
  ADD CONSTRAINT `doctor_ibfk_1` FOREIGN KEY (`Clinic_ID`) REFERENCES `Clinic` (`Clinic_ID`),
  ADD CONSTRAINT `doctor_ibfk_2` FOREIGN KEY (`Admin_ID`) REFERENCES `Admin` (`Admin_ID`);

--
-- Constraints for table `Nurse`
--
ALTER TABLE `Nurse`
  ADD CONSTRAINT `nurse_ibfk_1` FOREIGN KEY (`Clinic_ID`) REFERENCES `Clinic` (`Clinic_ID`),
  ADD CONSTRAINT `nurse_ibfk_2` FOREIGN KEY (`Admin_ID`) REFERENCES `Admin` (`Admin_ID`);

--
-- Constraints for table `Prescription`
--
ALTER TABLE `Prescription`
  ADD CONSTRAINT `prescription_ibfk_1` FOREIGN KEY (`Appointment_ID`) REFERENCES `Appointment` (`Appointment_ID`) ON DELETE CASCADE;

--
-- Constraints for table `Prescription_medicine`
--
ALTER TABLE `Prescription_medicine`
  ADD CONSTRAINT `prescription_medicine_ibfk_1` FOREIGN KEY (`Prescription_ID`) REFERENCES `Prescription` (`Prescription_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_medicine_ibfk_2` FOREIGN KEY (`Medicine_ID`) REFERENCES `Medicine` (`Medicine_ID`);

--
-- Constraints for table `Bill`
--
ALTER TABLE `Bill`
  ADD CONSTRAINT `bill_ibfk_1` FOREIGN KEY (`Patient_ID`) REFERENCES `Patient` (`Patient_ID`),
  ADD CONSTRAINT `bill_ibfk_2` FOREIGN KEY (`Appointment_ID`) REFERENCES `Appointment` (`Appointment_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
