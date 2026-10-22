<?php

declare(strict_types=1);

/**
 * new-theme — copy the Starter theme into a new, renamed theme.
 *
 *   php tools/new-theme.php <vendor/name> <target-dir> [--display="Aurora"] [--author="Your Name"]
 *
 * Example:
 *
 *   php tools/new-theme.php acme/aurora ~/code/aurora --display="Aurora" --author="Acme Studio"
 *
 * What it changes in the copy:
 *
 *   • theme.json — name, displayName, version (0.1.0), author, description;
 *     homepage and the demoContent list are kept/cleared sensibly
 *   • the CSS/JS prefix "starter-" → "<name>-" in views/ and starter/*.json
 *     (class names, data attributes, section classes, demo node ids)
 *
 * What it leaves for you: tokens.json (your palette), screenshot.png, the
 * demo content's words, and the README of your theme.
 *
 * Needs PHP 8.1+ and nothing else.
 */

$args = array_slice($argv, 1);
$positional = array_values(array_filter($args, fn (string $a): bool => ! str_starts_with($a, '--')));
$options = [];
foreach ($args as $arg) {
    if (preg_match('/^--([a-z]+)=(.*)$/', $arg, $m) === 1) {
        $options[$m[1]] = $m[2];
    }
}

if (count($positional) !== 2) {
    fwrite(STDERR, "Usage: php tools/new-theme.php <vendor/name> <target-dir> [--display=\"Name\"] [--author=\"You\"]\n");
    exit(2);
}

[$name, $target] = $positional;

if (preg_match('#^[a-z0-9]([a-z0-9_-]*[a-z0-9])?/[a-z0-9]([a-z0-9_-]*[a-z0-9])?$#', $name) !== 1) {
    fwrite(STDERR, "\"{$name}\" is not a valid theme name. Use lowercase vendor/name, e.g. acme/aurora.\n");
    exit(2);
}

[$vendor, $package] = explode('/', $name, 2);
$prefix = preg_replace('/[^a-z0-9-]/', '-', $package);

if ($prefix === 'starter') {
    fwrite(STDERR, "Pick a name other than \"starter\" — the copy would keep the reference theme's prefix.\n");
    exit(2);
}

$source = dirname(__DIR__).'/starter-theme';
if (file_exists($target) && (new FilesystemIterator($target))->valid()) {
    fwrite(STDERR, "{$target} already exists and is not empty.\n");
    exit(2);
}

copyTree($source, $target);

// ── theme.json ──
$manifestPath = $target.'/theme.json';
$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$display = $options['display'] ?? ucwords(str_replace(['-', '_'], ' ', $package));
$manifest['name'] = $name;
$manifest['displayName'] = $display;
$manifest['description'] = "{$display} — a theme for Magna Pages.";
$manifest['version'] = '0.1.0';
$manifest['author'] = $options['author'] ?? $vendor;
unset($manifest['homepage']);
$manifest['tags'] = [];
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

// ── README ── Starter's describes Starter; a new theme gets its own skeleton,
// with the band-class table every theme README should keep.
file_put_contents($target.'/README.md', <<<MD
# {$display}

{$display} is a theme for [Magna Pages](https://github.com/Magna-CMS/Magna-Pages).

![{$display}](screenshot.png)

## Section classes

Type these into a section's **Advanced → CSS class** in the builder. They combine.

| Class | Effect |
|---|---|
| `{$prefix}-band--muted` | Surface-coloured background |
| `{$prefix}-band--brand` | Brand-colour background, light text, inverted buttons |
| `{$prefix}-band--center` | Centred headings, text and actions |
| `{$prefix}-band--narrow` | Content held to the reading width |
| `{$prefix}-band--tight` | Half the usual vertical space |

## Tokens

Every colour, font and width is a token in `tokens.json`; retune them from the builder's
**Design** panel. List their roles here.

## Requirements

See `compat` in `theme.json`.

MD);

// ── prefix ──
$changed = 0;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (! str_ends_with($path, '.blade.php') && ! str_starts_with(substr($path, strlen($target)), '/starter/')) {
        continue;
    }
    $contents = (string) file_get_contents($path);
    // starter-header, data-starter-scheme, --starter-shadow, starter-sec-1 …
    $updated = (string) preg_replace('/(?<![a-z0-9_])starter-(?=[a-z])/', $prefix.'-', $contents);
    if ($updated !== $contents) {
        file_put_contents($path, $updated);
        $changed++;
    }
}

fwrite(STDOUT, <<<TXT
Created {$name} in {$target} ({$changed} files re-prefixed "starter-" → "{$prefix}-").

Next:
  1. Link it into a Magna site:   ln -s "\$(realpath {$target})" /path/to/magna/themes/{$vendor}/{$package}
  2. Activate it:                 Admin → Themes → {$display} → Activate
  3. Make it yours:               edit tokens.json first — every colour, font and width lives there
  4. Check it as you go:          php tools/theme-lint.php {$target}
                                  php artisan magna:theme:check {$name}   (on the site)
  5. Replace screenshot.png and the demo content's words before you ship.

TXT);

function copyTree(string $from, string $to): void
{
    if (! is_dir($to) && ! mkdir($to, 0755, true) && ! is_dir($to)) {
        throw new RuntimeException("Could not create {$to}");
    }
    foreach (new FilesystemIterator($from, FilesystemIterator::SKIP_DOTS) as $item) {
        $destination = $to.'/'.$item->getFilename();
        if ($item->isDir()) {
            copyTree($item->getPathname(), $destination);
        } else {
            copy($item->getPathname(), $destination);
        }
    }
}
