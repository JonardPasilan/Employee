<?php
require_once __DIR__ . '/db.php';

function createProfileTableIfNeeded(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS employee_health_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL UNIQUE,
            class_type VARCHAR(50) NULL,
            profile_data LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (employee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function postStr(string $key, $default = ''): string {
    if (!isset($_POST[$key])) return (string)$default;
    return trim((string)$_POST[$key]);
}

function postInt(string $key, int $default = 0): int {
    if (!isset($_POST[$key])) return $default;
    return intval($_POST[$key]);
}

function postBool(string $key): bool {
    return isset($_POST[$key]) && $_POST[$key] !== '0' && $_POST[$key] !== '';
}

/** @return array<string,mixed> */
function loadExistingProfile(mysqli $conn, int $employee_id): array {
    if ($employee_id <= 0) return [];
    $stmt = $conn->prepare("SELECT profile_data FROM employee_health_profiles WHERE employee_id=? LIMIT 1");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row || empty($row['profile_data'])) return [];
    $d = json_decode($row['profile_data'], true);
    return is_array($d) ? $d : [];
}

if (isset($_POST['save'])) {
    createProfileTableIfNeeded($conn);

    $employee_id = postInt('employee_id', 0);
    $existing = loadExistingProfile($conn, $employee_id);

    $full_name = postStr('full_name');
    $birthday = postStr('birthday');
    $age_sex_raw = postStr('age_sex');
    $age = postInt('age', 0);
    $gender = postStr('gender');
    if ($age <= 0 && preg_match('/^(\d+)/', trim($age_sex_raw), $m)) {
        $age = (int)$m[1];
    }
    if ($gender === '' && preg_match('/^\d+\s+(.+)$/u', trim($age_sex_raw), $m)) {
        $gender = trim($m[1]);
    }
    if ($employee_id > 0) {
        $er = $conn->query("SELECT age, sex FROM employees WHERE id=" . intval($employee_id) . " LIMIT 1");
        $prev = ($er && $er->num_rows > 0) ? $er->fetch_assoc() : null;
        if ($age <= 0 && $prev) $age = (int)$prev['age'];
        if ($gender === '' && $prev) $gender = (string)$prev['sex'];
    }

    $contact_number = postStr('contact_number');
    $religion = postStr('religion');
    $occupation = postStr('occupation');
    $department = postStr('department');
    $deptForEmployee = $department !== '' ? $department : $occupation;
    $civil_status = postStr('civil_status');
    $home_address = postStr('home_address');

    if ($employee_id > 0) {
        $stmt = $conn->prepare("UPDATE employees SET name=?, age=?, sex=?, birthday=?, address=?, contact=?, department=?, civil_status=? WHERE id=?");
        $stmt->bind_param(
            "sissssssi",
            $full_name,
            $age,
            $gender,
            $birthday,
            $home_address,
            $contact_number,
            $deptForEmployee,
            $civil_status,
            $employee_id
        );
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO employees (name, age, sex, birthday, address, contact, department, civil_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssss", $full_name, $age, $gender, $birthday, $home_address, $contact_number, $deptForEmployee, $civil_status);
        $stmt->execute();
        $employee_id = $stmt->insert_id;
        $stmt->close();
    }

    $classification_final = postStr('classification_final');
    $medical_class = $classification_final !== '' ? $classification_final : postStr('medical_class');
    if ($medical_class === '' && !empty($existing['medical']['class_type'])) {
        $medical_class = (string)$existing['medical']['class_type'];
    }

    $classification_notes = postStr('classification_notes');

    $emergency_complete_name = postStr('emergency_complete_name');
    $emergency_relationship = postStr('emergency_relationship');
    $emergency_address = postStr('emergency_address');
    $emergency_contact_number = postStr('emergency_contact_number');

    $smoking = postStr('smoking');
    $alcohol_drinking = postStr('alcohol_drinking');
    $illegal_drug_use = postStr('illegal_drug_use');
    $illegal_drug_use_specify = postStr('illegal_drug_use_specify');
    $sexually_active = postStr('sexually_active');
    $no_of_sexual_partners = postInt('no_of_sexual_partners', 0);

    $past_medical = [
        'allergy' => postBool('past_allergy'),
        'food_allergy' => postBool('past_food_allergy'),
        'food_allergy_specify' => postStr('food_allergy_specify'),
        'drug_allergy' => postBool('past_drug_allergy'),
        'asthma' => postBool('past_asthma'),
        'cancer' => postBool('past_cancer'),
        'coronary_artery_disease' => postBool('past_coronary_artery_disease'),
        'hypertension_elevated_bp' => postBool('past_hypertension_elevated_bp'),
        'hypertension_specify' => postStr('hypertension_specify'),
        'congenital_heart_disorder' => postBool('past_congenital_heart_disorder'),
        'peptic_ulcer' => postBool('past_peptic_ulcer'),
        'psychological_disorder' => postBool('past_psychological_disorder'),
        'psychological_disorder_specify' => postStr('psychological_disorder_specify'),
        'thyroid_disease' => postBool('past_thyroid_disease'),
        'pcos' => postBool('past_pcos'),
        'epilepsy_seizure_disorder' => postBool('past_epilepsy_seizure_disorder'),
        'epilepsy_specify' => postStr('epilepsy_specify'),
        'skin_disorder' => postBool('past_skin_disorder'),
        'skin_disorder_specify' => postStr('skin_disorder_specify'),
        'tuberculosis' => postBool('past_tuberculosis'),
        'tuberculosis_specify' => postStr('tuberculosis_specify'),
        'hepatitis' => postBool('past_hepatitis'),
        'hepatitis_specify' => postStr('hepatitis_specify'),
        'diabetes_heart_disorder' => postBool('past_diabetes_heart_disorder'),
        'other_findings' => postStr('other_findings'),
        'allergy_specify_type' => postStr('allergy_specify_type'),
    ];

    $examCategories = [
        'skin', 'head_neck_scalp', 'eyes_external', 'pupils', 'ears_nose_sinuses', 'mouth_throat',
        'neck_lymph_thyroid', 'chest_breast_axilla', 'lungs', 'heart_valvular', 'back_abdomen',
        'genitalia', 'anus_rectum', 'extremities',
    ];
    $prevPh = is_array($existing['physical_screening'] ?? null) ? $existing['physical_screening'] : [];
    $phVal = function (string $key) use ($prevPh): string {
        return isset($_POST[$key]) ? trim((string)$_POST[$key]) : (string)($prevPh[$key] ?? '');
    };
    $physical_screening = [
        'height_cm' => $phVal('height_cm'),
        'weight_kg' => $phVal('weight_kg'),
        'blood_pressure' => $phVal('blood_pressure'),
        'pulse_rate' => $phVal('pulse_rate'),
        'respiration' => $phVal('respiration'),
        'spo2' => $phVal('spo2'),
        'bmi' => $phVal('bmi'),
        'bmi_class' => $phVal('bmi_class'),
        'visual_acuity_type' => $phVal('visual_acuity_type'),
        'right_vision_od' => $phVal('right_vision_od'),
        'left_vision_os' => $phVal('left_vision_os'),
        'ishihara_color_vision' => $phVal('ishihara_color_vision'),
        'hearing_ad' => $phVal('hearing_ad'),
        'hearing_as' => $phVal('hearing_as'),
        'speech' => $phVal('speech'),
    ];
    foreach ($examCategories as $ek) {
        $sk = 'pe_' . $ek . '_status';
        $fk = 'pe_' . $ek . '_findings';
        $physical_screening[$sk] = isset($_POST[$sk]) ? trim((string)$_POST[$sk]) : (string)($prevPh[$sk] ?? 'Normal');
        if ($physical_screening[$sk] === '') {
            $physical_screening[$sk] = 'Normal';
        }
        $physical_screening[$fk] = isset($_POST[$fk]) ? trim((string)$_POST[$fk]) : (string)($prevPh[$fk] ?? '');
    }

    $ancillaryTests = ['cbc', 'fecalysis', 'pregnancy_test', 'urinalysis', 'chest_xray', 'hbsag', 'mmse'];
    $ancillary = is_array($existing['ancillary'] ?? null) ? $existing['ancillary'] : [];
    foreach ($ancillaryTests as $t) {
        $sk = 'anc_' . $t . '_status';
        $fk = 'anc_' . $t . '_findings';
        if (isset($_POST[$sk])) {
            $ancillary[$sk] = postStr($sk);
        }
        if (isset($_POST[$fk])) {
            $ancillary[$fk] = postStr($fk);
        }
    }
    $ancillary['blood_type'] = postStr('anc_blood_type');
    $ancillary['summary'] = postStr('anc_summary');

    $hospital_admission = array_merge(
        is_array($existing['hospital_admission'] ?? null) ? $existing['hospital_admission'] : [],
        [
            'hospital_admission_diagnosis1' => postStr('hospital_admission_diagnosis1'),
            'hospital_admission_when1' => postStr('hospital_admission_when1'),
            'hospital_admission_diagnosis2' => postStr('hospital_admission_diagnosis2'),
            'hospital_admission_when2' => postStr('hospital_admission_when2'),
            'past_surgical_operation1_type' => postStr('past_surgical_operation1_type'),
            'past_surgical_operation1_when' => postStr('past_surgical_operation1_when'),
            'past_surgical_operation2_type' => postStr('past_surgical_operation2_type'),
            'past_surgical_operation2_when' => postStr('past_surgical_operation2_when'),
            'disability' => postStr('disability'),
            'disability_registered' => postStr('disability_registered'),
            'willing_donate_blood' => postStr('willing_donate_blood'),
            'family_history_mother' => postStr('family_history_mother'),
            'family_history_father' => postStr('family_history_father'),
        ]
    );

    $immunizations = array_merge(
        is_array($existing['immunizations'] ?? null) ? $existing['immunizations'] : [],
        [
            'immunized_against_covid_19' => postStr('immunized_against_covid_19'),
            'newborn_immunizations' => postStr('newborn_immunizations'),
            'hpv_doses' => postStr('hpv_doses'),
            'tetanus_doses' => postStr('tetanus_doses'),
            'influenza_year' => postStr('influenza_year'),
            'pneumococcal_doses' => postStr('pneumococcal_doses'),
            'covid_brand' => postStr('covid_brand'),
            'covid_1st_dose' => postStr('covid_1st_dose'),
            'covid_2nd_dose' => postStr('covid_2nd_dose'),
            'covid_1st_booster' => postStr('covid_1st_booster'),
            'covid_2nd_booster' => postStr('covid_2nd_booster'),
            'unvaccinated_reason' => postStr('unvaccinated_reason'),
            'other_immunizations' => postStr('other_immunizations'),
            'physical_notes_other_findings' => postStr('physical_notes_other_findings'),
        ]
    );

    $newCore = [
        'personal' => array_merge(is_array($existing['personal'] ?? null) ? $existing['personal'] : [], [
            'profile_date' => postStr('profile_date'),
            'clinic_file_number' => postStr('clinic_file_number'),
            'department' => $department,
            'full_name' => $full_name,
            'age_sex' => $age_sex_raw,
            'birthday' => $birthday,
            'age' => $age,
            'gender' => $gender,
            'contact_number' => $contact_number,
            'religion' => $religion,
            'occupation' => $occupation,
            'civil_status' => $civil_status,
            'home_address' => $home_address,
            'municipal_address' => postStr('municipal_address'),
            'category' => postStr('category'),
        ]),
        'emergency' => [
            'complete_name' => $emergency_complete_name,
            'relationship' => $emergency_relationship,
            'address' => $emergency_address,
            'contact_number' => $emergency_contact_number,
        ],
        'medical' => [
            'class_type' => $medical_class,
            'medical_condition_findings' => $classification_notes !== '' ? $classification_notes : postStr('medical_condition_findings'),
        ],
        'classification' => [
            'final' => $classification_final,
            'notes' => $classification_notes,
        ],
        'remarks' => [
            'general' => postStr('remarks_general'),
            'examined_by' => postStr('remarks_examined_by'),
            'exam_date' => postStr('remarks_exam_date'),
            'license_number' => postStr('remarks_license_number'),
        ],
        'personal_social_history' => [
            'smoking' => $smoking,
            'smoking_pack_day' => postStr('smoking_pack_day'),
            'smoking_years' => postStr('smoking_years'),
            'pack_day_years' => postStr('pack_day_years'),
            'alcohol_drinking' => $alcohol_drinking,
            'alcohol_type_beer' => postBool('alcohol_type_beer'),
            'alcohol_type_others' => postBool('alcohol_type_others'),
            'alcohol_bottles_session' => postStr('alcohol_bottles_session'),
            'alcohol_occasionally' => postBool('alcohol_occasionally'),
            'alcohol_monthly' => postBool('alcohol_monthly'),
            'alcohol_type_frequency' => postStr('alcohol_type_frequency'),
            'illegal_drug_use' => $illegal_drug_use,
            'illegal_drug_use_specify' => $illegal_drug_use_specify,
            'sexually_active' => $sexually_active,
            'no_of_sexual_partners' => $no_of_sexual_partners,
            'partners_male' => postBool('partners_male'),
            'partners_female' => postBool('partners_female'),
            'partners_both' => postBool('partners_both'),
        ],
        'past_medical_history' => $past_medical,
        'immunizations' => $immunizations,
        'hospital_admission' => $hospital_admission,
        'physical_screening' => $physical_screening,
        'ancillary' => $ancillary,
        'screening_height' => postStr('screening_height'),
        'screening_weight' => postStr('screening_weight'),
        'screening_blood_pressure' => postStr('screening_blood_pressure'),
        'screening_pulse_rate' => postStr('screening_pulse_rate'),
        'screening_respiration' => postStr('screening_respiration'),
        'screening_spo2' => postStr('screening_spo2'),
        'screening_bmi' => postStr('screening_bmi'),
        'screening_bmi_class' => postStr('screening_bmi_class'),
    ];

    $profile = array_replace_recursive($existing, $newCore);

    $checkboxRoots = [
        'alcohol_type_beer', 'alcohol_type_others', 'alcohol_occasionally', 'alcohol_monthly',
        'partners_male', 'partners_female', 'partners_both',
        'delivery_type_normal', 'delivery_type_caesarean', 'delivery_location_hospital', 'delivery_location_home',
        'neuro_normal_thought', 'neuro_normal_emotional', 'neuro_normal_psychological',
        'heent_anicteric_sclerae', 'heent_perla', 'heent_aural_discharge', 'heent_intact_tympanic',
        'heent_nasal_flaring', 'heent_nasal_discharge', 'heent_tonsillopharyngeal', 'heent_hypertension_tonsils',
        'heent_palpable_mass', 'heent_exudates',
        'resp_normal_heart_beat_sounds', 'resp_symmetrical_chest', 'resp_restrictions', 'resp_crackles_rates',
        'resp_wheezing', 'resp_clear_breath_sounds',
        'cardio_normal_heart_beat', 'cardio_clubbing_fingers', 'cardio_finger_discoloration', 'cardio_heart_murmur',
        'cardio_irregular_heart_beat', 'cardio_palpitations', 'cardio_fluid_volume_excess', 'cardio_fatigue_mobility',
        'gastro_regular_bowel', 'gastro_constipation', 'gastro_loose_bowel', 'gastro_hyperacidity',
        'urinary_flank_pain', 'urinary_painful_urination',
        'integ_pallor', 'integ_rashes', 'integ_jaundice', 'integ_good_skin_turgor', 'integ_cyanosis',
        'extrem_gross_deformity', 'extrem_normal_gait', 'extrem_normal_strength',
    ];
    foreach ($checkboxRoots as $cb) {
        $profile[$cb] = postBool($cb);
    }

    $peKeys = [];
    foreach ($examCategories as $ek) {
        $peKeys[] = 'pe_' . $ek . '_status';
        $peKeys[] = 'pe_' . $ek . '_findings';
    }
    $nestedFlatNames = [];
    foreach (['personal', 'emergency', 'medical', 'classification', 'remarks', 'personal_social_history', 'past_medical_history', 'immunizations', 'hospital_admission', 'physical_screening', 'ancillary'] as $sec) {
        if (!empty($newCore[$sec]) && is_array($newCore[$sec])) {
            foreach (array_keys($newCore[$sec]) as $nk) {
                $nestedFlatNames[] = $nk;
            }
        }
    }
    $nestedHandled = array_flip(array_merge(
        ['save', 'employee_id'],
        array_keys($newCore),
        $checkboxRoots,
        $nestedFlatNames,
        array_map(function ($t) {
            return 'anc_' . $t . '_status';
        }, $ancillaryTests),
        array_map(function ($t) {
            return 'anc_' . $t . '_findings';
        }, $ancillaryTests),
        $peKeys
    ));

    foreach ($_POST as $k => $v) {
        if ($k === 'save' || $k === 'employee_id') continue;
        if (is_array($v)) continue;
        if (strpos((string)$k, 'past_') === 0) continue;
        if (isset($nestedHandled[$k])) continue;
        $profile[$k] = trim((string)$v);
    }

    $profile_json = json_encode($profile, JSON_UNESCAPED_UNICODE);

    $exists = $conn->query("SELECT id FROM employee_health_profiles WHERE employee_id=" . intval($employee_id));
    if ($exists && $exists->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE employee_health_profiles SET class_type=?, profile_data=? WHERE employee_id=?");
        $stmt->bind_param("ssi", $medical_class, $profile_json, $employee_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO employee_health_profiles (employee_id, class_type, profile_data) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $employee_id, $medical_class, $profile_json);
        $stmt->execute();
        $stmt->close();
    }

    try {
        if (isset($_POST['blood_type']) || isset($_POST['height']) || isset($_POST['weight']) || isset($_POST['allergies']) || isset($_POST['conditions'])) {
            $eid = $employee_id;
            $b = postStr('blood_type');
            $h = postStr('height');
            $w = postStr('weight');
            $a = postStr('allergies');
            $c = postStr('conditions');
            $conn->query("INSERT INTO employee_health (employee_id, blood_type, height, weight, allergies, conditions)
                VALUES (" . intval($eid) . ", '" . $conn->real_escape_string($b) . "', '" . $conn->real_escape_string($h) . "', '" . $conn->real_escape_string($w) . "',
                    '" . $conn->real_escape_string($a) . "', '" . $conn->real_escape_string($c) . "')
                ON DUPLICATE KEY UPDATE
                    blood_type=VALUES(blood_type),
                    height=VALUES(height),
                    weight=VALUES(weight),
                    allergies=VALUES(allergies),
                    conditions=VALUES(conditions)
            ");
        }
    } catch (Throwable $e) {
        // no-op
    }

    header("Location: employees.php");
    exit();
}
?>
