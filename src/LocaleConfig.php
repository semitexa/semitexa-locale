<?php

declare(strict_types=1);

namespace Semitexa\Locale;

use Semitexa\Core\Environment;

readonly class LocaleConfig
{
    /**
     * @param string[] $supportedLocales
     * @param string[] $resolverPriority
     */
    public function __construct(
        public bool $enabled = true,
        public string $defaultLocale = 'en',
        public string $fallbackLocale = 'en',
        public array $supportedLocales = ['en'],
        public array $resolverPriority = ['path', 'header'],
    ) {}

    public static function fromEnvironment(): self
    {
        $enabled = Environment::getEnvValue('LOCALE_ENABLED') !== 'false';
        $defaultLocale = Environment::getEnvValue('LOCALE_DEFAULT', 'en');
        $fallbackLocale = Environment::getEnvValue('LOCALE_FALLBACK', 'en');
        $strategy = Environment::getEnvValue('LOCALE_STRATEGY', 'path');

        $supportedRaw = Environment::getEnvValue('LOCALE_SUPPORTED', 'en,uk,de,pl,ru');
        $supportedLocales = array_filter(array_map('trim', explode(',', (string) $supportedRaw)));

        $cookieEnabled = Environment::getEnvValue('LOCALE_COOKIE_ENABLED') === 'true';

        $resolverPriority = match ($strategy) {
            'header' => $cookieEnabled ? ['cookie', 'header'] : ['header'],
            'path' => $cookieEnabled ? ['cookie', 'path'] : ['path'],
            'both' => $cookieEnabled ? ['cookie', 'path', 'header'] : ['path', 'header'],
            default => $cookieEnabled ? ['cookie', 'path'] : ['path'],
        };

        return new self(
            enabled: $enabled,
            defaultLocale: $defaultLocale,
            fallbackLocale: $fallbackLocale,
            supportedLocales: array_values($supportedLocales),
            resolverPriority: $resolverPriority,
        );
    }
}
