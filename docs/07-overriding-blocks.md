# 7. Overriding block views

A file at `views/blocks/<handle>.blade.php` **replaces** the view of the block with that handle.
The whole view, not a part of it. This is the most powerful tool a theme has, and the easiest one
to misuse. Use it only when CSS cannot do the job.

| Situation | Use |
|---|---|
| Colours, spacing, typography, layout of a block | **CSS** ([chapter 6](06-styling-blocks.md)) |
| Hide part of a block | **CSS** |
| Add decorative markup: an icon per callout tone, a wrapper for a card layout | **Override** |
| Change semantics: render a "level 6 heading" as an eyebrow `<p>` | **Override** |
| Restyle a plugin's listing into your design system (Eva's `blog-posts`) | **Override** |

Starter ships two worked examples:

- [`callout.blade.php`](../starter-theme/views/blocks/callout.blade.php) handles **scalar data**
  (`body`, `heading`, `tone`) and adds a per-tone SVG icon.
- [`features.blade.php`](../starter-theme/views/blocks/features.blade.php) handles **list data**
  (a `json` field) and adds a body wrapper while skipping empty items.

## How resolution works

For each block, Magna Pages picks the first view that exists:

```
1. a theme addon's view      only for blocks from a plugin the addon pairsWith (chapter 9)
2. theme::blocks.<handle>    your override
3. magna::blocks.<handle>    the default: core's, or the plugin's that registered the block
```

Then it always includes `magna::block-behaviour.<handle>`, if core ships one, *beside* whichever
view won. Behaviour (scripts) is the block's. Markup is yours. You can replace the markup, but you
cannot replace or drop the behaviour.

A theme may override **core blocks, Magna Pages blocks and other plugins' blocks**. The handle
is the file name. A view for a handle that no enabled plugin provides is never used.

## What the view receives

| Variable | Contents |
|---|---|
| `$block['data']` | What the editor entered, keyed by field handle. **Every key is optional and may be stale**: a document can predate today's schema. |
| `$block['_resolved']` | Server-prepared values, **only for blocks that have a resolver** (`text`, `entries`, `nav`, `loop`, `embed`…). Absent otherwise, so always use `?? …`. |
| `$block['id']`, `$block['block']`, `$block['settings']` | The node's id, handle and settings. |
| `$definition` | The block's schema: fields, labels, options. |
| `$childrenHtml` | Container blocks only: the children, already rendered. Print with `{!! $childrenHtml !!}`. |
| `$localeAlternates` | `locale => URL` for the page, as in the layout. |

Each block's fields, options and resolved keys are listed in the
[block reference](reference/blocks.md).

## The rules

Every one of these is either enforced by `magna:theme:check` or breaks something visible.

**1. Render the root element first.** The builder stamps its `data-magna-node` marker, and Magna
Pages merges the class carrying the editor's per-block styles, into the **first element** of your
output. If a `<style>`, a comment-wrapped `<link>` or a second sibling comes first, the wrong
element becomes the block. Put any `@once <style>` *after* the root element. Render exactly one
root.

**2. Keep core's classes on the root.** `magna-block magna-block--<handle>` plus the block's own
name (`magna-callout`) and modifiers (`magna-callout--{tone}`). Other CSS targets them: Magna's
own, addons', and sites' custom CSS. `theme-lint` warns when one is missing.

**3. Keep behaviour hooks.** Some blocks' behaviour binds to `data-magna-*` attributes. The light/dark
switch needs `data-magna-scheme-toggle` on its button, with the `hidden` attribute that the
behaviour removes, plus the `__light`/`__dark` icon spans. Drop the hook and the control silently
never appears. `magna:theme:check` warns. Hooks are listed per block in the reference.

**4. Validate before you print into an attribute.** A `select` value goes into a class name.
Check it against the field's options, as core does:

```blade
@php
    $tone = (string) ($block['data']['tone'] ?? 'info');
    $tone = in_array($tone, ['info', 'success', 'warning', 'danger'], true) ? $tone : 'info';
@endphp
<aside class="magna-block magna-block--callout magna-callout magna-callout--{{ $tone }}" role="note">
```

**5. Escape everything.** Print with `{{ }}`. The only unescaped output allowed in a block view is
a sanitized resolved field, in exactly this form:

```blade
{!! $block['_resolved']['body'] ?? e($block['data']['body'] ?? '') !!}
```

**6. No script, no queries, no PHP.** No `<script>` in a block view (interactivity belongs to
core's behaviour views and your layout). No `<?php`, no `DB::`, `::query()`, `app(…)` or
`resolve()`. Use `@php … @endphp` only for local variables. → [chapter 10](10-rules-and-checks.md)

**7. Render nothing when there is nothing to show.** If the required field is empty, output
nothing, like core does. In the builder an empty block still gets a marker, so editors can select
it.

## Data shapes worth knowing

- **`json` fields are strings.** `features.items`, `stats.items`, `pricing.tiers`,
  `testimonials.items`, `team.members` and `logos.items` store a JSON *string*. Decode it, and
  accept an already-decoded array too:

  ```blade
  @php
      $raw = $block['data']['items'] ?? [];
      $items = is_array($raw) ? $raw : (json_decode(is_string($raw) ? $raw : '[]', true) ?: []);
  @endphp
  ```

- **`repeater` fields are arrays** (`faq.items`: `[{question, answer}]`).
- **`richtext` fields** (`text.body`) are raw HTML in `data`. Print the sanitized
  `_resolved` version, never `data`.
- **URLs**: pass editor-entered links through `\Magna\Blocks\Support\SafeUrl::sanitize()`, as core
  views do. It refuses `javascript:` and similar schemes.
- **Media-backed blocks** (`image`, `gallery`, `team`, `logos`, `logo`, `file`, `video`) look up
  the media library inside core's view. Prefer CSS for these: an override would have to repeat
  that lookup, and views are meant to receive data, not fetch it.

## Upgrades

An override is a copy of core's markup at one moment. When a core view changes (a new field, an
accessibility fix, a new hook), your copy does not. Keep overrides few, note in each file *why* it
exists, and diff against core's view when you update your `compat` floors.
`php tools/sync-blocks.php` regenerates the [block reference](reference/blocks.md) from new
sources, and its diff shows what changed.

---

Next: **[8. Demo content and editor defaults →](08-demo-content.md)**
