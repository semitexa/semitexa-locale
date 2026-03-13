<?php

declare(strict_types=1);

namespace Semitexa\Locale\Context;

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
 * Outside of a coroutine (CLI, tests) a plain static fallback is used.
 */
final class LocaleContextStore
{
    private const LOCALE_KEY         = '__locale';
    private const FALLBACK_KEY       = '__locale_fallback';
    private const URL_PREFIX_KEY     = '__locale_url_prefix';
    private const DEFAULT_LOCALE_KEY = '__locale_default';

    private static string $staticLocale         = 'en';
    private static string $staticFallbackLocale = 'en';
    private static bool $staticUrlPrefix        = false;
    private static string $staticDefaultLocale  = 'en';

    public static function setLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            Coroutine::getContext()[self::LOCALE_KEY] = $locale;
            return;
        }

        self::$staticLocale = $locale;
    }

    public static function getLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::LOCALE_KEY] ?? self::$staticLocale;
        }

        return self::$staticLocale;
    }

    public static function setFallbackLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            Coroutine::getContext()[self::FALLBACK_KEY] = $locale;
            return;
        }

        self::$staticFallbackLocale = $locale;
    }

    public static function getFallbackLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::FALLBACK_KEY] ?? self::$staticFallbackLocale;
        }

        return self::$staticFallbackLocale;
    }

    public static function setUrlPrefixEnabled(bool $enabled): void
    {
        if (self::inCoroutine()) {
            Coroutine::getContext()[self::URL_PREFIX_KEY] = $enabled;
            return;
        }

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
            Coroutine::getContext()[self::DEFAULT_LOCALE_KEY] = $locale;
            return;
        }

        self::$staticDefaultLocale = $locale;
    }

    public static function getDefaultLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::DEFAULT_LOCALE_KEY] ?? self::$staticDefaultLocale;
        }

        return self::$staticDefaultLocale;
    }

    /**
     * Reset static fallback state (useful in CLI/test teardown).
     */
    public static function clearFallback(): void
    {
        self::$staticLocale         = 'en';
        self::$staticFallbackLocale = 'en';
        self::$staticUrlPrefix      = false;
        self::$staticDefaultLocale  = 'en';
    }

    private static function inCoroutine(): bool
    {
        return class_exists(Coroutine::class, false) && Coroutine::getCid() > 0;
    }
}
