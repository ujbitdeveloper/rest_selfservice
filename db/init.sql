-- Kredensial per alat IoT (secret disimpan sebagai hash bcrypt)
CREATE TABLE device_credentials (
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

-- Data yang dikirim alat (upsert berdasarkan serial_hardware)
CREATE TABLE devices (
    id                   BIGSERIAL PRIMARY KEY,
    serial_hardware      VARCHAR(100) NOT NULL UNIQUE,
    device_os            VARCHAR(50),
    device_product_model VARCHAR(100),
    device_board_model   VARCHAR(100),
    os_version           VARCHAR(50),
    device_manufacturer  VARCHAR(100),
    pumps                JSONB NOT NULL DEFAULT '[]'::jsonb,
    site_id              VARCHAR(50),
    site_name            VARCHAR(200),
    site_address         TEXT,
    serial_number_ss     VARCHAR(100),
    created_at           TIMESTAMP NOT NULL DEFAULT NOW(),
    last_update          TIMESTAMP NOT NULL DEFAULT NOW()
);
