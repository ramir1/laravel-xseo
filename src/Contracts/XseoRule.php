<?php

declare(strict_types=1);

namespace Ramir\Xseo\Contracts;

use Ramir\Xseo\XseoManager;

interface XseoRule
{
    /**
     * @return array<string, mixed>
     */
    public function handle(XseoManager $xseo, mixed ...$params): array;
}
