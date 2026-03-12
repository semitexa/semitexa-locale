<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Cookie\CookieJarInterface;
use Semitexa\Core\Request;
use Semitexa\Locale\Resolver\CookieLocaleResolver;

final class CookieLocaleResolverTest extends TestCase
{
    #[Test]
    public function resolves_locale_from_cookie(): void
    {
        $cookieJar = $this->createCookieJar(['locale' => 'de']);
        $resolver = new CookieLocaleResolver($cookieJar, ['en', 'de']);

        $this->assertSame('de', $resolver->resolve($this->makeRequest()));
    }

    #[Test]
    public function returns_null_when_cookie_missing(): void
    {
        $cookieJar = $this->createCookieJar([]);
        $resolver = new CookieLocaleResolver($cookieJar, ['en']);

        $this->assertNull($resolver->resolve($this->makeRequest()));
    }

    #[Test]
    public function returns_null_when_cookie_empty(): void
    {
        $cookieJar = $this->createCookieJar(['locale' => '']);
        $resolver = new CookieLocaleResolver($cookieJar, ['en']);

        $this->assertNull($resolver->resolve($this->makeRequest()));
    }

    #[Test]
    public function returns_null_for_unsupported_locale(): void
    {
        $cookieJar = $this->createCookieJar(['locale' => 'fr']);
        $resolver = new CookieLocaleResolver($cookieJar, ['en', 'de']);

        $this->assertNull($resolver->resolve($this->makeRequest()));
    }

    #[Test]
    public function accepts_any_locale_when_supported_list_empty(): void
    {
        $cookieJar = $this->createCookieJar(['locale' => 'fr']);
        $resolver = new CookieLocaleResolver($cookieJar);

        $this->assertSame('fr', $resolver->resolve($this->makeRequest()));
    }

    #[Test]
    public function uses_custom_cookie_name(): void
    {
        $cookieJar = $this->createCookieJar(['lang' => 'uk']);
        $resolver = new CookieLocaleResolver($cookieJar, ['uk'], 'lang');

        $this->assertSame('uk', $resolver->resolve($this->makeRequest()));
    }

    private function createCookieJar(array $cookies): CookieJarInterface
    {
        return new class($cookies) implements CookieJarInterface {
            public function __construct(private readonly array $cookies) {}

            public function get(string $name, ?string $default = null): ?string
            {
                return $this->cookies[$name] ?? $default;
            }

            public function has(string $name): bool
            {
                return isset($this->cookies[$name]);
            }

            public function set(string $name, string $value, array $options = []): void {}
            public function remove(string $name, string $path = '/', ?string $domain = null): void {}
            public function getSetCookieLines(): array { return []; }
        };
    }

    private function makeRequest(): Request
    {
        return new Request(
            method: 'GET',
            uri: '/',
            headers: [],
            query: [],
            post: [],
            server: [],
            cookies: [],
        );
    }
}
