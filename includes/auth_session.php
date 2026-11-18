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

// Safely display values in HTML
function authEscape($value): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}