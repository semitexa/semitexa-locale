<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Service\I18n;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\SatisfiesServiceContract;
use Semitexa\Core\Log\FallbackErrorLogger;
use Semitexa\Core\Support\CoroutineLocal;
use Semitexa\Core\Tenant\TenantContextAccess;
use Semitexa\Core\Tenant\TenantContextStoreInterface;
use Semitexa\Locale\Application\Db\MySQL\Model\TranslationOverrideResource;
use Semitexa\Locale\Domain\Contract\TranslationOverrideProviderInterface;
use Semitexa\Orm\Application\Service\Uuid7;
use Semitexa\Orm\OrmManager;
use Semitexa\Orm\Query\Operator;
use Semitexa\Orm\Repository\DomainRepository;

/**
 * DB-backed per-tenant translation overrides (white-label terminology).
 *
 * The lookup sits on the render hot path (trans() is called per string), so a
 * per-call query is unacceptable. Instead the CURRENT tenant's overrides for a
 * locale are loaded ONCE and memoized coroutine-locally (per request), keyed by
 * (tenant, locale): one indexed query per request that actually translates in a
 * locale, fresh each request (no cross-request staleness), tenant-isolated.
 * A tenant with no overrides memoizes an empty map — still one cheap query.
 */
#[AsService]
#[SatisfiesServiceContract(of: TranslationOverrideProviderInterface::class)]
final class TranslationOverrideStore implements TranslationOverrideProviderInterface
{
    private const MEMO_KEY = 'locale.override.memo';

    #[InjectAsReadonly]
    protected OrmManager $orm;

    #[InjectAsReadonly]
    protected TenantContextStoreInterface $tenantContextStore;

    private ?DomainRepository $repository = null;

    /** @var array<string, true> memoKeys already logged as failed this worker (avoid per-request log spam). */
    private static array $loggedFailures = [];

    /** Test seam — production path uses property injection. */
    public function withOrmManager(OrmManager $orm): self
    {
        $this->orm = $orm;
        $this->repository = null;

        return $this;
    }

    /** Test seam — production path uses property injection. */
    public function withTenantContextStore(TenantContextStoreInterface $store): self
    {
        $this->tenantContextStore = $store;

        return $this;
    }

    public function override(string $key, string $locale): ?string
    {
        return $this->overridesFor($locale)[$key] ?? null;
    }

    /**
     * Set (or replace) one tenant override — the admin/white-label write path.
     * Tenant-stamped; invalidates the per-request memo for that locale.
     */
    public function set(string $locale, string $key, string $value): void
    {
        $tenant = $this->currentTenantId();
        $existing = $this->scoped()->query()
            ->where(TranslationOverrideResource::column('locale'), Operator::Equals, $locale)
            ->where(TranslationOverrideResource::column('message_key'), Operator::Equals, $key)
            ->fetchOneAs(TranslationOverrideResource::class, $this->orm()->getMapperRegistry());

        $row = new TranslationOverrideResource(
            id: $existing?->id ?? Uuid7::generate(),
            tenant_id: $tenant,
            locale: $locale,
            message_key: $key,
            value: $value,
            updated_at: new \DateTimeImmutable(),
        );

        if ($existing === null) {
            try {
                $this->scoped()->insert($row);
            } catch (\Throwable) {
                // Lost a concurrent first-write race on the unique
                // (tenant, locale, message_key) index — the row exists now;
                // re-fetch its id and update instead of failing.
                $winner = $this->scoped()->query()
                    ->where(TranslationOverrideResource::column('locale'), Operator::Equals, $locale)
                    ->where(TranslationOverrideResource::column('message_key'), Operator::Equals, $key)
                    ->fetchOneAs(TranslationOverrideResource::class, $this->orm()->getMapperRegistry());
                if ($winner !== null) {
                    $this->scoped()->update(new TranslationOverrideResource(
                        id: $winner->id,
                        tenant_id: $tenant,
                        locale: $locale,
                        message_key: $key,
                        value: $value,
                        updated_at: new \DateTimeImmutable(),
                    ));
                }
            }
        } else {
            $this->scoped()->update($row);
        }

        $this->forgetMemo($tenant, $locale);
    }

    /** Remove one tenant override (falls back to the global catalog again). */
    public function remove(string $locale, string $key): void
    {
        $tenant = $this->currentTenantId();
        $existing = $this->scoped()->query()
            ->where(TranslationOverrideResource::column('locale'), Operator::Equals, $locale)
            ->where(TranslationOverrideResource::column('message_key'), Operator::Equals, $key)
            ->fetchOneAs(TranslationOverrideResource::class, $this->orm()->getMapperRegistry());

        if ($existing !== null) {
            $this->scoped()->delete($existing);
            $this->forgetMemo($tenant, $locale);
        }
    }

    /**
     * The current tenant's override map for a locale (key => value), memoized
     * per request.
     *
     * @return array<string, string>
     */
    private function overridesFor(string $locale): array
    {
        $tenant = $this->currentTenantId();
        $memoKey = $tenant . "\0" . $locale;

        /** @var array<string, array<string, string>> $memo */
        $memo = CoroutineLocal::get(self::MEMO_KEY, []);
        if (isset($memo[$memoKey])) {
            return $memo[$memoKey];
        }

        $map = [];
        try {
            /** @var list<TranslationOverrideResource> $rows */
            $rows = $this->scoped()->query()
                ->where(TranslationOverrideResource::column('locale'), Operator::Equals, $locale)
                ->fetchAllAs(TranslationOverrideResource::class, $this->orm()->getMapperRegistry());
            foreach ($rows as $row) {
                $map[$row->message_key] = $row->value;
            }
        } catch (\Throwable $e) {
            // No table yet / DB hiccup: overrides are a best-effort enhancement
            // over the always-present global catalog — never break translation.
            // A genuine misconfiguration (missing column, permissions) would
            // otherwise silently disable white-labelling with no trace, so log
            // ONCE per (tenant, locale) per worker — not per request.
            if (!isset(self::$loggedFailures[$memoKey])) {
                self::$loggedFailures[$memoKey] = true;
                FallbackErrorLogger::log('Tenant translation overrides unavailable; falling back to the global catalog', [
                    'tenant' => $tenant,
                    'locale' => $locale,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
            $map = [];
        }

        $memo[$memoKey] = $map;
        CoroutineLocal::set(self::MEMO_KEY, $memo);

        return $map;
    }

    private function forgetMemo(string $tenant, string $locale): void
    {
        /** @var array<string, array<string, string>> $memo */
        $memo = CoroutineLocal::get(self::MEMO_KEY, []);
        unset($memo[$tenant . "\0" . $locale]);
        CoroutineLocal::set(self::MEMO_KEY, $memo);
    }

    private function scoped(): DomainRepository
    {
        return $this->repository()->forTenant($this->currentTenantId());
    }

    private function currentTenantId(): string
    {
        $context = isset($this->tenantContextStore) ? $this->tenantContextStore->tryGet() : null;

        return TenantContextAccess::tenantIdOrDefault($context);
    }

    private function repository(): DomainRepository
    {
        return $this->repository ??= $this->orm()->repository(
            TranslationOverrideResource::class,
            TranslationOverrideResource::class,
        );
    }

    private function orm(): OrmManager
    {
        if (!isset($this->orm)) {
            $this->orm = new OrmManager();
        }

        return $this->orm;
    }
}
