-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 07:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `employee` 
--

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `consultation_date` date DEFAULT NULL,
  `consultation_time` time DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `o2_saturation` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `height` decimal(6,2) DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `chief_complaint` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `medical_certificate` varchar(10) DEFAULT NULL,
  `certificate_copies` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `consultation_scan_path` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `age`, `sex`, `birthday`, `address`, `contact`, `department`, `civil_status`, `date_created`, `consultation_scan_path`) VALUES
(7, 'lorbs', 21, 'Male', '2026-03-30', 'san miguel', '11111111', 'IT', 'windows', '2026-03-30 01:34:24', NULL),
(9, 'Bane', 25, 'Male', '2003-10-06', 'san miguel', '11111111', 'IT', '', '2026-03-30 08:16:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_health_profiles`
--

CREATE TABLE `employee_health_profiles` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `class_type` varchar(50) DEFAULT NULL,
  `profile_data` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_health_profiles`
--

INSERT INTO `employee_health_profiles` (`id`, `employee_id`, `class_type`, `profile_data`, `created_at`, `updated_at`) VALUES
(2, 9, '', '{\"personal\":{\"profile_date\":\"2026-04-13\",\"clinic_file_number\":\"00009\",\"department\":\"IT\",\"full_name\":\"Bane\",\"age_sex\":\"25 Male\",\"birthday\":\"2003-10-06\",\"age\":25,\"gender\":\"Male\",\"contact_number\":\"11111111\",\"religion\":\"\",\"occupation\":\"IT\",\"civil_status\":\"\",\"home_address\":\"san miguel\",\"municipal_address\":\"\"},\"emergency\":{\"complete_name\":\"\",\"relationship\":\"\",\"address\":\"\",\"contact_number\":\"\"},\"medical\":{\"class_type\":\"\",\"medical_condition_findings\":\"\"},\"classification\":{\"final\":\"\",\"notes\":\"\"},\"remarks\":{\"general\":\"\",\"examined_by\":\"\",\"exam_date\":\"2026-04-13\",\"license_number\":\"\"},\"personal_social_history\":{\"smoking\":\"\",\"smoking_pack_day\":\"\",\"smoking_years\":\"\",\"pack_day_years\":\"\",\"alcohol_drinking\":\"\",\"alcohol_type_beer\":false,\"alcohol_type_others\":false,\"alcohol_bottles_session\":\"\",\"alcohol_occasionally\":false,\"alcohol_monthly\":false,\"alcohol_type_frequency\":\"\",\"illegal_drug_use\":\"\",\"illegal_drug_use_specify\":\"\",\"sexually_active\":\"\",\"no_of_sexual_partners\":0,\"partners_male\":false,\"partners_female\":false,\"partners_both\":false},\"past_medical_history\":{\"allergy\":false,\"food_allergy\":false,\"food_allergy_specify\":\"\",\"drug_allergy\":false,\"asthma\":false,\"cancer\":false,\"coronary_artery_disease\":false,\"hypertension_elevated_bp\":false,\"hypertension_specify\":\"\",\"congenital_heart_disorder\":false,\"peptic_ulcer\":false,\"psychological_disorder\":false,\"psychological_disorder_specify\":\"\",\"thyroid_disease\":false,\"pcos\":false,\"epilepsy_seizure_disorder\":false,\"epilepsy_specify\":\"\",\"skin_disorder\":false,\"skin_disorder_specify\":\"\",\"tuberculosis\":false,\"tuberculosis_specify\":\"\",\"hepatitis\":false,\"hepatitis_specify\":\"\",\"diabetes_heart_disorder\":false,\"other_findings\":\"\",\"allergy_specify_type\":\"\"},\"immunizations\":{\"immunized_against_covid_19\":\"\",\"newborn_immunizations\":\"\",\"hpv_doses\":\"\",\"tetanus_doses\":\"\",\"influenza_year\":\"\",\"pneumococcal_doses\":\"\",\"covid_brand\":\"\",\"covid_1st_dose\":\"\",\"covid_2nd_dose\":\"\",\"covid_1st_booster\":\"\",\"covid_2nd_booster\":\"\",\"unvaccinated_reason\":\"\",\"other_immunizations\":\"\",\"physical_notes_other_findings\":\"\"},\"hospital_admission\":{\"hospital_admission_diagnosis1\":\"\",\"hospital_admission_when1\":\"\",\"hospital_admission_diagnosis2\":\"\",\"hospital_admission_when2\":\"\",\"past_surgical_operation1_type\":\"\",\"past_surgical_operation1_when\":\"\",\"past_surgical_operation2_type\":\"\",\"past_surgical_operation2_when\":\"\",\"disability\":\"\",\"disability_registered\":\"\",\"willing_donate_blood\":\"\",\"family_history_mother\":\"\",\"family_history_father\":\"\"},\"physical_screening\":{\"height_cm\":\"\",\"weight_kg\":\"\",\"blood_pressure\":\"\",\"pulse_rate\":\"\",\"respiration\":\"\",\"spo2\":\"\",\"bmi\":\"\",\"bmi_class\":\"\",\"visual_acuity_type\":\"\",\"right_vision_od\":\"\",\"left_vision_os\":\"\",\"ishihara_color_vision\":\"\",\"hearing_ad\":\"\",\"hearing_as\":\"\",\"speech\":\"\",\"pe_skin_status\":\"Normal\",\"pe_skin_findings\":\"\",\"pe_head_neck_scalp_status\":\"Normal\",\"pe_head_neck_scalp_findings\":\"\",\"pe_eyes_external_status\":\"Normal\",\"pe_eyes_external_findings\":\"\",\"pe_pupils_status\":\"Normal\",\"pe_pupils_findings\":\"\",\"pe_ears_nose_sinuses_status\":\"Normal\",\"pe_ears_nose_sinuses_findings\":\"\",\"pe_mouth_throat_status\":\"Normal\",\"pe_mouth_throat_findings\":\"\",\"pe_neck_lymph_thyroid_status\":\"Normal\",\"pe_neck_lymph_thyroid_findings\":\"\",\"pe_chest_breast_axilla_status\":\"Normal\",\"pe_chest_breast_axilla_findings\":\"\",\"pe_lungs_status\":\"Normal\",\"pe_lungs_findings\":\"\",\"pe_heart_valvular_status\":\"Normal\",\"pe_heart_valvular_findings\":\"\",\"pe_back_abdomen_status\":\"Normal\",\"pe_back_abdomen_findings\":\"\",\"pe_genitalia_status\":\"Normal\",\"pe_genitalia_findings\":\"\",\"pe_anus_rectum_status\":\"Normal\",\"pe_anus_rectum_findings\":\"\",\"pe_extremities_status\":\"Normal\",\"pe_extremities_findings\":\"\"},\"ancillary\":{\"anc_cbc_status\":\"Normal\",\"anc_cbc_findings\":\"\",\"anc_fecalysis_status\":\"Normal\",\"anc_fecalysis_findings\":\"\",\"anc_pregnancy_test_status\":\"Negative\",\"anc_pregnancy_test_findings\":\"\",\"anc_urinalysis_status\":\"Normal\",\"anc_urinalysis_findings\":\"\",\"anc_chest_xray_status\":\"Normal\",\"anc_chest_xray_findings\":\"\",\"anc_hbsag_status\":\"Non-Reactive\",\"anc_hbsag_findings\":\"\",\"anc_mmse_status\":\"Normal\",\"anc_mmse_findings\":\"\",\"blood_type\":\"\",\"summary\":\"\"},\"screening_height\":\"\",\"screening_weight\":\"\",\"screening_blood_pressure\":\"\",\"screening_pulse_rate\":\"\",\"screening_respiration\":\"\",\"screening_spo2\":\"\",\"screening_bmi\":\"\",\"screening_bmi_class\":\"\",\"alcohol_type_beer\":false,\"alcohol_type_others\":false,\"alcohol_occasionally\":false,\"alcohol_monthly\":false,\"partners_male\":false,\"partners_female\":false,\"partners_both\":false,\"delivery_type_normal\":false,\"delivery_type_caesarean\":false,\"delivery_location_hospital\":false,\"delivery_location_home\":false,\"neuro_normal_thought\":false,\"neuro_normal_emotional\":false,\"neuro_normal_psychological\":false,\"heent_anicteric_sclerae\":false,\"heent_perla\":false,\"heent_aural_discharge\":false,\"heent_intact_tympanic\":false,\"heent_nasal_flaring\":false,\"heent_nasal_discharge\":false,\"heent_tonsillopharyngeal\":false,\"heent_hypertension_tonsils\":false,\"heent_palpable_mass\":false,\"heent_exudates\":false,\"resp_normal_heart_beat_sounds\":false,\"resp_symmetrical_chest\":false,\"resp_restrictions\":false,\"resp_crackles_rates\":false,\"resp_wheezing\":false,\"resp_clear_breath_sounds\":false,\"cardio_normal_heart_beat\":false,\"cardio_clubbing_fingers\":false,\"cardio_finger_discoloration\":false,\"cardio_heart_murmur\":false,\"cardio_irregular_heart_beat\":false,\"cardio_palpitations\":false,\"cardio_fluid_volume_excess\":false,\"cardio_fatigue_mobility\":false,\"gastro_regular_bowel\":false,\"gastro_constipation\":false,\"gastro_loose_bowel\":false,\"gastro_hyperacidity\":false,\"urinary_flank_pain\":false,\"urinary_painful_urination\":false,\"integ_pallor\":false,\"integ_rashes\":false,\"integ_jaundice\":false,\"integ_good_skin_turgor\":false,\"integ_cyanosis\":false,\"extrem_gross_deformity\":false,\"extrem_normal_gait\":false,\"extrem_normal_strength\":false,\"emergency_complete_name\":\"\",\"emergency_contact_number\":\"\",\"emergency_address\":\"\",\"emergency_relationship\":\"\",\"anc_blood_type\":\"\",\"anc_summary\":\"\",\"classification_notes\":\"\",\"remarks_general\":\"\",\"remarks_examined_by\":\"\",\"remarks_exam_date\":\"2026-04-13\",\"remarks_license_number\":\"\",\"maintenance_medication\":\"\",\"menarche\":\"\",\"last_menstrual_period\":\"\",\"period_duration\":\"\",\"interval_cycle\":\"\",\"pads_per_day\":\"\",\"onset_sexual_intercourse\":\"\",\"birth_control_method\":\"\",\"menopausal_age\":\"\",\"pregnant_now\":\"\",\"pregnant_months\":\"\",\"prenatal_where\":\"\",\"pregnancy_test_result\":\"\",\"gravida\":\"\",\"para\":\"\",\"term\":\"\",\"abortion\":\"\",\"live_birth\":\"\",\"delivery_complications\":\"\",\"family_planning_type\":\"\",\"family_planning_years\":\"\",\"neuro_feel_now\":\"\",\"neuro_years\":\"\",\"gastro_number_per_day\":\"\",\"gastro_borborygmi\":\"\",\"urinary_number_per_day\":\"\",\"urinary_amount_per_voiding\":\"\",\"extrem_others\":\"\",\"other_pertinent_findings\":\"\"}', '2026-04-13 05:25:58', '2026-04-13 05:25:58');

-- --------------------------------------------------------

--
-- Table structure for table `health_profiles`
--

CREATE TABLE `health_profiles` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `prescriber_name` varchar(100) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `medicine_id`, `quantity`, `action`, `patient_name`, `prescriber_name`, `date`) VALUES
(19, 18, 668, 'New Batch Added', NULL, NULL, '2026-04-28 01:27:51'),
(20, 19, 18, 'New Batch Added', NULL, NULL, '2026-04-28 01:29:36');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  `batch_number` int(11) DEFAULT 1,
  `type` varchar(20) DEFAULT 'medicine',
  `category` varchar(50) DEFAULT 'General',
  `unit` varchar(20) DEFAULT 'pcs',
  `quantity` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `brand_serial` varchar(255) DEFAULT NULL,
  `ris_id` varchar(255) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `qty_serviceable` int(11) DEFAULT 0,
  `qty_unserviceable` int(11) DEFAULT 0,
  `qty_repair` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `label`, `batch_number`, `type`, `category`, `unit`, `quantity`, `is_archived`, `brand_serial`, `ris_id`, `color`, `date_acquired`, `qty_serviceable`, `qty_unserviceable`, `qty_repair`, `remarks`, `expiration_date`, `created_at`) VALUES
(17, 'alcohol', 'Disinfection', 1, 'consumable', 'Consumable', 'pcs', 15, 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2026-05-09', '2026-04-23 03:30:40'),
(18, 'Aluminum Hydroxide Magnesium Hydroxide Simeticone', '178mg/233mg,Branded', 1, 'medicine', 'General', 'pcs', 668, 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2028-02-28', '2026-04-28 01:27:51'),
(19, 'Azithromycin', '500g', 1, 'medicine', 'General', 'box', 18, 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, '2028-04-28', '2026-04-28 01:29:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `consultation_date` (`consultation_date`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_health_profiles`
--
ALTER TABLE `employee_health_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `employee_id_2` (`employee_id`);

--
-- Indexes for table `health_profiles`
--
ALTER TABLE `health_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_health_profiles`
--
ALTER TABLE `employee_health_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `health_profiles`
--
ALTER TABLE `health_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
