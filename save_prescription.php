<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prescriptions.php');
    exit;
}

// Create prescriptions table if it doesn't exist
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

$employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$category = trim($_POST['category'] ?? '');
$office = trim($_POST['office'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$age = !empty($_POST['age']) ? intval($_POST['age']) : 0;
$gender = trim($_POST['gender'] ?? '');
$address = trim($_POST['address'] ?? '');
$prescription_date = !empty($_POST['prescription_date']) ? $_POST['prescription_date'] : date('Y-m-d');
$clinic_doctor = trim($_POST['clinic_doctor'] ?? '');

$medicine_1 = trim($_POST['medicine_1'] ?? '');
$instruction_1 = trim($_POST['instruction_1'] ?? '');
$medicine_2 = trim($_POST['medicine_2'] ?? '');
$instruction_2 = trim($_POST['instruction_2'] ?? '');
$medicine_3 = trim($_POST['medicine_3'] ?? '');
$instruction_3 = trim($_POST['instruction_3'] ?? '');
$note = trim($_POST['note'] ?? '');

$stmt = $conn->prepare("
    INSERT INTO prescriptions (
        employee_id, category, office, full_name, age, gender, address, 
        prescription_date, clinic_doctor, medicine_1, instruction_1, 
        medicine_2, instruction_2, medicine_3, instruction_3, note
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssisssssssssss",
    $employee_id, $category, $office, $full_name, $age, $gender, $address,
    $prescription_date, $clinic_doctor, $medicine_1, $instruction_1,
    $medicine_2, $instruction_2, $medicine_3, $instruction_3, $note
);

if ($stmt->execute()) {
    $inserted_id = $stmt->insert_id;
    $stmt->close();
    header("Location: print_prescription.php?id=" . $inserted_id);
} else {
    $stmt->close();
    header("Location: prescriptions.php?error=save_failed");
}
exit;
?>
