<?php

declare(strict_types=1);

namespace Semitexa\Locale\Domain\Model;

readonly class LocaleResolution
{
    public function __construct(
        public string $locale,
        public string $resolvedBy,
        public bool $hadPathPrefix,
        public ?string $strippedPath,
    ) {}
}
