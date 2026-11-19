<?php

// Read-only overview totals. Only guarded Admin pages should display these.
function adminDashboardStats(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT
            (SELECT COUNT(*)
             FROM booking b
             WHERE b.deleted_At IS NULL) AS total_reservations,

            (SELECT COUNT(*)
             FROM booking b
             JOIN booking_status bs ON bs.booking_Status_ID = b.booking_Status_ID
             WHERE b.deleted_At IS NULL
               AND bs.is_Active = 1
               AND bs.booking_Status_Name = 'Pending Approval') AS pending_approval,

            (SELECT COUNT(*)
             FROM room r
             JOIN room_type rt ON rt.room_Type_ID = r.room_Type_ID
             JOIN room_status rs ON rs.room_Status_ID = r.room_Status_ID
             WHERE r.deleted_At IS NULL
               AND rt.deleted_At IS NULL
               AND rs.is_Active = 1
               AND rs.ready_For_Checkin = 1) AS ready_rooms,

            (SELECT COALESCE(SUM(p.amount * pt.balance_Effect), 0)
             FROM payment p
             JOIN booking b ON b.booking_ID = p.booking_ID
             JOIN payment_status ps ON ps.payment_Status_ID = p.payment_Status_ID
             JOIN payment_type pt ON pt.payment_Type_ID = p.payment_Type_ID
             WHERE p.deleted_At IS NULL
               AND b.deleted_At IS NULL
               AND ps.counts_As_Paid = 1) AS verified_revenue
    ");

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
