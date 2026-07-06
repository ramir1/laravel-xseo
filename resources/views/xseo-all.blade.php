@if(isset($metas['amphtml'] ))
    <link rel="amphtml" href="{{ $metas['amphtml'] }}"/>
@endisset
@if(isset($metas['robots']))
    <meta name="robots" content="{{ $metas['robots'] }}">
@endisset
@if(isset($metas['title']))
    <title>{{$metas['title']}}</title>
@endif
@if(isset($metas['description']))
    <meta name="description" content="{{$metas['description']}}">
@endisset
@if(isset($metas['canonical']))
    <link rel="canonical" href="{{$metas['canonical']}}">
@endisset
@if(isset($metas['alternates']))
    @foreach($metas['alternates'] as $lang=>$url)
        @if($lang === App::getLocale())
            @continue;
        @endif
        <link rel="alternate" hreflang="{{$lang}}" href="{{ $url }}"/>
    @endforeach
@endisset
@if(isset($metas['og:image']))
    <meta property="og:image" content="{{ $metas['og:image'] }}"/>
@endisset
@if(isset($metas['og:image:type']))
    <meta property="og:image:type" content="{{ $metas['og:image:type'] }}"/>
@endisset
@if(isset($metas['og:type']))
    <meta property="og:type" content="{{ $metas['og:type'] }}"/>
@endisset
@if(isset($metas['og:title']))
    <meta property="og:title" content="{{ $metas['og:title'] }}"/>
@endisset
@if(isset($metas['og:description']))
    <meta property="og:description" content="{{ $metas['og:description'] }}"/>
@endisset
@if(isset($metas['og:url']))
    <meta property="og:url" content="{{ $metas['og:url'] }}"/>
@endisset
@if(isset($metas['og:site_name']))
    <meta property="og:site_name" content="{{ $metas['og:site_name'] }}"/>
@endisset
@if(isset($metas['twitter:card']))
    <meta property="twitter:card" content="{{ $metas['twitter:card'] }}"/>
@endisset
@if(isset($metas['twitter:title']))
    <meta property="twitter:title" content="{{ $metas['twitter:title'] }}"/>
@endisset
@if(isset($metas['twitter:description']))
    <meta property="twitter:description" content="{{ $metas['twitter:description'] }}"/>
@endisset
@if(isset($metas['twitter:image']))
    <meta property="twitter:image" content="{{ $metas['twitter:image'] }}"/>
@endisset
@if(isset($metas['schema']))
    @foreach($metas['schema'] as $schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
    @endforeach
@endisset

