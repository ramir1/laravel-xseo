<?php

declare(strict_types=1);

/**
 * Example rules file — illustrates Xseo::rule() / create() / parent() and
 * the config('xseo.copy') auto-fill feature. This file is NOT auto-loaded
 * by the package; copy the relevant pieces into your own app's rules file
 * referenced by config('xseo.files').
 */

use Ramir\Xseo\Facades\Xseo;

// Site-wide fallback, merged into every Xseo::create() call.
Xseo::rule('default', function () {
    return [
        'og:type' => 'website',
        'og:site_name' => config('app.name'),
        'twitter:card' => 'summary_large_image',
    ];
});

// Generic "home" page.
Xseo::rule('home', function () {
    return [
        'title' => 'Example Site — Home',
        'description' => 'A short, unique description of what this site does.',
        'canonical' => url('/'),
    ];
});

// Generic "post" page, reusing 'home' via parent() and overriding fields.
// Demonstrates: parent() reuse + config('xseo.copy') auto-filling
// og:title/twitter:title from 'title' below without repeating them here.
Xseo::rule('post.show', function ($xseo, $post) {
    return array_merge($xseo->parent('home'), [
        'title' => $post->title,
        'description' => $post->excerpt,
        'canonical' => route('posts.show', ['slug' => $post->slug]),
        'og:image' => $post->image_url ?? null,
    ]);
});
