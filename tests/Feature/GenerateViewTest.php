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
