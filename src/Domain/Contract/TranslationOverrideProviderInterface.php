<?php

declare(strict_types=1);

namespace Semitexa\Locale\Domain\Contract;

/**
 * Per-tenant translation override lookup.
 *
 * Consulted by {@see \Semitexa\Locale\Application\Service\I18n\TranslationService}
 * BEFORE the global code-shipped catalog: a tenant may override any message for
 * a locale (white-label terminology / branding) and everything it does not
 * override falls through to the shared catalog. Returns null when the current
 * tenant has no override for (key, locale) — including the single-tenant
 * 'default' context, which simply has no overrides.
 *
 * Implementations resolve the CURRENT tenant themselves (coroutine-local), so
 * the caller passes only key + locale.
 */
interface TranslationOverrideProviderInterface
{
    public function override(string $key, string $locale): ?string;
}
