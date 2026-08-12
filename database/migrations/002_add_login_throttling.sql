CREATE TABLE login_throttles (
    identifier_hash CHAR(64) NOT NULL,
    failure_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (identifier_hash),
    KEY idx_login_throttles_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
