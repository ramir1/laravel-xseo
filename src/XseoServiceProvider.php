<?php

declare(strict_types=1);

namespace Ramir\Xseo;

use Illuminate\Support\ServiceProvider;

class XseoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/xseo.php', 'xseo');

        // Синглтон биндится на класс, 'xseo' — алиас НА него. Обратный порядок
        // (singleton('xseo', ...) + alias(XseoManager::class, 'xseo')) означает,
        // что app('xseo') резолвится как XseoManager::class, для которого нет
        // singleton-биндинга — новый объект на каждый вызов, не кешируется.
        $this->app->singleton(XseoManager::class, fn () => new XseoManager);
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

        // Ленивая загрузка: require rules-файлов происходит только при первом
        // реальном резолве 'xseo' за запрос. Container::resolve() не повторяет
        // resolving-колбэки для уже закешированного синглтона, так что это
        // сработает ровно один раз за запрос, а не на каждом обращении.
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
