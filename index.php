<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Bella Vista Hotel & Events Place</title>

    <link rel="stylesheet" href="global_style.css">
    <link rel="stylesheet" href="landing.css">
    <link rel="stylesheet" href="availability_form.css">
    <link rel="stylesheet" href="photo_gallery.css">
    <link rel="stylesheet" href="rooms_facilities.css">
    <link rel="stylesheet" href="responsive_landing.css">

    </head>

<body>

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

    <!-- HERO SECTION -->
    <header class="hero" id="home">

        <div class="hero-visual">

            <!-- HERO IMAGE -->
            <img
                src="images/hero.webp"
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
                            <img src="images/standard room.jfif"
                                alt="Bella Vista Standard Room interior">

                            <figcaption>Standard Room</figcaption>
                        </figure>

                        <!-- FAMILY ROOM -->
                        <figure class="bv-carousel__slide is-next">
                            <img src="images/family room.webp"
                                alt="Bella Vista Family Room interior">

                            <figcaption>Family Room</figcaption>
                        </figure>

                        <!-- EVENT HALL -->
                        <figure class="bv-carousel__slide">
                            <img src="images/event hall.jfif"
                                alt="Bella Vista Event Hall interior">

                            <figcaption>Event Hall</figcaption>
                        </figure>

                        <!-- HOTEL EXTERIOR -->
                        <figure class="bv-carousel__slide is-prev">
                            <img src="images/hotel on mountains 2.webp"
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

                    <!-- STANDARD ROOM -->
                    <article class="room-card">

                        <button type="button"
                                class="room-card-open"
                                data-room="standard"
                                aria-label="View Standard Room details">

                            <img src="images/standard room.jfif"
                                alt="Bella Vista Standard Room">

                            <div class="room-card-content">

                                <span class="room-view">
                                    View Details &#8599;
                                </span>

                                <h3>Standard Room</h3>
                                <p>2–3 Guests</p>

                                <strong>₱3,500 / night</strong>

                            </div>

                        </button>

                    </article>

                    <!-- FAMILY ROOM -->
                    <article class="room-card">

                        <button type="button"
                                class="room-card-open"
                                data-room="family"
                                aria-label="View Family Room details">

                            <img src="images/family room.webp"
                                alt="Bella Vista Family Room">

                            <div class="room-card-content">

                                <span class="room-view">
                                    View Details &#8599;
                                </span>

                                <h3>Family Room</h3>
                                <p>Up to 5 Guests</p>

                                <strong>₱6,000 / night</strong>

                            </div>

                        </button>

                    </article>

                    <!-- EVENT HALL -->
                    <article class="room-card">

                        <button type="button"
                                class="room-card-open"
                                data-room="event"
                                aria-label="View Event Hall details">

                            <img src="images/event hall.jfif"
                                alt="Bella Vista Event Hall">

                            <div class="room-card-content">

                                <span class="room-view">
                                    View Details &#8599;
                                </span>

                                <h3>Event Hall</h3>
                                <p>30–40 Guests</p>

                                <strong>₱15,000 / night</strong>

                            </div>

                        </button>

                    </article>

                </div>

            </div>

        </section>

        <!-- ROOM DETAILS MODAL -->
        <dialog id="roomDetailsModal" class="room-modal">

            <button type="button"
                    class="room-modal-close"
                    aria-label="Close room details">
                &times;
            </button>

            <h2 id="roomModalTitle">Room Details</h2>

            <p id="roomModalCapacity"></p>

            <p id="roomModalPrice"></p>

            <p id="roomModalDescription"></p>

            <p id="roomModalFee"></p>

            <button type="button"
                    class="room-book-btn"
                    disabled>
                Book Now
            </button>

            <p class="room-modal-note">
                Online booking will be available when
                the reservation module is implemented.
            </p>

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

    
    <script src="gallery.js" defer></script>
    <script src="rooms_facilities.js" defer></script>
    <script src="navbar.js" defer></script>
</body>
</html>
