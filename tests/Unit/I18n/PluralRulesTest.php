<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\I18n;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\I18n\PluralRules;

final class PluralRulesTest extends TestCase
{
    #[Test]
    #[DataProvider('germanicProvider')]
    public function germanic_languages(string $locale, int $count, string $expected): void
    {
        $this->assertSame($expected, PluralRules::category($locale, $count));
    }

    public static function germanicProvider(): array
    {
        return [
            'en 0' => ['en', 0, 'other'],
            'en 1' => ['en', 1, 'one'],
            'en 2' => ['en', 2, 'other'],
            'en 100' => ['en', 100, 'other'],
            'de 1' => ['de', 1, 'one'],
            'de 5' => ['de', 5, 'other'],
        ];
    }

    #[Test]
    #[DataProvider('slavicEastProvider')]
    public function slavic_east_languages(string $locale, int $count, string $expected): void
    {
        $this->assertSame($expected, PluralRules::category($locale, $count));
    }

    public static function slavicEastProvider(): array
    {
        return [
            'uk 1' => ['uk', 1, 'one'],
            'uk 2' => ['uk', 2, 'few'],
            'uk 3' => ['uk', 3, 'few'],
            'uk 4' => ['uk', 4, 'few'],
            'uk 5' => ['uk', 5, 'many'],
            'uk 11' => ['uk', 11, 'many'],
            'uk 12' => ['uk', 12, 'many'],
            'uk 21' => ['uk', 21, 'one'],
            'uk 22' => ['uk', 22, 'few'],
            'uk 25' => ['uk', 25, 'many'],
            'uk 100' => ['uk', 100, 'many'],
            'uk 101' => ['uk', 101, 'one'],
            'uk 111' => ['uk', 111, 'many'],
            'ru 1' => ['ru', 1, 'one'],
            'ru 2' => ['ru', 2, 'few'],
            'ru 5' => ['ru', 5, 'many'],
            'ru 21' => ['ru', 21, 'one'],
        ];
    }

    #[Test]
    #[DataProvider('slavicWestProvider')]
    public function slavic_west_languages(string $locale, int $count, string $expected): void
    {
        $this->assertSame($expected, PluralRules::category($locale, $count));
    }

    public static function slavicWestProvider(): array
    {
        return [
            'pl 0' => ['pl', 0, 'many'],
            'pl 1' => ['pl', 1, 'one'],
            'pl 2' => ['pl', 2, 'few'],
            'pl 3' => ['pl', 3, 'few'],
            'pl 4' => ['pl', 4, 'few'],
            'pl 5' => ['pl', 5, 'many'],
            'pl 12' => ['pl', 12, 'many'],
            'pl 13' => ['pl', 13, 'many'],
            'pl 14' => ['pl', 14, 'many'],
            'pl 22' => ['pl', 22, 'few'],
            'pl 23' => ['pl', 23, 'few'],
            'pl 25' => ['pl', 25, 'many'],
            'pl 112' => ['pl', 112, 'many'],
        ];
    }

    #[Test]
    public function regional_locale_uses_language_family(): void
    {
        $this->assertSame('one', PluralRules::category('en-US', 1));
        $this->assertSame('other', PluralRules::category('en-GB', 5));
    }

    #[Test]
    public function unknown_language_defaults_to_germanic(): void
    {
        $this->assertSame('one', PluralRules::category('xx', 1));
        $this->assertSame('other', PluralRules::category('xx', 2));
    }
}
