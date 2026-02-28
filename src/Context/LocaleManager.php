<?php

declare(strict_types=1);

namespace Semitexa\Locale\Context;

use Semitexa\Core\Locale\LocaleContextInterface;

/**
 * Request-scoped locale context backed by LocaleContextStore.
 *
 * LocaleContextStore isolates the active locale per Swoole coroutine, so
 * concurrent HTTP requests never share locale state regardless of how many
 * times getInstance() is called within the same process.
 */
class LocaleManager implements LocaleContextInterface
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getLocale(): string
    {
        return LocaleContextStore::getLocale();
    }

    public function setLocale(string $locale): void
    {
        LocaleContextStore::setLocale($locale);
    }

    public function getFallbackLocale(): string
    {
        return LocaleContextStore::getFallbackLocale();
    }

    public function setFallbackLocale(string $locale): void
    {
        LocaleContextStore::setFallbackLocale($locale);
    }

    public static function get(): ?self
    {
        return self::$instance;
    }

    public static function getOrFail(): self
    {
        return self::$instance ?? self::getInstance();
    }
}
