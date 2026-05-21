<?php
require_once __DIR__ . '/db.php';

if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $type = $_POST['type'] ?? '';

    if ($type === 'consultation') {
        $stmt = $conn->prepare("DELETE FROM consultations WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        $eid = intval($_POST['employee_id'] ?? 0);
        header("Location: consultation.php?id=" . $eid . "&mode=list&success=deleted");
        exit();
    } elseif ($type === 'prescription') {
        $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: prescriptions.php?mode=list&success=deleted");
        exit();
    }
}
header("Location: employees.php");
exit();
?>