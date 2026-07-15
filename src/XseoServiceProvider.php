<?php

declare(strict_types=1);

namespace Ramir\Xseo;

use Illuminate\Support\ServiceProvider;

class XseoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/xseo.php', 'xseo');

        // The singleton is bound on the class, 'xseo' is an alias TO it. The
        // reverse order (singleton('xseo', ...) + alias(XseoManager::class,
        // 'xseo')) would mean app('xseo') resolves fine but XseoManager::class
        // itself has no singleton binding — a fresh instance on every call,
        // never cached.
        $this->app->scoped(XseoManager::class, fn () => new XseoManager);
        $this->app->alias(XseoManager::class, 'xseo');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'xseo');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/xseo.php' => config_path('xseo.php'),
            ], 'xseo-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/xseo'),
            ], 'xseo-views');
        }

        // Lazy loading: rule files are require()'d only on the first actual
        // resolution of 'xseo' per request. Container::resolve() doesn't
        // repeat resolving callbacks for an already-cached singleton, so this
        // fires exactly once per request, not on every access.
        $this->app->resolving('xseo', fn () => $this->loadRules());
    }

    protected function loadRules(): void
    {
        foreach ((array) config('xseo.files', []) as $file) {
            if (is_string($file) && file_exists($file)) {
                require $file;
            }
        }
    }
}
