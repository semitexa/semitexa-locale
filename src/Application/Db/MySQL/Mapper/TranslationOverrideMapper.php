<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Db\MySQL\Mapper;

use Semitexa\Locale\Application\Db\MySQL\Model\TranslationOverrideResource;
use Semitexa\Locale\Domain\Model\TranslationOverride;
use Semitexa\Orm\Attribute\AsMapper;
use Semitexa\Orm\Domain\Contract\ResourceModelMapperInterface;

/** The bridge between the MySQL row and the override the translator consults. */
#[AsMapper(resourceModel: TranslationOverrideResource::class, domainModel: TranslationOverride::class)]
final class TranslationOverrideMapper implements ResourceModelMapperInterface
{
    public function toDomain(object $resourceModel): object
    {
        $resourceModel instanceof TranslationOverrideResource
            || throw new \InvalidArgumentException('Unexpected resource model.');

        return new TranslationOverride(
            id: $resourceModel->id,
            tenantId: $resourceModel->tenant_id,
            locale: $resourceModel->locale,
            messageKey: $resourceModel->message_key,
            value: $resourceModel->value,
            updatedAt: $resourceModel->updated_at,
        );
    }

    public function toSourceModel(object $domainModel): object
    {
        $domainModel instanceof TranslationOverride || throw new \InvalidArgumentException('Unexpected domain model.');

        return new TranslationOverrideResource(
            id: $domainModel->getId(),
            tenant_id: $domainModel->getTenantId(),
            locale: $domainModel->getLocale(),
            message_key: $domainModel->getMessageKey(),
            value: $domainModel->getValue(),
            updated_at: $domainModel->getUpdatedAt() ?? new \DateTimeImmutable(),
        );
    }
}
