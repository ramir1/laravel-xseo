<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Rule files
    |--------------------------------------------------------------------------
    |
    | Absolute paths to PHP files that call Xseo::rule(...). Each file is
    | require()'d once during boot(). The package ships with no files here —
    | the consuming application publishes this config and points 'files' at
    | its own rules file(s), e.g. resource_path('xseo/rules.php').
    */
    'files' => [],

    /*
    |--------------------------------------------------------------------------
    | Class/array-based rules
    |--------------------------------------------------------------------------
    |
    | An alternative to Xseo::rule(Closure) registration: name => handler map,
    | where handler is one of:
    |   - a class-string implementing Ramir\Xseo\Contracts\XseoRule, resolved
    |     via the container and memoized on first use;
    |   - a [ClassName::class, 'method'] two-element callable-style array;
    |   - a plain associative array of static meta values (no invocation).
    | Unlike 'files' above (PHP files required unconditionally on first boot
    | of the xseo service), this is a plain serializable array — compatible
    | with `php artisan config:cache`, and each class is only autoloaded and
    | instantiated when its specific rule name is actually used in a request.
    */
    'rules' => [],

    /*
    |--------------------------------------------------------------------------
    | Seed metas
    |--------------------------------------------------------------------------
    |
    | Initial meta values merged into the manager before any rule runs.
    */
    'metas' => [],

    /*
    |--------------------------------------------------------------------------
    | Title divider
    |--------------------------------------------------------------------------
    |
    | Convenience separator exposed as $xseo->divider for apps that want to
    | concatenate title parts inside their own rule closures, e.g.
    | $page->title . config('xseo.divider') . 'My Site'.
    */
    'divider' => ' | ',

    /*
    |--------------------------------------------------------------------------
    | Auto-copy map
    |--------------------------------------------------------------------------
    |
    | When a rule sets a "source" key and does NOT also explicitly set one of
    | its "target" keys, XseoManager::create() copies source -> target. Lets
    | og:title/twitter:title stay in sync with title without repeating
    | yourself in every rule. Purely optional — set to [] to disable.
    */
    'copy' => [
        'title' => ['og:title', 'twitter:title'],
        'description' => ['og:description', 'twitter:description'],
        'canonical' => ['og:url'],
    ],
];
