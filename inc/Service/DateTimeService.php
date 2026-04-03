<?php
declare(strict_types=1);

namespace App\Service;

final class DateTimeService
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function toStorage(string $value): ?string
    {
        $clean = trim($value);

        if ($clean === '') {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i'];
        $timezone = new \DateTimeZone($this->timezoneValue());

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $clean, $timezone);

            if ($date instanceof \DateTimeImmutable) {
                return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($clean);
        if ($timestamp === false) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public function toInputValue(string $value): string
    {
        $date = $this->parseStorage($value);
        return $date?->format('Y-m-d\\TH:i') ?? '';
    }

    public function formatDateTime(string $value): string
    {
        $date = $this->parseStorage($value);

        if ($date === null) {
            return $value;
        }

        return $date->format($this->datePattern() . ' ' . $this->timePattern());
    }

    public function utcTimezoneOptions(): array
    {
        $options = [];

        for ($offset = -12 * 60; $offset <= 14 * 60; $offset += 30) {
            $sign = $offset < 0 ? '-' : '+';
            $abs = abs($offset);
            $hours = str_pad((string)intdiv($abs, 60), 2, '0', STR_PAD_LEFT);
            $minutes = str_pad((string)($abs % 60), 2, '0', STR_PAD_LEFT);
            $value = $offset === 0 ? 'UTC' : 'UTC' . $sign . $hours . ':' . $minutes;
            $options[$value] = $value;
        }

        return $options;
    }

    private function parseStorage(string $value): ?\DateTimeImmutable
    {
        $clean = trim($value);

        if ($clean === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $clean, new \DateTimeZone('UTC'));
        if (!$date instanceof \DateTimeImmutable) {
            $timestamp = strtotime($clean);
            if ($timestamp === false) {
                return null;
            }
            $date = new \DateTimeImmutable('@' . $timestamp);
        }

        return $date->setTimezone(new \DateTimeZone($this->timezoneValue()));
    }

    private function timezoneValue(): string
    {
        $settings = $this->settings->resolved();
        $timezone = trim((string)($settings['custom']['timezone'] ?? 'UTC'));

        if ($timezone === 'UTC') {
            return 'UTC';
        }

        if (preg_match('/^UTC([+-]\d{2}:\d{2})$/', $timezone, $matches) === 1) {
            return $matches[1];
        }

        return 'UTC';
    }

    private function datePattern(): string
    {
        $settings = $this->settings->resolved();
        $mode = (string)($settings['custom']['dateformat_mode'] ?? 'cs');
        $custom = trim((string)($settings['custom']['dateformat_custom'] ?? 'j. n. Y'));

        if ($mode === 'db') {
            return 'Y-m-d';
        }

        if ($mode === 'custom' && $custom !== '') {
            return $custom;
        }

        return 'j. n. Y';
    }

    private function timePattern(): string
    {
        $settings = $this->settings->resolved();
        $mode = (string)($settings['custom']['timeformat_mode'] ?? 'short');
        $custom = trim((string)($settings['custom']['timeformat_custom'] ?? 'H:i'));

        if ($mode === 'custom' && $custom !== '') {
            return $custom;
        }

        return 'H:i';
    }
}
