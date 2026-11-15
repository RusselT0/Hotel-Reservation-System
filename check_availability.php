
<?php

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Manila');

// Function for returning JSON responses
function sendResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, [
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

// Retrieve form values
$checkin  = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
$facility = $_POST['facility'] ?? '';
$guests   = $_POST['guests'] ?? '';

// Check whether values are valid strings
foreach ([$checkin, $checkout, $facility, $guests] as $value) {
    if (!is_string($value) || trim($value) === '') {
        sendResponse(400, [
            'success' => false,
            'message' => 'Please complete all fields.'
        ]);
    }
}

// Validate room type ID and number of guests
if (!ctype_digit($facility) || (int)$facility < 1) {
    sendResponse(400, [
        'success' => false,
        'message' => 'Invalid room/facility type.'
    ]);
}

$guestCount = filter_var(
    $guests,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($guestCount === false) {
    sendResponse(400, [
        'success' => false,
        'message' => 'Enter a valid number of guests.'
    ]);
}

// Validate dates using Philippine time
$timezone = new DateTimeZone('Asia/Manila');

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

if (
    !$checkinDate ||
    !$checkoutDate ||
    $checkinDate->format('Y-m-d') !== $checkin ||
    $checkoutDate->format('Y-m-d') !== $checkout
) {
    sendResponse(400, [
        'success' => false,
        'message' => 'Invalid check-in or checkout date.'
    ]);
}

$today = new DateTimeImmutable('today', $timezone);

if ($checkinDate < $today) {
    sendResponse(400, [
        'success' => false,
        'message' => 'Check-in cannot be in the past.'
    ]);
}

if ($checkoutDate <= $checkinDate) {
    sendResponse(400, [
        'success' => false,
        'message' => 'Checkout must be after check-in.'
    ]);
}

// Connect to MySQL
try {

    require_once __DIR__ . '/database.php';

    // Get hotel check-in and checkout times
    $settings = $pdo->query(
        "SELECT setting_Key, setting_Value
         FROM Hotel_Setting
         WHERE setting_Key IN ('checkin_time', 'checkout_time')"
    )->fetchAll(PDO::FETCH_KEY_PAIR);

    if (
        empty($settings['checkin_time']) ||
        empty($settings['checkout_time'])
    ) {
        throw new RuntimeException('Hotel schedule is not configured.');
    }

    $requestedCheckin =
        $checkin . ' ' . $settings['checkin_time'];

    $requestedCheckout =
        $checkout . ' ' . $settings['checkout_time'];

    // STEP A: Retrieve selected room type
    $stmt = $pdo->prepare(
        "SELECT
            room_Type_ID,
            room_Type,
            base_Price,
            included_Guests,
            max_Guests,
            extra_Person_Fee
        FROM Room_Type
        WHERE room_Type_ID = :type_id
        AND deleted_At IS NULL"
    );

    $stmt->execute([
        'type_id' => (int)$facility
    ]);

    $roomType = $stmt->fetch();

    if (!$roomType) {
        sendResponse(404, [
            'success' => false,
            'message' => 'Room type not found.'
        ]);
    }

    // STEP B: Check maximum guest capacity
    if ($guestCount > (int)$roomType['max_Guests']) {
        sendResponse(400, [
            'success' => false,
            'message' => 'Maximum capacity for ' .
                $roomType['room_Type'] . ' is ' .
                $roomType['max_Guests'] . ' guests.'
        ]);
    }

    // STEP C: Find rooms without overlapping bookings
    $sql = "
        SELECT
            r.room_ID,
            r.room_Code

        FROM Room r

        INNER JOIN Room_Status rs
            ON r.room_Status_ID = rs.room_Status_ID

        WHERE r.room_Type_ID = :type_id

        AND r.deleted_At IS NULL

        AND rs.ready_For_Checkin = 1

        AND NOT EXISTS (
            SELECT 1

            FROM Booking_Room br

            INNER JOIN Booking b
                ON br.booking_ID = b.booking_ID

            INNER JOIN Booking_Status bs
                ON b.booking_Status_ID = bs.booking_Status_ID

            WHERE br.room_ID = r.room_ID

            AND br.deleted_At IS NULL

            AND bs.blocks_Availability = 1

            AND (
                bs.requires_Hold_Expiry_Check = 0
                OR b.hold_Expires_At IS NULL
                OR b.hold_Expires_At > NOW()
            )

            AND b.checkin_Date < :checkout_date

            AND b.checkout_Date > :checkin_date
        )

        ORDER BY r.room_Code ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'type_id' => (int)$facility,
        'checkin_date' => $requestedCheckin,
        'checkout_date' => $requestedCheckout
    ]);

    $availableRooms = $stmt->fetchAll();

    // STEP D: Return results
        sendResponse(200, [
        'success' => true,
        'room_type' => $roomType['room_Type'],
        'checkin' => $checkin,
        'checkout' => $checkout,
        'guests' => $guestCount,

        // Room pricing and capacity
        'nightly_rate' => $roomType['base_Price'],
        'included_guests' => (int)$roomType['included_Guests'],
        'max_guests' => (int)$roomType['max_Guests'],
        'extra_person_fee' => $roomType['extra_Person_Fee'],

        // Available rooms
        'available_count' => count($availableRooms),
        'rooms' => $availableRooms
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    sendResponse(500, [
        'success' => false,
        'message' => 'Unable to check availability right now.'
    ]);
}
