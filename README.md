# Laravel Xseo

Generic SEO meta-tags manager for Laravel: title, description, canonical, hreflang alternates, Open Graph, Twitter Card and JSON-LD — driven by simple named rules, not scattered across every view.

## Installation

```bash
composer require ramir1/laravel-xseo
```

The package's service provider is auto-discovered — no manual registration needed.

## Publishing the config

```bash
php artisan vendor:publish --tag=xseo-config
```

This creates `config/xseo.php`, where you point `files` at your own rules file(s) and adjust `copy`/`rules` as needed.

## Publishing the view

```bash
php artisan vendor:publish --tag=xseo-views
```

This creates `resources/views/vendor/xseo/xseo-all.blade.php`, which Laravel's view resolution then prefers over the package's own copy — customize it freely to change how metas render.

## Rule DSL: `rule()` / `create()` / `parent()`

A **rule** is a named handler — a `Closure`, a class-string implementing `Contracts\XseoRule`, a `[Class::class, 'method']` pair, or a plain associative array of static values — that returns an array of meta key/value pairs. `Xseo::rule()` accepts any of these shapes directly:

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

Xseo::rule('posts.index', PostsIndexRule::class); // class-string, resolved lazily on first use
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

`Xseo::createOnly('name', ...$params)` behaves exactly like `create()` — same rule resolution, same `copy` auto-fill, same merge into the manager's state — except it never resolves or merges the `default` rule for that one call:

```php
Xseo::createOnly('standalone-embed', $widget);
```

Use this for call sites that must not inherit site-wide defaults (e.g. an embeddable widget page, an AMP variant, or an API-rendered fragment reusing the same manager).

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

`Xseo::rule()`-registered entries (of any supported handler shape — Closure, class-string, or array) always take priority over `config('xseo.rules')` entries of the same name.

## `ruleRegister()` — package-provided fallback rules

`Xseo::rule()` and `config('xseo.rules')` both assume *your* application is doing the
registering. If you're shipping a reusable package that wants to provide a rule out of the box —
but still let the consuming application override it — use `ruleRegister()` instead:

```php
// inside your package's service provider
Xseo::ruleRegister('blog.index', BlogIndexRule::class);
```

It accepts the same handler shapes as `config('xseo.rules')` (and the same shapes `Xseo::rule()`
itself accepts — a `Closure`, a class-string implementing `XseoRule`, a `[Class::class, 'method']`
pair, or a plain associative array of static values). The difference isn't accepted shapes, it's
priority: a `ruleRegister()` registration is only used as a fallback — the consuming application
can override it by registering the same name through either of the higher-priority mechanisms
above:

```php
// config/xseo.php — overrides the package's 'blog.index' rule
'rules' => [
    'blog.index' => \App\Xseo\CustomBlogRule::class,
],
```

Full resolution order for a given rule name:

1. `Xseo::rule($name, $handler)` — highest priority (Closure, class-string, `[Class, method]`, or static array);
2. `config('xseo.rules')[$name]`;
3. `Xseo::ruleRegister($name, $handler)` — package-provided fallback;
4. (only for `default`, see below) `config('xseo.defaults_class')`.

## Default rule fallback (`config('xseo.defaults')` / `defaults_class`)

If you don't want to register a `default` rule at all — via `Xseo::rule('default', ...)` or `config('xseo.rules')['default']` — you can instead set static fallback values directly:

```php
// config/xseo.php
'defaults' => [
    'og:type' => 'website',
    'og:site_name' => config('app.name'),
],
```

These are read by the package's shipped `Ramir\Xseo\Rules\DefaultRule`, wired in as `config('xseo.defaults_class')`. Resolution order for the `default` rule is:

1. `Xseo::rule('default', $handler)` — wins if registered;
2. `config('xseo.rules')['default']` — wins if set;
3. `Xseo::ruleRegister('default', $handler)` — wins if set;
4. `config('xseo.defaults_class')` (defaults to `Ramir\Xseo\Rules\DefaultRule`, which just returns `config('xseo.defaults', [])`).

If you need defaults computed at request time (current locale, tenant, authenticated user, etc.) instead of a static array, point `defaults_class` at your own `XseoRule` implementation:

```php
// config/xseo.php
'defaults_class' => \App\Xseo\ComputedDefaultsRule::class,
```

If none of the three is set, `create()` behaves exactly as if there were no default rule at all — an empty array is merged in, same as before this feature existed.

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
    {!! \Ramir\Xseo\Facades\Xseo::generate() !!}
</head>
```

**Gotcha:** the global `xseo()` helper is only for *reading/writing* metas — `xseo()` (Collection of all metas), `xseo('title')` (single value), `xseo(['title' => '...'])` (set/merge). It does **not** render anything. To output the tags, call `Xseo::generate()` (via the facade or `app(\Ramir\Xseo\XseoManager::class)`) — `xseo()->generate()` will fail, since `xseo()` returns a `Collection`, not the manager. This tripped up the first real integration of this package — worth remembering.

## Excluding tags from `generate()`

Some IDEs (e.g. PhpStorm) flag a Blade template as missing a `<title>` tag when the layout only
has `{!! Xseo::generate() !!}` in `<head>` — the tag is there, it's just generated by the
package rather than written literally in the template. If you'd rather write `<title>` yourself
in the layout and let `generate()` render everything else, exclude it:

```blade
<head>
    <title>{{ xseo('title') }}</title>
    {!! \Ramir\Xseo\Facades\Xseo::exclude('title')->generate() !!}
</head>
```

`exclude()` accepts a single key or an array of keys, and chains:

```php
Xseo::exclude('title')->generate();
Xseo::exclude(['title', 'og:title'])->generate();
Xseo::exclude('title')->exclude('canonical')->generate(); // accumulates
```

A key that isn't currently set is silently ignored — no error either way. The exclusion only
applies to that one `generate()` call: it's consumed and reset as soon as `generate()` runs, so
it never carries over to a later, unrelated `generate()` call, and the excluded value is still
readable afterwards via `xseo('title')`/`Xseo::get('title')` — `exclude()` only affects what gets
rendered, not the stored meta data.

## Running the package's own tests

```bash
composer install
vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

Or via the included Docker setup, no local PHP required:

```bash
docker compose run --rm php composer install
docker compose run --rm php vendor/bin/pest
docker compose run --rm php vendor/bin/pint --test
docker compose run --rm php vendor/bin/phpstan analyse --memory-limit=512M
```

## License

MIT.
