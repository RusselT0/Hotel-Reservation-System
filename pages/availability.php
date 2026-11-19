<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth_session.php';
$currentUser = authCurrentUser($pdo);

// Retrieve room types from database
$stmt = $pdo->query("
    SELECT room_Type_ID, room_Type
    FROM Room_Type
    WHERE deleted_At IS NULL
    ORDER BY room_Type ASC
");

$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the selected search values
function searchValue(string $key): string {
    $value = $_GET[$key] ?? '';
    return is_string($value) ? $value : '';
}

$checkin = searchValue('checkin');
$checkout = searchValue('checkout');
$facility = searchValue('facility');
$guests = searchValue('guests');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Room Availability | Bella Vista Hotel</title>
    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/landing.css?v=4">
    <link rel="stylesheet" href="../assets/css/availability_form.css?v=2">
    <link rel="stylesheet" href="../assets/css/availability_page.css?v=3">
    <link rel="stylesheet" href="../assets/css/responsive_landing.css?v=3">
</head>


<body>

<!-- HEADER -->
<header class="avp-header">

    <!-- REUSED NAVBAR -->
    <nav class="navbar avp-navbar">

        <a href="../index.php#home" class="logo">
            <img src="../assets/images/logo-removebg.png"
                alt="Bella Vista Hotel Logo"
                class="hotel-logo">

            <span class="logo-text">
                Bella Vista Hotel
                <small>& Events Place</small>
            </span>
        </a>

        <div class="nav-links">
            <a href="../index.php#about">About Us</a>
            <a href="../index.php#gallery">Gallery</a>
            <a href="../index.php#rooms">Rooms & Facilities</a>
            <?php if ($currentUser): ?>
                <a href="../<?= authEscape(authDashboardPath($currentUser['role'])) ?>" class="nav-account-link" title="Your dashboard"><?= authEscape($currentUser['first_name']) ?></a>
            <?php else: ?>
                <a href="login.php">Sign In / Register</a>
            <?php endif; ?>
        </div>

    </nav>

    <!-- REUSED AVAILABILITY FORM -->
    <div class="avp-search-container">

        <form class="availability-form avp-search-form"
              id="availabilityForm"
              action="availability.php"
              method="GET"
              novalidate>

            <div class="form-group">
                <label for="checkin">Check-in Date</label>
                <input type="date"
                       id="checkin"
                       name="checkin"
                       value="<?= htmlspecialchars($checkin, ENT_QUOTES, 'UTF-8') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="checkout">Check-out Date</label>
                <input type="date"
                       id="checkout"
                       name="checkout"
                       value="<?= htmlspecialchars($checkout, ENT_QUOTES, 'UTF-8') ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="facility">Room/Facility Type</label>

                <select id="facility" name="facility" required>
                    <option value="">Select Type</option>

                    <?php foreach ($roomTypes as $type): ?>
                        <option
                            value="<?= (int)$type['room_Type_ID'] ?>"
                            <?= $facility === (string)$type['room_Type_ID'] ? 'selected' : '' ?>>

                            <?= htmlspecialchars($type['room_Type'], ENT_QUOTES, 'UTF-8') ?>

                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="guests">Guests</label>

                <input type="number"
                       id="guests"
                       name="guests"
                       min="1"
                       value="<?= htmlspecialchars($guests, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="# of guests"
                       required>
            </div>

            <button type="submit" class="availability-btn">
                Search
            </button>

        </form>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="avp-main">

    <!-- BREADCRUMB -->
    <div class="avp-breadcrumb">
        <a href="../index.php">Home</a>
        <span>/</span>
        <span>Availability Results</span>
    </div>

    <div class="avp-layout">

        <!-- LEFT SIDEBAR -->
        <aside class="avp-sidebar">

            <section class="avp-filter-panel">
                <h2>Filter by:</h2>

                <div class="avp-filter-section">
                    <h3>Price per night</h3>
                    <p>Price filters coming soon</p>
                </div>

                <div class="avp-filter-section">
                    <h3>Room Features</h3>
                    <p>Amenity filters coming soon</p>
                </div>

                <div class="avp-filter-section">
                    <h3>Guest Capacity</h3>
                    <p>Capacity filters coming soon</p>
                </div>
            </section>

        </aside>

        <!-- RIGHT CONTENT -->
        <section class="avp-results-section">

            <div class="avp-results-header">
                <div>
                    <h1>Available Rooms & Facilities</h1>
                    <p id="availabilityCount">
                        Checking availability...
                    </p>
                </div>

                <span class="avp-view-label">
                    List View
                </span>
            </div>

            <!-- DYNAMIC ROOM CARDS WILL APPEAR HERE -->
            <div id="availabilityResults"
                 class="avp-room-list"
                 aria-live="polite">
            </div>

        </section>

    </div>
</main>

<!-- REUSED VALIDATION MODAL -->
<dialog id="availabilityErrorModal"
        class="availability-error-modal">

    <div class="availability-error-content">

        <span class="availability-error-icon">!</span>

        <h2>Unable to Check Availability</h2>

        <p id="availabilityErrorMessage"></p>

        <button type="button"
                id="closeAvailabilityError"
                class="availability-error-close">
            OK, Got It
        </button>

    </div>
</dialog>

<script src="../assets/js/navbar.js" defer></script>
<script src="../assets/js/availability_validation.js" defer></script>
<script src="../assets/js/availability_results.js?v=2" defer></script>

<!-- FOOTER -->
    <footer class="footer">

        <h3>Bella Vista Hotel & Events Place</h3>

        <p>
            Your Peaceful Eco-Chic Sanctuary
            in the Foothills
        </p>

        <p class="copyright">
            &copy; 2026 Bella Vista Hotel & Events Place.
            All rights reserved.
        </p>

    </footer>
</body>

</html>
