<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Request;
use Semitexa\Locale\Application\Service\Resolver\PathLocaleResolver;

final class PathLocaleResolverTest extends TestCase
{
    #[Test]
    public function resolves_supported_locale_from_path(): void
    {
        $resolver = new PathLocaleResolver(['en', 'uk', 'de']);
        $request = $this->makeRequest('/en/dashboard');

        $this->assertSame('en', $resolver->resolve($request));
    }

    #[Test]
    public function returns_null_for_unsupported_locale(): void
    {
        $resolver = new PathLocaleResolver(['en', 'uk']);
        $request = $this->makeRequest('/fr/dashboard');

        $this->assertNull($resolver->resolve($request));
    }

    #[Test]
    public function returns_null_for_empty_path(): void
    {
        $resolver = new PathLocaleResolver(['en']);
        $request = $this->makeRequest('/');

        $this->assertNull($resolver->resolve($request));
    }

    #[Test]
    public function resolves_locale_only_path(): void
    {
        $resolver = new PathLocaleResolver(['de']);
        $request = $this->makeRequest('/de');

        $this->assertSame('de', $resolver->resolve($request));
    }

    private function makeRequest(string $uri = '/'): Request
    {
        return new Request(
            method: 'GET',
            uri: $uri,
            headers: [],
            query: [],
            post: [],
            server: [],
            cookies: [],
        );
    }
}
