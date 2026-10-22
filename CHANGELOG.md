# Changelog

All notable changes to the Magna Theme Starter Kit are recorded here. The kit follows
[semantic versioning](https://semver.org); the Starter theme's own version is in
`starter-theme/theme.json`.

## [1.0.0] - 2026-10-22

First release.

### Added

- **Starter theme** (`acme/starter` 1.0.0): layout with fallback header and footer, 25 design
  tokens with dark values, a token-only stylesheet covering every core and Magna Pages block,
  block overrides for `callout` and `features`, demo content (3 pages, 2 menus, 3 patterns) and
  an optional builder-editable header and footer kit. Verified clean by `magna:theme:check` and
  rendered on Magna CMS 1.7.0 with Magna Pages 0.6.0-alpha.
- **Developer guide**: 13 chapters in `docs/`.
- **Block reference**: `docs/reference/blocks.md`, generated from Magna 1.7.0 and Magna Pages
  0.6.0-alpha.
- **Tools**: `theme-lint`, `new-theme`, `package`, `sync-blocks`.
- **JSON Schemas** for `theme.json`, `tokens.json` and site kits.
- **CI**: lint, scaffold, package and schema validation on every push; a weekly drift check
  against Magna and Magna Pages.

[1.0.0]: https://github.com/Magna-CMS/Magna-Theme-Starter-Kit/releases/tag/v1.0.0
