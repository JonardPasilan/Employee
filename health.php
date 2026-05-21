<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/db.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function arr_get($arr, array $keys, $default = '') {
    $cur = $arr;
    foreach ($keys as $k) {
        if (!is_array($cur) || !array_key_exists($k, $cur)) return $default;
        $cur = $cur[$k];
    }
    return $cur ?? $default;
}

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

createProfileTableIfNeeded($conn);

$id = intval($_GET['id'] ?? 0);
$mode = strtolower(trim((string)($_GET['mode'] ?? 'edit')));
if (!in_array($mode, ['add', 'edit', 'view'], true)) $mode = 'edit';

$employee = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $employee = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
}

$profileRow = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT class_type, profile_data FROM employee_health_profiles WHERE employee_id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $profileRow = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
}

$profile = [];
if ($profileRow && !empty($profileRow['profile_data'])) {
    $decoded = json_decode($profileRow['profile_data'], true);
    if (is_array($decoded)) $profile = $decoded;
}

// Fallbacks to employee table for personal basics (in case profile JSON is empty).
$personalFromEmployee = $employee ?: [];
$personalFallback = [
    'full_name' => $personalFromEmployee['name'] ?? '',
    'birthday' => $personalFromEmployee['birthday'] ?? '',
    'age' => $personalFromEmployee['age'] ?? '',
    'gender' => $personalFromEmployee['sex'] ?? '',
    'contact_number' => $personalFromEmployee['contact'] ?? '',
    'occupation' => $personalFromEmployee['department'] ?? '',
    'civil_status' => $personalFromEmployee['civil_status'] ?? '',
    'home_address' => $personalFromEmployee['address'] ?? '',
];

$personal = is_array($profile) ? ($profile['personal'] ?? []) : [];
$personal = array_merge($personalFallback, is_array($personal) ? $personal : []);

$emergency = is_array($profile) ? ($profile['emergency'] ?? []) : [];
$medical = is_array($profile) ? ($profile['medical'] ?? []) : [];
$history = is_array($profile) ? ($profile['personal_social_history'] ?? []) : [];
$pastMedical = is_array($profile) ? ($profile['past_medical_history'] ?? []) : [];
$immunizations = is_array($profile) ? ($profile['immunizations'] ?? []) : [];
$hospital = is_array($profile) ? ($profile['hospital_admission'] ?? []) : [];
$physical = is_array($profile) ? ($profile['physical_screening'] ?? []) : [];

$empDisplay = $id > 0 ? str_pad((string)$id, 5, '0', STR_PAD_LEFT) : '';

// In view mode, prevent editing by disabling inputs.
$isView = $mode === 'view';
$isEdit = $mode === 'edit';
$isAdd = $mode === 'add';
?>

<div class="container health-page" style="max-width: 1200px;">
    <div style="background: var(--color-brand); color: white; padding: 24px; border-radius: var(--radius-lg) var(--radius-lg) 0 0; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--shadow-sm);">
        <div>
            <h2 style="margin:0; color: white; font-size: var(--text-xl); letter-spacing: -0.5px;">Employee Health Profile</h2>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-sm); font-weight: 500; margin-top: 4px;">
                NBSC Official Health & Safety Records
            </div>
        </div>
        <a href="employees.php" style="font-size: 28px; line-height: 1; color: white; opacity: 0.7; transition: opacity var(--transition-fast);" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">&times;</a>
    </div>

    <style>
        .health-container {
            background: var(--color-surface);
            padding: 32px;
            border: 1px solid var(--color-border);
            border-top: none;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        /* Wizard Indicator Styles */
        .step-navbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 32px;
            padding: 12px;
            background: var(--color-overlay);
            border-radius: var(--radius-md);
            border: 1px solid var(--color-border);
        }

        .step-pill {
            padding: 8px 16px;
            border-radius: var(--radius-full);
            background: var(--color-surface);
            border: 1px solid var(--color-border-strong);
            color: var(--color-text-secondary);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .step-pill.active {
            background: var(--color-brand);
            color: white;
            border-color: var(--color-brand);
            box-shadow: var(--shadow-sm);
        }

        .step-pill:hover:not(.active) {
            background: var(--color-brand-light);
            border-color: var(--color-brand);
            color: var(--color-brand);
        }

        /* Form Layout */
        .form-section {
            margin-bottom: 40px;
            animation: fadeIn 300ms ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: var(--text-md);
            color: var(--color-brand);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--color-brand-light);
            font-weight: 700;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 900px) {
            .form-row { grid-template-columns: 1fr; }
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--color-border);
        }

        .wizard-page { display: none; }
        .wizard-page.active { display: block; }

        /* Legacy Checkbox Grid Compatibility */
        .inline-checks {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: var(--color-canvas);
        }
    </style>

    <div class="health-container">
        <!-- Step Indicator (Rule #11) -->
        <div class="step-navbar">
            <div class="step-pill" data-step-ind="1">I. Personal</div>
            <div class="step-pill" data-step-ind="2">II. Emergency</div>
            <div class="step-pill" data-step-ind="3">III. Medical</div>
            <div class="step-pill" data-step-ind="4">IV. Soc/Hist</div>
            <div class="step-pill" data-step-ind="5">V. Past Med</div>
            <div class="step-pill" data-step-ind="6">VI. Immunize</div>
            <div class="step-pill" data-step-ind="7">VII. Hospital</div>
            <div class="step-pill" data-step-ind="8">VIII. Physical</div>
            <div class="step-pill" data-step-ind="9">IX. Screening</div>
            <div class="step-pill" data-step-ind="10">X. Vitals</div>
            <div class="step-pill" data-step-ind="11">XI. Final Class</div>
        </div>

        <form id="healthWizardForm" method="POST" action="save_health.php" autocomplete="off">
            <input type="hidden" name="employee_id" value="<?php echo h($id); ?>">
            <input type="hidden" name="mode" value="<?php echo h($mode); ?>">
            <input type="hidden" name="save" value="1">

        <!-- STEP 1: PERSONAL PROFILE -->
        <div class="wizard-page active" data-step="1">
            <div class="section-title">I. PERSONAL PROFILE</div>
            
            <div class="form-row">
                <div class="field">
                    <label>Date</label>
                    <input type="date" name="profile_date" value="<?php echo h($personal['profile_date'] ?? date('Y-m-d')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>College Clinic File Number</label>
                    <input type="text" name="clinic_file_number" value="<?php echo h($personal['clinic_file_number'] ?? $empDisplay); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Department / Office</label>
                    <select name="department" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- Select Office / Department --</option>
                        <?php
                        $offices = [
                            'Accounting Office',
                            'Appraisal, Testing, and Admission Office',
                            'Budget Office',
                            'Cashering Office',
                            'Commision And Audit Office',
                            'Community Extension Services Divison',
                            'Cultural And Arts Development Office',
                            'Curriculum And Instruction Divison Office/Kalampusan Office',
                            'Department of General Education Curricula',
                            'Events Management Office',
                            'Gender and Development Office',
                            'General Services Office',
                            'Guidance and Counseling Office',
                            'Health Services Office',
                            'Human Resource Management and Development Office',
                            'Income Generation Program Office',
                            'Information and Communication Technology Management Office',
                            'Institute for Business Management',
                            'Institute for Teacher Education',
                            'Internal Audit Office',
                            'International Affiairs Extension Linkage Office',
                            'Learning Resource Center',
                            'Legal Office',
                            'National Service training Program Office/ Campus Safety Management Office',
                            'Office of the College and Board Secretary',
                            'Office of the College President',
                            'Office of the Vice President for Academic Affairs',
                            'Office of the Vice President for Administration and Finance',
                            'Office of the Vice President for Research, Extension, and Innovation',
                            'Office of the Vice President for Student Affairs and Services',
                            'Physical Plant and Facilities Management Office',
                            'Planning and Development Office',
                            'Public Information Office',
                            'Quality Assurance Office',
                            'Records Management Office',
                            'Registrar Office',
                            'Research and Innovation Office',
                            'Security Office',
                            'Supply and Property Management Office',
                            'System and Network Administration Office'
                        ];
                        $currentDept = trim((string)($personal['department'] ?? $personal['occupation'] ?? ''));
                        foreach ($offices as $off) {
                            $sel = ($currentDept === $off) ? 'selected' : '';
                            echo '<option value="' . h($off) . '" ' . $sel . '>' . h($off) . '</option>';
                        }
                        // If they have a custom department not in the list, still show it as an option so data isn't lost
                        if ($currentDept !== '' && !in_array($currentDept, $offices)) {
                            echo '<option value="' . h($currentDept) . '" selected>' . h($currentDept) . ' (Custom)</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>NAME (Last Name, First Name, Middle Name)</label>
                    <input type="text" name="full_name" value="<?php echo h($personal['full_name'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?> required>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Age/SEX</label>
                    <input type="text" name="age_sex" value="<?php echo h($personal['age_sex'] ?? (($personal['age'] ?? '') . ' ' . ($personal['gender'] ?? ''))); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Birthday</label>
                    <input type="date" name="birthday" value="<?php echo h($personal['birthday'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Home Address</label>
                    <textarea name="home_address" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($personal['home_address'] ?? ''); ?></textarea>
                </div>
                <div class="field">
                    <label>Religion</label>
                    <input type="text" name="religion" value="<?php echo h($personal['religion'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Municipal Address</label>
                    <textarea name="municipal_address" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($personal['municipal_address'] ?? ''); ?></textarea>
                </div>
                <div class="field">
                    <label>Occupation</label>
                    <input type="text" name="occupation" value="<?php echo h($personal['occupation'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Contact Number(s)</label>
                    <input type="text" name="contact_number" value="<?php echo h($personal['contact_number'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Civil Status</label>
                    <select name="civil_status" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <?php
                        $civilOptions = ['Single', 'Married', 'Separated', 'Widowed'];
                        $currentCivil = $personal['civil_status'] ?? '';
                        foreach ($civilOptions as $opt) {
                            $sel = ($currentCivil === $opt) ? 'selected' : '';
                            echo '<option value="' . h($opt) . '" ' . $sel . '>' . h($opt) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Category</label>
                    <select name="category" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <?php
                        $catOptions = ['Regular', 'Job Order', 'Contract of Service'];
                        $currentCat = $personal['category'] ?? '';
                        foreach ($catOptions as $opt) {
                            $sel = (strcasecmp($currentCat, $opt) === 0) ? 'selected' : '';
                            echo '<option value="' . h($opt) . '" ' . $sel . '>' . h($opt) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="field">
                </div>
            </div>
        </div>

        <!-- STEP 2: EMERGENCY -->
        <div class="wizard-page" data-step="2">
            <div class="section-title">II. IN CASE OF EMERGENCY (PLEASE CONTACT)</div>
            <div class="form-row">
                <div class="field">
                    <label>Complete Name</label>
                    <input type="text" name="emergency_complete_name" value="<?php echo h($emergency['complete_name'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Contact Number</label>
                    <input type="text" name="emergency_contact_number" value="<?php echo h($emergency['contact_number'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>
            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Address (Street/Purok, Barangay, Municipality, Province)</label>
                    <textarea name="emergency_address" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($emergency['address'] ?? ''); ?></textarea>
                </div>
                <div class="field">
                    <label>Relationship</label>
                    <input type="text" name="emergency_relationship" value="<?php echo h($emergency['relationship'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>
        </div>

        <!-- STEP 4: PAST MEDICAL HISTORY -->
        <div class="wizard-page" data-step="4">
            <div class="section-title">IV. PAST MEDICAL HISTORY</div>
            
            <div class="inline-checks">
                <div class="check">
                    <input type="checkbox" name="past_allergy" value="1" <?php echo !empty($pastMedical['allergy']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Allergy</span>
                </div>

                <div class="check">
                    <input type="checkbox" name="past_food_allergy" value="1" <?php echo !empty($pastMedical['food_allergy']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Food</div>
                        <input type="text" name="food_allergy_specify" value="<?php echo h($pastMedical['food_allergy_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="check">
                    <input type="checkbox" name="past_drug_allergy" value="1" <?php echo !empty($pastMedical['drug_allergy']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Drug</span>
                </div>

                <div class="check">
                    <input type="checkbox" name="past_epilepsy_seizure_disorder" value="1" <?php echo !empty($pastMedical['epilepsy_seizure_disorder']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Epilepsy/Seizure Disorder</div>
                        <input type="text" name="epilepsy_specify" value="<?php echo h($pastMedical['epilepsy_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="past_asthma" value="1" <?php echo !empty($pastMedical['asthma']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Asthma</span>
                </label>

                <label class="check">
                    <input type="checkbox" name="past_congenital_heart_disorder" value="1" <?php echo !empty($pastMedical['congenital_heart_disorder']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Congenital Heart Disorder</span>
                </label>

                <label class="check">
                    <input type="checkbox" name="past_thyroid_disease" value="1" <?php echo !empty($pastMedical['thyroid_disease']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Thyroid Disease</span>
                </label>

                <div class="check">
                    <input type="checkbox" name="past_skin_disorder" value="1" <?php echo !empty($pastMedical['skin_disorder']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Skin Disorder</div>
                        <input type="text" name="skin_disorder_specify" value="<?php echo h($pastMedical['skin_disorder_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="past_cancer" value="1" <?php echo !empty($pastMedical['cancer']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Cancer</span>
                </label>

                <label class="check">
                    <input type="checkbox" name="past_diabetes_heart_disorder" value="1" <?php echo !empty($pastMedical['diabetes_heart_disorder']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Diabetes Heart Disorder</span>
                </label>

                <label class="check">
                    <input type="checkbox" name="past_peptic_ulcer" value="1" <?php echo !empty($pastMedical['peptic_ulcer']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Peptic Ulcer</span>
                </label>

                <div class="check">
                    <input type="checkbox" name="past_tuberculosis" value="1" <?php echo !empty($pastMedical['tuberculosis']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Tuberculosis</div>
                        <input type="text" name="tuberculosis_specify" value="<?php echo h($pastMedical['tuberculosis_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="past_coronary_artery_disease" value="1" <?php echo !empty($pastMedical['coronary_artery_disease']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Coronary Artery Disease</span>
                </label>

                <div class="check" style="grid-column:1 / -1;"></div>

                <label class="check">
                    <input type="checkbox" name="past_pcos" value="1" <?php echo !empty($pastMedical['pcos']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">PCOS</span>
                </label>

                <div class="check">
                    <input type="checkbox" name="past_hepatitis" value="1" <?php echo !empty($pastMedical['hepatitis']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Hepatitis</div>
                        <input type="text" name="hepatitis_specify" value="<?php echo h($pastMedical['hepatitis_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="check">
                    <input type="checkbox" name="past_hypertension_elevated_bp" value="1" <?php echo !empty($pastMedical['hypertension_elevated_bp']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Hypertension/Elevated BP</div>
                        <input type="text" name="hypertension_specify" value="<?php echo h($pastMedical['hypertension_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="check">
                    <input type="checkbox" name="past_psychological_disorder" value="1" <?php echo !empty($pastMedical['psychological_disorder']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);margin-bottom:6px;">Psychological Disorder</div>
                        <input type="text" name="psychological_disorder_specify" value="<?php echo h($pastMedical['psychological_disorder_specify'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Others</label>
                    <textarea name="other_findings" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($pastMedical['other_findings'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- STEP 8: PHYSICAL SCREENING -->
        <div class="wizard-page" data-step="3">
            <div class="section-title">III. PERSONAL/SOCIAL HISTORY</div>
            
            <div class="form-row">
                <div class="field">
                    <label>Height (cm)</label>
                    <input type="text" name="height_cm" value="<?php echo h($physical['height_cm'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Weight (kg)</label>
                    <input type="text" name="weight_kg" value="<?php echo h($physical['weight_kg'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Blood Pressure</label>
                    <input type="text" name="blood_pressure" value="<?php echo h($physical['blood_pressure'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Pulse Rate</label>
                    <input type="text" name="pulse_rate" value="<?php echo h($physical['pulse_rate'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Respiration</label>
                    <input type="text" name="respiration" value="<?php echo h($physical['respiration'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>SpO2</label>
                    <input type="text" name="spo2" value="<?php echo h($physical['spo2'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>BMI</label>
                    <input type="text" name="bmi" value="<?php echo h($physical['bmi'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>BMI Class</label>
                    <input type="text" name="bmi_class" value="<?php echo h($physical['bmi_class'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">Visual Acuity</div>
            <div class="form-row">
                <div class="field">
                    <label>Visual Acuity Type</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="visual_acuity_type" value="Corrected" <?php echo (($physical['visual_acuity_type'] ?? '') === 'Corrected') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Corrected</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="visual_acuity_type" value="Uncorrected" <?php echo (($physical['visual_acuity_type'] ?? '') === 'Uncorrected') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Uncorrected</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Right Vision (OD)</label>
                    <input type="text" name="right_vision_od" value="<?php echo h($physical['right_vision_od'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Left Vision (OS)</label>
                    <input type="text" name="left_vision_os" value="<?php echo h($physical['left_vision_os'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Ishihara Color Vision</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="ishihara_color_vision" value="Adequate" <?php echo (($physical['ishihara_color_vision'] ?? '') === 'Adequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Adequate</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="ishihara_color_vision" value="Defective" <?php echo (($physical['ishihara_color_vision'] ?? '') === 'Defective') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Defective</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">Ear/Hearing by fork</div>
            <div class="form-row">
                <div class="field">
                    <label>AD (Right Ear)</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="hearing_ad" value="Adequate" <?php echo (($physical['hearing_ad'] ?? '') === 'Adequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Adequate</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="hearing_ad" value="Inadequate" <?php echo (($physical['hearing_ad'] ?? '') === 'Inadequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Inadequate</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Speech</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="speech" value="Clear" <?php echo (($physical['speech'] ?? '') === 'Clear') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Clear</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="speech" value="Unclear" <?php echo (($physical['speech'] ?? '') === 'Unclear') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Unclear</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">Physical Examination</div>
            <?php
            $examCategories = [
                'skin' => 'Skin',
                'head_neck_scalp' => 'Head, Neck, Scalp',
                'eyes_external' => 'Eyes (external)',
                'pupils' => 'Pupils (Ophthalmoscopic)',
                'ears_nose_sinuses' => 'Ears, Nose, Sinuses',
                'mouth_throat' => 'Mouth, Throat',
                'neck_lymph_thyroid' => 'Neck, Lymph nodes, Thyroid',
                'chest_breast_axilla' => 'Chest, Breast, Axilla',
                'lungs' => 'Lungs',
                'heart_valvular' => 'Heart & Valvular',
                'back_abdomen' => 'Back & Abdomen',
                'genitalia' => 'Genitalia',
                'anus_rectum' => 'Anus, Rectum',
                'extremities' => 'Extremities'
            ];
            foreach ($examCategories as $key => $label):
                $statusKey = 'pe_' . $key . '_status';
                $findingsKey = 'pe_' . $key . '_findings';
                $status = $physical[$statusKey] ?? '';
                $findings = $physical[$findingsKey] ?? '';
            ?>
            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label><?php echo h($label); ?></label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="<?php echo h($statusKey); ?>" value="Normal" <?php echo $status === 'Normal' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Normal</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="<?php echo h($statusKey); ?>" value="Abnormal" <?php echo $status === 'Abnormal' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Abnormal</span>
                        </label>
                    </div>
                </div>
                <div class="field">
                    <label>Findings (if abnormal)</label>
                    <input type="text" name="<?php echo h($findingsKey); ?>" value="<?php echo h($findings); ?>" placeholder="Enter findings if abnormal" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- STEP 9: ANCILLARY EXAMINATIONS -->
        <div class="wizard-page" data-step="5">
            <div class="section-title">V. HOSPITAL ADMISSIONS</div>
            
            <?php
            $ancillary = is_array($profile) ? ($profile['ancillary'] ?? []) : [];
            $ancillaryTests = [
                'cbc' => 'A. Complete Blood Count',
                'fecalysis' => 'B. Fecalysis / Stool Exam',
                'pregnancy_test' => 'C. Pregnancy Test',
                'urinalysis' => 'D. Urinalysis',
                'chest_xray' => 'E. Chest X-Ray',
                'hbsag' => 'F. Hepatitis B Screening (HBsAg)',
                'mmse' => 'H. MMSE Score'
            ];
            foreach ($ancillaryTests as $key => $label):
                $statusKey = 'anc_' . $key . '_status';
                $findingsKey = 'anc_' . $key . '_findings';
                $status = $ancillary[$statusKey] ?? '';
                $findings = $ancillary[$findingsKey] ?? '';
            ?>
            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label><?php echo h($label); ?></label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <?php if ($key === 'pregnancy_test'): ?>
                            <label style="display:flex;align-items:center;gap:5px;margin:0;">
                                <input type="radio" name="<?php echo h($statusKey); ?>" value="Negative" <?php echo $status === 'Negative' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                                <span>Negative</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:5px;margin:0;">
                                <input type="radio" name="<?php echo h($statusKey); ?>" value="Positive" <?php echo $status === 'Positive' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                                <span>Positive</span>
                            </label>
                        <?php elseif ($key === 'hbsag'): ?>
                            <label style="display:flex;align-items:center;gap:5px;margin:0;">
                                <input type="radio" name="<?php echo h($statusKey); ?>" value="Non-Reactive" <?php echo $status === 'Non-Reactive' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                                <span>Non-Reactive</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:5px;margin:0;">
                                <input type="radio" name="<?php echo h($statusKey); ?>" value="Reactive" <?php echo $status === 'Reactive' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                                <span>Reactive</span>
                            </label>
                        <?php else: ?>
                            <label style="display:flex;align-items:center;gap:5px;margin:0;">
                                <input type="radio" name="<?php echo h($statusKey); ?>" value="Normal" <?php echo $status === 'Normal' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                                <span>Normal</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:5px;margin:0;">
                                <input type="radio" name="<?php echo h($statusKey); ?>" value="Abnormal" <?php echo $status === 'Abnormal' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                                <span>Abnormal</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field">
                    <label>Findings (if abnormal)</label>
                    <input type="text" name="<?php echo h($findingsKey); ?>" value="<?php echo h($findings); ?>" placeholder="Enter findings if abnormal" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>G. Blood Type</label>
                    <input type="text" name="anc_blood_type" value="<?php echo h($ancillary['blood_type'] ?? ''); ?>" placeholder="e.g., O+, A-, B+" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field"></div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Summary</label>
                    <textarea name="anc_summary" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($ancillary['summary'] ?? ''); ?></textarea>
                    <div class="muted" style="margin-top:6px;">If all results are normal, state: "All ancillary findings are within normal limits."</div>
                </div>
            </div>
        </div>

        <!-- STEP 10: EMPLOYEE CLASSIFICATION -->
        <div class="wizard-page" data-step="6">
            <div class="section-title">VI. MAINTENANCE MEDICATION AND OB/GYNE</div>
            
            <?php
            $classification = is_array($profile) ? ($profile['classification'] ?? []) : [];
            $finalClass = (string)($classification['final'] ?? '');
            if ($finalClass === '' && $id > 0 && $profileRow && !empty($profileRow['class_type'])) {
                $ct = trim((string)$profileRow['class_type']);
                if (preg_match('/Class\s*([ABC])/i', $ct, $m)) {
                    $m1 = strtoupper($m[1]);
                    if ($m1 === 'A') $finalClass = 'REGULAR';
                    elseif ($m1 === 'B') $finalClass = 'JOB ORDER';
                    elseif ($m1 === 'C') $finalClass = 'CONTRACT OF SERVICE';
                } elseif (preg_match('/^[ABC]$/i', $ct)) {
                    $m1 = strtoupper($ct);
                    if ($m1 === 'A') $finalClass = 'REGULAR';
                    elseif ($m1 === 'B') $finalClass = 'JOB ORDER';
                    elseif ($m1 === 'C') $finalClass = 'CONTRACT OF SERVICE';
                } else {
                    $finalClass = $ct; // Already migrated or custom
                }
            }
            if ($finalClass === '' && !empty($medical['class_type'])) {
                $ct = (string)$medical['class_type'];
                if (preg_match('/Class\s*([ABC])/i', $ct, $m)) {
                    $m1 = strtoupper($m[1]);
                    if ($m1 === 'A') $finalClass = 'REGULAR';
                    elseif ($m1 === 'B') $finalClass = 'JOB ORDER';
                    elseif ($m1 === 'C') $finalClass = 'CONTRACT OF SERVICE';
                }
            }
            ?>

            <div style="margin-top:14px;padding:16px;background:var(--color-overlay);border-radius:10px;border:1px solid var(--color-border);">
                <label style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;cursor:pointer;">
                    <input type="radio" name="classification_final" value="REGULAR" <?php echo $finalClass === 'REGULAR' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?> style="margin-top:3px;">
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);">REGULAR - Medically, physically, and mentally FIT for ANY WORK or STUDY</div>
                        <div class="muted" style="margin-top:4px;">No medical conditions or limitations</div>
                    </div>
                </label>

                <label style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;cursor:pointer;">
                    <input type="radio" name="classification_final" value="JOB ORDER" <?php echo $finalClass === 'JOB ORDER' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?> style="margin-top:3px;">
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);">JOB ORDER - Physically underdeveloped or with correctible defects but FIT TO WORK or STUDY</div>
                        <div class="muted" style="margin-top:4px;">Minor conditions that can be corrected</div>
                    </div>
                </label>

                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;">
                    <input type="radio" name="classification_final" value="CONTRACT OF SERVICE" <?php echo $finalClass === 'CONTRACT OF SERVICE' ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?> style="margin-top:3px;">
                    <div>
                        <div style="font-weight:800;color:var(--color-text-primary);">CONTRACT OF SERVICE - With medical condition requiring further evaluation or limitation</div>
                        <div class="muted" style="margin-top:4px;">Requires additional assessment or work restrictions</div>
                    </div>
                </label>
            </div>

            <div class="form-row" style="margin-top:20px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Classification Notes</label>
                    <textarea name="classification_notes" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($classification['notes'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- STEP 11: REMARKS -->
        <div class="wizard-page" data-step="7">
            <div class="section-title">VII. HEAD TO TOE ASSESSMENT</div>
            
            <?php
            $remarks = is_array($profile) ? ($profile['remarks'] ?? []) : [];
            ?>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>General Remarks</label>
                    <textarea name="remarks_general" style="min-height:150px;" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($remarks['general'] ?? ''); ?></textarea>
                    <div class="muted" style="margin-top:6px;">Enter any additional observations, recommendations, or follow-up requirements</div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Examined By</label>
                    <input type="text" name="remarks_examined_by" value="<?php echo h($remarks['examined_by'] ?? ''); ?>" placeholder="Physician Name" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Date of Examination</label>
                    <input type="date" name="remarks_exam_date" value="<?php echo h($remarks['exam_date'] ?? date('Y-m-d')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>License Number</label>
                    <input type="text" name="remarks_license_number" value="<?php echo h($remarks['license_number'] ?? ''); ?>" placeholder="Physician License No." <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field"></div>
            </div>
        </div>

        <!-- STEP 3: PERSONAL/SOCIAL HISTORY -->
        <div class="wizard-page" data-step="8">
            <div class="section-title">VIII. PHYSICAL SCREENING</div>
            
            <div class="form-row">
                <div class="field">
                    <label>Smoking</label>
                    <select name="smoking" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <option value="YES" <?php echo (($history['smoking'] ?? '') === 'YES') ? 'selected' : ''; ?>>YES</option>
                        <option value="NO" <?php echo (($history['smoking'] ?? '') === 'NO') ? 'selected' : ''; ?>>NO</option>
                        <option value="QUITTED" <?php echo (($history['smoking'] ?? '') === 'QUITTED') ? 'selected' : ''; ?>>QUITTED</option>
                    </select>
                </div>
                <div class="field">
                    <label>Pack/Day</label>
                    <input type="text" name="smoking_pack_day" value="<?php echo h($history['smoking_pack_day'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Years</label>
                    <input type="text" name="smoking_years" value="<?php echo h($history['smoking_years'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field"></div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Alcohol Drinking</label>
                    <select name="alcohol_drinking" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <option value="YES" <?php echo (($history['alcohol_drinking'] ?? '') === 'YES') ? 'selected' : ''; ?>>YES</option>
                        <option value="NO" <?php echo (($history['alcohol_drinking'] ?? '') === 'NO') ? 'selected' : ''; ?>>NO</option>
                        <option value="QUITTED" <?php echo (($history['alcohol_drinking'] ?? '') === 'QUITTED') ? 'selected' : ''; ?>>QUITTED</option>
                    </select>
                </div>
                <div class="field">
                    <label>Type</label>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="alcohol_type_beer" value="1" <?php echo !empty($history['alcohol_type_beer']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Beer/Rhum</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="alcohol_type_others" value="1" <?php echo !empty($history['alcohol_type_others']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Others</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Bottle(s)/Session</label>
                    <input type="text" name="alcohol_bottles_session" value="<?php echo h($history['alcohol_bottles_session'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Frequency</label>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="alcohol_occasionally" value="1" <?php echo !empty($history['alcohol_occasionally']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Occasionally</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="alcohol_monthly" value="1" <?php echo !empty($history['alcohol_monthly']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Monthly</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Illegal Drug Use</label>
                    <select name="illegal_drug_use" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <option value="YES" <?php echo (($history['illegal_drug_use'] ?? '') === 'YES') ? 'selected' : ''; ?>>YES</option>
                        <option value="NO" <?php echo (($history['illegal_drug_use'] ?? '') === 'NO') ? 'selected' : ''; ?>>NO</option>
                        <option value="QUITTED" <?php echo (($history['illegal_drug_use'] ?? '') === 'QUITTED') ? 'selected' : ''; ?>>QUITTED</option>
                    </select>
                </div>
                <div class="field"></div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Sexually Active</label>
                    <select name="sexually_active" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <option value="YES" <?php echo (($history['sexually_active'] ?? '') === 'YES') ? 'selected' : ''; ?>>YES</option>
                        <option value="NO" <?php echo (($history['sexually_active'] ?? '') === 'NO') ? 'selected' : ''; ?>>NO</option>
                        <option value="QUITTED" <?php echo (($history['sexually_active'] ?? '') === 'QUITTED') ? 'selected' : ''; ?>>QUITTED</option>
                    </select>
                </div>
                <div class="field">
                    <label>How many sexual partners within this year?</label>
                    <input type="number" name="no_of_sexual_partners" value="<?php echo h($history['no_of_sexual_partners'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Partner Gender</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="partners_male" value="1" <?php echo !empty($history['partners_male']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Male</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="partners_female" value="1" <?php echo !empty($history['partners_female']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Female</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="partners_both" value="1" <?php echo !empty($history['partners_both']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Both</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 5: HOSPITAL ADMISSION / PAST SURGICAL HISTORY / DISABILITY -->
        <div class="wizard-page" data-step="9">
            <div class="section-title">IV. ANCILLARY EXAMINATIONS</div>
            <div class="form-row">
                <div class="field">
                    <label>Diagnosis</label>
                    <input type="text" name="hospital_admission_diagnosis1" value="<?php echo h($hospital['hospital_admission_diagnosis1'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>When?</label>
                    <input type="date" name="hospital_admission_when1" value="<?php echo h($hospital['hospital_admission_when1'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Diagnosis</label>
                    <input type="text" name="hospital_admission_diagnosis2" value="<?php echo h($hospital['hospital_admission_diagnosis2'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>When?</label>
                    <input type="date" name="hospital_admission_when2" value="<?php echo h($hospital['hospital_admission_when2'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">PAST SURGICAL HISTORY</div>
            <div class="form-row">
                <div class="field">
                    <label>Operating Type</label>
                    <input type="text" name="past_surgical_operation1_type" value="<?php echo h($hospital['past_surgical_operation1_type'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>When?</label>
                    <input type="date" name="past_surgical_operation1_when" value="<?php echo h($hospital['past_surgical_operation1_when'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Operating Type</label>
                    <input type="text" name="past_surgical_operation2_type" value="<?php echo h($hospital['past_surgical_operation2_type'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>When?</label>
                    <input type="date" name="past_surgical_operation2_when" value="<?php echo h($hospital['past_surgical_operation2_when'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">PERSON WITH DISABILITY</div>
            <div class="form-row">
                <div class="field">
                    <label>Specify</label>
                    <input type="text" name="disability" value="<?php echo h($hospital['disability'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Registration Status</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="disability_registered" value="Registered" <?php echo (($hospital['disability_registered'] ?? '') === 'Registered') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Registered</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="disability_registered" value="Not Registered" <?php echo (($hospital['disability_registered'] ?? '') === 'Not Registered') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Not Registered</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">WILLING TO DONATE BLOOD</div>
            <div class="form-row">
                <div class="field" style="grid-column:1 / -1;">
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="willing_donate_blood" value="YES" <?php echo (($hospital['willing_donate_blood'] ?? '') === 'YES') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>YES</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="willing_donate_blood" value="WILLING" <?php echo (($hospital['willing_donate_blood'] ?? '') === 'WILLING') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>WILLING</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="willing_donate_blood" value="VERY WILLING" <?php echo (($hospital['willing_donate_blood'] ?? '') === 'VERY WILLING') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>VERY WILLING</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="willing_donate_blood" value="NO" <?php echo (($hospital['willing_donate_blood'] ?? '') === 'NO') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>NO</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">FAMILY HISTORY OF DISEASE</div>
            <div class="form-row">
                <div class="field">
                    <label>Mother Side (Please enumerate)</label>
                    <textarea name="family_history_mother" rows="4" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($hospital['family_history_mother'] ?? ''); ?></textarea>
                </div>
                <div class="field">
                    <label>Father Side (Please enumerate)</label>
                    <textarea name="family_history_father" rows="4" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($hospital['family_history_father'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="section-title" style="margin-top:24px;">IMMUNIZATIONS</div>
            <div class="form-row">
                <div class="field">
                    <label>Complete Newborn Immunizations</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="newborn_immunizations" value="yes" <?php echo (($immunizations['newborn_immunizations'] ?? '') === 'yes') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Yes</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="newborn_immunizations" value="no" <?php echo (($immunizations['newborn_immunizations'] ?? '') === 'no') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>No</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="newborn_immunizations" value="unknown" <?php echo (($immunizations['newborn_immunizations'] ?? '') === 'unknown') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Unknown</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>For Women - HPV (How many doses?)</label>
                    <input type="text" name="hpv_doses" value="<?php echo h($immunizations['hpv_doses'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Tetanus Toxoid (How many doses?)</label>
                    <input type="text" name="tetanus_doses" value="<?php echo h($immunizations['tetanus_doses'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Influenza/Flu (Year)</label>
                    <input type="text" name="influenza_year" value="<?php echo h($immunizations['influenza_year'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Pneumococcal Vaccine (How many doses?)</label>
                    <input type="text" name="pneumococcal_doses" value="<?php echo h($immunizations['pneumococcal_doses'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Others: Specify</label>
                    <input type="text" name="other_immunizations" value="<?php echo h($immunizations['other_immunizations'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>COVID-19 Brand Name</label>
                    <input type="text" name="covid_brand" value="<?php echo h($immunizations['covid_brand'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?> placeholder="PF, MO, SN, AZ, SP, JJ, SP">
                </div>
                <div class="field">
                    <label>1st Dose</label>
                    <input type="date" name="covid_1st_dose" value="<?php echo h($immunizations['covid_1st_dose'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>2nd Dose</label>
                    <input type="date" name="covid_2nd_dose" value="<?php echo h($immunizations['covid_2nd_dose'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>1st Booster</label>
                    <input type="date" name="covid_1st_booster" value="<?php echo h($immunizations['covid_1st_booster'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>2nd Booster</label>
                    <input type="date" name="covid_2nd_booster" value="<?php echo h($immunizations['covid_2nd_booster'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field"></div>
            </div>

            <div class="form-row" style="margin-top:8px;">
                <div class="field" style="grid-column:1 / -1;">
                    <div class="muted" style="font-size:11px;padding:8px;background:var(--color-overlay);border-radius:6px;">
                        <strong>Legend for COVID-19 Brand Names:</strong> PF-Pfizer; MO-Moderna; SN-Sinovac; AZ-AstraZeneca; SP-Sputnik; JJ-Janssen; SP-Sinopharm
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Unvaccinated with COVID-19: Reason</label>
                    <textarea name="unvaccinated_reason" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($immunizations['unvaccinated_reason'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- STEP 6: MAINTENANCE MEDICATION AND OB/GYNE -->
        <div class="wizard-page" data-step="10">
            <div class="section-title">V. EMPLOYEE CLASSIFICATION</div>
            
            <div class="form-row">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Maintenance Medication Taken:</label>
                    <input type="text" name="maintenance_medication" value="<?php echo h($profile['maintenance_medication'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <h5 style="margin-top:30px;font-weight:bold;">OB/GYNE:</h5>
            <h6 style="font-weight:bold;margin-top:20px;">MENSTRUAL HISTORY:</h6>
            
            <div class="form-row">
                <div class="field">
                    <label>Menarche (1st Menstruation):</label>
                    <input type="text" name="menarche" value="<?php echo h($profile['menarche'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Last Menstrual Period (this Month/last Month):</label>
                    <input type="text" name="last_menstrual_period" value="<?php echo h($profile['last_menstrual_period'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Period/Duration:</label>
                    <input type="text" name="period_duration" value="<?php echo h($profile['period_duration'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Interval/Cycle:</label>
                    <input type="text" name="interval_cycle" value="<?php echo h($profile['interval_cycle'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>No. of pads per day:</label>
                    <input type="text" name="pads_per_day" value="<?php echo h($profile['pads_per_day'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Onset of Sexual Intercourse:</label>
                    <input type="text" name="onset_sexual_intercourse" value="<?php echo h($profile['onset_sexual_intercourse'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Birth Control Method:</label>
                    <input type="text" name="birth_control_method" value="<?php echo h($profile['birth_control_method'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Menopausal Stage?</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="menopausal_stage" value="YES" <?php echo (($profile['menopausal_stage'] ?? '') === 'YES') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>YES</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="menopausal_stage" value="NO" <?php echo (($profile['menopausal_stage'] ?? '') === 'NO') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>NO</span>
                        </label>
                        <label style="margin-left:10px;">If yes? What age?</label>
                        <input type="text" name="menopausal_age" value="<?php echo h($profile['menopausal_age'] ?? ''); ?>" style="width:100px;display:inline-block;padding:6px;border:1px solid #e3e6ea;border-radius:6px;" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">PREGNANCY HISTORY:</h6>
            
            <div class="form-row">
                <div class="field">
                    <label>Are you pregnant now?</label>
                    <input type="text" name="pregnant_now" value="<?php echo h($profile['pregnant_now'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>How many months?</label>
                    <input type="text" name="pregnant_months" value="<?php echo h($profile['pregnant_months'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Pre-Natal Check-up?</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="prenatal_checkup" value="YES" <?php echo (($profile['prenatal_checkup'] ?? '') === 'YES') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>YES</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="prenatal_checkup" value="NO" <?php echo (($profile['prenatal_checkup'] ?? '') === 'NO') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>NO</span>
                        </label>
                        <label style="margin-left:10px;">WHERE?</label>
                        <input type="text" name="prenatal_where" value="<?php echo h($profile['prenatal_where'] ?? ''); ?>" style="width:200px;display:inline-block;padding:6px;border:1px solid #e3e6ea;border-radius:6px;" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>
                <div class="field">
                    <label>Subject for Pregnancy Test:</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="pregnancy_test" value="YES" <?php echo (($profile['pregnancy_test'] ?? '') === 'YES') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>YES</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="pregnancy_test" value="NO" <?php echo (($profile['pregnancy_test'] ?? '') === 'NO') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>NO</span>
                        </label>
                        <label style="margin-left:10px;">Result:</label>
                        <input type="text" name="pregnancy_test_result" value="<?php echo h($profile['pregnancy_test_result'] ?? ''); ?>" style="width:120px;display:inline-block;padding:6px;border:1px solid #e3e6ea;border-radius:6px;" <?php echo $isView ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Gravida:</label>
                    <input type="text" name="gravida" value="<?php echo h($profile['gravida'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Para:</label>
                    <input type="text" name="para" value="<?php echo h($profile['para'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Term:</label>
                    <input type="text" name="term" value="<?php echo h($profile['term'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Abortion:</label>
                    <input type="text" name="abortion" value="<?php echo h($profile['abortion'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Live Birth:</label>
                    <input type="text" name="live_birth" value="<?php echo h($profile['live_birth'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field"></div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Type of Delivery:</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="delivery_type_normal" value="1" <?php echo !empty($profile['delivery_type_normal']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Normal Vaginal Delivery</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="delivery_type_caesarean" value="1" <?php echo !empty($profile['delivery_type_caesarean']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Caesarean Section</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="delivery_location_hospital" value="1" <?php echo !empty($profile['delivery_location_hospital']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Hospital</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="checkbox" name="delivery_location_home" value="1" <?php echo !empty($profile['delivery_location_home']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Home Delivery</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Complications:</label>
                    <input type="text" name="delivery_complications" value="<?php echo h($profile['delivery_complications'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Family Planning Done, What Type?</label>
                    <input type="text" name="family_planning_type" value="<?php echo h($profile['family_planning_type'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>No. of Years:</label>
                    <input type="text" name="family_planning_years" value="<?php echo h($profile['family_planning_years'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>
        </div>

        <!-- STEP 7: HEAD TO TOE ASSESSMENT -->
        <div class="wizard-page" data-step="11">
            <div class="section-title">VI. REMARKS</div>
            
            <h6 style="font-weight:bold;margin-top:20px;">NEUROLOGICAL ASSESSMENT:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="neuro_normal_thought" value="1" <?php echo !empty($profile['neuro_normal_thought']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal thought processes</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="neuro_normal_emotional" value="1" <?php echo !empty($profile['neuro_normal_emotional']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal Emotional Status</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="neuro_normal_psychological" value="1" <?php echo !empty($profile['neuro_normal_psychological']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal Psychological Status</span>
                </label>
            </div>
            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>How do you feel right now?</label>
                    <input type="text" name="neuro_feel_now" value="<?php echo h($profile['neuro_feel_now'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>No. of Years:</label>
                    <input type="text" name="neuro_years" value="<?php echo h($profile['neuro_years'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">HEENT</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="heent_anicteric_sclerae" value="1" <?php echo !empty($profile['heent_anicteric_sclerae']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Anicteric sclerae</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_perla" value="1" <?php echo !empty($profile['heent_perla']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">PERLA</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_aural_discharge" value="1" <?php echo !empty($profile['heent_aural_discharge']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Aural Discharge</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_intact_tympanic" value="1" <?php echo !empty($profile['heent_intact_tympanic']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Intact Tympanic Membrane</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_nasal_flaring" value="1" <?php echo !empty($profile['heent_nasal_flaring']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Nasal Flaring</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_nasal_discharge" value="1" <?php echo !empty($profile['heent_nasal_discharge']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Nasal Discharge</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_tonsillopharyngeal" value="1" <?php echo !empty($profile['heent_tonsillopharyngeal']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Tonsillopharyngeal congestion</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_hypertension_tonsils" value="1" <?php echo !empty($profile['heent_hypertension_tonsils']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Hypertension tonsils</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_palpable_mass" value="1" <?php echo !empty($profile['heent_palpable_mass']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Palpable Mass</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="heent_exudates" value="1" <?php echo !empty($profile['heent_exudates']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Exudates</span>
                </label>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">RESPIRATORY ASSESSMENT:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="resp_normal_heart_beat_sounds" value="1" <?php echo !empty($profile['resp_normal_heart_beat_sounds']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal Heart Beat sounds</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="resp_symmetrical_chest" value="1" <?php echo !empty($profile['resp_symmetrical_chest']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Symmetrical Chest Expansion</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="resp_restrictions" value="1" <?php echo !empty($profile['resp_restrictions']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Restrictions</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="resp_crackles_rates" value="1" <?php echo !empty($profile['resp_crackles_rates']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Crackles/Rates</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="resp_wheezing" value="1" <?php echo !empty($profile['resp_wheezing']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Wheezing</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="resp_clear_breath_sounds" value="1" <?php echo !empty($profile['resp_clear_breath_sounds']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Clear breath sounds</span>
                </label>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">CARDIOVASCULAR ASSESSMENT:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="cardio_normal_heart_beat" value="1" <?php echo !empty($profile['cardio_normal_heart_beat']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal Heart Beat</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_clubbing_fingers" value="1" <?php echo !empty($profile['cardio_clubbing_fingers']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Clubbing of fingers</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_finger_discoloration" value="1" <?php echo !empty($profile['cardio_finger_discoloration']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Finger Discoloration</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_heart_murmur" value="1" <?php echo !empty($profile['cardio_heart_murmur']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Heart Murmur</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_irregular_heart_beat" value="1" <?php echo !empty($profile['cardio_irregular_heart_beat']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Irregular Heart Beat</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_palpitations" value="1" <?php echo !empty($profile['cardio_palpitations']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Palpitations</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_fluid_volume_excess" value="1" <?php echo !empty($profile['cardio_fluid_volume_excess']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Fluid Volume Excess</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="cardio_fatigue_mobility" value="1" <?php echo !empty($profile['cardio_fatigue_mobility']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Fatigue on mobility</span>
                </label>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">GASTROINTESTINAL ASSESSMENT:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="gastro_regular_bowel" value="1" <?php echo !empty($profile['gastro_regular_bowel']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Regular Bowel Movement</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="gastro_constipation" value="1" <?php echo !empty($profile['gastro_constipation']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Constipation</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="gastro_loose_bowel" value="1" <?php echo !empty($profile['gastro_loose_bowel']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Loose bowel movement</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="gastro_hyperacidity" value="1" <?php echo !empty($profile['gastro_hyperacidity']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Hyperacidity</span>
                </label>
            </div>
            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Number per day:</label>
                    <input type="text" name="gastro_number_per_day" value="<?php echo h($profile['gastro_number_per_day'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Borborygmi:</label>
                    <input type="text" name="gastro_borborygmi" value="<?php echo h($profile['gastro_borborygmi'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">URINARY ASSESSMENT:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="urinary_flank_pain" value="1" <?php echo !empty($profile['urinary_flank_pain']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Flank pain</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="urinary_painful_urination" value="1" <?php echo !empty($profile['urinary_painful_urination']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Painful Urination</span>
                </label>
            </div>
            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>No. of urination per day:</label>
                    <input type="text" name="urinary_number_per_day" value="<?php echo h($profile['urinary_number_per_day'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Estimated amount per voiding:</label>
                    <input type="text" name="urinary_amount_per_voiding" value="<?php echo h($profile['urinary_amount_per_voiding'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">INTEGUMENTARY ASSESSMENT:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="integ_pallor" value="1" <?php echo !empty($profile['integ_pallor']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Pallor</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="integ_rashes" value="1" <?php echo !empty($profile['integ_rashes']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Rashes</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="integ_jaundice" value="1" <?php echo !empty($profile['integ_jaundice']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Jaundice</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="integ_good_skin_turgor" value="1" <?php echo !empty($profile['integ_good_skin_turgor']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Good skin turgor</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="integ_cyanosis" value="1" <?php echo !empty($profile['integ_cyanosis']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Cyanosis</span>
                </label>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">EXTREMITIES:</h6>
            <div class="inline-checks">
                <label class="check">
                    <input type="checkbox" name="extrem_gross_deformity" value="1" <?php echo !empty($profile['extrem_gross_deformity']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Gross Deformity</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="extrem_normal_gait" value="1" <?php echo !empty($profile['extrem_normal_gait']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal Gait</span>
                </label>
                <label class="check">
                    <input type="checkbox" name="extrem_normal_strength" value="1" <?php echo !empty($profile['extrem_normal_strength']) ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                    <span style="font-weight:800;color:var(--color-text-primary);">Normal Strength</span>
                </label>
            </div>
            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>Others:</label>
                    <input type="text" name="extrem_others" value="<?php echo h($profile['extrem_others'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field" style="grid-column:1 / -1;">
                    <label>OTHER PERTINENT FINDINGS ASSESSMENT:</label>
                    <textarea name="other_pertinent_findings" rows="4" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($profile['other_pertinent_findings'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- STEP 8: PHYSICAL SCREENING & EXAMINATION -->
        <div class="wizard-page" data-step="8">
            <div class="section-title">VIII. PHYSICAL SCREENING</div>
            <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:14px;">Please check(/) appropriate box or supply needed information.</p>
            
            <?php
            $va8 = (string)($physical['visual_acuity_type'] ?? $profile['visual_acuity_type'] ?? '');
            $ish8 = (string)($physical['ishihara_color_vision'] ?? $profile['ishihara_color_vision'] ?? '');
            $had8 = (string)($physical['hearing_ad'] ?? $profile['hearing_ad'] ?? '');
            $sp8 = (string)($physical['speech'] ?? $profile['speech'] ?? '');
            ?>
            <div class="form-row">
                <div class="field">
                    <label>Height (cm)</label>
                    <input type="text" name="screening_height" value="<?php echo h((string)($profile['screening_height'] ?? $physical['height_cm'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Weight (kg)</label>
                    <input type="text" name="screening_weight" value="<?php echo h((string)($profile['screening_weight'] ?? $physical['weight_kg'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Blood Pressure</label>
                    <input type="text" name="screening_blood_pressure" value="<?php echo h((string)($profile['screening_blood_pressure'] ?? $physical['blood_pressure'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Pulse Rate</label>
                    <input type="text" name="screening_pulse_rate" value="<?php echo h((string)($profile['screening_pulse_rate'] ?? $physical['pulse_rate'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Respiration</label>
                    <input type="text" name="screening_respiration" value="<?php echo h((string)($profile['screening_respiration'] ?? $physical['respiration'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>SpO2</label>
                    <input type="text" name="screening_spo2" value="<?php echo h((string)($profile['screening_spo2'] ?? $physical['spo2'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>BMI</label>
                    <input type="text" name="screening_bmi" value="<?php echo h((string)($profile['screening_bmi'] ?? $physical['bmi'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>BMI Class</label>
                    <input type="text" name="screening_bmi_class" value="<?php echo h((string)($profile['screening_bmi_class'] ?? $physical['bmi_class'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">Visual Acuity</h6>
            <div class="form-row">
                <div class="field">
                    <label>Visual Acuity Type</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="visual_acuity_type" value="Corrected" <?php echo ($va8 === 'Corrected') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Corrected</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="visual_acuity_type" value="Uncorrected" <?php echo ($va8 === 'Uncorrected') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Uncorrected</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Right Vision (OD)</label>
                    <input type="text" name="right_vision_od" value="<?php echo h((string)($profile['right_vision_od'] ?? $physical['right_vision_od'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field">
                    <label>Left Vision (OS)</label>
                    <input type="text" name="left_vision_os" value="<?php echo h((string)($profile['left_vision_os'] ?? $physical['left_vision_os'] ?? '')); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Ishihara Color Vision</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="ishihara_color_vision" value="Adequate" <?php echo ($ish8 === 'Adequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Adequate</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="ishihara_color_vision" value="Defective" <?php echo ($ish8 === 'Defective') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Defective</span>
                        </label>
                    </div>
                </div>
            </div>

            <h6 style="font-weight:bold;margin-top:30px;">Ear/Hearing by fork</h6>
            <div class="form-row">
                <div class="field">
                    <label>AD (Right Ear)</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="hearing_ad" value="Adequate" <?php echo ($had8 === 'Adequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Adequate</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="hearing_ad" value="Inadequate" <?php echo ($had8 === 'Inadequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Inadequate</span>
                        </label>
                    </div>
                </div>
                <div class="field">
                    <label>AS (Left Ear)</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="hearing_as" value="Adequate" <?php echo (($profile['hearing_as'] ?? $physical['hearing_as'] ?? '') === 'Adequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Adequate</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="hearing_as" value="Inadequate" <?php echo (($profile['hearing_as'] ?? $physical['hearing_as'] ?? '') === 'Inadequate') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Inadequate</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px;">
                <div class="field">
                    <label>Speech</label>
                    <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="speech" value="Clear" <?php echo ($sp8 === 'Clear') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Clear</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;margin:0;">
                            <input type="radio" name="speech" value="Unclear" <?php echo ($sp8 === 'Unclear') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Unclear</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard controls -->
        <div class="wizard-actions">
            <button class="btn-secondary" type="button" id="prevBtn">← Previous</button>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <button class="btn-secondary" type="button" id="nextBtn">Next →</button>
                <?php if (!$isView): ?>
                    <button class="btn-primary" type="submit" name="save">Save Health Profile</button>
            
                <?php endif; ?>
                <a class="btn-outline" href="employees.php">Cancel</a>
            </div>
        </div>
    </form>

</div>

<script>
    (function () {
        const isView = <?php echo $isView ? 'true' : 'false'; ?>;
        const pages = Array.from(document.querySelectorAll('.wizard-page'));
        const pills = Array.from(document.querySelectorAll('.step-pill[data-step-ind]'));
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        let pageIndex = 0;
        function showPageByIndex(idx) {
            pageIndex = Math.max(0, Math.min(idx, pages.length - 1));
            pages.forEach((p, i) => p.classList.toggle('active', i === pageIndex));
            const stepNum = parseInt(pages[pageIndex].getAttribute('data-step'), 10) || 1;
            pills.forEach(pi => {
                const n = parseInt(pi.getAttribute('data-step-ind'), 10);
                pi.classList.toggle('active', n === stepNum);
            });
            prevBtn.style.visibility = pageIndex === 0 ? 'hidden' : 'visible';
            nextBtn.style.display = pageIndex === pages.length - 1 ? 'none' : 'inline-block';
        }

        prevBtn.addEventListener('click', function () {
            if (pageIndex > 0) showPageByIndex(pageIndex - 1);
        });
        nextBtn.addEventListener('click', function () {
            if (pageIndex < pages.length - 1) showPageByIndex(pageIndex + 1);
        });
        pills.forEach(pi => {
            pi.addEventListener('click', function () {
                const target = parseInt(pi.getAttribute('data-step-ind'), 10);
                const i = pages.findIndex(p => parseInt(p.getAttribute('data-step'), 10) === target);
                if (i !== -1) showPageByIndex(i);
            });
        });

        showPageByIndex(0);

        // If view mode, disable form controls (so it matches screenshot intent).
        if (isView) {
            document.querySelectorAll('#healthWizardForm input, #healthWizardForm select, #healthWizardForm textarea, #healthWizardForm button').forEach(el => {
                if (el.tagName.toLowerCase() === 'button') return;
                el.setAttribute('disabled', 'disabled');
            });
        }
    })();
</script>
