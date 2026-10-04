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
date_default_timezone_set("Asia/Kathmandu");
$today = date("l, j F Y");

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
// latest 3 notices for the dashboard box
$latest_notices = $conn->query(
    "SELECT id, title, category, created_at FROM notices ORDER BY created_at DESC LIMIT 3"
);
// latest 3 notes for the dashboard box
$latest_notes = $conn->query(
    "SELECT id, title, subject, file_name, created_at FROM notes ORDER BY created_at DESC LIMIT 3"
);
// ----- fun greeting -----
$h = (int) date("G");

if ($h >= 5 && $h < 12) {
    $slots = [
        ["Morning,", "Coffee first, deadlines second."],
        ["Up already,", "Respect."],
        ["New day,", "Let's see what's waiting."]
    ];
} elseif ($h >= 12 && $h < 17) {
    $slots = [
        ["Hey,", "Mid-day check-in time."],
        ["Afternoon,", "Survived the lectures?"],
        ["Welcome back,", "Let's see what you missed."]
    ];
} elseif ($h >= 17 && $h < 22) {
    $slots = [
        ["Evening,", "Anything new? Let's find out."],
        ["Hey,", "Today's almost done. One last look?"],
        ["Back again,", "Good, we saved you a seat."]
    ];
} else {
    $slots = [
        ["Still up,", "Quick check, then sleep."],
        ["It's late,", "Whatever it is, it can wait till morning."],
        ["Night owl mode,", "We see you."]
    ];
}

$pick    = $slots[array_rand($slots)];
$hello   = $pick[0];
$tagline = $pick[1];

// what is new in the last 3 days
$res = $conn->query("SELECT COUNT(*) AS total FROM notices WHERE created_at > (NOW() - INTERVAL 3 DAY)");
$new_notices = $res ? (int) $res->fetch_assoc()["total"] : 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM notes WHERE created_at > (NOW() - INTERVAL 3 DAY)");
$new_notes = $res ? (int) $res->fetch_assoc()["total"] : 0;

$parts = [];
if ($new_notices > 0) {
    $parts[] = $new_notices . " new notice" . ($new_notices === 1 ? "" : "s");
}
if ($new_notes > 0) {
    $parts[] = $new_notes . " new note" . ($new_notes === 1 ? "" : "s");
}

if (count($parts) > 0) {
    $openers   = ["Heads up, something new dropped:", "Fresh stuff just landed:", "You missed some things:"];
    $news_line = $openers[array_rand($openers)] . " " . implode(" and ", $parts) . ".";
} elseif ($show_election_banner) {
    $news_line = "Your class needs a CR, and your vote counts.";
} else {
    $news_line = "All quiet today. Enjoy the calm.";
}
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
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="cc-main">

<section class="cc-hero">
    <span class="cc-chip">
        <i class="ti ti-calendar"></i><?php echo $today; ?> &bull; BCA 4th Sem
    </span>

    <p class="cc-hero-hello"><?php echo htmlspecialchars($hello); ?></p>
    <h1 class="cc-hero-name"><?php echo htmlspecialchars($firstName); ?></h1>
    <p class="cc-hero-sub"><?php echo htmlspecialchars($tagline); ?></p>

    <p class="cc-hero-news"><?php echo htmlspecialchars($news_line); ?></p>
</section>
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
    <?php if ($latest_notices && $latest_notices->num_rows > 0): ?>
        <?php while ($n = $latest_notices->fetch_assoc()): ?>
          <?php $ts = strtotime($n["created_at"]); ?>
<a href="/Campus_Connect/notices/index.php" class="cc-item">
    <div class="cc-tile cat-<?php echo strtolower($n["category"]); ?>">
        <span class="cc-tile-day"><?php echo date("d", $ts); ?></span>
        <span class="cc-tile-month"><?php echo date("M", $ts); ?></span>
    </div>
    <div class="cc-item-text">
        <p class="cc-item-title"><?php echo htmlspecialchars($n["title"]); ?></p>
        <p class="cc-item-meta"><?php echo htmlspecialchars($n["category"]); ?></p>
    </div>
    <i class="ti ti-chevron-right"></i>
</a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="cc-empty">
            <i class="ti ti-bell"></i>
            <p>No notices yet. New announcements will show up here.</p>
        </div>
    <?php endif; ?>
</div>
            </section>

            <section>
                <div class="cc-section-head">
                    <h2>New notes</h2>
                    <a href="/Campus_Connect/notes/index.php">See all</a>
                </div>
               <div class="cc-list">
    <?php if ($latest_notes && $latest_notes->num_rows > 0): ?>
        <?php while ($nt = $latest_notes->fetch_assoc()): ?>
            <?php
                $ext = strtolower(pathinfo($nt["file_name"], PATHINFO_EXTENSION));
                if ($ext === "pdf") {
                    $ft = "pdf";
                    $ficon = "ti-file-type-pdf";
                } elseif ($ext === "doc" || $ext === "docx") {
                    $ft = "doc";
                    $ficon = "ti-file-type-doc";
                } elseif ($ext === "ppt" || $ext === "pptx") {
                    $ft = "ppt";
                    $ficon = "ti-file-type-ppt";
                } else {
                    $ft = "other";
                    $ficon = "ti-file";
                }
            ?>
            <a href="/Campus_Connect/notes/index.php" class="cc-item">
                <div class="cc-tile ft-<?php echo $ft; ?>">
                    <i class="ti <?php echo $ficon; ?>"></i>
                </div>
                <div class="cc-item-text">
                    <p class="cc-item-title"><?php echo htmlspecialchars($nt["title"]); ?></p>
                    <p class="cc-item-meta">
                        <?php echo htmlspecialchars($nt["subject"]); ?>
                        &bull; <?php echo date("j M", strtotime($nt["created_at"])); ?>
                    </p>
                </div>
                <i class="ti ti-chevron-right"></i>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="cc-empty">
            <i class="ti ti-notebook"></i>
            <p>No notes yet. Uploads from your teachers will show up here.</p>
        </div>
    <?php endif; ?>
</div>
            </section>

        </div>

    </main>
</div>

</body>
</html>