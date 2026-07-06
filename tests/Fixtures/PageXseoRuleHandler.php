<?php

declare(strict_types=1);

namespace Ramir\Xseo\Tests\Fixtures;

use Ramir\Xseo\XseoManager;

class PageXseoRuleHandler
{
    public function meta(XseoManager $xseo, string $slug): array
    {
        return ['title' => "Page: $slug"];
    }
}
