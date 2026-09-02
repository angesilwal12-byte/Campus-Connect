<?php

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../config/db.php";

// Make sure only students can access this page
if ($_SESSION["role"] !== "student") {
    header("Location: /Campus_Connect/index.php");
    exit;
}

// Temporary fallback until we confirm your database column
$studentName = $_SESSION["name"] ?? "Student";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | Campus Connect</title>

    <link rel="stylesheet" href="/Campus_Connect/assets/css/style.css">

</head>

<body>

    <?php require_once __DIR__ . "/../includes/navbar.php"; ?>


    <main class="student-dashboard">

        <!-- =========================
             WELCOME
        ========================== -->

        <section class="dashboard-welcome">

            <div>

                <p class="welcome-label">
                    STUDENT DASHBOARD
                </p>

                <h1>
                    Welcome back,
                    <?php echo htmlspecialchars($studentName); ?> 👋
                </h1>

                <p class="welcome-text">
                    Stay updated with your classes, notes and important campus notices.
                </p>

            </div>

        </section>


        <!-- =========================
             STATISTICS
        ========================== -->

        <section class="dashboard-stats">

            <div class="stat-card">

                <div class="stat-icon">
                    📚
                </div>

                <div>

                    <p class="stat-label">
                        NOTES
                    </p>

                    <h2>
                        0
                    </h2>

                    <p class="stat-description">
                        Available resources
                    </p>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📢
                </div>

                <div>

                    <p class="stat-label">
                        NOTICES
                    </p>

                    <h2>
                        0
                    </h2>

                    <p class="stat-description">
                        Campus announcements
                    </p>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👤
                </div>

                <div>

                    <p class="stat-label">
                        PROFILE
                    </p>

                    <h2>
                        ✓
                    </h2>

                    <p class="stat-description">
                        Account active
                    </p>

                </div>

            </div>

        </section>


        <!-- =========================
             QUICK ACTIONS
        ========================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <p class="section-label">
                        QUICK ACCESS
                    </p>

                    <h2>
                        What do you want to do?
                    </h2>

                </div>

            </div>


            <div class="action-grid">


                <a href="/Campus_Connect/notes/" class="action-card">

                    <div class="action-icon">
                        📚
                    </div>

                    <div class="action-content">

                        <h3>
                            Browse Notes
                        </h3>

                        <p>
                            Find study materials uploaded by teachers.
                        </p>

                    </div>

                    <span class="action-arrow">
                        →
                    </span>

                </a>


                <a href="/Campus_Connect/notices/" class="action-card">

                    <div class="action-icon">
                        📢
                    </div>

                    <div class="action-content">

                        <h3>
                            View Notices
                        </h3>

                        <p>
                            Check important announcements from campus.
                        </p>

                    </div>

                    <span class="action-arrow">
                        →
                    </span>

                </a>


                <a href="#" class="action-card">

                    <div class="action-icon">
                        👤
                    </div>

                    <div class="action-content">

                        <h3>
                            My Profile
                        </h3>

                        <p>
                            View and manage your student information.
                        </p>

                    </div>

                    <span class="action-arrow">
                        →
                    </span>

                </a>


            </div>

        </section>


        <!-- =========================
             LOWER CONTENT
        ========================== -->

        <section class="dashboard-columns">


            <!-- RECENT NOTICES -->

            <div class="dashboard-panel">

                <div class="panel-header">

                    <div>

                        <p class="section-label">
                            CAMPUS UPDATES
                        </p>

                        <h2>
                            Recent Notices
                        </h2>

                    </div>

                    <a href="/Campus_Connect/notices/">
                        View all →
                    </a>

                </div>


                <div class="empty-state">

                    <div class="empty-icon">
                        📢
                    </div>

                    <h3>
                        No notices yet
                    </h3>

                    <p>
                        New campus announcements will appear here.
                    </p>

                </div>

            </div>


            <!-- RECENT NOTES -->

            <div class="dashboard-panel">

                <div class="panel-header">

                    <div>

                        <p class="section-label">
                            STUDY MATERIAL
                        </p>

                        <h2>
                            Recent Notes
                        </h2>

                    </div>

                    <a href="/Campus_Connect/notes/">
                        View all →
                    </a>

                </div>


                <div class="empty-state">

                    <div class="empty-icon">
                        📚
                    </div>

                    <h3>
                        No notes yet
                    </h3>

                    <p>
                        Notes uploaded by teachers will appear here.
                    </p>

                </div>

            </div>


        </section>


    </main>


    <?php require_once __DIR__ . "/../includes/footer.php"; ?>

</body>

</html>