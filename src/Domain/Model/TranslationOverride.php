<?php

declare(strict_types=1);

namespace Semitexa\Locale\Domain\Model;

/**
 * One tenant's replacement for a catalog message.
 *
 * White-labelling is what this exists for: two sites on one install call the
 * same button different things, and neither is a change to the shipped
 * catalog. The override wins for its tenant and locale, and nothing else.
 */
final readonly class TranslationOverride
{
    public function __construct(
        private string $id,
        private ?string $tenantId,
        private string $locale,
        private string $messageKey,
        private string $value,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
