-- Untuk database yang SUDAH berjalan (tabel devices tetap aman)
DROP TABLE IF EXISTS refresh_tokens;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS device_credentials (
    serial_hardware  VARCHAR(100) PRIMARY KEY,
    secret_hash      VARCHAR(255) NOT NULL,
    active           BOOLEAN NOT NULL DEFAULT TRUE,
    confirmed        BOOLEAN NOT NULL DEFAULT TRUE,  -- false = hasil auto-enroll, belum terbukti secret-nya diterima alat
    created_at       TIMESTAMP NOT NULL DEFAULT NOW(),
    secret_rotated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE refresh_tokens (
    jti              VARCHAR(64) PRIMARY KEY,
    serial_hardware  VARCHAR(100) NOT NULL REFERENCES device_credentials(serial_hardware) ON DELETE CASCADE,
    expires_at       TIMESTAMP NOT NULL,
    revoked          BOOLEAN NOT NULL DEFAULT FALSE,
    created_at       TIMESTAMP NOT NULL DEFAULT NOW()
);
