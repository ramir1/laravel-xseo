<?php

declare(strict_types=1);
use Ramir\Xseo\Rules\DefaultRule;

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
    | Default rule fallback values
    |--------------------------------------------------------------------------
    |
    | Static meta key/value pairs used as the 'default' rule's output when
    | neither Xseo::rule('default', ...) nor config('xseo.rules')['default']
    | is registered (see XseoManager::resolveRule()). Read by the shipped
    | Ramir\Xseo\Rules\DefaultRule — see 'defaults_class' below to swap it
    | out. Leave as [] (the default) for no fallback defaults at all, which
    | preserves this package's pre-'defaults' behavior exactly.
    */
    'defaults' => [],

    /*
    |--------------------------------------------------------------------------
    | Default rule fallback class
    |--------------------------------------------------------------------------
    |
    | Class-string implementing Ramir\Xseo\Contracts\XseoRule, resolved via
    | the container and memoized, used as the lowest-priority fallback when
    | resolving the 'default' rule: Xseo::rule('default', ...) wins first,
    | then config('xseo.rules')['default'], then this class. The shipped
    | Ramir\Xseo\Rules\DefaultRule simply returns config('xseo.defaults', []).
    | Point this at your own XseoRule implementation instead for defaults
    | computed at request time (locale, tenant, auth user, ...).
    */
    'defaults_class' => DefaultRule::class,

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
