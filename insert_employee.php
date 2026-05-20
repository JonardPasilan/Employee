<?php
require_once __DIR__ . '/db.php';

if(isset($_POST['save'])){
    $n = (string)($_POST['name'] ?? '');
    $a = intval($_POST['age'] ?? 0);
    $s = (string)($_POST['sex'] ?? '');
    $b = (string)($_POST['birthday'] ?? '');
    $ad = (string)($_POST['address'] ?? '');
    $c = (string)($_POST['contact'] ?? '');
    $d = (string)($_POST['department'] ?? '');
    $cs = (string)($_POST['civil_status'] ?? '');

    $stmt = $conn->prepare("INSERT INTO employees (name, age, sex, birthday, address, contact, department, civil_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssss", $n, $a, $s, $b, $ad, $c, $d, $cs);
    $stmt->execute();
    $stmt->close();

    $newId = intval($conn->insert_id);
    header("Location: health.php?mode=edit&id=" . $newId);
    exit();
}
?>