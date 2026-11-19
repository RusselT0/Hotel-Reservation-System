<?php

// Read-only query helpers; call only from a guarded Admin page.
function adminReservationStatuses(PDO $pdo): array {
    return $pdo->query('SELECT booking_Status_ID, booking_Status_Name FROM booking_status ORDER BY booking_Status_ID')->fetchAll(PDO::FETCH_ASSOC);
}

function adminReservationList(PDO $pdo, string $search, ?int $statusID, int $page, int $pageSize = 10): array {
    $where = ['b.deleted_At IS NULL'];
    $params = [];

    if ($statusID !== null) {
        $where[] = 'b.booking_Status_ID = :status_id';
        $params['status_id'] = $statusID;
    }

    if ($search !== '') {
        // Treat wildcard characters in user searches as literal characters.
        $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $where[] = "(CAST(b.booking_ID AS CHAR) LIKE :booking_id ESCAPE '!'
            OR b.confirmation_Code LIKE :confirmation ESCAPE '!'
            OR CONCAT_WS(' ', u.first_Name, u.last_Name) LIKE :guest_name ESCAPE '!'
            OR u.email_Address LIKE :guest_email ESCAPE '!'
            OR EXISTS (SELECT 1 FROM booking_room br JOIN room r ON r.room_ID = br.room_ID
                WHERE br.booking_ID = b.booking_ID AND br.deleted_At IS NULL
                  AND r.room_Code LIKE :room_code ESCAPE '!'))";
        foreach (['booking_id', 'confirmation', 'guest_name', 'guest_email', 'room_code'] as $key) {
            $params[$key] = $pattern;
        }
    }

    $from = ' FROM booking b JOIN users u ON u.user_ID = b.user_ID
        JOIN booking_status bs ON bs.booking_Status_ID = b.booking_Status_ID
        WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare('SELECT COUNT(*)' . $from);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $pageSize = max(1, min(50, $pageSize));
    $pages = max(1, (int)ceil($total / $pageSize));
    $page = max(1, min($page, $pages));

    $sql = "SELECT b.booking_ID, b.confirmation_Code, b.checkin_Date, b.checkout_Date,
        b.booking_Amount, b.required_Downpayment, b.created_At,
        CONCAT_WS(' ', u.first_Name, u.last_Name) AS guest_name,
        bs.booking_Status_Name AS booking_status,
        (bs.requires_Hold_Expiry_Check = 1 AND b.hold_Expires_At IS NOT NULL
          AND b.hold_Expires_At <= NOW()) AS hold_expired,
        (SELECT GROUP_CONCAT(r.room_Code ORDER BY r.room_Code SEPARATOR ', ')
         FROM booking_room br JOIN room r ON r.room_ID = br.room_ID
         WHERE br.booking_ID = b.booking_ID AND br.deleted_At IS NULL) AS room_codes,
        (SELECT GROUP_CONCAT(DISTINCT ps.payment_Status_Name ORDER BY ps.payment_Status_Name SEPARATOR ', ')
         FROM payment p JOIN payment_status ps ON ps.payment_Status_ID = p.payment_Status_ID
         WHERE p.booking_ID = b.booking_ID AND p.deleted_At IS NULL) AS payment_statuses,
        (SELECT COALESCE(SUM(p.amount * pt.balance_Effect), 0)
         FROM payment p JOIN payment_status ps ON ps.payment_Status_ID = p.payment_Status_ID
         JOIN payment_type pt ON pt.payment_Type_ID = p.payment_Type_ID
         WHERE p.booking_ID = b.booking_ID AND p.deleted_At IS NULL AND ps.counts_As_Paid = 1) AS verified_paid"
        . $from . ' ORDER BY b.created_At DESC, b.booking_ID DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
    $stmt->execute();

    return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'pages' => $pages, 'page_size' => $pageSize];
}

function adminReservationDetails(PDO $pdo, int $bookingID): ?array {
    $stmt = $pdo->prepare("SELECT b.booking_ID, b.confirmation_Code, b.checkin_Date, b.checkout_Date,
        b.booking_Amount, b.required_Downpayment, b.created_At, b.hold_Expires_At,
        CONCAT_WS(' ', u.first_Name, u.last_Name) AS guest_name, u.email_Address, u.phone_Number,
        bs.booking_Status_Name AS booking_status,
        (DATE(b.checkin_Date) >= CURDATE() AND b.checkout_Date > NOW()) AS stay_valid,
        (bs.requires_Hold_Expiry_Check = 1 AND b.hold_Expires_At IS NOT NULL AND b.hold_Expires_At <= NOW()) AS hold_expired
        FROM booking b JOIN users u ON u.user_ID = b.user_ID
        JOIN booking_status bs ON bs.booking_Status_ID = b.booking_Status_ID
        WHERE b.booking_ID = :id AND b.deleted_At IS NULL");
    $stmt->execute(['id' => $bookingID]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) return null;

    $stmt = $pdo->prepare('SELECT r.room_Code, rt.room_Type, br.num_Guest, br.nightly_Rate,
        br.included_Guests_Snapshot, br.extra_Guest_Fee, br.checked_In_At, br.checked_Out_At
        FROM booking_room br JOIN room r ON r.room_ID = br.room_ID JOIN room_type rt ON rt.room_Type_ID = r.room_Type_ID
        WHERE br.booking_ID = :id AND br.deleted_At IS NULL ORDER BY r.room_Code');
    $stmt->execute(['id' => $bookingID]);
    $booking['rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT a.addon_Name, ba.quantity, ba.unit_Price FROM booking_addon ba
        JOIN addon a ON a.addon_ID = ba.addon_ID WHERE ba.booking_ID = :id AND ba.deleted_At IS NULL ORDER BY a.addon_Name');
    $stmt->execute(['id' => $bookingID]);
    $booking['addons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT p.payment_ID, p.amount, p.reference_No, p.payment_Date, p.created_At,
        ps.payment_Status_Name, ps.counts_As_Paid, pt.payment_Type_Name, pt.balance_Effect, pm.payment_Method_Name
        FROM payment p JOIN payment_status ps ON ps.payment_Status_ID = p.payment_Status_ID
        JOIN payment_type pt ON pt.payment_Type_ID = p.payment_Type_ID
        JOIN payment_method pm ON pm.payment_Method_ID = p.payment_Method_ID
        WHERE p.booking_ID = :id AND p.deleted_At IS NULL ORDER BY p.created_At DESC, p.payment_ID DESC');
    $stmt->execute(['id' => $bookingID]);
    $booking['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(p.amount * pt.balance_Effect), 0)
        FROM payment p JOIN payment_status ps ON ps.payment_Status_ID = p.payment_Status_ID
        JOIN payment_type pt ON pt.payment_Type_ID = p.payment_Type_ID
        WHERE p.booking_ID = :id AND p.deleted_At IS NULL AND ps.counts_As_Paid = 1');
    $stmt->execute(['id' => $bookingID]);
    $booking['verified_paid'] = $stmt->fetchColumn();
    return $booking;
}
