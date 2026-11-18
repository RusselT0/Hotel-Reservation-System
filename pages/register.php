<?php

require_once __DIR__ . '/../includes/auth_session.php';

$authError = '';
$authSuccess = '';

$firstName = '';
$lastName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim(authPost('first_name'));
    $lastName = trim(authPost('last_name'));
    $email = strtolower(trim(authPost('email')));
    $phone = trim(authPost('phone'));

    $password = authPost('password');
    $confirmPassword = authPost('confirm_password');

    // ========================================
    // VALIDATE REGISTRATION DETAILS
    // ========================================

    if (!validAuthToken()) {

        $authError = "Invalid request. Please try again.";

    } elseif (
        $firstName === '' ||
        $lastName === '' ||
        strlen($firstName) > 80 ||
        strlen($lastName) > 80
    ) {

        $authError = "Please enter a valid first and last name.";

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190
    ) {

        $authError = "Please enter a valid email address.";

    } elseif (
        !preg_match('/^\+?[0-9][0-9\s-]{6,24}$/', $phone)
    ) {

        $authError = "Please enter a valid phone number.";

    } elseif (
        strlen($password) < 12 ||
        strlen($password) > 72 ||
        strpos($password, "\0") !== false
    ) {

        $authError =
            "Your password must contain at least 12 characters " .
            "and must not exceed 72 bytes.";

    } elseif ($password !== $confirmPassword) {

        $authError = "Passwords do not match.";

    } else {

        try {

            require_once __DIR__ . '/../includes/database.php';

            // ========================================
            // CHECK EXISTING EMAIL
            // ========================================

            $stmt = $pdo->prepare("
                SELECT user_ID
                FROM Users
                WHERE email_Address = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $authError =
                    "This email cannot be registered. " .
                    "Please use another email or sign in.";

            } else {

                // Hash password before saving
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // ========================================
                // CREATE GUEST ACCOUNT
                // ========================================

                $stmt = $pdo->prepare("
                    INSERT INTO Users (
                        role_ID,
                        account_Status_ID,
                        first_Name,
                        last_Name,
                        email_Address,
                        phone_Number,
                        password_Hash
                    )
                    SELECT
                        ur.role_ID,
                        ast.account_Status_ID,
                        :first_name,
                        :last_name,
                        :email,
                        :phone,
                        :password_hash

                    FROM User_Role ur

                    CROSS JOIN Account_Status ast

                    WHERE ur.role_Name = 'Guest'
                      AND ur.is_Active = 1
                      AND ast.account_Status_Name = 'Active'
                      AND ast.is_Active = 1
                ");

                $stmt->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'password_hash' => $passwordHash
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Guest role configuration is missing.'
                    );
                }

                $_SESSION['auth_success'] =
                    "Your account has been created successfully. " .
                    "You may now sign in.";

                header('Location: login.php', true, 303);
                exit;

            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            if ((int)($e->errorInfo[1] ?? 0) === 1062) {

                $authError =
                    "This email cannot be registered.";

            } else {

                $authError =
                    "Unable to create your account. Please try again.";

            }

        } catch (Throwable $e) {

            error_log($e->getMessage());

            $authError =
                "Unable to create your account. Please try again.";

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

    <title>Register | Bella Vista Hotel</title>

    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/login.css?v=5">
    <link rel="stylesheet" href="../assets/css/responsive_landing.css?v=19">

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

                <a href="../index.php" class="auth-back-link">
                    &#8592; Back to Home
                </a>

            </header>

            <div class="auth-form-wrapper">

                <!-- HOTEL LOGO -->

                <div class="auth-brand">

                    <img
                        src="../assets/images/logo-removebg.png"
                        alt="Bella Vista Hotel Logo"
                        class="auth-logo"
                    >

                    <h2>Bella Vista Hotel</h2>

                    <span>& Events Place</span>

                </div>

                <!-- REGISTER HEADING -->
                <div class="auth-heading">

                    <h1>Create an Account</h1>

                    <p>Register to manage your reservations.</p>

                </div>

                <form
                    id="registerForm"
                    class="auth-form"
                    method="POST"
                    action="register.php"
                    novalidate
                >

                    <input
                        type="hidden"
                        name="auth_token"
                        value="<?= authEscape($_SESSION['auth_token']) ?>"
                    >

                    <div class="auth-fields-grid">

                        <div class="auth-field">

                            <label for="first_name">First Name</label>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                placeholder="First name"
                                autocomplete="given-name"
                                maxlength="80"
                                value="<?= authEscape($firstName) ?>"
                                required
                            >

                        </div>

                        <div class="auth-field">

                            <label for="last_name">Last Name</label>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                placeholder="Last name"
                                autocomplete="family-name"
                                maxlength="80"
                                value="<?= authEscape($lastName) ?>"
                                required
                            >

                        </div>

                    </div>

                    <div class="auth-field">

                        <label for="email">Email Address</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            maxlength="190"
                            value="<?= authEscape($email) ?>"
                            required
                        >

                    </div>

                    <div class="auth-field">

                        <label for="phone">Phone Number</label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="09XXXXXXXXX"
                            autocomplete="tel"
                            maxlength="25"
                            value="<?= authEscape($phone) ?>"
                            required
                        >

                    </div>

                    <div class="auth-field">

                        <label for="password">Password</label>

                        <div class="auth-password-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                autocomplete="new-password"
                                minlength="12"
                                maxlength="72"
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

                    <div class="auth-field">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            autocomplete="new-password"
                            minlength="12"
                            maxlength="72"
                            required
                        >

                    </div>

                    <button type="submit" class="auth-submit-btn">
                        Create Account
                    </button>

                </form>

                <div class="auth-register">

                    <p>
                        Already have an account?

                        <a href="login.php" class="auth-text-btn">
                            Sign In
                        </a>
                    </p>

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
                <?= $authError ? 'Registration Error' : 'Success' ?>
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

    <script src="../assets/js/login.js?v=2" defer></script>

</body>
</html>
