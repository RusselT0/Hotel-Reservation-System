<?php

// Keep activation and recovery eligibility separate; never issue Guest codes for staff.
function adminGuestTokenPasswordCondition(string $purpose): string {
    return match ($purpose) {
        'Activation' => 'u.password_Hash IS NULL',
        'Password Reset' => "u.password_Hash IS NOT NULL AND u.password_Hash <> ''",
        default => throw new InvalidArgumentException('Unsupported guest token purpose.')
    };
}

function adminGuestTokenCandidates(PDO $pdo, string $search, string $purpose = 'Activation'): array {
    $passwordCondition = adminGuestTokenPasswordCondition($purpose);
    $sql = "SELECT u.user_ID, u.first_Name, u.last_Name, u.email_Address,
        (SELECT COUNT(*) FROM booking b WHERE b.user_ID = u.user_ID AND b.deleted_At IS NULL) AS booking_count,
        EXISTS (SELECT 1 FROM account_token t WHERE t.user_ID = u.user_ID
          AND t.purpose = :purpose AND t.used_At IS NULL AND t.revoked_At IS NULL
          AND t.expires_At > NOW()) AS has_active_token
        FROM users u JOIN user_role r ON r.role_ID = u.role_ID
        JOIN account_status s ON s.account_Status_ID = u.account_Status_ID
        WHERE r.role_Name = 'Guest' AND r.is_Active = 1
          AND s.account_Status_Name = 'Active' AND s.is_Active = 1
          AND $passwordCondition AND u.deleted_At IS NULL";
    $params = ['purpose' => $purpose];
    if ($search !== '') {
        $sql .= ' AND u.email_Address = :email';
        $params['email'] = $search;
    }
    $sql .= ' ORDER BY u.last_Name, u.first_Name, u.user_ID LIMIT 50';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Called only after a guarded Admin explicitly attests to independently verifying identity.
function adminIssueGuestToken(PDO $pdo, int $guestID, int $adminID, string $purpose = 'Activation'): string {
    $passwordCondition = adminGuestTokenPasswordCondition($purpose);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT u.user_ID FROM users u
            JOIN user_role r ON r.role_ID = u.role_ID
            JOIN account_status s ON s.account_Status_ID = u.account_Status_ID
            WHERE u.user_ID = ? AND u.deleted_At IS NULL AND $passwordCondition
              AND r.role_Name = 'Guest' AND r.is_Active = 1
              AND s.account_Status_Name = 'Active' AND s.is_Active = 1 FOR UPDATE");
        $stmt->execute([$guestID]);
        if (!$stmt->fetchColumn()) throw new RuntimeException('This guest is no longer eligible for this account action.');

        $admin = authCurrentUser($pdo, true);
        if (!$admin || $admin['role'] !== 'Admin' || $admin['user_id'] !== $adminID) {
            throw new RuntimeException('Your administrator session is no longer valid.');
        }

        // Serialize issuance by locking the guest row; revoke older unused codes for this purpose only.
        $stmt = $pdo->prepare("UPDATE account_token SET revoked_At = NOW()
            WHERE user_ID = ? AND purpose = ? AND used_At IS NULL AND revoked_At IS NULL");
        $stmt->execute([$guestID, $purpose]);

        $code = bin2hex(random_bytes(32));
        $hash = hash('sha256', $code);
        $stmt = $pdo->prepare("INSERT INTO account_token
            (user_ID, token_Hash, purpose, delivery_Method, issued_By, created_At, expires_At)
            VALUES (?, ?, ?, 'Admin Assisted', ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
        $stmt->execute([$guestID, $hash, $purpose, $adminID]);
        $pdo->commit();
        return $code;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
