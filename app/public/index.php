
<?php
require_once __DIR__ . '/../app/config/app.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= APP_NAME ?></title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/global.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/components.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/landing.css">
</head>

<body>

    <?php
    include __DIR__ . '/../app/includes/navbar.php';
    ?>

    <main>

        <section class="hero">
            <div class="hero-content">
                <h1>
                    Your Peaceful Eco-Chic Sanctuary
                    in the Foothills
                </h1>

                <p>
                    Experience comfort, relaxation,
                    and unforgettable moments
                    at Bella Vista Hotel.
                </p>

                <a href="<?= BASE_URL ?>/rooms.php"
                   class="hero-button">
                    Explore Rooms
                </a>
            </div>
        </section>

        <section class="about-section" id="about">
            <h2>About Us & History</h2>

            <p>
                Established in 2021, Bella Vista
                Hotel & Events Place offers
                a peaceful getaway surrounded
                by natural beauty.
            </p>
        </section>

        <section class="rooms-section" id="rooms">
            <h2>Rooms & Facilities</h2>

            <p>
                Discover our accommodations
                and event facilities.
            </p>
        </section>

    </main>

    <?php
    include __DIR__ . '/../app/includes/footer.php';
    ?>

    <script
        src="<?= BASE_URL ?>/assets/js/main.js"
        defer>
    </script>

</body>
</html>
