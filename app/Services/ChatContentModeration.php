<?php

namespace App\Services;

use Illuminate\Support\Str;

class ChatContentModeration
{
    /** Generic URL pattern (http, https, or common tld-style) */
    private const URL_PATTERN = '/(?:https?:\/\/|www\.)[^\s<>"\']+|(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}(?:\/[^\s<>"\']*)?/iu';

    public function __construct(
        private readonly array $config
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('chat_moderation', []));
    }

    /**
     * Check if message content is allowed. Returns ['allowed' => bool, 'reason' => string|null].
     */
    public function check(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return ['allowed' => false, 'reason' => 'Message cannot be empty.'];
        }

        $lower = Str::lower($body);

        // 1. Links / URLs
        if ($this->containsUrl($body)) {
            if (! empty($this->config['block_links'])) {
                return ['allowed' => false, 'reason' => 'Sharing links is not allowed in chat. Keep conversations on the app.'];
            }
            if ($this->containsSocialLink($body)) {
                return ['allowed' => false, 'reason' => 'Sharing social media or external links is not allowed. Keep conversations on the app.'];
            }
        }

        // 2. Social media links (even if block_links is false)
        if ($this->containsSocialLink($body)) {
            return ['allowed' => false, 'reason' => 'Sharing social media or external links is not allowed. Keep conversations on the app.'];
        }

        // 3. Money / payment content
        $moneyReason = $this->checkMoneyPhrases($lower);
        if ($moneyReason !== null) {
            return ['allowed' => false, 'reason' => $moneyReason];
        }

        // 4. Banned keywords
        $keywordReason = $this->checkBannedKeywords($lower);
        if ($keywordReason !== null) {
            return ['allowed' => false, 'reason' => $keywordReason];
        }

        return ['allowed' => true, 'reason' => null];
    }

    private function containsUrl(string $body): bool
    {
        return (bool) preg_match(self::URL_PATTERN, $body);
    }

    private function containsSocialLink(string $body): bool
    {
        $domains = $this->config['social_domains'] ?? [];
        $lower = Str::lower($body);
        foreach ($domains as $domain) {
            if (Str::contains($lower, $domain)) {
                return true;
            }
        }
        return false;
    }

    private function checkMoneyPhrases(string $lower): ?string
    {
        $phrases = $this->config['money_phrases'] ?? [];
        foreach ($phrases as $phrase) {
            if (str_contains($phrase, '.*') || str_contains($phrase, '\d')) {
                $pattern = '#' . str_replace('#', '\\#', $phrase) . '#iu';
                if (@preg_match($pattern, $lower) === 1) {
                    return 'Messages about money, payments, or financial requests are not allowed.';
                }
            } elseif (Str::contains($lower, Str::lower($phrase))) {
                return 'Messages about money, payments, or financial requests are not allowed.';
            }
        }
        return null;
    }

    private function checkBannedKeywords(string $lower): ?string
    {
        $keywords = $this->config['banned_keywords'] ?? [];
        foreach ($keywords as $keyword) {
            if (str_contains($keyword, '.*')) {
                $pattern = '#' . str_replace('#', '\\#', $keyword) . '#iu';
                if (@preg_match($pattern, $lower) === 1) {
                    return 'This message contains content that is not allowed. Please keep the conversation appropriate and on the app.';
                }
            } elseif (Str::contains($lower, Str::lower($keyword))) {
                return 'This message contains content that is not allowed. Please keep the conversation appropriate and on the app.';
            }
        }
        return null;
    }
}
