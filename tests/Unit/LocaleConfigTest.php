<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\LocaleConfig;

final class LocaleConfigTest extends TestCase
{
    #[Test]
    public function defaults_are_sensible(): void
    {
        $config = new LocaleConfig();

        $this->assertTrue($config->enabled);
        $this->assertSame('en', $config->defaultLocale);
        $this->assertSame('en', $config->fallbackLocale);
        $this->assertSame(['en', 'uk', 'de', 'pl', 'ru'], $config->supportedLocales);
        $this->assertSame(['path', 'header'], $config->resolverPriority);
    }

    #[Test]
    public function accepts_custom_values(): void
    {
        $config = new LocaleConfig(
            enabled: false,
            defaultLocale: 'uk',
            fallbackLocale: 'en',
            supportedLocales: ['en', 'uk', 'de'],
            resolverPriority: ['header'],
        );

        $this->assertFalse($config->enabled);
        $this->assertSame('uk', $config->defaultLocale);
        $this->assertSame(['en', 'uk', 'de'], $config->supportedLocales);
        $this->assertSame(['header'], $config->resolverPriority);
    }

    #[Test]
    public function from_environment_uses_defaults_when_no_env(): void
    {
        putenv('LOCALE_ENABLED');
        putenv('LOCALE_DEFAULT');
        putenv('LOCALE_FALLBACK');
        putenv('LOCALE_STRATEGY');
        putenv('LOCALE_SUPPORTED');
        putenv('LOCALE_COOKIE_ENABLED');

        $config = LocaleConfig::fromEnvironment();

        $this->assertTrue($config->enabled);
        $this->assertSame('en', $config->defaultLocale);
        $this->assertNotEmpty($config->supportedLocales);
    }
}
