<?php
require_once __DIR__ . "/../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass  = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($pass, $user["password"])) {
        $_SESSION["user_id"]   = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"]      = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: /Campus_Connect/admin/dashboard.php");
        } elseif ($user["role"] === "teacher") {
            header("Location: /Campus_Connect/teacher/dashboard.php");
        } else {
            header("Location: /Campus_Connect/student/dashboard.php");
        }
        exit;
    }
    $error = "Invalid email or password.";
}

include __DIR__ . "/../includes/header.php";
include __DIR__ . "/../includes/navbar.php";
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <h1>Login</h1>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <p class="auth-footer">Don't have an account? <a href="register.php">Register</a></p>
  </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>