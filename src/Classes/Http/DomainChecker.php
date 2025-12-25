<?php

namespace PROLANCEE\Support\Classes\Http;

use Exception;

final class DomainChecker
{
    /**
     * Cached list of allowed domains.
     *
     * @var array<int, string>
     */
    private static array $allowedDomains = [];

    /**
     * Boot method to load allowed domains from config (only once).
     *
     * @return void
     */
    public static function boot(): void
    {
        if (!empty(self::$allowedDomains)) {
            return;
        }

        $configured = self::allowed_domains();

        if (is_string($configured)) {
            self::$allowedDomains = array_map('trim', explode(',', $configured));
        } elseif (is_array($configured)) {
            self::$allowedDomains = $configured;
        }

        if (empty(self::$allowedDomains)) {
            self::$allowedDomains = [
                'localhost',
                '127.0.0.1',
                parse_url(config('app.url'), PHP_URL_HOST),
            ];
        }

        self::$allowedDomains = array_unique(array_filter(self::$allowedDomains));
    }

    /**
     * Ensure the current request domain is authorized.
     *
     * @throws Exception If the domain is not in the allowed list.
     * @return void
     */
    public static function checkDomainBySameSite(): void
    {
        // Get the current domain from the request root
        $currentDomain = parse_url(request()->root(), PHP_URL_HOST) ?? '';

        // Normalize local IP to 'localhost'
        if ($currentDomain === '127.0.0.1') {
            $currentDomain = 'localhost';
        }

        // If cross-origin, prefer Referer domain
        $refererDomain = parse_url(request()->header('Referer'), PHP_URL_HOST) ?? '';
        if ($refererDomain) {
            $currentDomain = $refererDomain === '127.0.0.1' ? 'localhost' : $refererDomain;
        }

        // Validate domain against allowed list
        $isValidDomain = false;

        foreach (self::$allowedDomains as $allowed) {
            if ($allowed === '*') {
                $isValidDomain = true;
                break;
            }

            if ($currentDomain === $allowed) {
                $isValidDomain = true;
                break;
            }

            if (str_starts_with($allowed, '*.') && str_ends_with($currentDomain, substr($allowed, 1))) {
                $isValidDomain = true;
                break;
            }
        }

        if (!$isValidDomain) {
            throw new Exception("Encryption not allowed from domain: {$currentDomain}");
        }
    }

    /**
     * Get the list of allowed domains from config with normalization & validation.
     *
     * @return array<int, string>
     */
    private static function allowed_domains(): array
    {
        return (function (): array {
            $raw = trim((string) config('prolancee.support.allowed_domains', '*'));
            if ($raw === '') {
                return [];
            }

            $normalized = str_replace(['|', ','], ',', $raw);
            $domains = array_map('trim', explode(',', $normalized));

            return array_values(array_unique(array_filter($domains, function (string $domain): bool {
                return $domain === '*'
                    || $domain === 'localhost'
                    || $domain === '127.0.0.1'
                    || preg_match('/^(\*\.)?[\w.-]+\.[a-z]{2,}$/i', $domain);
            })));
        })();
    }
}
