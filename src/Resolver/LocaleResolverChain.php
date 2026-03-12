<?php

declare(strict_types=1);

namespace Semitexa\Locale\Resolver;

use Semitexa\Core\Request;

final class LocaleResolverChain implements LocaleResolverInterface
{
    /** @var LocaleResolverInterface[] */
    private array $resolvers;

    public function __construct(LocaleResolverInterface ...$resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function resolve(Request $request): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $locale = $resolver->resolve($request);

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }
}
