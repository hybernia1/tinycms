<?php
declare(strict_types=1);

namespace App\Service\Support;

final class DateTimeFormatter
{
    private string $dateFormat;
    private string $dateTimeFormat;

    public function __construct(string $dateFormat, string $dateTimeFormat)
    {
        $this->dateFormat = trim($dateFormat) !== '' ? $dateFormat : 'Y-m-d';
        $this->dateTimeFormat = trim($dateTimeFormat) !== '' ? $dateTimeFormat : 'Y-m-d H:i:s';
    }

    public function formatDate(?string $value, string $fallback = ''): string
    {
        $timestamp = $this->resolveTimestamp($value);
        if ($timestamp === null) {
            return $fallback;
        }

        return date($this->dateFormat, $timestamp);
    }

    public function formatDateTime(?string $value, string $fallback = ''): string
    {
        $timestamp = $this->resolveTimestamp($value);
        if ($timestamp === null) {
            return $fallback;
        }

        return date($this->dateTimeFormat, $timestamp);
    }

    public function toInputDateTimeLocal(?string $value, string $fallback = ''): string
    {
        $timestamp = $this->resolveTimestamp($value);
        if ($timestamp === null) {
            return $fallback;
        }

        return date('Y-m-d\\TH:i', $timestamp);
    }

    private function resolveTimestamp(?string $value): ?int
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return $timestamp;
    }
}
