<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/guest_activation.php';

$isReset = defined('BELLA_VISTA_PASSWORD_RESET');
$purpose = $isReset ? 'Password Reset' : 'Activation';
$heading = $isReset ? 'Reset Guest Password' : 'Activate Guest Account';
$action = $isReset ? 'reset_password.php' : 'activate.php';
$attemptKey = $isReset ? 'reset_attempts' : 'activation_attempts';
$codeLabel = $isReset ? 'Password reset code' : 'Activation code';

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

if (authCurrentUser($pdo)) {
    $_SESSION['auth_guard_error'] = 'Sign out before continuing with guest account recovery or activation.';
    header('Location: auth_account.php', true, 303);
    exit;
}

$authError = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(authPost('email')));
    $code = strtolower(trim(authPost('activation_code')));
    $password = authPost('password');
    $confirm = authPost('confirm_password');

    // Basic session-based throttling; stronger shared throttling is a separate security milestone.
    $now = time();
    $attempts = $_SESSION[$attemptKey] ?? [];
    $attempts = array_values(array_filter($attempts, static fn($t) => is_int($t) && $t > $now - 900));
    $_SESSION[$attemptKey] = $attempts;

    if (!validAuthToken()) {
        $authError = 'Invalid request. Please try again.';
    } elseif (count($attempts) >= 5) {
        $authError = 'Too many attempts. Please wait 15 minutes and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $authError = 'Please enter a valid email address.';
    } elseif (!preg_match('/\A[0-9a-f]{64}\z/', $code)) {
        $authError = 'Enter the complete code provided by the hotel.';
    } elseif (strlen($password) < 12 || strlen($password) > 72 || strpos($password, "\0") !== false) {
        $authError = 'Your password must contain at least 12 characters and not exceed 72 bytes.';
    } elseif ($password !== $confirm) {
        $authError = 'Passwords do not match.';
    } else {
        $_SESSION[$attemptKey][] = $now;
        try {
            if (redeemGuestActivation($pdo, $email, $code, $password, $purpose)) {
                unset($_SESSION[$attemptKey]);
                $_SESSION['auth_success'] = $isReset ? 'Your password has been reset. Sign in with your new password.' : 'Your guest account has been activated. Sign in with your new password.';
                header('Location: login.php', true, 303);
                exit;
            }
            $authError = 'The code or account details are invalid or expired. Contact the hotel for assistance.';
        } catch (Throwable $e) {
            error_log('Guest account token redemption failed.');
            $authError = 'Unable to complete your request. Please try again or contact the hotel.';
        }
    }
    unset($code, $password, $confirm);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= authEscape($heading) ?> | Bella Vista Hotel</title>
    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/login.css?v=6">
    <link rel="stylesheet" href="../assets/css/responsive_landing.css?v=19">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-visual">
            <div class="auth-overlay"></div>
            <div class="auth-visual-content"><span class="auth-eyebrow">WELCOME TO BELLA VISTA</span><h1>Your Peaceful<br>Sanctuary Awaits.</h1><p><?= $isReset ? 'Recover access to your guest account securely.' : 'Activate your guest account to keep your existing reservation history.' ?></p><div class="auth-accent-line"></div><span class="auth-visual-caption">Bella Vista Hotel &amp; Events Place</span></div>
        </section>
        <section class="auth-panel">
            <header class="auth-header"><a href="../index.php" class="auth-back-link">&#8592; Back to Home</a></header>
            <div class="auth-form-wrapper">
                <div class="auth-brand"><img src="../assets/images/logo-removebg.png" alt="Bella Vista Hotel Logo" class="auth-logo"><h2>Bella Vista Hotel</h2><span>&amp; Events Place</span></div>
                <div class="auth-heading"><h1><?= authEscape($heading) ?></h1><p><?= $isReset ? 'Contact the hotel to verify your identity and receive a private password reset code.' : 'Previously booked without an account? Obtain a private activation code from the hotel after identity verification.' ?></p></div>
                <form id="activateForm" class="auth-form" method="POST" action="<?= authEscape($action) ?>" novalidate>
                    <input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>">
                    <div class="auth-field"><label for="email">Booking email address</label><input type="email" id="email" name="email" maxlength="190" autocomplete="email" value="<?= authEscape($email) ?>" required></div>
                    <div class="auth-field"><label for="activation_code"><?= authEscape($codeLabel) ?></label><input type="text" id="activation_code" name="activation_code" maxlength="64" pattern="[0-9a-fA-F]{64}" autocomplete="off" spellcheck="false" required></div>
                    <div class="auth-field"><label for="password">New password (at least 12 characters)</label><div class="auth-password-wrapper"><input type="password" id="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required><button type="button" id="togglePassword" class="auth-toggle-password" aria-label="Show password" aria-pressed="false">Show</button></div></div>
                    <div class="auth-field"><label for="confirm_password">Confirm password</label><input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="72" required></div>
                    <button type="submit" class="auth-submit-btn"><?= $isReset ? 'Reset password' : 'Activate account' ?></button>
                </form>
                <div class="auth-register"><p><a href="login.php" class="auth-text-btn">Back to Sign In</a></p><p>New guest? <a href="register.php" class="auth-text-btn">Register</a></p></div>
            </div>
            <footer class="auth-footer"><p>&copy; 2026 Bella Vista Hotel &amp; Events Place. All rights reserved.</p></footer>
        </section>
    </main>
    <dialog id="loginFeedbackModal" class="auth-feedback-modal" data-auto-open="<?= $authError ? 'true' : 'false' ?>" aria-labelledby="authModalTitle" aria-describedby="authModalMessage">
        <div class="auth-modal-content"><div class="auth-modal-icon" aria-hidden="true">!</div><h2 id="authModalTitle"><?= $isReset ? 'Password Reset Error' : 'Activation Error' ?></h2><p id="authModalMessage"><?= authEscape($authError) ?></p><form method="dialog"><button type="submit" class="auth-submit-btn">Okay</button></form></div>
    </dialog>
    <script src="../assets/js/login.js?v=3" defer></script>
</body>
</html>
