# 13. Troubleshooting

### My theme doesn't appear on the Themes page

- The folder must be `themes/<vendor>/<name>/`, matching `"name"` in `theme.json` exactly. A theme
  sitting in the wrong folder is skipped on purpose.
- `theme.json` must be valid JSON with a valid `name` and a `version`. Run
  `php tools/theme-lint.php <dir>`. A malformed manifest hides only that theme.
- Using a custom themes root? Check `MAGNA_THEMES_PATH`.

### "Not compatible with this version of Magna" / "needs magna-cms/pages …"

Activation checks `compat.magna` and `compat.plugins` against the running site. The message names
the gap: the plugin is either missing, disabled, or older than your floor.

### My change doesn't show up

1. **Page cache.** Guests get a cached copy of each page for up to an hour. Stay signed in while
   you work (signed-in visitors always get a fresh render), or flush it: save **Pages → Site
   settings**, or re-activate the theme.
2. **Browser cache.** Hard-reload.
3. **Compiled views.** Blade recompiles a view when its source is newer than the compiled copy.
   Files copied in with old timestamps, from an archive or a restored backup, can be missed.
   Re-activating the theme re-stamps them, or run `php artisan view:clear`.

### A token doesn't apply

- Its value contains `;`, `(`, `<` or `>`, or is empty, so it is dropped at render. No `rgb()`,
  `calc()`, `var()` in tokens ([chapter 4](04-design-tokens.md)).
- Two tokens compile to the same variable (`layout.gap` and `spacing.gap` → `--gap`).
- The layout doesn't print `$tokensCss`, or prints your stylesheet **before** it.
- A site override from the Design panel is replacing your value. That is by design.

### Dark mode doesn't switch

- The token has no `dark` value.
- The site is pinned to one scheme (**Pages → Site settings → Colour scheme**). `$tokensCss` then
  contains no dark rules, and Starter hides its switch.
- Your CSS uses a hard-coded colour instead of a token.

### My block override is ignored

- The file name must be the block's **handle** exactly: `views/blocks/callout.blade.php`.
- A theme **addon** can never override a core block, only blocks of plugins in its `pairsWith`.
- The plugin that provides the block isn't enabled.

### Editors' styles on a block don't apply

One of your rules targets the block's root with more than single-class specificity. Wrap the
context in `:where()` ([chapter 6](06-styling-blocks.md#leave-room-for-editors-styles)).

### A block is unselectable in the builder, or the wrong element highlights

Your override renders something before its root element (a `<style>`, a second sibling). Render
the root first ([chapter 7](07-overriding-blocks.md#the-rules)).

### The light/dark switch block is invisible

Your override of `scheme-toggle` dropped `data-magna-scheme-toggle`, or the `hidden` attribute
that the behaviour removes. `magna:theme:check` warns about the first.

### `magna:theme:check` flags a comment

The audit reads the raw source, comments included. Rephrase the comment so it doesn't contain
`<script>` or an unescaped echo.

### Demo import "skipped" everything

The import only adds: pages whose slug already exists, menus that already have items and patterns
whose name is taken are all left alone. On a scratch site, `php artisan magna:site:sync <file>`
applies a kit as an upsert.

### The header part from my kit doesn't appear

It must be published, `kind: "part"`, with slug `header` (or `footer`). A kit cannot set a part's
role, so the slug is what makes it the site's chrome. Also check that the page didn't choose
*no header* in its settings.

---

Back to the **[guide index](README.md)**.
