# 3. Theme anatomy

```
acme/starter/
├── theme.json                     required — the manifest
├── tokens.json                    recommended — design tokens → CSS variables
├── screenshot.png                 recommended — the card on the Themes page (1200×900)
├── views/
│   ├── system/
│   │   └── layout.blade.php       recommended — the page shell
│   ├── partials/                  optional — your own includes: head, header, footer, styles, scripts
│   │   └── *.blade.php                        reached as theme::partials.<name>
│   └── blocks/                    optional — block view overrides
│       └── <handle>.blade.php                 replaces core's view for that block
└── starter/                       optional — demo content
    └── *.json                                 magna-site-kit bundles
```

Nothing else is read by Magna. You may keep a `README.md`, a `LICENSE`, an `art/` folder of
marketing images or a `composer.json` beside these. The packaging tool leaves development-only
folders out of the zip ([chapter 12](12-packaging-and-publishing.md)).

## `theme.json`

The manifest. Only `name` and `version` are required. Everything else is presentational, except
`compat`, which decides whether the theme may be activated at all.

```json
{
    "name": "acme/starter",
    "displayName": "Starter",
    "description": "A small, readable reference theme for Magna Pages.",
    "version": "1.0.0",
    "author": "Magna CMS",
    "license": "MIT",
    "homepage": "https://github.com/Magna-CMS/Magna-Theme-Starter-Kit",
    "screenshot": "screenshot.png",
    "type": "magna-theme",
    "tags": ["starter", "business", "minimal", "dark-mode"],
    "compat": {
        "magna": "^1.6.2",
        "plugins": { "magna-cms/pages": ">=0.3.0-alpha" }
    },
    "demoContent": ["starter/demo.json"],
    "blockSeeds": {
        "heading": { "level": "h2", "text": "Section title" }
    }
}
```

| Key | Required | What it does |
|---|---|---|
| `name` | **yes** | `vendor/name`: lowercase letters, digits, `-` and `_`, exactly one slash. It must match the folder the theme is installed in (`themes/vendor/name/`), or Magna ignores the theme. |
| `version` | **yes** | Semantic version. The Themes page compares it to offer updates. |
| `displayName` | | Shown on the Themes page. Defaults to `name`. |
| `description`, `author`, `license`, `homepage`, `tags` | | Shown on the Themes page and in the store. |
| `screenshot` | | Path of the preview image, relative to the theme folder. |
| `type` | | `magna-theme` (default) or `magna-theme-addon` ([chapter 9](09-addons.md)). |
| `compat.magna` | | Composer constraint on Magna core. Defaults to `^1.0`. Checked **on activation**, so a core upgrade can make a theme incompatible without anything being reinstalled. |
| `compat.plugins` | | Plugin version floors as `package: constraint`. **Declare `magna-cms/pages`.** When a floor is not met, activation is refused and the message names the gap. Without it, a theme built on a newer Pages feature silently renders wrong on an older site, because unknown block settings are dropped and nothing reports it. |
| `demoContent` | | The bundles **Import demo content** applies. Only `.json` files inside `starter/`. Without this key, every `starter/*.json` counts ([chapter 8](08-demo-content.md)). |
| `blockSeeds` | | What a newly inserted block starts with, per block handle, merged over the block's own defaults ([chapter 8](08-demo-content.md)). |
| `extends`, `pairsWith`, `priority` | addons only | [Chapter 9](09-addons.md). |

The full rules are in [`schemas/theme.schema.json`](../schemas/theme.schema.json). Point your
editor at it for autocomplete. Starter's `theme.json` already does, through `"$schema"`, which
Magna ignores.

## `tokens.json`

Categories of named values that Magna Pages compiles into CSS custom properties, with optional
dark values. [Chapter 4](04-design-tokens.md).

## `views/system/layout.blade.php`

The page shell, rendered for every page. It is optional: without it, pages render inside Magna
Pages' bare built-in shell (semantic HTML, almost no styling). [Chapter 5](05-layout.md).

## `views/partials/`

Your own Blade includes, reached as `theme::partials.<name>`. Magna does not require any of them.
Starter splits its layout into `head`, `header`, `footer`, `styles` and `scripts` partials so each
file stays readable.

## `views/blocks/<handle>.blade.php`

A file here **replaces** the default view for the block with that handle: core blocks, Magna Pages
blocks, or a plugin's blocks (Eva overrides Magna Blog's `blog-posts`). Most themes need few or
none, since CSS reaches almost everything. [Chapter 7](07-overriding-blocks.md).

## `starter/*.json`

Demo content as *site kits*: pages, menus, template parts, patterns and settings, cross-referenced
by slug. [Chapter 8](08-demo-content.md).

## There is no `assets/` folder

Magna serves **no files from a theme folder**. A `.css`, `.js` or image file placed in a theme
cannot be linked from a page. That is why Starter (and Eva) ship their CSS and JavaScript as
inline `<style>`/`<script>` in layout partials. This costs nothing extra, because the page cache
stores the whole HTML document anyway. For images and fonts:

- **Icons**: inline SVG in your views, as Starter's header and callout override do.
- **Fonts**: the system font stack (Starter), or a font CDN linked in `<head>` (Eva uses Google
  Fonts). Weigh the privacy and performance cost of a third-party font host.
- **Images**: anything editors choose comes from the media library. Decorative theme images can be
  CSS gradients, inline SVG, `data:` URIs for small files, or an external URL you control.

---

Next: **[4. Design tokens →](04-design-tokens.md)**
