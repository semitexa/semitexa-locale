<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\I18n;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Context\LocaleContextStore;
use Semitexa\Locale\Context\LocaleManager;
use Semitexa\Core\Tenant\Layer\TenantLayerInterface;
use Semitexa\Core\Tenant\Layer\TenantLayerValueInterface;
use Semitexa\Core\Tenant\TenantContextInterface;
use Semitexa\Core\Tenant\TenantContextStoreInterface;
use Semitexa\Locale\Application\Service\I18n\TranslationCatalog;
use Semitexa\Locale\Application\Service\I18n\TranslationOverrideStore;
use Semitexa\Locale\Application\Service\I18n\TranslationService;
use Semitexa\Locale\Domain\Contract\TranslationOverrideProviderInterface;
use Semitexa\Orm\Domain\Model\ConnectionConfig;
use Semitexa\Orm\OrmManager;

/**
 * Per-tenant translation overrides: a tenant's override wins over the global
 * code-shipped catalog for its own strings; everything it does not override
 * falls through to the catalog; and one tenant can neither read nor overwrite
 * another's overrides (the DB store is #[TenantScoped]).
 */
final class TranslationOverrideTest extends TestCase
{
    private function catalog(): TranslationCatalog
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['welcome' => 'Welcome', 'bye' => 'Goodbye']);

        return $catalog;
    }

    #[Test]
    public function an_override_wins_over_the_catalog_and_missing_keys_fall_back(): void
    {
        $provider = new StubOverrideProvider(['welcome' => 'Howdy']); // no 'bye' override
        LocaleContextStore::setLocale('en'); LocaleContextStore::setFallbackLocale('en');
        $service = new TranslationService($this->catalog(), new LocaleManager(), $provider);

        self::assertSame('Howdy', $service->trans('welcome'), 'Override wins over the catalog.');
        self::assertSame('Goodbye', $service->trans('bye'), 'Un-overridden key falls back to the catalog.');
        self::assertSame('missing', $service->trans('missing'), 'Unknown key returns the key itself.');
        self::assertTrue($service->hasTranslation('welcome'));
    }

    #[Test]
    public function precedence_is_override_locale_then_catalog_locale_then_fallback(): void
    {
        $catalog = new TranslationCatalog();
        $catalog->addMessages('en', 'Demo', ['welcome' => 'Welcome']);
        $catalog->addMessages('fr', 'Demo', ['welcome' => 'Bienvenue']);

        LocaleContextStore::setLocale('fr');
        LocaleContextStore::setFallbackLocale('en');

        // Tenant overrides 'welcome' ONLY in en (not fr).
        $provider = new LocaleAwareOverrideProvider(['en' => ['welcome' => 'Howdy']]);
        $service = new TranslationService($catalog, new LocaleManager(), $provider);

        self::assertSame(
            'Bienvenue',
            $service->trans('welcome'),
            'catalog(fr) must win over override(en) — the en override must not shadow the French translation.',
        );

        // But if the tenant overrides in fr, that wins.
        $providerFr = new LocaleAwareOverrideProvider(['fr' => ['welcome' => 'Salut']]);
        $serviceFr = new TranslationService($catalog, new LocaleManager(), $providerFr);
        self::assertSame('Salut', $serviceFr->trans('welcome'), 'override(fr) wins over catalog(fr).');
    }

    #[Test]
    public function no_provider_is_pure_catalog_behaviour(): void
    {
        LocaleContextStore::setLocale('en'); LocaleContextStore::setFallbackLocale('en');
        $service = new TranslationService($this->catalog(), new LocaleManager()); // overrides = null

        self::assertSame('Welcome', $service->trans('welcome'));
    }

    #[Test]
    public function the_db_store_isolates_overrides_per_tenant(): void
    {
        $orm = new OrmManager(config: new ConnectionConfig(driver: 'sqlite', sqliteMemory: true));
        $orm->getAdapter()->execute(
            'CREATE TABLE locale_translation_override (
                id TEXT PRIMARY KEY, tenant_id TEXT, locale TEXT NOT NULL,
                message_key TEXT NOT NULL, value TEXT NOT NULL, updated_at TEXT NOT NULL
            )',
        );

        $ctx = new OverrideTenantContextStore();
        $store = (new TranslationOverrideStore())->withOrmManager($orm)->withTenantContextStore($ctx);

        $ctx->switchTo('acme');
        $store->set('en', 'welcome', 'Acme Welcome');

        // Globex sees no override on the same (locale, key), sets its own.
        $ctx->switchTo('globex');
        self::assertNull($store->override('welcome', 'en'), 'Globex must not read Acme\'s override.');
        $store->set('en', 'welcome', 'Globex Welcome');
        self::assertSame('Globex Welcome', $store->override('welcome', 'en'));

        // Acme is untouched.
        $ctx->switchTo('acme');
        self::assertSame('Acme Welcome', $store->override('welcome', 'en'));

        // remove() falls back to the global catalog again (null override).
        $store->remove('en', 'welcome');
        self::assertNull($store->override('welcome', 'en'));
    }
}

final class StubOverrideProvider implements TranslationOverrideProviderInterface
{
    /** @param array<string, string> $map */
    public function __construct(private readonly array $map) {}

    public function override(string $key, string $locale): ?string
    {
        return $this->map[$key] ?? null;
    }
}

final class LocaleAwareOverrideProvider implements TranslationOverrideProviderInterface
{
    /** @param array<string, array<string, string>> $byLocale */
    public function __construct(private readonly array $byLocale) {}

    public function override(string $key, string $locale): ?string
    {
        return $this->byLocale[$locale][$key] ?? null;
    }
}


final class OverrideTenantContextStore implements TenantContextStoreInterface
{
    private ?TenantContextInterface $context = null;

    public function switchTo(string $tenantId): void
    {
        $this->context = new class ($tenantId) implements TenantContextInterface {
            public function __construct(private readonly string $id) {}

            public function getTenantId(): string
            {
                return $this->id;
            }

            public function getLayer(TenantLayerInterface $layer): ?TenantLayerValueInterface
            {
                return null;
            }

            public function hasLayer(TenantLayerInterface $layer): bool
            {
                return false;
            }
        };
    }

    public function get(): TenantContextInterface
    {
        return $this->context ?? throw new \LogicException('no context');
    }

    public function tryGet(): ?TenantContextInterface
    {
        return $this->context;
    }

    public function set(TenantContextInterface $context): void
    {
        $this->context = $context;
    }

    public function clear(): void
    {
        $this->context = null;
    }
}
