<?php
declare(strict_types=1);

namespace App\Service\Application;

final class Ai
{
    private const GOOGLE_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemma-3-27b-it:generateContent';

    public function generateWithGoogle(string $apiKey, string $prompt): array
    {
        $key = trim($apiKey);
        $text = trim($prompt);
        if ($key === '' || $text === '') {
            return ['success' => false, 'error' => 'INVALID_INPUT'];
        }

        $payload = json_encode([
            'contents' => [[
                'parts' => [[
                    'text' => $text,
                ]],
            ]],
        ], JSON_UNESCAPED_UNICODE);

        if (!is_string($payload) || $payload === '') {
            return ['success' => false, 'error' => 'INVALID_PAYLOAD'];
        }

        $response = $this->postJson(self::GOOGLE_ENDPOINT, $payload, [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $key,
        ]);

        if ($response === '') {
            return ['success' => false, 'error' => 'REQUEST_FAILED'];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'error' => 'INVALID_RESPONSE'];
        }

        $parts = (array)($decoded['candidates'][0]['content']['parts'] ?? []);
        $result = '';
        foreach ($parts as $part) {
            $result .= (string)($part['text'] ?? '');
        }

        $value = trim($result);
        if ($value === '') {
            return ['success' => false, 'error' => 'EMPTY_RESULT'];
        }

        return ['success' => true, 'text' => $value];
    }

    private function postJson(string $url, string $body, array $headers): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl !== false) {
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_CONNECTTIMEOUT => 4,
                ]);
                $result = curl_exec($curl);
                curl_close($curl);
                if (is_string($result)) {
                    return $result;
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 20,
                'header' => implode("\r\n", $headers),
                'content' => $body,
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? $result : '';
    }
}
