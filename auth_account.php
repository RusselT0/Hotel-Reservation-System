<?php

require_once __DIR__ . '/auth_session.php';

// Prevent unauthenticated access
if (empty($_SESSION['auth_user'])) {
    header('Location: login.php');
    exit;
}

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

$user = $_SESSION['auth_user'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Account | Bella Vista Hotel</title>

    <link rel="stylesheet" href="global_style.css">

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

</body>
</html>