BEGIN;

CREATE TABLE IF NOT EXISTS public.password_reset_state (
    id_user INTEGER PRIMARY KEY REFERENCES public."user"(id_user) ON DELETE CASCADE,
    password_reset_otp_digest CHAR(64),
    password_reset_otp_expires_at TIMESTAMP,
    password_reset_attempt_count SMALLINT NOT NULL DEFAULT 0,
    password_reset_last_sent_at TIMESTAMP,
    password_reset_flow_digest CHAR(64),
    password_reset_verified_at TIMESTAMP,
    password_reset_grant_digest CHAR(64),
    password_reset_grant_expires_at TIMESTAMP,
    password_reset_request_count INTEGER NOT NULL DEFAULT 0,
    password_reset_request_window_started_at TIMESTAMP
);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'user'
          AND column_name = 'password_reset_otp_digest'
    ) THEN
        INSERT INTO public.password_reset_state (
            id_user,
            password_reset_otp_digest,
            password_reset_otp_expires_at,
            password_reset_attempt_count,
            password_reset_last_sent_at,
            password_reset_flow_digest,
            password_reset_verified_at,
            password_reset_grant_digest,
            password_reset_grant_expires_at,
            password_reset_request_count,
            password_reset_request_window_started_at
        )
        SELECT
            id_user,
            password_reset_otp_digest,
            password_reset_otp_expires_at,
            password_reset_attempt_count,
            password_reset_last_sent_at,
            password_reset_flow_digest,
            password_reset_verified_at,
            password_reset_grant_digest,
            password_reset_grant_expires_at,
            password_reset_request_count,
            password_reset_request_window_started_at
        FROM public."user"
        ON CONFLICT (id_user) DO NOTHING;
    END IF;
END $$;

ALTER TABLE public."user"
    DROP COLUMN IF EXISTS password_reset_otp_digest,
    DROP COLUMN IF EXISTS password_reset_otp_expires_at,
    DROP COLUMN IF EXISTS password_reset_attempt_count,
    DROP COLUMN IF EXISTS password_reset_last_sent_at,
    DROP COLUMN IF EXISTS password_reset_flow_digest,
    DROP COLUMN IF EXISTS password_reset_verified_at,
    DROP COLUMN IF EXISTS password_reset_grant_digest,
    DROP COLUMN IF EXISTS password_reset_grant_expires_at,
    DROP COLUMN IF EXISTS password_reset_request_count,
    DROP COLUMN IF EXISTS password_reset_request_window_started_at;

COMMIT;
