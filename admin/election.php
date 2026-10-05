<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only admins can manage the election
if ($_SESSION["role"] !== "admin") {
    header("Location: /Campus_Connect/index.php");
    exit;
}

function go($type, $text) {
    $_SESSION["flash"] = ["type" => $type, "text" => $text];
    header("Location: /Campus_Connect/admin/election.php");
    exit;
}

function getInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initials;
}

// the latest election is the "current" one
$current = $conn->query("SELECT * FROM elections ORDER BY id DESC LIMIT 1")->fetch_assoc();

/* ---------- handle actions ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "create_election") {
        if ($current && $current["status"] !== "closed") {
            go("error", "Close the current election before starting a new one.");
        }
        $title    = trim($_POST["title"] ?? "");
        $semester = trim($_POST["semester"] ?? "");
        if ($title === "") {
            go("error", "Please enter a title for the election.");
        }
        $stmt = $conn->prepare("INSERT INTO elections (title, semester, status) VALUES (?, ?, 'draft')");
        $stmt->bind_param("ss", $title, $semester);
        $stmt->execute();
        go("success", "Election created. Now add the candidates.");
    }

    if (!$current) {
        go("error", "There is no election yet.");
    }
    $eid = (int) $current["id"];

    if ($action === "add_candidate") {
        if ($current["status"] !== "draft") {
            go("error", "Candidates can only be added before voting opens.");
        }
        $name      = trim($_POST["name"] ?? "");
        $manifesto = trim($_POST["manifesto"] ?? "");
        if ($name === "") {
            go("error", "Please enter the candidate's name.");
        }
        $stmt = $conn->prepare("INSERT INTO candidates (election_id, name, manifesto) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $eid, $name, $manifesto);
        $stmt->execute();
        go("success", "Candidate added.");
    }

    if ($action === "remove_candidate") {
        if ($current["status"] !== "draft") {
            go("error", "Candidates can only be removed before voting opens.");
        }
        $cid = (int) ($_POST["candidate_id"] ?? 0);
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ? AND election_id = ?");
        $stmt->bind_param("ii", $cid, $eid);
        $stmt->execute();
        go("success", "Candidate removed.");
    }

    if ($action === "open_election") {
        if ($current["status"] !== "draft") {
            go("error", "This election cannot be opened.");
        }
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM candidates WHERE election_id = ?");
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()["total"] < 2) {
            go("error", "Add at least 2 candidates before opening voting.");
        }
        $stmt = $conn->prepare("UPDATE elections SET status = 'open' WHERE id = ?");
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        go("success", "Voting is now open. Students can vote.");
    }

    if ($action === "close_election") {
        if ($current["status"] !== "open") {
            go("error", "This election is not open.");
        }
        $stmt = $conn->prepare("UPDATE elections SET status = 'closed' WHERE id = ?");
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        go("success", "Voting is closed. The results are ready.");
    }

    go("error", "Unknown action.");
}

/* ---------- load data for the page ---------- */
$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);

$candidates  = [];
$results     = [];
$turnout     = 0;
$total_votes = 0;
$max_votes   = 0;

if ($current) {
    $eid = (int) $current["id"];

    $stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY id");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM voters WHERE election_id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $turnout = (int) $stmt->get_result()->fetch_assoc()["total"];

    // results are only loaded once voting is closed
    if ($current["status"] === "closed") {
        $stmt = $conn->prepare(
            "SELECT c.id, c.name, COUNT(v.id) AS votes
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = c.election_id
             WHERE c.election_id = ?
             GROUP BY c.id, c.name
             ORDER BY votes DESC, c.name"
        );
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($results as $r) {
            $total_votes += (int) $r["votes"];
        }
        $max_votes = $results ? (int) $results[0]["votes"] : 0;
    }
}

$status = $current["status"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CR Election | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/election.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/theme.css">

</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="el-main">
        <div class="el-wrap">

            <div class="el-header">
                <div>
                    <h1>CR election</h1>
                    <p>Create, open and close the class representative vote</p>
                </div>
                <?php if ($current): ?>
                    <span class="el-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($flash): ?>
                <div class="el-alert <?php echo $flash["type"]; ?>"><?php echo htmlspecialchars($flash["text"]); ?></div>
            <?php endif; ?>

            <?php if ($current): ?>
                <p class="el-current">
                    <?php echo htmlspecialchars($current["title"]); ?>
                    <?php if ($current["semester"]): ?> &bull; <?php echo htmlspecialchars($current["semester"]); ?><?php endif; ?>
                </p>
            <?php endif; ?>


            <?php if ($status === "draft"): ?>

                <section class="el-card">
                    <h2>Candidates</h2>

                    <?php if (count($candidates) === 0): ?>
                        <p class="el-muted">No candidates yet. Add at least 2 below.</p>
                    <?php endif; ?>

                    <?php foreach ($candidates as $c): ?>
                        <div class="el-cand">
                            <div class="el-avatar"><?php echo htmlspecialchars(getInitials($c["name"])); ?></div>
                            <div class="el-cand-info">
                                <strong><?php echo htmlspecialchars($c["name"]); ?></strong>
                                <span><?php echo htmlspecialchars($c["manifesto"] ?? ""); ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Remove this candidate?');">
                                <input type="hidden" name="action" value="remove_candidate">
                                <input type="hidden" name="candidate_id" value="<?php echo (int) $c["id"]; ?>">
                                <button type="submit" class="el-btn-danger"><i class="ti ti-trash"></i>Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </section>

                <section class="el-card">
                    <h2>Add a candidate</h2>
                    <form method="POST" class="el-form">
                        <input type="hidden" name="action" value="add_candidate">

                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" maxlength="100" placeholder="Ram Sharma" required>

                        <label for="manifesto">Manifesto (optional)</label>
                        <textarea id="manifesto" name="manifesto" rows="3" placeholder="What will this candidate do for the class?"></textarea>

                        <button type="submit" class="el-btn"><i class="ti ti-plus"></i>Add candidate</button>
                    </form>
                </section>

                <section class="el-card">
                    <h2>Ready to start?</h2>
                    <p class="el-muted">Once voting opens, candidates can no longer be added or removed.</p>
                    <form method="POST" onsubmit="return confirm('Open voting now? Students will be able to vote.');">
                        <input type="hidden" name="action" value="open_election">
                        <button type="submit" class="el-btn" <?php echo count($candidates) < 2 ? "disabled" : ""; ?>>
                            <i class="ti ti-player-play"></i>Open voting
                        </button>
                    </form>
                    <?php if (count($candidates) < 2): ?>
                        <p class="el-hint">Add at least 2 candidates to unlock this button.</p>
                    <?php endif; ?>
                </section>

            <?php elseif ($status === "open"): ?>

                <section class="el-card">
                    <h2>Voting is open</h2>
                    <div class="el-turnout">
                        <strong><?php echo $turnout; ?></strong>
                        <span>students have voted so far</span>
                    </div>
                    <p class="el-muted">Results stay hidden until you close voting, so the vote stays fair.</p>
                    <form method="POST" onsubmit="return confirm('Close voting? Students will no longer be able to vote.');">
                        <input type="hidden" name="action" value="close_election">
                        <button type="submit" class="el-btn-danger solid"><i class="ti ti-player-stop"></i>Close voting</button>
                    </form>
                </section>

            <?php elseif ($status === "closed"): ?>

                <section class="el-card">
                    <h2>Results</h2>
                    <p class="el-muted"><?php echo $total_votes; ?> vote<?php echo $total_votes === 1 ? "" : "s"; ?> counted</p>

                    <?php foreach ($results as $r): ?>
                        <?php
                            $votes   = (int) $r["votes"];
                            $percent = $total_votes > 0 ? round($votes / $total_votes * 100) : 0;
                            $winner  = $max_votes > 0 && $votes === $max_votes;
                        ?>
                        <div class="el-result <?php echo $winner ? "winner" : ""; ?>">
                            <div class="el-result-top">
                                <strong>
                                    <?php echo htmlspecialchars($r["name"]); ?>
                                    <?php if ($winner): ?><span class="el-win"><i class="ti ti-trophy"></i>Leading</span><?php endif; ?>
                                </strong>
                                <span><?php echo $votes; ?> vote<?php echo $votes === 1 ? "" : "s"; ?> &bull; <?php echo $percent; ?>%</span>
                            </div>
                            <div class="el-bar"><div class="el-bar-fill" style="width: <?php echo $percent; ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                </section>

            <?php endif; ?>


            <?php if (!$current || $status === "closed"): ?>
                <section class="el-card">
                    <h2>Start a new election</h2>
                    <form method="POST" class="el-form">
                        <input type="hidden" name="action" value="create_election">

                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" maxlength="150" placeholder="CR Election" required>

                        <label for="semester">Class or semester</label>
                        <input type="text" id="semester" name="semester" maxlength="50" placeholder="BCA 4th Sem">

                        <button type="submit" class="el-btn"><i class="ti ti-plus"></i>Create election</button>
                    </form>
                </section>
            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>