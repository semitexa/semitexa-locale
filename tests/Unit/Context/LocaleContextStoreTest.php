<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Context\LocaleContextStore;

final class LocaleContextStoreTest extends TestCase
{
    protected function tearDown(): void
    {
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
}
