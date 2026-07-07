<?php

declare(strict_types=1);

use Ramir\Xseo\Tests\Fixtures\CustomDefaultsRule;
use Ramir\Xseo\XseoManager;

it('falls back to config xseo.defaults via the shipped DefaultRule when nothing else is registered', function () {
    config(['xseo.defaults' => ['og:type' => 'website', 'og:site_name' => 'Acme']]);

    $xseo = new XseoManager;
    $xseo->rule('home', fn () => ['title' => 'Home']);
    $xseo->create('home');

    expect($xseo->get('og:type'))->toBe('website');
    expect($xseo->get('og:site_name'))->toBe('Acme');
    expect($xseo->get('title'))->toBe('Home');
});

it('back-compat: resolves default to [] when nothing is configured for defaults at all', function () {
    config(['xseo.copy' => []]);

    $xseo = new XseoManager;
    $xseo->rule('home', fn () => ['title' => 'Home']);
    $xseo->create('home');

    expect($xseo->get()->all())->toBe(['title' => 'Home']);
});

it('config xseo.rules default still overrides defaults_class/xseo.defaults', function () {
    config([
        'xseo.defaults' => ['og:type' => 'from-defaults-array'],
        'xseo.rules' => ['default' => ['og:type' => 'from-config-rules']],
    ]);

    $xseo = new XseoManager;
    $xseo->create([]);

    expect($xseo->get('og:type'))->toBe('from-config-rules');
});

it('Xseo::rule(default, ...) still overrides both config xseo.rules and defaults_class', function () {
    config([
        'xseo.defaults' => ['og:type' => 'from-defaults-array'],
        'xseo.rules' => ['default' => ['og:type' => 'from-config-rules']],
    ]);

    $xseo = new XseoManager;
    $xseo->rule('default', fn () => ['og:type' => 'from-closure']);
    $xseo->create([]);

    expect($xseo->get('og:type'))->toBe('from-closure');
});

it('a custom xseo.defaults_class is resolved via the container instead of the shipped DefaultRule', function () {
    config(['xseo.defaults_class' => CustomDefaultsRule::class]);

    $xseo = new XseoManager;
    $xseo->create([]);

    expect($xseo->get('og:type'))->toBe('article');
    expect($xseo->get('og:site_name'))->toBe('Custom Defaults Site');
});

it('memoizes the defaults_class fallback instead of resolving it via the container on every create() call', function () {
    CustomDefaultsRule::$constructed = 0;
    config(['xseo.defaults_class' => CustomDefaultsRule::class]);

    $xseo = new XseoManager;
    $xseo->create([]);
    $xseo->create([]);
    $xseo->parent('default');

    expect(CustomDefaultsRule::$constructed)->toBe(1);
});
