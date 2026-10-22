# Magna theme developer guide

Read these in order the first time; come back to any chapter on its own later. Each chapter
ends with what to read next.

| # | Chapter | You will learn |
|---|---|---|
| 1 | [How Magna Pages renders a theme](01-how-pages-renders.md) | The mental model: what a theme controls, what editors control, and the path a request takes |
| 2 | [Quick start](02-quick-start.md) | A local Magna site with the Starter theme running, in about fifteen minutes |
| 3 | [Theme anatomy](03-theme-anatomy.md) | Every file in a theme folder, what is required and what is optional |
| 4 | [Design tokens](04-design-tokens.md) | `tokens.json`, the CSS variables it becomes, dark mode and the Design panel |
| 5 | [The layout](05-layout.md) | `views/system/layout.blade.php` and every variable it receives |
| 6 | [Styling blocks with CSS](06-styling-blocks.md) | Page structure, section "bands", class names and how editor styles interact with yours |
| 7 | [Overriding block views](07-overriding-blocks.md) | When CSS is not enough: replacing a block's markup safely |
| 8 | [Demo content and editor defaults](08-demo-content.md) | `starter/*.json` site kits, patterns, `blockSeeds` |
| 9 | [Theme addons](09-addons.md) | Styling one plugin's blocks inside someone else's theme |
| 10 | [Rules and checks](10-rules-and-checks.md) | What `magna:theme:check` refuses, and why; using `theme-lint` and CI |
| 11 | [Quality checklist](11-quality-checklist.md) | Accessibility, performance, dark mode, responsive, builder — before every release |
| 12 | [Packaging and publishing](12-packaging-and-publishing.md) | Versions, compatibility, the zip, updates and the store |
| 13 | [Troubleshooting](13-troubleshooting.md) | "My theme doesn't show up", "my change isn't visible" and other common problems |

Reference:

- [Block reference](reference/blocks.md) — every block's fields, classes and behaviour hooks (generated from source)
- [`theme.json` schema](../schemas/theme.schema.json) · [`tokens.json` schema](../schemas/tokens.schema.json) · [site kit schema](../schemas/site-kit.schema.json)

The [Starter theme](../starter-theme) is the worked example for every chapter. Its files are
short and commented; when a chapter says "see Starter", open the file it names.

## Versions this guide describes

Written and verified against **Magna CMS 1.7.0** and **Magna Pages 0.6.0-alpha**. Magna Pages
is alpha software: its block schemas and the layout payload can still change between releases.
Every behaviour described here was checked against that source and against a running site; where
a feature needs a minimum version, the chapter says so.
