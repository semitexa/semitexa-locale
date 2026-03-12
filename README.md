# Semitexa Locale

Internationalization (i18n) module for the Semitexa Framework. Provides per-request locale resolution, coroutine-safe context storage, and a translation system with CLDR plural support.

## Requirements

- PHP 8.4+
- `semitexa/core` ^1.0
- Optional: `semitexa/tenancy` for tenant-driven locale resolution

## Installation

```bash
composer require semitexa/locale
```

## Configuration

All configuration is done via environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `LOCALE_ENABLED` | `true` | Enable/disable locale resolution |
| `LOCALE_DEFAULT` | `en` | Default locale code |
| `LOCALE_FALLBACK` | `en` | Fallback locale for missing translations |
| `LOCALE_SUPPORTED` | `en,uk,de,pl,ru` | Comma-separated list of supported locales |
| `LOCALE_STRATEGY` | `path` | Resolution strategy: `path`, `header`, or `both` |
| `LOCALE_COOKIE_ENABLED` | `false` | Enable cookie-based locale persistence |

Or construct `LocaleConfig` directly:

```php
$config = new LocaleConfig(
    enabled: true,
    defaultLocale: 'en',
    fallbackLocale: 'en',
    supportedLocales: ['en', 'uk', 'de', 'pl'],
    resolverPriority: ['cookie', 'path', 'header'],
);
```

## Locale Resolution

Locale is resolved per-request through a chain of resolvers, tried in priority order:

| Resolver | Key | Description |
|----------|-----|-------------|
| `CookieLocaleResolver` | `cookie` | Reads from a cookie (default name: `locale`) |
| `PathLocaleResolver` | `path` | Extracts from URL first segment (`/en/dashboard`) |
| `HeaderLocaleResolver` | `header` | Parses `Accept-Language` with RFC 7231 quality factors |

The first resolver to return a match wins. If none match, the locale remains at the default.

### Custom resolvers

Implement `LocaleResolverInterface`:

```php
use Semitexa\Locale\Resolver\LocaleResolverInterface;
use Semitexa\Core\Request;

final class QueryParamLocaleResolver implements LocaleResolverInterface
{
    public function resolve(Request $request): ?string
    {
        return $request->query['lang'] ?? null;
    }
}
```

Compose resolvers with `LocaleResolverChain`:

```php
$chain = new LocaleResolverChain(
    new CookieLocaleResolver($cookieJar, $supported),
    new PathLocaleResolver($supported),
    new QueryParamLocaleResolver(),
    new HeaderLocaleResolver($supported),
);
```

## Context Store

`LocaleContextStore` provides coroutine-safe locale storage. In Swoole HTTP mode, each request runs in its own coroutine with isolated state. Outside coroutines (CLI, tests), static fallback properties are used.

```php
use Semitexa\Locale\Context\LocaleContextStore;

LocaleContextStore::setLocale('uk');
LocaleContextStore::getLocale(); // 'uk'

LocaleContextStore::setFallbackLocale('en');
LocaleContextStore::getFallbackLocale(); // 'en'

// Reset (useful in test teardown)
LocaleContextStore::clearFallback();
```

`LocaleManager` implements `LocaleContextInterface` and wraps the store. Obtain it via DI rather than the deprecated `getInstance()` singleton.

## Translation

### Loading translations

Place JSON files in your module's locale directory:

```
src/modules/MyModule/Application/View/locales/
    en.json
    uk.json
    de.json
```

### JSON format

Simple keys:
```json
{
    "welcome": "Welcome to the app",
    "hello": "Hello, {{name}}!"
}
```

Plural forms (CLDR categories):
```json
{
    "items": {
        "one": "{{count}} item",
        "other": "{{count}} items"
    }
}
```

For Slavic languages (uk, ru, pl), use all three required categories:
```json
{
    "items": {
        "one": "{{count}} елемент",
        "few": "{{count}} елементи",
        "many": "{{count}} елементів"
    }
}
```

Legacy pipe-delimited format is also supported: `"{{count}} item|{{count}} items"`

### Using TranslationService

```php
use Semitexa\Locale\I18n\TranslationService;
use Semitexa\Locale\I18n\TranslationCatalog;
use Semitexa\Locale\I18n\Loader\JsonFileLoader;

// Build catalog (once at worker boot)
$catalog = new TranslationCatalog();
$loader = new JsonFileLoader('/path/to/src/modules');
$loader->load($catalog);

// Create service
$service = new TranslationService($catalog, $localeContext);

$service->trans('welcome');                         // "Welcome to the app"
$service->trans('hello', ['name' => 'World']);       // "Hello, World!"
$service->transChoice('items', 1);                  // "1 item"
$service->transChoice('items', 5);                  // "5 items"
$service->trans('welcome', locale: 'uk');            // explicit locale override
$service->hasTranslation('welcome');                 // true
```

### Module-namespaced keys

Translations are accessible both by flat key and by `Module.key`:

```php
$service->trans('welcome');           // first module to register the key wins
$service->trans('MyModule.welcome');  // explicit module scope
```

### Supported plural rules

| Family | Languages | Categories |
|--------|-----------|------------|
| Germanic | en, de, nl, sv, da, no | `one`, `other` |
| Slavic East | uk, ru, be | `one`, `few`, `many` |
| Slavic West | pl | `one`, `few`, `many` |

Unknown languages default to Germanic rules.

## Events

### LocaleResolved

Dispatched after a locale is successfully resolved from a request:

```php
use Semitexa\Locale\Event\LocaleResolved;
use Semitexa\Core\Attributes\AsEventListener;

#[AsEventListener(event: LocaleResolved::class)]
final class PersistLocaleCookieListener
{
    public function handle(LocaleResolved $event): void
    {
        // $event->locale     — resolved locale code (e.g. "uk")
        // $event->resolvedBy — resolver key ("path", "header", "cookie")
    }
}
```

### TenantResolvedLocaleListener

When `semitexa/tenancy` is installed, this listener automatically sets the locale from the tenant's `LocaleLayer` when a tenant is resolved.

## Testing

```bash
cd pakages/semitexa-locale
../../vendor/bin/phpunit
```

112 tests covering resolvers, context store, plural rules, translation catalog, loader, service, bootstrapper, and events.

## Package Structure

```
src/
├── LocaleBootstrapper.php          — Request lifecycle orchestrator
├── LocaleConfig.php                — Configuration DTO
├── Context/
│   ├── LocaleContextStore.php      — Coroutine-safe storage
│   └── LocaleManager.php           — LocaleContextInterface implementation
├── Resolver/
│   ├── LocaleResolverInterface.php — Resolver contract
│   ├── LocaleResolverChain.php     — Composable ordered chain
│   ├── PathLocaleResolver.php      — URL path segment
│   ├── HeaderLocaleResolver.php    — Accept-Language with q-factors
│   └── CookieLocaleResolver.php    — Cookie-based user preference
├── I18n/
│   ├── TranslationService.php      — Instance-based translation
│   ├── TranslationCatalog.php      — Readonly message store
│   ├── PluralRules.php             — CLDR plural categories
│   └── Loader/
│       └── JsonFileLoader.php      — Module JSON file discovery
└── Event/
    ├── LocaleResolved.php              — Post-resolution event
    └── TenantResolvedLocaleListener.php — Tenancy integration
```

## License

MIT
