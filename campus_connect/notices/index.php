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

// which filter tag is active
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

$total = $result ? $result->num_rows : 0;
$sub   = $total === 0 ? "Nothing pinned up yet" : ($total === 1 ? "1 notice pinned up" : $total . " notices pinned up");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/board.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/theme.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="pb-main">
        <div class="pb-board">

            <div class="pb-sign">
                <b>Notice board</b>
                <span><?php echo $sub; ?></span>
            </div>

            <div class="pb-bar">
                <div class="pb-tags">
                    <a href="index.php" class="pb-tag <?php echo $filter === 'All' ? 'on' : ''; ?>"><i class="hole"></i>All</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?php echo $cat; ?>" class="pb-tag <?php echo $filter === $cat ? 'on' : ''; ?>">
                            <i class="hole"></i><?php echo $cat; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($can_post): ?>
                    <a href="/Campus_Connect/notices/create.php" class="pb-post">
                        <i class="ti ti-plus"></i>Post notice
                    </a>
                <?php endif; ?>
            </div>

            <div class="pb-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $i = 0; ?>
                    <?php while ($n = $result->fetch_assoc()): ?>
                        <?php
                            $i++;
                            $time    = strtotime($n["created_at"]);
                            $is_new  = $time > strtotime("-3 days");
                            $cat     = $n["category"];
                            $slug    = strtolower($cat);
                            $preview = mb_strimwidth($n["content"], 0, 120, "...");
                        ?>
                        <article class="pb-note pb-<?php echo $slug; ?>">

                            <?php if ($i % 2 === 0): ?>
                                <span class="pb-tape"></span>
                            <?php else: ?>
                                <span class="pb-pin"></span>
                            <?php endif; ?>

                            <?php if ($is_new): ?><span class="pb-new">NEW</span><?php endif; ?>

                            <span class="pb-date"><?php echo date("j M", $time); ?></span>
                            <h3 class="pb-title"><?php echo htmlspecialchars($n["title"]); ?></h3>
                            <p class="pb-body"><?php echo htmlspecialchars($preview); ?></p>

                            <details class="pb-more">
                                <summary>Read full notice</summary>
                                <p><?php echo nl2br(htmlspecialchars($n["content"])); ?></p>
                            </details>

                            <div class="pb-foot">
                                <span class="pb-cat">
                                    <i class="ti <?php echo $icons[$cat] ?? 'ti-speakerphone'; ?>"></i><?php echo $cat; ?>
                                </span>
                                <span><i class="ti ti-user"></i><?php echo htmlspecialchars($n["full_name"]); ?></span>

                                <?php if ($role === "admin"): ?>
                                    <form method="POST" action="/Campus_Connect/notices/delete.php" class="pb-delete-form"
                                          onsubmit="return confirm('Take this notice down? This cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo (int) $n["id"]; ?>">
                                        <button type="submit" class="pb-delete"><i class="ti ti-pinned-off"></i>Take down</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="pb-empty">
                        <i class="ti ti-pin"></i>
                        <p>The board is empty. Nothing pinned up here yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

</body>
</html>