# 1. How Magna Pages renders a theme

Magna CMS on its own is **headless**: it stores content and serves it over an API. It draws no
web pages. **Magna Pages** is the official plugin that turns the same install into a website. It
adds pages, menus, header and footer templates, a design-token system and a visual builder.

**A Magna theme is built on top of Magna Pages.** Pages owns the rendering pipeline. A theme
supplies the *look* that pipeline draws with:

```
┌──────────────────────── Magna Pages (the plugin) ─────────────────────────┐
│  routing · page cache · the builder · menus · templates · block resolution │
│                                                                            │
│        draws every page with ──►  YOUR THEME                               │
│                                   ├─ theme.json     who you are            │
│                                   ├─ tokens.json    colours, fonts, sizes  │
│                                   ├─ views/system/layout.blade.php         │
│                                   │                 the page shell         │
│                                   └─ views/blocks/*.blade.php (optional)   │
│                                                     restyled block markup  │
└────────────────────────────────────────────────────────────────────────────┘
                     on top of Magna CMS (content, media, users, API)
```

A theme is **data, not code**. It has no PHP classes, nothing boots and there is nothing to
enable. It is a folder of JSON, Blade templates and CSS. Activating a theme changes one setting,
and a broken theme can never take the admin panel down.

## Who controls what

Getting this split right is most of the job.

| The **theme** decides | **Editors** decide, in the builder |
|---|---|
| The page shell: `<head>`, fallback header and footer, skip link, scripts | Which pages exist, and their sections, columns and blocks |
| The design vocabulary: tokens, typography, spacing rhythm | Content of every block |
| How every block *looks*, through CSS against stable class names | Per-node styles: padding, colours, borders, widths, per breakpoint |
| Optionally, how a block is *marked up* (block view overrides) | Section CSS classes, chosen from the vocabulary your theme documents |
| The starting content of new blocks (`blockSeeds`) | Header and footer as template parts, which replace your fallbacks |
| Demo content: pages, menus and patterns a site can import | Token values, through the builder's **Design** panel (overrides yours, per site) |

A good theme makes the right column easy. Tokens give editors a palette to retune instead of hex
codes to fight. Band classes give them named layouts. Fallback chrome gives them a finished site
before they have designed anything.

## The path of one request

```
GET /about
  │
  ├─ maintenance mode on? ──────────────► plugin's maintenance page (not themed)
  ├─ cached copy for guests? ───────────► served as-is (the whole HTML document)
  ├─ a published page at this path? ────► PageRenderer
  ├─ a mounted collection / plugin page? ► rendered into $mainHtml, then PageRenderer
  ├─ a redirect? ───────────────────────► 301/302
  └─ nothing ───────────────────────────► the site's 404 page (themed) or a bare fallback

PageRenderer
  1. resolves the page's header and footer template parts
  2. renders  views/system/layout.blade.php   ← your layout, with ~20 variables
       └─ which @includes magna-pages::partials.sections
            └─ for each section → columns → blocks:
                 view = addon view      (plugin blocks only)
                      → theme::blocks.{handle}   ← your override, if any
                      → magna::blocks.{handle}   ← core / plugin default
                 + magna::block-behaviour.{handle}  (always core's; you cannot replace it)
  3. stores the HTML in the page cache (guests only)
```

Five consequences follow from this pipeline. Every chapter after this one builds on them:

1. **The builder canvas is the real page.** The builder loads your layout in an iframe through the
   same renderer and adds `data-magna-node` attributes so a click maps back to a block. There is
   no separate editor preview to keep in sync. What editors see is your theme. The canvas also
   injects its script before `</body>`, so your layout must have one.
2. **The first element of a block's output is the block.** The builder's marker, and the class
   carrying an editor's per-block styles, are stamped onto it. A block view must render its root
   element first. → [chapter 7](07-overriding-blocks.md)
3. **One cached HTML document is served to every guest.** A layout or view must never print
   anything that differs per visitor, such as a logged-in name or a CSRF token. Light and dark
   both ship in the same stylesheet, which is why dark mode is a CSS concern and not a server
   decision. → [chapter 4](04-design-tokens.md)
4. **Views receive data; they never query.** Everything a layout needs arrives as a variable,
   including closures such as `$menu('footer')` and `$pageUrl('pricing')`. The theme audit refuses
   database queries, container lookups and PHP escapes. → [chapter 10](10-rules-and-checks.md)
5. **Anything you leave out falls back, never breaks.** No layout means a bare built-in shell. No
   block override means core's view. No tokens means no CSS variables. An unknown block renders
   nothing. You can start with three files and grow.

## Where the theme files live on a site

```
my-magna-site/
└── themes/
    └── <vendor>/
        └── <name>/          ← must match "name": "<vendor>/<name>" in theme.json
            ├── theme.json
            └── …
```

The themes root can be moved with `MAGNA_THEMES_PATH`. A theme whose folder does not match its
manifest's `name` is ignored. Installed themes appear under **Admin → Themes**, where one is
activated and demo content can be imported.

---

Next: **[2. Quick start →](02-quick-start.md)**
