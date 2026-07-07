<?php

declare(strict_types=1);

namespace Semitexa\Locale\Domain\Contract;

use Semitexa\Locale\Configuration\LocaleConfig;

/**
 * Resolves the EFFECTIVE language pack for the CURRENT tenant.
 *
 * Given the global (ENV-derived) {@see LocaleConfig} as the base, returns it
 * with the current tenant's overrides applied — its own default locale,
 * fallback, and supported-locale set — so a tenant can offer a different set
 * of languages / default than the framework-wide pack. Anything the tenant
 * does not specify falls through to the base. The core implementation (no
 * store bound) simply returns the base unchanged.
 *
 * Called once per request during locale resolution, so it may read a store;
 * the implementation resolves the current tenant itself (coroutine-local).
 */
interface LocalePackProviderInterface
{
    public function resolvedPack(LocaleConfig $base): LocaleConfig;
}
