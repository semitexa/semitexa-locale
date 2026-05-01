<?php

declare(strict_types=1);

namespace Semitexa\Locale\Domain\Contract;

use Semitexa\Core\Request;

interface LocaleResolverInterface
{
    public function resolve(Request $request): ?string;
}
