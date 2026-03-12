<?php

declare(strict_types=1);

namespace Semitexa\Locale\Event;

/**
 * Dispatched after the locale has been resolved for the current request.
 *
 * Listeners can use this to persist the locale (e.g. write a cookie),
 * adjust response headers, or trigger locale-dependent side effects.
 */
final readonly class LocaleResolved
{
    public function __construct(
        public string $locale,
        public string $resolvedBy,
    ) {}
}
