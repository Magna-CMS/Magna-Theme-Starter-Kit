{{--
    Fallback header — drawn only when the site has NOT published a "header"
    template part. It gives a fresh install a finished look: the site name,
    the "primary" menu, a light/dark switch and a small-screen menu.

    $headerMenu is the resolved "primary" menu (a list of links, each with
    label, url, target and children). It is an empty list when the site has
    no such menu, so every use is guarded.

    The small-screen menu is a <details> element: it opens and closes with
    no JavaScript at all.
--}}
<header class="starter-header">
    <div class="starter-header__bar">
        <a class="starter-brand" href="{{ $homeUrl }}">{{ $siteName }}</a>

        @if($headerMenu !== [])
            <nav class="starter-nav magna-nav" aria-label="Primary">
                @include('magna-pages::partials.nav-items', ['items' => $headerMenu, 'depth' => 0])
            </nav>
        @endif

        <div class="starter-header__actions">
            {{--
                Shown only when the palette actually has a dark reading the
                visitor may switch to. $tokensCss carries a
                :root[data-theme="dark"] rule exactly when the theme declares
                dark values AND the site follows the visitor's preference —
                a site pinned to light or dark gets no switch.
            --}}
            @if(str_contains($tokensCss, 'data-theme="dark"'))
                <button type="button" class="starter-icon-button" data-starter-scheme aria-label="Switch between light and dark" aria-pressed="false" hidden>
                    <svg class="starter-icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    <svg class="starter-icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>
            @endif

            @if($headerMenu !== [])
                <details class="starter-menu">
                    <summary class="starter-icon-button">
                        <span class="starter-visually-hidden">Menu</span>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </summary>
                    <nav class="starter-menu__panel magna-nav" aria-label="Primary">
                        @include('magna-pages::partials.nav-items', ['items' => $headerMenu, 'depth' => 0])
                    </nav>
                </details>
            @endif
        </div>
    </div>
</header>
