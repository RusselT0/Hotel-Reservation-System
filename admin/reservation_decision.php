<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';
$user = authRequireRoles($pdo, ['Admin'], '../pages/login.php', '../pages/auth_account.php');
require_once __DIR__ . '/../includes/admin_reservation_actions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$bookingID = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$paymentID = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$decision = authPost('decision');
$reason = trim(authPost('decline_reason'));

try {
    if (!validAuthToken() || !$bookingID) throw new RuntimeException('Invalid request. Please review the reservation again.');
    if ($decision === 'approve' && authPost('verified_manually') !== '1') {
        throw new RuntimeException('Confirm that you manually verified the submitted payment before approval.');
    }
    $_SESSION['admin_reservation_feedback'] = adminReservationDecision($pdo, $bookingID, $decision, (int)$user['user_id'], $paymentID, $reason, authPost('payment_review_token'));
} catch (Throwable $e) {
    error_log('Admin reservation decision: ' . $e->getMessage());
    $_SESSION['admin_reservation_feedback'] = $e instanceof RuntimeException && !($e instanceof PDOException)
        ? $e->getMessage()
        : 'Unable to process this reservation. No decision was committed.';
}

header('Location: reservations.php' . ($bookingID ? '?' . http_build_query(['detail' => $bookingID]) : ''), true, 303);
exit;
