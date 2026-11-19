<?php

require_once __DIR__ . '/../includes/auth_session.php';

// ========================================
// 1. RETRIEVE SUCCESSFUL BOOKING DETAILS
// ========================================

$booking = $_SESSION['booking_success'] ?? null;

// Prevent opening this page without
// a successful reservation submission.
if (!is_array($booking)) {

    header('Location: ../index.php');
    exit;
}

// ========================================
// 2. ESCAPE OUTPUT SAFELY
// ========================================

function e($value): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ========================================
// PAYMENT FORM DATA
// ========================================

require_once __DIR__ . '/../includes/database.php';
$currentUser = authCurrentUser($pdo);

// One-time security token for payment submission
if (empty($_SESSION['payment_token'])) {
    $_SESSION['payment_token'] = bin2hex(
        random_bytes(32)
    );
}

// Retrieve payment feedback
$paymentMessage = $_SESSION['payment_message'] ?? null;
$paymentError = $_SESSION['payment_error'] ?? null;

unset(
    $_SESSION['payment_message'],
    $_SESSION['payment_error']
);

// Get the CURRENT booking status from MySQL
$stmt = $pdo->prepare("
    SELECT
        b.booking_ID,
        b.required_Downpayment,
        b.hold_Expires_At,
        bs.booking_Status_Name,
        (
            b.hold_Expires_At IS NOT NULL
            AND b.hold_Expires_At > NOW()
        ) AS hold_active
    FROM Booking b
    JOIN Booking_Status bs
        ON b.booking_Status_ID = bs.booking_Status_ID
    WHERE b.booking_ID = ?
      AND b.deleted_At IS NULL
");

$stmt->execute([
    (int)$booking['booking_id']
]);

$currentBooking = $stmt->fetch(PDO::FETCH_ASSOC);

$canSubmitPayment = (
    $currentBooking &&
    $currentBooking['booking_Status_Name'] === 'Pending Payment' &&
    (int)$currentBooking['hold_active'] === 1
);

// Retrieve permitted online/manual payment methods
$methodStmt = $pdo->query("
    SELECT payment_Method_ID, payment_Method_Name
    FROM Payment_Method
    WHERE is_Active = 1
      AND payment_Method_Name IN ('GCash', 'Bank Transfer')
    ORDER BY payment_Method_Name
");

$paymentMethods = $methodStmt->fetchAll(
    PDO::FETCH_ASSOC
);

// ========================================
// ROOM INFORMATION FOR BOOKING SUMMARY
// ========================================

$roomStmt = $pdo->prepare("
    SELECT
        r.room_Code,
        rt.room_Type,
        b.checkin_Date,
        b.checkout_Date,
        br.num_Guest

    FROM Booking b

    JOIN Booking_Room br
        ON b.booking_ID = br.booking_ID

    JOIN Room r
        ON br.room_ID = r.room_ID

    JOIN Room_Type rt
        ON r.room_Type_ID = rt.room_Type_ID

    WHERE b.booking_ID = ?
      AND b.deleted_At IS NULL
      AND br.deleted_At IS NULL

    LIMIT 1
");

$roomStmt->execute([
    (int)$booking['booking_id']
]);

$roomInfo = $roomStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Match the existing room images
$roomImages = [
    'Standard Room' => '../assets/images/standard room.jfif',
    'Family Room' => '../assets/images/family room.webp',
    'Event Hall' => '../assets/images/event hall.jfif'
];

$roomType = $roomInfo['room_Type'] ?? 'Selected Room';

$roomImage = $roomImages[$roomType]
    ?? '../assets/images/hero.webp';

// ========================================
// CURRENT PAYMENT AND BOOKING STATUS
// ========================================

$currentStatus = $currentBooking['booking_Status_Name']
    ?? 'Unavailable';

$holdExpired = (
    $currentStatus === 'Pending Payment' &&
    !$canSubmitPayment
);

$totalCents = (int)($booking['total_cents'] ?? 0);

$depositCents = (int)(
    $booking['deposit_cents'] ?? 0
);

$balanceCents = max(
    0,
    $totalCents - $depositCents
);

// ========================================
// MODAL FEEDBACK
// ========================================

$feedbackTitle = '';
$feedbackMessage = '';
$feedbackType = 'error';

if (!empty($paymentError)) {

    $feedbackTitle = 'Payment Error';
    $feedbackMessage = $paymentError;

} elseif (!empty($paymentMessage)) {

    $feedbackTitle = 'Payment Details Submitted';
    $feedbackMessage = $paymentMessage;
    $feedbackType = 'success';

} elseif (!$currentBooking) {

    $feedbackTitle = 'Reservation Error';
    $feedbackMessage =
        'The reservation could not be found.';

} elseif ($holdExpired) {

    $feedbackTitle = 'Payment Hold Expired';
    $feedbackMessage =
        'The payment submission deadline has passed. ' .
        'This reservation can no longer submit payment details.';

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reservation Pending | Bella Vista Hotel
    </title>

    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/landing.css?v=4">
    <link rel="stylesheet" href="../assets/css/booking_created.css?v=3">
    <link rel="stylesheet" href="../assets/css/responsive_landing.css?v=18">
</head>

<body>

<!-- NAVIGATION -->
<header class="booking-created-header">
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
                    <?php if ($currentUser): ?>
                        <a href="../<?= authEscape(authDashboardPath($currentUser['role'])) ?>" class="nav-account-link" title="Your dashboard"><?= authEscape($currentUser['first_name']) ?></a>
                    <?php else: ?>
                        <a href="login.php">Sign In / Register</a>
                    <?php endif; ?>
                </div>

        </nav>
</header>

<!-- ========================================
     BOOKING CREATED - MAIN CONTENT
======================================== -->

<main class="bc-page">

    <div class="bc-container">

        <!-- PAGE HEADING -->

        <div class="bc-heading">

            <p class="bc-eyebrow">
                BELLA VISTA HOTEL & EVENTS PLACE
            </p>

            <h1>Reservation Request Submitted</h1>

            <p>
                Review your reservation and complete
                the simulated downpayment process.
            </p>

        </div>

        <div class="bc-layout">

            <!-- ========================================
                 LEFT COLUMN
            ======================================== -->

            <div class="bc-main-column">

                <!-- RESERVATION STATUS -->

                <section class="bc-card">

                    <div class="bc-section-heading">

                        <div class="bc-heading-icon">
                            01
                        </div>

                        <div>
                            <h2>Reservation Status</h2>

                            <p>
                                Current status of your booking
                            </p>
                        </div>

                    </div>

                    <div class="bc-status-panel">

                        <span class="bc-status-label">
                            Current Status
                        </span>

                        <strong class="bc-status-value">
                            <?= e($currentStatus) ?>
                        </strong>

                    </div>

                    <div class="bc-info-row">

                        <span>Booking Reference</span>

                        <strong>
                            #<?= (int)$booking['booking_id'] ?>
                        </strong>

                    </div>

                    <?php if ($currentStatus === 'Pending Payment'): ?>

                        <div class="bc-info-row">

                            <span>Payment Hold Deadline</span>

                            <strong>
                                <?= e(
                                    $currentBooking['hold_Expires_At']
                                    ?? $booking['hold_until']
                                    ?? 'Unavailable'
                                ) ?>
                            </strong>

                        </div>

                        <p class="bc-hint">
                            Philippine Standard Time (UTC+08:00)
                        </p>

                    <?php endif; ?>

                </section>

                <!-- ========================================
                     DOWNPAYMENT SUBMISSION
                ======================================== -->

                <?php if ($canSubmitPayment): ?>

                    <section class="bc-card">

                        <div class="bc-section-heading">

                            <div class="bc-heading-icon">
                                02
                            </div>

                            <div>
                                <h2>Downpayment Submission</h2>

                                <p>
                                    Submit your simulated payment
                                    details before the hold expires.
                                </p>
                            </div>

                        </div>

                        <div class="bc-amount-panel">

                            <span>Required Downpayment (30%)</span>

                            <strong>
                                ₱<?= number_format(
                                    (float)$currentBooking['required_Downpayment'],
                                    2
                                ) ?>
                            </strong>

                        </div>

                        <?php if (!empty($paymentMethods)): ?>

                            <form
                                method="POST"
                                action="../actions/submit_downpayment.php"
                                class="bc-payment-form"
                                id="bcPaymentForm"
                                novalidate
                            >

                                <!-- SECURITY TOKEN -->

                                <input
                                    type="hidden"
                                    name="payment_token"
                                    value="<?= e(
                                        $_SESSION['payment_token']
                                    ) ?>"
                                >

                                <!-- PAYMENT METHOD -->

                                <div class="bc-field">

                                    <label for="bcPaymentMethod">
                                        Payment Method
                                    </label>

                                    <select
                                        id="bcPaymentMethod"
                                        name="payment_method_id"
                                        required
                                    >

                                        <option value="">
                                            Select Payment Method
                                        </option>

                                        <?php foreach (
                                            $paymentMethods as $method
                                        ): ?>

                                            <option
                                                value="<?= (int)$method['payment_Method_ID'] ?>"
                                            >
                                                <?= e(
                                                    $method['payment_Method_Name']
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>

                                <!-- REFERENCE NUMBER -->

                                <div class="bc-field">

                                    <label for="bcReference">
                                        Transaction Reference Number
                                    </label>

                                    <input
                                        type="text"
                                        id="bcReference"
                                        name="reference_no"
                                        minlength="6"
                                        maxlength="120"
                                        pattern="[A-Za-z0-9 -]{6,120}"
                                        placeholder="Enter test transaction reference"
                                        required
                                    >

                                    <span class="bc-field-hint">
                                        Example: GCASH20261116001
                                    </span>

                                </div>

                                <!-- SIMULATION NOTICE -->

                                <div class="bc-simulation-notice">

                                    <strong>
                                        Payment Simulation
                                    </strong>

                                    <p>
                                        This feature is for academic
                                        demonstration only. No real
                                        money is transferred. Use a
                                        fictional transaction reference
                                        for testing.
                                    </p>

                                </div>

                                <button
                                    type="submit"
                                    class="bc-primary-btn"
                                >
                                    Submit Simulated Payment
                                </button>

                            </form>

                        <?php endif; ?>

                    </section>

                <?php elseif ($currentStatus === 'Pending Approval'): ?>

                    <!-- WAITING FOR ADMIN VERIFICATION -->

                    <section class="bc-card">

                        <div class="bc-section-heading">

                            <div class="bc-heading-icon">
                                02
                            </div>

                            <div>
                                <h2>Awaiting Admin Verification</h2>

                                <p>
                                    Simulated payment details
                                    have been submitted.
                                </p>
                            </div>

                        </div>

                        <div class="bc-status-panel">

                            <strong>
                                Pending Approval
                            </strong>

                            <p>
                                The administrator will review
                                the submitted payment reference
                                before confirming the reservation.
                            </p>

                        </div>

                    </section>

                <?php elseif ($holdExpired): ?>

                    <!-- EXPIRED PAYMENT HOLD -->

                    <section class="bc-card">

                        <div class="bc-section-heading">

                            <div class="bc-heading-icon">
                                02
                            </div>

                            <div>
                                <h2>Payment Submission Unavailable</h2>

                                <p>
                                    Your payment hold has expired.
                                </p>
                            </div>

                        </div>

                        <a
                            href="../index.php"
                            class="bc-secondary-link"
                        >
                            Search for Another Room
                        </a>

                    </section>

                <?php endif; ?>

            </div>

            <!-- ========================================
                 RIGHT COLUMN - BOOKING SUMMARY
            ======================================== -->

            <aside class="bc-summary-column">

                <section class="bc-summary-card">

                    <!-- ROOM IMAGE -->

                    <img
                        src="<?= e($roomImage) ?>"
                        alt="<?= e($roomType) ?>"
                        class="bc-room-image"
                    >

                    <div class="bc-summary-content">

                        <span class="bc-summary-eyebrow">
                            YOUR SELECTED ACCOMMODATION
                        </span>

                        <h2>
                            <?= e($roomType) ?>
                        </h2>

                        <p class="bc-room-code">
                            <?= e(
                                $roomInfo['room_Code']
                                ?? $booking['room_code']
                            ) ?>
                        </p>

                        <div class="bc-divider"></div>

                        <!-- RESERVATION DETAILS -->

                        <h3>Reservation Details</h3>

                        <div class="bc-details-grid">

                            <div>

                                <span>Check-in</span>

                                <strong>
                                    <?php if (
                                        !empty($roomInfo['checkin_Date'])
                                    ): ?>

                                        <?= e(date(
                                            'M j, Y',
                                            strtotime(
                                                $roomInfo['checkin_Date']
                                            )
                                        )) ?>

                                    <?php else: ?>
                                        Unavailable
                                    <?php endif; ?>
                                </strong>

                            </div>

                            <div>

                                <span>Check-out</span>

                                <strong>
                                    <?php if (
                                        !empty($roomInfo['checkout_Date'])
                                    ): ?>

                                        <?= e(date(
                                            'M j, Y',
                                            strtotime(
                                                $roomInfo['checkout_Date']
                                            )
                                        )) ?>

                                    <?php else: ?>
                                        Unavailable
                                    <?php endif; ?>
                                </strong>

                            </div>

                            <div>

                                <span>Guests</span>

                                <strong>
                                    <?= (int)(
                                        $roomInfo['num_Guest'] ?? 0
                                    ) ?>
                                </strong>

                            </div>

                            <div>

                                <span>Booking Reference</span>

                                <strong>
                                    #<?= (int)$booking['booking_id'] ?>
                                </strong>

                            </div>

                        </div>

                        <div class="bc-divider"></div>

                        <!-- PAYMENT BREAKDOWN -->

                        <h3>Payment Breakdown</h3>

                        <div class="bc-price-row">

                            <span>Total Booking Amount</span>

                            <strong>
                                ₱<?= number_format(
                                    $totalCents / 100,
                                    2
                                ) ?>
                            </strong>

                        </div>

                        <div class="bc-price-row bc-deposit-row">

                            <span>Required Downpayment (30%)</span>

                            <strong>
                                ₱<?= number_format(
                                    $depositCents / 100,
                                    2
                                ) ?>
                            </strong>

                        </div>

                        <div class="bc-price-row">

                            <span>Remaining Balance</span>

                            <strong>
                                ₱<?= number_format(
                                    $balanceCents / 100,
                                    2
                                ) ?>
                            </strong>

                        </div>

                        <div class="bc-divider"></div>

                        <p class="bc-summary-note">
                            Your reservation will only be
                            confirmed after the simulated
                            payment is reviewed and approved
                            by an administrator.
                        </p>

                    </div>

                </section>

            </aside>

        </div>

        <!-- RETURN LINK -->

        <div class="bc-bottom-links">

            <a href="../index.php">
                Return to Home
            </a>

        </div>

    </div>

</main>

<!-- ========================================
     PAYMENT FEEDBACK MODAL
======================================== -->

<dialog
    id="bcFeedbackModal"
    class="bc-feedback-modal"
    data-auto-open="<?= $feedbackMessage !== '' ? 'true' : 'false' ?>"
    aria-labelledby="bcModalTitle"
    aria-describedby="bcModalMessage"
>

    <div class="bc-modal-content">

        <div
            class="bc-modal-icon <?= $feedbackType === 'success'
                ? 'bc-modal-success'
                : 'bc-modal-error' ?>"
        >
            <?= $feedbackType === 'success' ? '✓' : '!' ?>
        </div>

        <h2 id="bcModalTitle">
            <?= e($feedbackTitle) ?>
        </h2>

        <p id="bcModalMessage">
            <?= e($feedbackMessage) ?>
        </p>

        <form method="dialog">

            <button
                type="submit"
                class="bc-modal-btn"
            >
                Okay
            </button>

        </form>

    </div>

</dialog>

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

        <script src="../assets/js/navbar.js?v=2" defer></script>
        <script src="../assets/js/booking_created.js?v=1" defer></script>
</body>
</html>
