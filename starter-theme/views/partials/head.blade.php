{{--
    <head> metadata: title, description, language alternates and the
    pre-paint colour-scheme script.

    $seoTitle is the page's own "SEO title" and is used as-is when set (the
    author wrote the whole thing). Otherwise the page title gets the site
    name appended, except on a page that is already called the site name.
--}}
@php
    $starterTitle = $seoTitle !== ''
        ? $seoTitle
        : ($title !== '' && $title !== $siteName ? $title.' · '.$siteName : $siteName);
@endphp
<title>{{ $starterTitle }}</title>
@if($metaDescription !== '')
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:description" content="{{ $metaDescription }}">
@endif
<meta property="og:title" content="{{ $starterTitle }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="website">
<meta name="color-scheme" content="light dark">

{{-- hreflang links, only when the page really has translations. --}}
@if(count($localeAlternates ?? []) > 1)
    @foreach($localeAlternates as $alternateLocale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url($alternateUrl) }}">
    @endforeach
@endif

{{--
    Apply a visitor's stored light/dark choice before the first paint, so a
    reader who picked dark never sees the light palette flash. The key
    "magna-theme" is the one the core scheme-toggle block uses, so this
    theme's header button and that block always agree.

    Scripts are allowed in the layout and its partials. They are NOT allowed
    in views/blocks/ — magna:theme:check refuses them there.
--}}
<script>try{var s=localStorage.getItem('magna-theme');if(s==='light'||s==='dark'){document.documentElement.setAttribute('data-theme',s)}}catch(e){}</script>
