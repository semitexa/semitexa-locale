# semitexa/locale

Internationalization module with per-request locale resolution, coroutine-safe context, and translation with CLDR plural support.

## Purpose

Resolves the active locale on every request through a composable, configurable resolver chain (commonly cookie, URL path, and Accept-Language header; actual order follows configuration). Stores the result in coroutine-safe context and provides a translation service with CLDR plural rules for Germanic and Slavic languages, with other languages currently falling back to Germanic rules.

## Role in Semitexa

Depends on Core. Optional integration with Tenancy for tenant-driven locale defaults. Used by SSR for locale-aware template rendering, Mail for localized email content, and any module that needs translated output.

## Key Features

- Resolver chain (configuration-driven priority): `CookieLocaleResolver`, `PathLocaleResolver`, `HeaderLocaleResolver` (RFC 7231 quality factors)
- Custom resolvers via `LocaleResolverInterface`
- Coroutine-safe `LocaleContextStore` for Swoole
- `TranslationService` with named placeholders and `transChoice()` for plurals
- CLDR plural rules: Germanic (en, de), Slavic East (uk, ru), Slavic West (pl)
- JSON translation files per module (`Application/View/locales/{lang}.json`)
- Module-namespaced keys (`MyModule.welcome`)
- `LocaleResolved` event for post-resolution hooks
- `TenantResolvedLocaleListener` for automatic tenant-based locale when Tenancy is active

## Notes

Translation catalogs are loaded once at worker boot and shared across requests. The context store uses Swoole coroutine storage in HTTP mode and static fallback in CLI/test mode.
