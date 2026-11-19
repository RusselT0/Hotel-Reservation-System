<?php
// Include only after the Admin guard succeeds.
if (!isset($user, $adminSection) || $user['role'] !== 'Admin') {
    http_response_code(404);
    exit;
}

$adminModules = [
    ['id' => 'reservations', 'label' => 'Reservations', 'href' => 'reservations.php', 'description' => 'Review incoming requests and view reservation details.'],
    ['id' => 'rooms', 'label' => 'Rooms & Facilities', 'href' => null, 'description' => 'Manage inventory, availability and room information.'],
    ['id' => 'housekeeping', 'label' => 'Housekeeping', 'href' => null, 'description' => 'Coordinate cleaning assignments and room readiness.'],
    ['id' => 'users', 'label' => 'Users', 'href' => null, 'description' => 'Manage guest and staff accounts.'],
    ['id' => 'reports', 'label' => 'Reports', 'href' => null, 'description' => 'Review booking activity and verified revenue.']
];
?>
<aside class="admin-sidebar">
    <a class="admin-brand" href="dashboard.php">
        <img src="../assets/images/logo-removebg.png" alt="Bella Vista Hotel logo">
        <span>Bella Vista Hotel<small>&amp; Events Place</small></span>
    </a>
    <div class="admin-sidebar-label">Management</div>
    <nav class="admin-menu" aria-label="Admin navigation">
        <a class="admin-menu-item<?= $adminSection === 'overview' ? ' is-active' : '' ?>" href="dashboard.php" <?= $adminSection === 'overview' ? 'aria-current="page"' : '' ?>>
            <span>Overview</span><?php if ($adminSection === 'overview'): ?><span class="admin-menu-indicator" aria-hidden="true"></span><?php endif; ?>
        </a>
        <?php foreach ($adminModules as $module): ?>
            <?php if ($module['href']): ?>
                <a class="admin-menu-item<?= $adminSection === $module['id'] ? ' is-active' : '' ?>" href="<?= authEscape($module['href']) ?>" <?= $adminSection === $module['id'] ? 'aria-current="page"' : '' ?>>
                    <span><?= authEscape($module['label']) ?></span><?php if ($adminSection === $module['id']): ?><span class="admin-menu-indicator" aria-hidden="true"></span><?php endif; ?>
                </a>
            <?php else: ?>
                <span class="admin-menu-item is-planned" aria-disabled="true"><span><?= authEscape($module['label']) ?></span><small>Planned</small></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-bottom">
        <div class="admin-sidebar-label">Account</div>
        <a class="admin-menu-item" href="../pages/auth_account.php">My account</a>
        <a class="admin-menu-item" href="../index.php">Hotel website</a>
        <form method="POST" action="../pages/auth_account.php">
            <input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>">
            <button type="submit" class="admin-signout">Sign out</button>
        </form>
    </div>
</aside>
