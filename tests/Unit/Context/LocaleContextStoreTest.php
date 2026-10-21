<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Lifecycle\PerRequestStateRegistry;
use Semitexa\Locale\Context\LocaleContextStore;

final class LocaleContextStoreTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $environment = [];

    protected function setUp(): void
    {
        // Without a request the store answers the configured locales, so the
        // tests that expect 'en' pin it instead of trusting the environment.
        foreach (['LOCALE_DEFAULT', 'LOCALE_FALLBACK'] as $key) {
            $this->environment[$key] = getenv($key);
            putenv($key . '=en');
        }
        LocaleContextStore::clearFallback();
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            $value === false ? putenv($key) : putenv($key . '=' . $value);
        }
        LocaleContextStore::clearFallback();
    }

    #[Test]
    public function set_and_get_locale(): void
    {
        LocaleContextStore::setLocale('de');

        $this->assertSame('de', LocaleContextStore::getLocale());
    }

    #[Test]
    public function default_locale_is_en(): void
    {
        $this->assertSame('en', LocaleContextStore::getLocale());
    }

    #[Test]
    public function set_and_get_fallback_locale(): void
    {
        LocaleContextStore::setFallbackLocale('uk');

        $this->assertSame('uk', LocaleContextStore::getFallbackLocale());
    }

    #[Test]
    public function default_fallback_locale_is_en(): void
    {
        $this->assertSame('en', LocaleContextStore::getFallbackLocale());
    }

    #[Test]
    public function clear_fallback_resets_to_defaults(): void
    {
        LocaleContextStore::setLocale('de');
        LocaleContextStore::setFallbackLocale('uk');

        LocaleContextStore::clearFallback();

        $this->assertSame('en', LocaleContextStore::getLocale());
        $this->assertSame('en', LocaleContextStore::getFallbackLocale());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function the_end_of_a_unit_of_work_resets_the_fallback_for_the_next_one(): void
    {
        // The queue worker runs jobs outside a coroutine and calls resetAll()
        // after each: a locale one tenant's job set must not reach the next.
        // Separate process: registering the resetter and calling resetAll()
        // are process-wide, so neither may leak into (or depend on) other tests.
        LocaleContextStore::setLocale('fr-FR');
        LocaleContextStore::setSupportedLocales(['fr-FR', 'en']);

        PerRequestStateRegistry::resetAll();

        $this->assertSame('en', LocaleContextStore::getLocale());
        $this->assertSame([], LocaleContextStore::getSupportedLocales());
    }

    #[Test]
    public function without_a_request_the_locale_is_the_configured_default(): void
    {
        // CLI and queue jobs got 'en' whatever LOCALE_DEFAULT said.
        putenv('LOCALE_DEFAULT=uk');
        putenv('LOCALE_FALLBACK=de');
        LocaleContextStore::clearFallback();

        $this->assertSame('uk', LocaleContextStore::getLocale());
        $this->assertSame('uk', LocaleContextStore::getDefaultLocale());
        $this->assertSame('de', LocaleContextStore::getFallbackLocale());
    }
}
