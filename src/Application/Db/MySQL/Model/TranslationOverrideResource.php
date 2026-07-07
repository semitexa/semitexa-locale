<?php

declare(strict_types=1);

namespace Semitexa\Locale\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\PrimaryKey;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Metadata\HasColumnReferences;
use Semitexa\Orm\Metadata\HasRelationReferences;

/**
 * One tenant's override of a single translation message for a locale.
 *
 * Tenant-scoped (same_storage): the ORM gate filters every read by the ambient
 * tenant, so one tenant can neither read nor overwrite another's overrides,
 * even on the identical (locale, message_key). The global code-shipped catalog
 * is the fallback for anything not overridden here.
 */
#[FromTable(name: 'locale_translation_override')]
#[Index(columns: ['tenant_id', 'locale', 'message_key'], unique: true, name: 'uniq_locale_override_scope')]
#[TenantScoped(strategy: 'same_storage', column: 'tenant_id')]
final readonly class TranslationOverrideResource
{
    use HasColumnReferences;
    use HasRelationReferences;

    public function __construct(
        #[PrimaryKey(strategy: 'manual')]
        #[Column(type: MySqlType::Varchar, length: 36)]
        public string $id,

        /** Owning tenant; the ORM gate filters every query by this. */
        #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
        public ?string $tenant_id,

        #[Column(type: MySqlType::Varchar, length: 16)]
        public string $locale,

        #[Column(type: MySqlType::Varchar, length: 255)]
        public string $message_key,

        #[Column(type: MySqlType::LongText)]
        public string $value,

        #[Column(type: MySqlType::Datetime)]
        public \DateTimeImmutable $updated_at,
    ) {}
}
