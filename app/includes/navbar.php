
<?php
require_once __DIR__ . '/../config/app.php';
?>

<header class="site-header">
    <nav class="navbar">

        <a href="<?= BASE_URL ?>/index.php"
           class="hotel-logo">
            Bella Vista Hotel
        </a>

        <div class="nav-links">
            <a href="<?= BASE_URL ?>/index.php">
                Home
            </a>

            <a href="<?= BASE_URL ?>/rooms.php">
                Rooms & Facilities
            </a>

            <a href="<?= BASE_URL ?>/login.php">
                Login
            </a>

            <a href="<?= BASE_URL ?>/register.php"
               class="nav-button">
                Register
            </a>
        </div>

    </nav>
</header>
