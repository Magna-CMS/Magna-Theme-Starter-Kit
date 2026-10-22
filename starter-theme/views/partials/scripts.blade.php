{{--
    Starter behaviour — one small, dependency-free script. Everything it does
    is an enhancement: the page reads and navigates fully without it.

      • wires the fallback header's light/dark button (un-hides it, flips
        data-theme on <html>, remembers the choice under "magna-theme")
      • closes the small-screen menu after a link in it is followed

    Lives in a layout partial on purpose. Block views may not carry
    <script>; the layout may.
--}}
<script>
(function () {
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function scheme() {
        var set = root.getAttribute('data-theme');
        if (set === 'light' || set === 'dark') { return set; }
        return media && media.matches ? 'dark' : 'light';
    }

    function sync() {
        var dark = scheme() === 'dark';
        document.querySelectorAll('[data-starter-scheme]').forEach(function (button) {
            button.setAttribute('aria-pressed', dark ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-starter-scheme]').forEach(function (button) {
        button.hidden = false;
        button.addEventListener('click', function () {
            var next = scheme() === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('magna-theme', next); } catch (e) {}
            sync();
        });
    });
    if (media && media.addEventListener) { media.addEventListener('change', sync); }
    sync();

    document.querySelectorAll('.starter-menu a').forEach(function (link) {
        link.addEventListener('click', function () {
            var menu = link.closest('details');
            if (menu) { menu.open = false; }
        });
    });
})();
</script>
