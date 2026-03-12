<?php

declare(strict_types=1);

namespace Semitexa\Locale\I18n;

use Semitexa\Core\Locale\LocaleContextInterface;

final class TranslationService
{
    public function __construct(
        private readonly TranslationCatalog $catalog,
        private readonly LocaleContextInterface $localeContext,
    ) {}

    public function trans(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->localeContext->getLocale();
        $fallback = $this->localeContext->getFallbackLocale();

        $message = $this->catalog->get($key, $locale)
            ?? ($fallback !== $locale ? $this->catalog->get($key, $fallback) : null)
            ?? $key;

        // If the value is an array (plural forms stored as object in JSON),
        // return the 'other' form for non-plural trans() calls
        if (is_array($message)) {
            $message = $message['other'] ?? $message['one'] ?? $key;
        }

        return $this->interpolate($message, $params);
    }

    public function transChoice(string $key, int $count, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->localeContext->getLocale();
        $fallback = $this->localeContext->getFallbackLocale();

        $raw = $this->catalog->get($key, $locale)
            ?? ($fallback !== $locale ? $this->catalog->get($key, $fallback) : null);

        if ($raw === null) {
            return $this->interpolate($key, $params + ['count' => (string) $count]);
        }

        $message = $this->resolvePlural($raw, $locale, $count) ?? $key;

        $message = str_replace(':count', (string) $count, $message);
        $message = str_replace('{{count}}', (string) $count, $message);

        return $this->interpolate($message, $params + ['count' => (string) $count]);
    }

    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale ??= $this->localeContext->getLocale();

        return $this->catalog->has($key, $locale);
    }

    public function getLocale(): string
    {
        return $this->localeContext->getLocale();
    }

    /**
     * Resolve the correct plural form from a raw catalog value.
     *
     * Supports two formats:
     * 1. Array with CLDR keys: {"one": "...", "few": "...", "many": "...", "other": "..."}
     * 2. Legacy pipe-delimited string: "one form|other form"
     */
    private function resolvePlural(string|array $raw, string $locale, int $count): ?string
    {
        $category = PluralRules::category($locale, $count);

        if (is_array($raw)) {
            return $raw[$category] ?? $raw['other'] ?? $raw['one'] ?? null;
        }

        // Legacy pipe-delimited format: "one|other"
        if (str_contains($raw, '|')) {
            $parts = array_map('trim', explode('|', $raw));

            return match ($category) {
                'one' => $parts[0] ?? $raw,
                default => $parts[1] ?? $parts[0] ?? $raw,
            };
        }

        return $raw;
    }

    private function interpolate(string $message, array $params): string
    {
        foreach ($params as $k => $v) {
            $message = str_replace('{{' . $k . '}}', (string) $v, $message);
        }

        return $message;
    }
}
