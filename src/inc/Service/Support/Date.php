<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Date
{
    private const FALLBACK_DATE_FORMAT = 'd.m.Y';
    private const FALLBACK_DATETIME_FORMAT = 'd.m.Y H:i';
    private const DATE_FORMATS = ['d.m.Y', 'j.n.Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'F j, Y'];
    private const DATETIME_FORMATS = ['d.m.Y H:i', 'j.n.Y H:i', 'Y-m-d H:i', 'd/m/Y H:i', 'm/d/Y h:i A', 'F j, Y H:i'];
    private const EXAMPLE_TIME = 1776803400;

    private static string $defaultDateTimeFormat = self::FALLBACK_DATETIME_FORMAT;

    private string $dateFormat;
    private string $dateTimeFormat;

    public function __construct(string $dateFormat, string $dateTimeFormat)
    {
        $this->dateFormat = self::normalizeDateFormat($dateFormat);
        $this->dateTimeFormat = self::normalizeDateTimeFormat($dateTimeFormat);
    }

    public static function configure(string $dateTimeFormat): void
    {
        self::$defaultDateTimeFormat = self::normalizeDateTimeFormat($dateTimeFormat);
    }

    public static function dateFormatOptions(): array
    {
        return self::formatOptions(self::DATE_FORMATS);
    }

    public static function dateTimeFormatOptions(): array
    {
        return self::formatOptions(self::DATETIME_FORMATS);
    }

    public static function normalizeDateFormat(string $format): string
    {
        $clean = trim($format);
        return in_array($clean, self::DATE_FORMATS, true) ? $clean : self::FALLBACK_DATE_FORMAT;
    }

    public static function normalizeDateTimeFormat(string $format): string
    {
        $clean = trim($format);
        return in_array($clean, self::DATETIME_FORMATS, true) ? $clean : self::FALLBACK_DATETIME_FORMAT;
    }

    public static function formatDateTimeValue(string $value): string
    {
        $timestamp = strtotime(trim($value));
        return $timestamp === false ? '' : date(self::$defaultDateTimeFormat, $timestamp);
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

    private static function formatOptions(array $formats): array
    {
        $options = [];
        foreach ($formats as $format) {
            $options[$format] = date($format, self::EXAMPLE_TIME);
        }
        return $options;
    }
}
