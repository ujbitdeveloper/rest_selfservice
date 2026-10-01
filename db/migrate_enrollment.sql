-- Untuk database yang SUDAH punya tabel device_credentials
ALTER TABLE device_credentials
    ADD COLUMN IF NOT EXISTS confirmed BOOLEAN NOT NULL DEFAULT TRUE;
