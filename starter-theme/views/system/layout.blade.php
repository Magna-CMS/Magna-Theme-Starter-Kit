{{--
    Starter — the page shell.

    Every page Magna Pages serves is drawn through this file: builder pages,
    plugin pages (which arrive pre-rendered in $mainHtml), collection pages
    and the not-found page. Replacing it is optional; without it a theme
    renders inside the plugin's bare fallback shell.

    The full list of variables this view receives is in
    docs/05-layout.md. The rules every layout must keep:

      1. Print $tokensCss inside a <style> in <head> — it is your tokens.json
         compiled to CSS variables, with the site's Design-tab overrides and
         dark values already applied.
      2. Print $mainHtml when it is set, otherwise include the sections
         partial. Never both.
      3. Prefer $headerPartHtml / $footerPartHtml (header and footer the site
         designed in the builder) over your own fallback chrome.
      4. Print $popupsHtml just before </body> (popups, consent banner).

    Views receive data; they never query. Everything below reads variables
    the renderer passed in — no models, no facades, no container lookups.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('theme::partials.head')

    @if($tokensCss !== '')
        <style>{!! $tokensCss !!}</style>
    @endif
    @include('theme::partials.styles')
</head>
<body class="starter-page{{ !empty($mainHtml) ? ' starter-page--app' : '' }}">
<a class="starter-skip" href="#starter-main">Skip to content</a>

@if(!empty($headerPartHtml))
    {{-- The site published a "header" template part: it wins. --}}
    <header class="starter-header starter-header--part">{!! $headerPartHtml !!}</header>
@else
    @include('theme::partials.header')
@endif

<main id="starter-main" class="starter-main">
    @if(!empty($mainHtml))
        {{-- A plugin frontend page (a blog post, an account screen…). --}}
        {!! $mainHtml !!}
    @else
        @include('magna-pages::partials.sections')
    @endif
</main>

@if(!empty($footerPartHtml))
    <footer class="starter-footer starter-footer--part">{!! $footerPartHtml !!}</footer>
@else
    @include('theme::partials.footer')
@endif

@if(!empty($popupsHtml))
    {!! $popupsHtml !!}
@endif

@include('theme::partials.scripts')
</body>
</html>
