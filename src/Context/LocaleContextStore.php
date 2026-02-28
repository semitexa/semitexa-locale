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
    private const LOCALE_KEY   = '__locale';
    private const FALLBACK_KEY = '__locale_fallback';

    private static string $fallbackLocale         = 'en';
    private static string $fallbackFallbackLocale = 'en';

    public static function setLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            Coroutine::getContext()[self::LOCALE_KEY] = $locale;
            return;
        }

        self::$fallbackLocale = $locale;
    }

    public static function getLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::LOCALE_KEY] ?? self::$fallbackFallbackLocale;
        }

        return self::$fallbackLocale;
    }

    public static function setFallbackLocale(string $locale): void
    {
        if (self::inCoroutine()) {
            Coroutine::getContext()[self::FALLBACK_KEY] = $locale;
            return;
        }

        self::$fallbackFallbackLocale = $locale;
    }

    public static function getFallbackLocale(): string
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext()[self::FALLBACK_KEY] ?? self::$fallbackFallbackLocale;
        }

        return self::$fallbackFallbackLocale;
    }

    /**
     * Reset static fallback state (useful in CLI/test teardown).
     */
    public static function clearFallback(): void
    {
        self::$fallbackLocale         = 'en';
        self::$fallbackFallbackLocale = 'en';
    }

    private static function inCoroutine(): bool
    {
        return class_exists(Coroutine::class, false) && Coroutine::getCid() > 0;
    }
}
