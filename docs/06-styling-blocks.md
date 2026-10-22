# 6. Styling blocks with CSS

Most of a theme is CSS. Core's block markup carries stable class names on purpose, so a theme can
restyle every block without replacing any markup. **Reach for CSS first**: it keeps your theme
compatible with future block changes, it keeps behaviour hooks intact, and it is all
`magna:theme:check` needs to see.

Starter's stylesheet is [`views/partials/styles.blade.php`](../starter-theme/views/partials/styles.blade.php).
It covers every core and Magna Pages block in eight commented sections.

## The structure of every page

The sections partial produces this. Your CSS gives it width, rhythm and responsiveness. Magna
Pages deliberately ships almost no layout CSS of its own.

```html
<section class="magna-section [editor's CSS class] [magna-hide-*] [magna-motion--*]"
         id="[editor's anchor]" style="[editor's styles, token overrides]">
  <div class="magna-section__inner">
    <div class="magna-columns">
      <div class="magna-column" style="flex: 6 6 0%">   <!-- span 6 of 12 -->
        <div class="magna-block magna-block--heading magna-heading magna-heading--left">…</div>
        <div class="magna-block magna-block--text magna-prose">…</div>
      </div>
      <div class="magna-column" style="flex: 6 6 0%">…</div>
    </div>
  </div>
</section>
```

What your theme must provide (Starter's section 3 does all of it):

```css
.magna-section__inner { max-width: var(--max-width); margin-inline: auto; padding: … var(--gutter); }
.magna-columns        { display: flex; flex-wrap: wrap; gap: 2rem; }
.magna-column         { min-width: 0; }
@media (max-width: 767.98px) {
    .magna-columns { flex-direction: column; }
    .magna-column  { flex: 1 1 100% !important; }   /* beats the inline flex */
}
```

- Each column carries its width as an inline `flex: N N 0%`, so stacking on phones needs
  `!important`.
- A row may instead store explicit grid tracks (`settings.row.columnTemplate`). Magna Pages then
  emits `display: grid` for that row from its own stylesheet, the inline `flex` stops applying,
  and the narrow-screen behaviour is part of the document. Your flex rules do no harm there.
- Magna Pages owns the device-visibility classes (`magna-hide-desktop|tablet|mobile`) and the
  motion presets (`magna-motion--fade|rise`). You don't need to style them.

## Breakpoints

Use the editor's three devices, so "hide on mobile" in the builder and your own mobile rules
change at the same width:

| Device | Width | Media query |
|---|---|---|
| Desktop | ≥ 1024px | `(min-width: 1024px)` |
| Tablet | 768–1023px | `(max-width: 1023.98px)` |
| Mobile | < 768px | `(max-width: 767.98px)` |

## Section "bands": your design vocabulary

Editors give a section a **CSS class** in the builder (*Advanced → CSS class*), as free text. That
field is how a theme offers named layouts: a muted band, a brand-coloured band, a centred band.
Starter's vocabulary:

| Class | Effect |
|---|---|
| `starter-band--muted` | surface background |
| `starter-band--brand` | brand-colour background, light text, inverted buttons |
| `starter-band--center` | centred headings, text and actions |
| `starter-band--narrow` | content held to a comfortable reading width |
| `starter-band--tight` | half the usual vertical space |

Classes combine: `starter-band--muted starter-band--center`. The builder does not list your
classes for editors yet, so **publish your vocabulary** in your theme's README, and use it in your
demo content and patterns so editors meet it in context.

Prefix every class with your theme's name. `band--muted` alone will collide with another theme's
CSS or a site's custom CSS.

## Target the wrapper, not the element

Core block views put their classes on a **wrapper** around the semantic element:

```html
<div class="magna-block magna-block--heading magna-heading magna-heading--center">
    <h2>Section title</h2>
</div>
```

```css
.magna-heading h2 { … }      /* ✔ matches */
h2.magna-heading  { … }      /* ✘ matches nothing, and nothing warns you */
```

Every block's root classes, inner classes and modifiers are listed in the
[block reference](reference/blocks.md). Modifiers carry field values. For example
`magna-cta--{layout}` becomes `magna-cta--centered` or `magna-cta--left-right`, so you can style
each option an editor can choose.

## Leave room for editors' styles

Editors can set padding, margins, colours, borders, radius, font size and more on any section,
column or block, per device. The builder's colour fields accept `var(--color-primary)`, so
editors' choices can follow your palette. Magna Pages applies their styles like this:

| Node | Applied as | What that means for you |
|---|---|---|
| Section, column | inline `style` attribute | always beats your stylesheet |
| Block | a `.magna-n-{id}` rule in a `<style>` printed **after** the page's sections | beats your rules **only at equal specificity** |

So **keep rules that style a block's root element at single-class weight**. For a contextual rule,
wrap the context in `:where()`, which adds no specificity:

```css
.magna-cta { padding: 3rem; }                                  /* ✔ an editor's padding wins */
.starter-band--brand .magna-cta { padding: 0; }                /* ✘ silently beats the editor */
:where(.starter-band--brand) .magna-cta { padding: 0; }        /* ✔ same effect, editor still wins */
```

Rules for elements *inside* a block (`.magna-cta__headline`, `.magna-heading h2`) are not
affected. Editors style the root.

## Content that is not a block

Your stylesheet also styles HTML that arrives through `$mainHtml`. Each source documents its own
markup:

- **Plugin frontend pages** are wrapped in `div.magna-frontend-page.magna-frontend-page--content`
  (or `--app`). Magna Blog's post page puts its `<article>` directly inside, with `.post-title`,
  `.post-meta`, `.featured`, `.post-body` and `.post-nav`, plus a `section.post-comments`. The
  [Blog README](https://github.com/Magna-CMS/Blog) lists every hook.
- **Collections** (content types mounted on a URL) render `.magna-collection--archive` and
  `.magna-collection--single` when the site has not designed a template for them.
- **Shared pagination**: `.magna-pagination` with `__prev`, `__next` and `__status`.
- **Popups and consent**: `.magna-popup`, `.magna-popup__panel`, `.magna-popup__close`,
  `.magna-consent`.

## Rich text

The text block renders sanitized HTML inside `.magna-prose`: paragraphs, headings, lists,
blockquotes, code, tables, images and links. Style it as article typography, with
`max-width: var(--reading-width)` for line length.

## A workflow that works

1. Get **tokens** right first. Most of the look follows from them.
2. Build the page structure rules (section, inner, columns, bands).
3. Open the demo home page **in the builder** and work through block families: typography, then
   buttons, then the marketing blocks, then media. Starter's stylesheet is in that order.
4. Check each family at mobile width and in dark mode as you go, not at the end.

---

Next: **[7. Overriding block views →](07-overriding-blocks.md)**
