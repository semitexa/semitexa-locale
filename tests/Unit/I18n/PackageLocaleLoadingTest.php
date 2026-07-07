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
}
