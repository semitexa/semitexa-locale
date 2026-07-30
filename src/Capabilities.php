<?php

declare(strict_types=1);

namespace Semitexa\Locale;

use Semitexa\Core\Attribute\Capability;

/**
 * What this package offers, for the capability catalog.
 *
 * Without this the package is invisible to anyone whose project has not
 * installed it - which is precisely the audience worth telling, since they are
 * the ones about to build it by hand. The convention is one `Capabilities` class
 * per package: a definite place to look, and a definite place for a guard to
 * check.
 *
 * Nothing reads this at runtime.
 */
#[Capability(
    id: 'locale.translations',
    summary: 'Locale resolution and translation with catalog files plus per-tenant database overrides.',
    useWhen: 'The interface is read in more than one language, or one tenant wants different wording.',
    avoidWhen: 'Single language, single tenant, and no plan for a second.',
    replaces: [
        'translated strings selected by an if-branch on the current locale',
        'a per-tenant copy of a template, forked to change three words',
    ],
)]
final class Capabilities
{
}
