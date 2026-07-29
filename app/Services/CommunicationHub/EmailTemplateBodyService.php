<?php

namespace App\Services\CommunicationHub;

final class EmailTemplateBodyService
{
    /** Convert stored HTML (or plain text) into readable text for editing and compose. */
    public function toPlainText(string $body): string
    {
        $text = trim($body);
        if ($text === '') {
            return '';
        }

        if (str_contains($text, '&lt;') && ! str_contains($text, '<')) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (! str_contains($text, '<')) {
            return $this->normalizeWhitespace($text);
        }

        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|h[1-6]|div|li|tr)>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->normalizeWhitespace($text);
    }

    /** Turn plain-text template content into simple HTML for outbound email. */
    public function toHtml(string $plain): string
    {
        $plain = $this->normalizeWhitespace($plain);
        if ($plain === '') {
            return '';
        }

        $paragraphs = preg_split("/\n{2,}/", $plain) ?: [];
        $html = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $lines = preg_split("/\r\n|\n|\r/", $paragraph) ?: [];
            $escaped = array_map(
                static fn (string $line): string => e($line),
                array_filter($lines, static fn (string $line): bool => $line !== '')
            );
            $html[] = '<p>'.implode('<br>', $escaped).'</p>';
        }

        return implode('', $html);
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
