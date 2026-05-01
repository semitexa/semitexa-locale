<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Request;
use Semitexa\Locale\Application\Service\Resolver\LocaleResolverChain;
use Semitexa\Locale\Domain\Contract\LocaleResolverInterface;

final class LocaleResolverChainTest extends TestCase
{
    #[Test]
    public function returns_first_non_null_result(): void
    {
        $first = $this->createResolver(null);
        $second = $this->createResolver('de');
        $third = $this->createResolver('en');

        $chain = new LocaleResolverChain($first, $second, $third);

        $this->assertSame('de', $chain->resolve($this->makeRequest()));
    }

    #[Test]
    public function returns_null_when_all_resolvers_return_null(): void
    {
        $chain = new LocaleResolverChain(
            $this->createResolver(null),
            $this->createResolver(null),
        );

        $this->assertNull($chain->resolve($this->makeRequest()));
    }

    #[Test]
    public function returns_null_with_no_resolvers(): void
    {
        $chain = new LocaleResolverChain();

        $this->assertNull($chain->resolve($this->makeRequest()));
    }

    #[Test]
    public function single_resolver_returns_its_value(): void
    {
        $chain = new LocaleResolverChain($this->createResolver('uk'));

        $this->assertSame('uk', $chain->resolve($this->makeRequest()));
    }

    private function createResolver(?string $result): LocaleResolverInterface
    {
        return new class($result) implements LocaleResolverInterface {
            public function __construct(private readonly ?string $result) {}

            public function resolve(Request $request): ?string
            {
                return $this->result;
            }
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
