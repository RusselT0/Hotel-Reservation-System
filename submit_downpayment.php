
<?php

session_start();

require_once __DIR__ . '/database.php';

// ========================================
// 1. ALLOW POST REQUESTS ONLY
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// ========================================
// 2. VERIFY BOOKING SESSION
// ========================================

$booking = $_SESSION['booking_success'] ?? null;

if (
    !is_array($booking) ||
    empty($booking['booking_id'])
) {
    http_response_code(403);
    exit('Booking session not found.');
}

// ========================================
// 3. VERIFY FORM SECURITY TOKEN
// ========================================

$sessionToken = $_SESSION['payment_token'] ?? null;
$postedToken = $_POST['payment_token'] ?? null;

if (
    !is_string($sessionToken) ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    http_response_code(403);
    exit('Invalid payment submission token.');
}

// ========================================
// 4. READ AND VALIDATE FORM INPUTS
// ========================================

$bookingID = (int)$booking['booking_id'];

$paymentMethodID = filter_var(
    $_POST['payment_method_id'] ?? null,
    FILTER_VALIDATE_INT
);

$referenceInput = $_POST['reference_no'] ?? '';

$referenceNo = is_string($referenceInput)
    ? trim($referenceInput)
    : '';

if (
    !$paymentMethodID ||
    !preg_match('/^[A-Za-z0-9 -]{6,120}$/', $referenceNo)
) {
    $_SESSION['payment_error'] =
        'Please select a valid payment method and ' .
        'enter a valid transaction reference.';

    header('Location: booking_created.php', true, 303);
    exit;
}

try {

    // ========================================
    // 5. BEGIN DATABASE TRANSACTION
    // ========================================

    $pdo->beginTransaction();

    // ========================================
    // 6. LOCK AND RETRIEVE BOOKING
    // ========================================

    $stmt = $pdo->prepare("
        SELECT
            b.booking_ID,
            b.user_ID,
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

        FOR UPDATE
    ");

    $stmt->execute([$bookingID]);

    $currentBooking = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$currentBooking) {
        throw new RuntimeException(
            'Booking was not found.'
        );
    }

    // ========================================
    // 7. VALIDATE BOOKING STATUS AND HOLD
    // ========================================

    if (
        $currentBooking['booking_Status_Name']
        !== 'Pending Payment'
    ) {
        throw new RuntimeException(
            'Payment details can no longer be submitted ' .
            'for this reservation.'
        );
    }

    if ((int)$currentBooking['hold_active'] !== 1) {
        throw new RuntimeException(
            'Your payment hold has expired.'
        );
    }

    if (
        (float)$currentBooking['required_Downpayment'] <= 0
    ) {
        throw new RuntimeException(
            'Invalid required downpayment.'
        );
    }

    // ========================================
    // 8. VALIDATE SELECTED PAYMENT METHOD
    // ========================================

    $methodStmt = $pdo->prepare("
        SELECT payment_Method_ID
        FROM Payment_Method

        WHERE payment_Method_ID = ?
          AND payment_Method_Name IN (
              'GCash',
              'Bank Transfer'
          )
          AND is_Active = 1
    ");

    $methodStmt->execute([$paymentMethodID]);

    if (!$methodStmt->fetchColumn()) {
        throw new RuntimeException(
            'Invalid payment method.'
        );
    }

    // ========================================
    // 9. GET PAYMENT STATUS: PENDING
    // ========================================

    $paymentStatusID = $pdo->query("
        SELECT payment_Status_ID
        FROM Payment_Status

        WHERE payment_Status_Name = 'Pending'
          AND counts_As_Paid = 0
          AND is_Active = 1

        LIMIT 1
    ")->fetchColumn();

    // ========================================
    // 10. GET PAYMENT TYPE: DOWNPAYMENT
    // ========================================

    $paymentTypeID = $pdo->query("
        SELECT payment_Type_ID
        FROM Payment_Type

        WHERE payment_Type_Name = 'Downpayment'
          AND is_Active = 1

        LIMIT 1
    ")->fetchColumn();

    // ========================================
    // 11. GET BOOKING STATUS: PENDING APPROVAL
    // ========================================

    $approvalStatusID = $pdo->query("
        SELECT booking_Status_ID
        FROM Booking_Status

        WHERE booking_Status_Name = 'Pending Approval'
          AND blocks_Availability = 1
          AND requires_Hold_Expiry_Check = 0
          AND is_Active = 1

        LIMIT 1
    ")->fetchColumn();

    if (
        !$paymentStatusID ||
        !$paymentTypeID ||
        !$approvalStatusID
    ) {
        throw new RuntimeException(
            'Required payment statuses are not configured.'
        );
    }

    // ========================================
    // 12. PREVENT DUPLICATE DOWNPAYMENTS
    // ========================================

    $duplicateStmt = $pdo->prepare("
        SELECT p.payment_ID

        FROM Payment p

        JOIN Payment_Type pt
            ON p.payment_Type_ID = pt.payment_Type_ID

        WHERE p.booking_ID = ?
          AND pt.payment_Type_Name = 'Downpayment'
          AND p.deleted_At IS NULL

        LIMIT 1
    ");

    $duplicateStmt->execute([$bookingID]);

    if ($duplicateStmt->fetch()) {
        throw new RuntimeException(
            'A downpayment submission already exists ' .
            'for this reservation.'
        );
    }

    // ========================================
    // 13. INSERT PAYMENT RECORD
    // ========================================

    $paymentInsert = $pdo->prepare("
        INSERT INTO Payment (
            booking_ID,
            payment_Method_ID,
            processed_By,
            payment_Status_ID,
            payment_Type_ID,
            amount,
            reference_No,
            payment_Date
        )
        VALUES (?, ?, NULL, ?, ?, ?, ?, NULL)
    ");

    $paymentInsert->execute([
        $bookingID,
        $paymentMethodID,
        $paymentStatusID,
        $paymentTypeID,
        $currentBooking['required_Downpayment'],
        $referenceNo
    ]);

    // ========================================
    // 14. UPDATE BOOKING STATUS
    // ========================================

    $updateBooking = $pdo->prepare("
        UPDATE Booking
        SET booking_Status_ID = ?
        WHERE booking_ID = ?
    ");

    $updateBooking->execute([
        $approvalStatusID,
        $bookingID
    ]);

    // ========================================
    // 15. RECORD PAYMENT SUBMISSION IN LOG
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
        (int)$currentBooking['user_ID'],
        $approvalStatusID,
        'Downpayment Submitted',
        'Payment reference submitted; awaiting verification.'
    ]);

    // ========================================
    // 16. SAVE ALL DATABASE CHANGES
    // ========================================

    $pdo->commit();

    // Invalidate the submission token
    unset($_SESSION['payment_token']);

    $_SESSION['payment_message'] =
        'Payment details submitted successfully. ' .
        'Your reservation is awaiting admin verification.';

    header('Location: booking_created.php', true, 303);
    exit;

} catch (Throwable $e) {

    // ========================================
    // 17. ROLLBACK IF AN ERROR OCCURS
    // ========================================

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Payment submission error: ' . $e->getMessage()
    );

    $_SESSION['payment_error'] =
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to save payment details. ' .
              'Please check your reference and try again.';

    header('Location: booking_created.php', true, 303);
    exit;
}
