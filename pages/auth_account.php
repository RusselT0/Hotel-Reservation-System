<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';
$user = authRequireRoles($pdo, ['Admin', 'Housekeeping', 'Guest'], 'login.php');

$guardError = $_SESSION['auth_guard_error'] ?? '';
unset($_SESSION['auth_guard_error']);

// Handle logout
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    validAuthToken()
) {

    $_SESSION = [];

    session_regenerate_id(true);

    header('Location: login.php', true, 303);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Account | Bella Vista Hotel</title>

    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/login.css?v=6">

</head>

<body>

    <h1>
        Welcome, <?= authEscape($user['first_name']) ?>!
    </h1>

    <p>
        User ID: <?= (int)$user['user_id'] ?>
    </p>

    <p>
        Email: <?= authEscape($user['email']) ?>
    </p>

    <p>
        Role: <?= authEscape($user['role']) ?>
    </p>

    <p><a href="../index.php">Back to Home</a></p>
    <p><a href="../<?= authEscape(authDashboardPath($user['role'])) ?>">Go to Dashboard</a></p>

    <form method="POST">

        <input
            type="hidden"
            name="auth_token"
            value="<?= authEscape($_SESSION['auth_token']) ?>"
        >

        <button type="submit">
            Sign Out
        </button>

    </form>

    <?php if ($guardError): ?>
        <dialog id="accountFeedbackModal" class="auth-feedback-modal"
                aria-labelledby="accountModalTitle" aria-describedby="accountModalMessage">
            <div class="auth-modal-content">
                <div class="auth-modal-icon" aria-hidden="true">!</div>
                <h2 id="accountModalTitle">Access Denied</h2>
                <p id="accountModalMessage"><?= authEscape($guardError) ?></p>
                <form method="dialog">
                    <button type="submit" class="auth-submit-btn">Okay</button>
                </form>
            </div>
        </dialog>
        <script src="../assets/js/auth_guard_feedback.js?v=1" defer></script>
    <?php endif; ?>

</body>
</html>
