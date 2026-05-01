<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\I18n;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Context\LocaleContextStore;
use Semitexa\Locale\Context\LocaleManager;
use Semitexa\Locale\Application\Service\I18n\TranslationCatalog;
use Semitexa\Locale\Application\Service\I18n\TranslationService;

final class TranslationServiceTest extends TestCase
{
    private TranslationCatalog $catalog;
    private LocaleManager $localeContext;
    private TranslationService $service;

    protected function setUp(): void
    {
        $this->catalog = new TranslationCatalog();
        $this->localeContext = new LocaleManager();
        $this->service = new TranslationService($this->catalog, $this->localeContext);

        $this->catalog->addMessages('en', 'Demo', [
            'welcome' => 'Welcome',
            'hello' => 'Hello, {{name}}!',
            'items_pipe' => '{{count}} item|{{count}} items',
            'items' => ['one' => '{{count}} item', 'other' => '{{count}} items'],
        ]);

        $this->catalog->addMessages('uk', 'Demo', [
            'welcome' => 'Ласкаво просимо',
            'items' => [
                'one' => '{{count}} елемент',
                'few' => '{{count}} елементи',
                'many' => '{{count}} елементів',
            ],
        ]);

        LocaleContextStore::setLocale('en');
        LocaleContextStore::setFallbackLocale('en');
    }

    protected function tearDown(): void
    {
        LocaleContextStore::clearFallback();
    }

    #[Test]
    public function trans_returns_message(): void
    {
        $this->assertSame('Welcome', $this->service->trans('welcome'));
    }

    #[Test]
    public function trans_interpolates_params(): void
    {
        $this->assertSame('Hello, World!', $this->service->trans('hello', ['name' => 'World']));
    }

    #[Test]
    public function trans_returns_key_when_missing(): void
    {
        $this->assertSame('missing.key', $this->service->trans('missing.key'));
    }

    #[Test]
    public function trans_falls_back_to_fallback_locale(): void
    {
        LocaleContextStore::setLocale('de');
        LocaleContextStore::setFallbackLocale('en');

        $this->assertSame('Welcome', $this->service->trans('welcome'));
    }

    #[Test]
    public function trans_with_explicit_locale(): void
    {
        $this->assertSame('Ласкаво просимо', $this->service->trans('welcome', locale: 'uk'));
    }

    #[Test]
    public function trans_choice_with_cldr_array_english(): void
    {
        $this->assertSame('1 item', $this->service->transChoice('items', 1));
        $this->assertSame('5 items', $this->service->transChoice('items', 5));
    }

    #[Test]
    public function trans_choice_with_cldr_array_ukrainian(): void
    {
        LocaleContextStore::setLocale('uk');

        $this->assertSame('1 елемент', $this->service->transChoice('items', 1));
        $this->assertSame('2 елементи', $this->service->transChoice('items', 2));
        $this->assertSame('5 елементів', $this->service->transChoice('items', 5));
        $this->assertSame('21 елемент', $this->service->transChoice('items', 21));
        $this->assertSame('22 елементи', $this->service->transChoice('items', 22));
        $this->assertSame('11 елементів', $this->service->transChoice('items', 11));
    }

    #[Test]
    public function trans_choice_with_legacy_pipe_format(): void
    {
        $this->assertSame('1 item', $this->service->transChoice('items_pipe', 1));
        $this->assertSame('5 items', $this->service->transChoice('items_pipe', 5));
    }

    #[Test]
    public function trans_choice_returns_key_when_missing(): void
    {
        $this->assertSame('missing', $this->service->transChoice('missing', 3));
    }

    #[Test]
    public function has_translation(): void
    {
        $this->assertTrue($this->service->hasTranslation('welcome'));
        $this->assertFalse($this->service->hasTranslation('nope'));
    }

    #[Test]
    public function module_namespaced_key(): void
    {
        $this->assertSame('Welcome', $this->service->trans('Demo.welcome'));
    }

    #[Test]
    public function get_locale_returns_current(): void
    {
        LocaleContextStore::setLocale('de');

        $this->assertSame('de', $this->service->getLocale());
    }
}
