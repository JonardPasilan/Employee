<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/db.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Create prescriptions table if it doesn't exist yet
$conn->query("
    CREATE TABLE IF NOT EXISTS prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NULL,
        category VARCHAR(100) NULL,
        office VARCHAR(100) NULL,
        full_name VARCHAR(255) NULL,
        age INT NULL,
        gender VARCHAR(20) NULL,
        address TEXT NULL,
        prescription_date DATE NULL,
        clinic_doctor VARCHAR(255) NULL,
        medicine_1 VARCHAR(255) NULL,
        instruction_1 VARCHAR(255) NULL,
        medicine_2 VARCHAR(255) NULL,
        instruction_2 VARCHAR(255) NULL,
        medicine_3 VARCHAR(255) NULL,
        instruction_3 VARCHAR(255) NULL,
        note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (employee_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$mode = strtolower(trim((string)($_GET['mode'] ?? 'list')));
if (!in_array($mode, ['list', 'add'], true)) $mode = 'list';

// Fetch all employees for the dropdown in add mode
$employees = [];
if ($mode === 'add') {
    $res = $conn->query("SELECT * FROM employees ORDER BY name ASC");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $employees[] = $row;
        }
    }
}
?>

<div class="container consultation-page" style="max-width: 900px;">
    <div style="background: var(--color-brand); color: white; padding: 24px; border-radius: var(--radius-lg) var(--radius-lg) 0 0; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--shadow-sm);">
        <div>
            <h2 style="margin:0; color: white; font-size: var(--text-xl); letter-spacing: -0.5px;">Medical Prescriptions</h2>
            <div style="color: rgba(255,255,255,0.8); font-size: var(--text-sm); font-weight: 500; margin-top: 4px;">
                <?php echo $mode === 'list' ? 'History Log' : 'New Entry'; ?>
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

        .form-section { margin-bottom: 40px; }
        .section-header {
            display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--color-brand-light);
        }
        .section-header h3 { font-size: var(--text-md); color: var(--color-brand); text-transform: uppercase; letter-spacing: 1px; }

        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .grid-1 { display: grid; grid-template-columns: 1fr; gap: 20px; }

        @media (max-width: 768px) { .grid-3, .grid-2 { grid-template-columns: 1fr; } }

        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .btn-stack { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; padding-top: 24px; border-top: 1px solid var(--color-border); }
        
        .medicine-block {
            background: var(--color-overlay);
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            border: 1px solid var(--color-border);
        }
        .medicine-block h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: var(--color-text-primary);
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
        <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: var(--text-lg); color: var(--color-text-primary);">Prescriptions History Log</h3>
            <a href="prescriptions.php?mode=add" class="btn btn-brand" style="height: 38px;">+ New Prescription</a>
        </div>
        
        <div style="overflow-x:auto;">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient Name</th>
                        <th>Clinic/Doctor</th>
                        <th>Medicines Prescribed</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $r = $conn->query("
                        SELECT p.*, e.name AS emp_name 
                        FROM prescriptions p 
                        LEFT JOIN employees e ON p.employee_id = e.id 
                        ORDER BY p.id DESC
                    ");
                    if ($r && $r->num_rows > 0) {
                        while ($row = $r->fetch_assoc()) {
                            // Compile medicines preview list
                            $medList = [];
                            for ($i = 1; $i <= 3; $i++) {
                                if (!empty($row['medicine_' . $i])) {
                                    $medList[] = $row['medicine_' . $i];
                                }
                            }
                            $medsText = count($medList) > 0 ? implode(', ', $medList) : 'None';
                            
                            $dateStr = date('Y-m-d H:i', strtotime($row['created_at']));
                            $patientName = $row['emp_name'] ?? $row['full_name'] ?? 'Unknown';

                            echo '<tr>';
                            echo '<td style="font-weight: 700; color: var(--color-brand);">' . h($dateStr) . '</td>';
                            echo '<td style="font-weight: 600;">' . h($patientName) . '</td>';
                            echo '<td style="font-size: 13px; color: var(--color-text-secondary);">' . h($row['clinic_doctor']) . '</td>';
                            echo '<td style="font-size: 13px;">' . h($medsText) . '</td>';
                            echo '<td>';
                            echo '<a href="print_prescription.php?id=' . $row['id'] . '" target="_blank" class="btn btn-tiny btn-view">Print / View</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" style="padding:48px; text-align:center; color: var(--color-text-muted); font-weight: 600;">No prescription records found.</td></tr>';
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
    <form method="POST" action="save_prescription.php" class="consult-container">
        
        <!-- Section 1: Patient Details -->
        <div class="form-section">
            <div class="section-header">
                <h3>Patient Details</h3>
            </div>
            
            <div class="grid-2">
                <div class="field-group">
                    <label class="field-label">Select Employee (Auto-fill)</label>
                    <select name="employee_id" id="employeeSelect" onchange="fillEmployeeDetails()">
                        <option value="">-- Manual Entry --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?php echo h($emp['id']); ?>"><?php echo h($emp['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Clinic / Doctor</label>
                    <input type="text" name="clinic_doctor" value="Dr. Val Acosta Clinic" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Category</label>
                    <input type="text" name="category" id="catField">
                </div>
                <div class="field-group">
                    <label class="field-label">Office / Department</label>
                    <input type="text" name="office" id="officeField">
                </div>
            </div>

            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Full Name</label>
                    <input type="text" name="full_name" id="nameField" required>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Age</label>
                    <input type="number" name="age" id="ageField">
                </div>
                <div class="field-group">
                    <label class="field-label">Gender</label>
                    <select name="gender" id="genderField">
                        <option value="">-- select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Prescription Date</label>
                    <input type="date" name="prescription_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Address</label>
                    <textarea name="address" id="addrField" rows="2"></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Prescription -->
        <div class="form-section">
            <div class="section-header">
                <h3>Rx (Medicines)</h3>
            </div>
            
            <div class="medicine-block">
                <h4>Medicine 1</h4>
                <div class="grid-1">
                    <div class="field-group">
                        <input type="text" name="medicine_1" placeholder="Drug Name & Dosage (e.g. Paracetamol 500mg)">
                        <input type="text" name="instruction_1" placeholder="Sig: (e.g. Take 1 tab every 8 hours for fever)">
                    </div>
                </div>
            </div>

            <div class="medicine-block">
                <h4>Medicine 2</h4>
                <div class="grid-1">
                    <div class="field-group">
                        <input type="text" name="medicine_2" placeholder="Drug Name & Dosage">
                        <input type="text" name="instruction_2" placeholder="Sig:">
                    </div>
                </div>
            </div>

            <div class="medicine-block">
                <h4>Medicine 3</h4>
                <div class="grid-1">
                    <div class="field-group">
                        <input type="text" name="medicine_3" placeholder="Drug Name & Dosage">
                        <input type="text" name="instruction_3" placeholder="Sig:">
                    </div>
                </div>
            </div>
            
            <div class="grid-1" style="margin-top: 20px;">
                <div class="field-group">
                    <label class="field-label">Additional Notes</label>
                    <textarea name="note" rows="3" placeholder="Rest for 3 days, drink plenty of fluids..."></textarea>
                </div>
            </div>
        </div>

        <div class="btn-stack">
            <a href="prescriptions.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-brand">Save & Print Prescription</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
    const employees = <?php echo json_encode($employees); ?>;
    
    function fillEmployeeDetails() {
        const select = document.getElementById('employeeSelect');
        const id = select.value;
        
        if (!id) {
            document.getElementById('nameField').value = '';
            document.getElementById('ageField').value = '';
            document.getElementById('genderField').value = '';
            document.getElementById('addrField').value = '';
            document.getElementById('officeField').value = '';
            document.getElementById('catField').value = '';
            return;
        }

        const emp = employees.find(e => e.id == id);
        if (emp) {
            document.getElementById('nameField').value = emp.name || '';
            document.getElementById('ageField').value = emp.age || '';
            
            // Set Gender
            const g = document.getElementById('genderField');
            if(emp.sex === 'Male' || emp.sex === 'Female') {
                g.value = emp.sex;
            } else {
                g.value = '';
            }

            document.getElementById('addrField').value = emp.address || '';
            document.getElementById('officeField').value = emp.department || '';
            document.getElementById('catField').value = emp.category || '';
        }
    }
</script>
