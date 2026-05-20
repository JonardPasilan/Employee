<?php
require_once __DIR__ . '/db.php';

if(isset($_POST['delete'])){
    $id = intval($_POST['id']);

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

    $stmt = $conn->prepare("DELETE FROM employee_health_profiles WHERE employee_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: employees.php");
    exit();
}
?>