{{--
    Starter stylesheet — inlined into every page.

    Why inline? Magna serves no files from a theme folder, and the page cache
    stores each page's full HTML anyway, so one <style> element is the
    simplest thing that works everywhere. (Fonts and images you host
    elsewhere can still be linked from the layout's <head>.)

    Every colour, font and size below reads a design token from tokens.json:

        colors.primary      → var(--color-primary)
        layout.maxWidth     → var(--max-width)
        typography.fontBody → var(--font-body)

    That is what lets the Design panel, scheduled designs, per-section token
    overrides and light/dark all work without anyone touching this file.
    Tokens cannot hold calc()/clamp()/rgb() — any value containing "(" is
    refused — so the arithmetic lives here, in the CSS, built FROM tokens.

    Map:
      1. Base & typography        5. Core blocks: marketing
      2. Shell: header & footer   6. Core blocks: media & dynamic
      3. Page structure & bands   7. Magna Pages blocks, chrome & plugins
      4. Core blocks: content     8. Utilities & motion

    Selector rule: core block views put their classes on a WRAPPER element.
    Write `.magna-heading h2`, never `h2.magna-heading` — the second matches
    nothing and nothing warns you.
--}}
<style>
/* ───────────────────────── 1. Base & typography ───────────────────────── */
:root {
    color-scheme: light;
    --starter-shadow: 0 1px 2px rgb(16 24 40 / .06), 0 8px 24px -12px rgb(16 24 40 / .18);
    --starter-ring: 0 0 0 3px color-mix(in srgb, var(--color-primary) 35%, transparent);
}
/* The dark reading. Same three-state logic Magna uses for the tokens:
   system preference unless the visitor chose light, or an explicit dark. */
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) { color-scheme: dark; --starter-shadow: 0 1px 2px rgb(0 0 0 / .4), 0 8px 24px -12px rgb(0 0 0 / .6); }
}
:root[data-theme="dark"] { color-scheme: dark; --starter-shadow: 0 1px 2px rgb(0 0 0 / .4), 0 8px 24px -12px rgb(0 0 0 / .6); }

*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; scroll-padding-top: 5rem; }
body {
    margin: 0;
    background: var(--color-bg);
    color: var(--color-text);
    font-family: var(--font-body);
    font-size: var(--font-size-base);
    line-height: var(--line-height);
    -webkit-font-smoothing: antialiased;
}
h1, h2, h3, h4, h5, h6 {
    margin: 0 0 .5em;
    color: var(--color-heading);
    font-family: var(--font-heading);
    font-weight: var(--heading-weight);
    line-height: 1.2;
    letter-spacing: -.015em;
}
h1 { font-size: clamp(2.25rem, 1.6rem + 2.6vw, 3.5rem); letter-spacing: -.025em; }
h2 { font-size: clamp(1.75rem, 1.4rem + 1.4vw, 2.5rem); }
h3 { font-size: 1.375rem; }
h4 { font-size: 1.125rem; }
h5, h6 { font-size: 1rem; }
p { margin: 0 0 1em; }
a { color: var(--color-primary); text-underline-offset: .2em; }
a:hover { color: var(--color-primary-strong); }
img, video, iframe { max-width: 100%; height: auto; }
hr { border: 0; border-top: 1px solid var(--color-border); margin: 2rem 0; }
code, kbd, pre { font-family: var(--font-mono); font-size: .9em; }
:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
::selection { background: color-mix(in srgb, var(--color-primary) 25%, transparent); }

.starter-visually-hidden {
    position: absolute; width: 1px; height: 1px; overflow: hidden;
    clip-path: inset(50%); white-space: nowrap;
}
.starter-skip {
    position: absolute; left: 1rem; top: -4rem; z-index: 100;
    padding: .6rem 1rem; border-radius: var(--radius);
    background: var(--color-primary); color: var(--color-on-primary); text-decoration: none;
}
.starter-skip:focus { top: 1rem; }

/* ───────────────────────── 2. Shell: header & footer ───────────────────────── */
.starter-header {
    position: sticky; top: 0; z-index: 40;
    background: color-mix(in srgb, var(--color-bg) 88%, transparent);
    backdrop-filter: saturate(1.6) blur(10px);
    border-bottom: 1px solid var(--color-border);
}
/* A published header part brings its own layout; the theme only supplies
   the surface it sits on. */
.starter-header--part { position: static; }
.starter-header__bar {
    display: flex; align-items: center; gap: 1.5rem;
    max-width: var(--max-width); margin-inline: auto;
    padding: .85rem var(--gutter);
}
.starter-brand {
    color: var(--color-heading); font-weight: 700; font-size: 1.125rem;
    letter-spacing: -.01em; text-decoration: none; white-space: nowrap;
}
.starter-brand:hover { color: var(--color-heading); }
.starter-brand--small { font-size: 1rem; }

/* Menus come from Pages' nav-items partial: ul.magna-nav__list > li > a,
   recursing for children. The same markup serves the nav block. */
.starter-nav { margin-left: auto; }
.starter-nav .magna-nav__list { display: flex; gap: .25rem; margin: 0; padding: 0; list-style: none; }
.starter-nav .magna-nav__item { position: relative; }
.starter-nav .magna-nav__item > a {
    display: block; padding: .45rem .75rem; border-radius: var(--radius);
    color: var(--color-text); text-decoration: none; font-weight: 500;
}
.starter-nav .magna-nav__item > a:hover { background: var(--color-surface); color: var(--color-heading); }
.starter-nav .magna-nav__list--depth-1 {
    position: absolute; top: 100%; left: 0; min-width: 12rem; z-index: 10;
    display: none; flex-direction: column; padding: .4rem;
    background: var(--color-bg); border: 1px solid var(--color-border);
    border-radius: var(--radius); box-shadow: var(--starter-shadow);
}
.starter-nav .magna-nav__item:hover > .magna-nav__list--depth-1,
.starter-nav .magna-nav__item:focus-within > .magna-nav__list--depth-1 { display: flex; }
.magna-nav__description { display: none; }

.starter-header__actions { display: flex; align-items: center; gap: .25rem; margin-left: auto; }
.starter-nav + .starter-header__actions { margin-left: 0; }
.starter-icon-button {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.5rem; height: 2.5rem; padding: 0; border: 0; border-radius: 999px;
    background: transparent; color: var(--color-text); cursor: pointer; list-style: none;
}
.starter-icon-button::-webkit-details-marker { display: none; }
.starter-icon-button:hover { background: var(--color-surface); }
.starter-icon-button[hidden] { display: none; }

/* Light/dark icon swap, driven by the same three states as the tokens. */
.starter-icon-sun { display: none; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .starter-icon-sun { display: block; }
    :root:not([data-theme="light"]) .starter-icon-moon { display: none; }
}
:root[data-theme="dark"] .starter-icon-sun { display: block; }
:root[data-theme="dark"] .starter-icon-moon { display: none; }

.starter-menu { position: relative; display: none; }
.starter-menu__panel {
    position: absolute; right: 0; top: calc(100% + .5rem); width: min(18rem, 85vw);
    padding: .5rem; background: var(--color-bg);
    border: 1px solid var(--color-border); border-radius: var(--radius-large);
    box-shadow: var(--starter-shadow);
}
.starter-menu__panel .magna-nav__list { margin: 0; padding: 0; list-style: none; }
.starter-menu__panel .magna-nav__list--depth-1 { padding-left: 1rem; }
.starter-menu__panel a {
    display: block; padding: .6rem .75rem; border-radius: var(--radius);
    color: var(--color-text); text-decoration: none;
}
.starter-menu__panel a:hover { background: var(--color-surface); }
@media (max-width: 767.98px) {
    .starter-nav { display: none; }
    .starter-menu { display: block; }
    /* (0,2,0) so it outbids `.starter-nav + .starter-header__actions`. */
    .starter-header__bar > .starter-header__actions { margin-left: auto; }
}

.starter-main { min-height: 60vh; }

.starter-footer { border-top: 1px solid var(--color-border); background: var(--color-surface); }
.starter-footer__inner {
    display: flex; flex-wrap: wrap; align-items: center; gap: 1rem 2rem;
    max-width: var(--max-width); margin-inline: auto; padding: 2.5rem var(--gutter);
}
.starter-footer__nav ul { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; margin: 0; padding: 0; list-style: none; }
.starter-footer__nav a { color: var(--color-muted); text-decoration: none; }
.starter-footer__nav a:hover { color: var(--color-heading); }
.starter-footer__legal { margin: 0 0 0 auto; color: var(--color-muted); font-size: .9rem; }
.starter-footer--part { padding: 0; }

/* ───────────────────────── 3. Page structure & bands ─────────────────────────
   Every page is:  section.magna-section > .magna-section__inner
                     > .magna-columns > .magna-column (inline flex: N N 0%)
   A section's CSS class (Builder → Advanced → CSS class) is free text, so the
   theme publishes a vocabulary editors can type. Starter's bands:

     starter-band--muted    surface background
     starter-band--brand    brand-colour background, light text
     starter-band--center   centred text and actions
     starter-band--narrow   content limited to a comfortable reading width
     starter-band--tight    half the usual vertical space

   Combine them: "starter-band--muted starter-band--center".
------------------------------------------------------------------------------ */
.magna-section { position: relative; }
.magna-section__inner {
    max-width: var(--max-width); margin-inline: auto;
    padding: calc(var(--section-space) / 2) var(--gutter);
}
.magna-section:first-of-type .magna-section__inner { padding-top: var(--section-space); }
.magna-section:last-of-type .magna-section__inner { padding-bottom: var(--section-space); }
.magna-columns { display: flex; flex-wrap: wrap; gap: 2rem; }
.magna-column { min-width: 0; }
.magna-column > * + * { margin-top: 1.25rem; }
@media (max-width: 767.98px) {
    /* Stack columns on phones. !important because each column carries its
       width as an inline style; a row that stores grid tracks is unaffected. */
    .magna-columns { flex-direction: column; }
    .magna-column { flex: 1 1 100% !important; }
}

.starter-band--muted { background: var(--color-surface); }
.starter-band--brand { background: var(--color-primary); color: var(--color-on-primary); }
.starter-band--brand :is(h1, h2, h3, h4, h5, h6, a) { color: inherit; }
.starter-band--brand .magna-btn--primary { background: var(--color-on-primary); color: var(--color-primary); border-color: var(--color-on-primary); }
.starter-band--brand .magna-btn--outline { color: var(--color-on-primary); border-color: currentColor; }
.starter-band--center { text-align: center; }
.starter-band--center :is(.magna-cta__actions, .magna-hero__ctas, .magna-block--button) { justify-content: center; }
.starter-band--narrow .magna-section__inner { max-width: calc(var(--reading-width) + 2 * var(--gutter)); }
.starter-band--tight .magna-section__inner { padding-block: calc(var(--section-space) / 4); }
.magna-section.starter-band--muted .magna-section__inner,
.magna-section.starter-band--brand .magna-section__inner { padding-block: calc(var(--section-space) * .75); }

/* Header/footer parts are wrapped by Pages in .magna-chrome; their sections
   should not carry a full page section's vertical rhythm. Declared after the
   section rules above so it wins at equal specificity. */
.magna-chrome .magna-section .magna-section__inner { padding-block: 1rem; }
.magna-chrome--header .magna-columns { align-items: center; }
.magna-chrome--header .magna-column:last-child { display: flex; justify-content: flex-end; }
.magna-chrome--header .magna-block--nav .magna-nav__list { justify-content: flex-end; }
.magna-chrome--footer .magna-section .magna-section__inner { padding-block: 2.5rem; }
.magna-chrome--footer .magna-heading :is(h2, h3, h4) { font-size: .8rem; letter-spacing: .08em; text-transform: uppercase; color: var(--color-muted); }
@media (max-width: 767.98px) {
    /* Logo and switch share the first line; the menu wraps below them. */
    .magna-chrome--header .magna-columns { flex-direction: row; flex-wrap: wrap; gap: .5rem 1rem; }
    .magna-chrome--header .magna-column { flex: 1 1 auto !important; }
    .magna-chrome--header .magna-column:has(.magna-block--nav) { order: 3; flex-basis: 100% !important; }
    .magna-chrome--header .magna-block--nav .magna-nav__list { justify-content: flex-start; }
}

/* ───────────────────────── 4. Core blocks: content ───────────────────────── */
.magna-heading--center { text-align: center; }
.magna-heading--right { text-align: right; }
.magna-heading :is(h1, h2, h3, h4, h5, h6) { margin: 0; }

/* Rich text (the text block). Its body is sanitized server-side. */
.magna-prose { max-width: var(--reading-width); }
:where(.starter-band--center) .magna-prose { margin-inline: auto; }
.magna-prose > :first-child { margin-top: 0; }
.magna-prose > :last-child { margin-bottom: 0; }
.magna-prose :is(h2, h3, h4) { margin-top: 1.6em; }
.magna-prose :is(ul, ol) { padding-left: 1.4em; }
.magna-prose li + li { margin-top: .3em; }
.magna-prose blockquote {
    margin: 1.5em 0; padding: .25em 0 .25em 1.25em;
    border-left: 3px solid var(--color-primary); color: var(--color-muted);
}
.magna-prose :not(pre) > code { padding: .1em .35em; border-radius: 6px; background: var(--color-surface); }
.magna-prose table { width: 100%; border-collapse: collapse; }
.magna-prose :is(th, td) { padding: .5em .75em; border-bottom: 1px solid var(--color-border); text-align: left; }
.magna-prose img { border-radius: var(--radius); }

/* Buttons — used by the button, hero, cta and pricing blocks. */
.magna-block--button { display: flex; }
.magna-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    padding: .7rem 1.25rem; border: 1px solid transparent; border-radius: var(--radius);
    font: inherit; font-weight: 600; line-height: 1.2; text-decoration: none;
    cursor: pointer; transition: background-color .15s, color .15s, border-color .15s, box-shadow .15s;
}
.magna-btn:focus-visible { outline: none; box-shadow: var(--starter-ring); }
.magna-btn--primary { background: var(--color-primary); color: var(--color-on-primary); }
.magna-btn--primary:hover { background: var(--color-primary-strong); color: var(--color-on-primary); }
.magna-btn--secondary { background: var(--color-surface); color: var(--color-heading); border-color: var(--color-border); }
.magna-btn--secondary:hover { border-color: var(--color-muted); color: var(--color-heading); }
.magna-btn--outline { background: transparent; color: var(--color-primary); border-color: currentColor; }
.magna-btn--outline:hover { background: color-mix(in srgb, var(--color-primary) 8%, transparent); }
.magna-btn--ghost { background: transparent; color: var(--color-primary); }
.magna-btn--ghost:hover { background: var(--color-surface); }
.magna-btn--sm { padding: .45rem .85rem; font-size: .875rem; }
.magna-btn--lg { padding: .9rem 1.6rem; font-size: 1.0625rem; }

.magna-quote { margin-inline: 0; margin-bottom: 0; padding: 1.5rem 1.75rem; border-left: 4px solid var(--color-primary); background: var(--color-surface); border-radius: 0 var(--radius) var(--radius) 0; }
.magna-quote__text { margin: 0; font-size: 1.25rem; line-height: 1.5; color: var(--color-heading); }
.magna-quote__attribution { margin-top: .75rem; color: var(--color-muted); font-size: .95rem; }
.magna-quote__name { font-weight: 600; color: var(--color-text); }
.magna-quote__name + .magna-quote__source::before { content: " · "; }

/* Callout — Starter overrides this block's view (views/blocks/callout.blade.php)
   to add an icon; these rules style both that markup and core's. */
.magna-callout {
    --tone: var(--color-info);
    display: grid; grid-template-columns: auto 1fr; gap: .25rem .85rem;
    padding: 1rem 1.25rem; border-radius: var(--radius);
    border: 1px solid color-mix(in srgb, var(--tone) 35%, transparent);
    background: color-mix(in srgb, var(--tone) 8%, var(--color-bg));
}
.magna-callout--success { --tone: var(--color-success); }
.magna-callout--warning { --tone: var(--color-warning); }
.magna-callout--danger { --tone: var(--color-danger); }
.magna-callout > :not(.starter-callout__icon) { grid-column: 2; }
.starter-callout__icon { grid-row: span 2; color: var(--tone); padding-top: .15rem; }
.magna-callout__heading { margin: 0; font-weight: 700; color: var(--color-heading); }
.magna-callout__body { color: var(--color-text); }

.magna-code { margin-inline: 0; margin-bottom: 0; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--color-border); }
.magna-code__filename { padding: .5rem 1rem; font: 600 .8rem var(--font-mono); color: var(--color-muted); background: var(--color-surface); border-bottom: 1px solid var(--color-border); }
.magna-code__pre { margin: 0; padding: 1rem 1.25rem; overflow-x: auto; background: var(--color-surface); line-height: 1.55; }

.magna-table { overflow-x: auto; }
.magna-table__table { width: 100%; border-collapse: collapse; font-size: .95rem; }
.magna-table__table :is(th, td) { padding: .65rem .85rem; border-bottom: 1px solid var(--color-border); text-align: left; vertical-align: top; }
.magna-table__table thead th { color: var(--color-heading); border-bottom-width: 2px; }
.magna-table__table tfoot :is(th, td) { font-weight: 700; border-bottom: 0; }
.magna-table__caption { caption-side: bottom; padding-top: .6rem; color: var(--color-muted); font-size: .875rem; text-align: left; }

.magna-divider { border: 0; border-top: 1px solid var(--color-border); margin: 0; }
.magna-divider--dashed { border-top-style: dashed; }
.magna-divider--dotted { border-top-style: dotted; }
.magna-divider--icon-center { display: flex; align-items: center; gap: 1rem; border: 0; color: var(--color-muted); }
.magna-divider__line { flex: 1; margin: 0; }

.magna-spacer--xs { height: .5rem; }
.magna-spacer--sm { height: 1rem; }
.magna-spacer--md { height: 2rem; }
.magna-spacer--lg { height: 3rem; }
.magna-spacer--xl { height: 4.5rem; }
.magna-spacer--2xl { height: 6rem; }

.magna-icon { color: var(--color-primary); line-height: 0; }
.magna-icon__link { color: inherit; }

.magna-html { overflow-wrap: anywhere; }

/* ───────────────────────── 5. Core blocks: marketing ───────────────────────── */
.magna-hero { padding-block: calc(var(--section-space) / 2); }
.magna-hero__content { max-width: 46rem; }
.magna-hero--centered { text-align: center; }
.magna-hero--centered .magna-hero__content { margin-inline: auto; }
.magna-hero--full-bleed .magna-hero__content { max-width: none; }
.magna-hero__headline { margin-bottom: .35em; }
.magna-hero__subheadline { font-size: 1.25rem; color: var(--color-muted); max-width: 38rem; }
.magna-hero--centered .magna-hero__subheadline { margin-inline: auto; }
.magna-hero__ctas { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
.magna-hero--centered .magna-hero__ctas { justify-content: center; }

.magna-cta { padding: clamp(2rem, 4vw, 3.5rem); border-radius: var(--radius-large); background: var(--color-surface); border: 1px solid var(--color-border); }
/* :where() keeps this at single-class weight, so an editor's own padding or
   background on the block (a .magna-n-… rule Pages prints later) still wins. */
:where(.starter-band--brand, .starter-band--muted) .magna-cta { background: transparent; border: 0; padding: 0; }
.magna-cta--centered { text-align: center; }
.magna-cta--left-right { display: grid; grid-template-columns: 1fr auto; gap: .5rem 2rem; align-items: center; }
.magna-cta--left-right .magna-cta__actions { grid-row: span 2; grid-column: 2; }
.magna-cta__headline { margin-bottom: .35em; }
.magna-cta__body { color: var(--color-muted); max-width: 40rem; margin-bottom: 0; }
.starter-band--brand .magna-cta__body { color: inherit; opacity: .9; }
.magna-cta--centered .magna-cta__body { margin-inline: auto; }
.magna-cta__actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem; }
.magna-cta--centered .magna-cta__actions { justify-content: center; }
.magna-cta--left-right .magna-cta__actions { margin-top: 0; }
@media (max-width: 767.98px) {
    .magna-cta--left-right { grid-template-columns: 1fr; }
    .magna-cta--left-right .magna-cta__actions { grid-column: 1; grid-row: auto; margin-top: 1rem; }
}

/* Features — Starter also overrides this view to give each item a card. */
.magna-features { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); }
.magna-features--horizontal-list { grid-template-columns: 1fr; }
.magna-features__item { padding: 1.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-large); background: var(--color-bg); }
.magna-features--horizontal-list .magna-features__item { display: grid; grid-template-columns: auto 1fr; gap: 0 1rem; }
.magna-features--horizontal-list .magna-features__item > :not(.magna-features__icon) { grid-column: 2; }
.magna-features__icon {
    display: inline-grid; place-items: center; width: 2.75rem; height: 2.75rem; margin-bottom: 1rem;
    border-radius: var(--radius); font-size: 1.35rem;
    background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);
}
.magna-features--horizontal-list .magna-features__icon { grid-row: span 2; margin-bottom: 0; }
.magna-features__title { margin-bottom: .35rem; font-size: 1.125rem; }
.magna-features__description { margin: 0; color: var(--color-muted); }

.magna-stats { display: grid; gap: 1.5rem; grid-template-columns: repeat(3, 1fr); }
.magna-stats--cols-2 { grid-template-columns: repeat(2, 1fr); }
.magna-stats--cols-4 { grid-template-columns: repeat(4, 1fr); }
.magna-stats__item { display: flex; flex-direction: column; gap: .25rem; padding: 1.25rem 0; border-top: 2px solid var(--color-primary); }
.magna-stats__value { font-size: clamp(2rem, 1.5rem + 2vw, 2.75rem); font-weight: 800; line-height: 1.1; color: var(--color-heading); letter-spacing: -.03em; }
.magna-stats__label { color: var(--color-muted); }
@media (max-width: 767.98px) { .magna-stats { grid-template-columns: repeat(2, 1fr) !important; } }

.magna-pricing { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); align-items: stretch; }
.magna-pricing__tier { text-align: left; display: flex; flex-direction: column; gap: 1rem; padding: 1.75rem; border: 1px solid var(--color-border); border-radius: var(--radius-large); background: var(--color-bg); }
.magna-pricing__tier--highlighted { border: 2px solid var(--color-primary); box-shadow: var(--starter-shadow); }
.magna-pricing__name { margin: 0; font-size: 1.125rem; }
.magna-pricing__amount { font-size: 2.5rem; font-weight: 800; color: var(--color-heading); letter-spacing: -.03em; }
.magna-pricing__period { color: var(--color-muted); }
.magna-pricing__features { flex: 1; margin: 0; padding: 0; list-style: none; }
.magna-pricing__features li { padding: .4rem 0 .4rem 1.6rem; position: relative; }
.magna-pricing__features li::before { content: "✓"; position: absolute; left: 0; color: var(--color-primary); font-weight: 700; }

.magna-testimonials { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); }
.magna-testimonials--carousel { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: .5rem; }
.magna-testimonials--carousel .magna-testimonials__item { flex: 0 0 min(24rem, 85%); scroll-snap-align: start; }
.magna-testimonials__item { margin: 0; padding: 1.5rem; border-radius: var(--radius-large); background: var(--color-surface); }
.magna-testimonials__quote { font-size: 1.0625rem; color: var(--color-heading); }
.magna-testimonials__quote::before { content: "“"; }
.magna-testimonials__quote::after { content: "”"; }
/* Flex drops the whitespace core's markup leaves between name and ", role". */
.magna-testimonials__author { display: flex; flex-wrap: wrap; color: var(--color-muted); font-size: .95rem; }
.starter-band--center .magna-testimonials__author { justify-content: center; }
.magna-testimonials__author strong { color: var(--color-text); }

.magna-team { display: grid; gap: 1.5rem; grid-template-columns: repeat(3, 1fr); }
.magna-team--cols-2 { grid-template-columns: repeat(2, 1fr); }
.magna-team--cols-4 { grid-template-columns: repeat(4, 1fr); }
.magna-team__photo { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: var(--radius-large); margin-bottom: .85rem; }
.magna-team__name { margin-bottom: .1rem; font-size: 1.0625rem; }
.magna-team__role { margin-bottom: .5rem; color: var(--color-primary); font-weight: 500; font-size: .95rem; }
.magna-team__bio { color: var(--color-muted); font-size: .95rem; }
@media (max-width: 767.98px) { .magna-team { grid-template-columns: 1fr 1fr !important; } }

.magna-faq { border-top: 1px solid var(--color-border); }
.magna-faq__item { border-bottom: 1px solid var(--color-border); }
.magna-faq__question {
    display: flex; justify-content: space-between; gap: 1rem; padding: 1.1rem 0;
    font-weight: 600; color: var(--color-heading); cursor: pointer; list-style: none;
}
.magna-faq__question::-webkit-details-marker { display: none; }
.magna-faq__question::after { content: "+"; color: var(--color-primary); font-size: 1.25rem; line-height: 1; }
.magna-faq__item[open] .magna-faq__question::after { content: "−"; }
.magna-faq__answer { padding: 0 0 1.1rem; color: var(--color-muted); max-width: var(--reading-width); }

.magna-logos { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 2rem 3rem; }
.magna-logos__item img { max-height: 2.5rem; width: auto; }
.magna-logos--grayscale .magna-logos__item img { filter: grayscale(1); opacity: .7; transition: filter .2s, opacity .2s; }
.magna-logos--grayscale .magna-logos__item:hover img { filter: none; opacity: 1; }

/* ───────────────────────── 6. Core blocks: media & dynamic ───────────────────────── */
.magna-image { margin-inline: 0; margin-bottom: 0; }
.magna-image img { display: block; border-radius: var(--radius); }
.magna-image figcaption { margin-top: .5rem; color: var(--color-muted); font-size: .875rem; }
.magna-image--float-left { float: left; max-width: 50%; margin: 0 1.5rem 1rem 0; }
.magna-image--float-right { float: right; max-width: 50%; margin: 0 0 1rem 1.5rem; }
.magna-image--captioned { padding: .75rem; border: 1px solid var(--color-border); border-radius: var(--radius-large); }

.magna-gallery { display: grid; gap: .75rem; grid-template-columns: repeat(3, 1fr); }
.magna-gallery--cols-2 { grid-template-columns: repeat(2, 1fr); }
.magna-gallery--cols-4 { grid-template-columns: repeat(4, 1fr); }
.magna-gallery__item img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: var(--radius); }

.magna-video video { display: block; width: 100%; border-radius: var(--radius); }
.magna-video__link {
    display: grid; place-items: center; aspect-ratio: 16 / 9; border-radius: var(--radius);
    background: var(--color-surface) center / cover no-repeat; color: var(--color-heading); text-decoration: none;
}
.magna-video__play-label { padding: .6rem 1.1rem; border-radius: 999px; background: var(--color-bg); font-weight: 600; box-shadow: var(--starter-shadow); }

.magna-file__link {
    display: inline-flex; align-items: baseline; gap: .75rem; padding: .85rem 1.1rem;
    border: 1px solid var(--color-border); border-radius: var(--radius); text-decoration: none; color: var(--color-heading);
}
.magna-file__link:hover { border-color: var(--color-primary); }
.magna-file__meta { color: var(--color-muted); font-size: .85rem; }
.magna-file__description { margin-top: .5rem; color: var(--color-muted); font-size: .95rem; }

.magna-entries { display: grid; gap: 1.25rem; }
.magna-entries--cards, .magna-entries--grid { grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
.magna-entries--cards .magna-entries__item { padding: 1.25rem; border: 1px solid var(--color-border); border-radius: var(--radius-large); }
.magna-entries--list .magna-entries__item { padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); }
.magna-entries__title { margin-bottom: .35rem; font-size: 1.125rem; }
.magna-entries__title a { color: var(--color-heading); text-decoration: none; }
.magna-entries__title a:hover { color: var(--color-primary); }
.magna-entries__excerpt { color: var(--color-muted); margin-bottom: .5rem; }
.magna-entries__date, .magna-entries__empty { color: var(--color-muted); font-size: .875rem; }

.magna-search__input {
    flex: 1; min-width: 0; padding: .65rem .9rem; font: inherit; color: var(--color-text);
    background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius);
}
.magna-search__input:focus { outline: none; border-color: var(--color-primary); box-shadow: var(--starter-ring); }
.magna-search__submit {
    padding: .65rem 1rem; border: 0; border-radius: var(--radius); font: inherit; font-weight: 600;
    background: var(--color-primary); color: var(--color-on-primary); cursor: pointer;
}

.magna-logo__link { display: inline-flex; align-items: center; color: var(--color-heading); text-decoration: none; }
.magna-logo__text { font-weight: 700; font-size: 1.125rem; }
.magna-logo__heading { margin: 0; font-size: inherit; }

.magna-container { min-width: 0; }

/* ───────────────────────── 7. Magna Pages blocks, chrome & plugins ───────────────────────── */
/* nav block: the same nav-items markup the header uses. */
.magna-block--nav .magna-nav__list { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin: 0; padding: 0; list-style: none; }
.magna-block--nav .magna-nav__list--depth-1 { display: none; }
.magna-block--nav a { color: var(--color-text); text-decoration: none; font-weight: 500; }
.magna-block--nav a:hover { color: var(--color-primary); }
.magna-chrome--footer .magna-block--nav .magna-nav__list { flex-direction: column; gap: .4rem; }
.magna-chrome--footer .magna-block--nav a { color: var(--color-muted); font-weight: 400; }

.magna-loop__heading { font-size: 1.375rem; }
.magna-loop__items { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); margin: 0; padding: 0; list-style: none; }
.magna-loop__item { overflow: hidden; border: 1px solid var(--color-border); border-radius: var(--radius-large); }
.magna-loop__image { display: block; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; }
.magna-loop__body { padding: 1.1rem 1.25rem; }
.magna-loop__title { display: block; margin-bottom: .35rem; font-weight: 700; color: var(--color-heading); text-decoration: none; }
.magna-loop__description { color: var(--color-muted); margin-bottom: .35rem; }
.magna-loop__date { color: var(--color-muted); font-size: .875rem; }

.magna-embed { margin-inline: 0; margin-bottom: 0; }
.magna-embed__frame { border-radius: var(--radius); overflow: hidden; }
.magna-embed__caption { margin-top: .5rem; color: var(--color-muted); font-size: .875rem; }

.magna-locale-switcher__items { display: flex; gap: .5rem; margin: 0; padding: 0; list-style: none; }
.magna-locale-switcher a, .magna-locale-switcher__current { padding: .2rem .45rem; border-radius: 6px; font-size: .85rem; font-weight: 600; text-decoration: none; }
.magna-locale-switcher__current { background: var(--color-surface); color: var(--color-heading); }

/* Shared pagination (Loop block, collection archives, Blog listings). */
.magna-pagination { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 2rem; }
.magna-pagination :is(.magna-pagination__prev, .magna-pagination__next) {
    padding: .5rem .9rem; border: 1px solid var(--color-border); border-radius: var(--radius); text-decoration: none;
}
.magna-pagination__status { color: var(--color-muted); font-size: .9rem; }

/* Popups and the consent banner (printed via $popupsHtml). */
.magna-popup__panel { background: var(--color-bg); color: var(--color-text); border-radius: var(--radius-large); box-shadow: var(--starter-shadow); }
.magna-consent { background: var(--color-bg); color: var(--color-text); border-top: 1px solid var(--color-border); }

/* Collections (mounted content types) and plugin frontend pages arrive in
   $mainHtml. Give them the same width and rhythm as builder pages. */
.magna-frontend-page, .magna-collection {
    max-width: var(--max-width); margin-inline: auto; padding: var(--section-space) var(--gutter);
}
.magna-frontend-page--content > article, .magna-collection--single { max-width: calc(var(--reading-width) + 2 * var(--gutter)); margin-inline: auto; }
.magna-collection__items { display: grid; gap: 1rem; margin: 0; padding: 0; list-style: none; }
.magna-collection__item { padding-bottom: 1rem; border-bottom: 1px solid var(--color-border); }
.magna-collection__date { color: var(--color-muted); font-size: .875rem; }

/* Magna Blog's post page — the plugin documents these hooks in its README. */
.magna-frontend-page--content .post-title { margin-bottom: .5rem; }
.magna-frontend-page--content .post-meta { color: var(--color-muted); font-size: .95rem; margin-bottom: 2rem; }
.magna-frontend-page--content .featured { display: block; width: 100%; border-radius: var(--radius-large); margin-bottom: 2rem; }
.magna-frontend-page--content .post-body { line-height: 1.75; }
.magna-frontend-page--content .post-nav { display: flex; justify-content: space-between; gap: 1rem; margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border); }

/* ───────────────────────── 8. Utilities & motion ───────────────────────── */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; scroll-behavior: auto !important; }
}
@media print {
    .starter-header, .starter-footer, .starter-skip { display: none; }
    .magna-section__inner { padding-block: 1rem; }
}
</style>
