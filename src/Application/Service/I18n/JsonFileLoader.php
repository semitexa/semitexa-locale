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
        // Installed packages first — an app module (scanned below) may then
        // override a package's key for the same locale (addMessages keeps the
        // first value only for the unprefixed alias; prefixed keys are per-module).
        foreach ($this->extraLocaleDirs as $module => $localesDir) {
            if (is_string($module) && $module !== '' && is_dir($localesDir)) {
                $this->loadDir($catalog, $module, $localesDir);
            }
        }

        if (!is_dir($this->modulesRoot)) {
            return;
        }

        foreach (glob($this->modulesRoot . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
            $localesDir = $this->resolveLocalesDir($moduleDir);

            if ($localesDir === null) {
                continue;
            }

            $module = basename($moduleDir);

            $this->loadDir($catalog, $module, $localesDir);
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
