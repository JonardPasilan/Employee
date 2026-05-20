<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employees.php');
    exit;
}

// Create consultations table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS consultations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        full_name VARCHAR(255) NULL,
        sex VARCHAR(20) NULL,
        age INT NULL,
        birthdate DATE NULL,
        civil_status VARCHAR(50) NULL,
        phone VARCHAR(50) NULL,
        office VARCHAR(255) NULL,
        address TEXT NULL,
        consultation_date DATE NULL,
        consultation_time TIME NULL,
        blood_pressure VARCHAR(20) NULL,
        heart_rate INT NULL,
        respiratory_rate INT NULL,
        o2_saturation DECIMAL(5,2) NULL,
        temperature DECIMAL(4,2) NULL,
        height DECIMAL(6,2) NULL,
        weight DECIMAL(6,2) NULL,
        chief_complaint TEXT NULL,
        diagnosis TEXT NULL,
        notes TEXT NULL,
        medical_certificate VARCHAR(10) NULL,
        certificate_copies INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (employee_id),
        INDEX (consultation_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Collect form data
$employee_id = intval($_POST['employee_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$sex = trim($_POST['sex'] ?? '');
$age = !empty($_POST['age']) ? intval($_POST['age']) : 0;
$birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : '';
$civil_status = trim($_POST['civil_status'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$office = trim($_POST['office'] ?? '');
$address = trim($_POST['address'] ?? '');
$consultation_date = !empty($_POST['consultation_date']) ? $_POST['consultation_date'] : date('Y-m-d');
$consultation_time = !empty($_POST['consultation_time']) ? $_POST['consultation_time'] : date('H:i:s');
$blood_pressure = trim($_POST['blood_pressure'] ?? '');
$heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : 0;
$respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : 0;
$o2_saturation = !empty($_POST['o2_saturation']) ? floatval($_POST['o2_saturation']) : 0.0;
$temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : 0.0;
$height = !empty($_POST['height']) ? floatval($_POST['height']) : 0.0;
$weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : 0.0;
$chief_complaint = trim($_POST['chief_complaint'] ?? '');
$diagnosis = trim($_POST['diagnosis'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$medical_certificate = trim($_POST['medical_certificate'] ?? 'No');
$certificate_copies = !empty($_POST['certificate_copies']) ? intval($_POST['certificate_copies']) : 1;

// Insert consultation record
$stmt = $conn->prepare("
    INSERT INTO consultations (
        employee_id, full_name, sex, age, birthdate, civil_status, phone, office, address,
        consultation_date, consultation_time, blood_pressure, heart_rate, respiratory_rate,
        o2_saturation, temperature, height, weight, chief_complaint, diagnosis, notes,
        medical_certificate, certificate_copies
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ississssssssiiddddssssi",
    $employee_id, $full_name, $sex, $age, $birthdate, $civil_status, $phone, $office, $address,
    $consultation_date, $consultation_time, $blood_pressure, $heart_rate, $respiratory_rate,
    $o2_saturation, $temperature, $height, $weight, $chief_complaint, $diagnosis, $notes,
    $medical_certificate, $certificate_copies
);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: consultation.php?id=' . $employee_id . '&mode=list&success=consultation_saved');
} else {
    $stmt->close();
    header('Location: consultation.php?id=' . $employee_id . '&mode=list&error=save_failed');
}
exit;
?>
