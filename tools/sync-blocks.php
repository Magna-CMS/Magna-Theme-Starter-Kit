<?php

declare(strict_types=1);

/**
 * sync-blocks — regenerate the kit's block data from Magna's own source.
 *
 *   php tools/sync-blocks.php --magna=../Magna [--pages=../Magna-Pages]
 *
 * Reads every core block definition (src/Magna/Blocks/blocks/*.json) and its
 * default view, plus Magna Pages' block views when --pages is given, and
 * writes two files:
 *
 *   tools/data/blocks.json      what theme-lint checks overrides against
 *   docs/reference/blocks.md    the block reference theme authors read
 *
 * Run it after a Magna or Magna Pages release and commit the diff: the
 * reference then changes exactly when the platform does, instead of
 * drifting. For maintainers of this kit; theme authors never need it.
 */

$options = getopt('', ['magna:', 'pages::', 'help']);

if (isset($options['help']) || ! isset($options['magna'])) {
    fwrite(STDOUT, "Usage: php tools/sync-blocks.php --magna=<path to Magna> [--pages=<path to Magna-Pages>]\n");
    exit(isset($options['help']) ? 0 : 2);
}

$magna = rtrim((string) $options['magna'], '/');
$pages = isset($options['pages']) && is_string($options['pages']) ? rtrim($options['pages'], '/') : null;
$kitRoot = dirname(__DIR__);

$definitionsDir = $magna.'/src/Magna/Blocks/blocks';
$viewsDir = $magna.'/src/Magna/Blocks/resources/views';

if (! is_dir($definitionsDir)) {
    fwrite(STDERR, "No block definitions at {$definitionsDir} — is --magna the Magna repository root?\n");
    exit(2);
}

/**
 * Magna Pages registers its blocks in PHP (PagesPlugin::blocks()), so their
 * schemas are restated here. Keep in step with that method; the views and
 * therefore the classes and hooks are still read from source.
 */
const PAGES_BLOCKS = [
    ['handle' => 'nav', 'label' => 'Navigation', 'category' => 'site', 'fields' => [
        ['handle' => 'menu', 'type' => 'select', 'label' => 'Menu', 'required' => true, 'note' => 'options: every menu handle on the site'],
    ]],
    ['handle' => 'loop', 'label' => 'Loop', 'category' => 'dynamic', 'fields' => [
        ['handle' => 'heading', 'type' => 'text', 'label' => 'Heading', 'required' => false],
        ['handle' => 'source', 'type' => 'select', 'label' => 'Data source', 'required' => true, 'note' => 'options: data sources plugins register'],
        ['handle' => 'limit', 'type' => 'number', 'label' => 'Items', 'required' => false, 'default' => 6],
        ['handle' => 'pagination', 'type' => 'select', 'label' => 'Pagination', 'required' => false, 'default' => 'none', 'options' => ['none' => 'None', 'pages' => 'Pages']],
    ]],
    ['handle' => 'embed', 'label' => 'Embed', 'category' => 'media', 'fields' => [
        ['handle' => 'url', 'type' => 'link', 'label' => 'Video URL', 'required' => true],
        ['handle' => 'caption', 'type' => 'text', 'label' => 'Caption', 'required' => false],
    ]],
    ['handle' => 'locale-switcher', 'label' => 'Language switcher', 'category' => 'site', 'fields' => []],
];

$blocks = [];

foreach (glob($definitionsDir.'/*.json') ?: [] as $file) {
    $definition = json_decode((string) file_get_contents($file), true);
    if (! is_array($definition) || ! is_string($definition['handle'] ?? null)) {
        continue;
    }
    $blocks[] = describe($definition, 'core', $viewsDir.'/blocks/'.$definition['handle'].'.blade.php', $viewsDir.'/block-behaviour/'.$definition['handle'].'.blade.php');
}

foreach (PAGES_BLOCKS as $definition) {
    $view = $pages === null ? null : $pages.'/resources/views/blocks/'.$definition['handle'].'.blade.php';
    $blocks[] = describe($definition, 'magna-cms/pages', $view, null);
}

usort($blocks, fn (array $a, array $b): int => [$a['category'], $a['handle']] <=> [$b['category'], $b['handle']]);

$data = [
    // No timestamp: the files change only when the platform does, which is
    // what lets the weekly drift check compare them byte for byte.
    'generatedBy' => 'tools/sync-blocks.php',
    'sources' => array_filter(['magna' => version($magna), 'magna-cms/pages' => $pages === null ? null : version($pages)]),
    'blocks' => $blocks,
];

@mkdir(__DIR__.'/data', 0755, true);
file_put_contents(__DIR__.'/data/blocks.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

@mkdir($kitRoot.'/docs/reference', 0755, true);
file_put_contents($kitRoot.'/docs/reference/blocks.md', markdown($data));

fwrite(STDOUT, 'Wrote '.count($blocks)." blocks to tools/data/blocks.json and docs/reference/blocks.md\n");

// ─────────────────────────────────────────────────────────────────────────

/**
 * @param  array<string, mixed>  $definition
 * @return array<string, mixed>
 */
function describe(array $definition, string $source, ?string $viewPath, ?string $behaviourPath): array
{
    $view = $viewPath !== null && is_file($viewPath) ? (string) file_get_contents($viewPath) : '';
    $code = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $view);
    $code = (string) preg_replace('!/\*.*?\*/!s', '', $code);
    $markup = (string) preg_replace('/@php\b(?!\s*\().*?@endphp/s', '', $code);

    $rootClasses = [];
    if (preg_match('/class="([^"]*)"/', $markup, $m) === 1) {
        // Static class names only: drop Blade echoes and modifier stems.
        $static = (string) preg_replace('/\{\{.*?\}\}|@if\(.*?\).*?@endif/s', ' ', $m[1]);
        foreach (preg_split('/\s+/', $static, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
            if (preg_match('/^[a-z][a-z0-9_-]*$/', $class) === 1 && ! str_ends_with($class, '-')) {
                $rootClasses[] = $class;
            }
        }
    }

    preg_match_all('/class="([^"]*)"/', $markup, $all);
    $classes = [];
    foreach ($all[1] as $attribute) {
        $static = (string) preg_replace('/\{\{.*?\}\}|@if\(.*?\).*?@endif/s', ' ', $attribute);
        foreach (preg_split('/\s+/', $static, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $class) {
            if (preg_match('/^magna-[a-z0-9_-]+$/', $class) === 1) {
                $classes[] = str_ends_with($class, '-') ? $class.'*' : $class;
            }
        }
    }

    preg_match_all('/data-magna-[a-z0-9-]+/i', $code, $hooks);
    preg_match_all("/\\\$block\\['_resolved'\\]\\['([a-z0-9_]+)'\\]/i", $code, $resolved);
    preg_match_all('/class="[^"]*\{\{\s*\$(\w+)\s*\}\}/', $markup, $modifiers);

    $fields = [];
    foreach (is_array($definition['fields'] ?? null) ? $definition['fields'] : [] as $field) {
        $options = $field['options'] ?? null;
        $entry = [
            'handle' => (string) $field['handle'],
            'type' => (string) ($field['type'] ?? 'text'),
            'label' => (string) ($field['label'] ?? ''),
            'required' => (bool) ($field['required'] ?? false),
        ];
        if (array_key_exists('default', $field)) {
            $entry['default'] = $field['default'];
        }
        if (is_array($options) && $options !== [] && ! array_is_list($options)) {
            $entry['options'] = array_map('strval', array_keys($options));
        }
        if (isset($field['note'])) {
            $entry['note'] = $field['note'];
        }
        $fields[] = $entry;
    }

    return [
        'handle' => (string) $definition['handle'],
        'label' => (string) ($definition['label'] ?? $definition['handle']),
        'category' => (string) ($definition['category'] ?? 'other'),
        'source' => $source,
        'container' => (bool) ($definition['container'] ?? false),
        'fields' => $fields,
        'rootClasses' => array_values(array_unique($rootClasses)),
        'classes' => array_values(array_unique($classes)),
        'hooks' => array_values(array_unique(array_map('strtolower', $hooks[0]))),
        'resolved' => array_values(array_unique($resolved[1])),
        'behaviourView' => $behaviourPath !== null && is_file($behaviourPath),
        'readsMedia' => str_contains($code, 'Media::'),
        'rendersEmpty' => preg_match('/^\s*@if\s*\(/', trim((string) preg_replace('/@php\b(?!\s*\().*?@endphp/s', '', $code))) === 1,
    ];
}

function version(string $repo): ?string
{
    $candidates = [$repo.'/magna.json', $repo.'/composer.json'];
    foreach ($candidates as $file) {
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (is_array($data) && is_string($data['version'] ?? null)) {
            return $data['version'];
        }
    }
    $provider = $repo.'/src/Magna/MagnaServiceProvider.php';
    if (is_file($provider) && preg_match("/VERSION\s*=\s*'([^']+)'/", (string) file_get_contents($provider), $m) === 1) {
        return $m[1];
    }

    return null;
}

/** @param array<string, mixed> $data */
function markdown(array $data): string
{
    $sources = [];
    foreach ($data['sources'] as $package => $version) {
        $sources[] = "`{$package}` {$version}";
    }

    $out = "<!-- Generated by tools/sync-blocks.php — do not edit by hand. -->\n\n";
    $out .= "# Block reference\n\n";
    $out .= 'Every block a theme can style or override, generated from the platform\'s own source'
        .($sources === [] ? '' : ' ('.implode(', ', $sources).')').".\n\n";
    $out .= "For each block:\n\n";
    $out .= "- **Fields** — what an editor fills in. In an overriding view they arrive as `\$block['data']['<field>']`. Treat every one as optional and possibly stale: validate `select` values against the listed options before they reach an attribute.\n";
    $out .= "- **Root classes** — the classes on the block's outermost element. An override should keep them.\n";
    $out .= "- **All classes** — everything core's markup carries, i.e. what your CSS can target without overriding anything. `--*` marks a modifier whose suffix is a field value (e.g. `magna-heading--*` → `magna-heading--center`).\n";
    $out .= "- **Resolved** — server-prepared values in `\$block['_resolved'][…]`. The only values a block view may print unescaped are sanitized `_resolved` fields.\n";
    $out .= "- **Behaviour hooks** — `data-magna-*` attributes core's script binds to. An override must keep them.\n\n";
    $out .= "Field types: `text`, `textarea`, `url`/`link`, `select`, `number`, `boolean`, `media` (a media-library id), `icon`, `richtext` (sanitized HTML — print `_resolved`), `json` (a JSON **string**; `json_decode` it), `repeater` (an array of rows).\n\n";

    $groups = [];
    foreach ($data['blocks'] as $block) {
        $groups[$block['category']][] = $block;
    }

    $out .= "## Contents\n\n";
    foreach ($groups as $category => $blocks) {
        $links = array_map(fn (array $b): string => '[`'.$b['handle'].'`](#'.$b['handle'].')', $blocks);
        $out .= '- **'.ucfirst($category).'** — '.implode(' · ', $links)."\n";
    }
    $out .= "\n";

    foreach ($groups as $category => $blocks) {
        $out .= '## '.ucfirst($category)."\n\n";
        foreach ($blocks as $block) {
            $out .= "### {$block['handle']}\n\n";
            $source = $block['source'] === 'core' ? 'Magna core' : 'Magna Pages';
            $out .= "**{$block['label']}** · from {$source} · override at `views/blocks/{$block['handle']}.blade.php`\n\n";

            if ($block['fields'] !== []) {
                $out .= "| Field | Type | Default | Options / notes |\n|---|---|---|---|\n";
                foreach ($block['fields'] as $field) {
                    $default = array_key_exists('default', $field) ? '`'.json_encode($field['default'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'`' : '';
                    $notes = isset($field['options']) ? implode(', ', array_map(fn (string $o): string => $o === '' ? '`""`' : "`{$o}`", $field['options'])) : '';
                    if (isset($field['note'])) {
                        $notes = trim($notes.' '.$field['note']);
                    }
                    if ($field['type'] === 'json' && preg_match('/\((.+)\)/', $field['label'], $m) === 1) {
                        $notes = trim($notes.' '.str_replace('|', '\\|', $m[1]));
                    }
                    $required = $field['required'] ? ' **required**' : '';
                    $out .= "| `{$field['handle']}`{$required} | {$field['type']} | {$default} | {$notes} |\n";
                }
                $out .= "\n";
            } else {
                $out .= "No fields.\n\n";
            }

            if ($block['rootClasses'] !== []) {
                $out .= '- Root classes: '.implode(' ', array_map(fn (string $c): string => "`{$c}`", $block['rootClasses']))."\n";
            }
            $others = array_values(array_diff($block['classes'], $block['rootClasses']));
            if ($others !== []) {
                $out .= '- All classes: '.implode(' ', array_map(fn (string $c): string => "`{$c}`", $others))."\n";
            }
            if ($block['resolved'] !== []) {
                $out .= '- Resolved: '.implode(', ', array_map(fn (string $r): string => "`\$block['_resolved']['{$r}']`", $block['resolved']))."\n";
            }
            if ($block['hooks'] !== []) {
                $out .= '- Behaviour hooks (keep in an override): '.implode(', ', array_map(fn (string $h): string => "`{$h}`", $block['hooks']))."\n";
            }
            if ($block['behaviourView']) {
                $out .= "- Ships a behaviour view (`magna::block-behaviour.{$block['handle']}`) that renders beside whichever view wins, so an override keeps the behaviour as long as it keeps the hooks.\n";
            }
            if ($block['container']) {
                $out .= "- Container: receives its children already rendered as `\$childrenHtml` — print it unescaped exactly where the children go.\n";
            }
            if ($block['readsMedia']) {
                $out .= "- Looks up media-library files inside core's view. Prefer styling it with CSS over overriding it: an override would have to repeat that lookup.\n";
            }
            $out .= "\n";
        }
    }

    return $out;
}
