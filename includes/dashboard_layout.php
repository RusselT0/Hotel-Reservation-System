<?php
// This template is only rendered by a guarded dashboard entry page.
if (!isset($user, $dashboardTitle, $dashboardDescription, $dashboardSections)) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= authEscape($dashboardTitle) ?> | Bella Vista Hotel</title>
    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=1">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <a href="../index.php" class="dashboard-brand">
            <img src="../assets/images/logo-removebg.png" alt="Bella Vista Hotel logo">
            <span>Bella Vista Hotel <small>&amp; Events Place</small></span>
        </a>
        <nav class="dashboard-nav" aria-label="Dashboard navigation">
            <a href="../index.php">Home</a>
            <a href="../pages/auth_account.php">Account</a>
            <form method="POST" action="../pages/auth_account.php">
                <input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>">
                <button type="submit">Sign Out</button>
            </form>
        </nav>
    </header>

    <main class="dashboard-main">
        <section class="dashboard-intro">
            <p class="dashboard-role"><?= authEscape($user['role']) ?> workspace</p>
            <h1><?= authEscape($dashboardTitle) ?></h1>
            <h2>Welcome, <?= authEscape($user['first_name']) ?>!</h2>
            <p><?= authEscape($dashboardDescription) ?></p>
            <?php if ($user['role'] === 'Guest'): ?>
                <a class="dashboard-primary" href="../index.php#rooms">Explore rooms and facilities</a>
            <?php endif; ?>
        </section>

        <section aria-labelledby="dashboard-sections-heading">
            <h2 id="dashboard-sections-heading">Planned sections</h2>
            <div class="dashboard-grid">
                <?php foreach ($dashboardSections as $section): ?>
                    <article class="dashboard-card">
                        <h3><?= authEscape($section) ?></h3>
                        <p>Not implemented yet</p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <footer class="dashboard-footer">Bella Vista Hotel &amp; Events Place</footer>
</body>
</html>
