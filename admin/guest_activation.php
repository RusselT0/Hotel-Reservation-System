<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';
$user = authRequireRoles($pdo, ['Admin'], '../pages/login.php', '../pages/auth_account.php');
require_once __DIR__ . '/../includes/admin_guest_activation.php';

header('Cache-Control: no-store');
$isReset = ($_GET['mode'] ?? '') === 'reset';
$purpose = $isReset ? 'Password Reset' : 'Activation';
$pageTitle = $isReset ? 'Guest password recovery' : 'Guest activation';
$codeLabel = $isReset ? 'Password reset code' : 'Activation code';
$pageUrl = $isReset ? 'guest_activation.php?mode=reset' : 'guest_activation.php';
$adminSection = $isReset ? 'guest_password_recovery' : 'guest_activation';
$flashKey = $isReset ? 'guest_password_recovery_feedback' : 'guest_activation_feedback';
$feedback = $_SESSION[$flashKey] ?? null;
unset($_SESSION[$flashKey]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && authPost('action') === 'issue') {
    $guestID = filter_input(INPUT_POST, 'guest_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    try {
        if (!validAuthToken() || !$guestID) throw new RuntimeException('Invalid request. Please try again.');
        if (authPost('identity_verified') !== '1') throw new RuntimeException('Independently verify guest identity before issuing this code.');
        $code = adminIssueGuestToken($pdo, $guestID, $user['user_id'], $purpose);
        $_SESSION[$flashKey] = ['message' => $codeLabel . ' issued. Share it privately only with the verified guest. It expires in 30 minutes and is shown only once.', 'code' => $code];
    } catch (Throwable $e) {
        $_SESSION[$flashKey] = ['message' => $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Unable to issue the code. Please try again.'];
        // Never log raw codes, token hashes, contact information or SQL parameter values.
        if (!($e instanceof RuntimeException) || $e instanceof PDOException) error_log('Guest activation issuance failed.');
    }
    header('Location: ' . $pageUrl, true, 303);
    exit;
}

$search = $_SERVER['REQUEST_METHOD'] === 'POST' && authPost('action') === 'search' ? trim(authPost('email')) : '';
$guests = [];
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validAuthToken()) {
        $feedback = ['message' => 'Invalid request. Please try again.'];
    } elseif ($search !== '' && (strlen($search) > 190 || !filter_var($search, FILTER_VALIDATE_EMAIL))) {
        $feedback = ['message' => 'Enter a valid guest email address.'];
    } else {
        $guests = adminGuestTokenCandidates($pdo, $search, $purpose);
    }
} catch (Throwable $e) {
    error_log('Could not load guest activation list.');
    $feedback = ['message' => 'Unable to load eligible guests. Please try again.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= authEscape($pageTitle) ?> | Bella Vista Hotel</title>
    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=3">
    <link rel="stylesheet" href="../assets/css/admin_reservations.css?v=2">
    <link rel="stylesheet" href="../assets/css/admin_guest_activation.css?v=1">
    <script src="../assets/js/admin_guest_activation.js?v=1" defer></script>
</head>
<body class="admin-page">
    <a class="admin-skip" href="#admin-main">Skip to main content</a>
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-workspace">
        <header class="admin-topbar"><div><p class="admin-breadcrumb">Management / <?= authEscape($pageTitle) ?></p><p class="admin-topbar-title">Administration</p></div><a class="admin-profile" href="../pages/auth_account.php"><span class="admin-profile-role">Admin</span><span class="admin-profile-name"><?= authEscape(trim($user['first_name'].' '.$user['last_name'])) ?></span></a></header>
        <main class="admin-main" id="admin-main">
            <section class="admin-welcome"><div><p class="admin-eyebrow">Account management</p><h1><?= authEscape($pageTitle) ?></h1><p><?= $isReset ? 'Issue a one-time recovery code for an independently verified guest who already has a password.' : 'Issue a one-time code for an independently verified guest who previously booked without a password.' ?></p></div><span class="admin-workspace-badge">Admin assisted</span></section>
            <section class="reservation-panel admin-section" aria-labelledby="eligible-guests">
                <div class="admin-section-heading"><h2 id="eligible-guests">Eligible guest records</h2><span class="admin-subtle">Up to 50 matching records</span></div>
                <p class="activation-guidance">Verify identity in person using appropriate identification and hotel records. Email, name or booking number alone is not proof of ownership. Never issue a code to an unverified requester.</p>
                <?php if ($isReset): ?><p class="activation-guidance">Password reset redemption is still being developed. Do not issue codes to real guests until it is available.</p><?php endif; ?>
                <form method="POST" action="<?= authEscape($pageUrl) ?>" class="activation-search" novalidate>
                    <input type="hidden" name="action" value="search"><input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>">
                    <label>Guest email<input type="email" name="email" maxlength="190" value="<?= authEscape($search) ?>" placeholder="Search exact email"></label>
                    <button class="reservation-button" type="submit">Search</button><a href="<?= authEscape($pageUrl) ?>" class="reservation-secondary">Reset</a>
                </form>
                <div class="reservation-table-wrap" tabindex="0" aria-label="Eligible guests; scroll horizontally if needed">
                    <table class="reservation-table activation-table"><thead><tr><th scope="col">Guest</th><th scope="col">Reservations</th><th scope="col">Code status</th><th scope="col">Issue code</th></tr></thead><tbody>
                    <?php foreach ($guests as $guest): ?>
                        <tr><td><strong><?= authEscape($guest['first_Name'].' '.$guest['last_Name']) ?></strong><span><?= authEscape($guest['email_Address']) ?></span><small>User #<?= (int)$guest['user_ID'] ?></small></td><td><?= (int)$guest['booking_count'] ?></td><td><?= $guest['has_active_token'] ? 'Code active' : 'No active code' ?></td><td>
                            <form method="POST" action="<?= authEscape($pageUrl) ?>" class="activation-issue" novalidate>
                                <input type="hidden" name="action" value="issue">
                                <input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>"><input type="hidden" name="guest_id" value="<?= (int)$guest['user_ID'] ?>">
                                <label><input type="checkbox" name="identity_verified" value="1"> Identity independently verified</label>
                                <button class="reservation-button" type="submit"><?= $guest['has_active_token'] ? 'Replace code' : 'Issue code' ?></button>
                            </form>
                        </td></tr>
                    <?php endforeach; ?>
                    <?php if (!$guests): ?><tr><td colspan="4" class="reservation-empty">No eligible guests match this search.</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </section>
        </main><footer class="admin-footer">Bella Vista Hotel &amp; Events Place &middot; Administration</footer>
    </div>
    <?php if ($feedback): ?>
        <dialog id="activationFeedback" class="admin-feedback-modal" aria-labelledby="activation-feedback-title" aria-describedby="activation-feedback-message">
            <h2 id="activation-feedback-title"><?= authEscape(isset($feedback['code']) ? $codeLabel . ' issued' : $pageTitle) ?></h2>
            <p id="activation-feedback-message"><?= authEscape($feedback['message']) ?></p>
            <?php if (isset($feedback['code'])): ?><label for="issued-code">One-time code</label><input id="issued-code" class="activation-code" type="text" readonly value="<?= authEscape($feedback['code']) ?>" autocomplete="off" spellcheck="false"><?php endif; ?>
            <form method="dialog"><button type="submit">Okay</button></form>
        </dialog>
    <?php endif; ?>
</body>
</html>
