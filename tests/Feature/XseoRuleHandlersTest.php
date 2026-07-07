<?php

declare(strict_types=1);

use Ramir\Xseo\Facades\Xseo;
use Ramir\Xseo\Tests\Fixtures\HomeXseoRule;
use Ramir\Xseo\Tests\Fixtures\NotARule;
use Ramir\Xseo\Tests\Fixtures\PageXseoRuleHandler;
use Ramir\Xseo\XseoManager;

it('resolves a class-string XseoRule handler and memoizes the instance', function () {
    HomeXseoRule::$constructed = 0;
    config(['xseo.rules' => ['fixture.class' => HomeXseoRule::class]]);

    $xseo = new XseoManager;
    $xseo->create('fixture.class');
    $xseo->parent('fixture.class');

    expect($xseo->get('title'))->toBe('From class rule');
    expect(HomeXseoRule::$constructed)->toBe(1);
});

it('resolves a [Class, method] callable-style handler', function () {
    config(['xseo.rules' => ['fixture.method' => [PageXseoRuleHandler::class, 'meta']]]);

    $xseo = new XseoManager;
    $xseo->create('fixture.method', 'about');

    expect($xseo->get('title'))->toBe('Page: about');
});

it('resolves a plain associative array handler as static data without invocation', function () {
    config(['xseo.rules' => ['fixture.static' => ['title' => 'Static title']]]);

    $xseo = new XseoManager;
    $xseo->create('fixture.static');

    expect($xseo->get('title'))->toBe('Static title');
});

it('prefers a Closure registered via rule() over the same name in config xseo.rules', function () {
    config(['xseo.rules' => ['fixture.class' => HomeXseoRule::class]]);

    $xseo = new XseoManager;
    $xseo->rule('fixture.class', fn () => ['title' => 'From closure']);
    $xseo->create('fixture.class');

    expect($xseo->get('title'))->toBe('From closure');
});

it('throws for a class-string handler that does not implement XseoRule', function () {
    config(['xseo.rules' => ['fixture.invalid' => NotARule::class]]);

    $xseo = new XseoManager;

    expect(fn () => $xseo->create('fixture.invalid'))->toThrow(InvalidArgumentException::class);
});

it('is resolvable via the Xseo facade too', function () {
    config(['xseo.rules' => ['fixture.class' => HomeXseoRule::class]]);

    Xseo::create('fixture.class');

    expect(Xseo::get('title'))->toBe('From class rule');
});

it('create() accepts an inline [Class, method] handler with no registration at all', function () {
    $xseo = new XseoManager;
    $xseo->rule('default', fn () => ['og:type' => 'website']);

    $xseo->create([PageXseoRuleHandler::class, 'meta'], 'inline-slug');

    expect($xseo->get('title'))->toBe('Page: inline-slug');
    expect($xseo->get('og:type'))->toBe('website');
});

it('create() accepts an inline Closure directly, not just via rule()', function () {
    $xseo = new XseoManager;

    $xseo->create(fn ($xseo, string $slug) => ['title' => "Inline: $slug"], 'hello');

    expect($xseo->get('title'))->toBe('Inline: hello');
});

it('create() accepts an inline plain array of static metas', function () {
    $xseo = new XseoManager;

    $xseo->create(['title' => 'Static inline title']);

    expect($xseo->get('title'))->toBe('Static inline title');
});

it('parent() accepts an inline [Class, method] handler and does not mutate state', function () {
    $xseo = new XseoManager;

    $result = $xseo->parent([PageXseoRuleHandler::class, 'meta'], 'about');

    expect($result)->toBe(['title' => 'Page: about']);
    expect($xseo->get()->isEmpty())->toBeTrue();
});

it('ruleRegister() supplies a rule when nothing else registers that name', function () {
    $xseo = new XseoManager;
    $xseo->ruleRegister('fixture.package', fn () => ['title' => 'From package fallback']);

    $xseo->create('fixture.package');

    expect($xseo->get('title'))->toBe('From package fallback');
});

it('config xseo.rules overrides a ruleRegister() registration of the same name', function () {
    config(['xseo.rules' => ['fixture.package' => ['title' => 'From app config']]]);

    $xseo = new XseoManager;
    $xseo->ruleRegister('fixture.package', fn () => ['title' => 'From package fallback']);

    $xseo->create('fixture.package');

    expect($xseo->get('title'))->toBe('From app config');
});

it('an explicit rule() overrides a ruleRegister() registration of the same name', function () {
    $xseo = new XseoManager;
    $xseo->ruleRegister('fixture.package', fn () => ['title' => 'From package fallback']);
    $xseo->rule('fixture.package', fn () => ['title' => 'From explicit rule()']);

    $xseo->create('fixture.package');

    expect($xseo->get('title'))->toBe('From explicit rule()');
});

it('ruleRegister() accepts the same handler shapes as config xseo.rules', function () {
    HomeXseoRule::$constructed = 0;

    $xseo = new XseoManager;
    $xseo->ruleRegister('fixture.package.class', HomeXseoRule::class);
    $xseo->ruleRegister('fixture.package.method', [PageXseoRuleHandler::class, 'meta']);
    $xseo->ruleRegister('fixture.package.static', ['title' => 'Static package title']);

    $xseo->create('fixture.package.class');
    expect($xseo->get('title'))->toBe('From class rule');
    expect(HomeXseoRule::$constructed)->toBe(1);

    $xseo->create('fixture.package.method', 'about');
    expect($xseo->get('title'))->toBe('Page: about');

    $xseo->create('fixture.package.static');
    expect($xseo->get('title'))->toBe('Static package title');
});

it('ruleRegister(\'default\', ...) wins over defaults_class but loses to rule()/config for \'default\'', function () {
    config(['xseo.defaults' => ['og:type' => 'from-defaults-class']]);

    $xseo = new XseoManager;
    $xseo->ruleRegister('default', fn () => ['og:type' => 'from-package-default']);

    $xseo->create([]);

    expect($xseo->get('og:type'))->toBe('from-package-default');
});

it('ruleRegister(\'default\', ...) loses to config xseo.rules default', function () {
    config(['xseo.rules' => ['default' => ['og:type' => 'from-app-config']]]);

    $xseo = new XseoManager;
    $xseo->ruleRegister('default', fn () => ['og:type' => 'from-package-default']);

    $xseo->create([]);

    expect($xseo->get('og:type'))->toBe('from-app-config');
});

it('ruleRegister(\'default\', ...) loses to an explicit rule(\'default\', ...)', function () {
    $xseo = new XseoManager;
    $xseo->ruleRegister('default', fn () => ['og:type' => 'from-package-default']);
    $xseo->rule('default', fn () => ['og:type' => 'from-explicit-rule']);

    $xseo->create([]);

    expect($xseo->get('og:type'))->toBe('from-explicit-rule');
});
