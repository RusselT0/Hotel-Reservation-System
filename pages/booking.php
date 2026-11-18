
<?php

session_start();

$bookingSaveError = $_SESSION['booking_save_error'] ?? null;
unset($_SESSION['booking_save_error']);

require_once __DIR__ . '/../includes/database.php';

// Receive booking information from URL
$roomID = filter_input(
    INPUT_GET,
    'room_id',
    FILTER_VALIDATE_INT
);

$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';

$guests = filter_input(
    INPUT_GET,
    'guests',
    FILTER_VALIDATE_INT
);

$error = null;
$room = null;
$nights = 0;

$timezone = new DateTimeZone('Asia/Manila');

// Validate parameters
if (
    !$roomID ||
    !$guests ||
    $guests < 1 ||
    !is_string($checkin) ||
    !is_string($checkout)
) {

    $error = "Invalid booking information.";

} else {

    // Validate the selected dates
    $checkinDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $checkin,
        $timezone
    );

    $checkoutDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $checkout,
        $timezone
    );

    $today = new DateTimeImmutable('today', $timezone);

    if (
        !$checkinDate ||
        !$checkoutDate ||
        $checkinDate->format('Y-m-d') !== $checkin ||
        $checkoutDate->format('Y-m-d') !== $checkout ||
        $checkinDate < $today ||
        $checkoutDate <= $checkinDate
    ) {

        $error = "Invalid check-in or checkout dates.";

    } else {

        $nights = (int) $checkinDate
            ->diff($checkoutDate)->days;

        // Retrieve actual room information
        $sql = "
            SELECT
                r.room_ID,
                r.room_Code,
                rt.room_Type,
                rt.base_Price,
                rt.included_Guests,
                rt.max_Guests,
                rt.extra_Person_Fee

            FROM Room r

            INNER JOIN Room_Type rt
                ON r.room_Type_ID = rt.room_Type_ID

            WHERE r.room_ID = :room_id
              AND r.deleted_At IS NULL
              AND rt.deleted_At IS NULL
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'room_id' => $roomID
        ]);

        $room = $stmt->fetch();

        if (!$room) {

            $error = "Selected room was not found.";

        } elseif ($guests > (int)$room['max_Guests']) {

            $error = "The selected room cannot accommodate "
                   . $guests . " guests.";

        }
    }
}

// Safely display text in HTML
function escapeHTML($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ========================================
// RETRIEVE AVAILABLE ADD-ONS
// ========================================

$addons = [];
$addonLookup = [];

// Stores guest selections
$selectedAddons = [];

// Stores calculated amounts and display rows
$addonLines = [];
$bookingBreakdown = null;

// Required downpayment percentage
$downpaymentPercentage = null;
$holdMinutes = null;

try {
    $stmt = $pdo->query("
        SELECT setting_Key, setting_Value
        FROM Hotel_Setting
        WHERE setting_Key IN (
            'downpayment_percent',
            'pending_hold_minutes'
        )
    ");

    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $percentageValue = $settings['downpayment_percent'] ?? null;
    $holdValue = $settings['pending_hold_minutes'] ?? null;

    if (
        !is_numeric($percentageValue) ||
        (float)$percentageValue <= 0 ||
        (float)$percentageValue > 100 ||
        !is_string($holdValue) ||
        !ctype_digit($holdValue) ||
        (int)$holdValue < 1
    ) {
        throw new RuntimeException(
            "Invalid booking configuration."
        );
    }

    $downpaymentPercentage = (float)$percentageValue;
    $holdMinutes = (int)$holdValue;

} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = "Booking settings are not configured correctly.";
}

if ($error === null) {

    try {
        $stmt = $pdo->query(
            "SELECT addon_ID, addon_Name, addon_Price
             FROM Addon
             WHERE deleted_At IS NULL
             ORDER BY addon_Name ASC"
        );

        $addons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($addons as $addon) {
            $addonLookup[(int)$addon['addon_ID']] = $addon;
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "Unable to load add-ons right now.";
    }
}


/* ========================================
   GUEST INFORMATION PROCESSING
======================================== */

// Default form values
$guestData = [
    'first_Name' => '',
    'last_Name' => '',
    'email_Address' => '',
    'phone_Number' => ''
];

$guestError = null;
$guestReviewed = false;
$termsAccepted = false;

// Process the guest information form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {

    // Check whether the guest accepted the booking terms
    $termsAccepted = ($_POST['accept_terms'] ?? null) === '1';

    // Retrieve submitted values
    foreach ($guestData as $field => $default) {

        $value = $_POST[$field] ?? '';

        $guestData[$field] = is_string($value)
            ? trim($value)
            : '';
    }

    // Check required fields
    if (in_array('', $guestData, true)) {

        $guestError = "Please complete all guest information.";

    } elseif (
        strlen($guestData['first_Name']) > 80 ||
        strlen($guestData['last_Name']) > 80
    ) {

        $guestError = "Guest names are too long.";

    } elseif (
        strlen($guestData['email_Address']) > 190 ||
        !filter_var(
            $guestData['email_Address'],
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $guestError = "Please enter a valid email address.";

    } elseif (
        strlen($guestData['phone_Number']) > 25 ||
        !preg_match(
            '/^\+?[0-9][0-9\s-]{6,24}$/',
            $guestData['phone_Number']
        )
    ) {

        $guestError = "Please enter a valid phone number.";

    } elseif (!$termsAccepted) {

        $guestError = "Please accept the booking terms and conditions.";

    } else {

        // ========================================
        // VALIDATE SELECTED ADD-ONS
        // ========================================

        $submittedAddons = $_POST['addons'] ?? [];

        if (!is_array($submittedAddons)) {

            $guestError = "Invalid add-on selection.";

        } else {

            foreach ($submittedAddons as $addonID => $quantity) {

                $addonID = (string)$addonID;

                // Only accept add-ons that exist in the database
                if (
                    !ctype_digit($addonID) ||
                    !isset($addonLookup[(int)$addonID]) ||
                    !is_scalar($quantity)
                ) {
                    $guestError = "Invalid add-on selection.";
                    break;
                }

                $quantity = filter_var(
                    (string)$quantity,
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 0,
                            'max_range' => 10
                        ]
                    ]
                );

                if ($quantity === false) {
                    $guestError = "Add-on quantity must be between 0 and 10.";
                    break;
                }

                $selectedAddons[(int)$addonID] = $quantity;
            }

            // ========================================
            // CALCULATE BOOKING SUBTOTAL
            // ========================================

            if ($guestError === null) {

                // Work in centavos to avoid floating-point
                // errors when adding currency amounts.
                $nightlyRateCents = (int)round(
                    (float)$room['base_Price'] * 100
                );

                // Room subtotal = nightly rate x nights
                $roomSubtotalCents =
                    $nightlyRateCents * $nights;

                $addonSubtotalCents = 0;

                // Calculate selected add-ons
                foreach ($addons as $addon) {

                    $id = (int)$addon['addon_ID'];

                    $quantity = $selectedAddons[$id] ?? 0;

                    if ($quantity === 0) {
                        continue;
                    }

                    $unitPriceCents = (int)round(
                        (float)$addon['addon_Price'] * 100
                    );

                    $lineTotalCents =
                        $unitPriceCents * $quantity;

                    $addonSubtotalCents += $lineTotalCents;

                    $addonLines[] = [
                        'name' => $addon['addon_Name'],
                        'quantity' => $quantity,
                        'line_total_cents' => $lineTotalCents
                    ];
                }

                // ========================================
                // EXTRA-PERSON FEE CALCULATION
                // ========================================

                // Number of guests exceeding included capacity
                $extraGuests = max(
                    0,
                    (int)$guests - (int)$room['included_Guests']
                );

                // Check whether the required fee is configured
                $extraFeeMissing = (
                    $room['extra_Person_Fee'] === null ||
                    $room['extra_Person_Fee'] === ''
                );

                if ($extraGuests > 0 && $extraFeeMissing) {

                    $guestError =
                        "The extra-person fee for this room has not been configured.";

                } else {

                    // Fixed rate per additional person
                    $extraGuestRateCents = $extraFeeMissing
                        ? 0
                        : (int)round(
                            (float)$room['extra_Person_Fee'] * 100
                        );

                    // Charged ONCE per person, not per night
                    $extraGuestChargeCents =
                        $extraGuests * $extraGuestRateCents;

                    // ========================================
                    // FINAL BOOKING TOTAL
                    // ========================================

                    $bookingTotalCents =
                        $roomSubtotalCents +
                        $extraGuestChargeCents +
                        $addonSubtotalCents;

                    // ========================================
                    // REQUIRED DOWNPAYMENT
                    // ========================================

                    $downpaymentCents = null;
                    $remainingBalanceCents = null;

                    if (
                        $downpaymentPercentage !== null &&
                        (
                            $downpaymentPercentage <= 0 ||
                            $downpaymentPercentage > 100
                        )
                    ) {

                        $guestError =
                            "Invalid downpayment configuration.";

                    } else {

                        if ($downpaymentPercentage !== null) {

                            $downpaymentCents = (int)round(
                                $bookingTotalCents *
                                ($downpaymentPercentage / 100)
                            );

                            $remainingBalanceCents =
                                $bookingTotalCents - $downpaymentCents;
                        }

                        // ========================================
                        // STORE CALCULATED BREAKDOWN
                        // ========================================

                        $bookingBreakdown = [
                            'room_subtotal_cents' => $roomSubtotalCents,
                            'addon_subtotal_cents' => $addonSubtotalCents,

                            'extra_guests' => $extraGuests,
                            'extra_guest_rate_cents' => $extraGuestRateCents,
                            'extra_guest_charge_cents' => $extraGuestChargeCents,

                            'total_cents' => $bookingTotalCents,
                            'downpayment_cents' => $downpaymentCents,
                            'remaining_balance_cents' => $remainingBalanceCents
                        ];

                        $guestReviewed = true;

                        $_SESSION['booking_review'] = [
                            'token' => bin2hex(random_bytes(32)),
                            'created_at' => time(),

                            'room_id' => (int)$roomID,
                            'checkin' => $checkin,
                            'checkout' => $checkout,
                            'guests' => (int)$guests,

                            'guest' => $guestData,

                            'addons' => array_filter(
                                $selectedAddons,
                                static fn($quantity) => $quantity > 0
                            ),

                            'terms_accepted' => $termsAccepted
                        ];

                    }
                }
            }
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

    <title>Book a Room | Bella Vista Hotel</title>

    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/landing.css?v=3">
    <link rel="stylesheet" href="../assets/css/booking.css?v=6">
    <link rel="stylesheet" href="../assets/css/responsive_landing.css?v=3">
</head>

<body>
<header class="booking-header">
<!-- NAVIGATION -->
            <nav class="navbar">

                <a href="../index.php" class="logo">
                    <img
                        src="../assets/images/logo-removebg.png"
                        alt="Bella Vista Hotel & Events Place Logo"
                        class="hotel-logo"
                    >

                    <span class="logo-text">
                        Bella Vista Hotel
                        <small>& Events Place</small>
                    </span>
                </a>

                <div class="nav-links">
                    <a href="../index.php#about">About Us</a>
                    <a href="../index.php#gallery">Gallery</a>
                    <a href="../index.php#rooms">Rooms & Facilities</a>
                    <a href="login.php">Sign In / Register</a>
                </div>

            </nav>
</header>

<!-- ==========================================
     BOOKING PAGE MAIN CONTENT
========================================== -->

<main class="bk-page">

    <div class="bk-container">

        <!-- PAGE INTRODUCTION -->

        <div class="bk-page-heading">

            <p class="bk-eyebrow">
                BELLA VISTA HOTEL & EVENTS PLACE
            </p>

            <h1>Complete Your Reservation</h1>

            <p>
                Review your selected room, provide your guest
                information, and prepare your reservation.
            </p>

        </div>

        <?php if ($error): ?>

            <!-- INVALID BOOKING -->

            <section class="bk-panel">

                <h2>Unable to Continue</h2>

                <p class="bk-error">
                    <?= escapeHTML($error) ?>
                </p>

                <a href="../index.php" class="bk-back-link">
                    Return to Home
                </a>

            </section>

        <?php else: ?>

            <?php

            // ======================================
            // ROOM IMAGE BASED ON TYPE
            // ======================================

            $roomImages = [
                'Standard Room' => '../assets/images/standard room.jfif',
                'Family Room' => '../assets/images/family room.webp',
                'Event Hall' => '../assets/images/event hall.jfif'
            ];

            $roomImage = $roomImages[$room['room_Type']]
                ?? '../assets/images/hero.webp';

            // ======================================
            // INITIAL PRICE PREVIEW
            // ======================================

            $previewRoomCents = (int)round(
                (float)$room['base_Price'] * 100
            ) * $nights;

            $previewExtraGuests = max(
                0,
                (int)$guests -
                (int)$room['included_Guests']
            );

            $previewExtraRateCents = (int)round(
                (float)$room['extra_Person_Fee'] * 100
            );

            $previewExtraCents =
                $previewExtraGuests * $previewExtraRateCents;

            $previewFeeMissing = (
                $previewExtraGuests > 0 &&
                $previewExtraRateCents <= 0
            );

            ?>

            <div class="bk-layout">

                <!-- ==========================
                     LEFT: BOOKING FORM
                =========================== -->

                <div class="bk-main-column">

                    <?php if ($guestReviewed): ?>

                        <!-- REVIEWED GUEST DETAILS -->

                        <section class="bk-panel">

                            <div class="bk-panel-heading">
                                <span class="bk-step">01</span>

                                <div>
                                    <h2>Guest Information</h2>
                                    <p>Details successfully reviewed</p>
                                </div>
                            </div>

                            <div class="bk-review-grid">

                                <div>
                                    <span>Full Name</span>

                                    <strong>
                                        <?= escapeHTML(
                                            $guestData['first_Name']
                                        ) ?>
                                        <?= escapeHTML(
                                            $guestData['last_Name']
                                        ) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Email Address</span>

                                    <strong>
                                        <?= escapeHTML(
                                            $guestData['email_Address']
                                        ) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Phone Number</span>

                                    <strong>
                                        <?= escapeHTML(
                                            $guestData['phone_Number']
                                        ) ?>
                                    </strong>
                                </div>

                            </div>

                        </section>

                        <!-- REVIEWED ADD-ONS -->

                        <section class="bk-panel">

                            <div class="bk-panel-heading">
                                <span class="bk-step">02</span>

                                <div>
                                    <h2>Selected Add-ons</h2>
                                    <p>Additional services for your stay</p>
                                </div>
                            </div>

                            <?php if (empty($addonLines)): ?>

                                <p class="bk-muted">
                                    No optional add-ons selected.
                                </p>

                            <?php else: ?>

                                <?php foreach ($addonLines as $addon): ?>

                                    <div class="bk-price-row">

                                        <span>
                                            <?= escapeHTML($addon['name']) ?>
                                            × <?= (int)$addon['quantity'] ?>
                                        </span>

                                        <strong>
                                            ₱<?= number_format(
                                                $addon['line_total_cents'] / 100,
                                                2
                                            ) ?>
                                        </strong>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </section>

                        <!-- TERMS ACCEPTED -->

                        <section class="bk-panel">

                            <div class="bk-panel-heading">
                                <span class="bk-step">03</span>

                                <div>
                                    <h2>Terms & Conditions</h2>
                                    <p>Hotel booking policies</p>
                                </div>
                            </div>

                            <p class="bk-accepted">
                                Terms and Conditions Accepted
                            </p>

                        </section>

                        <!-- FINAL SUBMISSION -->

                        <section class="bk-panel bk-submit-panel">

                            <h2>Ready to Submit?</h2>

                            <p>
                                Please verify the complete booking
                                summary before submitting your
                                reservation request.
                            </p>

                            <?php if (
                                !empty(
                                    $_SESSION['booking_review']['token']
                                ) &&
                                $bookingBreakdown['downpayment_cents'] !== null
                            ): ?>

                                <form
                                    method="POST"
                                    action="../actions/save_pending_booking.php"
                                >

                                    <input
                                        type="hidden"
                                        name="review_token"
                                        value="<?= escapeHTML(
                                            $_SESSION['booking_review']['token']
                                        ) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="bk-primary-btn"
                                    >
                                        Submit Reservation
                                    </button>

                                </form>

                                <p class="bk-small-note">
                                    Your selected room will be
                                    temporarily held for
                                    <?= (int)$holdMinutes ?> minutes
                                    after submission.
                                </p>

                            <?php else: ?>

                                <p class="bk-error">
                                    Submission is unavailable.
                                    Please review the booking
                                    details again.
                                </p>

                            <?php endif; ?>

                        </section>

                    <?php else: ?>

                        <!-- ==========================
                             GUEST BOOKING FORM
                        =========================== -->

                        <form
                            method="POST"
                            class="bk-form"
                            action="booking.php?<?= escapeHTML(
                                http_build_query([
                                    'room_id' => $roomID,
                                    'checkin' => $checkin,
                                    'checkout' => $checkout,
                                    'guests' => $guests
                                ])
                            ) ?>"
                        >

                            <!-- GUEST INFORMATION -->

                            <section class="bk-panel">

                                <div class="bk-panel-heading">

                                    <span class="bk-step">01</span>

                                    <div>
                                        <h2>Guest Information</h2>

                                        <p>
                                            Enter your personal details
                                            to continue your reservation.
                                        </p>
                                    </div>

                                </div>

                                <div class="bk-fields-grid">

                                    <div class="bk-field">

                                        <label for="first_Name">
                                            First Name
                                        </label>

                                        <input
                                            type="text"
                                            id="first_Name"
                                            name="first_Name"
                                            maxlength="80"
                                            autocomplete="given-name"
                                            placeholder="Enter first name"
                                            value="<?= escapeHTML(
                                                $guestData['first_Name']
                                            ) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="bk-field">

                                        <label for="last_Name">
                                            Last Name
                                        </label>

                                        <input
                                            type="text"
                                            id="last_Name"
                                            name="last_Name"
                                            maxlength="80"
                                            autocomplete="family-name"
                                            placeholder="Enter last name"
                                            value="<?= escapeHTML(
                                                $guestData['last_Name']
                                            ) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="bk-field">

                                        <label for="email_Address">
                                            Email Address
                                        </label>

                                        <input
                                            type="email"
                                            id="email_Address"
                                            name="email_Address"
                                            maxlength="190"
                                            autocomplete="email"
                                            placeholder="example@email.com"
                                            value="<?= escapeHTML(
                                                $guestData['email_Address']
                                            ) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="bk-field">

                                        <label for="phone_Number">
                                            Phone Number
                                        </label>

                                        <input
                                            type="tel"
                                            id="phone_Number"
                                            name="phone_Number"
                                            maxlength="25"
                                            autocomplete="tel"
                                            placeholder="09XXXXXXXXX"
                                            value="<?= escapeHTML(
                                                $guestData['phone_Number']
                                            ) ?>"
                                            required
                                        >

                                    </div>

                                </div>

                                <p class="bk-small-note">
                                    You can book as a guest without
                                    creating an account.
                                </p>

                            </section>

                            <!-- OPTIONAL ADD-ONS -->

                            <section class="bk-panel">

                                <div class="bk-panel-heading">

                                    <span class="bk-step">02</span>

                                    <div>
                                        <h2>Optional Add-ons</h2>

                                        <p>
                                            Select additional services
                                            to enhance your stay.
                                        </p>
                                    </div>

                                </div>

                                <?php if (empty($addons)): ?>

                                    <p class="bk-muted">
                                        No add-ons are currently available.
                                    </p>

                                <?php else: ?>

                                    <?php foreach ($addons as $addon): ?>

                                        <?php
                                            $addonID = (int)$addon['addon_ID'];
                                            $quantity =
                                                $selectedAddons[$addonID] ?? 0;
                                        ?>

                                        <div class="bk-addon-row">

                                            <div class="bk-addon-info">

                                                <strong>
                                                    <?= escapeHTML(
                                                        $addon['addon_Name']
                                                    ) ?>
                                                </strong>

                                                <span>
                                                    ₱<?= number_format(
                                                        (float)$addon['addon_Price'],
                                                        2
                                                    ) ?> each
                                                </span>

                                            </div>

                                            <div class="bk-addon-qty">

                                                <label
                                                    for="addon_<?= $addonID ?>"
                                                >
                                                    Quantity
                                                </label>

                                                <input
                                                    type="number"
                                                    id="addon_<?= $addonID ?>"
                                                    name="addons[<?= $addonID ?>]"
                                                    min="0"
                                                    max="10"
                                                    value="<?= (int)$quantity ?>"
                                                >

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </section>

                            <!-- BOOKING TERMS -->

                            <section class="bk-panel">

                                <div class="bk-panel-heading">

                                    <span class="bk-step">03</span>

                                    <div>
                                        <h2>Terms & Conditions</h2>

                                        <p>
                                            Please read the booking
                                            policies before continuing.
                                        </p>
                                    </div>

                                </div>

                                <div class="bk-terms">

                                    <ul>
                                        <li>Check-in time is 2:00 PM.</li>
                                        <li>Check-out time is 12:00 PM.</li>
                                        <li>A downpayment is required to secure the reservation.</li>
                                        <li>The remaining balance must be settled upon check-in.</li>
                                        <li>The downpayment is non-refundable when cancellation occurs less than three days before check-in.</li>
                                        <li>Extra-person fees are charged once per additional guest per reservation, not per night.</li>
                                    </ul>

                                </div>

                                <label class="bk-agreement">

                                    <input
                                        type="checkbox"
                                        name="accept_terms"
                                        value="1"
                                        <?= !empty($termsAccepted)
                                            ? 'checked'
                                            : '' ?>
                                        required
                                    >

                                    <span>
                                        I have read and agree to the
                                        booking terms and conditions.
                                    </span>

                                </label>

                            </section>

                            <button
                                type="submit"
                                class="bk-primary-btn"
                            >
                                Review Booking Details
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

                <!-- ==========================
                     RIGHT: BOOKING SUMMARY
                =========================== -->

                <aside class="bk-summary-column">

                    <section class="bk-summary-card">

                        <img
                            class="bk-room-image"
                            src="<?= escapeHTML($roomImage) ?>"
                            alt="<?= escapeHTML(
                                $room['room_Type']
                            ) ?>"
                        >

                        <div class="bk-summary-content">

                            <span class="bk-summary-eyebrow">
                                YOUR SELECTED ACCOMMODATION
                            </span>

                            <h2>
                                <?= escapeHTML($room['room_Type']) ?>
                            </h2>

                            <p class="bk-room-code">
                                <?= escapeHTML($room['room_Code']) ?>
                            </p>

                            <div class="bk-summary-divider"></div>

                            <h3>Reservation Details</h3>

                            <div class="bk-details-grid">

                                <div>
                                    <span>Check-in</span>
                                    <strong>
                                        <?= escapeHTML(
                                            date(
                                                'M j, Y',
                                                strtotime($checkin)
                                            )
                                        ) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Check-out</span>
                                    <strong>
                                        <?= escapeHTML(
                                            date(
                                                'M j, Y',
                                                strtotime($checkout)
                                            )
                                        ) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Duration</span>
                                    <strong>
                                        <?= (int)$nights ?>
                                        night(s)
                                    </strong>
                                </div>

                                <div>
                                    <span>Guests</span>
                                    <strong>
                                        <?= (int)$guests ?>
                                    </strong>
                                </div>

                            </div>

                            <div class="bk-summary-divider"></div>

                            <h3>Price Breakdown</h3>

                            <div class="bk-price-row">

                                <span>
                                    Room rate × <?= (int)$nights ?>
                                    night(s)
                                </span>

                                <strong>
                                    ₱<?= number_format(
                                        $guestReviewed
                                            ? $bookingBreakdown['room_subtotal_cents'] / 100
                                            : $previewRoomCents / 100,
                                        2
                                    ) ?>
                                </strong>

                            </div>

                            <div class="bk-price-row">

                                <span>
                                    Additional guest fee
                                </span>

                                <strong>
                                    ₱<?= number_format(
                                        $guestReviewed
                                            ? $bookingBreakdown['extra_guest_charge_cents'] / 100
                                            : $previewExtraCents / 100,
                                        2
                                    ) ?>
                                </strong>

                            </div>

                            <?php if ($guestReviewed): ?>

                                <div class="bk-price-row">

                                    <span>Optional add-ons</span>

                                    <strong>
                                        ₱<?= number_format(
                                            $bookingBreakdown['addon_subtotal_cents'] / 100,
                                            2
                                        ) ?>
                                    </strong>

                                </div>

                                <div class="bk-summary-divider"></div>

                                <div class="bk-price-row bk-total-row">

                                    <span>Total Booking Amount</span>

                                    <strong>
                                        ₱<?= number_format(
                                            $bookingBreakdown['total_cents'] / 100,
                                            2
                                        ) ?>
                                    </strong>

                                </div>

                                <?php if (
                                    $bookingBreakdown['downpayment_cents'] !== null
                                ): ?>

                                    <div class="bk-payment-breakdown">

                                        <div class="bk-price-row">

                                            <span>
                                                Required Downpayment
                                                (<?= escapeHTML(
                                                    $downpaymentPercentage
                                                ) ?>%)
                                            </span>

                                            <strong>
                                                ₱<?= number_format(
                                                    $bookingBreakdown['downpayment_cents'] / 100,
                                                    2
                                                ) ?>
                                            </strong>

                                        </div>

                                        <div class="bk-price-row">

                                            <span>Remaining Balance</span>

                                            <strong>
                                                ₱<?= number_format(
                                                    $bookingBreakdown['remaining_balance_cents'] / 100,
                                                    2
                                                ) ?>
                                            </strong>

                                        </div>

                                    </div>

                                <?php endif; ?>

                            <?php else: ?>

                                <div class="bk-summary-divider"></div>

                                <?php if ($previewFeeMissing): ?>

                                    <p class="bk-error">
                                        The additional guest fee needs
                                        to be configured.
                                    </p>

                                <?php else: ?>

                                    <div class="bk-price-row bk-total-row">

                                        <span>
                                            Amount Before Add-ons
                                        </span>

                                        <strong>
                                            ₱<?= number_format(
                                                ($previewRoomCents + $previewExtraCents) / 100,
                                                2
                                            ) ?>
                                        </strong>

                                    </div>

                                <?php endif; ?>

                                <p class="bk-small-note">
                                    The final total, add-ons, and
                                    required downpayment will be
                                    shown after reviewing your details.
                                </p>

                            <?php endif; ?>

                        </div>

                    </section>

                </aside>

            </div>

        <?php endif; ?>

    </div>

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

        <!-- ========================================
            BOOKING FEEDBACK MODAL
        ======================================== -->

        <?php

        $modalTitle = '';
        $modalMessage = '';

        // Determine which server message to display
        if (!empty($error)) {

            $modalTitle = 'Booking Error';
            $modalMessage = $error;

        } elseif (!empty($bookingSaveError)) {

            $modalTitle = 'Reservation Error';
            $modalMessage = $bookingSaveError;

        } elseif (!empty($guestError)) {

            $modalTitle = 'Validation Error';
            $modalMessage = $guestError;

        }

        ?>

        <dialog
            id="bookingFeedbackModal"
            class="bk-feedback-modal"
            data-auto-open="<?= $modalMessage !== '' ? 'true' : 'false' ?>"
            aria-labelledby="bkModalTitle"
            aria-describedby="bkModalMessage"
        >

            <div class="bk-feedback-content">

                <div class="bk-feedback-icon">
                    !
                </div>

                <h2 id="bkModalTitle">
                    <?= escapeHTML($modalTitle) ?>
                </h2>

                <p id="bkModalMessage">
                    <?= escapeHTML($modalMessage) ?>
                </p>

                <form method="dialog">

                    <button
                        type="submit"
                        class="bk-modal-button"
                    >
                        Okay
                    </button>

                </form>

            </div>

        </dialog>

        <script src="../assets/js/booking_modal.js?v=1" defer></script>
        <script src="../assets/js/navbar.js" defer></script>
</body>
</html>