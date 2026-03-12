<?php

declare(strict_types=1);

namespace Semitexa\Locale\I18n\Loader;

use Semitexa\Locale\I18n\TranslationCatalog;

/**
 * Discovers and loads JSON locale files from module directories.
 *
 * Expected structure: {modulesRoot}/{Module}/Application/View/locales/{locale}.json
 *
 * JSON files may contain flat key-value strings or nested objects for plural forms:
 *   { "key": "value", "items": { "one": "1 item", "few": "{{count}} items", "many": "..." } }
 */
final class JsonFileLoader
{
    public function __construct(
        private readonly string $modulesRoot,
    ) {}

    public function load(TranslationCatalog $catalog): void
    {
        if (!is_dir($this->modulesRoot)) {
            return;
        }

        foreach (glob($this->modulesRoot . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
            $localesDir = $moduleDir . '/Application/View/locales';

            if (!is_dir($localesDir)) {
                continue;
            }

            $module = basename($moduleDir);

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
