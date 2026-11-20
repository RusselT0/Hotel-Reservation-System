<?php

// Separate activation and reset eligibility; the calling page selects the purpose.
// Keep lock order consistent with issuance: guest first, then account tokens.
function redeemGuestActivation(PDO $pdo, string $email, string $code, string $password, string $purpose = 'Activation'): bool {
    $condition = match ($purpose) {
        'Activation' => 'password_Hash IS NULL',
        'Password Reset' => "password_Hash IS NOT NULL AND password_Hash <> ''",
        default => throw new InvalidArgumentException('Invalid token purpose.')
    };
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 ||
        !preg_match('/\A[0-9a-f]{64}\z/', $code) || strlen($password) < 12 ||
        strlen($password) > 72 || strpos($password, "\0") !== false) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT u.user_ID FROM users u
            JOIN user_role r ON r.role_ID = u.role_ID
            JOIN account_status s ON s.account_Status_ID = u.account_Status_ID
            WHERE u.email_Address = ? AND u.deleted_At IS NULL AND $condition
              AND r.role_Name = 'Guest' AND r.is_Active = 1
              AND s.account_Status_Name = 'Active' AND s.is_Active = 1 FOR UPDATE");
        $stmt->execute([$email]);
        $guestID = $stmt->fetchColumn();
        if (!$guestID) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare("SELECT t.token_ID FROM account_token t
            JOIN users issuer ON issuer.user_ID = t.issued_By
            JOIN user_role ir ON ir.role_ID = issuer.role_ID
            JOIN account_status ist ON ist.account_Status_ID = issuer.account_Status_ID
            WHERE t.user_ID = ? AND t.token_Hash = ? AND t.purpose = ?
              AND t.delivery_Method = 'Admin Assisted' AND t.used_At IS NULL AND t.revoked_At IS NULL
              AND t.expires_At > NOW()
              AND issuer.deleted_At IS NULL AND issuer.password_Hash IS NOT NULL
              AND ir.role_Name = 'Admin' AND ir.is_Active = 1
              AND ist.account_Status_Name = 'Active' AND ist.is_Active = 1 FOR UPDATE");
        $stmt->execute([(int)$guestID, hash('sha256', $code), $purpose]);
        $tokenID = $stmt->fetchColumn();
        if (!$tokenID) {
            $pdo->rollBack();
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_Hash = ? WHERE user_ID = ? AND $condition AND deleted_At IS NULL");
        $stmt->execute([$passwordHash, (int)$guestID]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Activation account changed.');

        $stmt = $pdo->prepare('UPDATE account_token SET used_At = NOW() WHERE token_ID = ? AND user_ID = ? AND used_At IS NULL AND revoked_At IS NULL AND expires_At > NOW()');
        $stmt->execute([(int)$tokenID, (int)$guestID]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Activation token changed or expired.');

        $stmt = $pdo->prepare("UPDATE account_token SET revoked_At = NOW() WHERE user_ID = ? AND purpose = ? AND used_At IS NULL AND revoked_At IS NULL");
        $stmt->execute([(int)$guestID, $purpose]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
