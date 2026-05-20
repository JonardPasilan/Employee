<?php
// Legacy route: redirect to the new multi-step health profile wizard.
$id = intval($_GET['id'] ?? 0);
header("Location: health.php?mode=edit&id=" . $id);
exit();
?>