<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Ai as AiService;
use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Ai extends BaseAdmin
{
    public function __construct(
        Auth $authService,
        private AiService $ai,
        private SettingsService $settings,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function generateApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
            return;
        }

        $apiKey = trim((string)($this->settings->resolved()['google_api_key'] ?? ''));
        $model = trim((string)($this->settings->resolved()['google_api_model'] ?? 'gemma-3-27b-it'));
        if ($apiKey === '') {
            $this->apiError('AI_NOT_CONFIGURED', I18n::t('content.ai_not_configured'), 422);
            return;
        }

        $instruction = trim((string)($_POST['instruction'] ?? ''));
        $sourceInput = trim((string)($_POST['source'] ?? ''));
        $body = (string)($_POST['body'] ?? '');
        $count = max(1, min(10, (int)($_POST['count'] ?? 1)));
        $target = trim((string)($_POST['target'] ?? ''));
        if ($instruction === '' || !in_array($target, ['name', 'excerpt', 'terms', 'body'], true)) {
            $this->apiError('INVALID_INPUT', I18n::t('common.invalid_data'));
            return;
        }

        $source = $sourceInput !== '' ? $sourceInput : $body;
        if ($target !== 'body') {
            $source = $this->normalizeSourceText($source, 300);
        }
        if ($source === '') {
            $this->apiError('EMPTY_SOURCE', I18n::t('content.ai_empty_source'));
            return;
        }

        $prompt = $this->buildPrompt($target, $instruction, $source);
        $result = $this->ai->generateWithGoogle($apiKey, $prompt, $model);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('AI_FAILED', I18n::t('content.ai_failed'), 422);
            return;
        }

        $text = (string)($result['text'] ?? '');
        if ($target === 'body') {
            $text = $this->normalizeBodyHtml($text);
        }
        $variants = [$text];
        if ($target !== 'body') {
            $text = trim($text);
            $variants = $this->extractVariants($text, $count);
            if ($variants === []) {
                $variants = [$text];
            }
        }

        $this->apiOk([
            'target' => $target,
            'text' => (string)($variants[0] ?? ''),
            'variants' => $variants,
        ]);
    }

    private function buildPrompt(string $target, string $instruction, string $source): string
    {
        if ($target === 'name') {
            return "Task: Generate exactly three SEO title variants for a news article.\n"
                . "Rules: each variant on separate line, max 70 characters, plain text only, no quotes, no numbering, no explanations.\n"
                . "Localized instruction:\n{$instruction}\n\n"
                . "Article source:\n{$source}";
        }

        if ($target === 'excerpt') {
            return "Task: Generate exactly three excerpt variants for a news article.\n"
                . "Rules: each variant on separate line, max 500 characters, plain text only, no numbering, no explanations.\n"
                . "Localized instruction:\n{$instruction}\n\n"
                . "Article source:\n{$source}";
        }

        if ($target === 'body') {
            return "Task: Rewrite selected article HTML fragment.\n"
                . "Rules: Keep HTML formatting, return only clean HTML fragment, no commentary, no tips, no explanations.\n"
                . "User instruction:\n{$instruction}\n\n"
                . "Selected HTML:\n{$source}";
        }

        return "Task: Generate exactly ten article tags.\n"
            . "Rules: one tag per line, plain text only, short tag, no numbering, no explanations.\n"
            . "Localized instruction:\n{$instruction}\n\n"
            . "Article source:\n{$source}";
    }

    private function extractVariants(string $text, int $limit): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $variants = [];

        foreach ($lines as $line) {
            $value = trim((string)$line);
            $value = preg_replace('/^[-*]\s+/', '', $value) ?? $value;
            $value = preg_replace('/^\d+\.\s+/', '', $value) ?? $value;
            if ($value === '') {
                continue;
            }
            $variants[] = $value;
            if (count($variants) >= $limit) {
                break;
            }
        }

        return $variants;
    }

    private function normalizeSourceText(string $value, int $maxWords): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($clean === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $clean) ?? $clean;
        $words = preg_split('/\s+/', trim($normalized)) ?: [];
        $slice = array_slice(array_filter($words, static fn($word): bool => $word !== ''), 0, max(1, $maxWords));
        return trim(implode(' ', $slice));
    }

    private function normalizeBodyHtml(string $value): string
    {
        $clean = trim($value);
        if (preg_match('/^\s*```(?:html)?\s*(.*?)\s*```\s*$/is', $clean, $match) === 1) {
            return trim((string)($match[1] ?? ''));
        }

        return $clean;
    }
}
