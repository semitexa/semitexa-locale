<?php

declare(strict_types=1);

namespace Semitexa\Locale\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Locale\Domain\Event\LocaleResolved;

final class LocaleResolvedTest extends TestCase
{
    #[Test]
    public function stores_locale_and_resolver(): void
    {
        $event = new LocaleResolved('de', 'path');

        $this->assertSame('de', $event->locale);
        $this->assertSame('path', $event->resolvedBy);
    }

    #[Test]
    public function is_readonly(): void
    {
        $ref = new \ReflectionClass(LocaleResolved::class);

        $this->assertTrue($ref->isReadOnly());
    }
}
