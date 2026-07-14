# Laravel Xseo

[English](README.md) · **Русский**

Универсальный менеджер SEO-мета-тегов для Laravel: title, description, canonical, hreflang-альтернативы, Open Graph, Twitter Card и JSON-LD — управляется простыми именованными правилами, а не разбросан по всем вьюхам.

## Установка

```bash
composer require ramir1/laravel-xseo
```

Сервис-провайдер пакета обнаруживается автоматически — вручную регистрировать ничего не нужно.

## Публикация конфига

```bash
php artisan vendor:publish --tag=xseo-config
```

Создаст `config/xseo.php`, где вы указываете `files` для своих файлов с правилами и настраиваете `copy`/`rules` под себя.

## Публикация вьюхи

```bash
php artisan vendor:publish --tag=xseo-views
```

Создаст `resources/views/vendor/xseo/xseo-all.blade.php`, которую механизм резолва вьюх Laravel будет предпочитать копии из пакета — можно свободно кастомизировать, как рендерятся метатеги.

## DSL правил: `rule()` / `create()` / `parent()`

**Правило** — это именованный хендлер: `Closure`, class-string, реализующий `Contracts\XseoRule`, пара `[Class::class, 'method']` или обычный ассоциативный массив статических значений — который возвращает массив пар ключ/значение мета-тегов. `Xseo::rule()` принимает любую из этих форм напрямую:

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

Xseo::rule('posts.index', PostsIndexRule::class); // class-string, резолвится лениво при первом использовании
```

Вызов зарегистрированного правила по имени — обычно из контроллера, перед возвратом вьюхи:

```php
Xseo::create('posts.show', $post);
```

`Xseo::create('posts.show', $post)` запускает правило `default` (если оно зарегистрировано) плюс именованное правило, мержит результаты (именованное правило побеждает при конфликте ключей), применяет авто-заполнение `copy` (см. ниже) и мержит всё это в текущие metas менеджера.

`Xseo::parent('name', ...$params)` вызывает другое именованное правило напрямую и возвращает его **сырой массив**, не трогая состояние менеджера — полезно, когда одно правило строится поверх другого:

```php
Xseo::rule('posts.show', function ($xseo, $post) {
    return array_merge($xseo->parent('posts.index'), [
        'title' => $post->title,
    ]);
});
```

`Xseo::createOnly('name', ...$params)` ведёт себя точно как `create()` — тот же резолв правила, то же авто-заполнение `copy`, тот же мерж в состояние менеджера — но никогда не резолвит и не мержит правило `default` для этого конкретного вызова:

```php
Xseo::createOnly('standalone-embed', $widget);
```

Используйте это для мест вызова, которые не должны наследовать общесайтовые дефолты (например, страница embeddable-виджета, AMP-вариант или API-рендерящийся фрагмент, использующий тот же менеджер).

## `config('xseo.rules')` — правила на основе класса/массива

Как альтернатива регистрации замыканий в файле, который `require`-ится при каждом запросе, можно замапить имена правил прямо в конфиге:

```php
// config/xseo.php
'rules' => [
    'posts.show' => \App\Xseo\PostsShowRule::class,
    'posts.index' => [\App\Xseo\PostsRules::class, 'index'],
    'legal.terms' => ['title' => 'Terms of Service', 'description' => '...'],
],
```

Class-string хендлер должен реализовывать `Ramir\Xseo\Contracts\XseoRule`:

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

Это обычный, сериализуемый массив class-string-ов — полностью совместимый с `php artisan config:cache`. В отличие от `files` (который `require`-ит весь файл с правилами целиком, регистрируя все замыкания в нём, в момент первого резолва сервиса `xseo` за запрос), класс или хендлер `[Class, 'method']` здесь автозагружается и инстанцируется только тогда, когда конкретное имя правила реально используется — и только один раз за запрос (резолвнутый инстанс мемоизируется).

Записи, зарегистрированные через `Xseo::rule()` (любой поддерживаемой формы — Closure, class-string или массив), всегда имеют приоритет над записями `config('xseo.rules')` с тем же именем.

## `ruleRegister()` — фолбэк-правила от пакетов

`Xseo::rule()` и `config('xseo.rules')` оба предполагают, что регистрацией занимается *ваше* приложение. Если вы поставляете переиспользуемый пакет, который хочет предоставить правило "из коробки" — но всё же позволить потребляющему приложению его переопределить — используйте вместо этого `ruleRegister()`:

```php
// внутри сервис-провайдера вашего пакета
Xseo::ruleRegister('blog.index', BlogIndexRule::class);
```

Он принимает те же формы хендлера, что и `config('xseo.rules')` (и те же формы, что принимает сам `Xseo::rule()` — `Closure`, class-string реализующий `XseoRule`, пара `[Class::class, 'method']` или обычный ассоциативный массив статических значений). Разница не в принимаемых формах, а в приоритете: регистрация через `ruleRegister()` используется только как фолбэк — потребляющее приложение может переопределить её, зарегистрировав то же имя через любой из двух вышестоящих по приоритету механизмов:

```php
// config/xseo.php — переопределяет правило 'blog.index' из пакета
'rules' => [
    'blog.index' => \App\Xseo\CustomBlogRule::class,
],
```

Полный порядок резолва для данного имени правила:

1. `Xseo::rule($name, $handler)` — наивысший приоритет (Closure, class-string, `[Class, method]` или статический массив);
2. `config('xseo.rules')[$name]`;
3. `Xseo::ruleRegister($name, $handler)` — фолбэк от пакета;
4. (только для `default`, см. ниже) `config('xseo.defaults_class')`.

## Фолбэк правила `default` (`config('xseo.defaults')` / `defaults_class`)

Если вы вообще не хотите регистрировать правило `default` — ни через `Xseo::rule('default', ...)`, ни через `config('xseo.rules')['default']` — можно вместо этого напрямую задать статические фолбэк-значения:

```php
// config/xseo.php
'defaults' => [
    'og:type' => 'website',
    'og:site_name' => config('app.name'),
],
```

Их читает шипованный пакетом `Ramir\Xseo\Rules\DefaultRule`, подключённый как `config('xseo.defaults_class')`. Порядок резолва для правила `default`:

1. `Xseo::rule('default', $handler)` — побеждает, если зарегистрировано;
2. `config('xseo.rules')['default']` — побеждает, если задано;
3. `Xseo::ruleRegister('default', $handler)` — побеждает, если задано;
4. `config('xseo.defaults_class')` (по умолчанию `Ramir\Xseo\Rules\DefaultRule`, который просто возвращает `config('xseo.defaults', [])`).

Если вам нужны дефолты, вычисляемые во время запроса (текущая локаль, тенант, аутентифицированный пользователь и т. д.) вместо статического массива, укажите в `defaults_class` свою реализацию `XseoRule`:

```php
// config/xseo.php
'defaults_class' => \App\Xseo\ComputedDefaultsRule::class,
```

Если ни один из трёх уровней не задан, `create()` ведёт себя точно так же, как если бы правила `default` не было вовсе — мержится пустой массив, как и до появления этой фичи.

## Инлайн-правила — вообще без регистрации

`create()`/`parent()` принимают зарегистрированное имя (строку), но также и хендлер напрямую — те же формы, что поддерживает `config('xseo.rules')`, резолвящиеся заново прямо в месте вызова:

```php
Xseo::create([PostsShowRule::class, 'show'], $post);   // [Class::class, 'method']
Xseo::create(fn ($xseo, $post) => ['title' => $post->title], $post);  // Closure
Xseo::create(['title' => 'Terms of Service']);          // статический массив
```

Используйте `config('xseo.rules')`, когда одно и то же правило переиспользуется в нескольких местах вызова, или важна совместимость с `config:cache` независимо от конкретного контроллера. Используйте инлайн-хендлер, когда правило специфично для одного действия контроллера и нет смысла давать ему имя в конфиге вообще.

## Вызов правила из контроллера

Если правило было зарегистрировано по имени (через `Xseo::rule()`, `config('xseo.rules')` или `ruleRegister()`), просто вызовите `create()` с этим именем:

```php
use Ramir\Xseo\Facades\Xseo;

class PageController
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        Xseo::create('posts.show', $page);

        return view('pages.show', ['page' => $page]);
    }
}
```

Либо, вообще без регистрации, передайте хендлер `[Class::class, 'method']` напрямую:

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

Вызывайте `Xseo::create($rule, ...$params)` перед возвратом вьюхи — тогда вызов `Xseo::generate()` в layout'е (см. ниже) будет иметь доступ к metas на момент рендера.

## Авто-копирование (`config('xseo.copy')`)

```php
'copy' => [
    'title' => ['og:title', 'twitter:title'],
    'description' => ['og:description', 'twitter:description'],
    'canonical' => ['og:url'],
],
```

Когда правило задаёт "исходный" ключ (например, `title`) и **не** задаёт явно один из его "целевых" ключей (например, `og:title`), `create()` копирует значение туда. Полностью опционально — установите `[]`, чтобы отключить.

## Использование в Blade

```blade
<head>
    {!! \Ramir\Xseo\Facades\Xseo::generate() !!}
</head>
```

**Важный нюанс:** глобальный хелпер `xseo()` предназначен только для *чтения/записи* metas — `xseo()` (Collection всех metas), `xseo('title')` (одно значение), `xseo(['title' => '...'])` (установка/мерж). Он **не** рендерит ничего. Чтобы вывести теги, вызывайте `Xseo::generate()` (через фасад или `app(\Ramir\Xseo\XseoManager::class)`) — `xseo()->generate()` не сработает, потому что `xseo()` возвращает `Collection`, а не менеджер. Именно на этом споткнулась первая реальная интеграция этого пакета — стоит запомнить.

## Исключение тегов из `generate()`

Некоторые IDE (например, PhpStorm) помечают Blade-шаблон как не содержащий тег `<title>`, если в layout в `<head>` есть только `{!! Xseo::generate() !!}` — тег там на самом деле есть, просто он генерируется пакетом, а не написан буквально в шаблоне. Если вы хотите сами написать `<title>` в layout'е и позволить `generate()` отрендерить всё остальное — исключите его:

```blade
<head>
    <title>{{ xseo('title') }}</title>
    {!! \Ramir\Xseo\Facades\Xseo::exclude('title')->generate() !!}
</head>
```

`exclude()` принимает один ключ или массив ключей и поддерживает цепочки вызовов:

```php
Xseo::exclude('title')->generate();
Xseo::exclude(['title', 'og:title'])->generate();
Xseo::exclude('title')->exclude('canonical')->generate(); // накапливается
```

Ключ, которого сейчас нет, молча игнорируется — в любом случае без ошибки. Исключение действует только на этот один вызов `generate()`: оно потребляется и сбрасывается сразу же, как только `generate()` отрабатывает, так что оно никогда не переносится на следующий, не связанный с этим вызов `generate()`, а исключённое значение по-прежнему доступно после этого через `xseo('title')`/`Xseo::get('title')` — `exclude()` влияет только на то, что рендерится, а не на сохранённые данные.

## Запуск собственных тестов пакета

```bash
composer install
vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

Либо через прилагаемый Docker-конфиг, без локального PHP:

```bash
docker compose run --rm php composer install
docker compose run --rm php vendor/bin/pest
docker compose run --rm php vendor/bin/pint --test
docker compose run --rm php vendor/bin/phpstan analyse --memory-limit=512M
```

## Лицензия

MIT.
