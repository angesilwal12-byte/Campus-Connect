<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only admins can open this page
if ($_SESSION["role"] !== "admin") {
    header("Location: /Campus_Connect/index.php");
    exit;
}

$firstName = explode(" ", trim($_SESSION["full_name"] ?? "Admin"))[0];

date_default_timezone_set("Asia/Kathmandu");
$hour = (int) date("G");
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}
$today = date("l, j F Y");

// counts for the stat cards
$res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
$total_students = $res ? $res->fetch_assoc()["total"] : 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'");
$total_teachers = $res ? $res->fetch_assoc()["total"] : 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM notices");
$total_notices = $res ? $res->fetch_assoc()["total"] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/dashboard.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/tdashboard.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="cc-main">

        <section class="cc-hero">
            <span class="cc-chip">
                <i class="ti ti-calendar"></i><?php echo $today; ?>
            </span>

            <p class="cc-hero-hello"><?php echo $greeting; ?>,</p>
            <h1 class="cc-hero-name"><?php echo htmlspecialchars($firstName); ?></h1>
            <p class="cc-hero-sub">Administrator</p>
        </section>

        <div class="cc-stats">
            <div class="cc-stat">
                <i class="ti ti-users"></i>
                <div>
                    <strong><?php echo (int) $total_students; ?></strong>
                    <span>Students</span>
                </div>
            </div>
            <div class="cc-stat">
                <i class="ti ti-chalkboard"></i>
                <div>
                    <strong><?php echo (int) $total_teachers; ?></strong>
                    <span>Teachers</span>
                </div>
            </div>
            <div class="cc-stat">
                <i class="ti ti-bell"></i>
                <div>
                    <strong><?php echo (int) $total_notices; ?></strong>
                    <span>Notices posted</span>
                </div>
            </div>
        </div>

        <div class="cc-section-head">
            <h2>Quick actions</h2>
        </div>

        <div class="cc-actions">
            <a href="/Campus_Connect/notices/create.php" class="cc-action">
                <i class="ti ti-speakerphone"></i>
                <div>
                    <h3>Post a notice</h3>
                    <p>Share an announcement with the campus.</p>
                </div>
            </a>

            <a href="/Campus_Connect/notices/index.php" class="cc-action">
                <i class="ti ti-bell"></i>
                <div>
                    <h3>View notices</h3>
                    <p>See everything on the notice board.</p>
                </div>
            </a>
        </div>

    </main>
</div>

</body>
</html>