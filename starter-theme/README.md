# Starter

A small, readable reference theme for **Magna Pages**. It has one layout, 25 design tokens with
light and dark values, styles for every core and Magna Pages block, two example block overrides,
and demo content.

It exists to be **read and copied**. Every file is commented with *why*, not just *what*. Start
with the [developer guide](https://github.com/Magna-CMS/Magna-Theme-Starter-Kit/blob/main/docs/README.md).

![Starter, light](https://raw.githubusercontent.com/Magna-CMS/Magna-Theme-Starter-Kit/main/art/home-light.jpg)

## Files

| File | What it shows |
|---|---|
| [`theme.json`](theme.json) | A complete manifest: compat floors, demo content, block seeds |
| [`tokens.json`](tokens.json) | A role-named palette with dark values; type and spacing tokens |
| [`views/system/layout.blade.php`](views/system/layout.blade.php) | The layout contract, kept in ~60 lines |
| [`views/partials/head.blade.php`](views/partials/head.blade.php) | Title, description, hreflang, the pre-paint scheme script |
| [`views/partials/header.blade.php`](views/partials/header.blade.php) | Fallback header: menu, light/dark switch, a no-JS small-screen menu |
| [`views/partials/footer.blade.php`](views/partials/footer.blade.php) | Fallback footer: an optional second menu via `$menu('footer')` |
| [`views/partials/styles.blade.php`](views/partials/styles.blade.php) | The whole stylesheet, built only from tokens |
| [`views/partials/scripts.blade.php`](views/partials/scripts.blade.php) | The only JavaScript: the scheme switch and menu closing |
| [`views/blocks/callout.blade.php`](views/blocks/callout.blade.php) | Override with scalar data: a per-tone icon |
| [`views/blocks/features.blade.php`](views/blocks/features.blade.php) | Override with list (`json`) data |
| [`starter/demo.json`](starter/demo.json) | Demo content: three pages, two menus, three patterns |
| [`starter/chrome.json`](starter/chrome.json) | Optional: header and footer as builder-editable template parts |

## Section classes

Type these into a section's **Advanced → CSS class** in the builder. They combine.

| Class | Effect |
|---|---|
| `starter-band--muted` | Surface-coloured background |
| `starter-band--brand` | Brand-colour background, light text, inverted buttons |
| `starter-band--center` | Centred headings, text and actions |
| `starter-band--narrow` | Content held to the reading width |
| `starter-band--tight` | Half the usual vertical space |

## Tokens

| Variable | Role |
|---|---|
| `--color-bg`, `--color-surface` | Page background; cards and muted bands |
| `--color-text`, `--color-heading`, `--color-muted` | Body text; headings; secondary text |
| `--color-border` | Borders and dividers |
| `--color-primary`, `--color-primary-strong`, `--color-on-primary` | Brand colour; its hover; text on it |
| `--color-info`, `--color-success`, `--color-warning`, `--color-danger` | Callout tones |
| `--font-body`, `--font-heading`, `--font-mono` | Font stacks |
| `--font-size-base`, `--line-height`, `--heading-weight` | Body size and rhythm; heading weight |
| `--max-width`, `--reading-width`, `--gutter`, `--section-space` | Page width; text measure; side padding; vertical rhythm |
| `--radius`, `--radius-large` | Corners: controls; cards |

## Requirements

Magna CMS 1.6.2+ with Magna Pages 0.3.0-alpha+. *Import demo content* needs Magna CMS 1.7.0+ and
Magna Pages 0.6.0-alpha+.

## License

MIT, like the rest of this kit.
