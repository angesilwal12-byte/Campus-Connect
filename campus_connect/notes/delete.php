<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only admins can delete, and only through the form button (POST)
if ($_SESSION["role"] !== "admin" || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /Campus_Connect/notes/index.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("SELECT file_name FROM notes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $note = $stmt->get_result()->fetch_assoc();

    if ($note) {
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // remove the file from the folder too
        $path = __DIR__ . "/../uploads/notes/" . basename($note["file_name"]);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

header("Location: /Campus_Connect/notes/index.php");
exit;