<?php
require_once __DIR__ . '/db.php';

if(isset($_POST['update'])){
    $id = intval($_POST['id'] ?? 0);
    $n = (string)($_POST['name'] ?? '');
    $a = intval($_POST['age'] ?? 0);
    $s = (string)($_POST['sex'] ?? '');
    $b = (string)($_POST['birthday'] ?? '');
    $ad = (string)($_POST['address'] ?? '');
    $c = (string)($_POST['contact'] ?? '');
    $d = (string)($_POST['department'] ?? '');
    $cs = (string)($_POST['civil_status'] ?? '');

    if($id > 0){
        $stmt = $conn->prepare("UPDATE employees SET name=?, age=?, sex=?, birthday=?, address=?, contact=?, department=?, civil_status=? WHERE id=?");
        $stmt->bind_param("sissssssi", $n, $a, $s, $b, $ad, $c, $d, $cs, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: health.php?mode=edit&id=" . $id);
    exit();
}
?>