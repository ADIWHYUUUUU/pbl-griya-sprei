ALTER TABLE "user"
    ADD COLUMN IF NOT EXISTS password_reset_token_hash CHAR(64),
    ADD COLUMN IF NOT EXISTS password_reset_expires_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS password_reset_used_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS password_reset_requested_at TIMESTAMP;
