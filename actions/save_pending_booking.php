<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';

// ========================================
// 1. ACCEPT POST REQUESTS ONLY
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// ========================================
// 2. VALIDATE THE BOOKING REVIEW SESSION
// ========================================

$review = $_SESSION['booking_review'] ?? null;
$postedToken = $_POST['review_token'] ?? null;

if (
    !is_array($review) ||
    !is_string($postedToken) ||
    !isset($review['token']) ||
    !hash_equals((string)$review['token'], $postedToken) ||
    time() - (int)($review['created_at'] ?? 0) > 900
) {
    http_response_code(403);
    exit(
        'Your booking review has expired. ' .
        'Please review the details again.'
    );
}

// Retrieve previously validated booking data
$roomID = filter_var(
    $review['room_id'] ?? null,
    FILTER_VALIDATE_INT
);

$guests = filter_var(
    $review['guests'] ?? null,
    FILTER_VALIDATE_INT
);

$checkin = $review['checkin'] ?? null;
$checkout = $review['checkout'] ?? null;

$guest = $review['guest'] ?? null;
$selections = $review['addons'] ?? null;
$reviewGuestID = $review['auth_guest_id'] ?? null;

try {

    // ========================================
    // 3. VALIDATE RESERVATION INFORMATION
    // ========================================

    if (
        !$roomID ||
        !$guests ||
        $guests < 1 ||
        !is_string($checkin) ||
        !is_string($checkout) ||
        !is_array($guest) ||
        !is_array($selections) ||
        ($review['terms_accepted'] ?? false) !== true
    ) {
        throw new RuntimeException(
            'Invalid booking review. Please start again.'
        );
    }

    // Validate guest details again
    foreach (
        ['first_Name', 'last_Name',
         'email_Address', 'phone_Number'] as $field
    ) {

        if (
            !isset($guest[$field]) ||
            !is_string($guest[$field]) ||
            trim($guest[$field]) === ''
        ) {
            throw new RuntimeException(
                'Guest information is incomplete.'
            );
        }

        $guest[$field] = trim($guest[$field]);
    }

    if (
        strlen($guest['first_Name']) > 80 ||
        strlen($guest['last_Name']) > 80 ||
        strlen($guest['email_Address']) > 190 ||
        !filter_var(
            $guest['email_Address'],
            FILTER_VALIDATE_EMAIL
        ) ||
        strlen($guest['phone_Number']) > 25 ||
        !preg_match(
            '/^\+?[0-9][0-9\s-]{6,24}$/',
            $guest['phone_Number']
        )
    ) {
        throw new RuntimeException(
            'Guest information is invalid.'
        );
    }

    // ========================================
    // 4. VALIDATE BOOKING DATES
    // ========================================

    $tz = new DateTimeZone('Asia/Manila');

    $checkinDay = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $checkin,
        $tz
    );

    $checkoutDay = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $checkout,
        $tz
    );

    $today = new DateTimeImmutable('today', $tz);

    if (
        !$checkinDay ||
        !$checkoutDay ||
        $checkinDay->format('Y-m-d') !== $checkin ||
        $checkoutDay->format('Y-m-d') !== $checkout ||
        $checkinDay < $today ||
        $checkoutDay <= $checkinDay
    ) {
        throw new RuntimeException(
            'The selected dates are no longer valid.'
        );
    }

    $nights = (int)$checkinDay
        ->diff($checkoutDay)->days;

    // ========================================
    // 5. START DATABASE TRANSACTION
    // ========================================

    $pdo->beginTransaction();

    // ========================================
    // 6. RETRIEVE HOTEL SETTINGS
    // ========================================

    $configStmt = $pdo->query("
        SELECT setting_Key, setting_Value
        FROM Hotel_Setting
        WHERE setting_Key IN (
            'downpayment_percent',
            'pending_hold_minutes',
            'checkin_time',
            'checkout_time'
        )
    ");

    $settings = $configStmt->fetchAll(
        PDO::FETCH_KEY_PAIR
    );

    $percentageRaw =
        $settings['downpayment_percent'] ?? null;

    $holdRaw =
        $settings['pending_hold_minutes'] ?? null;

    $checkinTime =
        $settings['checkin_time'] ?? null;

    $checkoutTime =
        $settings['checkout_time'] ?? null;

    if (
        !is_numeric($percentageRaw) ||
        (float)$percentageRaw <= 0 ||
        (float)$percentageRaw > 100 ||
        !is_string($holdRaw) ||
        !ctype_digit($holdRaw) ||
        (int)$holdRaw < 1 ||
        !is_string($checkinTime) ||
        !preg_match(
            '/^\d{2}:\d{2}:\d{2}$/',
            $checkinTime
        ) ||
        !is_string($checkoutTime) ||
        !preg_match(
            '/^\d{2}:\d{2}:\d{2}$/',
            $checkoutTime
        )
    ) {
        throw new RuntimeException(
            'Hotel booking settings are incomplete.'
        );
    }

    $checkinAt = $checkin . ' ' . $checkinTime;
    $checkoutAt = $checkout . ' ' . $checkoutTime;

    // ========================================
    // 7. LOCK AND RETRIEVE SELECTED ROOM
    // ========================================

    $stmt = $pdo->prepare("
        SELECT
            r.room_ID,
            r.room_Type_ID,
            r.room_Code,
            rs.ready_For_Checkin,
            rt.base_Price,
            rt.included_Guests,
            rt.max_Guests,
            rt.extra_Person_Fee

        FROM Room r

        JOIN Room_Type rt
            ON rt.room_Type_ID = r.room_Type_ID

        JOIN Room_Status rs
            ON rs.room_Status_ID = r.room_Status_ID

        WHERE r.room_ID = ?
          AND r.deleted_At IS NULL
          AND rt.deleted_At IS NULL
          AND rs.is_Active = 1

        FOR UPDATE
    ");

    $stmt->execute([$roomID]);

    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$room ||
        (int)$room['ready_For_Checkin'] !== 1
    ) {
        throw new RuntimeException(
            'This room is no longer ready for booking.'
        );
    }

    if ($guests > (int)$room['max_Guests']) {
        throw new RuntimeException(
            'The selected room cannot accommodate this guest count.'
        );
    }

    // ========================================
    // 8. CHECK FOR OVERLAPPING BOOKINGS
    // ========================================

    $conflict = $pdo->prepare("
        SELECT b.booking_ID

        FROM Booking_Room br

        JOIN Booking b
            ON b.booking_ID = br.booking_ID

        JOIN Booking_Status bs
            ON bs.booking_Status_ID = b.booking_Status_ID

        WHERE br.room_ID = :room_id
          AND br.deleted_At IS NULL
          AND b.deleted_At IS NULL

          AND bs.blocks_Availability = 1

          AND (
              bs.requires_Hold_Expiry_Check = 0
              OR b.hold_Expires_At IS NULL
              OR b.hold_Expires_At > NOW()
          )

          AND b.checkin_Date < :checkout_at
          AND b.checkout_Date > :checkin_at

        LIMIT 1
        FOR UPDATE
    ");

    $conflict->execute([
        'room_id' => $roomID,
        'checkin_at' => $checkinAt,
        'checkout_at' => $checkoutAt
    ]);

    if ($conflict->fetch()) {
        throw new RuntimeException(
            'Another guest has already reserved ' .
            'this room for those dates.'
        );
    }

    // ========================================
    // 9. RECALCULATE ROOM CHARGES
    // ========================================

    $extraGuests = max(
        0,
        $guests - (int)$room['included_Guests']
    );

    $nightlyCents = (int)round(
        (float)$room['base_Price'] * 100
    );

    $extraRateCents = (int)round(
        (float)$room['extra_Person_Fee'] * 100
    );

    if ($extraGuests > 0 && $extraRateCents <= 0) {
        throw new RuntimeException(
            'The fixed extra-person fee needs to be ' .
            'configured before booking this guest count.'
        );
    }

    // ========================================
    // 10. RECALCULATE SELECTED ADD-ONS
    // ========================================

    $addonsToSave = [];
    $addonsCents = 0;

    $addonStmt = $pdo->prepare("
        SELECT a.addon_ID, a.addon_Price
        FROM addon a
        JOIN room_type_addon rta ON rta.addon_ID = a.addon_ID
        WHERE a.addon_ID = ?
          AND rta.room_Type_ID = ?
          AND rta.deleted_At IS NULL
          AND a.deleted_At IS NULL
        FOR UPDATE
    ");

    foreach ($selections as $addonID => $quantity) {

        $id = filter_var(
            $addonID,
            FILTER_VALIDATE_INT
        );

        $qty = filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        );

        if (
            !$id ||
            $qty === false ||
            $qty < 1 ||
            $qty > 10
        ) {
            throw new RuntimeException(
                'Invalid add-on selection.'
            );
        }

        $addonStmt->execute([$id, (int)$room['room_Type_ID']]);

        $addon = $addonStmt->fetch(PDO::FETCH_ASSOC);

        if (!$addon) {
            throw new RuntimeException(
                'One of the selected add-ons ' .
                'is no longer available for the selected room type.'
            );
        }

        $priceCents = (int)round(
            (float)$addon['addon_Price'] * 100
        );

        $addonsCents += $priceCents * $qty;

        $addonsToSave[] = [
            'id' => $id,
            'qty' => $qty,
            'unit_cents' => $priceCents
        ];
    }

    // ========================================
    // 11. CALCULATE TOTAL AND DOWNPAYMENT
    // ========================================

    $totalCents =
        ($nightlyCents * $nights) +
        ($extraRateCents * $extraGuests) +
        $addonsCents;

    $depositCents = (int)round(
        $totalCents * ((float)$percentageRaw / 100)
    );

    if ($totalCents < 1 || $depositCents < 1) {
        throw new RuntimeException(
            'Invalid booking amount.'
        );
    }

    // ========================================
    // 12. RESOLVE VERIFIED GUEST IDENTITY
    // ========================================

    // Revalidate the session and lock the live account before saving.
    $hadAccountSession = isset($_SESSION['auth_user']);
    $currentUser = authCurrentUser($pdo, true);
    $currentGuest = $currentUser && $currentUser['role'] === 'Guest'
        ? $currentUser
        : null;
    $currentGuestID = $currentGuest['user_id'] ?? null;

    if (($hadAccountSession && !$currentUser) || $reviewGuestID !== $currentGuestID) {
        unset($_SESSION['booking_review']);
        throw new RuntimeException('Your account session changed. Please review the booking again.');
    }

    $userID = null;

    if ($currentGuest) {
        // The reviewed contact details must still match the authenticated account.
        if ($guest['first_Name'] !== trim((string)$currentGuest['first_name']) ||
            $guest['last_Name'] !== trim((string)$currentGuest['last_name']) ||
            $guest['email_Address'] !== trim((string)$currentGuest['email']) ||
            $guest['phone_Number'] !== trim((string)$currentGuest['phone_number'])) {
            unset($_SESSION['booking_review']);
            throw new RuntimeException('Your account details changed. Please review the booking again.');
        }
        $userID = $currentGuestID;
    } else {
        // Anonymous visitors may book with a new email, but cannot claim an existing account.
        $existingStmt = $pdo->prepare("
            SELECT user_ID
            FROM Users
            WHERE email_Address = ?
            LIMIT 1
            FOR UPDATE
        ");

        $existingStmt->execute([$guest['email_Address']]);

        if ($existingStmt->fetch()) {
            throw new RuntimeException(
                'This email has an existing record. ' .
                'Verified sign-in or email confirmation ' .
                'is required to reuse it.'
            );
        }
    }

    // ========================================
    // 13. RETRIEVE REQUIRED STATUS IDS
    // ========================================

    $roleID = $pdo->query("
        SELECT role_ID
        FROM User_Role
        WHERE role_Name = 'Guest'
          AND is_Active = 1
        LIMIT 1
    ")->fetchColumn();

    $accountStatusID = $pdo->query("
        SELECT account_Status_ID
        FROM Account_Status
        WHERE account_Status_Name = 'Active'
          AND is_Active = 1
        LIMIT 1
    ")->fetchColumn();

    $bookingStatusID = $pdo->query("
        SELECT booking_Status_ID
        FROM Booking_Status
        WHERE booking_Status_Name = 'Pending Payment'
          AND blocks_Availability = 1
          AND requires_Hold_Expiry_Check = 1
          AND is_Active = 1
        LIMIT 1
    ")->fetchColumn();

    if (
        !$roleID ||
        !$accountStatusID ||
        !$bookingStatusID
    ) {
        throw new RuntimeException(
            'The required guest or booking statuses ' .
            'are not configured.'
        );
    }

    // ========================================
    // 14. INSERT ONLY NEW ANONYMOUS GUESTS
    // ========================================

    if ($userID === null) {
        $userInsert = $pdo->prepare("
            INSERT INTO Users (
                role_ID,
                account_Status_ID,
                first_Name,
                last_Name,
                email_Address,
                phone_Number,
                password_Hash
            )
            VALUES (?, ?, ?, ?, ?, ?, NULL)
        ");

        $userInsert->execute([
            $roleID,
            $accountStatusID,
            $guest['first_Name'],
            $guest['last_Name'],
            $guest['email_Address'],
            $guest['phone_Number']
        ]);

        $userID = (int)$pdo->lastInsertId();
    }

    // ========================================
    // 15. CALCULATE 30-MINUTE HOLD EXPIRY
    // ========================================

    $holdUntil = (
        new DateTimeImmutable('now', $tz)
    )->modify(
        '+' . (int)$holdRaw . ' minutes'
    )->format('Y-m-d H:i:s');

    // ========================================
    // 16. INSERT PENDING BOOKING
    // ========================================

    $bookingInsert = $pdo->prepare("
        INSERT INTO Booking (
            user_ID,
            booking_Status_ID,
            checkin_Date,
            checkout_Date,
            booking_Amount,
            required_Downpayment,
            hold_Expires_At
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $bookingInsert->execute([
        $userID,
        $bookingStatusID,
        $checkinAt,
        $checkoutAt,
        number_format(
            $totalCents / 100, 2, '.', ''
        ),
        number_format(
            $depositCents / 100, 2, '.', ''
        ),
        $holdUntil
    ]);

    $bookingID = (int)$pdo->lastInsertId();

    // ========================================
    // 17. INSERT BOOKING ROOM DETAILS
    // ========================================

    $roomInsert = $pdo->prepare("
        INSERT INTO Booking_Room (
            booking_ID,
            room_ID,
            num_Guest,
            nightly_Rate,
            extra_Guest_Fee,
            included_Guests_Snapshot
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $roomInsert->execute([
        $bookingID,
        $roomID,
        $guests,
        number_format(
            $nightlyCents / 100, 2, '.', ''
        ),
        number_format(
            $extraRateCents / 100, 2, '.', ''
        ),
        (int)$room['included_Guests']
    ]);

    // ========================================
    // 18. INSERT SELECTED ADD-ONS
    // ========================================

    $addonInsert = $pdo->prepare("
        INSERT INTO Booking_Addon (
            booking_ID,
            addon_ID,
            quantity,
            unit_Price
        )
        VALUES (?, ?, ?, ?)
    ");

    foreach ($addonsToSave as $addon) {

        $addonInsert->execute([
            $bookingID,
            $addon['id'],
            $addon['qty'],
            number_format(
                $addon['unit_cents'] / 100,
                2,
                '.',
                ''
            )
        ]);
    }

    // ========================================
    // 19. RECORD THE BOOKING ACTION
    // ========================================

    $logInsert = $pdo->prepare("
        INSERT INTO Booking_Log (
            booking_ID,
            user_ID,
            booking_Status_ID,
            action_Type,
            remarks
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $logInsert->execute([
        $bookingID,
        $userID,
        $bookingStatusID,
        'Created',
        'Pending downpayment.'
    ]);

    // ========================================
    // 20. COMMIT ALL DATABASE CHANGES
    // ========================================

    $pdo->commit();

    // Remove the review so it cannot be submitted again
    unset($_SESSION['booking_review']);

    // Store result for the confirmation screen
    $_SESSION['booking_success'] = [
        'booking_id' => $bookingID,
        'room_code' => $room['room_Code'],
        'total_cents' => $totalCents,
        'deposit_cents' => $depositCents,
        'hold_until' => $holdUntil
    ];

    header('Location: ../pages/booking_created.php', true, 303);
    exit;

} catch (Throwable $e) {

    // ========================================
    // 21. ROLLBACK ON ERROR
    // ========================================

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Booking save failed: ' . $e->getMessage()
    );

    $message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Unable to save the reservation. Please try again.';

    $_SESSION['booking_save_error'] = $message;

    header(
        'Location: ../pages/booking.php?' .
        http_build_query([
            'room_id' => $review['room_id'],
            'checkin' => $review['checkin'],
            'checkout' => $review['checkout'],
            'guests' => $review['guests']
        ]),
        true,
        303
    );
    exit;
}
