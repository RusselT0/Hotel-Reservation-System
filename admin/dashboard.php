<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';

$user = authRequireRoles($pdo, ['Admin'], '../pages/login.php', '../pages/auth_account.php');
require_once __DIR__ . '/../includes/admin_dashboard_stats.php';

$stats = null;
$statsError = '';

try {
    $stats = adminDashboardStats($pdo);
} catch (Throwable $e) {
    error_log('Admin overview: ' . $e->getMessage());
    $statsError = 'Unable to load dashboard statistics right now. Please refresh to try again.';
}

$adminMetrics = [
    ['label' => 'Total reservations', 'value' => $stats ? number_format((int)$stats['total_reservations']) : null,
     'description' => 'All non-deleted bookings'],
    ['label' => 'Pending approval', 'value' => $stats ? number_format((int)$stats['pending_approval']) : null,
     'description' => 'Awaiting admin review'],
    ['label' => 'Rooms & facilities ready', 'value' => $stats ? number_format((int)$stats['ready_rooms']) : null,
     'description' => 'Ready status, not date availability'],
    ['label' => 'Verified revenue', 'value' => $stats ? '₱' . number_format((float)$stats['verified_revenue'], 2) : null,
     'description' => 'Verified receipts less refunds · all time']
];

$adminSection = 'overview';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Bella Vista Hotel</title>
    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=3">
</head>
<body class="admin-page">
    <a class="admin-skip" href="#admin-main">Skip to main content</a>

    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="admin-workspace">
        <header class="admin-topbar">
            <div>
                <p class="admin-breadcrumb">Management / Overview</p>
                <p class="admin-topbar-title">Administration</p>
            </div>
            <a class="admin-profile" href="../pages/auth_account.php">
                <span class="admin-profile-role">Admin</span>
                <span class="admin-profile-name"><?= authEscape(trim($user['first_name'] . ' ' . $user['last_name'])) ?></span>
            </a>
        </header>

        <main class="admin-main" id="admin-main">
            <section class="admin-welcome" aria-labelledby="admin-title">
                <div>
                    <p class="admin-eyebrow">Bella Vista Hotel &amp; Events Place</p>
                    <h1 id="admin-title">Dashboard overview</h1>
                    <p>Welcome, <?= authEscape($user['first_name']) ?>. Manage your hotel operations from one workspace.</p>
                </div>
                <span class="admin-workspace-badge">Admin workspace</span>
            </section>

            <section class="admin-section" aria-labelledby="overview-heading">
                <div class="admin-section-heading">
                    <h2 id="overview-heading">Operational overview</h2>
                    <span class="admin-subtle"><?= $stats ? 'Current database snapshot' : 'Statistics unavailable' ?></span>
                </div>
                <div class="admin-metric-grid">
                    <?php foreach ($adminMetrics as $metric): ?>
                        <article class="admin-metric">
                            <h3><?= authEscape($metric['label']) ?></h3>
                            <p class="admin-metric-value" <?= $metric['value'] === null ? 'aria-label="Unavailable"' : '' ?>><?= authEscape($metric['value'] ?? '—') ?></p>
                            <p><?= authEscape($metric['description']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-section" aria-labelledby="modules-heading">
                <div class="admin-section-heading">
                    <h2 id="modules-heading">Management modules</h2>
                    <span class="admin-subtle">Available and planned modules</span>
                </div>
                <div class="admin-module-grid">
                    <?php foreach ($adminModules as $module): ?>
                        <article class="admin-module">
                            <div class="admin-module-heading">
                                <h3><?= authEscape($module['label']) ?></h3>
                                <span class="admin-planned-badge"><?= $module['href'] ? 'View' : 'Planned' ?></span>
                            </div>
                            <p><?= authEscape($module['description']) ?></p>
                            <?php if ($module['href']): ?><a class="admin-module-link" href="<?= authEscape($module['href']) ?>">Open module &rarr;</a><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <footer class="admin-footer">Bella Vista Hotel &amp; Events Place &middot; Administration</footer>
    </div>
    <?php if ($statsError): ?>
        <dialog id="adminOverviewFeedback" class="admin-feedback-modal"
                aria-labelledby="adminFeedbackTitle" aria-describedby="adminFeedbackMessage">
            <h2 id="adminFeedbackTitle">Overview unavailable</h2>
            <p id="adminFeedbackMessage"><?= authEscape($statsError) ?></p>
            <form method="dialog"><button type="submit">Okay</button></form>
        </dialog>
        <script src="../assets/js/admin_dashboard.js?v=1" defer></script>
    <?php endif; ?>
</body>
</html>
