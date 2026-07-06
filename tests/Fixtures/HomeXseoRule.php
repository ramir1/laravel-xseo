<?php

declare(strict_types=1);

namespace Ramir\Xseo\Tests\Fixtures;

use Ramir\Xseo\Contracts\XseoRule;
use Ramir\Xseo\XseoManager;

class HomeXseoRule implements XseoRule
{
    public static int $constructed = 0;

    public function __construct()
    {
        self::$constructed++;
    }

    public function handle(XseoManager $xseo, mixed ...$params): array
    {
        return ['title' => 'From class rule'];
    }
}
