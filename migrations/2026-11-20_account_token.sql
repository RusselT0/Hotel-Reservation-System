-- Step 12.6A: account activation and future password-reset token storage.
-- Back up the current database before applying once.
CREATE TABLE account_token (
    token_ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_ID INT UNSIGNED NOT NULL,
    token_Hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    purpose ENUM('Activation', 'Password Reset') NOT NULL,
    delivery_Method ENUM('Email', 'Admin Assisted') NOT NULL,
    issued_By INT UNSIGNED DEFAULT NULL,
    created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_At DATETIME NOT NULL,
    used_At DATETIME DEFAULT NULL,
    revoked_At DATETIME DEFAULT NULL,
    PRIMARY KEY (token_ID),
    UNIQUE KEY uq_account_token_hash (token_Hash),
    KEY idx_account_token_user_purpose (user_ID, purpose, used_At, revoked_At, expires_At),
    KEY idx_account_token_issuer (issued_By),
    CONSTRAINT fk_account_token_user FOREIGN KEY (user_ID) REFERENCES users (user_ID),
    CONSTRAINT fk_account_token_issuer FOREIGN KEY (issued_By) REFERENCES users (user_ID),
    CONSTRAINT chk_account_token_expiry CHECK (expires_At > created_At),
    CONSTRAINT chk_account_token_issuer CHECK (
        (delivery_Method = 'Admin Assisted' AND issued_By IS NOT NULL)
        OR (delivery_Method = 'Email' AND issued_By IS NULL)
    ),
    CONSTRAINT chk_account_token_consumption CHECK (used_At IS NULL OR revoked_At IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
