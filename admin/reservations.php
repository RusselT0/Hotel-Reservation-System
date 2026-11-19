<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';
$user = authRequireRoles($pdo, ['Admin'], '../pages/login.php', '../pages/auth_account.php');
require_once __DIR__ . '/../includes/admin_reservations.php';
require_once __DIR__ . '/../includes/admin_reservation_actions.php';

$adminSection = 'reservations';
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$statusID = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$statusID = $statusID === false ? null : $statusID;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$detailID = filter_input(INPUT_GET, 'detail', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$feedback = $_SESSION['admin_reservation_feedback'] ?? '';
unset($_SESSION['admin_reservation_feedback']);
$statuses = [];
$list = null;
$detail = null;

if (strlen($search) > 190) {
    $search = '';
    $feedback = 'Search text is too long. Please enter a shorter search.';
}

try {
    $statuses = adminReservationStatuses($pdo);
    if ($statusID !== null && !in_array($statusID, array_map('intval', array_column($statuses, 'booking_Status_ID')), true)) {
        $statusID = null;
        $feedback = 'The selected reservation status is unavailable.';
    }
    $list = adminReservationList($pdo, $search, $statusID, $page);
    if ($detailID !== null) {
        $detail = $detailID ? adminReservationDetails($pdo, $detailID) : null;
        if (!$detail) $feedback = 'The selected reservation was not found.';
    }
} catch (Throwable $e) {
    error_log('Admin reservations: ' . $e->getMessage());
    $feedback = 'Unable to load reservation information. Please try again.';
}

function reservationUrl(string $search, ?int $status, int $page, ?int $detail = null): string {
    $query = ['q' => $search, 'status' => $status, 'page' => $page, 'detail' => $detail];
    return 'reservations.php?' . http_build_query(array_filter($query, static fn($v) => $v !== null && $v !== ''));
}

function reservationMoney($value): string {
    return '₱' . number_format((float)$value, 2);
}

function reservationDate(?string $value, bool $time = false): string {
    return $value ? date($time ? 'M j, Y · g:i A' : 'M j, Y', strtotime($value)) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations | Bella Vista Hotel</title>
    <link rel="stylesheet" href="../assets/css/global_style.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=3">
    <link rel="stylesheet" href="../assets/css/admin_reservations.css?v=2">
    <script src="../assets/js/admin_reservations.js?v=2" defer></script>
</head>
<body class="admin-page">
    <a class="admin-skip" href="#admin-main">Skip to main content</a>
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-workspace">
        <header class="admin-topbar">
            <div><p class="admin-breadcrumb">Management / Reservations</p><p class="admin-topbar-title">Administration</p></div>
            <a class="admin-profile" href="../pages/auth_account.php"><span class="admin-profile-role">Admin</span><span class="admin-profile-name"><?= authEscape(trim($user['first_name'] . ' ' . $user['last_name'])) ?></span></a>
        </header>
        <main class="admin-main" id="admin-main">
            <section class="admin-welcome">
                <div><p class="admin-eyebrow">Reservation management</p><h1>Reservations</h1><p>Search bookings and review guest, room, and payment information.</p></div>
                <span class="admin-workspace-badge">Reservation review</span>
            </section>

            <section class="reservation-panel admin-section" aria-labelledby="reservations-heading">
                <div class="admin-section-heading">
                    <h2 id="reservations-heading">Reservation records</h2>
                    <span class="admin-subtle"><?= $list ? number_format($list['total']) . ' matching records' : 'Records unavailable' ?></span>
                </div>
                <form method="GET" action="reservations.php" class="reservation-filter" novalidate>
                    <label>Search bookings<input name="q" type="search" maxlength="190" value="<?= authEscape($search) ?>" placeholder="Guest, email, room or booking ID"></label>
                    <label>Booking status<select name="status"><option value="">All statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= (int)$status['booking_Status_ID'] ?>" <?= $statusID === (int)$status['booking_Status_ID'] ? 'selected' : '' ?>><?= authEscape($status['booking_Status_Name']) ?></option>
                        <?php endforeach; ?>
                    </select></label>
                    <button class="reservation-button" type="submit">Search</button>
                    <a class="reservation-secondary" href="reservations.php">Reset</a>
                </form>

                <?php if ($list !== null): ?>
                    <div class="reservation-table-wrap" tabindex="0" aria-label="Reservation records, scroll horizontally if needed">
                        <table class="reservation-table">
                            <thead><tr><th scope="col">Booking / guest</th><th scope="col">Room</th><th scope="col">Stay dates</th><th scope="col">Total amount</th><th scope="col">Booking status</th><th scope="col">Payment records</th><th scope="col">Details</th></tr></thead>
                            <tbody>
                            <?php foreach ($list['rows'] as $row): ?>
                                <tr>
                                    <td><strong>#<?= (int)$row['booking_ID'] ?></strong><span><?= authEscape($row['guest_name']) ?></span><?php if ($row['confirmation_Code']): ?><small><?= authEscape($row['confirmation_Code']) ?></small><?php endif; ?></td>
                                    <td><?= authEscape($row['room_codes'] ?? 'Not assigned') ?></td>
                                    <td><?= authEscape(reservationDate($row['checkin_Date'])) ?><span>to <?= authEscape(reservationDate($row['checkout_Date'])) ?></span></td>
                                    <td><strong><?= authEscape(reservationMoney($row['booking_Amount'])) ?></strong><small>Deposit <?= authEscape(reservationMoney($row['required_Downpayment'])) ?></small></td>
                                    <td><span class="reservation-status"><?= authEscape($row['booking_status']) ?></span><?php if ($row['hold_expired']): ?><small>Hold expired</small><?php endif; ?></td>
                                    <td><?= authEscape($row['payment_statuses'] ?? 'No payment records') ?><small>Verified net: <?= authEscape(reservationMoney($row['verified_paid'])) ?></small></td>
                                    <td><a class="reservation-detail-link" href="<?= authEscape(reservationUrl($search, $statusID, $list['page'], (int)$row['booking_ID'])) ?>">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$list['rows']): ?><tr><td colspan="7" class="reservation-empty">No reservations match these filters.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="reservation-pagination" aria-label="Reservation pages">
                        <span>Page <?= $list['page'] ?> of <?= $list['pages'] ?></span>
                        <?php if ($list['page'] > 1): ?><a href="<?= authEscape(reservationUrl($search, $statusID, $list['page'] - 1)) ?>">Previous</a><?php endif; ?>
                        <?php if ($list['page'] < $list['pages']): ?><a href="<?= authEscape(reservationUrl($search, $statusID, $list['page'] + 1)) ?>">Next</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        </main>
        <footer class="admin-footer">Bella Vista Hotel &amp; Events Place &middot; Administration</footer>
    </div>

    <?php if ($detail): ?>
        <dialog id="reservationDetails" class="reservation-dialog" aria-labelledby="reservation-details-title">
            <div class="reservation-dialog-heading"><div><p class="admin-eyebrow">Reservation record</p><h2 id="reservation-details-title">Booking #<?= (int)$detail['booking_ID'] ?></h2></div><form method="dialog"><button type="submit" class="reservation-secondary">Close</button></form></div>
            <span class="reservation-status"><?= authEscape($detail['booking_status']) ?></span>
            <?php if ($detail['hold_expired']): ?><span class="reservation-status">Hold expired</span><?php endif; ?>
            <dl class="reservation-details-grid">
                <div><dt>Guest</dt><dd><?= authEscape($detail['guest_name']) ?></dd></div>
                <div><dt>Email</dt><dd><?= authEscape($detail['email_Address']) ?></dd></div>
                <div><dt>Phone</dt><dd><?= authEscape($detail['phone_Number'] ?? '—') ?></dd></div>
                <div><dt>Confirmation code</dt><dd><?= authEscape($detail['confirmation_Code'] ?? 'Not issued') ?></dd></div>
                <div><dt>Check-in</dt><dd><?= authEscape(reservationDate($detail['checkin_Date'], true)) ?></dd></div>
                <div><dt>Checkout</dt><dd><?= authEscape(reservationDate($detail['checkout_Date'], true)) ?></dd></div>
                <div><dt>Created</dt><dd><?= authEscape(reservationDate($detail['created_At'], true)) ?></dd></div>
                <div><dt>Hold expiry</dt><dd><?= authEscape(reservationDate($detail['hold_Expires_At'], true)) ?></dd></div>
                <div><dt>Booking total</dt><dd><?= authEscape(reservationMoney($detail['booking_Amount'])) ?></dd></div>
                <div><dt>Required downpayment</dt><dd><?= authEscape(reservationMoney($detail['required_Downpayment'])) ?></dd></div>
                <div><dt>Verified net payments</dt><dd><?= authEscape(reservationMoney($detail['verified_paid'])) ?></dd></div>
            </dl>
            <section class="reservation-detail-section"><h3>Rooms &amp; facilities</h3>
                <?php foreach ($detail['rooms'] as $room): ?>
                    <article><strong><?= authEscape($room['room_Code'] . ' · ' . $room['room_Type']) ?></strong><p><?= (int)$room['num_Guest'] ?> guest(s), <?= (int)$room['included_Guests_Snapshot'] ?> included · <?= authEscape(reservationMoney($room['nightly_Rate'])) ?>/night · Extra guest fee <?= authEscape(reservationMoney($room['extra_Guest_Fee'])) ?> per additional guest, once per reservation</p><p>Actual check-in: <?= authEscape(reservationDate($room['checked_In_At'], true)) ?> · Actual checkout: <?= authEscape(reservationDate($room['checked_Out_At'], true)) ?></p></article>
                <?php endforeach; ?>
                <?php if (!$detail['rooms']): ?><p>No room records.</p><?php endif; ?>
            </section>
            <section class="reservation-detail-section"><h3>Add-ons</h3>
                <?php foreach ($detail['addons'] as $addon): ?><article><strong><?= authEscape($addon['addon_Name']) ?></strong><p><?= (int)$addon['quantity'] ?> × <?= authEscape(reservationMoney($addon['unit_Price'])) ?></p></article><?php endforeach; ?>
                <?php if (!$detail['addons']): ?><p>No add-ons selected.</p><?php endif; ?>
            </section>
            <section class="reservation-detail-section"><h3>Payment records</h3>
                <?php foreach ($detail['payments'] as $payment): ?><article><strong><?= authEscape($payment['payment_Type_Name'] . ' · ' . reservationMoney($payment['amount'])) ?></strong><p><?= authEscape($payment['payment_Method_Name'] . ' · ' . $payment['payment_Status_Name']) ?></p><p>Reference: <?= authEscape($payment['reference_No'] ?? '—') ?> · Payment date: <?= authEscape(reservationDate($payment['payment_Date'], true)) ?></p></article><?php endforeach; ?>
                <?php if (!$detail['payments']): ?><p>No payment records.</p><?php endif; ?>
            </section>
            <?php if ($detail['booking_status'] === 'Pending Approval' && $detail['confirmation_Code'] === null): ?>
                <?php
                $pendingPayments = array_values(array_filter($detail['payments'], static fn($p) => $p['payment_Status_Name'] === 'Pending'));
                $hasVerified = (bool)array_filter($detail['payments'], static fn($p) => (int)$p['counts_As_Paid'] === 1);
                $candidate = count($pendingPayments) === 1 && $pendingPayments[0]['payment_Type_Name'] === 'Downpayment' ? $pendingPayments[0] : null;
                ?>
                <section class="reservation-detail-section reservation-decision"><h3>Reservation decision</h3>
                    <?php if (!(int)$detail['stay_valid']): ?>
                        <p>Approval is unavailable because the selected stay dates have passed.</p>
                    <?php elseif (!$candidate): ?>
                        <p>Approval requires a single pending downpayment record.</p>
                    <?php else: ?>
                        <form method="POST" action="reservation_decision.php" novalidate>
                            <input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>">
                            <input type="hidden" name="booking_id" value="<?= (int)$detail['booking_ID'] ?>">
                            <input type="hidden" name="payment_id" value="<?= (int)$candidate['payment_ID'] ?>">
                            <input type="hidden" name="payment_review_token" value="<?= authEscape(adminPaymentReviewToken($candidate, $_SESSION['auth_token'])) ?>">
                            <input type="hidden" name="decision" value="approve">
                            <p>Only approve after independently checking the submitted reference and amount. This is a manually verified academic simulation, not automatic bank verification.</p>
                            <label class="reservation-review-check"><input type="checkbox" name="verified_manually" value="1"> I have manually verified the submitted downpayment.</label>
                            <button type="submit" class="reservation-button">Verify payment &amp; approve</button>
                        </form>
                    <?php endif; ?>
                    <?php if (!$hasVerified): ?>
                        <form method="POST" action="reservation_decision.php" novalidate>
                            <input type="hidden" name="auth_token" value="<?= authEscape($_SESSION['auth_token']) ?>">
                            <input type="hidden" name="booking_id" value="<?= (int)$detail['booking_ID'] ?>">
                            <input type="hidden" name="decision" value="decline">
                            <label for="decline-reason">Decline reason</label>
                            <textarea id="decline-reason" name="decline_reason" maxlength="500" rows="3" placeholder="State why this reservation cannot be approved"></textarea>
                            <p>Declining releases the reservation dates and cancels pending payment records.</p>
                            <button type="submit" class="reservation-decline">Decline reservation</button>
                        </form>
                    <?php else: ?>
                        <p>Decline is unavailable because this reservation has verified payments. Refund handling is not implemented yet.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </dialog>
    <?php endif; ?>
    <?php if ($feedback): ?>
        <dialog id="reservationFeedback" class="admin-feedback-modal" aria-labelledby="reservationFeedbackTitle" aria-describedby="reservationFeedbackMessage">
            <h2 id="reservationFeedbackTitle">Reservation information</h2><p id="reservationFeedbackMessage"><?= authEscape($feedback) ?></p><form method="dialog"><button type="submit">Okay</button></form>
        </dialog>
    <?php endif; ?>
</body>
</html>
