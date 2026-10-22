# 12. Packaging and publishing

## Versioning

Use [semantic versioning](https://semver.org). For a theme, the public interface is everything a
site or an editor builds on:

| Change | Version |
|---|---|
| Removing or renaming a **token** (site overrides of it stop applying) | **major** |
| Removing or renaming a **band class** (editors' sections lose their look) | **major** |
| Raising `compat.magna` or `compat.plugins` past what sites commonly run | **major** |
| New tokens, new band classes, new block styles, new demo patterns | minor |
| CSS fixes, accessibility fixes | patch |

## Compatibility

```json
"compat": {
    "magna": "^1.6.2",
    "plugins": { "magna-cms/pages": ">=0.3.0-alpha" }
}
```

- Set the floors to the **oldest** versions you actually tested on.
- Both are checked **when the theme is activated**. A site that later downgrades a plugin keeps
  the theme active, but cannot re-activate it.
- Raise the Magna Pages floor whenever you start relying on a newer Pages feature, such as a
  layout variable, a block field or a document setting. Without the floor, an older site renders
  your theme subtly wrong and nobody is told.

## The zip

```bash
php tools/package.php path/to/theme
# Packaged 13 files into dist/acme-starter-1.0.0.zip (111 KB)
# sha256 …
```

The tool runs `theme-lint` first and refuses to package a theme with errors. The archive holds one
top-level folder named after the package (`starter/`), which is what Magna's installer unwraps. It
leaves out `art/`, `.git`, `.github`, `node_modules`, `tests` and earlier zips. Magna's installer
refuses archives containing symlinks and archives over 500 MB uncompressed. The tool never adds a
symlink.

## Installing on a site

- **By hand:** extract or copy the folder to `themes/<vendor>/<name>/`, then activate it on the
  Themes page.
- **From the Magna store:** the site's Themes page lists store themes under *Browse*. A purchase
  or free claim installs the signed package into `themes/` and can import demo content in the same
  step.

Installing or updating through Magna stamps the theme's views fresh and flushes Magna Pages' page
cache, so the new version is served on the next request. If you copy files in by hand on a live
site, re-activate the theme afterwards to get the same effect.

## Updates

An update replaces the theme's files and nothing else:

- Site content is untouched. **Changed demo kits are not re-applied.** The Themes page tells the
  admin that the update ships site content and names the `magna:site:sync` command.
- Token overrides from the Design panel survive, as long as the token names still exist.
- Sections using your band classes keep them. Keep the classes working.

## Publishing to the Magna store

The store is how most sites will find your theme. Prepare:

- **Name**: `vendor/name`, where the vendor is yours. It is also the folder name and the update key.
- **Screenshot**: 1200×900, the theme's demo home page, light scheme.
- **Gallery art**: home light and dark, a content page, mobile. Keep these in `art/` in your
  repository; the zip leaves them out.
- **License**: an SPDX identifier in `theme.json`, and the license text in the package.
- **A clean audit**: the store's "checked" badge runs `magna:theme:check`.
- **A README** that documents your band classes and token roles, since editors and site owners
  will need both.

The store's submission process is run by Magna CMS. Check the Magna repository for current
submission instructions.

---

Next: **[13. Troubleshooting →](13-troubleshooting.md)**
