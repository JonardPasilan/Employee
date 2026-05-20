<?php
session_start();
session_destroy();
header("Location: access.php");
exit;
?>
