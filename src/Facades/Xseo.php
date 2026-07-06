<?php

declare(strict_types=1);

namespace Ramir\Xseo\Facades;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Ramir\Xseo\XseoManager;

/**
 * @method static void rule(string $name, Closure $callback)
 * @method static array parent(string|array|Closure $rule, mixed ...$params)
 * @method static void create(string|array|Closure $rule, mixed ...$params)
 * @method static Collection|string|null get(string|false $key = false)
 * @method static Collection set(array $meta = [])
 * @method static string generate()
 *
 * @see XseoManager
 */
class Xseo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'xseo';
    }
}
