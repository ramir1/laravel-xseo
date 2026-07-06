<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Ramir\Xseo\Facades\Xseo;

if (! function_exists('xseo')) {
    /**
     * Convenience wrapper around the Xseo facade:
     *   xseo()               -> Collection of all current metas
     *   xseo('title')        -> a single meta value (via Xseo::get())
     *   xseo(['title'=>'x']) -> sets/merges given metas, returns resulting Collection
     */
    function xseo(array|string|null $value = null): string|Collection
    {
        if (is_array($value)) {
            return Xseo::set($value);
        }

        if (is_string($value)) {
            return Xseo::get($value);
        }

        return Xseo::get();
    }
}
