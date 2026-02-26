<?php

declare(strict_types=1);

namespace Semitexa\Locale\Resolver;

use Semitexa\Core\Request;

final class HeaderLocaleResolver implements LocaleResolverInterface
{
    public function __construct(
        private readonly string $headerName = 'Accept-Language',
    ) {}

    public function resolve(Request $request): ?string
    {
        $header = $request->getHeader($this->headerName);
        
        if ($header === null || $header === '') {
            return null;
        }

        $locales = array_map('trim', explode(',', $header));
        
        if (empty($locales)) {
            return null;
        }

        $first = $locales[0];
        $locale = strtolower(explode(';', $first)[0]);
        
        $parts = explode('-', $locale);
        if (count($parts) === 2) {
            $locale = $parts[0] . '-' . strtoupper($parts[1]);
        }

        return $locale;
    }
}
