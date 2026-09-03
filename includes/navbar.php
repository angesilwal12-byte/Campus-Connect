<nav class="nav">
    <a href="/Campus_Connect/index.php">Campus Connect</a>
    <?php if (isset($_SESSION["user_id"])): ?>
        <span><?php echo htmlspecialchars($_SESSION["full_name"]); ?> (<?php echo htmlspecialchars($_SESSION["role"]); ?>)</span>
        <a href="/Campus_Connect/notes/index.php">Notes</a>
        <a href="/Campus_Connect/notices/index.php">Notices</a>
        <a href="/Campus_Connect/auth/logout.php">Logout</a>
    <?php else: ?>
        <div>
        <a href="/Campus_Connect/auth/login.php">Login</a>
        <a href="/Campus_Connect/auth/register.php">Register</a>
        </div>
    <?php endif; ?>
</nav>