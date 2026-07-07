<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Request;
use Semitexa\Locale\Application\Service\LocaleBootstrapper;
use Semitexa\Locale\Configuration\LocaleConfig;
use Semitexa\Locale\Context\LocaleContextStore;
use Semitexa\Locale\Context\LocaleManager;
use Semitexa\Locale\Domain\Contract\LocalePackProviderInterface;

/**
 * A per-tenant pack provider overlays the base LocaleConfig, so locale
 * resolution validates against the CURRENT tenant's supported set + default.
 * Two "tenants" (two providers) with different packs resolve differently for
 * the identical request; with no provider the base pack is used unchanged.
 */
final class LocaleBootstrapperPackTest extends TestCase
{
    protected function tearDown(): void
    {
        LocaleContextStore::clearFallback();
    }

    private function base(): LocaleConfig
    {
        // Global pack offers many locales, default en.
        return new LocaleConfig(
            defaultLocale: 'en',
            fallbackLocale: 'en',
            supportedLocales: ['en', 'uk', 'de', 'pl'],
            resolverPriority: ['header'],
        );
    }

    #[Test]
    public function a_tenant_pack_restricts_the_supported_set_and_default(): void
    {
        // Tenant A offers only fr/de, default fr — NOT in the global set.
        $manager = new LocaleManager();
        $bootstrapper = new LocaleBootstrapper(
            $manager,
            $this->base(),
            packProvider: new FixedPack(new LocaleConfig(
                defaultLocale: 'fr',
                fallbackLocale: 'fr',
                supportedLocales: ['fr', 'de'],
                resolverPriority: ['header'],
            )),
        );

        // Request asks for 'uk' (global-supported, but NOT in this tenant's set)
        // → rejected → tenant default 'fr'.
        $bootstrapper->resolve($this->makeRequest('/', ['Accept-Language' => 'uk']));
        self::assertSame('fr', $manager->getLocale(), 'A locale outside the tenant pack falls to the tenant default.');

        // Request asks for 'de' (in the tenant's set) → resolves 'de'.
        $bootstrapper->resolve($this->makeRequest('/', ['Accept-Language' => 'de']));
        self::assertSame('de', $manager->getLocale());

        // resolve() publishes the tenant's EFFECTIVE supported set into the
        // context store — the per-request source for the language-switcher UI,
        // sitemap alternates and locale_switch_url prefix stripping.
        self::assertSame(['fr', 'de'], LocaleContextStore::getSupportedLocales());
    }

    #[Test]
    public function a_second_tenant_gets_its_own_pack_for_the_same_request(): void
    {
        $manager = new LocaleManager();
        // Tenant B: only uk, default uk.
        $bootstrapper = new LocaleBootstrapper(
            $manager,
            $this->base(),
            packProvider: new FixedPack(new LocaleConfig(
                defaultLocale: 'uk',
                fallbackLocale: 'uk',
                supportedLocales: ['uk'],
                resolverPriority: ['header'],
            )),
        );

        $bootstrapper->resolve($this->makeRequest('/', ['Accept-Language' => 'de']));
        self::assertSame('uk', $manager->getLocale(), 'de is not in tenant B pack → tenant B default uk.');
    }

    #[Test]
    public function no_provider_uses_the_base_pack(): void
    {
        $manager = new LocaleManager();
        $bootstrapper = new LocaleBootstrapper($manager, $this->base()); // no provider

        $bootstrapper->resolve($this->makeRequest('/', ['Accept-Language' => 'de']));
        self::assertSame('de', $manager->getLocale(), 'Base pack allows de.');
    }

    private function makeRequest(string $uri = '/', array $headers = []): Request
    {
        return new Request(
            method: 'GET',
            uri: $uri,
            headers: $headers,
            query: [],
            post: [],
            server: [],
            cookies: [],
        );
    }
}

final class FixedPack implements LocalePackProviderInterface
{
    public function __construct(private readonly LocaleConfig $pack) {}

    public function resolvedPack(LocaleConfig $base): LocaleConfig
    {
        return $this->pack;
    }
}
