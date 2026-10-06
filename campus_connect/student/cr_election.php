<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth_check.php";

// only students can vote
if ($_SESSION["role"] !== "student") {
    header("Location: /Campus_Connect/index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$message = "";
$msg_type = "";
$already_voted = false;
$candidates = null;
function getInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
    return $initials;
}

// find the open election
$result = $conn->query("SELECT * FROM elections WHERE status='open' LIMIT 1");
$election = $result ? $result->fetch_assoc() : null;

if ($election) {
    $election_id = $election["id"];

    // already voted?
    $stmt = $conn->prepare("SELECT id FROM voters WHERE election_id=? AND user_id=?");
    $stmt->bind_param("ii", $election_id, $user_id);
    $stmt->execute();
    $already_voted = $stmt->get_result()->num_rows > 0;

    // handle vote
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !$already_voted) {
        $candidate_id = (int)($_POST["candidate_id"] ?? 0);

        $stmt = $conn->prepare("SELECT id FROM candidates WHERE id=? AND election_id=?");
        $stmt->bind_param("ii", $candidate_id, $election_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 1) {
            $conn->begin_transaction();
            try {
                $s1 = $conn->prepare("INSERT INTO voters (election_id, user_id) VALUES (?, ?)");
                $s1->bind_param("ii", $election_id, $user_id);
                $s1->execute();

                $s2 = $conn->prepare("INSERT INTO votes (election_id, candidate_id) VALUES (?, ?)");
                $s2->bind_param("ii", $election_id, $candidate_id);
                $s2->execute();

                $conn->commit();
                $already_voted = true;
                $message = "Your vote has been recorded. Thank you!";
                $msg_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Something went wrong. Please try again.";
                $msg_type = "error";
            }
        } else {
            $message = "Invalid candidate.";
            $msg_type = "error";
        }
    }

    // candidates
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id=?");
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $candidates = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CR Election | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/admin.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/cr_election.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/theme.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="student-dashboard">
        <div class="vote-container">
<h2 class="vote-title">CR Election</h2>
<?php if ($election): ?>
    <p class="vote-sub"><?= htmlspecialchars($election["title"]) ?> &bull; <?= htmlspecialchars($election["semester"]) ?></p>
<?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (!$election): ?>
                <div class="vote-empty">No election is open right now.</div>

            <?php elseif ($already_voted): ?>
                <div class="vote-empty">You have already voted in this election. &#10003;</div>

            <?php else: ?>
             <form method="POST" class="vote-form" onsubmit="return confirm('Are you sure? You can only vote once and cannot change your vote later.');">
                    <div class="candidate-list">
                        <?php while ($c = $candidates->fetch_assoc()): ?>
                            <label class="candidate-card">
                                <input type="radio" name="candidate_id" value="<?= $c["id"] ?>" required>
                                <div class="candidate-body">
                                   <div class="candidate-avatar"><?= htmlspecialchars(getInitials($c["name"])) ?></div>
                                    <div class="candidate-info">
                                        <h3><?= htmlspecialchars($c["name"]) ?></h3>
                                        <p><?= htmlspecialchars($c["manifesto"]) ?></p>
                                    </div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    </div>

                    <button type="submit" class="btn-vote">Submit Vote</button>
                    <p class="vote-note">&#128274; Your vote is anonymous. You can only vote once.</p>
                </form>
            <?php endif; ?>

        </div>
    </main>
</div>

</body>
</html>