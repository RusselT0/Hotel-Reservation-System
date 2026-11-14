<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Bella Vista Hotel & Events Place</title>

    <!-- Global Styles -->
    <link rel="stylesheet" href="global_style.css?v=2">

    <!-- Landing Page Styles -->
    <link rel="stylesheet" href="landing.css?v=2">

    <!-- Responsive Styles -->
    <link rel="stylesheet" href="responsive_landing.css?v=2">
    </head>

<body>

    <!-- HERO SECTION -->
    <header class="hero" id="home">

        <div class="hero-visual">

            <!-- HERO IMAGE -->
            <img
                src="images/hero.webp"
                alt="Bella Vista Hotel exterior with mountain scenery"
                class="hero-image"
            >

            <!-- NAVIGATION -->
            <nav class="navbar">
                    
                <a href="#home" class="logo">
                    <img
                        src="images/logo-removebg.png"
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
                    <a href="login.php">Sign In / Register</a>
                </div>

            </nav>

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
        <form class="availability-form">

            <div class="form-group">
                <label for="checkin">Check-in Date</label>
                <input type="date"
                       id="checkin"
                       name="checkin">
            </div>

            <div class="form-group">
                <label for="checkout">Check-out Date</label>
                <input type="date"
                       id="checkout"
                       name="checkout">
            </div>

            <div class="form-group">
                <label for="facility">Room/Facility Type</label>
                <select id="facility" name="facility">
                    <option value="">Select Type</option>
                    <option value="standard">Standard Room</option>
                    <option value="family">Family Room</option>
                    <option value="event">Event Hall</option>
                </select>
            </div>

            <div class="form-group">
                <label for="guests">Guests</label>
                <input type="number"
                       id="guests"
                       name="guests"
                       min="1"
                       placeholder="# of guests">
            </div>

            <button type="button" class="availability-btn">
                <span class="search-icon" aria-hidden="true">
                    &#9906;
                </span>
                Check Availability
            </button>

        </form>

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
        <section class="gallery-section" id="gallery">

            <div class="container">

                <h2 class="section-title">Photo Gallery</h2>

                <p class="gallery-description">
                    Explore the beauty and comfort of
                    Bella Vista Hotel & Events Place.
                </p>

                <div class="gallery-carousel"
                    aria-label="Hotel photo gallery"
                    aria-roledescription="carousel">

                    <div class="gallery-window">

                        <div class="gallery-track">

                            <!-- IMAGE 1 -->
                            <figure class="gallery-slide">
                                <img
                                    src="images/standard room.jfif"
                                    alt="Comfortable Standard Room interior"
                                >
                                <figcaption>
                                    Standard Room
                                </figcaption>
                            </figure>

                            <!-- IMAGE 2 -->
                            <figure class="gallery-slide">
                                <img
                                    src="images/family room.webp"
                                    alt="Spacious Family Room interior"
                                >
                                <figcaption>
                                    Family Room
                                </figcaption>
                            </figure>

                            <!-- IMAGE 3 -->
                            <figure class="gallery-slide">
                                <img
                                    src="images/event hall.jfif"
                                    alt="Bella Vista Event Hall interior"
                                >
                                <figcaption>
                                    Event Hall
                                </figcaption>
                            </figure>

                            <!-- IMAGE 4 -->
                            <figure class="gallery-slide">
                                <img
                                    src="images/hotel on mountains 2.webp"
                                    alt="Bella Vista Hotel exterior at night"
                                >
                                <figcaption>
                                    Hotel Exterior
                                </figcaption>
                            </figure>

                        </div>

                    </div>

                    <!-- PREVIOUS BUTTON -->
                    <button
                        type="button"
                        class="gallery-control prev"
                        aria-label="Previous photo">
                        &#10094;
                    </button>

                    <!-- NEXT BUTTON -->
                    <button
                        type="button"
                        class="gallery-control next"
                        aria-label="Next photo">
                        &#10095;
                    </button>

                </div>

                <!-- SLIDE INDICATORS -->
                <div class="gallery-dots"
                    aria-label="Choose a gallery photo">
                </div>

            </div>

        </section>


                <!-- ROOMS AND FACILITIES -->
                <section class="rooms-section" id="rooms">

                    <div class="container">

                        <h2 class="section-title">
                            Rooms & Facilities Inventory
                        </h2>

                        <div class="room-grid">

                            <!-- STANDARD ROOM -->
                            <article class="room-card">

                                <img
                                    src="images/standard room.jfif"
                                    alt="Interior of a Bella Vista Standard Room"
                                >

                                <div class="room-info">
                                    <h3>Standard Room</h3>
                                    <p>Good for 2–3 persons</p>
                                    <span>₱3,500 / night</span>
                                </div>

                            </article>

                            <!-- FAMILY ROOM -->
                            <article class="room-card">

                                <img
                                    src="images/family room.webp"
                                    alt="Interior of a Bella Vista Family Room"
                                >

                                <div class="room-info">
                                    <h3>Family Room</h3>
                                    <p>Good for up to 5 persons</p>
                                    <span>₱6,000 / night</span>
                                </div>

                            </article>

                            <!-- EVENT HALL -->
                            <article class="room-card">

                                <img
                                    src="images/event hall.jfif"
                                    alt="Bella Vista Event Hall for celebrations and gatherings"
                                >

                                <div class="room-info">
                                    <h3>Event Hall</h3>
                                    <p>30 guests included, maximum 40</p>
                                    <span>₱15,000 / night</span>
                                </div>

                            </article>

                        </div>

                    </div>

                </section>

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

    <script src="gallery.js" defer></script>

</body>
</html>
