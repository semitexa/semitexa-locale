<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Service\I18n;

use Semitexa\Locale\Application\Service\I18n\TranslationCatalog;

/**
 * Discovers and loads JSON locale files from module directories.
 *
 * Two module layouts are supported, mirroring \Semitexa\Core\ModuleRegistry's
 * template-path resolution (canonical first, legacy fallback):
 *   {modulesRoot}/{Module}/src/Application/View/locales/{locale}.json  (canonical)
 *   {modulesRoot}/{Module}/Application/View/locales/{locale}.json      (legacy)
 *
 * JSON files may contain flat key-value strings or nested objects for plural forms:
 *   { "key": "value", "items": { "one": "1 item", "few": "{{count}} items", "many": "..." } }
 */
final class JsonFileLoader
{
    /**
     * @param array<string, string> $extraLocaleDirs moduleName => absolute locales dir
     *        (installed PACKAGES — the modulesRoot scan only covers src/modules/*)
     */
    public function __construct(
        private readonly string $modulesRoot,
        private readonly array $extraLocaleDirs = [],
    ) {}

    public function load(TranslationCatalog $catalog): void
    {
        // App modules first: addMessages keeps the first value for the
        // unprefixed alias (prefixed keys are per module), so whoever loads
        // first wins `key` alone. Packages went first, and a package that
        // started shipping translations (cms: 85 keys) took over an app's
        // unprefixed `blocks.align`, though the app is meant to override.
        if (is_dir($this->modulesRoot)) {
            foreach (glob($this->modulesRoot . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
                $localesDir = $this->resolveLocalesDir($moduleDir);

                if ($localesDir === null) {
                    continue;
                }

                $this->loadDir($catalog, basename($moduleDir), $localesDir);
            }
        }

        foreach ($this->extraLocaleDirs as $module => $localesDir) {
            // A numeric name comes back from the array as an int key.
            $module = (string) $module;
            if ($module !== '' && is_dir($localesDir)) {
                $this->loadDir($catalog, $module, $localesDir);
            }
        }
    }

    /** Load every {locale}.json in one module's locales dir into the catalog. */
    private function loadDir(TranslationCatalog $catalog, string $module, string $localesDir): void
    {
        foreach (glob($localesDir . '/*.json') ?: [] as $file) {
            $locale = basename($file, '.json');
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            $messages = json_decode($content, true);

            if (!is_array($messages)) {
                continue;
            }

            $validated = [];
            foreach ($messages as $key => $value) {
                if (is_string($value)) {
                    $validated[$key] = $value;
                } elseif (is_array($value) && $this->isValidPluralMap($value)) {
                    $validated[$key] = $value;
                }
            }

            if ($validated !== []) {
                $catalog->addMessages($locale, $module, $validated);
            }
        }
    }

    private function resolveLocalesDir(string $moduleDir): ?string
    {
        foreach (['/src/Application/View/locales', '/Application/View/locales'] as $candidate) {
            $path = $moduleDir . $candidate;
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    private function isValidPluralMap(array $value): bool
    {
        $allowedKeys = ['zero', 'one', 'two', 'few', 'many', 'other'];

        foreach ($value as $k => $v) {
            if (!is_string($v) || !in_array($k, $allowedKeys, true)) {
                return false;
            }
        }

        return $value !== [];
    }
}
