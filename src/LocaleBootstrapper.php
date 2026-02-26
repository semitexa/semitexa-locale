<?php

declare(strict_types=1);

namespace Semitexa\Locale;

use Semitexa\Core\Request;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Locale\Context\LocaleManager;
use Semitexa\Locale\Resolver\LocaleResolverInterface;
use Semitexa\Locale\Resolver\PathLocaleResolver;
use Semitexa\Locale\Resolver\HeaderLocaleResolver;

final class LocaleBootstrapper
{
    private LocaleResolverInterface $resolver;
    private ?EventDispatcherInterface $events = null;
    private bool $enabled;

    public function __construct(?EventDispatcherInterface $events = null)
    {
        $this->events = $events;
        $this->enabled = getenv('LOCALE_ENABLED') !== 'false';
        $this->resolver = $this->buildResolver();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function resolve(Request $request): void
    {
        $locale = $this->resolver->resolve($request);

        if ($locale !== null) {
            LocaleManager::getInstance()->setLocale($locale);
        }
    }

    private function buildResolver(): LocaleResolverInterface
    {
        $strategy = getenv('LOCALE_STRATEGY') ?? 'path';

        return match ($strategy) {
            'path' => new PathLocaleResolver($this->getSupportedLocales()),
            'header' => new HeaderLocaleResolver(),
            default => new PathLocaleResolver($this->getSupportedLocales()),
        };
    }

    private function getSupportedLocales(): array
    {
        $locales = getenv('LOCALE_SUPPORTED') ?? 'en,uk,de,pl,ru';
        
        return array_filter(array_map('trim', explode(',', $locales)));
    }
}
