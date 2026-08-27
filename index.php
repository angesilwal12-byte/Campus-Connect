<?php
require_once __DIR__ . "/config/db.php";

if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["role"];

    if ($role === "admin") {
        header("Location: /Campus_Connect/admin/dashboard.php");
    } elseif ($role === "teacher") {
        header("Location: /Campus_Connect/teacher/dashboard.php");
    } else {
        header("Location: /Campus_Connect/student/dashboard.php");
    }

    exit;
}

header("Location: /Campus_Connect/auth/login.php");
exit;