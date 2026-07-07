<?php

declare(strict_types=1);

namespace Ramir\Xseo;

use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ramir\Xseo\Contracts\XseoRule;
use Ramir\Xseo\Rules\DefaultRule;

class XseoManager
{
    private Collection $metas;

    /** @var array<string, Closure> */
    protected array $rules = [];

    public string $divider;

    public function __construct()
    {
        $this->metas = collect(config('xseo.metas', []));
        $this->divider = config('xseo.divider', ' | ');
    }

    /**
     * Register a named rule. A rule receives the manager instance plus any
     * extra arguments passed to create()/parent(), and returns an array of
     * meta key => value pairs.
     */
    public function rule(string $name, Closure $callback): void
    {
        $this->rules[$name] = $callback;
    }

    /**
     * Call another rule directly and return its raw array result, WITHOUT
     * merging it into the manager's state. Lets one rule reuse/build on top
     * of another, e.g.:
     *
     *   Xseo::rule('posts.show', function ($xseo, $post) {
     *       return array_merge($xseo->parent('posts.index'), [
     *           'title' => $post->title,
     *       ]);
     *   });
     *
     * $rule accepts the same shapes as create(): a registered name (string),
     * an inline [Class::class, 'method'], or a Closure.
     */
    public function parent(string|array|Closure $rule, mixed ...$params): array
    {
        $resolved = $this->normalizeRule($rule);

        return $resolved ? $resolved($this, ...$params) : [];
    }

    /**
     * Run the 'default' rule (if registered) plus $rule, merge results
     * ($rule wins on conflicts), apply config('xseo.copy') auto-fill, and
     * merge everything into the manager's current metas.
     *
     * $rule can be:
     *   - a registered name (string) — looked up via rule() closures or
     *     config('xseo.rules'), memoized (see resolveRule());
     *   - an inline [Class::class, 'method'] callable-style array, or a
     *     plain associative array of static meta values — resolved fresh
     *     each call, no registration needed;
     *   - a Closure, used as-is.
     */
    public function create(string|array|Closure $rule, mixed ...$params): void
    {
        $default = $this->resolveRule('default');
        $defaults = $default ? $default($this) : [];

        $this->mergeRuleResult($defaults, $rule, ...$params);
    }

    /**
     * Same as create(), but never resolves/merges the 'default' rule for this
     * one call — no Xseo::rule('default', ...), no config('xseo.rules')
     * ['default'], no config('xseo.defaults_class') fallback. Still applies
     * config('xseo.copy') auto-fill and merges into the manager's state, same
     * as create(). Use when a specific call site must NOT inherit the
     * site-wide defaults (e.g. an embeddable/standalone page).
     */
    public function createOnly(string|array|Closure $rule, mixed ...$params): void
    {
        $this->mergeRuleResult([], $rule, ...$params);
    }

    /**
     * Shared body of create()/createOnly(): resolve $rule, merge it on top of
     * $defaults ($rule wins on conflicts), apply config('xseo.copy')
     * auto-fill, and merge the result into the manager's current metas.
     * $defaults is [] for createOnly() and the resolved 'default' rule's
     * output for create().
     */
    private function mergeRuleResult(array $defaults, string|array|Closure $rule, mixed ...$params): void
    {
        $resolved = $this->normalizeRule($rule);
        $items = $resolved ? $resolved($this, ...$params) : [];

        $merged = array_merge($defaults, $items);

        foreach (config('xseo.copy', []) as $source => $targets) {
            if (empty($merged[$source])) {
                continue;
            }

            foreach ((array) $targets as $target) {
                if (empty($merged[$target])) {
                    $merged[$target] = $merged[$source];
                }
            }
        }

        $this->metas = $this->metas->merge($merged);
    }

    /**
     * Find a rule's Closure handler: first among those registered via rule(),
     * then from config('xseo.rules') (built and memoized back into
     * $this->rules). For the 'default' rule specifically, and only that name,
     * one more fallback tier applies below both of those: config
     * ('xseo.defaults_class') (defaulting to the shipped Rules\DefaultRule,
     * which just returns config('xseo.defaults', [])), also memoized. This
     * keeps 'default' resolution non-null in practice (the shipped class
     * always resolves), but its *output* is [] when nothing is configured
     * anywhere, matching the old behavior byte-for-byte. See
     * makeRuleClosure() for the supported handler shapes.
     */
    protected function resolveRule(string $name): ?Closure
    {
        if (isset($this->rules[$name])) {
            return $this->rules[$name];
        }

        // Not config("xseo.rules.$name") — rule names routinely contain dots
        // themselves (e.g. 'pages.show'), which config()'s dot-notation would
        // otherwise misinterpret as a nested path instead of a literal key.
        $handler = config('xseo.rules', [])[$name] ?? null;

        if ($handler !== null) {
            return $this->rules[$name] = $this->makeRuleClosure("xseo.rules.$name", $handler);
        }

        if ($name === 'default') {
            return $this->rules[$name] = $this->makeRuleClosure(
                'xseo.defaults_class',
                config('xseo.defaults_class', DefaultRule::class)
            );
        }

        return null;
    }

    /**
     * Normalizes the $rule argument of create()/parent() into a callable
     * Closure:
     *   - Closure -> used as-is;
     *   - string -> a registered name, resolved via resolveRule() (rule()
     *     closures or config('xseo.rules'), memoized);
     *   - array -> an inline handler, normalized fresh each call via
     *     makeRuleClosure() (no name to memoize against).
     */
    protected function normalizeRule(string|array|Closure $rule): ?Closure
    {
        if ($rule instanceof Closure) {
            return $rule;
        }

        if (is_string($rule)) {
            return $this->resolveRule($rule);
        }

        return $this->makeRuleClosure('Xseo::create()/parent() inline rule', $rule);
    }

    /**
     * Normalizes a handler (from config('xseo.rules') or an inline call) into
     * a Closure with the same signature as a rule() callback. Supported
     * shapes:
     *   - a class-string implementing XseoRule, resolved via the container
     *     once and memoized (constructor DI is supported);
     *   - a [ClassName::class, 'method'] two-element callable-style array;
     *   - a plain associative array of static meta values, returned as-is.
     */
    protected function makeRuleClosure(string $context, mixed $handler): Closure
    {
        if (is_string($handler)) {
            if (! is_a($handler, XseoRule::class, true)) {
                throw new InvalidArgumentException(
                    "$context: class $handler must implement ".XseoRule::class
                );
            }

            $instance = app($handler);

            return fn (XseoManager $xseo, mixed ...$params) => $instance->handle($xseo, ...$params);
        }

        if (is_array($handler) && array_is_list($handler) && count($handler) === 2
            && is_string($handler[0]) && is_string($handler[1])) {
            [$class, $method] = $handler;
            $instance = app($class);

            return fn (XseoManager $xseo, mixed ...$params) => $instance->{$method}($xseo, ...$params);
        }

        if (is_array($handler)) {
            return fn (): array => $handler;
        }

        throw new InvalidArgumentException("$context: unsupported handler type.");
    }

    public function get(string|false $key = false): Collection|string|null
    {
        if ($key) {
            return $this->metas[$key] ?? null;
        }

        return $this->metas;
    }

    public function set(array $meta = []): Collection
    {
        return $this->metas = $this->metas->merge($meta);
    }

    public function generate(): string
    {
        return view('xseo::xseo-all', ['metas' => $this->metas])->render();
    }
}
