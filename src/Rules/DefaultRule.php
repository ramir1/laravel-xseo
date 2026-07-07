<?php

declare(strict_types=1);

namespace Ramir\Xseo\Rules;

use Ramir\Xseo\Contracts\XseoRule;
use Ramir\Xseo\XseoManager;

/**
 * Shipped fallback handler for the 'default' rule. Used only when neither
 * Xseo::rule('default', ...) nor config('xseo.rules')['default'] is
 * registered — see XseoManager::resolveRule(). Simply returns
 * config('xseo.defaults', []) as-is.
 *
 * Point config('xseo.defaults_class') at your own XseoRule implementation
 * instead if you need defaults computed at request time (current locale,
 * tenant, authenticated user, etc.) rather than a static config array.
 */
class DefaultRule implements XseoRule
{
    /**
     * @return array<string, mixed>
     */
    public function handle(XseoManager $xseo, mixed ...$params): array
    {
        return config('xseo.defaults', []);
    }
}
