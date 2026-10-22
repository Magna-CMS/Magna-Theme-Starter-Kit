# 8. Demo content and editor defaults

A theme is judged in the first minute after activation. Demo content makes that minute look like
your screenshot. It lives in `starter/*.json` as **site kits**: the same format Magna Pages uses
to move a site between environments.

## What a site kit holds

```json
{
    "format": "magna-site-kit",
    "version": 1,
    "pages":     [ { "title": "Home", "slug": "home", "status": "published", "blocks_data": [ … ] } ],
    "templates": [ { "title": "Header", "slug": "header", "kind": "part", "status": "published", "blocks_data": [ … ] } ],
    "menus":     [ { "handle": "primary", "name": "Primary navigation", "tree": [ … ] } ],
    "patterns":  [ { "name": "Starter — FAQ", "kind": "section", "document": { … } } ],
    "settings":  { "home_page_slug": "home", "not_found_page_slug": "not-found" }
}
```

Everything is cross-referenced by **slug** (pages, menu links) or **name** (patterns), never by
database id, so a kit applies on any site. The full shape is in
[`schemas/site-kit.schema.json`](../schemas/site-kit.schema.json), and Starter's
[`demo.json`](../starter-theme/starter/demo.json) is a complete example.

| Key | Notes |
|---|---|
| `pages` | `title` and `slug` required. Only `"status": "published"` pages render. `meta_title`, `meta_description`, `page_settings`, `locale`, `path` and `template` are optional. |
| `templates` | Template parts and popups. `kind`: `part`, `popup`, `collection`, `page` or `system`. A kit cannot set a part's *role*, so name your header part **`header`** and your footer part **`footer`**. Those slugs become the site's chrome automatically. |
| `menus` | `tree` items: `{label, type: "page", page_slug}` or `{label, type: "url", url}`, plus `target` and `children`. The `primary` menu feeds the layout's `$headerMenu`. |
| `patterns` | Reusable sections in the builder's library. Ship your band designs as patterns, so editors can insert a pricing table or an FAQ band in one click. |
| `settings` | `home_page_slug`, `not_found_page_slug`. |
| `plugin:entity` keys | Other plugins' content, e.g. Magna Blog posts under `blog:posts`. Skipped (and reported) when that plugin is not enabled. |

## The block document

`blocks_data` is a list of sections, and patterns' `document` is one section:

```json
{
    "id": "starter-sec-1",
    "type": "section",
    "settings": { "cssClass": "starter-band--muted", "anchor": "features" },
    "columns": [
        {
            "id": "starter-col-1",
            "span": 12,
            "settings": {},
            "blocks": [
                { "id": "starter-blk-1", "block": "heading", "settings": {},
                  "data": { "level": "h2", "text": "Everything a first theme needs", "align": "center" } }
            ]
        }
    ]
}
```

- **Ids** must be unique within a document. Prefix them with your theme name.
- **Column spans** must add up to **12** in every section, or the document is refused on save.
- **`json` fields are JSON strings**: `"items": "[{\"title\":\"Fast\"}]"`, not arrays. `repeater`
  fields (`faq.items`) *are* arrays.
- **Media is not bundled.** Media fields hold media-library ids, which do not exist on another
  site. Design demo content that looks good without images: the core hero, features, stats,
  pricing, testimonials and FAQ blocks need none.

`theme-lint` checks all of this: spans, duplicate ids, unknown block handles, `json` fields stored
as arrays, menu links to pages the kit does not create, and missing slugs.

## How it gets applied

| Route | Behaviour |
|---|---|
| **Admin → Themes → Import demo content** (or *Include demo content* when installing) | **Add-only.** Pages and templates whose slug already exists are skipped. A menu is filled only if missing or empty. Patterns are added only under new names. Home and 404 are set only if unset. Nothing the site already has is changed. Needs Magna CMS 1.7.0+ and Magna Pages 0.6.0+. |
| `php artisan magna:site:sync <file>` | **Upsert.** Updates pages with the same slug, replaces menu trees, and writes settings (including maintenance mode). For development and for applying a kit on purpose. Preview first with `magna:site:diff <file>`. |

Which files the import uses is decided by `demoContent` in `theme.json`:

```json
"demoContent": ["starter/demo.json"]
```

Without that key, **every** `starter/*.json` is imported. Declaring it lets you keep optional kits
beside the demo. Starter ships [`starter/chrome.json`](../starter-theme/starter/chrome.json), a
header and footer as builder-editable template parts. It is *not* in `demoContent`, so a demo
import keeps the theme's own fallback chrome. A site that wants editable chrome applies it on
purpose:

```bash
php artisan magna:site:sync themes/acme/starter/starter/chrome.json
```

Only `.json` files inside the theme's own `starter/` folder are accepted, up to 25 MB each.

**Theme updates never re-apply demo content.** When an update ships changed kits, the Themes page
tells the admin and names the sync command. Applying it over pages editors have changed is their
decision.

## Authoring workflow

Don't write kits by hand beyond small edits. Build the content where it will be seen:

1. On a scratch site with your theme active, build the pages, menus and patterns in the builder.
2. Export everything: `php artisan magna:site:export starter/demo.json`.
3. Trim the export to what you ship. Remove pages you were only testing with, and make sure no
   block references a media id.
4. Run `php tools/theme-lint.php .`, then import into a **fresh** site and look at every page.

## `blockSeeds`: what a new block starts with

When an editor drops a block into a page, it starts with the block schema's defaults, which are
generic. `blockSeeds` in `theme.json` replaces them per block with content that belongs to your
design:

```json
"blockSeeds": {
    "heading": { "level": "h2", "text": "Section title" },
    "cta": {
        "headline": "Ready when you are.",
        "button_primary_label": "Get started",
        "layout": "centered"
    }
}
```

Seeds are **merged over** the schema's own, so you list only the fields you want different.
Keys are field handles from the [block reference](reference/blocks.md). A seed for a handle that
is not a block, or a JSON list where a field map belongs, is ignored. `theme-lint` reports both,
plus field names the block does not have.

---

Next: **[9. Theme addons →](09-addons.md)**
