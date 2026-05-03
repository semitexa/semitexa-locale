<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Service\Resolver;

use Semitexa\Locale\Domain\Contract\LocaleResolverInterface;

use Semitexa\Core\Cookie\CookieJarInterface;
use Semitexa\Core\Request;

final class CookieLocaleResolver implements LocaleResolverInterface
{
    public const DEFAULT_COOKIE_NAME = 'locale';

    /** @param string[] $supportedLocales */
    public function __construct(
        private readonly CookieJarInterface $cookieJar,
        private readonly array $supportedLocales = [],
        private readonly string $cookieName = self::DEFAULT_COOKIE_NAME,
    ) {}

    public function resolve(Request $request): ?string
    {
        $locale = $this->cookieJar->get($this->cookieName);

        if ($locale === null || $locale === '') {
            return null;
        }

        if ($this->supportedLocales !== [] && !in_array($locale, $this->supportedLocales, true)) {
            return null;
        }

        return $locale;
    }
}
