<?php
$cc_role = $_SESSION["role"] ?? "student";
$cc_name = $_SESSION["full_name"] ?? "User";
$cc_uri  = $_SERVER["REQUEST_URI"];

$cc_parts = preg_split('/\s+/', trim($cc_name));
$cc_initials = strtoupper(substr($cc_parts[0], 0, 1));
if (count($cc_parts) > 1) {
    $cc_initials .= strtoupper(substr(end($cc_parts), 0, 1));
}

function cc_active($needle) {
    global $cc_uri;
    return strpos($cc_uri, $needle) !== false ? "active" : "";
}
?>
<aside class="cc-sidebar">

    <div class="cc-brand">
        <i class="ti ti-school"></i>
        <span>Campus Connect</span>
    </div>

    <nav class="cc-nav">
        <a href="/Campus_Connect/<?php echo $cc_role; ?>/dashboard.php" class="<?php echo cc_active('/dashboard.php'); ?>">
            <i class="ti ti-home"></i>Home
        </a>
        <a href="/Campus_Connect/notes/index.php" class="<?php echo cc_active('/notes/'); ?>">
            <i class="ti ti-notebook"></i>Notes
        </a>
        <a href="/Campus_Connect/notices/index.php" class="<?php echo cc_active('/notices/'); ?>">
            <i class="ti ti-bell"></i>Notices
        </a>
        <?php if ($cc_role === "student"): ?>
            <a href="/Campus_Connect/student/cr_election.php" class="<?php echo cc_active('cr_election'); ?>">
                <i class="ti ti-checkbox"></i>Vote
            </a>
        <?php endif; ?>
    </nav>

    <div class="cc-user">
        <div class="cc-user-row">
            <div class="cc-avatar"><?php echo htmlspecialchars($cc_initials); ?></div>
            <span><?php echo htmlspecialchars($cc_parts[0]); ?></span>
        </div>
        <a href="/Campus_Connect/auth/logout.php" class="cc-logout">
            <i class="ti ti-logout"></i>Logout
        </a>
    </div>

</aside>
