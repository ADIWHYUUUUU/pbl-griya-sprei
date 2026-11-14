ALTER TABLE "user"
    ADD COLUMN IF NOT EXISTS password_reset_otp_digest CHAR(64),
    ADD COLUMN IF NOT EXISTS password_reset_otp_expires_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS password_reset_attempt_count SMALLINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS password_reset_last_sent_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS password_reset_flow_digest CHAR(64),
    ADD COLUMN IF NOT EXISTS password_reset_verified_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS password_reset_grant_digest CHAR(64),
    ADD COLUMN IF NOT EXISTS password_reset_grant_expires_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS password_reset_request_count INTEGER NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS password_reset_request_window_started_at TIMESTAMP;
