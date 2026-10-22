# 11. Quality checklist

Go through this before every release. Copy it into your release issue and tick it off.

## Checks pass

- [ ] `php tools/theme-lint.php . --strict` is clean
- [ ] `php artisan magna:theme:check vendor/name` is clean on a real site
- [ ] Activation works on the **lowest** versions your `compat` claims, not only the newest

## Fresh install

- [ ] On an **empty** site: activate, import demo content, and every demo page looks like your screenshots
- [ ] On a site **with content**: activating breaks nothing, and demo import skips what exists
- [ ] With **no menus** at all, the fallback header and footer still look intentional
- [ ] With the admin panel at `/`, the wordmark still reaches the home page (`$homeUrl`)

## Every block

- [ ] The demo pages, plus a scratch page holding every core block, look right. Use the
      [block reference](reference/blocks.md) as the list
- [ ] Every option of every `select` field has a style (`magna-hero--full-bleed`, `magna-stats--cols-4`, …)
- [ ] Empty and half-filled blocks degrade gracefully (a features block with one item, a CTA with no buttons)
- [ ] Long words, long headings and very long menus don't overflow

## Builder

- [ ] Open each demo page in the builder: the canvas looks like the published page
- [ ] Selecting, inserting and dragging blocks works, so nothing in your CSS or JavaScript blocks clicks
- [ ] Per-block styles an editor sets (padding, background, colour) visibly apply. Your root
      selectors are single-class weight ([chapter 6](06-styling-blocks.md#leave-room-for-editors-styles))
- [ ] A header and footer designed as template parts replace your fallbacks cleanly, including sticky
- [ ] The Design panel lists your tokens, and changing one changes the page

## Responsive

- [ ] 360px, 768px, 1024px and 1440px widths, with no horizontal scrolling at any of them
- [ ] Multi-column sections stack on phones
- [ ] The small-screen menu opens, closes, and can be used with the keyboard

## Light and dark

- [ ] Every colour token has a `dark` value
- [ ] Follow-system, **always light** and **always dark** (Pages → Site settings) all look right
- [ ] The switch (yours and the core block) works, survives a reload, and causes no flash on load
- [ ] Images and SVG icons stay visible on both backgrounds

## Accessibility

- [ ] Text contrast is at least 4.5:1 (3:1 for large text and UI) in **both** schemes
- [ ] A skip link to `<main>`, and visible `:focus-visible` styles on every interactive element
- [ ] Icon-only buttons have an accessible name, and decorative SVGs have `aria-hidden="true"`
- [ ] `prefers-reduced-motion` disables your transitions and animations
- [ ] One `<h1>` per page in the demo content; heading levels don't skip
- [ ] Lighthouse Accessibility ≥ 95 on the demo home page

## Performance

- [ ] No render-blocking third-party requests you didn't intend (fonts are the usual one)
- [ ] The inline stylesheet stays lean (Starter is about 35 KB unminified)
- [ ] Lighthouse Performance ≥ 90 on mobile for the demo home page

## SEO

- [ ] `<title>` uses `$seoTitle` when set, and otherwise the page title plus the site name
- [ ] `<meta name="description">` when `$metaDescription` is set
- [ ] `hreflang` links when a page has translations
- [ ] `<html lang>` comes from `app()->getLocale()`

## Package

- [ ] `version` bumped ([chapter 12](12-packaging-and-publishing.md)), and `CHANGELOG` updated
- [ ] `screenshot.png` is current (1200×900)
- [ ] `php tools/package.php .` builds the zip, and the zip installs on a clean site

---

Next: **[12. Packaging and publishing →](12-packaging-and-publishing.md)**
