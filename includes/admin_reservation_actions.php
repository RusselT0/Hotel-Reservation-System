<?php

// Convert database DECIMAL amounts to exact centavos for comparison.
function adminPaymentCents($amount): int {
    $value = (string)$amount;
    if (!preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/', $value, $parts)) {
        throw new RuntimeException('Invalid payment amount.');
    }
    return (int)$parts[1] * 100 + (int)str_pad($parts[2] ?? '', 2, '0');
}

// Bind the manual review to the exact pending payment shown in the dialog.
function adminPaymentReviewToken(array $payment, string $token): string {
    return hash_hmac('sha256', json_encode([(int)$payment['payment_ID'], (string)$payment['amount'], (string)$payment['reference_No'], (string)$payment['payment_Method_Name']]), $token);
}

function adminReservationDecision(PDO $pdo, int $bookingID, string $decision, int $adminID, ?int $paymentID, string $reason, string $reviewToken = ''): string {
    if (!in_array($decision, ['approve', 'decline'], true)) {
        throw new RuntimeException('Invalid reservation action.');
    }
    if ($decision === 'decline' && ($reason === '' || strlen($reason) > 500)) {
        throw new RuntimeException('Enter a decline reason of up to 500 bytes.');
    }

    $pdo->beginTransaction();
    try {
        // Lock rooms first, matching the public reservation save's lock order.
        $roomIDs = [];
        if ($decision === 'approve') {
            $stmt = $pdo->prepare('SELECT room_ID FROM booking_room WHERE booking_ID = ? AND deleted_At IS NULL ORDER BY room_ID');
            $stmt->execute([$bookingID]);
            $roomIDs = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            if (!$roomIDs) throw new RuntimeException('No assigned rooms were found.');

            $lockRoom = $pdo->prepare('SELECT r.room_ID, rs.ready_For_Checkin FROM room r
                JOIN room_type rt ON rt.room_Type_ID = r.room_Type_ID
                JOIN room_status rs ON rs.room_Status_ID = r.room_Status_ID
                WHERE r.room_ID = ? AND r.deleted_At IS NULL AND rt.deleted_At IS NULL
                AND rs.is_Active = 1 FOR UPDATE');
            foreach ($roomIDs as $roomID) {
                $lockRoom->execute([$roomID]);
                $room = $lockRoom->fetch(PDO::FETCH_ASSOC);
                if (!$room || (int)$room['ready_For_Checkin'] !== 1) {
                    throw new RuntimeException('A selected room is no longer ready for booking.');
                }
            }
        }

        $stmt = $pdo->prepare('SELECT b.booking_ID, b.checkin_Date, b.checkout_Date, b.required_Downpayment,
            b.booking_Status_ID, b.confirmation_Code, bs.booking_Status_Name, bs.is_Active,
            (DATE(b.checkin_Date) >= CURDATE() AND b.checkout_Date > NOW()) AS stay_valid
            FROM booking b JOIN booking_status bs ON bs.booking_Status_ID = b.booking_Status_ID
            WHERE b.booking_ID = ? AND b.deleted_At IS NULL FOR UPDATE');
        $stmt->execute([$bookingID]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking || $booking['booking_Status_Name'] !== 'Pending Approval' || (int)$booking['is_Active'] !== 1) {
            throw new RuntimeException('Only reservations awaiting approval can be processed.');
        }
        if ($booking['confirmation_Code'] !== null) throw new RuntimeException('This reservation already has a confirmation code.');

        if ($decision === 'approve') {
            if (!(int)$booking['stay_valid'] || $booking['confirmation_Code'] !== null) {
                throw new RuntimeException('The reservation dates or confirmation state are no longer valid.');
            }
            $stmt = $pdo->prepare('SELECT room_ID FROM booking_room WHERE booking_ID = ? AND deleted_At IS NULL ORDER BY room_ID FOR UPDATE');
            $stmt->execute([$bookingID]);
            if (array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)) !== $roomIDs) {
                throw new RuntimeException('Room assignments changed. Please review the reservation again.');
            }
            $conflict = $pdo->prepare('SELECT b.booking_ID FROM booking_room br
                JOIN booking b ON b.booking_ID = br.booking_ID
                JOIN booking_status bs ON bs.booking_Status_ID = b.booking_Status_ID
                WHERE br.room_ID = ? AND b.booking_ID <> ? AND br.deleted_At IS NULL AND b.deleted_At IS NULL
                  AND bs.blocks_Availability = 1
                  AND (bs.requires_Hold_Expiry_Check = 0 OR b.hold_Expires_At IS NULL OR b.hold_Expires_At > NOW())
                  AND b.checkin_Date < ? AND b.checkout_Date > ? LIMIT 1 FOR UPDATE');
            foreach ($roomIDs as $roomID) {
                $conflict->execute([$roomID, $bookingID, $booking['checkout_Date'], $booking['checkin_Date']]);
                if ($conflict->fetch()) throw new RuntimeException('A room has a conflicting reservation.');
            }
        }

        // Lock the actual records, not client-supplied amounts or status IDs.
        $stmt = $pdo->prepare('SELECT p.payment_ID, p.amount, p.reference_No,
            ps.payment_Status_Name, ps.counts_As_Paid, pt.payment_Type_Name, pt.balance_Effect, pm.payment_Method_Name
            FROM payment p JOIN payment_status ps ON ps.payment_Status_ID = p.payment_Status_ID
            JOIN payment_type pt ON pt.payment_Type_ID = p.payment_Type_ID
            JOIN payment_method pm ON pm.payment_Method_ID = p.payment_Method_ID
            WHERE p.booking_ID = ? AND p.deleted_At IS NULL ORDER BY p.payment_ID FOR UPDATE');
        $stmt->execute([$bookingID]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pending = array_values(array_filter($payments, static fn($p) => $p['payment_Status_Name'] === 'Pending'));
        $verified = array_values(array_filter($payments, static fn($p) => (int)$p['counts_As_Paid'] === 1));

        // Recheck the administrator while the transaction is still open.
        $actor = authCurrentUser($pdo, true);
        if (!$actor || $actor['role'] !== 'Admin' || $actor['user_id'] !== $adminID) {
            throw new RuntimeException('Your administrator session is no longer valid.');
        }

        if ($decision === 'approve') {
            if (count($pending) !== 1 || (int)$pending[0]['payment_ID'] !== $paymentID ||
                $pending[0]['payment_Type_Name'] !== 'Downpayment' || (int)$pending[0]['balance_Effect'] !== 1 ||
                (int)$pending[0]['counts_As_Paid'] !== 0 || trim((string)$pending[0]['reference_No']) === '') {
                throw new RuntimeException('A single submitted pending downpayment must be reviewed before approval.');
            }
            if (!hash_equals(adminPaymentReviewToken($pending[0], $_SESSION['auth_token']), $reviewToken)) {
                throw new RuntimeException('Payment details changed. Please review them again before approval.');
            }
            $net = adminPaymentCents($pending[0]['amount']);
            foreach ($verified as $payment) {
                if ($payment['payment_Type_Name'] === 'Downpayment' || (int)$payment['balance_Effect'] === -1) {
                    $net += adminPaymentCents($payment['amount']) * (int)$payment['balance_Effect'];
                }
            }
            $required = adminPaymentCents($booking['required_Downpayment']);
            if ($required <= 0 || $net < $required) {
                throw new RuntimeException('The verified downpayment would be below the required amount.');
            }
            $bookingTarget = 'Confirmed';
            $paymentTarget = 'Verified';
            $code = 'BV-' . strtoupper(bin2hex(random_bytes(12)));
            $remarks = 'Admin manually verified submitted downpayment and approved reservation.';
            $logAction = 'Approved';
        } else {
            if ($verified) throw new RuntimeException('This reservation has verified payments. Use the future refund workflow instead.');
            $bookingTarget = 'Declined';
            $paymentTarget = 'Cancelled';
            $code = null;
            $remarks = $reason;
            $logAction = 'Declined';
        }

        $stmt = $pdo->prepare('SELECT booking_Status_ID FROM booking_status WHERE booking_Status_Name = ? AND is_Active = 1 AND blocks_Availability = ? AND requires_Hold_Expiry_Check = 0');
        $stmt->execute([$bookingTarget, $decision === 'approve' ? 1 : 0]);
        $bookingStatusID = $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT payment_Status_ID FROM payment_status WHERE payment_Status_Name = ? AND is_Active = 1 AND counts_As_Paid = ?');
        $stmt->execute([$paymentTarget, $decision === 'approve' ? 1 : 0]);
        $paymentStatusID = $stmt->fetchColumn();
        if (!$bookingStatusID || !$paymentStatusID) throw new RuntimeException('Required reservation statuses are not configured.');

        foreach ($pending as $payment) {
            $stmt = $pdo->prepare('UPDATE payment SET payment_Status_ID = ?, processed_By = ?' .
                ($decision === 'approve' ? ', payment_Date = COALESCE(payment_Date, NOW())' : '') . ' WHERE payment_ID = ? AND booking_ID = ? AND deleted_At IS NULL');
            $stmt->execute([$paymentStatusID, $adminID, $payment['payment_ID'], $bookingID]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('Payment record changed. Please review again.');
        }

        $stmt = $pdo->prepare('UPDATE booking SET booking_Status_ID = ?, confirmation_Code = ?, hold_Expires_At = NULL WHERE booking_ID = ? AND booking_Status_ID = ? AND deleted_At IS NULL');
        $stmt->execute([$bookingStatusID, $code, $bookingID, $booking['booking_Status_ID']]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Reservation changed. Please review again.');
        $stmt = $pdo->prepare('INSERT INTO booking_log (booking_ID, user_ID, booking_Status_ID, action_Type, remarks) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$bookingID, $adminID, $bookingStatusID, $logAction, $remarks]);
        $pdo->commit();
        return $decision === 'approve' ? 'Reservation approved. Confirmation code: ' . $code : 'Reservation declined. Pending payments were cancelled.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
