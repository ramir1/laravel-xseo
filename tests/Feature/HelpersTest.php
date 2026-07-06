<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Ramir\Xseo\Facades\Xseo;

it('xseo() with no args returns all metas as a Collection', function () {
    Xseo::set(['title' => 'Home']);

    expect(xseo())->toBeInstanceOf(Collection::class);
    expect(xseo()->get('title'))->toBe('Home');
});

it('xseo(string) returns a single meta value', function () {
    Xseo::set(['title' => 'Home']);

    expect(xseo('title'))->toBe('Home');
});

it('xseo(array) sets metas and returns the resulting Collection', function () {
    $result = xseo(['title' => 'New title']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect(Xseo::get('title'))->toBe('New title');
});
