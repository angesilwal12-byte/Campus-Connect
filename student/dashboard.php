<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only students can open this page
if ($_SESSION["role"] !== "student") {
    header("Location: /Campus_Connect/index.php");
    exit;
}

$studentName = $_SESSION["full_name"] ?? "Student";
$firstName   = explode(" ", trim($studentName))[0];
$user_id     = $_SESSION["user_id"];

// is there an open election this student hasn't voted in yet?
$show_election_banner = false;

$result   = $conn->query("SELECT id FROM elections WHERE status='open' LIMIT 1");
$election = $result ? $result->fetch_assoc() : null;

if ($election) {
    $stmt = $conn->prepare("SELECT id FROM voters WHERE election_id=? AND user_id=?");
    $stmt->bind_param("ii", $election["id"], $user_id);
    $stmt->execute();
    $show_election_banner = $stmt->get_result()->num_rows === 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/dashboard.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="cc-main">

        <h1 class="cc-greeting">
            Hi, <?php echo htmlspecialchars($firstName); ?>
        </h1>
        <p class="cc-subtitle">BCA 4th Sem</p>

        <?php if ($show_election_banner): ?>
            <div class="cc-banner">
                <i class="ti ti-checkbox"></i>
                <div class="cc-banner-text">
                    <strong>CR election is open</strong>
                    <span>Your vote is anonymous and counts once</span>
                </div>
                <a href="/Campus_Connect/student/cr_election.php" class="cc-banner-btn">Vote now</a>
            </div>
        <?php endif; ?>

        <div class="cc-columns">

            <section>
                <div class="cc-section-head">
                    <h2>Latest notices</h2>
                    <a href="/Campus_Connect/notices/index.php">See all</a>
                </div>
                <div class="cc-list">
                    <div class="cc-empty">
                        <i class="ti ti-bell"></i>
                        <p>No notices yet. New announcements will show up here.</p>
                    </div>
                </div>
            </section>

            <section>
                <div class="cc-section-head">
                    <h2>New notes</h2>
                    <a href="/Campus_Connect/notes/index.php">See all</a>
                </div>
                <div class="cc-list">
                    <div class="cc-empty">
                        <i class="ti ti-notebook"></i>
                        <p>No notes yet. Uploads from your teachers will show up here.</p>
                    </div>
                </div>
            </section>

        </div>

    </main>
</div>

</body>
</html>