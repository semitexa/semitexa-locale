<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Service;

use Semitexa\Locale\Configuration\LocaleConfig;

use Semitexa\Locale\Domain\Model\LocaleResolution;

use Semitexa\Core\Cookie\CookieJarInterface;
use Semitexa\Core\Locale\LocaleContextInterface;
use Semitexa\Core\Request;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Locale\Domain\Event\LocaleResolved;
use Semitexa\Locale\Application\Service\Resolver\CookieLocaleResolver;
use Semitexa\Locale\Domain\Contract\LocaleResolverInterface;
use Semitexa\Locale\Domain\Contract\LocalePackProviderInterface;
use Semitexa\Locale\Application\Service\Resolver\PathLocaleResolver;
use Semitexa\Locale\Application\Service\Resolver\HeaderLocaleResolver;

final class LocaleBootstrapper
{
    private LocaleConfig $config;

    public function __construct(
        private readonly LocaleContextInterface $localeContext,
        ?LocaleConfig $config = null,
        private readonly ?EventDispatcherInterface $events = null,
        /**
         * Per-tenant pack overlay (null = the global pack for everyone).
         * Applied per request in {@see resolve()} so each tenant's default /
         * fallback / supported-set drives its own locale resolution.
         */
        private readonly ?LocalePackProviderInterface $packProvider = null,
    ) {
        $this->config = $config ?? LocaleConfig::fromEnvironment();
    }

    /**
     * The pack in effect for the CURRENT tenant: the base config with the
     * tenant's overrides overlaid, or the base when no provider is bound.
     * Public so the lifecycle phase (redirect logic, context-store values)
     * operates on the SAME pack resolution validated against.
     */
    public function getEffectiveConfig(): LocaleConfig
    {
        return $this->packProvider?->resolvedPack($this->config) ?? $this->config;
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

    public function resolve(Request $request, ?CookieJarInterface $cookieJar = null): ?LocaleResolution
    {
        // Resolve under the CURRENT tenant's pack (base when no provider).
        $config = $this->getEffectiveConfig();

        if (!$config->enabled) {
            return null;
        }

        $this->localeContext->setLocale($config->defaultLocale);
        $this->localeContext->setFallbackLocale($config->fallbackLocale);
        \Semitexa\Locale\Context\LocaleContextStore::setSupportedLocales($config->supportedLocales);

        $resolution = null;

        foreach ($config->resolverPriority as $key) {
            $resolver = $this->createResolver($key, $config->supportedLocales, $cookieJar);

            if ($resolver === null) {
                continue;
            }

            if ($key === 'path'
                && $config->urlPrefixEnabled
                && $resolver instanceof PathLocaleResolver
            ) {
                $detection = $resolver->detect($request);
                if ($detection !== null) {
                    $resolution = $detection;
                    break;
                }
                // No prefix in URL → default locale is authoritative; skip cookie/header
                $resolution = new LocaleResolution(
                    locale: $config->defaultLocale,
                    resolvedBy: 'path',
                    hadPathPrefix: false,
                    strippedPath: null,
                );
                break;
            }

            $result = $resolver->resolve($request);

            if ($result !== null) {
                $resolution = new LocaleResolution(
                    locale: $result,
                    resolvedBy: $key,
                    hadPathPrefix: false,
                    strippedPath: null,
                );
                break;
            }
        }

        if ($resolution !== null) {
            $this->localeContext->setLocale($resolution->locale);

            if ($this->events !== null) {
                $this->events->dispatch(new LocaleResolved($resolution->locale, $resolution->resolvedBy));
            }
        }

        return $resolution;
    }

    /**
     * @param string[] $supportedLocales the EFFECTIVE (tenant) supported set
     */
    private function createResolver(string $key, array $supportedLocales, ?CookieJarInterface $cookieJar = null): ?LocaleResolverInterface
    {
        return match ($key) {
            'path' => new PathLocaleResolver($supportedLocales),
            'header' => new HeaderLocaleResolver($supportedLocales),
            'cookie' => $cookieJar !== null
                ? new CookieLocaleResolver($cookieJar, $supportedLocales)
                : null,
            default => null,
        };
    }
}
