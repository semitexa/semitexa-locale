<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Request;
use Semitexa\Locale\Application\Service\Resolver\HeaderLocaleResolver;

final class HeaderLocaleResolverTest extends TestCase
{
    #[Test]
    public function resolves_single_locale(): void
    {
        $resolver = new HeaderLocaleResolver(['en', 'de']);
        $request = $this->makeRequest(['Accept-Language' => 'de']);

        $this->assertSame('de', $resolver->resolve($request));
    }

    #[Test]
    public function sorts_by_quality_factor_and_returns_best_supported(): void
    {
        $resolver = new HeaderLocaleResolver(['en', 'de']);
        $request = $this->makeRequest(['Accept-Language' => 'fr;q=0.9, de;q=0.8, en;q=0.7']);

        $this->assertSame('de', $resolver->resolve($request));
    }

    #[Test]
    public function returns_null_when_no_supported_match(): void
    {
        $resolver = new HeaderLocaleResolver(['en']);
        $request = $this->makeRequest(['Accept-Language' => 'fr, de']);

        $this->assertNull($resolver->resolve($request));
    }

    #[Test]
    public function returns_null_for_empty_header(): void
    {
        $resolver = new HeaderLocaleResolver(['en']);
        $request = $this->makeRequest(['Accept-Language' => '']);

        $this->assertNull($resolver->resolve($request));
    }

    #[Test]
    public function returns_null_for_missing_header(): void
    {
        $resolver = new HeaderLocaleResolver(['en']);
        $request = $this->makeRequest();

        $this->assertNull($resolver->resolve($request));
    }

    #[Test]
    public function handles_regional_codes(): void
    {
        $resolver = new HeaderLocaleResolver(['en-US', 'en-GB']);
        $request = $this->makeRequest(['Accept-Language' => 'en-gb;q=0.9, en-us;q=1.0']);

        $this->assertSame('en-US', $resolver->resolve($request));
    }

    #[Test]
    public function ignores_wildcard(): void
    {
        $resolver = new HeaderLocaleResolver(['en']);
        $request = $this->makeRequest(['Accept-Language' => '*, en;q=0.5']);

        $this->assertSame('en', $resolver->resolve($request));
    }

    #[Test]
    public function handles_malformed_quality_gracefully(): void
    {
        $resolver = new HeaderLocaleResolver(['en']);
        $request = $this->makeRequest(['Accept-Language' => 'en;q=notanumber']);

        $this->assertSame('en', $resolver->resolve($request));
    }

    #[Test]
    public function accepts_any_locale_when_supported_list_empty(): void
    {
        $resolver = new HeaderLocaleResolver();
        $request = $this->makeRequest(['Accept-Language' => 'fr;q=0.5, de;q=0.9']);

        $this->assertSame('de', $resolver->resolve($request));
    }

    private function makeRequest(array $headers = []): Request
    {
        return new Request(
            method: 'GET',
            uri: '/',
            headers: $headers,
            query: [],
            post: [],
            server: [],
            cookies: [],
        );
    }
}
