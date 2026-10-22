{{--
    Fallback footer — drawn only when the site has NOT published a "footer"
    template part.

    $menu('footer') asks for any menu by its handle. It returns an empty
    list for a menu the site has not created, so the footer simply shows
    fewer links instead of breaking. That is the pattern for every optional
    piece of chrome: ask for it, and render nothing when it is not there.
--}}
@php
    $starterFooterLinks = $menu('footer');
@endphp
<footer class="starter-footer">
    <div class="starter-footer__inner">
        <a class="starter-brand starter-brand--small" href="{{ $homeUrl }}">{{ $siteName }}</a>

        @if($starterFooterLinks !== [])
            <nav class="starter-footer__nav" aria-label="Footer">
                <ul>
                    @foreach($starterFooterLinks as $link)
                        <li>
                            <a href="{{ $link['url'] }}" @if(!empty($link['target'])) target="{{ $link['target'] }}" rel="noopener noreferrer" @endif>{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <p class="starter-footer__legal">&copy; {{ date('Y') }} {{ $siteName }}</p>
    </div>
</footer>
