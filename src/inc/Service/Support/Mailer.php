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

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->formatAddress($sender),
            'Reply-To: ' . $this->formatAddress($sender),
            'X-Mailer: TinyCMS',
        ];

        return @mail($to, $subject, $body, implode("\r\n", $headers));
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
