# 5. The layout

`views/system/layout.blade.php` is the page shell, the `<html>` document every page is drawn
in. That covers builder pages, the 404 page, collection pages and plugin pages such as Magna Blog
posts. It is the only file in a theme that sees whole pages, and the only place your own
JavaScript may live.

Starter's layout is [here](../starter-theme/views/system/layout.blade.php), and is about 60 lines.

## The contract

Every layout must do four things:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle !== '' ? $seoTitle : $title }}</title>

    {{-- 1. Your tokens, with Design-panel overrides and dark values applied. --}}
    @if($tokensCss !== '')
        <style>{!! $tokensCss !!}</style>
    @endif
</head>
<body>

{{-- 2. The site's own header part wins over your fallback chrome. --}}
@if(!empty($headerPartHtml))
    <header>{!! $headerPartHtml !!}</header>
@else
    …your fallback header…
@endif

<main>
    {{-- 3. A plugin page's pre-rendered HTML, OR the page's sections. Never both. --}}
    @if(!empty($mainHtml))
        {!! $mainHtml !!}
    @else
        @include('magna-pages::partials.sections')
    @endif
</main>

@if(!empty($footerPartHtml))
    <footer>{!! $footerPartHtml !!}</footer>
@else
    …your fallback footer…
@endif

{{-- 4. Popups and the cookie-consent banner. --}}
@if(!empty($popupsHtml))
    {!! $popupsHtml !!}
@endif
</body>
</html>
```

Keep the closing `</body>`: the builder injects its canvas script just before it.

`theme-lint` checks all of this, including inside partials your layout includes.

## Variables

| Variable | Type | What it is |
|---|---|---|
| `$title` | string | The page's title as editors wrote it ("Home", "Pricing"). Empty for some plugin pages. |
| `$seoTitle` | string | The page's own **SEO title**, or `''`. When set, use it **as-is**: the author wrote the whole thing, site name included. |
| `$metaDescription` | string | The page's meta description (or one a plugin page supplies), or `''`. |
| `$siteName` | string | The site's name from Site settings (falls back to "Magna"). |
| `$homeUrl` | string | Where your wordmark should link. Usually `/`, but **not always**: when the admin panel owns `/`, it is the home page's own address. Never hard-code `/`. |
| `$headerMenu` | array | The resolved menu with handle **`primary`**, a list of items. `[]` when the site has no such menu. |
| `$menu` | closure | `$menu('footer')` resolves **any** menu by handle. `[]` for a handle the site has not created, so you can ask for optional menus safely. |
| `$pageUrl` | closure | `$pageUrl('pricing')` returns a published page's URL by slug, or `null` for a draft or unknown slug. Use it for CTAs in your chrome instead of hard-coded paths, which break on rename, nesting or a locale prefix. |
| `$tokensCss` | string | Your compiled tokens. Print raw inside `<style>`. `''` when there are none. |
| `$headerPartHtml` | ?string | The site's published header template part, already rendered and wrapped in `div.magna-chrome.magna-chrome--header`. `null`/empty when there is none, or the page chose *no header*. |
| `$footerPartHtml` | ?string | The same, for the footer. |
| `$mainHtml` | ?string | Set for **plugin frontend pages and collections**: the main slot, pre-rendered. Print it **instead of** the sections partial. |
| `$popupsHtml` | ?string | Published popups for this path, plus the consent banner and its scripts. Print just before `</body>`. |
| `$localeAlternates` | array | `locale => URL` for the page's published translations. Print `hreflang` links when there is more than one. |
| `$builderMode` | bool | `true` when the page is drawn inside the builder canvas. |

Variables such as `$tree`, `$registry`, `$resolver`, `$blockViewFor`, `$pageCss`,
`$conditionsPass` and `$resolveBindings` also arrive. They exist for the sections partial, which
inherits them through `@include`. Don't use them yourself.

### Menu items

`$headerMenu` and `$menu('…')` return the same shape, nested to any depth:

```php
[
    'label' => 'About',
    'url' => '/about',
    'target' => '',             // '' or '_blank'
    'rel' => '',
    'title_attr' => '',
    'css_class' => '',
    'description' => '',
    'children' => [ /* items */ ],
]
```

The easiest way to print one is Magna Pages' own recursive partial. It is the same markup the
`nav` block uses, so one set of CSS styles both:

```blade
<nav class="magna-nav" aria-label="Primary">
    @include('magna-pages::partials.nav-items', ['items' => $headerMenu, 'depth' => 0])
</nav>
```

It renders `ul.magna-nav__list.magna-nav__list--depth-N > li.magna-nav__item > a`, plus
`.magna-nav__item--child` and `.magna-nav__description`.

## Fallback chrome and template parts

Editors can design a header and footer in the builder as **template parts**. Which one a page gets
is decided by Magna Pages, in this order:

1. the page's own choice (including an explicit *no header*);
2. the site default set in **Pages → Site settings**;
3. a published part whose slug is literally `header` / `footer`.

When one applies, you receive it as `$headerPartHtml`. When none does, draw your own **fallback
chrome** from `$siteName`, `$homeUrl` and the menus, so a fresh install looks finished. Starter's
[header](../starter-theme/views/partials/header.blade.php) and
[footer](../starter-theme/views/partials/footer.blade.php) partials are exactly that.

Wrap the part HTML *directly* in an element, as above. A part marked **sticky** gets CSS that
sticks both its `.magna-chrome` wrapper and whatever element directly contains it.

## `<head>` and SEO

A solid default, from [Starter's head partial](../starter-theme/views/partials/head.blade.php):

```blade
@php
    $pageTitle = $seoTitle !== ''
        ? $seoTitle
        : ($title !== '' && $title !== $siteName ? $title.' · '.$siteName : $siteName);
@endphp
<title>{{ $pageTitle }}</title>
@if($metaDescription !== '')
    <meta name="description" content="{{ $metaDescription }}">
@endif
@if(count($localeAlternates ?? []) > 1)
    @foreach($localeAlternates as $alternateLocale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ url($alternateUrl) }}">
    @endforeach
@endif
```

If the site runs **Magna SEO** or another plugin that writes meta tags, coordinate with it rather
than duplicating tags.

## JavaScript

Scripts are allowed in the layout and its partials. They are **refused in block views**
([chapter 10](10-rules-and-checks.md)). Keep them small, dependency-free and progressive: the page
should read and navigate without them.

Two scripts most themes want:

```html
<!-- In <head>: apply a stored light/dark choice before first paint. -->
<script>try{var s=localStorage.getItem('magna-theme');if(s==='light'||s==='dark'){document.documentElement.setAttribute('data-theme',s)}}catch(e){}</script>
```

and a scheme toggle for your fallback header. See
[Starter's scripts partial](../starter-theme/views/partials/scripts.blade.php). It shares the
`magna-theme` storage key with the core switch block, so the two always agree.

In the builder canvas (`$builderMode`), the builder swaps single blocks in place as editors type,
without reloading the page. A script that enhances blocks once on load will not see the swapped
ones. Prefer CSS, and skip scroll-jacking, modals or anything that steals focus while
`$builderMode` is true:

```blade
@unless($builderMode ?? false)
    @include('theme::partials.scripts')
@endunless
```

## CSS

Print your stylesheet inline from a partial, after `$tokensCss` so it can read the variables:

```blade
@include('theme::partials.styles')   {{-- <style>…</style> --}}
```

There is no theme asset URL ([chapter 3](03-theme-anatomy.md#there-is-no-assets-folder)), and the
page cache stores the whole document anyway. Keep the stylesheet lean. Starter's styles every
core block in about 35 KB before compression.

## Rules that come from the page cache

Magna Pages stores a guest's page as **one HTML document and serves it to every guest** for up to
an hour. Path-specific output is fine, because the path is the cache key (plus `?page=N` on
paginated listings, while every other query string bypasses the cache). **Visitor-specific**
output is not:

- no `auth()`, `@auth`/`@guest` or user names. The first visitor's version would be served to everyone;
- no `@csrf` or `csrf_token()`. Forms belong to plugins (Magna Forms), which handle them;
- no `session()`, no random values, no "now" clocks that need to be exact.

Light/dark is the model to copy: both readings ship to everyone, and the browser picks.

## Pages your layout does not draw

- **Maintenance mode** (503) uses Magna Pages' own page, not your layout.
- The **404** is themed when the site has a *not-found page* set (Starter's demo content sets one).
  Without one, a bare built-in page is used.

---

Next: **[6. Styling blocks with CSS →](06-styling-blocks.md)**
