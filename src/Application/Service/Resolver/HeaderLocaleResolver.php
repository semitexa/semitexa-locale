<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Service\Resolver;

use Semitexa\Locale\Domain\Contract\LocaleResolverInterface;

use Semitexa\Core\Request;

final class HeaderLocaleResolver implements LocaleResolverInterface
{
    /** @param string[] $supportedLocales */
    public function __construct(
        private readonly array $supportedLocales = [],
        private readonly string $headerName = 'Accept-Language',
    ) {}

    public function resolve(Request $request): ?string
    {
        $header = $request->getHeader($this->headerName);

        if ($header === null || $header === '') {
            return null;
        }

        $entries = $this->parseAcceptLanguage($header);

        foreach ($entries as $locale) {
            if ($this->supportedLocales === []) {
                return $locale;
            }

            if (in_array($locale, $this->supportedLocales, true)) {
                return $locale;
            }

            // Fallback: try matching the primary language subtag (e.g. "en" from "en-US")
            $separatorPosition = strpos($locale, '-');
            if ($separatorPosition !== false) {
                $primarySubtag = substr($locale, 0, $separatorPosition);

                if (in_array($primarySubtag, $this->supportedLocales, true)) {
                    return $primarySubtag;
                }
            }
        }

        return null;
    }

    /**
     * Parse Accept-Language header into locale codes sorted by quality factor descending.
     *
     * @return string[]
     */
    private function parseAcceptLanguage(string $header): array
    {
        $entries = [];

        foreach (array_map('trim', explode(',', $header)) as $part) {
            $segments = array_map('trim', explode(';', $part));
            $locale = $this->normalizeLocale($segments[0]);

            if ($locale === '' || $locale === '*') {
                continue;
            }

            $quality = 1.0;
            foreach (array_slice($segments, 1) as $param) {
                if (str_starts_with($param, 'q=')) {
                    $quality = (float) substr($param, 2);
                    break;
                }
            }

            $entries[] = ['locale' => $locale, 'quality' => $quality];
        }

        usort($entries, static fn(array $a, array $b) => $b['quality'] <=> $a['quality']);

        return array_column($entries, 'locale');
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        $parts = explode('-', $locale, 2);

        if (isset($parts[1])) {
            return $parts[0] . '-' . strtoupper($parts[1]);
        }

        return $locale;
    }
}
