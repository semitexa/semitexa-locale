<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\I18n\Loader;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Application\Service\I18n\JsonFileLoader;
use Semitexa\Locale\Application\Service\I18n\TranslationCatalog;

final class JsonFileLoaderTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = sys_get_temp_dir() . '/semitexa_locale_test_' . uniqid();
        $moduleLocales = $this->fixtureDir . '/TestMod/Application/View/locales';
        mkdir($moduleLocales, 0777, true);

        file_put_contents($moduleLocales . '/en.json', json_encode([
            'welcome' => 'Welcome',
            'items' => ['one' => '1 item', 'other' => '{{count}} items'],
        ]));

        file_put_contents($moduleLocales . '/uk.json', json_encode([
            'welcome' => 'Ласкаво просимо',
        ]));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->fixtureDir);
    }

    #[Test]
    public function loads_json_files_into_catalog(): void
    {
        $catalog = new TranslationCatalog();
        $loader = new JsonFileLoader($this->fixtureDir);
        $loader->load($catalog);

        $this->assertSame('Welcome', $catalog->get('welcome', 'en'));
        $this->assertSame('Welcome', $catalog->get('TestMod.welcome', 'en'));
        $this->assertSame('Ласкаво просимо', $catalog->get('welcome', 'uk'));
    }

    #[Test]
    public function loads_plural_arrays(): void
    {
        $catalog = new TranslationCatalog();
        $loader = new JsonFileLoader($this->fixtureDir);
        $loader->load($catalog);

        $items = $catalog->get('items', 'en');
        $this->assertIsArray($items);
        $this->assertSame('1 item', $items['one']);
        $this->assertSame('{{count}} items', $items['other']);
    }

    #[Test]
    public function handles_missing_directory(): void
    {
        $catalog = new TranslationCatalog();
        $loader = new JsonFileLoader('/nonexistent/path');
        $loader->load($catalog);

        $this->assertEmpty($catalog->getLocales());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
