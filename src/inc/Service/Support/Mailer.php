<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Mailer
{
    public function send(string $to, string $subject, string $body, ?string $from = null): bool
    {
        $to = trim($to);
        $subject = trim($subject);
        $sender = $this->resolveSender($from);

        if ($to === '' || $subject === '' || $sender === '') {
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $sender,
            'Reply-To: ' . $sender,
            'X-Mailer: TinyCMS',
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private function resolveSender(?string $from): string
    {
        $candidate = trim((string)$from);
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return $candidate;
        }

        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'domena.tld'));
        if ($host === '') {
            $host = 'domena.tld';
        }
        $domain = explode(':', $host)[0] ?? 'domena.tld';
        $domain = trim($domain);
        if ($domain === '') {
            $domain = 'domena.tld';
        }

        return 'tinycms@' . $domain;
    }
}
