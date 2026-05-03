<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\I18n;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Application\Service\I18n\TranslationCatalog;

final class TranslationCatalogTest extends TestCase
{
    #[Test]
    public function stores_and_retrieves_messages(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['hello' => 'Hello']);

        $this->assertSame('Hello', $catalog->get('hello', 'en'));
        $this->assertSame('Hello', $catalog->get('Demo.hello', 'en'));
    }

    #[Test]
    public function returns_null_for_missing_key(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['hello' => 'Hello']);

        $this->assertNull($catalog->get('missing', 'en'));
    }

    #[Test]
    public function returns_null_for_missing_locale(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['hello' => 'Hello']);

        $this->assertNull($catalog->get('hello', 'de'));
    }

    #[Test]
    public function first_module_wins_for_unnamespaced_keys(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'ModA', ['hello' => 'Hello from A']);
        $catalog->addMessages('en', 'ModB', ['hello' => 'Hello from B']);

        $this->assertSame('Hello from A', $catalog->get('hello', 'en'));
        $this->assertSame('Hello from A', $catalog->get('ModA.hello', 'en'));
        $this->assertSame('Hello from B', $catalog->get('ModB.hello', 'en'));
    }

    #[Test]
    public function stores_plural_arrays(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('uk', 'Demo', [
            'items' => ['one' => '{{count}} елемент', 'few' => '{{count}} елементи', 'many' => '{{count}} елементів'],
        ]);

        $result = $catalog->get('items', 'uk');
        $this->assertIsArray($result);
        $this->assertSame('{{count}} елемент', $result['one']);
    }

    #[Test]
    public function has_returns_correct_boolean(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['hello' => 'Hello']);

        $this->assertTrue($catalog->has('hello', 'en'));
        $this->assertTrue($catalog->has('Demo.hello', 'en'));
        $this->assertFalse($catalog->has('missing', 'en'));
    }

    #[Test]
    public function get_locales_returns_loaded_locales(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['a' => 'b']);
        $catalog->addMessages('uk', 'Demo', ['a' => 'б']);

        $locales = $catalog->getLocales();
        $this->assertContains('en', $locales);
        $this->assertContains('uk', $locales);
    }
}
