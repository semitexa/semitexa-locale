<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Service\I18n;

/**
 * CLDR-based plural category resolution.
 *
 * Maps locale codes to their plural function and returns the appropriate
 * plural category: 'zero', 'one', 'two', 'few', 'many', 'other'.
 *
 * @see https://www.unicode.org/cldr/charts/latest/supplemental/language_plural_rules.html
 */
final class PluralRules
{
    /**
     * @return 'zero'|'one'|'two'|'few'|'many'|'other'
     */
    public static function category(string $locale, int $count): string
    {
        $language = strtolower(explode('-', str_replace('_', '-', $locale), 2)[0]);

        return match (self::family($language)) {
            'germanic' => self::germanic($count),
            'slavic_east' => self::slavicEast($count),
            'slavic_west' => self::slavicWest($count),
            default => self::germanic($count),
        };
    }

    /**
     * English, German, Dutch, etc.: one | other
     */
    private static function germanic(int $count): string
    {
        return $count === 1 ? 'one' : 'other';
    }

    /**
     * Ukrainian, Russian, Belarusian: one | few | many
     *
     * 1 елемент, 2-4 елементи, 5-20 елементів, 21 елемент, 22-24 елементи...
     */
    private static function slavicEast(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        return match (true) {
            $mod10 === 1 && $mod100 !== 11 => 'one',
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20) => 'few',
            default => 'many',
        };
    }

    /**
     * Polish: one | few | many
     *
     * 1 element, 2-4 elementy, 5-21 elementów, 22-24 elementy...
     */
    private static function slavicWest(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        return match (true) {
            $count === 1 => 'one',
            $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => 'few',
            default => 'many',
        };
    }

    private static function family(string $language): string
    {
        return match ($language) {
            'en', 'de', 'nl', 'sv', 'da', 'no', 'nb', 'nn' => 'germanic',
            'uk', 'ru', 'be' => 'slavic_east',
            'pl' => 'slavic_west',
            default => 'germanic',
        };
    }
}
