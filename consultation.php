<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/db.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$id = intval($_GET['id'] ?? 0);
$cid = intval($_GET['cid'] ?? 0);
$mode = strtolower(trim((string)($_GET['mode'] ?? 'list')));
if (!in_array($mode, ['list', 'add', 'edit', 'view'], true)) $mode = 'list';

$employee = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $employee = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
}

$consultation = null;
if ($cid > 0) {
    $stmt = $conn->prepare("SELECT * FROM consultations WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $res = $stmt->get_result();
    $consultation = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
}

$isView = $mode === 'view';
?>

<div class="container consultation-page" style="max-width: 1200px;">
    <div style="background: var(--color-brand); color: white; padding: 24px; border-radius: var(--radius-lg) var(--radius-lg) 0 0; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--shadow-sm);">
        <div>
            <h2 style="margin:0; color: white; font-size: var(--text-xl); letter-spacing: -0.5px;">Medical Consultation</h2>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-sm); font-weight: 500; margin-top: 4px;">
                <?php echo $mode === 'list' ? 'History Log' : ($mode === 'add' ? 'New Entry' : ($isView ? 'View Record' : 'Edit Record')); ?>
            </div>
        </div>
        <a href="employees.php" style="font-size: 28px; line-height: 1; color: white; opacity: 0.7; transition: opacity var(--transition-fast);" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">&times;</a>
    </div>

    <style>
        .consult-container {
            background: var(--color-surface);
            padding: 32px;
            border: 1px solid var(--color-border);
            border-top: none;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .form-section {
            margin-bottom: 40px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-brand-light);
        }

        .section-header h3 {
            font-size: var(--text-md);
            color: var(--color-brand);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .grid-1 { display: grid; grid-template-columns: 1fr; gap: 20px; }

        @media (max-width: 768px) {
            .grid-3, .grid-2 { grid-template-columns: 1fr; }
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .radio-box-group {
            display: flex;
            gap: 16px;
            padding: 8px 0;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--color-text-secondary);
            cursor: pointer;
        }

        .radio-option input {
            width: 18px;
            height: 18px;
            accent-color: var(--color-brand);
        }

        .btn-stack {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
            padding-top: 24px;
            border-top: 1px solid var(--color-border);
        }

        /* List View Overrides */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--color-border);
        }

        .history-table th {
            background: var(--color-overlay);
            color: var(--color-text-secondary);
            font-size: 11px;
            text-transform: uppercase;
            padding: 14px;
            border-bottom: 2px solid var(--color-border);
        }

        .history-table td {
            padding: 16px 14px;
            border-bottom: 1px solid var(--color-border);
        }
    </style>

    <?php if ($mode === 'list'): ?>
    <div class="consult-container">

        <?php
        // Flash messages from save redirect
        $flashSuccess = $_GET['success'] ?? '';
        $flashError   = $_GET['error'] ?? '';
        if ($flashSuccess === 'consultation_saved'): ?>
            <div style="background: hsl(160,84%,95%); border: 1px solid hsl(160,84%,75%); color: hsl(160,50%,30%); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; display:flex; align-items:center; gap:8px;">
                ✅ Consultation record saved successfully.
            </div>
        <?php elseif ($flashSuccess === 'consultation_updated'): ?>
            <div style="background: hsl(210,84%,95%); border: 1px solid hsl(210,84%,75%); color: hsl(210,50%,30%); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; display:flex; align-items:center; gap:8px;">
                ✏️ Consultation record updated successfully.
            </div>
        <?php elseif ($flashError === 'save_failed'): ?>
            <div style="background: hsl(0,75%,95%); border: 1px solid hsl(0,75%,80%); color: hsl(0,50%,35%); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; display:flex; align-items:center; gap:8px;">
                ❌ Failed to save the record. Please try again.
            </div>
        <?php elseif ($flashSuccess === 'deleted'): ?>
            <div style="background: hsl(0,75%,95%); border: 1px solid hsl(0,75%,80%); color: hsl(0,50%,35%); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 600; display:flex; align-items:center; gap:8px;">
                🗑️ Consultation record deleted.
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-size: var(--text-lg); color: var(--color-text-primary);">
                <?php echo $id > 0 ? 'Consultation History for ' . h($employee['name'] ?? 'Employee') : 'All Consultations History'; ?>
            </h3>
            <?php if ($id > 0): ?>
                <a href="consultation.php?id=<?php echo $id; ?>&mode=add" class="btn btn-brand" style="height: 38px;">+ New Consultation</a>
            <?php else: ?>
                <div style="display: flex; gap: 8px; align-items: center; position: relative;">
                    <div style="position: relative;">
                        <input
                            type="text"
                            id="patientSearchInput"
                            placeholder="Search patient..."
                            autocomplete="off"
                            style="height: 38px; padding: 0 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); min-width: 220px; font-size: var(--text-sm);"
                            oninput="filterPatients(this.value)"
                            onfocus="showDropdown()"
                        >
                        <div id="patientDropdown" style="display:none; position:absolute; top:42px; left:0; width:100%; background:var(--color-surface); border:1px solid var(--color-border); border-radius:var(--radius-sm); box-shadow:var(--shadow-md); z-index:999; max-height:220px; overflow-y:auto;"></div>
                    </div>
                    <button onclick="startConsultation()" class="btn btn-brand" style="height: 38px;">+ New Consultation</button>
                </div>
                <script>
                const allPatients = <?php
                    $empList = $conn->query("SELECT id, name FROM employees ORDER BY name ASC");
                    $empArr = [];
                    if ($empList) {
                        while ($empRow = $empList->fetch_assoc()) {
                            $empArr[] = ['id' => (int)$empRow['id'], 'name' => $empRow['name']];
                        }
                    }
                    echo json_encode($empArr);
                ?>;

                let selectedPatientId = null;

                function filterPatients(query) {
                    selectedPatientId = null;
                    const dropdown = document.getElementById('patientDropdown');
                    const q = query.trim().toLowerCase();
                    const filtered = q === '' ? allPatients : allPatients.filter(p => p.name.toLowerCase().includes(q));

                    if (filtered.length === 0) {
                        dropdown.innerHTML = '<div style="padding:10px 14px; color:var(--color-text-muted); font-size:13px;">No patients found.</div>';
                    } else {
                        dropdown.innerHTML = filtered.map(p =>
                            `<div class="patient-option" data-id="${p.id}" data-name="${p.name.replace(/"/g,'&quot;')}"
                                style="padding:10px 14px; cursor:pointer; font-size:13px; font-weight:600; border-bottom:1px solid var(--color-border);"
                                onmousedown="selectPatient(${p.id}, '${p.name.replace(/'/g,"\\'")}')">
                                ${p.name}
                            </div>`
                        ).join('');
                    }
                    dropdown.style.display = 'block';
                }

                function showDropdown() {
                    filterPatients(document.getElementById('patientSearchInput').value);
                }

                function selectPatient(id, name) {
                    selectedPatientId = id;
                    document.getElementById('patientSearchInput').value = name;
                    document.getElementById('patientDropdown').style.display = 'none';
                }

                document.addEventListener('click', function(e) {
                    const input = document.getElementById('patientSearchInput');
                    const dropdown = document.getElementById('patientDropdown');
                    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });

                function startConsultation() {
                    if (!selectedPatientId) {
                        alert('Please search and select a patient first.');
                        document.getElementById('patientSearchInput').focus();
                        return;
                    }
                    window.location.href = 'consultation.php?id=' + selectedPatientId + '&mode=add';
                }
                </script>
            <?php endif; ?>
        </div>
        
        <div style="overflow-x:auto;">
            <table class="history-table">
                <thead>
                    <tr>
                        <?php if ($id === 0): ?>
                            <th>Patient Name</th>
                        <?php endif; ?>
                        <th>Date & Time</th>
                        <th>Chief Complaint</th>
                        <th>Diagnosis</th>
                        <th style="width: 150px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($id > 0) {
                        $r = $conn->query("SELECT * FROM consultations WHERE employee_id=" . intval($id) . " ORDER BY consultation_date DESC, consultation_time DESC");
                    } else {
                        $r = $conn->query("SELECT c.*, e.name AS emp_name FROM consultations c LEFT JOIN employees e ON c.employee_id = e.id ORDER BY c.consultation_date DESC, c.consultation_time DESC");
                    }
                    if ($r && $r->num_rows > 0) {
                        while ($row = $r->fetch_assoc()) {
                            echo '<tr>';
                            if ($id === 0) {
                                $empName = $row['emp_name'] ?? $row['full_name'] ?? 'Unknown';
                                echo '<td style="font-weight: 600;">' . h($empName) . '</td>';
                            }
                            echo '<td style="font-weight: 700; color: var(--color-brand);">' . h($row['consultation_date']) . ' <span style="font-weight: 400; color: var(--color-text-muted); font-size: 12px;">' . h($row['consultation_time']) . '</span></td>';
                            echo '<td style="font-size: 13px;">' . h($row['chief_complaint']) . '</td>';
                            echo '<td style="font-size: 13px; color: var(--color-text-secondary);">' . h($row['diagnosis']) . '</td>';
                            echo '<td>';
                            $rowEmpId = $row['employee_id'] ?? 0;
                            echo '<div class="action-group" style="display:flex;gap:6px;justify-content:center;white-space:nowrap;">';
                            echo '<a href="consultation.php?id=' . $rowEmpId . '&cid=' . $row['id'] . '&mode=view" class="btn btn-tiny btn-view">View</a>';
                            echo '<a href="consultation.php?id=' . $rowEmpId . '&cid=' . $row['id'] . '&mode=edit" class="btn btn-tiny btn-edit">Edit</a>';
                            echo '<button class="btn btn-tiny btn-delete" type="button" style="background: hsl(0, 75%, 95%); color: var(--color-danger); border: 1px solid hsl(0, 75%, 85%);" onclick="confirmDelete(' . $row['id'] . ', \'consultation\', ' . $rowEmpId . ')">Del</button>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="' . ($id === 0 ? 5 : 4) . '" style="padding:48px; text-align:center; color: var(--color-text-muted); font-weight: 600;">No consultations found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="btn-stack" style="border-top: none;">
            <a href="employees.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>
    <?php else: ?>
    <form method="POST" action="save_consultation.php" class="consult-container">
        <input type="hidden" name="employee_id" value="<?php echo h($id); ?>">
        <input type="hidden" name="cid" value="<?php echo $cid > 0 ? $cid : ''; ?>">

        <!-- Section 1: Personal -->
        <div class="form-section">
            <div class="section-header">
                <h3>Personal Data</h3>
            </div>
            
            <div class="grid-1">
                <div class="field-group">
                    <label class="field-label">Full Name (Last Name, First Name, Middle Name)</label>
                    <input type="text" name="full_name" value="<?php echo h($consultation['full_name'] ?? $employee['name'] ?? ''); ?>" required <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Sex</label>
                    <select name="sex" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <option value="Male" <?php echo (($consultation['sex'] ?? $employee['sex'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (($consultation['sex'] ?? $employee['sex'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Age</label>
                    <input type="number" name="age" value="<?php echo h($consultation['age'] ?? $employee['age'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field-group">
                    <label class="field-label">Birthdate</label>
                    <input type="date" name="birthdate" value="<?php echo h($consultation['birthdate'] ?? $employee['birthday'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Civil Status</label>
                    <select name="civil_status" <?php echo $isView ? 'disabled' : ''; ?>>
                        <option value="">-- select --</option>
                        <?php
                        $civilOptions = ['Single', 'Married', 'Separated', 'Widowed'];
                        $currentCivil = $consultation['civil_status'] ?? $employee['civil_status'] ?? '';
                        foreach ($civilOptions as $opt) {
                            $sel = ($currentCivil === $opt) ? 'selected' : '';
                            echo '<option value="' . h($opt) . '" ' . $sel . '>' . h($opt) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Contact Number</label>
                    <input type="tel" name="phone" value="<?php echo h($consultation['phone'] ?? $employee['contact'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field-group">
                    <label class="field-label">Department / Unit</label>
                    <input type="text" name="office" value="<?php echo h($consultation['office'] ?? $employee['department'] ?? ''); ?>" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Residential Address</label>
                    <textarea name="address" rows="2" <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($consultation['address'] ?? $employee['address'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Clinical -->
        <div class="form-section">
            <div class="section-header">
                <h3>Vitals & Clinical Data</h3>
            </div>
            
            <div class="grid-2">
                <div class="field-group">
                    <label class="field-label">Consultation Date</label>
                    <input type="date" name="consultation_date" value="<?php echo h($consultation['consultation_date'] ?? date('Y-m-d')); ?>" required <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field-group">
                    <label class="field-label">Time of Arrival</label>
                    <input type="time" name="consultation_time" value="<?php echo h($consultation['consultation_time'] ?? date('H:i')); ?>" required <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Blood Pressure (mmHg)</label>
                    <input type="text" name="blood_pressure" value="<?php echo h($consultation['blood_pressure'] ?? ''); ?>" placeholder="120/80" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field-group">
                    <label class="field-label">Heart Rate (bpm)</label>
                    <input type="number" name="heart_rate" value="<?php echo h($consultation['heart_rate'] ?? ''); ?>" placeholder="72" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field-group">
                    <label class="field-label">Respiratory Rate</label>
                    <input type="number" name="respiratory_rate" value="<?php echo h($consultation['respiratory_rate'] ?? ''); ?>" placeholder="16" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">O2 Saturation (%)</label>
                    <input type="number" name="o2_saturation" value="<?php echo h($consultation['o2_saturation'] ?? ''); ?>" placeholder="98" step="0.1" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
                <div class="field-group">
                    <label class="field-label">Body Temperature (°C)</label>
                    <input type="number" name="temperature" value="<?php echo h($consultation['temperature'] ?? ''); ?>" placeholder="36.5" step="0.1" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Chief Complaint</label>
                    <textarea name="chief_complaint" rows="3" placeholder="Description of patient concerns..." <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($consultation['chief_complaint'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Medical Assessment / Diagnosis</label>
                    <textarea name="diagnosis" rows="3" placeholder="Clinical findings..." <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($consultation['diagnosis'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Treatment Plan & Prescription</label>
                    <textarea name="notes" rows="4" placeholder="Medications, rest period, or referrals..." <?php echo $isView ? 'disabled' : ''; ?>><?php echo h($consultation['notes'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 3: Administrative -->
        <div class="form-section" style="margin-bottom: 0;">
            <div class="section-header">
                <h3>Administrative</h3>
            </div>
            
            <div class="grid-2">
                <div class="field-group">
                    <label class="field-label">Issue Medical Certificate?</label>
                    <div class="radio-box-group">
                        <label class="radio-option">
                            <input type="radio" name="medical_certificate" value="Yes" <?php echo (($consultation['medical_certificate'] ?? '') === 'Yes') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>Yes</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="medical_certificate" value="No" <?php echo (($consultation['medical_certificate'] ?? 'No') === 'No') ? 'checked' : ''; ?> <?php echo $isView ? 'disabled' : ''; ?>>
                            <span>No</span>
                        </label>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Required Copies</label>
                    <input type="number" name="certificate_copies" value="<?php echo h($consultation['certificate_copies'] ?? '1'); ?>" min="1" max="10" <?php echo $isView ? 'disabled' : ''; ?>>
                </div>
            </div>
        </div>

        <div class="btn-stack">
            <?php if (!$isView): ?>
                <a href="consultation.php?id=<?php echo $id; ?>&mode=list" class="btn btn-outline">Discard Changes</a>
                <button type="submit" class="btn btn-brand">
                    <?php echo $cid > 0 ? '💾 Update Consultation Record' : '✅ Commit Consultation Record'; ?>
                </button>
            <?php else: ?>
                <a href="consultation.php?id=<?php echo $id; ?>&cid=<?php echo $cid; ?>&mode=edit" class="btn btn-outline" style="border-color:var(--color-brand); color:var(--color-brand);">✏️ Edit This Record</a>
                <a href="consultation.php?id=<?php echo $id; ?>&mode=list" class="btn btn-outline">Close Record</a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- HIDDEN DELETE FORM -->
<form id="deleteForm" method="POST" action="delete_record.php">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="type" id="deleteType">
    <input type="hidden" name="employee_id" id="deleteEmployeeId">
    <input type="hidden" name="delete" value="1">
</form>

<!-- DELETE MODAL -->
<div id="deleteModal" class="modal" role="dialog" aria-modal="true" onclick="if(event.target===this) closeModal();">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to permanently delete this record? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-brand" style="background:var(--color-danger);" onclick="deleteNow()">Delete Record</button>
        </div>
    </div>
</div>

<script>
let deleteId = 0;
let deleteType = '';
let deleteEmpId = 0;

function confirmDelete(id, type, empId = 0) {
    deleteId = id;
    deleteType = type;
    deleteEmpId = empId;
    document.getElementById("deleteModal").classList.add("is-open");
}

function closeModal() {
    document.getElementById("deleteModal").classList.remove("is-open");
}

function deleteNow() {
    document.getElementById("deleteId").value = deleteId;
    document.getElementById("deleteType").value = deleteType;
    document.getElementById("deleteEmployeeId").value = deleteEmpId;
    document.getElementById("deleteForm").submit();
}
</script>

