<?php

declare(strict_types=1);

namespace Semitexa\Locale\Event;

use Semitexa\Core\Attributes\AsEventListener;
use Semitexa\Core\Event\EventExecution;
use Semitexa\Core\Locale\LocaleContextInterface;
use Semitexa\Core\Tenant\Layer\LocaleLayer;
use Semitexa\Tenancy\Event\TenantResolved;

/**
 * Listens to TenantResolved and sets the request locale from the tenant context
 * when the Locale layer is present. Allows locale to be driven by tenancy (e.g. path/domain)
 * without semitexa-locale depending on resolution logic.
 */
#[AsEventListener(event: TenantResolved::class, execution: EventExecution::Sync)]
final class TenantResolvedLocaleListener
{
    public function __construct(
        private readonly LocaleContextInterface $localeContext,
    ) {}

    public function handle(TenantResolved $event): void
    {
        $locale = $event->context->getLayer(new LocaleLayer());
        if ($locale === null) {
            return;
        }

        $this->localeContext->setLocale($locale->rawValue());
    }
}
