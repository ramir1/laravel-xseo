<?php

declare(strict_types=1);

use Ramir\Xseo\Facades\Xseo;

it('renders title, description and canonical tags', function () {
    Xseo::set([
        'title' => 'Example Title',
        'description' => 'Example description',
        'canonical' => 'https://example.test/',
    ]);

    $html = Xseo::generate();

    expect($html)
        ->toContain('<title>Example Title</title>')
        ->toContain('<meta name="description" content="Example description">')
        ->toContain('<link rel="canonical" href="https://example.test/">');
});

it('renders alternate hreflang links but skips the current locale', function () {
    app()->setLocale('en');

    Xseo::set([
        'alternates' => [
            'en' => 'https://example.test/en',
            'ru' => 'https://example.test/ru',
        ],
    ]);

    $html = Xseo::generate();

    expect($html)
        ->not->toContain('hreflang="en"')
        ->toContain('hreflang="ru" href="https://example.test/ru"');
});

it('renders nothing when no metas were ever set', function () {
    expect(trim(Xseo::generate()))->toBe('');
});

it('exclude() omits a single meta key from generate()', function () {
    Xseo::set([
        'title' => 'Example Title',
        'description' => 'Example description',
    ]);

    $html = Xseo::exclude('title')->generate();

    expect($html)
        ->not->toContain('<title>')
        ->toContain('<meta name="description" content="Example description">');
});

it('exclude() accepts an array of keys', function () {
    Xseo::set([
        'title' => 'Example Title',
        'description' => 'Example description',
        'canonical' => 'https://example.test/',
    ]);

    $html = Xseo::exclude(['title', 'canonical'])->generate();

    expect($html)
        ->not->toContain('<title>')
        ->not->toContain('<link rel="canonical"')
        ->toContain('<meta name="description" content="Example description">');
});

it('exclude() silently ignores keys that are not set', function () {
    Xseo::set(['title' => 'Example Title']);

    $html = Xseo::exclude('does-not-exist')->generate();

    expect($html)->toContain('<title>Example Title</title>');
});

it('exclude() only affects the next generate() call, not later ones', function () {
    Xseo::set(['title' => 'Example Title']);

    $excluded = Xseo::exclude('title')->generate();
    $normal = Xseo::generate();

    expect($excluded)->not->toContain('<title>');
    expect($normal)->toContain('<title>Example Title</title>');
});

it('chained exclude() calls accumulate', function () {
    Xseo::set([
        'title' => 'Example Title',
        'canonical' => 'https://example.test/',
    ]);

    $html = Xseo::exclude('title')->exclude('canonical')->generate();

    expect($html)
        ->not->toContain('<title>')
        ->not->toContain('<link rel="canonical"');
});

it('exclude() only affects rendering, not the stored meta data', function () {
    Xseo::set(['title' => 'Example Title']);

    Xseo::exclude('title')->generate();

    expect(Xseo::get('title'))->toBe('Example Title');
});

it('escapes </script> inside JSON-LD schema data so it cannot break out of the script tag', function () {
    Xseo::set([
        'schema' => [
            [
                '@type' => 'Review',
                'reviewBody' => '</script><script>alert(1)</script>',
            ],
        ],
    ]);

    $html = Xseo::generate();

    expect($html)
        ->not->toContain('</script><script>alert(1)</script>')
        ->toContain('<\/script>');
});
