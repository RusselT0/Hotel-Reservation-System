<?php

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth_session.php';
$currentUser = authCurrentUser($pdo);

try {

    // Retrieve active room/facility types
    $sql = "SELECT room_Type_ID, room_Type
            FROM Room_Type
            WHERE deleted_At IS NULL
            ORDER BY room_Type ASC";

    // Execute the query
    $stmt = $pdo->query($sql);

    // Store the results in an array
    $roomTypes = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log($e->getMessage());
    $roomTypes = [];

}

require_once __DIR__ . '/includes/room_catalog.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Bella Vista Hotel & Events Place</title>

    <link rel="stylesheet" href="assets/css/global_style.css">
    <link rel="stylesheet" href="assets/css/landing.css?v=4">
    <link rel="stylesheet" href="assets/css/availability_form.css?v=2">
    <link rel="stylesheet" href="assets/css/photo_gallery.css?v=2">
    <link rel="stylesheet" href="assets/css/rooms_facilities.css?v=4">
    <link rel="stylesheet" href="assets/css/responsive_landing.css">

    </head>

<body>

<!-- NAVIGATION -->
            <nav class="navbar">
                    
                <a href="#home" class="logo">
                    <img
                        src="assets/images/logo-removebg.png"
                        alt="Bella Vista Hotel & Events Place Logo"
                        class="hotel-logo"
                    >

                    <span class="logo-text">
                        Bella Vista Hotel
                        <small>& Events Place</small>
                    </span>
                </a>

                <div class="nav-links">
                    <a href="#about">About Us</a>
                    <a href="#gallery">Gallery</a>
                    <a href="#rooms">Rooms & Facilities</a>
                    <?php if ($currentUser): ?>
                        <a href="<?= authEscape(authDashboardPath($currentUser['role'])) ?>" class="nav-account-link" title="Your dashboard"><?= authEscape($currentUser['first_name']) ?></a>
                    <?php else: ?>
                        <a href="pages/login.php">Sign In / Register</a>
                    <?php endif; ?>
                </div>

            </nav>

    <!-- HERO SECTION -->
    <header class="hero" id="home">

        <div class="hero-visual">

            <!-- HERO IMAGE -->
            <img
                src="assets/images/hero.webp"
                alt="Bella Vista Hotel exterior with mountain scenery"
                class="hero-image"
            >

            <!-- HERO CONTENT -->
            <div class="hero-content">

                <h1>
                    Your Peaceful Eco-Chic
                    <br>
                    Sanctuary in the Foothills
                </h1>

                <p>
                    Experience warm Filipino hospitality,
                    serene mountain views, and
                    unforgettable intimate events.
                </p>
            </div>

    </div>

        <!-- BOOKING AVAILABILITY FORM -->
        <form class="availability-form"
            id="availabilityForm"
            action="pages/availability.php"
            method="GET"
            novalidate>

            <div class="form-group">
                <label for="checkin">Check-in Date</label>
                <input type="date"
                       id="checkin"
                       name="checkin"
                       required>
            </div>

            <div class="form-group">
                <label for="checkout">Check-out Date</label>
                <input type="date"
                       id="checkout"
                       name="checkout"
                       required>
            </div>

            <div class="form-group">
                <label for="facility">Room/Facility Type</label>
                <select id="facility" name="facility" required>
                    <option value="">Select Type</option>
                    <?php foreach ($roomTypes as $type): ?>
                        <option value="<?= (int) $type['room_Type_ID']; ?>">
                            <?= htmlspecialchars(
                                $type['room_Type'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
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
                       placeholder="# of guests"
                       required>
            </div>

            <button type="submit" class="availability-btn">
                <span class="search-icon" aria-hidden="true">
                    &#9906;
                </span>
                Check Availability
            </button>

        </form>


        <!-- AVAILABILITY VALIDATION MODAL -->
        <dialog id="availabilityErrorModal" class="availability-error-modal">

            <div class="availability-error-content">

                <span class="availability-error-icon">
                    !
                </span>

                <h2>Unable to Check Availability</h2>

                <p id="availabilityErrorMessage"></p>

                <button type="button"
                        id="closeAvailabilityError"
                        class="availability-error-close">
                    OK, Got It
                </button>

            </div>

        </dialog>

    </header>


    <main>

        <!-- ABOUT US & HISTORY -->
        <section class="about-section" id="about">

            <div class="container">

                <h2 class="section-title">
                    About Us & History
                </h2>

                <div class="about-grid">

                    <div class="about-content">
                        <p>
                            Bella Vista Hotel & Events Place
                            is a premier eco-chic boutique hotel
                            and events destination nestled at
                            the scenic foothills of the mountains.
                        </p>

                        <p>
                            It offers a tranquil, elegant,
                            and exclusive retreat for families,
                            tourists, and corporate clients
                            looking for a peaceful sanctuary
                            away from the hustle and bustle
                            of city life.
                        </p>
                    </div>

                    <div class="history-content">
                        <p>
                            Established in 2021, Bella Vista
                            began as an exclusive family-owned
                            resthouse. Due to growing demand
                            for intimate celebrations and
                            restful getaways, it was expanded
                            and transformed into a boutique
                            hotel and events venue.
                        </p>

                        <p>
                            Today, it is celebrated for its
                            warm Filipino hospitality,
                            picturesque views, and top-tier
                            service for accommodations and
                            special events.
                        </p>
                    </div>

                </div>

            </div>

        </section>


        
        <!-- PHOTO GALLERY -->
        <section class="bv-gallery" id="gallery">

            <div class="bv-gallery__panel">

                <div class="bv-gallery__header">
                    <h2>Photo Gallery</h2>
                </div>

                <p class="bv-gallery__description">
                    Explore the beauty and comfort of
                    Bella Vista Hotel & Events Place.
                </p>

                <div class="bv-carousel"
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Hotel Photo Gallery"
                    tabindex="0">

                    <div class="bv-carousel__stage">

                        <!-- STANDARD ROOM -->
                        <figure class="bv-carousel__slide is-active">
                            <img src="assets/images/standard room.jfif"
                                alt="Bella Vista Standard Room interior">

                            <figcaption>Standard Room</figcaption>
                        </figure>

                        <!-- FAMILY ROOM -->
                        <figure class="bv-carousel__slide is-next">
                            <img src="assets/images/family room.webp"
                                alt="Bella Vista Family Room interior">

                            <figcaption>Family Room</figcaption>
                        </figure>

                        <!-- EVENT HALL -->
                        <figure class="bv-carousel__slide">
                            <img src="assets/images/event hall.jfif"
                                alt="Bella Vista Event Hall interior">

                            <figcaption>Event Hall</figcaption>
                        </figure>

                        <!-- HOTEL EXTERIOR -->
                        <figure class="bv-carousel__slide is-prev">
                            <img src="assets/images/hotel on mountains 2.webp"
                                alt="Bella Vista Hotel exterior">

                            <figcaption>Hotel Exterior</figcaption>
                        </figure>

                    </div>

                    <button type="button"
                            class="bv-carousel__arrow prev"
                            aria-label="Previous photo">
                        &#10094;
                    </button>

                    <button type="button"
                            class="bv-carousel__arrow next"
                            aria-label="Next photo">
                        &#10095;
                    </button>

                </div>

                <div class="bv-carousel__dots"
                    aria-label="Gallery slide navigation">
                </div>

            </div>

        </section>
  
        <!-- ROOMS AND FACILITIES -->
        <section class="rooms-section" id="rooms">

            <div class="container">

                <div class="rooms-header">

                    <div>
                        <p class="rooms-eyebrow">
                            BELLA VISTA HOTEL & EVENTS PLACE
                        </p>

                        <h2>Rooms & Facilities</h2>

                        <p class="rooms-description">
                            Discover your perfect stay or event space.
                        </p>
                    </div>

                    <!-- CAROUSEL CONTROLS -->
                    <div class="rooms-controls">

                        <button type="button"
                                class="rooms-prev"
                                aria-label="Previous room">
                            &#10094;
                        </button>

                        <button type="button"
                                class="rooms-next"
                                aria-label="Next room">
                            &#10095;
                        </button>

                    </div>

                </div>

                <!-- HORIZONTAL CAROUSEL -->
                <div class="room-grid"
                    id="roomTrack"
                    role="region"
                    aria-label="Rooms and facilities"
                    tabindex="0">

                    <?php foreach ($catalogRooms as $room): ?>

                        <?php

                            $included = (int)$room['included_Guests'];
                            $maximum = (int)$room['max_Guests'];

                            $capacity = $included === $maximum
                                ? $maximum . ' Guests'
                                : $included . '–' . $maximum . ' Guests';

                            $roomDetails = json_encode(
                                $room,
                                JSON_INVALID_UTF8_SUBSTITUTE
                            );

                        ?>

                        <article class="room-card">

                            <button
                                type="button"
                                class="room-card-open"
                                data-room="<?= (int)$room['room_Type_ID'] ?>"
                                data-room-details="<?= catalogEscape($roomDetails) ?>"
                                aria-label="View <?= catalogEscape($room['room_Type']) ?> details"
                            >

                                <img
                                    src="<?= catalogEscape($room['image']) ?>"
                                    alt="<?= catalogEscape($room['room_Type']) ?>"
                                >

                                <div class="room-card-content">

                                    <span class="room-view">
                                        View Details &#8599;
                                    </span>

                                    <h3>
                                        <?= catalogEscape($room['room_Type']) ?>
                                    </h3>

                                    <p>
                                        <?= catalogEscape($capacity) ?>
                                    </p>

                                    <strong>
                                        ₱<?= number_format(
                                            (float)$room['base_Price'],
                                            0
                                        ) ?> / night
                                    </strong>

                                </div>

                            </button>

                        </article>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

        <!-- ROOM DETAILS MODAL -->
        <dialog
            id="roomDetailsModal"
            class="room-modal"
            aria-labelledby="roomModalTitle"
            data-catalog-error="<?= catalogEscape($roomCatalogError) ?>"
        >

            <button
                type="button"
                class="room-modal-close"
                aria-label="Close room details"
            >
                &times;
            </button>

            <h2 id="roomModalTitle">Room Details</h2>

            <div id="roomModalDetails">

                <p id="roomModalCapacity"></p>

                <p id="roomModalPrice"></p>

                <p id="roomModalFee"></p>

                <!-- BED CONFIGURATION -->

                <div class="room-modal-section" id="roomModalBedSection">

                    <h3>Bed Configuration</h3>

                    <ul id="roomModalBeds"></ul>

                </div>

                <!-- ROOM FEATURES -->

                <div class="room-modal-section" id="roomModalFeatureSection">

                    <h3>Features & Amenities</h3>

                    <ul id="roomModalFeatures"></ul>

                </div>

            </div>

            <button
                type="button"
                class="room-book-btn"
            >
                Check Availability
            </button>

            <p class="room-modal-note"></p>

        </dialog>

    </main>

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

    
    <script src="assets/js/gallery.js" defer></script>
    <script src="assets/js/rooms_facilities.js?v=2" defer></script>
    <script src="assets/js/navbar.js" defer></script>
    <script src="assets/js/availability_validation.js" defer></script>
</body>
</html>
