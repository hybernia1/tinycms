<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Support\I18n;
use App\Service\Support\Mailer;

final class Email
{
    private Mailer $mailer;
    private Settings $settings;

    public function __construct()
    {
        $this->mailer = new Mailer();
        $this->settings = new Settings();
    }

    public function send(string $to, string $templateKey, array $vars = []): bool
    {
        $subject = trim(I18n::t($templateKey . '.subject'));
        $body = $this->template(I18n::t($templateKey . '.body'), $vars);
        $websiteEmail = trim((string)($this->settings->resolved()['website_email'] ?? ''));
        $sender = filter_var($websiteEmail, FILTER_VALIDATE_EMAIL) ? $websiteEmail : null;

        return $this->mailer->send($to, $subject, $body, $sender);
    }

    private function template(string $message, array $vars): string
    {
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{' . trim((string)$key) . '}'] = (string)$value;
        }

        return strtr($message, $replace);
    }
}
