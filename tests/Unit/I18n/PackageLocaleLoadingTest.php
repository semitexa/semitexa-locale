<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\I18n;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Application\Service\I18n\JsonFileLoader;
use Semitexa\Locale\Application\Service\I18n\TranslationCatalog;

/**
 * Installed PACKAGES ship locale packs too (e.g. the OS shell strings), but
 * JsonFileLoader's modulesRoot scan only covers src/modules/*. The extra-dirs
 * channel (moduleName => locales dir, ModuleRegistry-derived at boot) loads
 * them into the same catalog; keys() is the enumeration seam client bundles
 * (the OS boot payload) are built from.
 */
final class PackageLocaleLoadingTest extends TestCase
{
    #[Test]
    public function extra_package_dirs_load_into_the_catalog_and_enumerate(): void
    {
        $dir = sys_get_temp_dir() . '/sx-locale-test-' . bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/en.json', json_encode(['shell.enter' => 'Enter', 'shell.bye' => 'Goodbye']));
        file_put_contents($dir . '/uk.json', json_encode(['shell.enter' => 'Увійти']));

        $catalog = new TranslationCatalog();
        (new JsonFileLoader('/nonexistent-modules-root', ['os' => $dir]))->load($catalog);

        self::assertSame('Enter', $catalog->get('os.shell.enter', 'en'));
        self::assertSame('Увійти', $catalog->get('os.shell.enter', 'uk'));
        self::assertSame(['os.shell.enter', 'os.shell.bye'], $catalog->keys('en', 'os.shell.'));
        self::assertSame([], $catalog->keys('en', 'nope.'), 'Prefix filter yields nothing for foreign prefixes.');

        unlink($dir . '/en.json');
        unlink($dir . '/uk.json');
        rmdir($dir);
    }

    /**
     * The unprefixed alias keeps the first value loaded, and packages loaded
     * first: a package that began shipping a key took it over from the app.
     */
    #[Test]
    public function an_app_module_wins_an_unprefixed_key_a_package_also_ships(): void
    {
        $root = sys_get_temp_dir() . '/sx-locale-test-' . bin2hex(random_bytes(4));
        mkdir($root . '/modules/Shop/Application/View/locales', 0777, true);
        mkdir($root . '/cms', 0777, true);
        file_put_contents($root . '/modules/Shop/Application/View/locales/en.json', json_encode(['blocks.align' => 'Shop alignment']));
        file_put_contents($root . '/cms/en.json', json_encode(['blocks.align' => 'Align']));

        $catalog = new TranslationCatalog();
        (new JsonFileLoader($root . '/modules', ['cms' => $root . '/cms']))->load($catalog);

        self::assertSame('Shop alignment', $catalog->get('blocks.align', 'en'));
        self::assertSame('Align', $catalog->get('cms.blocks.align', 'en'), 'the package keeps its prefixed key');

        exec('rm -rf ' . escapeshellarg($root));
    }
}
