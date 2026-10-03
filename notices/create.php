<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only admins and teachers can post
if (!in_array($_SESSION["role"], ["admin", "teacher"], true)) {
    header("Location: /Campus_Connect/notices/index.php");
    exit;
}

$categories = ["General", "Exam", "Holiday", "Event"];
$error    = "";
$title    = "";
$content  = "";
$category = "General";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title    = trim($_POST["title"] ?? "");
    $content  = trim($_POST["content"] ?? "");
    $category = $_POST["category"] ?? "General";

    if (!in_array($category, $categories, true)) {
        $category = "General";
    }

    if ($title === "" || $content === "") {
        $error = "Please fill in both the title and the message.";
    } elseif (strlen($title) > 150) {
        $error = "The title is too long. Keep it under 150 characters.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO notices (title, content, category, posted_by) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $title, $content, $category, $_SESSION["user_id"]);

        if ($stmt->execute()) {
            header("Location: /Campus_Connect/notices/index.php");
            exit;
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post notice | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/notices.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/tnotices.css">
</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="nt-main">
 <div class="nt-form-wrap">
        <a href="/Campus_Connect/notices/index.php" class="nt-back">
            <i class="ti ti-arrow-left"></i>Back to notices
        </a>

        <div class="nt-header">
            <div>
                <h1>Post a notice</h1>
                <p>Share an announcement with the campus</p>
            </div>
        </div>

        <form method="POST" class="nt-form">

            <?php if ($error): ?>
                <div class="nt-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <label for="title">Title</label>
            <input type="text" id="title" name="title" maxlength="150"
                   placeholder="Exam routine published"
                   value="<?php echo htmlspecialchars($title); ?>" required>

            <label for="category">Category</label>
            <select id="category" name="category">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? "selected" : ""; ?>>
                        <?php echo $cat; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="content">Message</label>
            <textarea id="content" name="content" rows="7"
                      placeholder="Write the full announcement here"
                      required><?php echo htmlspecialchars($content); ?></textarea>

            <div class="nt-form-actions">
                <a href="/Campus_Connect/notices/index.php" class="nt-cancel">Cancel</a>
                <button type="submit" class="nt-submit">Post notice</button>
            </div>

        </form>
</div>
    </main>
</div>

</body>
</html>