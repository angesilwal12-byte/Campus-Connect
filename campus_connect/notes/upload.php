<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// only teachers and admins can upload
if (!in_array($_SESSION["role"], ["teacher", "admin"], true)) {
    header("Location: /Campus_Connect/notes/index.php");
    exit;
}

// edit this list to match your subjects
$subjects = [
    "Software Engineering",
    "Numerical Methods",
    "Web Technology (PHP)",
    "XML",
    "Other"
];

$allowed  = ["pdf", "doc", "docx", "ppt", "pptx"];
$max_size = 10 * 1024 * 1024; // 10 MB

$error   = "";
$title   = "";
$subject = $subjects[0];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title   = trim($_POST["title"] ?? "");
    $subject = $_POST["subject"] ?? "";
    $file    = $_FILES["file"] ?? null;

    if ($title === "") {
        $error = "Please enter a title for the notes.";
    } elseif (strlen($title) > 150) {
        $error = "The title is too long. Keep it under 150 characters.";
    } elseif (!in_array($subject, $subjects, true)) {
        $error = "Please choose a subject from the list.";
        $subject = $subjects[0];
    } elseif (!$file || $file["error"] === UPLOAD_ERR_NO_FILE) {
        $error = "Please choose a file to upload.";
    } elseif ($file["error"] !== UPLOAD_ERR_OK) {
        $error = "The upload failed. The file may be too large.";
    } else {
        $original = basename($file["name"]);
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            $error = "Only PDF, Word and PowerPoint files are allowed.";
        } elseif ($file["size"] > $max_size) {
            $error = "The file is too big. The limit is 10 MB.";
        } else {
            // save under a random name so nothing can be overwritten or guessed
            $saved_name = bin2hex(random_bytes(16)) . "." . $ext;
            $folder     = __DIR__ . "/../uploads/notes/";

            if (move_uploaded_file($file["tmp_name"], $folder . $saved_name)) {
                $original = mb_substr($original, 0, 255);

                $stmt = $conn->prepare(
                    "INSERT INTO notes (title, subject, file_name, original_name, uploaded_by) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssssi", $title, $subject, $saved_name, $original, $_SESSION["user_id"]);
                $stmt->execute();

                header("Location: /Campus_Connect/notes/index.php");
                exit;
            } else {
                $error = "Could not save the file. Check that the uploads/notes folder exists.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload notes | Campus Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/sidebar.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/notes.css">
    <link rel="stylesheet" href="/Campus_Connect/assets/css/theme.css">

</head>
<body>

<div class="cc-layout">
    <?php require_once __DIR__ . "/../includes/sidebar.php"; ?>

    <main class="nb-main">
        <div class="nb-wrap narrow">

            <a href="/Campus_Connect/notes/index.php" class="nb-back">
                <i class="ti ti-arrow-left"></i>Back to notes
            </a>

            <div class="nb-header">
                <div>
                    <h1>Upload notes</h1>
                    <p>Share study material with students</p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="nb-form">

                <?php if ($error): ?>
                    <div class="nb-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <label for="title">Title</label>
                <input type="text" id="title" name="title" maxlength="150"
                       placeholder="Unit 3 notes"
                       value="<?php echo htmlspecialchars($title); ?>" required>

                <label for="subject">Subject</label>
                <select id="subject" name="subject">
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $subject === $s ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="file">File</label>
                <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx" required>
                <p class="nb-hint">PDF, Word or PowerPoint, up to 10 MB.</p>

                <div class="nb-form-actions">
                    <a href="/Campus_Connect/notes/index.php" class="nb-cancel">Cancel</a>
                    <button type="submit" class="nb-submit">Upload</button>
                </div>

            </form>

        </div>
    </main>
</div>

</body>
</html>