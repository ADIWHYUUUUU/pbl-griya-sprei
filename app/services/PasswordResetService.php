<?php

class PasswordResetService
{
    private $pdo;
    private $secret;
    private $apiKey;
    private $senderEmail;
    private $senderName;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->secret = getenv('PASSWORD_RESET_OTP_SECRET') ?: '';
        $this->apiKey = getenv('BREVO_API_KEY') ?: '';
        $this->senderEmail = getenv('BREVO_SENDER_EMAIL') ?: '';
        $this->senderName = getenv('BREVO_SENDER_NAME') ?: '';
    }

    public function requestCode($email, $ip, $flow)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_user, email, nama FROM "user"
             WHERE email = :email AND role = \'CUSTOMER\' LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $this->consumeIpLimit($ip);
            return 0;
        }
        $this->issueCode($user, $ip, $flow);
        return (int) $user['id_user'];
    }

    public function resendCode($userId, $ip, $flow)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_user, email, nama FROM "user"
             WHERE id_user = :id_user AND role = \'CUSTOMER\' LIMIT 1'
        );
        $stmt->execute([':id_user' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $this->issueCode($user, $ip, $flow);
        } else {
            $this->consumeIpLimit($ip);
        }
    }

    public function verifyCode($userId, $flow, $code)
    {
        if (!$this->hasSecret() || $userId < 1 || !preg_match('/^[0-9]{6}$/D', $code)) {
            return null;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT reset.password_reset_otp_digest AS otp_digest,
                        reset.password_reset_attempt_count AS attempts,
                        reset.password_reset_flow_digest AS flow_digest,
                        reset.password_reset_otp_expires_at > CURRENT_TIMESTAMP AS not_expired
                 FROM public.password_reset_state AS reset
                 JOIN public."user" AS account ON account.id_user = reset.id_user
                 WHERE reset.id_user = :id_user AND account.role = \'CUSTOMER\'
                 FOR UPDATE OF reset'
            );
            $stmt->execute([':id_user' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (
                !$row || !is_string($row['flow_digest'])
                || !hash_equals($row['flow_digest'], $this->digest('flow', $flow, $userId))
            ) {
                $this->pdo->commit();
                return null;
            }
            if (!is_string($row['otp_digest']) || (int) $row['attempts'] >= 5) {
                $this->pdo->commit();
                return null;
            }
            if (!$this->dbBoolean($row['not_expired'])) {
                $clear = $this->pdo->prepare(
                    'UPDATE public.password_reset_state
                     SET password_reset_otp_digest = NULL WHERE id_user = :id_user'
                );
                $clear->execute([':id_user' => $userId]);
                $this->pdo->commit();
                return null;
            }
            if (!hash_equals($row['otp_digest'], $this->digest('otp', $code, $userId))) {
                $update = $this->pdo->prepare(
                    'UPDATE public.password_reset_state
                     SET password_reset_attempt_count = password_reset_attempt_count + 1,
                         password_reset_otp_digest = CASE
                             WHEN password_reset_attempt_count + 1 >= 5 THEN NULL
                             ELSE password_reset_otp_digest END
                     WHERE id_user = :id_user'
                );
                $update->execute([':id_user' => $userId]);
                $this->pdo->commit();
                return null;
            }

            $grant = bin2hex(random_bytes(32));
            $update = $this->pdo->prepare(
                'UPDATE public.password_reset_state
                 SET password_reset_otp_digest = NULL,
                     password_reset_verified_at = CURRENT_TIMESTAMP,
                     password_reset_grant_digest = :digest,
                     password_reset_grant_expires_at = CURRENT_TIMESTAMP + INTERVAL \'15 minutes\'
                 WHERE id_user = :id_user'
            );
            $update->execute([
                ':digest' => $this->digest('grant', $grant, $userId),
                ':id_user' => $userId,
            ]);
            $this->pdo->commit();
            return $grant;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function hasValidGrant($userId, $grant)
    {
        if ($userId < 1 || !is_string($grant) || $grant === '' || !$this->hasSecret()) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT reset.password_reset_grant_digest AS digest,
                    reset.password_reset_grant_expires_at > CURRENT_TIMESTAMP AS not_expired
             FROM public.password_reset_state AS reset
             JOIN public."user" AS account ON account.id_user = reset.id_user
             WHERE reset.id_user = :id_user AND account.role = \'CUSTOMER\''
        );
        $stmt->execute([':id_user' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && is_string($row['digest'])
            && hash_equals($row['digest'], $this->digest('grant', $grant, $userId))
            && $this->dbBoolean($row['not_expired']);
    }

    public function updatePassword($userId, $grant, $passwordHash)
    {
        if ($userId < 1 || !is_string($grant) || $grant === '' || !$this->hasSecret()) {
            return false;
        }
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT reset.password_reset_grant_digest AS digest,
                        reset.password_reset_grant_expires_at > CURRENT_TIMESTAMP AS not_expired
                 FROM public.password_reset_state AS reset
                 JOIN public."user" AS account ON account.id_user = reset.id_user
                 WHERE reset.id_user = :id_user AND account.role = \'CUSTOMER\'
                 FOR UPDATE OF reset'
            );
            $stmt->execute([':id_user' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (
                !$row || !is_string($row['digest'])
                || !hash_equals($row['digest'], $this->digest('grant', $grant, $userId))
                || !$this->dbBoolean($row['not_expired'])
            ) {
                $this->pdo->commit();
                return false;
            }
            $update = $this->pdo->prepare(
                'UPDATE public."user" SET password = :password
                 WHERE id_user = :id_user AND role = \'CUSTOMER\''
            );
            $update->execute([':password' => $passwordHash, ':id_user' => $userId]);
            if ($update->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }
            $clear = $this->pdo->prepare(
                'UPDATE public.password_reset_state
                 SET password_reset_otp_digest = NULL,
                     password_reset_flow_digest = NULL,
                     password_reset_grant_digest = NULL,
                     password_reset_grant_expires_at = NULL
                 WHERE id_user = :id_user'
            );
            $clear->execute([':id_user' => $userId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function issueCode($user, $ip, $flow)
    {
        if (!$this->hasSecret()) {
            return;
        }
        $userId = (int) $user['id_user'];
        $this->pdo->beginTransaction();
        try {
            $initialize = $this->pdo->prepare(
                'INSERT INTO public.password_reset_state (id_user)
                 VALUES (:id_user)
                 ON CONFLICT (id_user) DO NOTHING'
            );
            $initialize->execute([':id_user' => $userId]);
            $stmt = $this->pdo->prepare(
                'SELECT password_reset_last_sent_at AS last_sent,
                        password_reset_request_count AS request_count,
                        password_reset_request_window_started_at AS window_started,
                        password_reset_request_window_started_at > CURRENT_TIMESTAMP - INTERVAL \'1 hour\' AS request_window_active,
                        password_reset_last_sent_at > CURRENT_TIMESTAMP - INTERVAL \'10 minutes\' AS code_recent,
                        password_reset_last_sent_at > CURRENT_TIMESTAMP - INTERVAL \'60 seconds\' AS cooling
                 FROM public.password_reset_state WHERE id_user = :id_user FOR UPDATE'
            );
            $stmt->execute([':id_user' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->pdo->commit();
                return;
            }

            if ($this->dbBoolean($row['code_recent'])) {
                $rebind = $this->pdo->prepare(
                    'UPDATE public.password_reset_state
                     SET password_reset_flow_digest = :flow WHERE id_user = :id_user'
                );
                $rebind->execute([
                    ':flow' => $this->digest('flow', $flow, $userId),
                    ':id_user' => $userId,
                ]);
            }
            $count = $this->dbBoolean($row['request_window_active']) ? (int) $row['request_count'] : 0;
            $allowed = $count < 5
                && !$this->dbBoolean($row['cooling'])
                && $this->consumeIpLimit($ip);
            if (!$allowed) {
                $this->pdo->commit();
                return;
            }
            if (!$this->emailConfigurationIsValid()) {
                error_log('[password-reset] Brevo email configuration is incomplete.');
                $this->pdo->commit();
                return;
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $digest = $this->digest('otp', $code, $userId);
            $update = $this->pdo->prepare(
                'UPDATE public.password_reset_state SET
                    password_reset_otp_digest = :digest,
                    password_reset_otp_expires_at = CURRENT_TIMESTAMP + INTERVAL \'10 minutes\',
                    password_reset_attempt_count = 0,
                    password_reset_last_sent_at = CURRENT_TIMESTAMP,
                    password_reset_flow_digest = :flow,
                    password_reset_verified_at = NULL,
                    password_reset_grant_digest = NULL,
                    password_reset_grant_expires_at = NULL,
                    password_reset_request_count = :count,
                    password_reset_request_window_started_at = CASE
                        WHEN :window_count = 1 THEN CURRENT_TIMESTAMP
                        ELSE password_reset_request_window_started_at END
                 WHERE id_user = :id_user'
            );
            $update->execute([
                ':digest' => $digest,
                ':flow' => $this->digest('flow', $flow, $userId),
                ':count' => $count + 1,
                ':window_count' => $count + 1,
                ':id_user' => $userId,
            ]);
            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        if (!$this->sendBrevoEmail($user['email'], $user['nama'], $code)) {
            $invalidate = $this->pdo->prepare(
                'UPDATE public.password_reset_state SET password_reset_otp_digest = NULL
                 WHERE id_user = :id_user AND password_reset_otp_digest = :digest'
            );
            $invalidate->execute([':id_user' => $userId, ':digest' => $digest]);
        }
    }

    private function consumeIpLimit($ip)
    {
        $file = fopen(__DIR__ . '/../../storage/password-reset-' . $this->scopeHash('ip:' . $ip) . '.json', 'c+');
        if ($file === false) {
            throw new RuntimeException('Unable to access password-reset rate-limit storage.');
        }
        try {
            if (!flock($file, LOCK_EX)) {
                throw new RuntimeException('Unable to lock password-reset rate-limit storage.');
            }
            rewind($file);
            $contents = stream_get_contents($file);
            $state = $contents === '' ? [] : json_decode($contents, true);
            if ($contents !== '' && !is_array($state)) {
                error_log('[password-reset] IP rate-limit state is unreadable.');
                return false;
            }
            $now = time();
            $started = (int) ($state['started'] ?? 0);
            $count = (int) ($state['count'] ?? 0);
            if ($started <= $now - 3600) {
                $started = $now;
                $count = 0;
            }
            if ($count >= 20) {
                return false;
            }
            rewind($file);
            ftruncate($file, 0);
            if (fwrite($file, json_encode(['started' => $started, 'count' => $count + 1])) === false || !fflush($file)) {
                throw new RuntimeException('Unable to save password-reset rate-limit state.');
            }
            return true;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    private function sendBrevoEmail($email, $name, $code)
    {
        if (!function_exists('curl_init')) {
            error_log('[password-reset] PHP cURL extension is unavailable.');
            return false;
        }
        $body = json_encode([
            'sender' => ['name' => $this->senderName, 'email' => $this->senderEmail],
            'to' => [['email' => $email, 'name' => $name]],
            'subject' => 'Kode Verifikasi Griya Sprei',
            'htmlContent' => '<p>Kode verifikasi reset password Anda:</p><p style="font-size:24px;font-weight:bold">'
                . htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
                . '</p><p>Kode berlaku selama 10 menit. Jangan bagikan kode ini.</p>',
            'textContent' => "Kode verifikasi reset password: {$code}\nKode berlaku selama 10 menit.",
        ]);
        if ($body === false) {
            error_log('[password-reset] Failed to encode Brevo email payload.');
            return false;
        }
        $curl = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'api-key: ' . $this->apiKey,
                'content-type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($curl);
        if ($response === false || $status < 200 || $status >= 300) {
            error_log('[password-reset] Brevo delivery failed; HTTP status ' . $status . ', cURL error ' . $curlError . '.');
            return false;
        }
        return true;
    }

    private function emailConfigurationIsValid()
    {
        return $this->apiKey !== ''
            && filter_var($this->senderEmail, FILTER_VALIDATE_EMAIL)
            && trim($this->senderName) !== '';
    }

    private function hasSecret()
    {
        if (strlen($this->secret) >= 32) {
            return true;
        }
        error_log('[password-reset] PASSWORD_RESET_OTP_SECRET must contain at least 32 characters.');
        return false;
    }

    private function digest($purpose, $value, $userId)
    {
        return hash_hmac('sha256', $purpose . ':' . $userId . ':' . $value, $this->secret);
    }

    private function scopeHash($scope)
    {
        return hash_hmac('sha256', 'rate:' . $scope, $this->secret);
    }

    private function dbBoolean($value)
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't';
    }
}
