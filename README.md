# Laravel Xseo

Generic SEO meta-tags manager for Laravel: title, description, canonical, hreflang alternates, Open Graph, Twitter Card and JSON-LD — driven by simple named rules, not scattered across every view.

## Installation

```bash
composer require ramir1/laravel-xseo
```

For local development against a monorepo (as in this project), require it via a path repository:

```json
"repositories": {
    "0": { "type": "path", "url": "./packages/ramir1/*", "options": { "symlink": true } }
},
"require": {
    "ramir1/laravel-xseo": "@dev"
}
```

The package's service provider is auto-discovered — no manual registration needed.

## Publishing the config

```bash
php artisan vendor:publish --tag=xseo-config
```

This creates `config/xseo.php`, where you point `files` at your own rules file(s) and adjust `copy`/`rules` as needed.

## Rule DSL: `rule()` / `create()` / `parent()`

A **rule** is a named closure (or class — see below) that returns an array of meta key/value pairs:

```php
use Ramir\Xseo\Facades\Xseo;

Xseo::rule('default', fn () => [
    'og:type' => 'website',
    'og:site_name' => config('app.name'),
]);

Xseo::rule('posts.show', function ($xseo, $post) {
    return [
        'title' => $post->title,
        'description' => $post->excerpt,
        'canonical' => route('posts.show', $post->slug),
    ];
});
```

`Xseo::create('posts.show', $post)` runs the `default` rule (if registered) plus the named rule, merges the results (named rule wins on conflicts), applies the `copy` auto-fill (see below), and merges everything into the manager's current metas.

`Xseo::parent('name', ...$params)` calls another named rule directly and returns its **raw array**, without touching the manager's state — useful for one rule to build on top of another:

```php
Xseo::rule('posts.show', function ($xseo, $post) {
    return array_merge($xseo->parent('posts.index'), [
        'title' => $post->title,
    ]);
});
```

## `config('xseo.rules')` — class/array-based rules

As an alternative to registering closures in a file that gets `require`'d on every request, you can map rule names directly in config:

```php
// config/xseo.php
'rules' => [
    'posts.show' => \App\Xseo\PostsShowRule::class,
    'posts.index' => [\App\Xseo\PostsRules::class, 'index'],
    'legal.terms' => ['title' => 'Terms of Service', 'description' => '...'],
],
```

A class-string handler must implement `Ramir\Xseo\Contracts\XseoRule`:

```php
use Ramir\Xseo\Contracts\XseoRule;
use Ramir\Xseo\XseoManager;

class PostsShowRule implements XseoRule
{
    public function handle(XseoManager $xseo, mixed ...$params): array
    {
        [$post] = $params;

        return ['title' => $post->title, 'canonical' => route('posts.show', $post->slug)];
    }
}
```

This is a plain, serializable array of class-strings — fully compatible with `php artisan config:cache`. Unlike `files` (which `require`s the whole rules file, and thus registers every closure in it, the moment the `xseo` service is first resolved in a request), a class or `[Class, 'method']` handler here is only autoloaded and instantiated when its specific rule name is actually used — and only once per request (the resolved instance is memoized).

`Xseo::rule()`-registered closures always take priority over `config('xseo.rules')` entries of the same name.

## Inline rules — no registration at all

`create()`/`parent()` accept a registered name (string), but also a handler directly — the same shapes `config('xseo.rules')` supports, resolved fresh at the call site:

```php
Xseo::create([PostsShowRule::class, 'show'], $post);   // [Class::class, 'method']
Xseo::create(fn ($xseo, $post) => ['title' => $post->title], $post);  // Closure
Xseo::create(['title' => 'Terms of Service']);          // static array
```

Use `config('xseo.rules')` when the same rule name is reused across several call sites, or you want it cacheable via `config:cache` independently of any one controller. Use an inline handler when a rule is specific to a single controller action and there's no reason to name it in config at all.

## Calling a rule from a controller

```php
use App\Xseo\PostsShowRule;
use Ramir\Xseo\Facades\Xseo;

class PageController
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        Xseo::create([PostsShowRule::class, 'show'], $page);

        return view('pages.show', ['page' => $page]);
    }
}
```

Call `Xseo::create($rule, ...$params)` before returning the view — the layout's `Xseo::generate()` call (see below) then has the metas available when it renders.

## Auto-copy (`config('xseo.copy')`)

```php
'copy' => [
    'title' => ['og:title', 'twitter:title'],
    'description' => ['og:description', 'twitter:description'],
    'canonical' => ['og:url'],
],
```

When a rule sets a source key (e.g. `title`) and does **not** also explicitly set one of its target keys (e.g. `og:title`), `create()` copies the value across. Purely optional — set to `[]` to disable.

## Usage in Blade

```blade
<head>
    <title>{{ $title }}</title>
    {!! \Ramir\Xseo\Facades\Xseo::generate() !!}
</head>
```

**Gotcha:** the global `xseo()` helper is only for *reading/writing* metas — `xseo()` (Collection of all metas), `xseo('title')` (single value), `xseo(['title' => '...'])` (set/merge). It does **not** render anything. To output the tags, call `Xseo::generate()` (via the facade or `app(\Ramir\Xseo\XseoManager::class)`) — `xseo()->generate()` will fail, since `xseo()` returns a `Collection`, not the manager. This tripped up the first real integration of this package — worth remembering.

## Running the package's own tests

```bash
composer install
vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT.
