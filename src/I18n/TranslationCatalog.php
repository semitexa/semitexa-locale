<?php

declare(strict_types=1);

namespace Semitexa\Locale\I18n;

/**
 * Readonly message store loaded once per worker.
 *
 * Messages are stored in a flat map keyed by `[locale][Module.key]`.
 * Unnamespaced keys are also stored for backward compatibility.
 */
final class TranslationCatalog
{
    /**
     * @var array<string, array<string, string|array<string, string>>>
     *      [locale => [key => message | [pluralCategory => message]]]
     */
    private array $messages = [];

    /**
     * Add messages for a module and locale.
     *
     * @param array<string, string|array<string, string>> $messages
     */
    public function addMessages(string $locale, string $module, array $messages): void
    {
        foreach ($messages as $key => $value) {
            // Module-namespaced key: "TenantDemo.welcome"
            $this->messages[$locale][$module . '.' . $key] = $value;

            // Also store without namespace for backward compatibility
            // (first module to register a key wins for unnamespaced lookup)
            if (!isset($this->messages[$locale][$key])) {
                $this->messages[$locale][$key] = $value;
            }
        }
    }

    /**
     * Get a message by key and locale.
     *
     * @return string|array<string, string>|null String for simple messages,
     *         array for plural forms (keyed by CLDR category), null if not found.
     */
    public function get(string $key, string $locale): string|array|null
    {
        return $this->messages[$locale][$key] ?? null;
    }

    public function has(string $key, string $locale): bool
    {
        return isset($this->messages[$locale][$key]);
    }

    /**
     * @return string[] All loaded locale codes.
     */
    public function getLocales(): array
    {
        return array_keys($this->messages);
    }
}
