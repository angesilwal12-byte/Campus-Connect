<?php
require_once __DIR__ . "/../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $pass  = $_POST["password"] ?? "";
    $role  = $_POST["role"] ?? "student";

    if (!in_array($role, ["student", "teacher"], true)) {
        $role = "student";
    }

    if ($name === "" || $email === "" || $pass === "") {
        $error = "All fields are required.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hash, $role);

        if ($stmt->execute()) {
            header("Location: /Campus_Connect/auth/login.php");
            exit;
        } else {
            $error = "Email already registered.";
        }
    }
}

include __DIR__ . "/../includes/header.php";
include __DIR__ . "/../includes/navbar.php";
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <h1>Register</h1>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

    <form method="post">
        <input type="text" name="full_name" placeholder="Full name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role">
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
        </select>
        <button type="submit">Register</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="login.php">Login</a></p>
  </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>