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
        $resolvedVars = array_replace($this->defaultVars($to), $this->normalizeVars($vars));
        $subject = trim(I18n::t($templateKey . '.subject'));
        $body = $this->template(I18n::t($templateKey . '.body'), $resolvedVars);
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

    private function defaultVars(string $to): array
    {
        $settings = $this->settings->resolved();
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $baseDir = trim(dirname($scriptName), '/.');
        $basePath = $baseDir === '' ? '' : '/' . $baseDir;
        $siteUrl = $scheme . '://' . $host . $basePath;
        $websiteEmail = trim((string)($settings['website_email'] ?? ''));
        $supportEmail = filter_var($websiteEmail, FILTER_VALIDATE_EMAIL) ? $websiteEmail : ('tinycms@' . (explode(':', $host)[0] ?? 'localhost'));

        return [
            'name' => I18n::t('auth.reset_email_generic_user'),
            'email' => trim($to),
            'token' => '',
            'site_name' => trim((string)($settings['sitename'] ?? 'TinyCMS')) !== '' ? (string)$settings['sitename'] : 'TinyCMS',
            'site_url' => $siteUrl,
            'login_url' => $siteUrl . '/auth/login',
            'support_email' => $supportEmail,
            'reset_link' => '',
            'link' => '',
        ];
    }

    private function normalizeVars(array $vars): array
    {
        $normalized = [];
        foreach ($vars as $key => $value) {
            $normalized[trim((string)$key)] = (string)$value;
        }

        $link = trim((string)($normalized['reset_link'] ?? ($normalized['link'] ?? '')));
        if ($link !== '') {
            $normalized['reset_link'] = $link;
            $normalized['link'] = $link;
        }

        if (trim((string)($normalized['token'] ?? '')) === '' && $link !== '') {
            $token = (string)parse_url($link, PHP_URL_QUERY);
            parse_str($token, $query);
            $normalized['token'] = trim((string)($query['token'] ?? ''));
        }

        return $normalized;
    }
}
