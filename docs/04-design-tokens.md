# 4. Design tokens

Tokens are the most important file in a theme. They are your palette, type and spacing as named
values. Magna Pages compiles them into CSS custom properties, and your stylesheet only ever reads
those properties. In return you get, for free:

- The builder's **Design** panel lets a site retune any token without touching your files.
- **Scheduled designs** (**Pages → Scheduled design**) swap token values for a date range, for a
  sale or a holiday palette.
- **Dark mode** comes from a second value per token, emitted in the same stylesheet.
- **Per-section overrides**: one section can redefine a token for itself only.
- **Editors' style controls** accept `var(--color-primary)`, so their choices follow the palette.

## The file

```json
{
    "colors": {
        "primary": { "value": "#3b5bdb", "dark": "#8ea2ff", "label": "Brand colour" },
        "text":    { "value": "#1d2330", "dark": "#e6e8ee" }
    },
    "typography": {
        "fontBody": { "value": "system-ui, -apple-system, \"Segoe UI\", Roboto, sans-serif" },
        "lineHeight": "1.65"
    },
    "layout": {
        "maxWidth": { "value": "1120px" },
        "radius":   { "value": "10px" }
    }
}
```

- Top-level keys are **categories**. Name them freely: `colors`, `typography`, `layout`,
  `spacing`, `shadows`, …
- Each token is either a plain value (`"lineHeight": "1.65"`) or an object with:
  - `value`: the light (default) value. Required in the object form.
  - `dark` (optional): the dark-mode value. Tokens without one are the same in both schemes,
    which is right for widths and radii.
  - `label` (optional): a human name for documentation and tooling. The Design panel currently lists
    tokens by their variable name.

## How names become CSS variables

| Token | CSS variable | Rule |
|---|---|---|
| `colors.primary` | `--color-primary` | `colors` gets a `color-` prefix |
| `colors.primaryStrong` | `--color-primary-strong` | camelCase → kebab-case |
| `layout.maxWidth` | `--max-width` | every other category uses **the key alone** |
| `typography.fontBody` | `--font-body` | |
| `spacing.section_gap` | `--section-gap` | `_` → `-` |

Because non-colour categories drop their category name, **keys must be unique across
categories**. `layout.gap` and `spacing.gap` both become `--gap`, and one silently wins.
`theme-lint` reports the collision.

Print the full mapping for any theme:

```bash
php tools/theme-lint.php starter-theme --tokens
```

The layout receives all of it as one ready-made string, `$tokensCss`, which it must print in
`<head>` ([chapter 5](05-layout.md)):

```html
<style>{!! $tokensCss !!}</style>
```

## The value rule: no `;` `(` `<` `>`

Token values are filtered **every time a page renders**. A value containing `;`, `(`, `<` or `>`,
or an empty value, is dropped: the variable is simply missing. The filter stops a stored value
from breaking out of the CSS rule or the `<style>` element. It also means:

- **No functions in tokens.** No `rgb()`, `hsl()`, `oklch()`, `calc()`, `clamp()`, `var()`,
  `url()`, `color-mix()`.
- Colours are **hex** (`#3b5bdb`, `#3b5bdb80` for alpha) or keywords. Hex also gets a colour
  swatch in the Design panel.
- Put fixed values in tokens and **do the arithmetic in your CSS**:

```css
/* tokens: layout.sectionSpace = "4.5rem", layout.gutter = "1.25rem" */
.magna-section__inner { padding: calc(var(--section-space) / 2) var(--gutter); }

/* tokens: colors.primary = "#3b5bdb" */
::selection { background: color-mix(in srgb, var(--color-primary) 25%, transparent); }
```

`magna:theme:check` and `theme-lint` both flag `;` and `(` as errors, because such a token can
never apply.

## Dark mode

Give every colour a `dark` value. What happens next depends on the site's **Pages → Site
settings → Colour scheme**:

| Setting | `$tokensCss` contains |
|---|---|
| Follow the visitor's preference (default) | light values on `:root`, dark values in `@media (prefers-color-scheme: dark)` guarded by `:root:not([data-theme="light"])`, and again under `:root[data-theme="dark"]` |
| Always light | light values only |
| Always dark | dark values on `:root` |

So in the default case, **three states work with no server involvement**: the system preference,
an explicit `data-theme="dark"` on `<html>`, and an explicit `data-theme="light"` overriding a
dark system. The core **Light / dark switch** block sets that attribute and remembers the choice
in `localStorage` under the key `magna-theme`. Use the same key in any switch of your own (Starter's
header button does).

Your CSS should follow the same three states for anything that is not a token, such as shadows or
`color-scheme`:

```css
:root { color-scheme: light; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) { color-scheme: dark; }
}
:root[data-theme="dark"] { color-scheme: dark; }
```

To avoid a flash of the light palette for a visitor who chose dark, apply the stored choice before
first paint, in `<head>` ([chapter 5](05-layout.md) shows the one-line script).

## The Design panel and scheduled designs

The builder's **Design** panel lists every variable your theme declares and lets a site override its *light*
value. Overrides:

- are stored per theme, with revisions, so a bad change is one click to revert;
- may only replace variables your theme declares. A site cannot invent new ones;
- pass the same `; ( < >` filter and a 120-character limit;
- do **not** change dark values. A site that retunes `--color-primary` keeps your dark primary.

Scheduled designs follow the same rules for a date range.

The consequence for theme authors: **every value a site might reasonably want to change should be
a token.** A colour hard-coded in your CSS is one the site owner cannot change.

## Section overrides

A section can override tokens for itself (`settings.tokenOverrides`, keyed by variable name without
`--`). They are emitted as custom properties in the section's `style` attribute, so anything inside
that reads the variable follows:

```json
{ "settings": { "tokenOverrides": { "color-primary": "#e11d48" } } }
```

This works only because your CSS reads tokens instead of hard-coded values. It is one more reason
to never write a raw hex in the stylesheet.

## Designing a good token set

- **Name by role, not by hue.** `primary`, `surface`, `muted`, `border`, not `blue`, `gray100`.
  A site that retunes `primary` to green should not be left with a variable called `blue`.
- **Pair foreground with background.** If you have `primary`, you need `onPrimary` (text on it).
  Check contrast for both readings: 4.5:1 for body text, 3:1 for large text and UI.
- **Keep it small.** Starter has 25 tokens. Every token is a decision you are asking a site owner
  to understand in the Design panel.
- **Tokens are a public interface.** Once released, renaming a token breaks every site that
  overrode it: the override no longer matches a declared variable and is ignored. Add new tokens
  freely, but rename or remove only in a major version.

---

Next: **[5. The layout →](05-layout.md)**
