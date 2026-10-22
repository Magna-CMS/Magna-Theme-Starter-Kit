# Contributing

Thanks for helping theme developers. Corrections are the most valuable contribution: if anything
in the guide didn't match what you saw on a real site, that is a bug. Please report it.

## Reporting

- **Docs gap or error**: use the *Docs gap* issue template. Include the Magna and Magna Pages
  versions, what the guide said, and what actually happened.
- **`theme-lint` disagrees with `magna:theme:check`**: use the *Bug report* template and include
  the output of both. `magna:theme:check` is the authority. The lint tool is wrong until proven
  otherwise.
- **Security issues in Magna itself** go to the Magna CMS project, not here.

## Changing things

Before opening a pull request:

```bash
php tools/theme-lint.php starter-theme --strict
for f in tools/*.php; do php -l "$f"; done
```

### The Starter theme

- Keep it **small and readable**. Starter is a teaching tool. A feature belongs here only if most
  themes need it; everything else is a recipe or a separate reference theme ([ROADMAP](ROADMAP.md)).
- Every rule in `styles.blade.php` reads tokens. No raw colours.
- Comments explain *why*. Never mention `<script>` or an unescaped echo in a block view's comment:
  the platform audit reads comments too.
- If you change the look, regenerate the screenshots (`starter-theme/screenshot.png` and `art/`)
  from a real site.
- Bump `starter-theme/theme.json` `version` and note it in `CHANGELOG.md`.

### Demo content

Build it in the builder, export it with `php artisan magna:site:export`, and keep node ids
prefixed `starter-`. The lint tool checks spans, ids and field shapes.

### The guide

- One chapter, one topic. Link instead of repeating.
- State behaviour you have **verified** on a running site or in source, and name the version when a
  feature needs one.
- `docs/reference/blocks.md` is generated. Don't edit it by hand; run `tools/sync-blocks.php`.

### Tools

- PHP 8.1+, no dependencies. They must run on a laptop with only PHP installed.
- `theme-lint`'s `[core]` rules mirror Magna Pages' `ThemeAuditor` and core's `ThemeManifest`.
  When those change upstream, update the rule tables at the top of `tools/theme-lint.php` in the
  same pull request, and say which upstream commit you mirrored.

### Keeping up with Magna

When Magna or Magna Pages releases:

```bash
php tools/sync-blocks.php --magna=../Magna --pages=../Magna-Pages
git diff docs/reference/blocks.md tools/data/blocks.json
```

Review the diff. New fields or classes may need Starter styles, and new hooks may need a guide
update. The weekly *Drift* workflow opens this conversation for you by failing.

## Commit messages

Short imperative subject (`Style the new table footer`), and a body that says why.
