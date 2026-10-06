<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

$id = (int) ($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT file_name, original_name FROM notes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();

if (!$note) {
    header("Location: /Campus_Connect/notes/index.php");
    exit;
}

$path = __DIR__ . "/../uploads/notes/" . basename($note["file_name"]);

if (!is_file($path)) {
    header("Location: /Campus_Connect/notes/index.php");
    exit;
}

$ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = [
    "pdf"  => "application/pdf",
    "doc"  => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "ppt"  => "application/vnd.ms-powerpoint",
    "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation"
];
$type = $types[$ext] ?? "application/octet-stream";

// make the download name safe
$name = str_replace(['"', "\r", "\n", "\\", "/"], "", $note["original_name"]);
if ($name === "") {
    $name = "notes." . $ext;
}

while (ob_get_level()) {
    ob_end_clean();
}

header("Content-Type: " . $type);
header("Content-Disposition: attachment; filename=\"" . $name . "\"; filename*=UTF-8''" . rawurlencode($name));
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
readfile($path);
exit;