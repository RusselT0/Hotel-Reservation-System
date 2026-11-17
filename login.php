
<?php

require_once __DIR__ . '/auth_session.php';

$authError = '';
$authSuccess = $_SESSION['auth_success'] ?? '';

unset($_SESSION['auth_success']);

$email = '';

// Process login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim(authPost('email'));
    $password = authPost('password');

    // ========================================
    // SERVER-SIDE VALIDATION
    // ========================================

    if (!validAuthToken()) {

        $authError = "Invalid request. Please try again.";

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190
    ) {

        $authError = "Please enter a valid email address.";

    } elseif ($password === '') {

        $authError = "Please enter your password.";

    } else {

        try {

            require_once __DIR__ . '/database.php';

            // Retrieve the user, role, and account status
            $stmt = $pdo->prepare("
                SELECT
                    u.user_ID,
                    u.first_Name,
                    u.last_Name,
                    u.email_Address,
                    u.password_Hash,
                    ur.role_Name,
                    ast.account_Status_Name

                FROM Users u

                JOIN User_Role ur
                    ON u.role_ID = ur.role_ID

                JOIN Account_Status ast
                    ON u.account_Status_ID =
                       ast.account_Status_ID

                WHERE u.email_Address = :email
                  AND u.deleted_At IS NULL
                  AND ur.is_Active = 1
                  AND ast.is_Active = 1

                LIMIT 1
            ");

            $stmt->execute([
                'email' => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify the submitted credentials
            if (
                !$user ||
                empty($user['password_Hash']) ||
                !password_verify(
                    $password,
                    $user['password_Hash']
                )
            ) {

                $authError = "Invalid email address or password.";

            } elseif (
                $user['account_Status_Name'] !== 'Active'
            ) {

                $authError = "Your account is currently unavailable.";

            } elseif (
                !in_array(
                    $user['role_Name'],
                    ['Admin', 'Housekeeping', 'Guest'],
                    true
                )
            ) {

                $authError = "Your account role is invalid.";

            } else {

                // Prevent session fixation
                session_regenerate_id(true);

                // Store authenticated user information
                $_SESSION['auth_user'] = [
                    'user_id' => (int)$user['user_ID'],
                    'first_name' => $user['first_Name'],
                    'last_name' => $user['last_Name'],
                    'email' => $user['email_Address'],
                    'role' => $user['role_Name']
                ];

                // Generate a new security token
                $_SESSION['auth_token'] =
                    bin2hex(random_bytes(32));

                // Temporary destination
                header('Location: auth_account.php', true, 303);
                exit;

            }

        } catch (Throwable $e) {

            error_log(
                'Login error: ' . $e->getMessage()
            );

            $authError =
                "Unable to sign in. Please try again later.";

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Sign In | Bella Vista Hotel</title>

    <link rel="stylesheet" href="global_style.css">
    <link rel="stylesheet" href="login.css?v=5">
    <link rel="stylesheet" href="responsive_landing.css?v=19">

</head>

<body class="auth-page">

    <main class="auth-layout">

        <!-- LEFT SIDE - HOTEL BRANDING -->

        <section class="auth-visual">

            <div class="auth-overlay"></div>

            <div class="auth-visual-content">

                <span class="auth-eyebrow">
                    WELCOME TO BELLA VISTA
                </span>

                <h1>
                    Your Peaceful<br>
                    Sanctuary Awaits.
                </h1>

                <p>
                    Experience warm Filipino hospitality,
                    serene mountain views, and unforgettable stays.
                </p>

                <div class="auth-accent-line"></div>

                <span class="auth-visual-caption">
                    Bella Vista Hotel & Events Place
                </span>

            </div>

        </section>

        <!-- RIGHT SIDE - LOGIN FORM -->

        <section class="auth-panel">

            <header class="auth-header">

                <a href="index.php" class="auth-back-link">
                    &#8592; Back to Home
                </a>

            </header>

            <div class="auth-form-wrapper">

                <!-- HOTEL LOGO -->

                <div class="auth-brand">

                    <img
                        src="images/logo-removebg.png"
                        alt="Bella Vista Hotel Logo"
                        class="auth-logo"
                    >

                    <h2>Bella Vista Hotel</h2>

                    <span>& Events Place</span>

                </div>

                <!-- LOGIN HEADING -->

                <div class="auth-heading">

                    <h1>Welcome Back</h1>

                    <p>
                        Sign in to access your account.
                    </p>

                </div>

                <!-- LOGIN FORM -->

                <form
                    id="loginForm"
                    class="auth-form"
                    method="POST"
                    action="login.php"
                    novalidate
                >

                <input
                    type="hidden"
                    name="auth_token"
                    value="<?= authEscape($_SESSION['auth_token']) ?>"
                >

                    <div class="auth-field">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            autocomplete="username"
                            maxlength="190"
                            value="<?= authEscape($email) ?>"
                            required
                        >

                    </div>

                    <div class="auth-field">

                        <label for="password">
                            Password
                        </label>

                        <div class="auth-password-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                class="auth-toggle-password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                Show
                            </button>

                        </div>

                    </div>

                    <div class="auth-options">

                        <button
                            type="button"
                            id="forgotPassword"
                            class="auth-text-btn"
                        >
                            Forgot password?
                        </button>

                    </div>

                    <button
                        type="submit"
                        class="auth-submit-btn"
                    >
                        Sign In
                    </button>

                </form>

                <!-- REGISTER -->

                <div class="auth-register">

                    <p>
                        Don't have an account?

                        <a href="register.php" class="auth-text-btn">
                            Register
                        </a>

                    </p>

                </div>

            </div>

            <!-- MINIMAL FOOTER -->

            <footer class="auth-footer">

                <p>
                    &copy; 2026 Bella Vista Hotel & Events Place.
                    All rights reserved.
                </p>

            </footer>

        </section>

    </main>

    <!-- FEEDBACK MODAL -->

    <dialog
        id="loginFeedbackModal"
        class="auth-feedback-modal"
        aria-labelledby="authModalTitle"
        aria-describedby="authModalMessage"
    >

        <div class="auth-modal-content">

            <div class="auth-modal-icon <?= $authSuccess && !$authError
                ? 'is-success'
                : '' ?>">
                <?= $authSuccess && !$authError ? '✓' : '!' ?>
            </div>

            <h2 id="authModalTitle">
                <?= $authError ? 'Login Error' : 'Success' ?>
            </h2>

            <p id="authModalMessage">
                <?= authEscape($authError ?: $authSuccess) ?>
            </p>

            <form method="dialog">

                <button
                    type="submit"
                    class="auth-submit-btn"
                >
                    Okay
                </button>

            </form>

        </div>

    </dialog>

    <script src="login.js?v=2" defer></script>

</body>
</html>
