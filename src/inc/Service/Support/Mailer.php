<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Mailer
{
    public function send(string $to, string $subject, string $body, ?string $from = null, array $settings = []): bool
    {
        $to = trim($to);
        $subject = trim($subject);
        $sender = $this->resolveSender($from, $settings);

        if ($to === '' || $subject === '' || $sender['email'] === '') {
            return false;
        }

        if ((string)($settings['mail_driver'] ?? 'php') === 'smtp') {
            return $this->sendSmtp($to, $subject, $body, $sender, $settings);
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->formatAddress($sender),
            'Reply-To: ' . $this->formatAddress($sender),
            'X-Mailer: TinyCMS',
        ];

        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private function sendSmtp(string $to, string $subject, string $body, array $sender, array $settings): bool
    {
        $host = trim((string)($settings['smtp_host'] ?? ''));
        if ($host === '') {
            return false;
        }

        $this->loadPhpMailer();
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return false;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = max(1, min(65535, (int)($settings['smtp_port'] ?? 587)));
            $mail->SMTPDebug = 0;
            $mail->SMTPAuth = trim((string)($settings['smtp_username'] ?? '')) !== '';
            $mail->Username = trim((string)($settings['smtp_username'] ?? ''));
            $mail->Password = (string)($settings['smtp_password'] ?? '');
            $mail->SMTPSecure = in_array((string)($settings['smtp_secure'] ?? ''), ['tls', 'ssl'], true)
                ? (string)$settings['smtp_secure']
                : '';
            $mail->SMTPAutoTLS = $mail->SMTPSecure !== '';
            $mail->setFrom($sender['email'], $sender['name']);
            $mail->addReplyTo($sender['email'], $sender['name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            return $mail->send();
        } catch (\Throwable) {
            return false;
        }
    }

    private function loadPhpMailer(): void
    {
        $base = BASE_DIR . '/' . INC_DIR . 'Lib/PHPMailer/';
        foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $file) {
            $path = $base . $file;
            if (is_file($path)) {
                require_once $path;
            }
        }
    }

    private function resolveSender(?string $from, array $settings): array
    {
        $candidate = trim((string)$from);
        $email = filter_var($candidate, FILTER_VALIDATE_EMAIL) ? $candidate : 'tinycms@' . RequestContext::domain();
        $name = trim(str_replace(["\r", "\n"], '', (string)($settings['sitename'] ?? '')));

        return [
            'email' => $email,
            'name' => $name,
        ];
    }

    private function formatAddress(array $sender): string
    {
        $email = trim(str_replace(["\r", "\n"], '', (string)($sender['email'] ?? '')));
        $name = trim(str_replace(["\r", "\n"], '', (string)($sender['name'] ?? '')));

        if ($name === '') {
            return $email;
        }

        return sprintf('"%s" <%s>', addcslashes($name, '"\\'), $email);
    }
}
