<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Mailer
{
    public function send(string $to, string $subject, string $body, string $from): bool
    {
        $to = trim($to);
        $subject = trim($subject);
        if ($to === '' || $subject === '' || trim($from) === '') {
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from,
            'Reply-To: ' . $from,
            'X-Mailer: TinyCMS',
        ];

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}
