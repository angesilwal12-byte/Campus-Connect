<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/subjects.php";
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

// ----- election state for the banner -----
// none   = no election running (or still a draft)
// open   = voting is open and you have not voted yet
// voted  = voting is open and you already voted
// closed = the latest election is closed
$election_state       = "none";
$show_election_banner = false;
$turnout              = 0;
$total_students       = 0;

$result   = $conn->query("SELECT id, status FROM elections ORDER BY id DESC LIMIT 1");
$election = $result ? $result->fetch_assoc() : null;

if ($election && $election["status"] === "open") {
    $stmt = $conn->prepare("SELECT id FROM voters WHERE election_id=? AND user_id=?");
    $stmt->bind_param("ii", $election["id"], $user_id);
    $stmt->execute();
    $has_voted = $stmt->get_result()->num_rows > 0;

    $election_state       = $has_voted ? "voted" : "open";
    $show_election_banner = !$has_voted;

    // how many classmates have voted so far (just a number, never who or for whom)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM voters WHERE election_id=?");
    $stmt->bind_param("i", $election["id"]);
    $stmt->execute();
    $turnout = (int) $stmt->get_result()->fetch_assoc()["total"];

    $res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
    $total_students = $res ? (int) $res->fetch_assoc()["total"] : 0;
} elseif ($election && $election["status"] === "closed") {
    $election_state = "closed";
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
// ----- subject cards: how many notes each subject has -----
$subject_counts = [];
$res = $conn->query("SELECT subject, COUNT(*) AS total FROM notes GROUP BY subject");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $subject_counts[$row["subject"]] = (int) $row["total"];
    }
}
$total_notes = array_sum($subject_counts);

$subject_names  = array_keys($cc_subjects);
$left_subjects  = array_slice($subject_names, 0, 3);
$right_subjects = array_slice($subject_names, 3);

function sj_card($name, $info, $count) {
    $label = $count === 1 ? "1 note" : $count . " notes";
    echo '<a href="/Campus_Connect/notes/index.php?subject=' . urlencode($name) . '" class="sj-card sj-' . htmlspecialchars($info["color"]) . '">'
       . '<i class="ti ' . htmlspecialchars($info["icon"]) . '"></i>'
       . '<div class="sj-text">'
       . '<strong>' . htmlspecialchars($info["short"]) . '</strong>'
       . '<span>' . htmlspecialchars($info["tagline"]) . '</span>'
       . '<em>' . $label . '</em>'
       . '</div></a>';
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
<link rel="stylesheet" href="/Campus_Connect/assets/css/subjects.css">
<link rel="stylesheet" href="/Campus_Connect/assets/css/theme.css">

</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="cc-main">

<section class="cc-hero">
    <span class="cc-blob"></span>

    <div class="cc-glass">

        <div class="cc-glass-text">
            <span class="cc-chip">
                <i class="ti ti-calendar"></i><?php echo $today; ?> &bull; BCA 4th Sem
            </span>

            <p class="cc-hero-hello"><?php echo htmlspecialchars($hello); ?></p>
            <h1 class="cc-hero-name"><?php echo htmlspecialchars($firstName); ?></h1>
            <p class="cc-hero-sub"><?php echo htmlspecialchars($tagline); ?></p>

            <p class="cc-hero-news"><?php echo htmlspecialchars($news_line); ?></p>
        </div>

        <svg class="cc-glass-art" viewBox="0 0 170 150" role="img" aria-label="Graduation cap on a stack of books">
            <circle cx="85" cy="78" r="60" fill="#fff" opacity="0.35"/>
            <rect x="42" y="100" width="92" height="16" rx="4" fill="#FFD9B8"/>
            <rect x="48" y="84" width="84" height="16" rx="4" fill="#8FD3C5"/>
            <rect x="38" y="68" width="88" height="16" rx="4" fill="#fff"/>
            <rect x="46" y="72" width="30" height="3" rx="1.5" fill="#3FA796"/>
            <rect x="54" y="88" width="34" height="3" rx="1.5" fill="#1F5E54"/>
            <rect x="50" y="104" width="28" height="3" rx="1.5" fill="#C98A4B"/>
            <polygon points="82,28 128,46 82,64 36,46" fill="#1F5E54"/>
            <path d="M60 54 L60 66 Q82 78 104 66 L104 54 L82 63 Z" fill="#2F7A6C"/>
            <line x1="128" y1="46" x2="128" y2="68" stroke="#FFD39A" stroke-width="2.5"/>
            <circle cx="128" cy="71" r="4" fill="#FFD39A"/>
            <path d="M148 24 L151 32 L159 35 L151 38 L148 46 L145 38 L137 35 L145 32 Z" fill="#fff"/>
            <path d="M24 44 L26 50 L32 52 L26 54 L24 60 L22 54 L16 52 L22 50 Z" fill="#fff" opacity="0.9"/>
            <path d="M150 96 L151.5 100 L155.5 101.5 L151.5 103 L150 107 L148.5 103 L144.5 101.5 L148.5 100 Z" fill="#fff" opacity="0.8"/>
        </svg>

    </div>
</section>
<?php if ($election_state === "open"): ?>
    <?php $pct = $total_students > 0 ? min(100, round($turnout / $total_students * 100)) : 0; ?>
    <div class="cb-banner open">
        <div class="cb-icon">
            <i class="ti ti-checkbox"></i>
            <span class="cb-pulse"></span>
        </div>
        <div class="cb-text">
            <strong>Your class needs a CR, and your vote counts</strong>
            <span>Takes 10 seconds and it is completely anonymous</span>
            <div class="cb-progress">
                <div class="cb-bar"><div class="cb-bar-fill" style="width: <?php echo $pct; ?>%"></div></div>
                <em><?php echo (int) $turnout; ?> of <?php echo (int) $total_students; ?> classmates have voted</em>
            </div>
        </div>
        <a href="/Campus_Connect/student/cr_election.php" class="cb-btn">Vote now</a>
    </div>

<?php elseif ($election_state === "voted"): ?>
    <div class="cb-banner voted">
        <div class="cb-icon done"><i class="ti ti-circle-check"></i></div>
        <div class="cb-text">
            <strong>Vote locked in. Thanks for showing up.</strong>
            <span>The result will be shared once voting closes.</span>
        </div>
        <span class="cb-tag"><i class="ti ti-lock"></i>Anonymous</span>
    </div>

<?php elseif ($election_state === "closed"): ?>
    <div class="cb-banner closed">
        <div class="cb-icon muted"><i class="ti ti-flag-3"></i></div>
        <div class="cb-text">
            <strong>Voting is closed</strong>
            <span>The result will be posted in the notices.</span>
        </div>
        <a href="/Campus_Connect/notices/index.php" class="cb-btn ghost">See notices</a>
    </div>
<?php endif; ?>

<section class="sj-section">

    <div class="sj-head">
        <p class="sj-eyebrow">YOUR SUBJECTS</p>
        <h2>Where will your <span>curiosity take you?</span></h2>
        <p class="sj-sub">Pick a subject and jump straight into the notes.</p>
    </div>

    <div class="sj-grid">

        <div class="sj-col">
            <?php foreach ($left_subjects as $name) {
                sj_card($name, $cc_subjects[$name], $subject_counts[$name] ?? 0);
            } ?>
        </div>

        <div class="sj-mascot">
            <svg viewBox="0 0 140 150" role="img" aria-label="A smiling blue and green earth with a dotted orbit and a sparkle">
                <ellipse cx="70" cy="80" rx="62" ry="24" fill="none" stroke="#3B9B5A" stroke-width="2.5" stroke-dasharray="1 7" stroke-linecap="round" transform="rotate(-20 70 80)"/>
                <circle cx="70" cy="80" r="40" fill="#8FD0EE"/>
                <path d="M38 66 Q42 50 58 48 Q70 50 66 62 Q60 72 48 76 Q40 76 38 66 Z" fill="#4FA85A"/>
                <path d="M84 84 Q96 76 106 82 Q108 96 98 104 Q86 104 82 94 Z" fill="#4FA85A"/>
                <path d="M60 31 Q72 28 82 34 Q76 40 66 40 Z" fill="#4FA85A" opacity="0.9"/>
                <path d="M54 80 Q58 76 62 80" fill="none" stroke="#1F4A63" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M78 80 Q82 76 86 80" fill="none" stroke="#1F4A63" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M62 91 Q70 99 78 91" fill="none" stroke="#1F4A63" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="51" cy="90" r="5.5" fill="#F7A8B8" opacity="0.85"/>
                <circle cx="89" cy="90" r="5.5" fill="#F7A8B8" opacity="0.85"/>
                <path d="M118 30 L121 38 L129 41 L121 44 L118 52 L115 44 L107 41 L115 38 Z" fill="#FFD04D"/>
            </svg>
            <p>So much to discover.</p>
        </div>

        <div class="sj-col">
            <?php foreach ($right_subjects as $name) {
                sj_card($name, $cc_subjects[$name], $subject_counts[$name] ?? 0);
            } ?>

            <a href="/Campus_Connect/notes/index.php" class="sj-card sj-all">
                <i class="ti ti-notebook"></i>
                <div class="sj-text">
                    <strong>All notes</strong>
                    <span>Browse everything at once.</span>
                    <em><?php echo $total_notes === 1 ? "1 note" : $total_notes . " notes"; ?></em>
                </div>
            </a>
        </div>

    </div>
</section>

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