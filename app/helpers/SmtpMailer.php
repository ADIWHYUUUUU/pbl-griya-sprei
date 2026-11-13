<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class SmtpMailer
{
    public function send($recipient, $recipientName, $subject, $body)
    {
        $host = getenv('SMTP_HOST') ?: '';
        $port = (int) (getenv('SMTP_PORT') ?: 0);
        $encryption = strtolower((string) (getenv('SMTP_ENCRYPTION') ?: ''));
        $username = (string) (getenv('SMTP_USERNAME') ?: '');
        $password = preg_replace('/\s+/', '', (string) (getenv('SMTP_PASSWORD') ?: ''));
        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: '';
        $fromName = getenv('SMTP_FROM_NAME') ?: 'Griya Sprei';

        if (
            $host === ''
            || $port < 1
            || (($username === '') !== ($password === ''))
            || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)
            || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
            || !in_array($encryption, ['none', 'starttls', 'ssl'], true)
        ) {
            throw new RuntimeException('Konfigurasi SMTP tidak lengkap atau tidak valid.');
        }

        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = $username !== '';
        if ($mailer->SMTPAuth) {
            $mailer->Username = $username;
            $mailer->Password = $password;
        }
        $mailer->Timeout = 15;
        $mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;

        if ($encryption === 'starttls') {
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        }

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($recipient, $recipientName);
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->send();
    }
}
