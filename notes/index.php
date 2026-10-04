<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

$role       = $_SESSION["role"];
$can_upload = in_array($role, ["teacher", "admin"], true);

// subjects that actually have notes become the filter chips
$subjects = [];
$res = $conn->query("SELECT DISTINCT subject FROM notes ORDER BY subject");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row["subject"];
    }
}

$filter = $_GET["subject"] ?? "All";
if ($filter !== "All" && !in_array($filter, $subjects, true)) {
    $filter = "All";
}

if ($filter === "All") {
    $result = $conn->query(
        "SELECT n.*, u.full_name FROM notes n
         JOIN users u ON u.id = n.uploaded_by
         ORDER BY n.created_at DESC"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT n.*, u.full_name FROM notes n
         JOIN users u ON u.id = n.uploaded_by
         WHERE n.subject = ?
         ORDER BY n.created_at DESC"
    );
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();
}

function file_kind($file_name) {
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($ext === "pdf") {
        return ["pdf", "ti-file-type-pdf", "PDF"];
    }
    if ($ext === "doc" || $ext === "docx") {
        return ["doc", "ti-file-type-doc", "Word"];
    }
    if ($ext === "ppt" || $ext === "pptx") {
        return ["ppt", "ti-file-type-ppt", "PowerPoint"];
    }
    return ["other", "ti-file", strtoupper($ext)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/notes.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="nb-main">
        <div class="nb-wrap">

            <div class="nb-header">
                <div>
                    <h1>Notes</h1>
                    <p>Study material shared by your teachers</p>
                </div>

                <?php if ($can_upload): ?>
                    <a href="/Campus_Connect/notes/upload.php" class="nb-upload-btn">
                        <i class="ti ti-upload"></i>Upload notes
                    </a>
                <?php endif; ?>
            </div>

            <?php if (count($subjects) > 0): ?>
                <div class="nb-chips">
                    <a href="index.php" class="<?php echo $filter === 'All' ? 'active' : ''; ?>">All</a>
                    <?php foreach ($subjects as $s): ?>
                        <a href="index.php?subject=<?php echo urlencode($s); ?>"
                           class="<?php echo $filter === $s ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($s); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="nb-list">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($n = $result->fetch_assoc()): ?>
                        <?php [$kind, $icon, $label] = file_kind($n["file_name"]); ?>
                        <article class="nb-card">
                            <div class="nb-icon ft-<?php echo $kind; ?>">
                                <i class="ti <?php echo $icon; ?>"></i>
                            </div>

                            <div class="nb-body">
                                <h3><?php echo htmlspecialchars($n["title"]); ?></h3>
                                <div class="nb-meta">
                                    <span class="nb-subject"><?php echo htmlspecialchars($n["subject"]); ?></span>
                                    <span><i class="ti ti-user"></i><?php echo htmlspecialchars($n["full_name"]); ?></span>
                                    <span><i class="ti ti-calendar"></i><?php echo date("j M Y", strtotime($n["created_at"])); ?></span>
                                    <span><?php echo $label; ?></span>
                                </div>
                            </div>

                            <a href="/Campus_Connect/notes/download.php?id=<?php echo (int) $n["id"]; ?>" class="nb-download">
                                <i class="ti ti-download"></i>Download
                            </a>
                            <?php if ($role === "admin"): ?>
    <form method="POST" action="/Campus_Connect/notes/delete.php" class="nb-delete-form"
          onsubmit="return confirm('Delete these notes? The file will be removed for good.');">
        <input type="hidden" name="id" value="<?php echo (int) $n["id"]; ?>">
        <button type="submit" class="nb-delete"><i class="ti ti-trash"></i>Delete</button>
    </form>
<?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="nb-empty">
                        <i class="ti ti-notebook"></i>
                        <p>No notes here yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

</body>
</html>