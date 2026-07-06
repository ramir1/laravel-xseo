<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Ramir\Xseo\Facades\Xseo;
use Ramir\Xseo\XseoManager;

it('merges default rule and named rule, named rule wins on conflicts', function () {
    $xseo = new XseoManager;
    $xseo->rule('default', fn () => ['og:type' => 'website', 'title' => 'Default']);
    $xseo->rule('home', fn () => ['title' => 'Home']);

    $xseo->create('home');

    expect($xseo->get('og:type'))->toBe('website');
    expect($xseo->get('title'))->toBe('Home');
});

it('parent() returns the raw result of another rule without mutating state', function () {
    $xseo = new XseoManager;
    $xseo->rule('base', fn () => ['title' => 'Base title']);

    expect($xseo->parent('base'))->toBe(['title' => 'Base title']);
    expect($xseo->get()->has('title'))->toBeFalse();
});

it('parent() returns an empty array for an unknown rule name', function () {
    $xseo = new XseoManager;

    expect($xseo->parent('nope'))->toBe([]);
});

it('get()/set() merge and read metas', function () {
    $xseo = new XseoManager;

    $xseo->set(['title' => 'A']);
    $xseo->set(['description' => 'B']);

    expect($xseo->get()->all())->toBe(['title' => 'A', 'description' => 'B']);
});

it('config xseo.copy auto-fills og:title/twitter:title from title when not explicitly set', function () {
    config(['xseo.copy' => ['title' => ['og:title', 'twitter:title']]]);

    $xseo = new XseoManager;
    $xseo->rule('home', fn () => ['title' => 'Hello']);
    $xseo->create('home');

    expect($xseo->get('og:title'))->toBe('Hello');
    expect($xseo->get('twitter:title'))->toBe('Hello');
});

it('does not override an explicitly set target of xseo.copy', function () {
    config(['xseo.copy' => ['title' => ['og:title']]]);

    $xseo = new XseoManager;
    $xseo->rule('home', fn () => ['title' => 'Hello', 'og:title' => 'Custom OG title']);
    $xseo->create('home');

    expect($xseo->get('og:title'))->toBe('Custom OG title');
});

it('is resolvable via the Xseo facade as a singleton', function () {
    Xseo::rule('home', fn () => ['title' => 'Via facade']);
    Xseo::create('home');

    expect(Xseo::get('title'))->toBe('Via facade');
});

it('returns null for a missing key instead of the whole collection', function () {
    $xseo = new XseoManager;
    $xseo->set(['title' => 'A']);

    expect($xseo->get('missing'))->toBeNull();
    expect($xseo->get())->toBeInstanceOf(Collection::class);
});

it('auto-fills og:url from canonical via xseo.copy', function () {
    config(['xseo.copy' => ['canonical' => ['og:url']]]);

    $xseo = new XseoManager;
    $xseo->rule('home', fn () => ['canonical' => 'https://example.test/']);
    $xseo->create('home');

    expect($xseo->get('og:url'))->toBe('https://example.test/');
});
