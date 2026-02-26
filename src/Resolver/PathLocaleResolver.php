<?php

declare(strict_types=1);

namespace Semitexa\Locale\Resolver;

use Semitexa\Core\Request;

final class PathLocaleResolver implements LocaleResolverInterface
{
    /** @param string[] $supportedLocales */
    public function __construct(
        private readonly array $supportedLocales = ['en', 'uk', 'de', 'pl', 'ru'],
    ) {}

    public function resolve(Request $request): ?string
    {
        $path = ltrim($request->getPath(), '/');
        
        if ($path === '') {
            return null;
        }

        $segments = explode('/', $path, 2);
        $firstSegment = $segments[0];

        if (!in_array($firstSegment, $this->supportedLocales, true)) {
            return null;
        }

        return $firstSegment;
    }
}
