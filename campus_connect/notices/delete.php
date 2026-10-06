<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only admins can delete, and only through the form button (POST)
if ($_SESSION["role"] !== "admin" || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /Campus_Connect/notices/index.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: /Campus_Connect/notices/index.php");
exit;