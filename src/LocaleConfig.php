<?php

declare(strict_types=1);

namespace Semitexa\Locale;

use Semitexa\Core\Environment;

readonly class LocaleConfig
{
    private const DEFAULT_SUPPORTED_LOCALES = ['en', 'uk', 'de', 'pl', 'ru'];
    private const DEFAULT_RESOLVER_PRIORITY = ['path', 'header'];

    /**
     * @param string[] $supportedLocales
     * @param string[] $resolverPriority
     */
    public function __construct(
        public bool $enabled = true,
        public string $defaultLocale = 'en',
        public string $fallbackLocale = 'en',
        public array $supportedLocales = self::DEFAULT_SUPPORTED_LOCALES,
        public array $resolverPriority = self::DEFAULT_RESOLVER_PRIORITY,
    ) {}

    public static function fromEnvironment(): self
    {
        $enabled = Environment::getEnvValue('LOCALE_ENABLED') !== 'false';
        $defaultLocale = Environment::getEnvValue('LOCALE_DEFAULT', 'en');
        $fallbackLocale = Environment::getEnvValue('LOCALE_FALLBACK', 'en');
        $strategy = Environment::getEnvValue('LOCALE_STRATEGY');

        $supportedRaw = Environment::getEnvValue('LOCALE_SUPPORTED');
        $supportedLocales = $supportedRaw !== null
            ? array_values(array_filter(array_map('trim', explode(',', $supportedRaw))))
            : self::DEFAULT_SUPPORTED_LOCALES;

        $cookieEnabled = Environment::getEnvValue('LOCALE_COOKIE_ENABLED') === 'true';

        $resolverPriority = match ($strategy) {
            'header' => $cookieEnabled ? ['cookie', 'header'] : ['header'],
            'path' => $cookieEnabled ? ['cookie', 'path'] : ['path'],
            'both' => $cookieEnabled ? ['cookie', 'path', 'header'] : ['path', 'header'],
            default => $cookieEnabled
                ? array_merge(['cookie'], self::DEFAULT_RESOLVER_PRIORITY)
                : self::DEFAULT_RESOLVER_PRIORITY,
        };

        return new self(
            enabled: $enabled,
            defaultLocale: $defaultLocale,
            fallbackLocale: $fallbackLocale,
            supportedLocales: $supportedLocales,
            resolverPriority: $resolverPriority,
        );
    }
}
