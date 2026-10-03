<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

$role     = $_SESSION["role"];
$can_post = in_array($role, ["admin", "teacher"], true);

$categories = ["Exam", "Holiday", "Event", "General"];
$icons = [
    "Exam"    => "ti-file-text",
    "Holiday" => "ti-beach",
    "Event"   => "ti-calendar-event",
    "General" => "ti-speakerphone"
];

// which filter chip is active
$filter = $_GET["category"] ?? "All";
if (!in_array($filter, $categories, true)) {
    $filter = "All";
}

if ($filter === "All") {
    $result = $conn->query(
        "SELECT n.*, u.full_name FROM notices n
         JOIN users u ON u.id = n.posted_by
         ORDER BY n.created_at DESC"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT n.*, u.full_name FROM notices n
         JOIN users u ON u.id = n.posted_by
         WHERE n.category = ?
         ORDER BY n.created_at DESC"
    );
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/notices.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="nt-main">
 <div class="nt-wrap">
        <div class="nt-header">
            <div>
                <h1>Notice board</h1>
                <p>Announcements from your campus</p>
            </div>

            <?php if ($can_post): ?>
                <a href="/Campus_Connect/notices/create.php" class="nt-post-btn">
                    <i class="ti ti-plus"></i>Post notice
                </a>
            <?php endif; ?>
        </div>

        <div class="nt-chips">
            <a href="index.php" class="<?php echo $filter === 'All' ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?php echo $cat; ?>" class="<?php echo $filter === $cat ? 'active' : ''; ?>">
                    <?php echo $cat; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="nt-list">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($n = $result->fetch_assoc()): ?>
                    <?php
                        $time   = strtotime($n["created_at"]);
                        $is_new = $time > strtotime("-3 days");
                        $cat    = $n["category"];
                        $preview = mb_strimwidth($n["content"], 0, 120, "...");
                    ?>
                    <article class="nt-card">
                        <div class="nt-date cat-<?php echo strtolower($cat); ?>">
                            <span class="nt-day"><?php echo date("d", $time); ?></span>
                            <span class="nt-month"><?php echo date("M", $time); ?></span>
                        </div>

                        <div class="nt-body">
                            <div class="nt-title-row">
                                <h3><?php echo htmlspecialchars($n["title"]); ?></h3>
                                <?php if ($is_new): ?><span class="nt-new">New</span><?php endif; ?>
                            </div>

                            <p class="nt-preview"><?php echo htmlspecialchars($preview); ?></p>

                            <details class="nt-more">
                                <summary>Read full notice</summary>
                                <p><?php echo nl2br(htmlspecialchars($n["content"])); ?></p>
                            </details>

                            <div class="nt-meta">
                                <span class="nt-cat cat-<?php echo strtolower($cat); ?>-text">
                                    <i class="ti <?php echo $icons[$cat] ?? 'ti-speakerphone'; ?>"></i><?php echo $cat; ?>
                                </span>
                                <span><i class="ti ti-user"></i><?php echo htmlspecialchars($n["full_name"]); ?></span>
                                 <?php if ($role === "admin"): ?>
        <form method="POST" action="/Campus_Connect/notices/delete.php" class="nt-delete-form"
              onsubmit="return confirm('Delete this notice? This cannot be undone.');">
            <input type="hidden" name="id" value="<?php echo (int) $n["id"]; ?>">
            <button type="submit" class="nt-delete"><i class="ti ti-trash"></i>Delete</button>
        </form>
    <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="nt-empty">
                    <i class="ti ti-bell"></i>
                    <p>No notices here yet.</p>
                </div>
            <?php endif; ?>
        </div>
 </div>
    </main>
</div>

</body>
</html>