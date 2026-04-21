<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Support\I18n;
use App\Service\Support\Mailer;
use App\Service\Support\RequestContext;

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
        [$subject, $body] = $this->templateParts($templateKey, $resolvedVars);

        return $this->mailer->send($to, $subject, $body, $this->senderFromSettings());
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
        $scheme = RequestContext::scheme();
        $authority = RequestContext::authority();
        $siteUrl = $scheme . '://' . $authority . RequestContext::path();
        $websiteEmail = trim((string)($settings['website_email'] ?? ''));
        $supportEmail = filter_var($websiteEmail, FILTER_VALIDATE_EMAIL) ? $websiteEmail : ('tinycms@' . RequestContext::domain());

        $siteName = (string)($settings['sitename'] ?? 'TinyCMS');

        return [
            'name' => I18n::t('auth.reset_email_generic_user'),
            'email' => trim($to),
            'token' => '',
            'site_name' => $siteName,
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

    private function templateParts(string $templateKey, array $vars): array
    {
        $subject = trim(I18n::t($templateKey . '.subject'));
        $body = $this->template(I18n::t($templateKey . '.body'), $vars);
        return [$subject, $body];
    }

    private function senderFromSettings(): ?string
    {
        $websiteEmail = trim((string)($this->settings->resolved()['website_email'] ?? ''));
        return filter_var($websiteEmail, FILTER_VALIDATE_EMAIL) ? $websiteEmail : null;
    }
}
