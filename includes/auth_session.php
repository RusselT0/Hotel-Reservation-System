<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a security token for form submissions
if (empty($_SESSION['auth_token'])) {
    $_SESSION['auth_token'] = bin2hex(random_bytes(32));
}

// Safely retrieve submitted form values
function authPost(string $key): string {

    $value = $_POST[$key] ?? '';

    return is_string($value) ? $value : '';
}

// Validate the form security token
function validAuthToken(): bool {

    return hash_equals(
        $_SESSION['auth_token'],
        authPost('auth_token')
    );
}

// Retrieve the current signed-in account using the server-side session ID.
function authCurrentUser(PDO $pdo, bool $lockForUpdate = false): ?array {

    $userID = $_SESSION['auth_user']['user_id'] ?? null;

    if (!is_int($userID) || $userID < 1) {
        return null;
    }

    try {
        $sql = "
            SELECT u.user_ID, u.first_Name, u.last_Name,
                   u.email_Address, u.phone_Number, ur.role_Name
            FROM users u
            JOIN user_role ur ON ur.role_ID = u.role_ID
            JOIN account_status ast ON ast.account_Status_ID = u.account_Status_ID
            WHERE u.user_ID = :user_id
              AND u.deleted_At IS NULL
              AND u.password_Hash IS NOT NULL
              AND ur.is_Active = 1
              AND ast.is_Active = 1
              AND ast.account_Status_Name = 'Active'
              AND ur.role_Name IN ('Admin', 'Housekeeping', 'Guest')
        ";
        // Only use a locking read inside the booking save transaction.
        if ($lockForUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userID]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            unset($_SESSION['auth_user']);
            return null;
        }

        $_SESSION['auth_user'] = [
            'user_id' => (int)$user['user_ID'],
            'first_name' => $user['first_Name'],
            'last_name' => $user['last_Name'],
            'email' => $user['email_Address'],
            'phone_number' => $user['phone_Number'],
            'role' => $user['role_Name']
        ];

        return $_SESSION['auth_user'];
    } catch (PDOException $e) {
        error_log('Unable to refresh signed-in account: ' . $e->getMessage());
        return null;
    }
}

// Check an already verified role against the roles allowed by a resource.
function authRoleAllowed(string $role, array $allowedRoles): bool {
    return in_array($role, ['Admin', 'Housekeeping', 'Guest'], true)
        && in_array($role, $allowedRoles, true);
}

// Project-relative destinations for verified account roles.
function authDashboardPath(string $role): string {
    return match ($role) {
        'Admin' => 'admin/dashboard.php',
        'Housekeeping' => 'housekeeping/dashboard.php',
        'Guest' => 'guest/dashboard.php',
        default => 'pages/auth_account.php'
    };
}

// Require a live account with an allowed role before protected work begins.
// Page callers provide fixed local redirect paths; action callers may omit them
// to receive HTTP 401/403 JSON responses instead.
function authRequireRoles(
    PDO $pdo,
    array $allowedRoles,
    ?string $loginPath = null,
    ?string $deniedPath = null
): array {
    $user = authCurrentUser($pdo);

    if ($user && authRoleAllowed($user['role'], $allowedRoles)) {
        return $user;
    }

    $status = $user ? 403 : 401;
    $message = $user
        ? 'You do not have permission to access this resource.'
        : 'Please sign in to continue.';

    $redirect = $user ? $deniedPath : $loginPath;

    if ($redirect !== null) {
        $_SESSION['auth_guard_error'] = $message;
        header('Location: ' . $redirect, true, 303);
        exit;
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Safely display values in HTML
function authEscape($value): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
