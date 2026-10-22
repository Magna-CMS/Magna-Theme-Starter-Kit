# 9. Theme addons

A **theme addon** styles one plugin's blocks inside a theme it does not own. A shop plugin's
product cards in the Starter theme, say, or a booking widget in *every* theme.

```json
{
    "name": "acme/starter-shop-kit",
    "displayName": "Shop kit for Starter",
    "version": "1.0.0",
    "type": "magna-theme-addon",
    "extends": "acme/starter",
    "pairsWith": ["acme/shop"],
    "priority": 0,
    "compat": { "magna": "^1.6.2" }
}
```

| Key | Meaning |
|---|---|
| `type` | `magna-theme-addon` |
| `extends` | The host theme's name, or `"*"` for any theme. |
| `pairsWith` | The plugins whose blocks this addon may restyle. Required, at least one. |
| `priority` | Tie-break between addons that both apply (higher wins). |

Scaffold one on a site with:

```bash
php artisan magna:theme-addon:make acme/starter-shop-kit --extends=acme/starter --pairs-with=acme/shop
```

## What an addon may contain

- **`views/blocks/<handle>.blade.php`**, but **only for blocks registered by a plugin in
  `pairsWith`**. An addon can never replace a core block's view, even with `extends: "*"`. That
  rule is enforced when the view is resolved, so a site-wide addon cannot rewrite every page's
  markup.
- **`tokens.json`**, *additively*. An addon can declare new tokens (`--color-chat-bubble`) but
  cannot change the host theme's: a key the theme already declares is ignored.
- **Nothing else.** No layout, no `views/system/`, no partials. `magna:theme:check` refuses an
  addon that ships them. An addon extends a theme's shell; it never replaces it.

Addons are never *activated*. They apply automatically whenever they are installed, compatible,
and their `extends` matches the active theme (or is `*`). When several apply, the order is: a
specific `extends` before `*`, then higher `priority`, then name.

## Addon or theme override?

If you own the theme, put plugin block views straight into the theme's `views/blocks/`, as Eva
does for Magna Blog's `blog-posts`. Addons are for the other direction: a **plugin author** who
wants their blocks to look native in popular themes, or a **site** that wants to restyle one
plugin without forking its theme.

---

Next: **[10. Rules and checks →](10-rules-and-checks.md)**
