<?php

declare(strict_types=1);

namespace Semitexa\Locale\Context;

use Semitexa\Core\Environment;
use Semitexa\Core\Lifecycle\CarriedContext;
use Semitexa\Core\Lifecycle\PerRequestStateRegistry;
use Swoole\Coroutine;

/**
 * Coroutine-safe storage for the active locale.
 *
 * In Swoole HTTP mode each request runs in its own coroutine.
 * Swoole\Coroutine::getContext() returns an ArrayObject that is
 * isolated per-coroutine and automatically destroyed when the
 * coroutine finishes, so locale changes in one request never
 * bleed into concurrent requests.
 *
 * Outside of a coroutine (CLI, tests) a plain static fallback is used. The
 * queue worker runs each job there, so the fallback is reset through
 * {@see PerRequestStateRegistry} after every unit of work — otherwise one
 * tenant's job leaves its locale for the next job on the worker.
 */
final class LocaleContextStore
{
    private const LOCALE_KEY            = '__locale';
    private const FALLBACK_KEY          = '__locale_fallback';
    private const URL_PREFIX_KEY        = '__locale_url_prefix';
    private const DEFAULT_LOCALE_KEY    = '__locale_default';
    private const SUPPORTED_LOCALES_KEY = '__locale_supported';

    // Unset until something sets them: a CLI process or a queue job then
    // reads LOCALE_DEFAULT / LOCALE_FALLBACK. They were 'en' outright, so a
    // queued mail on an install with LOCALE_DEFAULT=uk went out in English.
    private static ?string $staticLocale         = null;
    private static ?string $staticFallbackLocale = null;
    private static bool $staticUrlPrefix        = false;
    private static ?string $staticDefaultLocale  = null;
    /** @var string[] Empty = not set (callers fall back to their own config). */
    private static array $staticSupportedLocales = [];

    private static bool $registered = false;

    public static function setLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            self::ensureCarried();
            Coroutine::getContext()[self::LOCALE_KEY] = $locale;
            return;
        }

        self::ensureRegistered();
        self::$staticLocale = $locale;
    }

    public static function getLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::LOCALE_KEY] ?? self::$staticLocale ?? self::configured('LOCALE_DEFAULT');
        }

        return self::$staticLocale ?? self::configured('LOCALE_DEFAULT');
    }

    public static function setFallbackLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            self::ensureCarried();
            Coroutine::getContext()[self::FALLBACK_KEY] = $locale;
            return;
        }

        self::ensureRegistered();
        self::$staticFallbackLocale = $locale;
    }

    public static function getFallbackLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::FALLBACK_KEY] ?? self::$staticFallbackLocale ?? self::configured('LOCALE_FALLBACK');
        }

        return self::$staticFallbackLocale ?? self::configured('LOCALE_FALLBACK');
    }

    public static function setUrlPrefixEnabled(bool $enabled): void
    {
        if (self::inCoroutine()) {
            self::ensureCarried();
            Coroutine::getContext()[self::URL_PREFIX_KEY] = $enabled;
            return;
        }

        self::ensureRegistered();
        self::$staticUrlPrefix = $enabled;
    }

    public static function isUrlPrefixEnabled(): bool
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::URL_PREFIX_KEY] ?? self::$staticUrlPrefix;
        }

        return self::$staticUrlPrefix;
    }

    public static function setDefaultLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            self::ensureCarried();
            Coroutine::getContext()[self::DEFAULT_LOCALE_KEY] = $locale;
            return;
        }

        self::ensureRegistered();
        self::$staticDefaultLocale = $locale;
    }

    public static function getDefaultLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::DEFAULT_LOCALE_KEY] ?? self::$staticDefaultLocale ?? self::configured('LOCALE_DEFAULT');
        }

        return self::$staticDefaultLocale ?? self::configured('LOCALE_DEFAULT');
    }

    /**
     * The EFFECTIVE (per-tenant) supported-locale set for this request.
     * Set by locale resolution from the effective pack; an empty array means
     * "not set" — callers fall back to their own (global) config, so a cron /
     * pre-resolution context never sees a wrong tenant's set.
     *
     * @param string[] $locales
     */
    public static function setSupportedLocales(array $locales): void
    {
        if (self::inCoroutine()) {
            self::ensureCarried();
            Coroutine::getContext()[self::SUPPORTED_LOCALES_KEY] = $locales;
            return;
        }

        self::ensureRegistered();
        self::$staticSupportedLocales = $locales;
    }

    /** @return string[] Empty = not set for this request. */
    public static function getSupportedLocales(): array
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::SUPPORTED_LOCALES_KEY] ?? self::$staticSupportedLocales;
        }

        return self::$staticSupportedLocales;
    }

    /**
     * Reset static fallback state (useful in CLI/test teardown).
     */
    public static function clearFallback(): void
    {
        self::$staticLocale            = null;
        self::$staticFallbackLocale    = null;
        self::$staticUrlPrefix         = false;
        self::$staticDefaultLocale     = null;
        self::$staticSupportedLocales  = [];
    }

    private static function configured(string $key): string
    {
        $value = trim((string) Environment::getEnvValue($key, 'en'));

        return $value !== '' ? $value : 'en';
    }

    /** Lazy, so a worker that never sets a locale registers nothing. */
    private static function ensureRegistered(): void
    {
        if (self::$registered) {
            return;
        }

        PerRequestStateRegistry::register('locale_context_store', static function (): void {
            self::clearFallback();
        });
        self::$registered = true;
    }

    /** Work run after the response (an Async handler) renders in the request's locale. */
    private static function ensureCarried(): void
    {
        CarriedContext::carry(self::LOCALE_KEY, self::FALLBACK_KEY, self::URL_PREFIX_KEY, self::DEFAULT_LOCALE_KEY, self::SUPPORTED_LOCALES_KEY);
    }

    private static function inCoroutine(): bool
    {
        return class_exists(Coroutine::class, false) && Coroutine::getCid() > 0;
    }
}
