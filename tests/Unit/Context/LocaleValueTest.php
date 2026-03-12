<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Tenant\Layer\LocaleValue;

final class LocaleValueTest extends TestCase
{
    #[Test]
    public function creates_from_valid_two_letter_code(): void
    {
        $value = new LocaleValue('en');

        $this->assertSame('en', $value->code);
    }

    #[Test]
    public function creates_from_valid_regional_code(): void
    {
        $value = new LocaleValue('en-US');

        $this->assertSame('en-US', $value->code);
    }

    #[Test]
    public function rejects_invalid_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LocaleValue('INVALID');
    }

    #[Test]
    public function rejects_lowercase_region(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LocaleValue('en-us');
    }

    #[Test]
    public function from_code_normalizes_simple_code(): void
    {
        $value = LocaleValue::fromCode('EN');

        $this->assertSame('en', $value->code);
    }

    #[Test]
    public function from_code_normalizes_regional_code(): void
    {
        $value = LocaleValue::fromCode('en-us');

        $this->assertSame('en-US', $value->code);
    }

    #[Test]
    public function from_code_normalizes_mixed_case_regional(): void
    {
        $value = LocaleValue::fromCode('EN-us');

        $this->assertSame('en-US', $value->code);
    }

    #[Test]
    public function default_returns_en(): void
    {
        $value = LocaleValue::default();

        $this->assertSame('en', $value->code);
    }

    #[Test]
    public function raw_value_returns_code(): void
    {
        $value = new LocaleValue('uk');

        $this->assertSame('uk', $value->rawValue());
    }
}
