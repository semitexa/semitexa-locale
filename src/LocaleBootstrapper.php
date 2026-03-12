<?php

declare(strict_types=1);

namespace Semitexa\Locale;

use Semitexa\Core\Cookie\CookieJarInterface;
use Semitexa\Core\Locale\LocaleContextInterface;
use Semitexa\Core\Request;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Locale\Event\LocaleResolved;
use Semitexa\Locale\Resolver\CookieLocaleResolver;
use Semitexa\Locale\Resolver\LocaleResolverInterface;
use Semitexa\Locale\Resolver\LocaleResolverChain;
use Semitexa\Locale\Resolver\PathLocaleResolver;
use Semitexa\Locale\Resolver\HeaderLocaleResolver;

final class LocaleBootstrapper
{
    private LocaleConfig $config;

    public function __construct(
        private readonly LocaleContextInterface $localeContext,
        ?LocaleConfig $config = null,
        private readonly ?EventDispatcherInterface $events = null,
    ) {
        $this->config = $config ?? LocaleConfig::fromEnvironment();
    }

    public function isEnabled(): bool
    {
        return $this->config->enabled;
    }

    public function getConfig(): LocaleConfig
    {
        return $this->config;
    }

    public function getLocaleContext(): LocaleContextInterface
    {
        return $this->localeContext;
    }

    public function resolve(Request $request, ?CookieJarInterface $cookieJar = null): void
    {
        $resolvedBy = null;
        $locale = null;

        foreach ($this->config->resolverPriority as $key) {
            $resolver = $this->createResolver($key, $cookieJar);

            if ($resolver === null) {
                continue;
            }

            $result = $resolver->resolve($request);

            if ($result !== null) {
                $locale = $result;
                $resolvedBy = $key;
                break;
            }
        }

        if ($locale !== null) {
            $this->localeContext->setLocale($locale);

            if ($this->events !== null) {
                $this->events->dispatch(new LocaleResolved($locale, $resolvedBy));
            }
        }
    }

    private function createResolver(string $key, ?CookieJarInterface $cookieJar = null): ?LocaleResolverInterface
    {
        return match ($key) {
            'path' => new PathLocaleResolver($this->config->supportedLocales),
            'header' => new HeaderLocaleResolver($this->config->supportedLocales),
            'cookie' => $cookieJar !== null
                ? new CookieLocaleResolver($cookieJar, $this->config->supportedLocales)
                : null,
            default => null,
        };
    }
}
