<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Cookie\CookieJarInterface;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Core\Request;
use Semitexa\Locale\Context\LocaleContextStore;
use Semitexa\Locale\Context\LocaleManager;
use Semitexa\Locale\Event\LocaleResolved;
use Semitexa\Locale\LocaleBootstrapper;
use Semitexa\Locale\LocaleConfig;

final class LocaleBootstrapperTest extends TestCase
{
    protected function tearDown(): void
    {
        LocaleContextStore::clearFallback();
    }

    #[Test]
    public function resolves_locale_via_path_strategy(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en', 'de'],
            resolverPriority: ['path'],
        );

        $bootstrapper = new LocaleBootstrapper($manager, $config);
        $bootstrapper->resolve($this->makeRequest('/de/page'));

        $this->assertSame('de', $manager->getLocale());
    }

    #[Test]
    public function resolves_locale_via_header_strategy(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en', 'uk'],
            resolverPriority: ['header'],
        );

        $bootstrapper = new LocaleBootstrapper($manager, $config);
        $bootstrapper->resolve($this->makeRequest('/', ['Accept-Language' => 'uk']));

        $this->assertSame('uk', $manager->getLocale());
    }

    #[Test]
    public function chain_falls_through_to_second_resolver(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en', 'de'],
            resolverPriority: ['path', 'header'],
        );

        $bootstrapper = new LocaleBootstrapper($manager, $config);
        $bootstrapper->resolve($this->makeRequest('/page', ['Accept-Language' => 'de']));

        $this->assertSame('de', $manager->getLocale());
    }

    #[Test]
    public function does_not_set_locale_when_no_match(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en'],
            resolverPriority: ['path'],
        );

        $bootstrapper = new LocaleBootstrapper($manager, $config);
        $bootstrapper->resolve($this->makeRequest('/fr/page'));

        $this->assertSame('en', $manager->getLocale()); // default
    }

    #[Test]
    public function is_enabled_reflects_config(): void
    {
        $manager = new LocaleManager();

        $enabled = new LocaleBootstrapper($manager, new LocaleConfig(enabled: true));
        $disabled = new LocaleBootstrapper($manager, new LocaleConfig(enabled: false));

        $this->assertTrue($enabled->isEnabled());
        $this->assertFalse($disabled->isEnabled());
    }

    #[Test]
    public function exposes_locale_context(): void
    {
        $manager = new LocaleManager();
        $bootstrapper = new LocaleBootstrapper($manager);

        $this->assertSame($manager, $bootstrapper->getLocaleContext());
    }

    #[Test]
    public function resolves_locale_via_cookie_strategy(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en', 'pl'],
            resolverPriority: ['cookie'],
        );

        $cookieJar = $this->createCookieJar(['locale' => 'pl']);
        $bootstrapper = new LocaleBootstrapper($manager, $config);
        $bootstrapper->resolve($this->makeRequest(), $cookieJar);

        $this->assertSame('pl', $manager->getLocale());
    }

    #[Test]
    public function cookie_resolver_skipped_when_no_cookie_jar(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en', 'de'],
            resolverPriority: ['cookie', 'header'],
        );

        $bootstrapper = new LocaleBootstrapper($manager, $config);
        $bootstrapper->resolve($this->makeRequest('/', ['Accept-Language' => 'de']));

        $this->assertSame('de', $manager->getLocale());
    }

    #[Test]
    public function dispatches_locale_resolved_event(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en', 'de'],
            resolverPriority: ['path'],
        );

        $dispatched = [];
        $events = $this->createEventDispatcher($dispatched);

        $bootstrapper = new LocaleBootstrapper($manager, $config, $events);
        $bootstrapper->resolve($this->makeRequest('/de/page'));

        $this->assertCount(1, $dispatched);
        $this->assertInstanceOf(LocaleResolved::class, $dispatched[0]);
        $this->assertSame('de', $dispatched[0]->locale);
        $this->assertSame('path', $dispatched[0]->resolvedBy);
    }

    #[Test]
    public function does_not_dispatch_event_when_no_match(): void
    {
        $manager = new LocaleManager();
        $config = new LocaleConfig(
            supportedLocales: ['en'],
            resolverPriority: ['path'],
        );

        $dispatched = [];
        $events = $this->createEventDispatcher($dispatched);

        $bootstrapper = new LocaleBootstrapper($manager, $config, $events);
        $bootstrapper->resolve($this->makeRequest('/fr/page'));

        $this->assertCount(0, $dispatched);
    }

    private function makeRequest(string $uri = '/', array $headers = []): Request
    {
        return new Request(
            method: 'GET',
            uri: $uri,
            headers: $headers,
            query: [],
            post: [],
            server: [],
            cookies: [],
        );
    }

    private function createCookieJar(array $cookies): CookieJarInterface
    {
        return new class($cookies) implements CookieJarInterface {
            public function __construct(private readonly array $cookies) {}
            public function get(string $name, ?string $default = null): ?string { return $this->cookies[$name] ?? $default; }
            public function has(string $name): bool { return isset($this->cookies[$name]); }
            public function set(string $name, string $value, array $options = []): void {}
            public function remove(string $name, string $path = '/', ?string $domain = null): void {}
            public function getSetCookieLines(): array { return []; }
        };
    }

    private function createEventDispatcher(array &$dispatched): EventDispatcherInterface
    {
        return new class($dispatched) implements EventDispatcherInterface {
            public function __construct(private array &$dispatched) {}
            public function create(string $eventClass, array $payload): object { return new $eventClass(...$payload); }
            public function dispatch(object $event): void { $this->dispatched[] = $event; }
            public function addPostDispatchHook(callable $hook): void {}
        };
    }
}
