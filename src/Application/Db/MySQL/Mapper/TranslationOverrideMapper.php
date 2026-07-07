<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Db\MySQL\Mapper;

use Semitexa\Orm\Attribute\AsMapper;
use Semitexa\Orm\Domain\Contract\ResourceModelMapperInterface;
use Semitexa\Locale\Application\Db\MySQL\Model\TranslationOverrideResource;

/**
 * Self-mapping mapper for {@see TranslationOverrideResource} — resource is the
 * domain model, both directions are clone-passthroughs.
 */
#[AsMapper(
    resourceModel: TranslationOverrideResource::class,
    domainModel: TranslationOverrideResource::class,
)]
final class TranslationOverrideMapper implements ResourceModelMapperInterface
{
    public function toDomain(object $resourceModel): object
    {
        $resourceModel instanceof TranslationOverrideResource
            || throw new \InvalidArgumentException('Unexpected resource model.');

        return clone $resourceModel;
    }

    public function toSourceModel(object $domainModel): object
    {
        $domainModel instanceof TranslationOverrideResource
            || throw new \InvalidArgumentException('Unexpected domain model.');

        return clone $domainModel;
    }
}
