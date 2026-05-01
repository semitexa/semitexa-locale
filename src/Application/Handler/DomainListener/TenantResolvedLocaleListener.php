<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Handler\DomainListener;

use Semitexa\Core\Attribute\AsEventListener;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Event\EventExecution;
use Semitexa\Core\Locale\LocaleContextInterface;
use Semitexa\Core\Tenant\Layer\LocaleLayer;
use Semitexa\Tenancy\Domain\Event\TenantResolved;

/**
 * Listens to TenantResolved and sets the request locale from the tenant context
 * when the Locale layer is present. Allows locale to be driven by tenancy (e.g. path/domain)
 * without semitexa-locale depending on resolution logic.
 */
#[AsEventListener(event: TenantResolved::class, execution: EventExecution::Sync)]
final class TenantResolvedLocaleListener
{
    #[InjectAsReadonly]
    protected LocaleContextInterface $localeContext;

    public function handle(TenantResolved $event): void
    {
        $locale = $event->context->getLayer(new LocaleLayer());
        if ($locale === null) {
            return;
        }

        $this->localeContext->setLocale($locale->rawValue());
    }
}
