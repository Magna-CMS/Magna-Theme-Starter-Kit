# 10. Rules and checks

Magna treats a theme as **data**: templates and tokens, never a way to run code. That is what lets
a site install a theme from a stranger without handing them the server, and it is why the rules
below exist. The Magna store's "checked" badge runs exactly this audit.

## `magna:theme:check`: the authority

On any site with Magna Pages enabled:

```bash
php artisan magna:theme:check acme/starter   # one theme
php artisan magna:theme:check                # every installed theme
```

It exits non-zero when it finds an **error**. **Warnings** point at contract drift worth a look.

### Errors: a theme may never ship these

| Rule | Why |
|---|---|
| `theme.json` missing, invalid JSON, bad `name`, no `version` | The theme cannot be identified. |
| A token value containing `;` or `(` | Refused at render, so the token can never apply ([chapter 4](04-design-tokens.md)). |
| `<?php` or `<?=` anywhere in a view | Views are templates, never code. Use `@php … @endphp` for local variables. |
| Calls to `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `file_get_contents`, `file_put_contents`, `fopen`, `unlink`, `rmdir`, `require`, `include` (and `_once`), `base64_decode` | No place in a view. Blade's `@include` is fine. |
| `Entry::type(`, `::query(`, `DB::`, `resolve(`, `app(…)`, `app()->…` | **Views receive data; they never query.** The one exception is `app()->getLocale()`, for the `lang` attribute. |
| Unescaped output other than `$tokensCss`, `$headerPartHtml`, `$footerPartHtml`, `$popupsHtml`, `$mainHtml`, `$sectionsHtml`, or `$block['_resolved']['…']` | Everything else must be escaped with `{{ }}`. |
| `<script>` in `views/blocks/` | Block interactivity ships through core's behaviour views. Theme JavaScript belongs in the layout. |
| An addon shipping anything outside `views/blocks/` | Addons extend a theme's shell; they never replace it. |

> ⚠️ The audit reads the **raw source, comments included**. A Blade comment that *mentions*
> `<script>` inside a block view, or an unescaped echo inside a comment, is flagged exactly like
> the real thing. Describe them in words.

### Warnings

| Rule | Why |
|---|---|
| No `views/system/layout.blade.php` (full themes) | Pages render in the bare fallback shell. |
| A block override drops a `data-magna-*` hook the default view carries | The block's behaviour binds to it and will not run on your markup ([chapter 7](07-overriding-blocks.md)). |
| A block override drops inline `<script>` the default still carries, and core has no behaviour view for that block yet | You must carry that behaviour yourself, in the layout. |

## `theme-lint`: the same rules, plus the kit's, with no site

[`tools/theme-lint.php`](../tools/theme-lint.php) needs only PHP 8.1+. Run it on every save, in a
pre-commit hook, and in CI:

```bash
php tools/theme-lint.php path/to/theme            # human-readable
php tools/theme-lint.php path/to/theme --tokens   # + the CSS variables your tokens compile to
php tools/theme-lint.php path/to/theme --strict   # warnings fail too (use in CI)
php tools/theme-lint.php path/to/theme --json     # for tooling
```

Every finding is tagged:

- **`[core]`**: the same rule `magna:theme:check` or core's manifest loader applies. Fix it, or
  the theme will be refused or misbehave on a site.
- **`[kit]`**: an extra check for mistakes the platform does not catch:

| Kit check | Catches |
|---|---|
| Layout contract | missing `$tokensCss`, sections partial, `$mainHtml`, `</body>`, header/footer parts, popups, viewport, `lang` |
| Per-visitor output | `auth()`, `@auth`, `session()`, `@csrf` in views, which the page cache would serve to every guest |
| Override shape | core root classes missing; a `<style>` rendered before the block's root element |
| Tokens | variable-name collisions across categories; non-hex colours; no dark values; `<` `>` and empty values |
| Manifest | folder/name mismatch, missing `compat.plugins` floor for Magna Pages, missing screenshot, unknown `blockSeeds` handles or fields |
| Demo content | spans ≠ 12, duplicate ids, unknown blocks, `json` fields stored as arrays, unpublished pages, menu links to pages the kit lacks |

`theme-lint` mirrors the platform's rules as of the versions in the [guide index](README.md). If
the two ever disagree, `magna:theme:check` on a real site is right. Please
[open an issue](https://github.com/Magna-CMS/Magna-Theme-Starter-Kit/issues).

## CI for your theme

Copy this workflow into your theme's repository as `.github/workflows/theme.yml`. It fetches the
lint tool and block data from this kit at a pinned tag, so your checks don't change under you:

```yaml
name: Theme
on: [push, pull_request]
jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/checkout@v4
        with:
          repository: Magna-CMS/Magna-Theme-Starter-Kit
          ref: v1.0.0
          path: .starter-kit
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: php .starter-kit/tools/theme-lint.php . --strict
```

---

Next: **[11. Quality checklist →](11-quality-checklist.md)**
