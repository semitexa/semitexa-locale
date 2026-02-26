<?php

declare(strict_types=1);

namespace Semitexa\Locale\Context;

use Semitexa\Core\Locale\LocaleContextInterface;
use Semitexa\Core\Locale\DefaultLocaleContext;

class LocaleManager implements LocaleContextInterface
{
    private static ?self $instance = null;

    private string $locale = 'en';
    private string $fallbackLocale = 'en';

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
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
