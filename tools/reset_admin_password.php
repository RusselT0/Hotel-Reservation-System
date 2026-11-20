<?php

// Local maintenance utility. Never allow browser execution.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

echo "Bella Vista Hotel - Admin password reset\n";
echo "This updates only the single active Admin account.\n";
echo "Type RESET to continue: ";
if (trim((string)fgets(STDIN)) !== 'RESET') {
    echo "Cancelled.\n";
    exit;
}

echo "New password (12-72 bytes; visible in this terminal): ";
$first = fgets(STDIN);
echo "Confirm new password: ";
$second = fgets(STDIN);
$password = $first === false ? '' : rtrim($first, "\r\n");
$confirmation = $second === false ? '' : rtrim($second, "\r\n");
unset($first, $second);

if (strlen($password) < 12 || strlen($password) > 72 || strpos($password, "\0") !== false || !hash_equals($password, $confirmation)) {
    fwrite(STDERR, "Passwords must match and contain 12-72 bytes. No changes made.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/database.php';

try {
    $pdo->beginTransaction();
    $admins = $pdo->query("SELECT u.user_ID FROM users u
        JOIN user_role r ON r.role_ID = u.role_ID
        JOIN account_status s ON s.account_Status_ID = u.account_Status_ID
        WHERE r.role_Name = 'Admin' AND r.is_Active = 1
            AND s.account_Status_Name = 'Active' AND s.is_Active = 1
            AND u.deleted_At IS NULL FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);

    if (count($admins) !== 1) {
        throw new RuntimeException('Expected exactly one active Admin account.');
    }

    $adminID = (int)$admins[0];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_Hash = ? WHERE user_ID = ?');
    $stmt->execute([$hash, $adminID]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Admin password was not updated.');
    }
    $stmt = $pdo->prepare('SELECT password_Hash FROM users WHERE user_ID = ?');
    $stmt->execute([$adminID]);
    $storedHash = $stmt->fetchColumn();
    if (!is_string($storedHash) || !password_verify($password, $storedHash)) {
        throw new RuntimeException('Stored hash did not pass verification.');
    }
    $pdo->commit();
    echo "Admin password reset and verified successfully. Sign in with your existing email and new password.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Reset failed. No password change was committed.\n");
    exit(1);
} finally {
    unset($password, $confirmation, $hash, $storedHash);
}
