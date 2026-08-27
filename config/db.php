<?php
$host = "localhost";
$user = "root";
$pass = "";          // XAMPP Mac default is empty
$db   = "Campus_Connect";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}