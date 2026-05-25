<?php 
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/db.php';

// Ensure the JSON-based health profile table exists.
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

function post_search_like(string $s): string {
    // Basic cleanup for LIKE queries.
    return trim($s);
}

$search = isset($_GET['search']) ? post_search_like((string)$_GET['search']) : '';
$office_filter = isset($_GET['office']) ? trim((string)$_GET['office']) : '';
$class_filter = isset($_GET['class']) ? trim((string)$_GET['class']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Stats
$totalEmployees = (int)($conn->query("SELECT COUNT(*) AS c FROM employees")->fetch_assoc()['c'] ?? 0);
$classCounts = ['REGULAR' => 0, 'JOB ORDER' => 0, 'CONTRACT OF SERVICE' => 0];
$classRes = $conn->query("SELECT class_type, COUNT(*) AS c FROM employee_health_profiles GROUP BY class_type");
if ($classRes) {
    while ($row = $classRes->fetch_assoc()) {
        $ct = (string)($row['class_type'] ?? '');
        if (isset($classCounts[$ct])) $classCounts[$ct] = (int)$row['c'];
    }
}

// Query employees (search + pagination)
$whereClauses = [];
if ($search !== '') {
    $like = '%' . $conn->real_escape_string($search) . '%';
    $whereClauses[] = "(name LIKE '$like' OR department LIKE '$like' OR contact LIKE '$like')";
}
if ($office_filter !== '') {
    $safeOffice = $conn->real_escape_string($office_filter);
    $whereClauses[] = "department = '$safeOffice'";
}
if ($class_filter !== '') {
    $safeClass = $conn->real_escape_string($class_filter);
    $whereClauses[] = "id IN (SELECT employee_id FROM employee_health_profiles WHERE class_type = '$safeClass')";
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

$countRes = $conn->query("SELECT COUNT(*) AS c FROM employees $whereSql");
$totalFiltered = (int)($countRes->fetch_assoc()['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalFiltered / $limit));

$r = $conn->query("SELECT * FROM employees $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>

<div class="container employees-page" style="max-width: 100%; padding: 24px 32px;">
    <style>
        /* Contextual overrides for Employees dashboard */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            gap: 4px;
            border-top: 4px solid var(--color-brand);
            transition: box-shadow var(--transition-fast), transform var(--transition-fast);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        /* Specific tints for classes to avoid rainbow syndrome while maintaining semantics */
        .stat-card.class-a { border-top-color: var(--color-success); }
        .stat-card.class-b { border-top-color: var(--color-warning); }
        .stat-card.class-c { border-top-color: var(--color-danger); }

        .stat-card .label {
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--color-text-secondary);
        }

        .stat-card .value {
            font-size: var(--text-2xl);
            font-weight: 800;
            color: var(--color-text-primary);
        }

        .toolbar {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 16px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-xs);
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            flex: 1;
            align-items: center;
        }

        .search-form select {
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            min-width: 200px;
            max-width: 100%;
            flex: 1 1 auto;
        }

        .search-form .search-input-wrapper {
            position: relative;
            flex: 3 1 250px;
        }

        .search-form .search-input-wrapper input {
            width: 100%;
            height: 40px;
            padding-left: 36px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
        }

        .search-form .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            pointer-events: none;
        }

        .btn-brand {
            background: var(--color-brand);
            color: white;
            padding: 0 20px;
            height: 40px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: var(--text-sm);
        }

        .btn-brand:hover {
            background: var(--color-brand-dark);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline {
            background: var(--color-surface);
            border: 1px solid var(--color-border-strong);
            color: var(--color-text-primary);
            padding: 0 16px;
            height: 40px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: var(--text-sm);
        }

        .btn-outline:hover {
            background: var(--color-canvas);
            border-color: var(--color-text-secondary);
        }

        /* Table Design Fixes */
        .table-container {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--text-sm);
        }

        thead {
            background: var(--color-overlay);
            border-bottom: 2px solid var(--color-border);
        }

        th {
            text-align: left;
            padding: 16px 12px;
            font-weight: 700;
            color: var(--color-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 11px;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-primary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--color-brand-light);
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 6px;
        }

        .btn-tiny {
            padding: 6px 10px;
            border-radius: var(--radius-xs);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .btn-edit { background: var(--color-brand-light); color: var(--color-brand); border: 1px solid var(--color-border); }
        .btn-view { background: hsl(160, 84%, 95%); color: var(--color-success); border: 1px solid hsl(160, 84%, 85%); }
        .btn-consult { background: hsl(260, 70%, 95%); color: hsl(260, 70%, 45%); border: 1px solid hsl(260, 70%, 85%); }
        .btn-delete { background: hsl(0, 75%, 95%); color: var(--color-danger); border: 1px solid hsl(0, 75%, 85%); }

        .btn-tiny:hover { filter: brightness(0.95); transform: translateY(-1px); }

        /* Pagination Rule #9 Nesting */
        .pagination-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 6px;
        }

        .page-item {
            padding: 8px 14px;
            border: 1px solid var(--color-border);
            background: var(--color-surface);
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: var(--text-xs);
            color: var(--color-text-secondary);
        }

        .page-item.active {
            background: var(--color-brand);
            color: white;
            border-color: var(--color-brand);
        }

        .page-item:hover:not(.active) {
            background: var(--color-canvas);
            border-color: var(--color-border-strong);
        }
    </style>

    <div class="page-header">
        <div>
            <h2>Employees Dashboard</h2>
            <div style="color: var(--color-text-secondary); font-size: var(--text-sm); font-weight: 500;">
                Overview of patient records and classifications
            </div>
        </div>
        <a class="btn btn-brand" href="health.php?mode=add&id=0">
            + New Employee Profile
        </a>
    </div>

    <div class="stats-grid">
        <a href="employees.php" class="stat-card" style="text-decoration:none; cursor:pointer;">
            <div class="label">Total Employees</div>
            <div class="value"><?php echo (int)$totalEmployees; ?></div>
        </a>
        <a href="employees.php?class=REGULAR" class="stat-card class-a" style="text-decoration:none; cursor:pointer;">
            <div class="label">REGULAR</div>
            <div class="value"><?php echo (int)$classCounts['REGULAR']; ?></div>
        </a>
        <a href="employees.php?class=JOB+ORDER" class="stat-card class-b" style="text-decoration:none; cursor:pointer;">
            <div class="label">JOB ORDER</div>
            <div class="value"><?php echo (int)$classCounts['JOB ORDER']; ?></div>
        </a>
        <a href="employees.php?class=CONTRACT+OF+SERVICE" class="stat-card class-c" style="text-decoration:none; cursor:pointer;">
            <div class="label">CONTRACT OF SERVICE</div>
            <div class="value"><?php echo (int)$classCounts['CONTRACT OF SERVICE']; ?></div>
        </a>
    </div>

    <div class="toolbar">
        <form method="GET" class="search-form">
            <select name="office" onchange="this.form.submit()">
                <option value="">All Offices</option>
                <?php
                $officesList = [
                    "Accounting Office", "Appraisal, Testing, and Admission Office", "Budget Office", 
                    "Cashering Office", "Commision And Audit Office", "Community Extension Services Divison", 
                    "Cultural And Arts Development Office", "Curriculum And Instruction Divison Office/Kalampusan Office", 
                    "Department of General Education Curricula", "Events Management Office", "Gender and Development Office", 
                    "General Services Office", "Guidance and Counseling Office", "Health Services Office", 
                    "Human Resource Management and Development Office", "Income Generation Program Office", 
                    "Information and Communication Technology Management Office", "Institute for Business Management", 
                    "Institute for Teacher Education", "Internal Audit Office", "International Affiairs Extension Linkage Office", 
                    "Learning Resource Center", "Legal Office", "National Service training Program Office/ Campus Safety Management Office", 
                    "Office of the College and Board Secretary", "Office of the College President", 
                    "Office of the Vice President for Academic Affairs", "Office of the Vice President for Administration and Finance", 
                    "Pathfit/Sports Development Office", "Planning Development Office", "Procurement Management Office", 
                    "Project Management and Monitoring Office", "Public affairs and Information Office", 
                    "Quality Assurance Office", "Records and Archival Office", "Registar and Record Office", 
                    "Research Development and Innovation Division", "Research Ethics Office", 
                    "Student Accounts, Scholarship Aides, and Financial Assitance Office", "Student Affairs, Services, and Development Division", 
                    "Student Discipline Office", "Supply and Property Management Office"
                ];
                foreach ($officesList as $off) {
                    $sel = ($office_filter === $off) ? 'selected' : '';
                    echo '<option value="' . h($off) . '" ' . $sel . '>' . h($off) . '</option>';
                }
                ?>
            </select>
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" placeholder="Search by name, department, or contact..." value="<?php echo h($search); ?>">
            </div>
            <div style="display: flex; gap: 8px; flex-shrink: 0;">
                <button type="submit" class="btn btn-brand">Search</button>
                <?php if ($search !== '' || $office_filter !== ''): ?>
                    <a class="btn btn-outline" href="employees.php" style="display: inline-flex; align-items: center;">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="action-group" style="flex-shrink: 0;">
            <a class="btn btn-outline" href="#">
                <span>📤</span> Export
            </a>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Full Name</th>
                    <th>Birthdate</th>
                    <th>Contact</th>
                    <th>Religion</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Class</th>
                    <th style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $employees = [];
                $ids = [];
                if ($r && $r->num_rows > 0) {
                    while ($row = $r->fetch_assoc()) {
                        $employees[] = $row;
                        $ids[] = (int)$row['id'];
                    }
                }

                $profileMap = [];
                if (count($ids) > 0) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $types = str_repeat('i', count($ids));
                    $stmt = $conn->prepare("SELECT employee_id, class_type, profile_data FROM employee_health_profiles WHERE employee_id IN ($placeholders)");
                    $stmt->bind_param($types, ...$ids);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($pr = $res->fetch_assoc()) {
                        $profileMap[(int)$pr['employee_id']] = $pr;
                    }
                    $stmt->close();
                }

                if (count($employees) === 0) {
                    echo '<tr><td colspan="11" style="text-align:center; padding:40px; color:var(--color-text-muted);">No records matching your search were found.</td></tr>';
                } else {
                    foreach ($employees as $row) {
                        $eid = (int)$row['id'];
                        $empName = $row['name'] ?? '';
                        $birthday = $row['birthday'] ?? '';
                        $contact = $row['contact'] ?? '';
                        $gender = $row['sex'] ?? '';
                        $age = $row['age'] ?? '';
                        $occupation = $row['department'] ?? '';
                        $civilStatus = $row['civil_status'] ?? '';

                        $religion = '';
                        $classType = '';
                        if (isset($profileMap[$eid])) {
                            $classType = (string)($profileMap[$eid]['class_type'] ?? '');
                            $decoded = json_decode((string)($profileMap[$eid]['profile_data'] ?? ''), true);
                            if (is_array($decoded)) {
                                $religion = (string)arr_get($decoded, ['personal', 'religion'], '');
                                $occFromProfile = arr_get($decoded, ['personal', 'occupation'], '');
                                if ($occFromProfile !== '') $occupation = $occFromProfile;
                                $civilFromProfile = arr_get($decoded, ['personal', 'civil_status'], '');
                                if ($civilFromProfile !== '') $civilStatus = $civilFromProfile;
                            }
                        }

                        $empIdDisplay = 'EMP-' . str_pad((string)$eid, 5, '0', STR_PAD_LEFT);
                        echo '<tr>';
                        echo '<td><a href="health.php?id=' . (int)$eid . '&mode=view" style="color:var(--color-brand); font-weight:700;">' . h($empIdDisplay) . '</a></td>';
                        echo '<td style="font-weight:600;">' . h($empName) . '</td>';
                        echo '<td>' . h($birthday) . '</td>';
                        echo '<td>' . h($contact) . '</td>';
                        echo '<td>' . h($religion) . '</td>';
                        echo '<td>' . h($gender) . '</td>';
                        echo '<td>' . h($age) . '</td>';
                        echo '<td>' . h($occupation) . '</td>';
                        echo '<td>' . h($civilStatus) . '</td>';
                        echo '<td><span style="font-weight:700; color:' . ($classType === 'REGULAR' ? 'var(--color-success)' : ($classType === 'JOB ORDER' ? 'var(--color-warning)' : 'var(--color-danger)')) . '">' . h($classType) . '</span></td>';
                        echo '<td>';
                        echo '<div class="action-group">';
                        echo '<a class="btn btn-tiny btn-edit" href="health.php?id=' . $eid . '&mode=edit">Edit</a>';
                        echo '<a class="btn btn-tiny btn-view" href="health.php?id=' . $eid . '&mode=view">View</a>';
                        echo '<a class="btn btn-tiny btn-consult" href="consultation.php?id=' . $eid . '&mode=list">Consult</a>';
                        echo '<button class="btn btn-tiny btn-delete" type="button" onclick="confirmDelete(' . $eid . ')">Del</button>';
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
            ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        <?php
            $queryBase = [];
            if ($search !== '') $queryBase[] = 'search=' . urlencode($search);
            if ($office_filter !== '') $queryBase[] = 'office=' . urlencode($office_filter);
            if ($class_filter !== '') $queryBase[] = 'class=' . urlencode($class_filter);
            $queryStr = count($queryBase) > 0 ? ('?' . implode('&', $queryBase) . '&') : '?';

            echo '<a class="page-item" href="employees.php' . $queryStr . 'page=1">First</a>';
            $prevPage = max(1, $page - 1);
            echo '<a class="page-item" href="employees.php' . $queryStr . 'page=' . $prevPage . '">← Prev</a>';
            for ($p = 1; $p <= $totalPages; $p++) {
                if ($p < $page - 3 || $p > $page + 3) continue;
                $active = $p === $page ? 'active' : '';
                echo '<a class="page-item ' . $active . '" href="employees.php' . $queryStr . 'page=' . $p . '">' . $p . '</a>';
            }
            $nextPage = min($totalPages, $page + 1);
            echo '<a class="page-item" href="employees.php' . $queryStr . 'page=' . $nextPage . '">Next →</a>';
        ?>
    </div>
</div>

<!-- HIDDEN DELETE FORM -->
<form id="deleteForm" method="POST" action="delete_employee.php">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="delete" value="1">
</form>

<!-- DELETE MODAL -->
<div id="deleteModal" class="modal" role="dialog" aria-modal="true" onclick="if(event.target===this) closeModal();">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to permanently delete this employee record? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-brand" style="background:var(--color-danger);" onclick="deleteNow()">Delete Record</button>
        </div>
    </div>
</div>

<script>
let deleteId = 0;

function confirmDelete(id){
    deleteId = id;
    document.getElementById("deleteModal").classList.add("is-open");
}

function closeModal(){
    document.getElementById("deleteModal").classList.remove("is-open");
}

function deleteNow(){
    document.getElementById("deleteId").value = deleteId;
    document.getElementById("deleteForm").submit();
}
</script>