<?php
declare(strict_types=1);

namespace App\Service\Support;

final class MailService
{
    public function send(array $mailSettings, string $to, string $subject, string $body): bool
    {
        $driver = strtolower(trim((string)($mailSettings['mail_driver'] ?? 'php')));
        if ($driver === 'smtp') {
            $host = trim((string)($mailSettings['smtp_host'] ?? ''));
            $port = trim((string)($mailSettings['smtp_port'] ?? ''));
            if ($host !== '') {
                @ini_set('SMTP', $host);
            }
            if ($port !== '') {
                @ini_set('smtp_port', $port);
            }
        }

        $headers = ['MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
        if ($driver === 'smtp') {
            $from = trim((string)($mailSettings['smtp_user'] ?? ''));
            if ($from !== '') {
                $headers[] = 'From: ' . $from;
            }
        }

        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }
}
