# 2. Quick start

By the end of this page you will have a local Magna site running the Starter theme with its demo
content, and your own copy of the theme to change.

You need **PHP 8.3+** and **Composer**. The theme itself needs no Node.js and no build step.

## 1. A local Magna site with Magna Pages

Follow Magna's own [Quick Start](https://github.com/Magna-CMS/Magna#-quick-start): clone, run
`composer install`, then complete the browser installer. SQLite is fine for theme work. Then add
Magna Pages:

```bash
composer require magna-cms/pages
php artisan magna:plugin:enable magna-cms/pages
php artisan migrate
```

> **Working from a clone of Magna Pages instead of Composer?** Put it at
> `plugins-dev/magna-cms/pages/` and add a path repository to the site's `composer.json`
> (`{"type": "path", "url": "plugins-dev/magna-cms/pages"}`). Outside production, Magna
> discovers plugins in `plugins-dev/` only when they are wired in this way. Then run
> `magna:plugin:enable` as above.

Hand the site's root (`/`) to your pages, so the admin panel moves to `/admin`:

```bash
php artisan magna:panel:path --admin
```

(The same switch is in **Pages → Site settings**.)

## 2. Install the Starter theme

Clone this kit next to your site and link the theme into the site's `themes/` folder. The folder
path must match the theme's name, `acme/starter`:

```bash
git clone https://github.com/Magna-CMS/Magna-Theme-Starter-Kit.git
mkdir -p my-magna-site/themes/acme
ln -s "$(pwd)/Magna-Theme-Starter-Kit/starter-theme" my-magna-site/themes/acme/starter
```

A symlink means edits in the kit show up on the site immediately. On Windows, copy the folder
instead, or use `mklink /D`.

Check it the way a site does:

```bash
cd my-magna-site
php artisan magna:theme:check acme/starter
# acme/starter — clean
```

## 3. Activate it and import the demo content

Open **Admin → Themes**. Starter appears under *Installed*. Choose **Activate**, then **Import
demo content**. This adds a home page, an About page, a 404 page, two menus and three section
patterns. It only ever *adds*: anything your site already has (a page with the same slug, a menu
that has items) is left alone.

> **Import demo content** needs Magna CMS 1.7.0+ with Magna Pages 0.6.0+. On older versions,
> apply the kit from the command line instead. `magna:site:sync` *updates* existing pages with the
> same slug, so use it on a scratch site:
> `php artisan magna:site:sync themes/acme/starter/starter/demo.json`

Visit `http://localhost:8000/`. You should see this:

![The Starter theme's home page](../art/home-light.jpg)

Switch your system to dark mode, or use the moon button in the header. The whole palette changes.

## 4. Change one thing

Open `starter-theme/tokens.json` and change `colors.primary.value` from `#3b5bdb` to `#0f766e`.
Reload. Every button, link, focus ring, stat rule and the brand band follow, because the
stylesheet only ever reads `var(--color-primary)`.

> **Seeing the old version?** Magna Pages caches each page's full HTML for *guests* for up to an
> hour. While you work, stay signed in to the admin, since signed-in visitors always get a fresh
> render. Or flush the page cache: saving **Pages → Site settings** flushes it, and so does
> re-activating the theme.

Now open the page in the builder (**Pages → Home → Open builder**). The canvas is your theme.
Select a section and look at **Advanced → CSS class**. Typing `starter-band--muted` there uses the
theme's band vocabulary ([chapter 6](06-styling-blocks.md)).

## 5. Make your own theme

Don't edit Starter in place. Copy it under your own name:

```bash
php tools/new-theme.php yourname/aurora ../aurora --display="Aurora" --author="Your Name"
ln -s "$(realpath ../aurora)" my-magna-site/themes/yourname/aurora
```

The script renames the manifest and switches the `starter-` class prefix to `aurora-`. Then work
in this order. Each step is a chapter of this guide:

1. **Tokens** ([4](04-design-tokens.md)): your palette, type and spacing. This is most of a theme's identity.
2. **Layout** ([5](05-layout.md)): your header, footer and `<head>`.
3. **CSS** ([6](06-styling-blocks.md)): the stylesheet partial, one block family at a time.
4. **Overrides** ([7](07-overriding-blocks.md)): only where CSS truly cannot reach.
5. **Demo content** ([8](08-demo-content.md)): build it in the builder, export it, ship it.

Run `php tools/theme-lint.php ../aurora` whenever you save. It needs no site and runs in under a
second.

---

Next: **[3. Theme anatomy →](03-theme-anatomy.md)**
