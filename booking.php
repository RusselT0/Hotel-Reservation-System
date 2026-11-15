
<?php

require_once 'database.php';

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
// Set this only after confirming the hotel's policy.
$downpaymentPercentage = null;

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

// Process the guest information form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {

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

    <link rel="stylesheet" href="global_style.css">
    <link rel="stylesheet" href="landing.css?v=2">
    <link rel="stylesheet" href="booking.css?v=1">
    <link rel="stylesheet" href="responsive_landing.css">
</head>

<body>

<main style="max-width: 800px; margin: 50px auto; padding: 20px;">

    <h1>Complete Your Reservation</h1>

    <p>
        Review your selected room and reservation details.
    </p>

    <hr>

    <?php if ($error): ?>

        <p style="color: #b43e3e;">
            <?= escapeHTML($error) ?>
        </p>

        <a href="index.php">
            Return to Home
        </a>

    <?php else: ?>

        <h2>
            <?= escapeHTML($room['room_Code']) ?>
        </h2>

        <p>
            <strong>Room Type:</strong>
            <?= escapeHTML($room['room_Type']) ?>
        </p>

        <p>
            <strong>Check-in:</strong>
            <?= escapeHTML($checkin) ?>
        </p>

        <p>
            <strong>Check-out:</strong>
            <?= escapeHTML($checkout) ?>
        </p>

        <p>
            <strong>Number of Nights:</strong>
            <?= $nights ?>
        </p>

        <p>
            <strong>Number of Guests:</strong>
            <?= (int)$guests ?>
        </p>

        <p>
            <strong>Included Guests:</strong>
            <?= (int)$room['included_Guests'] ?>
        </p>

        <p>
            <strong>Maximum Capacity:</strong>
            <?= (int)$room['max_Guests'] ?>
        </p>

        <p>
            <strong>Base Nightly Rate:</strong>
            ₱<?= number_format(
                (float)$room['base_Price'],
                2
            ) ?>
        </p>

        <hr>

        
        <!-- GUEST INFORMATION SECTION -->

        <section class="guest-information">

            <h2>Guest Information</h2>

            <p>
                Enter your personal details to continue
                with your reservation.
            </p>

            <?php if ($guestError): ?>

                <p class="guest-error">
                    <?= escapeHTML($guestError) ?>
                </p>

            <?php endif; ?>

            <?php if ($guestReviewed): ?>

                <h3>Guest Details Reviewed</h3>

                <p>
                    <strong>Full Name:</strong>
                    <?= escapeHTML($guestData['first_Name']) ?>
                    <?= escapeHTML($guestData['last_Name']) ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?= escapeHTML($guestData['email_Address']) ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?= escapeHTML($guestData['phone_Number']) ?>
                </p>

                <p>
                    Your information has been validated,
                    but your reservation has not been saved yet.
                </p>

                <!-- BOOKING BREAKDOWN -->

                <section class="booking-breakdown">

                    <h3>Booking Summary</h3>

                    <p>
                        <strong>Room:</strong>
                        <?= escapeHTML($room['room_Code']) ?>
                    </p>

                    <p>
                        <strong>Number of Nights:</strong>
                        <?= (int)$nights ?>
                    </p>

                    <p>
                        <strong>Room Subtotal:</strong>
                        ₱<?= number_format(
                            $bookingBreakdown['room_subtotal_cents'] / 100,
                            2
                        ) ?>
                    </p>

                    <h4>Selected Add-ons</h4>

                    <?php if (empty($addonLines)): ?>

                        <p>No add-ons selected.</p>

                    <?php else: ?>

                        <?php foreach ($addonLines as $addon): ?>

                            <p>
                                <?= escapeHTML($addon['name']) ?>
                                × <?= (int)$addon['quantity'] ?>

                                —
                                ₱<?= number_format(
                                    $addon['line_total_cents'] / 100,
                                    2
                                ) ?>
                            </p>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    <p>
                        <strong>Add-ons Subtotal:</strong>
                        ₱<?= number_format(
                            $bookingBreakdown['addon_subtotal_cents'] / 100,
                            2
                        ) ?>
                    </p>

                    <!-- EXTRA GUEST CHARGES -->
                    <h4>Additional Guest Charges</h4>

                    <p>
                        <strong>Extra Guests:</strong>
                        <?= $bookingBreakdown['extra_guests'] ?>
                    </p>

                    <p>
                        <strong>Fixed Fee per Additional Guest:</strong>
                        ₱<?= number_format(
                            $bookingBreakdown['extra_guest_rate_cents'] / 100,
                            2
                        ) ?>
                    </p>

                    <p>
                        <strong>Total Extra Guest Charge:</strong>
                        ₱<?= number_format(
                            $bookingBreakdown['extra_guest_charge_cents'] / 100,
                            2
                        ) ?>
                    </p>

                    <hr>

                    <!-- FINAL TOTAL -->

                    <p class="booking-total">
                        <strong>Total Booking Amount:</strong>

                        ₱<?= number_format(
                            $bookingBreakdown['total_cents'] / 100,
                            2
                        ) ?>
                    </p>

                    <!-- DOWNPAYMENT -->

                    <?php if ($bookingBreakdown['downpayment_cents'] !== null): ?>

                        <p>
                            <strong>Required Downpayment:</strong>

                            ₱<?= number_format(
                                $bookingBreakdown['downpayment_cents'] / 100,
                                2
                            ) ?>
                        </p>

                        <p>
                            <strong>Remaining Balance:</strong>

                            ₱<?= number_format(
                                $bookingBreakdown['remaining_balance_cents'] / 100,
                                2
                            ) ?>
                        </p>

                    <?php else: ?>

                        <p class="booking-fee-notice">
                            Required downpayment is not yet configured.
                            The reservation cannot proceed to payment.
                        </p>

                    <?php endif; ?>

                    <p>
                        No reservation has been created yet.
                    </p>

                    <hr>
                    
                    <p>
                        The final total and required downpayment
                        will be calculated before saving the reservation.
                    </p>

                </section>

            <?php else: ?>

                <form method="POST"
                    class="guest-form"
                    action="booking.php?<?= escapeHTML(
                        http_build_query([
                            'room_id' => $roomID,
                            'checkin' => $checkin,
                            'checkout' => $checkout,
                            'guests' => $guests
                        ])
                    ) ?>">

                    <div class="guest-form-grid">

                        <div class="guest-form-group">
                            <label for="first_Name">First Name</label>

                            <input
                                type="text"
                                id="first_Name"
                                name="first_Name"
                                maxlength="80"
                                autocomplete="given-name"
                                value="<?= escapeHTML($guestData['first_Name']) ?>"
                                required>
                        </div>

                        <div class="guest-form-group">
                            <label for="last_Name">Last Name</label>

                            <input
                                type="text"
                                id="last_Name"
                                name="last_Name"
                                maxlength="80"
                                autocomplete="family-name"
                                value="<?= escapeHTML($guestData['last_Name']) ?>"
                                required>
                        </div>

                        <div class="guest-form-group">
                            <label for="email_Address">Email Address</label>

                            <input
                                type="email"
                                id="email_Address"
                                name="email_Address"
                                maxlength="190"
                                autocomplete="email"
                                value="<?= escapeHTML($guestData['email_Address']) ?>"
                                required>
                        </div>

                        <div class="guest-form-group">
                            <label for="phone_Number">Phone Number</label>

                            <input
                                type="tel"
                                id="phone_Number"
                                name="phone_Number"
                                maxlength="25"
                                autocomplete="tel"
                                placeholder="09XXXXXXXXX"
                                value="<?= escapeHTML($guestData['phone_Number']) ?>"
                                required>
                        </div>

                    </div>

                    <!-- OPTIONAL ADD-ONS -->
                    <div class="booking-addons">

                        <h3>Optional Add-ons</h3>

                        <p>
                            Enhance your stay by selecting additional services.
                            These are optional.
                        </p>

                        <?php if (empty($addons)): ?>

                            <p>No add-ons are currently available.</p>

                        <?php else: ?>

                            <?php foreach ($addons as $addon): ?>

                                <?php
                                    $addonID = (int)$addon['addon_ID'];
                                    $quantity = $selectedAddons[$addonID] ?? 0;
                                ?>

                                <div class="booking-addon-item">

                                    <div class="booking-addon-info">

                                        <strong>
                                            <?= escapeHTML($addon['addon_Name']) ?>
                                        </strong>

                                        <span>
                                            ₱<?= number_format(
                                                (float)$addon['addon_Price'],
                                                2
                                            ) ?> each
                                        </span>

                                    </div>

                                    <div class="booking-addon-quantity">

                                        <label for="addon_<?= $addonID ?>">
                                            Quantity
                                        </label>

                                        <input
                                            type="number"
                                            id="addon_<?= $addonID ?>"
                                            name="addons[<?= $addonID ?>]"
                                            min="0"
                                            max="10"
                                            value="<?= (int)$quantity ?>">

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                    <button type="submit" class="guest-continue-btn">
                        Review Guest Details
                    </button>

                </form>

            <?php endif; ?>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
