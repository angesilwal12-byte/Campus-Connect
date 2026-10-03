<?php $role = $_SESSION["role"] ?? "student"; ?>
<aside class="sidebar">
    <nav>
        <a href="/Campus_Connect/<?php echo $role; ?>/dashboard.php">Dashboard</a>
        <a href="/Campus_Connect/notes/index.php">Notes</a>
        <a href="/Campus_Connect/notices/index.php">Notices</a>

        <?php if ($role === "student"): ?>
            <a href="/Campus_Connect/student/cr_election.php">CR Voting</a>
        <?php endif; ?>

        <a href="/Campus_Connect/auth/logout.php">Logout</a>
    </nav>
</aside>