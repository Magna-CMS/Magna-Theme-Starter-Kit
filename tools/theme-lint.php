<?php

declare(strict_types=1);

/**
 * theme-lint — check a Magna theme folder without a Magna install.
 *
 *   php tools/theme-lint.php path/to/theme            check one theme
 *   php tools/theme-lint.php path/to/theme --tokens   also print the CSS variables tokens.json compiles to
 *   php tools/theme-lint.php path/to/theme --strict   treat warnings as errors (for CI)
 *   php tools/theme-lint.php path/to/theme --json     machine-readable findings
 *
 * Two kinds of rule run here:
 *
 *   [core]  The same rules `php artisan magna:theme:check` applies on a real
 *           site (Magna Pages' ThemeAuditor) and the same manifest rules
 *           core's ThemeManifest enforces when it loads theme.json. A theme
 *           that fails one of these will be refused or misbehave on a site.
 *   [kit]   Extra checks from this starter kit for mistakes the platform
 *           does not catch: a layout that forgets part of its contract, a
 *           block override that drops core's classes, per-visitor output
 *           that the page cache would serve to everyone, demo content that
 *           will not import.
 *
 * `magna:theme:check` on a real install stays the authority. This tool is
 * for the edit-save loop and for CI, where no Magna install exists.
 *
 * Needs PHP 8.1+ and nothing else.
 */

const LINT_VERSION = '1.0.0';

// ── Rule tables ───────────────────────────────────────────────────────────
// Mirrored from magna-cms/pages src/Themes/ThemeAuditor.php. Keep in step.

/** Raw-echo variables the layout contract itself requires printing. */
const RAW_ECHO_ALLOWLIST = ['tokensCss', 'headerPartHtml', 'footerPartHtml', 'popupsHtml', 'mainHtml', 'sectionsHtml'];

/** Function names that have no business appearing anywhere in a view. */
const FORBIDDEN_CALLS = [
    'eval', 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen',
    'file_get_contents', 'file_put_contents', 'fopen', 'unlink', 'rmdir',
    'require', 'require_once', 'include', 'include_once', 'base64_decode',
];

/** "Views receive data; they never query." */
const FORBIDDEN_QUERY_PATTERNS = [
    '/(?<![@\w$>])Entry\s*::\s*type\s*\(/i' => 'Entry::type() in a view — views receive data; they never query. Resolve it on the payload (e.g. the pageUrl closure) instead.',
    '/::\s*query\s*\(/i' => '::query() in a view — views receive data; they never query.',
    '/(?<![@\w$>])DB\s*::/' => 'DB:: in a view — views receive data; they never query.',
    '/(?<![@\w$>])resolve\s*\(/i' => 'resolve() in a view — views receive data; they never pull from the container.',
    '/(?<![@\w$>])app\s*\(\s*[^)\s]/i' => 'app(...) in a view — views receive data; they never pull from the container.',
    '/(?<![@\w$>])app\s*\(\s*\)\s*->\s*(?!getLocale\b)\w+/i' => 'app()-> in a view — only app()->getLocale() (the lang-attribute idiom) is allowed; views never pull from the container.',
];

/**
 * [kit] Output that differs per visitor. Magna Pages caches a guest's page
 * as one HTML document and serves it to every other guest, so anything
 * here either leaks one visitor's state to the next or never updates.
 */
const PER_VISITOR_PATTERNS = [
    '/(?<![\w$>])auth\s*\(/i' => 'auth() in a view — guest pages are cached whole and served to everyone; per-visitor output does not belong in a theme.',
    '/->\s*user\s*\(\s*\)/i' => '->user() in a view — guest pages are cached whole and served to everyone; per-visitor output does not belong in a theme.',
    '/(?<![\w$>])session\s*\(/i' => 'session() in a view — guest pages are cached whole; session state would be served to the next visitor.',
    '/@csrf\b|csrf_token\s*\(|csrf_field\s*\(/i' => 'A CSRF token in a theme view — it would be cached and served to every visitor. Forms belong to plugins (e.g. Magna Forms), which handle this.',
    '/@auth\b|@guest\b/' => '@auth/@guest in a view — guest pages are cached whole; the branch taken for the first visitor is served to all.',
];

/** [kit] What a layout (with its partials) must contain to keep the contract. */
const LAYOUT_CONTRACT = [
    'tokensCss' => ['/\{!!\s*\$tokensCss\s*!!\}/', 'error', 'never prints {!! $tokensCss !!} — tokens.json, the Design panel and dark mode will not apply.'],
    'sections' => ["/magna-pages::partials\\.sections/", 'error', "never includes 'magna-pages::partials.sections' — builder pages would render empty."],
    'mainHtml' => ['/\$mainHtml/', 'error', 'never prints $mainHtml — plugin pages (blog posts, collections) would render empty.'],
    'headerPartHtml' => ['/\$headerPartHtml/', 'warning', 'ignores $headerPartHtml — a header the site designs in the builder will never appear.'],
    'footerPartHtml' => ['/\$footerPartHtml/', 'warning', 'ignores $footerPartHtml — a footer the site designs in the builder will never appear.'],
    'popupsHtml' => ['/\$popupsHtml/', 'warning', 'never prints $popupsHtml — popups and the cookie-consent banner will not appear.'],
    'body' => ['#</body>#i', 'error', 'has no </body> — the builder injects its canvas bridge before it, so the page could not be edited.'],
    'viewport' => ['/name\s*=\s*["\']viewport["\']/i', 'warning', 'has no <meta name="viewport"> — pages will not be responsive on phones.'],
    'lang' => ['/<html[^>]*\slang\s*=/i', 'warning', 'has no lang attribute on <html> — screen readers and search engines need it.'],
];

$results = [];
$exit = main($argv, $results);
exit($exit);

// ── Entry point ───────────────────────────────────────────────────────────

/**
 * @param  list<string>  $argv
 */
function main(array $argv, array &$findings): int
{
    $args = array_slice($argv, 1);
    $flags = array_values(array_filter($args, fn (string $a): bool => str_starts_with($a, '--')));
    $paths = array_values(array_filter($args, fn (string $a): bool => ! str_starts_with($a, '--')));

    if (in_array('--help', $flags, true) || in_array('-h', $args, true) || $paths === []) {
        fwrite(STDOUT, usage());

        return $paths === [] && ! in_array('--help', $flags, true) ? 2 : 0;
    }

    $strict = in_array('--strict', $flags, true);
    $asJson = in_array('--json', $flags, true);
    $printTokens = in_array('--tokens', $flags, true);

    $blocks = loadBlockContract();
    $failed = false;
    $report = [];

    foreach ($paths as $path) {
        $base = realpath($path);
        if ($base === false || ! is_dir($base)) {
            fwrite(STDERR, "Not a directory: {$path}\n");

            return 2;
        }

        $findings = [];
        $manifest = checkManifest($base, $findings, $blocks);
        $variables = checkTokens($base, $findings);
        $isAddon = ($manifest['type'] ?? null) === 'magna-theme-addon';

        foreach (viewFiles($base.'/views') as $file) {
            checkView($base, $file, $isAddon, $findings, $blocks);
        }

        if (! $isAddon) {
            checkLayout($base, $findings);
        }

        checkDemoContent($base, $manifest, $findings, $blocks);

        $errors = count(array_filter($findings, fn (array $f): bool => $f['level'] === 'error'));
        $warnings = count(array_filter($findings, fn (array $f): bool => $f['level'] === 'warning'));
        $failed = $failed || $errors > 0 || ($strict && $warnings > 0);

        $report[] = [
            'theme' => $manifest['name'] ?? basename($base),
            'path' => $base,
            'errors' => $errors,
            'warnings' => $warnings,
            'findings' => $findings,
            'variables' => $variables,
        ];
    }

    if ($asJson) {
        fwrite(STDOUT, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

        return $failed ? 1 : 0;
    }

    foreach ($report as $theme) {
        printReport($theme, $printTokens);
    }

    return $failed ? 1 : 0;
}

function usage(): string
{
    return <<<'TXT'
    theme-lint — check a Magna theme folder without a Magna install.

    Usage:
      php tools/theme-lint.php <theme-dir> [<theme-dir>…] [--tokens] [--strict] [--json]

    Options:
      --tokens   print the CSS variables tokens.json compiles to
      --strict   exit non-zero on warnings as well as errors (use in CI)
      --json     print findings as JSON

    [core] findings mirror `php artisan magna:theme:check` and core's theme.json rules.
    [kit]  findings are this starter kit's extra checks.

    TXT;
}

// ── theme.json ────────────────────────────────────────────────────────────

/**
 * @return array<string, mixed> the decoded manifest (empty when unreadable)
 */
function checkManifest(string $base, array &$findings, array $blocks): array
{
    $file = $base.'/theme.json';
    if (! is_file($file)) {
        add($findings, 'error', 'core', 'theme.json', 'Missing — a theme package is recognised by its theme.json.');

        return [];
    }

    $data = json_decode((string) file_get_contents($file), true);
    if (! is_array($data)) {
        add($findings, 'error', 'core', 'theme.json', 'Not valid JSON: '.json_last_error_msg().'.');

        return [];
    }

    $name = $data['name'] ?? null;
    if (! is_string($name) || ! isValidName($name)) {
        add($findings, 'error', 'core', 'theme.json', 'A theme needs a "name" in vendor/name form (lowercase letters, digits, "-" and "_"; one slash).');
    } else {
        // Magna installs a theme at themes/<vendor>/<name>/ and skips a
        // manifest whose name does not match the folder it sits in.
        [$vendor, $package] = explode('/', $name, 2);
        if (basename($base) !== $package || basename(dirname($base)) !== $vendor) {
            add($findings, 'info', 'kit', 'theme.json', "Installed, this folder must live at themes/{$name}/ (a symlink is fine) — Magna ignores a theme whose folder does not match its name.");
        }
    }

    $version = $data['version'] ?? null;
    if (! is_string($version) || $version === '') {
        add($findings, 'error', 'core', 'theme.json', 'Theme has no "version".');
    } elseif (preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?(\+[0-9A-Za-z.-]+)?$/', $version) !== 1) {
        add($findings, 'warning', 'kit', 'theme.json', "\"version\": \"{$version}\" is not semantic (MAJOR.MINOR.PATCH) — update checks compare versions.");
    }

    $compat = $data['compat'] ?? null;
    if (! is_array($compat) || ! is_string($compat['magna'] ?? null) || $compat['magna'] === '') {
        add($findings, 'warning', 'kit', 'theme.json', 'No "compat.magna" constraint — core assumes "^1.0". Say which Magna versions you tested against.');
    }
    if (is_array($compat) && array_key_exists('plugins', $compat)) {
        if (! is_array($compat['plugins'])) {
            add($findings, 'warning', 'core', 'theme.json', '"compat.plugins" must be an object of package name → version constraint; it is ignored.');
        } else {
            foreach ($compat['plugins'] as $package => $constraint) {
                if (! is_string($package) || ! isValidName($package) || ! is_string($constraint) || $constraint === '') {
                    add($findings, 'warning', 'core', 'theme.json', 'compat.plugins entry "'.(is_string($package) ? $package : '?').'" is not "vendor/name": "constraint" — it is dropped.');
                }
            }
        }
    }
    $isAddon = ($data['type'] ?? null) === 'magna-theme-addon';
    if (! $isAddon && ! isset($data['compat']['plugins']['magna-cms/pages'])) {
        add($findings, 'warning', 'kit', 'theme.json', 'No "compat.plugins" floor for "magna-cms/pages". Themes render through Magna Pages — declare the version you built against so older sites refuse activation loudly instead of rendering wrong.');
    }

    $type = $data['type'] ?? 'magna-theme';
    if (! in_array($type, ['magna-theme', 'magna-theme-addon'], true)) {
        add($findings, 'warning', 'core', 'theme.json', "\"type\": \"{$type}\" is not recognised — it is read as a full theme (\"magna-theme\").");
    }

    if ($isAddon) {
        $extends = $data['extends'] ?? null;
        if (! is_string($extends) || ($extends !== '*' && ! isValidName($extends))) {
            add($findings, 'error', 'core', 'theme.json', 'An addon needs "extends": a host theme name or "*".');
        }
        $pairs = is_array($data['pairsWith'] ?? null) ? array_filter($data['pairsWith'], 'is_string') : [];
        if ($pairs === []) {
            add($findings, 'error', 'core', 'theme.json', 'An addon needs "pairsWith": the plugins whose blocks it styles.');
        }
    }

    foreach (['displayName' => 'shown on the Themes page', 'description' => 'shown on the Themes page', 'author' => 'shown on the Themes page', 'license' => 'required by the store'] as $key => $why) {
        if (! is_string($data[$key] ?? null) || trim($data[$key]) === '') {
            add($findings, 'warning', 'kit', 'theme.json', "No \"{$key}\" ({$why}).");
        }
    }

    $screenshot = $data['screenshot'] ?? null;
    if (! $isAddon) {
        if (! is_string($screenshot) || $screenshot === '') {
            add($findings, 'warning', 'kit', 'theme.json', 'No "screenshot" — the Themes page shows a blank card.');
        } elseif (! is_file($base.'/'.$screenshot)) {
            add($findings, 'warning', 'kit', 'theme.json', "\"screenshot\": \"{$screenshot}\" does not exist in the theme folder.");
        }
    }

    if (array_key_exists('tags', $data) && (! is_array($data['tags']) || array_filter($data['tags'], fn ($t): bool => ! is_string($t)) !== [])) {
        add($findings, 'warning', 'core', 'theme.json', '"tags" must be a list of strings; anything else is dropped.');
    }

    if (array_key_exists('blockSeeds', $data)) {
        if (! is_array($data['blockSeeds']) || array_is_list($data['blockSeeds'])) {
            add($findings, 'warning', 'core', 'theme.json', '"blockSeeds" must be an object of block handle → field values; it is ignored.');
        } else {
            foreach ($data['blockSeeds'] as $handle => $seed) {
                if (! is_array($seed) || ($seed !== [] && array_is_list($seed))) {
                    add($findings, 'warning', 'core', 'theme.json', "blockSeeds.{$handle} must be an object of field → value (a JSON list is dropped).");

                    continue;
                }
                $definition = $blocks[$handle] ?? null;
                if ($definition === null) {
                    add($findings, 'warning', 'kit', 'theme.json', "blockSeeds.{$handle}: no core or Magna Pages block has this handle. Fine if a plugin provides it; otherwise the seed is never used.");

                    continue;
                }
                $known = array_column($definition['fields'], 'handle');
                foreach (array_keys($seed) as $field) {
                    if (! in_array($field, $known, true)) {
                        add($findings, 'warning', 'kit', 'theme.json', "blockSeeds.{$handle}.{$field}: the {$handle} block has no such field (fields: ".implode(', ', $known).').');
                    }
                }
            }
        }
    }

    return $data;
}

function isValidName(string $name): bool
{
    return preg_match('#^[a-z0-9]([a-z0-9_-]*[a-z0-9])?/[a-z0-9]([a-z0-9_-]*[a-z0-9])?$#', $name) === 1;
}

// ── tokens.json ───────────────────────────────────────────────────────────

/**
 * @return array<string, array{light: string, dark: ?string, token: string}> CSS variable => values
 */
function checkTokens(string $base, array &$findings): array
{
    $file = $base.'/tokens.json';
    if (! is_file($file)) {
        add($findings, 'warning', 'kit', 'tokens.json', 'No tokens.json — the Design panel will have nothing to edit and $tokensCss will be empty.');

        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    if (! is_array($decoded)) {
        add($findings, 'error', 'core', 'tokens.json', 'Not valid JSON.');

        return [];
    }

    $variables = [];
    foreach ($decoded as $category => $tokens) {
        if ($category === '$schema') {
            continue;
        }
        if (! is_string($category) || ! is_array($tokens)) {
            add($findings, 'warning', 'kit', 'tokens.json', "Top-level \"{$category}\" is not an object of tokens — it is skipped.");

            continue;
        }

        foreach ($tokens as $key => $definition) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            $where = "{$category}.{$key}";
            $value = is_array($definition) ? ($definition['value'] ?? null) : $definition;
            $dark = is_array($definition) ? ($definition['dark'] ?? null) : null;

            if (! is_string($value) && ! is_numeric($value)) {
                add($findings, 'error', 'kit', 'tokens.json', "Token {$where} has no string \"value\" — it compiles to nothing.");

                continue;
            }
            if (preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $key) !== 1) {
                add($findings, 'warning', 'kit', 'tokens.json', "Token key \"{$where}\" should be camelCase letters and digits — it becomes part of a CSS variable name.");
            }

            foreach (['value' => $value, 'dark' => $dark] as $which => $candidate) {
                if ($candidate === null) {
                    continue;
                }
                if (! is_string($candidate) && ! is_numeric($candidate)) {
                    add($findings, 'error', 'kit', 'tokens.json', "Token {$where}.{$which} must be a string.");

                    continue;
                }
                $string = (string) $candidate;
                if (str_contains($string, ';') || str_contains($string, '(')) {
                    add($findings, 'error', 'core', 'tokens.json', "Token {$where} ({$which}) contains ';' or '(' — refused by the injection filter at render, so it will never apply. No rgb(), hsl(), calc(), clamp(), var() or url() in tokens: do that arithmetic in your CSS.");
                } elseif (str_contains($string, '<') || str_contains($string, '>')) {
                    add($findings, 'error', 'core', 'tokens.json', "Token {$where} ({$which}) contains '<' or '>' — refused at render, so it will never apply.");
                } elseif (trim($string) === '') {
                    add($findings, 'error', 'core', 'tokens.json', "Token {$where} ({$which}) is empty — refused at render.");
                }
            }

            $name = '--'.($category === 'colors' ? 'color-'.kebab($key) : kebab($key));
            if (isset($variables[$name])) {
                add($findings, 'error', 'kit', 'tokens.json', "Tokens {$variables[$name]['token']} and {$where} both compile to {$name} — the second silently wins. Non-colour categories use the key alone, so keys must be unique across categories.");
            }
            if ($category === 'colors' && is_string($value) && preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) !== 1 && ! in_array(strtolower($value), ['transparent', 'currentcolor', 'inherit', 'white', 'black'], true)) {
                add($findings, 'warning', 'kit', 'tokens.json', "Colour {$where} is \"{$value}\" — use a hex value so the Design panel can show a swatch for it.");
            }
            $variables[$name] = ['light' => (string) $value, 'dark' => $dark === null ? null : (string) $dark, 'token' => $where];
        }
    }

    if ($variables !== [] && array_filter($variables, fn (array $v): bool => $v['dark'] !== null) === []) {
        add($findings, 'warning', 'kit', 'tokens.json', 'No token declares a "dark" value — the site cannot offer a dark reading. Add "dark" beside "value" for your colours.');
    }

    return $variables;
}

/** The same kebab-casing Magna Pages' ThemeTokens applies. */
function kebab(string $key): string
{
    $kebab = (string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $key);

    return strtolower(str_replace('_', '-', $kebab));
}

// ── views ─────────────────────────────────────────────────────────────────

/** @return list<string> */
function viewFiles(string $dir): array
{
    if (! is_dir($dir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && str_ends_with($file->getFilename(), '.blade.php')) {
            $files[] = $file->getPathname();
        }
    }
    sort($files);

    return $files;
}

function checkView(string $base, string $file, bool $isAddon, array &$findings, array $blocks): void
{
    $relative = str_replace('\\', '/', substr($file, strlen($base) + 1));
    $source = (string) file_get_contents($file);
    $isBlockView = str_starts_with($relative, 'views/blocks/');

    // ── [core] the ThemeAuditor rules, applied to the raw source exactly as
    //    the auditor does (comments included — so do not even mention a
    //    forbidden construct in a comment).
    if ($isAddon && ! $isBlockView) {
        add($findings, 'error', 'core', $relative, 'Addons may only ship block views — layouts and system views belong to the host theme.');
    }

    if (preg_match('/<\?(php|=)?/i', $source) === 1) {
        add($findings, 'error', 'core', $relative, 'Raw <?php — theme views are templates, never code. Use @php … @endphp for local variables.');
    }

    foreach (FORBIDDEN_CALLS as $call) {
        if (preg_match('/(?<![@\w])'.preg_quote($call, '/').'\s*\(/i', $source) === 1) {
            add($findings, 'error', 'core', $relative, "Call to {$call}() — no place in a view.");
        }
    }

    foreach (FORBIDDEN_QUERY_PATTERNS as $pattern => $message) {
        if (preg_match($pattern, $source) === 1) {
            add($findings, 'error', 'core', $relative, $message);
        }
    }

    if (preg_match_all('/\{!!(.*?)!!\}/s', $source, $matches) > 0) {
        foreach ($matches[1] as $expression) {
            $trimmed = trim($expression);
            $allowed = in_array(ltrim($trimmed, '$'), RAW_ECHO_ALLOWLIST, true) && str_starts_with($trimmed, '$');
            if (! $allowed && preg_match('/^\$block\[\'_resolved\'\]\[\'[a-z0-9_]+\'\](\s*\?\?\s*e\(.+\))?$/', $trimmed) === 1) {
                $allowed = true;
            }
            if (! $allowed) {
                add($findings, 'error', 'core', $relative, "Raw echo {!! {$trimmed} !!} — only the layout-contract HTML variables (\$".implode(', $', RAW_ECHO_ALLOWLIST).") and \$block['_resolved'][…] may print unescaped.");
            }
        }
    }

    if ($isBlockView && preg_match('/<script\b/i', $source) === 1) {
        add($findings, 'error', 'core', $relative, 'A <script> element in a block view — interactivity ships through curated mechanisms, not theme script. Put theme JavaScript in a layout partial.');
    }

    // ── [kit] per-visitor output the page cache would leak.
    $code = withoutComments($source);
    foreach (PER_VISITOR_PATTERNS as $pattern => $message) {
        if (preg_match($pattern, $code) === 1) {
            add($findings, 'warning', 'kit', $relative, $message);
        }
    }

    if (! $isBlockView) {
        return;
    }

    $handle = basename($relative, '.blade.php');
    $definition = $blocks[$handle] ?? null;

    if ($definition === null) {
        add($findings, 'warning', 'kit', $relative, "Overrides \"{$handle}\", which is not a core or Magna Pages block. Fine if a plugin provides it (a theme may restyle plugin blocks too); otherwise this view is never used.");

        return;
    }

    // ── [core] what an override drops from the default (auditOverride).
    foreach ($definition['hooks'] as $hook) {
        if (! str_contains(strtolower($code), strtolower($hook))) {
            add($findings, 'warning', 'core', $relative, "Overrides {$handle} without its `{$hook}` attribute — that is the hook the block's behaviour binds to, so whatever it drives will not run on this markup.");
        }
    }

    // ── [kit] the root element and core's classes.
    $missing = array_values(array_filter(
        $definition['rootClasses'],
        fn (string $class): bool => preg_match('/(?<![\w-])'.preg_quote($class, '/').'(?![\w-])/', $code) !== 1,
    ));
    if ($missing !== []) {
        add($findings, 'warning', 'kit', $relative, "Overrides {$handle} without core's root class(es) ".implode(', ', $missing).' — other themes\' CSS, addons, the builder and site custom CSS target them. Keep them on your root element.');
    }

    $first = firstElement($code);
    if ($first !== null && in_array($first, ['style', 'script', 'link', 'meta'], true)) {
        add($findings, 'warning', 'kit', $relative, "The first element is <{$first}>. Magna Pages stamps the builder's node marker and the editor's style class on a block's FIRST element — render your root element first and put any @once <style> after it.");
    }
}

/** Source with Blade and PHP block comments removed (as the auditor's override check does). */
function withoutComments(string $source): string
{
    $stripped = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);

    return (string) preg_replace('!/\*.*?\*/!s', '', $stripped);
}

/** Tag name of the first HTML element after comments and @php blocks, or null. */
function firstElement(string $code): ?string
{
    $code = (string) preg_replace('/@php\b(?!\s*\().*?@endphp/s', '', $code);
    $code = (string) preg_replace('/<!--.*?-->/s', '', $code);

    return preg_match('/<([a-zA-Z][a-zA-Z0-9-]*)\b/', $code, $m) === 1 ? strtolower($m[1]) : null;
}

// ── layout ────────────────────────────────────────────────────────────────

function checkLayout(string $base, array &$findings): void
{
    $layout = $base.'/views/system/layout.blade.php';
    if (! is_file($layout)) {
        add($findings, 'warning', 'core', 'views/system/layout.blade.php', 'No layout shell — pages render through the built-in fallback shell.');

        return;
    }

    // The contract may be kept by the layout OR by a theme partial it
    // includes (head, header, footer…), so read them together.
    $seen = [];
    $source = withoutComments(gatherIncludes($base, $layout, $seen));

    foreach (LAYOUT_CONTRACT as [$pattern, $level, $message]) {
        if (preg_match($pattern, $source) !== 1) {
            add($findings, $level, 'kit', 'views/system/layout.blade.php', 'The layout '.$message);
        }
    }

    if (preg_match('/@include\(\s*[\'"]magna-pages::partials\.sections[\'"]/', $source) === 1
        && preg_match('/\$mainHtml/', $source) === 1
        && preg_match('/@if\s*\(\s*!\s*empty\(\s*\$mainHtml\s*\)\s*\)/', $source) !== 1
        && preg_match('/@empty\s*\(\s*\$mainHtml/', $source) !== 1
        && preg_match('/@isset\s*\(\s*\$mainHtml/', $source) !== 1) {
        add($findings, 'warning', 'kit', 'views/system/layout.blade.php', 'Check that $mainHtml and the sections partial are alternatives (@if(!empty($mainHtml)) … @else … @endif) — a plugin page must print $mainHtml INSTEAD of the sections.');
    }
}

/**
 * The layout's source followed by every theme:: view it includes, recursively.
 *
 * @param  array<string, true>  $seen
 */
function gatherIncludes(string $base, string $file, array &$seen): string
{
    if (isset($seen[$file]) || ! is_file($file)) {
        return '';
    }
    $seen[$file] = true;
    $source = (string) file_get_contents($file);

    if (preg_match_all('/@include(?:If|When|Unless|First)?\(\s*[\'"]theme::([a-zA-Z0-9_.\/-]+)[\'"]/', $source, $matches) > 0) {
        foreach ($matches[1] as $view) {
            $source .= "\n".gatherIncludes($base, $base.'/views/'.str_replace('.', '/', $view).'.blade.php', $seen);
        }
    }

    return $source;
}

// ── demo content (starter/*.json) ─────────────────────────────────────────

function checkDemoContent(string $base, array $manifest, array &$findings, array $blocks): void
{
    $starter = $base.'/starter';
    $declared = $manifest['demoContent'] ?? null;

    if (is_array($declared)) {
        foreach ($declared as $entry) {
            if (! is_string($entry) || ! str_starts_with($entry, 'starter/') || ! str_ends_with($entry, '.json') || str_contains($entry, '..')) {
                add($findings, 'error', 'core', 'theme.json', 'demoContent entry "'.(is_string($entry) ? $entry : '?').'" must be a .json file inside starter/ — anything else is refused.');
            } elseif (! is_file($base.'/'.$entry)) {
                add($findings, 'error', 'kit', 'theme.json', "demoContent entry \"{$entry}\" does not exist.");
            }
        }
    }

    if (! is_dir($starter)) {
        return;
    }

    foreach (glob($starter.'/*.json') ?: [] as $file) {
        $relative = 'starter/'.basename($file);
        $size = filesize($file);
        if ($size !== false && $size > 25 * 1024 * 1024) {
            add($findings, 'error', 'core', $relative, 'Larger than 25 MB — the importer refuses it.');

            continue;
        }

        $kit = json_decode((string) file_get_contents($file), true);
        if (! is_array($kit)) {
            add($findings, 'error', 'core', $relative, 'Not valid JSON.');

            continue;
        }
        if (($kit['format'] ?? null) !== 'magna-site-kit' || ($kit['version'] ?? null) !== 1) {
            add($findings, 'error', 'core', $relative, 'Not a magna-site-kit v1 bundle — needs "format": "magna-site-kit" and "version": 1.');

            continue;
        }

        $slugs = [];
        foreach (['pages', 'templates'] as $key) {
            foreach (is_array($kit[$key] ?? null) ? $kit[$key] : [] as $i => $row) {
                $label = "{$key}[{$i}]";
                if (! is_array($row)) {
                    continue;
                }
                $slug = $row['slug'] ?? null;
                if (! is_string($slug) || $slug === '') {
                    add($findings, 'error', 'core', $relative, "{$label} has no \"slug\" — it is skipped on import.");

                    continue;
                }
                $label = "{$key} \"{$slug}\"";
                $slugs[$key][] = $slug;
                if (! is_string($row['title'] ?? null) || $row['title'] === '') {
                    add($findings, 'error', 'kit', $relative, "{$label} has no \"title\" — pages and templates require one.");
                }
                if (($row['status'] ?? null) !== 'published') {
                    add($findings, 'warning', 'kit', $relative, "{$label} is not \"status\": \"published\" — it imports as a draft and will not render.");
                }
                if ($key === 'templates') {
                    $kind = $row['kind'] ?? null;
                    if (! in_array($kind, ['part', 'page', 'collection', 'popup', 'system'], true)) {
                        add($findings, 'error', 'kit', $relative, "{$label} needs \"kind\": part, page, collection, popup or system.");
                    }
                    if ($kind === 'part' && ! in_array($slug, ['header', 'footer'], true)) {
                        add($findings, 'warning', 'kit', $relative, "{$label} is a part whose slug is not \"header\" or \"footer\". A site kit cannot set a part's role, so only those two slugs become the site's chrome automatically.");
                    }
                }
                checkDocument($row['blocks_data'] ?? [], $label, $relative, $findings, $blocks);
            }
        }

        foreach (is_array($kit['patterns'] ?? null) ? $kit['patterns'] : [] as $i => $pattern) {
            if (! is_array($pattern) || ! is_string($pattern['name'] ?? null) || ! is_string($pattern['kind'] ?? null) || ! is_array($pattern['document'] ?? null)) {
                add($findings, 'error', 'core', $relative, "patterns[{$i}] needs \"name\", \"kind\" and a \"document\" object — it is skipped.");

                continue;
            }
            $document = $pattern['document'];
            checkDocument(array_is_list($document) ? $document : [$document], 'pattern "'.$pattern['name'].'"', $relative, $findings, $blocks);
        }

        $pageSlugs = $slugs['pages'] ?? [];
        foreach (is_array($kit['menus'] ?? null) ? $kit['menus'] : [] as $i => $menu) {
            if (! is_array($menu) || ! is_string($menu['handle'] ?? null) || ! is_string($menu['name'] ?? null) || ! is_array($menu['tree'] ?? null)) {
                add($findings, 'error', 'core', $relative, "menus[{$i}] needs \"handle\", \"name\" and a \"tree\" list — it is skipped.");

                continue;
            }
            checkMenuTree($menu['tree'], $menu['handle'], $pageSlugs, $relative, $findings);
        }

        $settings = is_array($kit['settings'] ?? null) ? $kit['settings'] : [];
        foreach (['home_page_slug', 'not_found_page_slug'] as $key) {
            $slug = $settings[$key] ?? null;
            if (is_string($slug) && $slug !== '' && ! in_array($slug, $pageSlugs, true)) {
                add($findings, 'warning', 'kit', $relative, "settings.{$key} is \"{$slug}\", but no page in this kit has that slug — it only works if the site already has one.");
            }
        }
    }
}

/**
 * @param  list<string>  $pageSlugs
 */
function checkMenuTree(array $tree, string $handle, array $pageSlugs, string $file, array &$findings): void
{
    foreach ($tree as $item) {
        if (! is_array($item)) {
            continue;
        }
        $label = is_string($item['label'] ?? null) ? $item['label'] : '?';
        if (($item['type'] ?? null) === 'page' && is_string($item['page_slug'] ?? null) && ! in_array($item['page_slug'], $pageSlugs, true)) {
            add($findings, 'warning', 'kit', $file, "Menu \"{$handle}\" item \"{$label}\" links page \"{$item['page_slug']}\", which this kit does not create.");
        }
        if (is_array($item['children'] ?? null)) {
            checkMenuTree($item['children'], $handle, $pageSlugs, $file, $findings);
        }
    }
}

/**
 * The structural rules Magna's PageTreeValidator applies on save.
 */
function checkDocument(mixed $document, string $label, string $file, array &$findings, array $blocks): void
{
    if (! is_array($document)) {
        add($findings, 'error', 'core', $file, "{$label}: blocks_data must be a list of sections.");

        return;
    }

    $ids = [];
    $note = function (string $id) use (&$ids, $label, $file, &$findings): void {
        if ($id === '') {
            return;
        }
        if (isset($ids[$id])) {
            add($findings, 'error', 'core', $file, "{$label}: duplicate node id \"{$id}\" — ids must be unique within a document.");
        }
        $ids[$id] = true;
    };

    foreach ($document as $s => $section) {
        if (! is_array($section)) {
            add($findings, 'error', 'core', $file, "{$label}: section #{$s} must be an object.");

            continue;
        }
        $sid = is_string($section['id'] ?? null) ? $section['id'] : '';
        if ($sid === '') {
            add($findings, 'error', 'core', $file, "{$label}: section #{$s} is missing an \"id\".");
        }
        $note($sid);
        $type = $section['type'] ?? 'section';
        if ($type === 'ref') {
            continue;
        }
        if ($type !== 'section') {
            add($findings, 'error', 'core', $file, "{$label}: section \"{$sid}\" has unknown type \"{$type}\".");

            continue;
        }

        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        $spans = 0;
        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }
            $spans += is_int($column['span'] ?? null) ? $column['span'] : 0;
            $note(is_string($column['id'] ?? null) ? $column['id'] : '');
            foreach (is_array($column['blocks'] ?? null) ? $column['blocks'] : [] as $block) {
                checkBlockNode($block, $label, $file, $findings, $blocks, $note);
            }
        }
        if ($columns !== [] && $spans !== 12) {
            add($findings, 'error', 'core', $file, "{$label}: section \"{$sid}\" column spans sum to {$spans}, must be 12.");
        }

        $overrides = $section['settings']['tokenOverrides'] ?? null;
        if (is_array($overrides)) {
            foreach ($overrides as $key => $value) {
                if (! is_string($value) || str_contains($value, ';') || str_contains((string) $key, ':') || str_contains((string) $key, ';')) {
                    add($findings, 'error', 'core', $file, "{$label}: section \"{$sid}\" tokenOverrides \"{$key}\" is not a plain name/value.");
                }
            }
        }
    }
}

function checkBlockNode(mixed $block, string $label, string $file, array &$findings, array $blocks, callable $note): void
{
    if (! is_array($block)) {
        return;
    }
    $note(is_string($block['id'] ?? null) ? $block['id'] : '');
    $handle = $block['block'] ?? null;
    if (! is_string($handle)) {
        add($findings, 'error', 'core', $file, "{$label}: a block has no \"block\" handle.");

        return;
    }
    $definition = $blocks[$handle] ?? null;
    if ($definition === null) {
        add($findings, 'warning', 'kit', $file, "{$label}: block \"{$handle}\" is not a core or Magna Pages block — it renders only where the plugin that provides it is enabled. Declare that plugin in compat.plugins.");
    } else {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        foreach ($definition['fields'] as $field) {
            if (($field['required'] ?? false) && ! array_key_exists($field['handle'], $data)) {
                add($findings, 'warning', 'kit', $file, "{$label}: {$handle} block \"".($block['id'] ?? '?')."\" has no \"{$field['handle']}\" (required).");
            }
            if (($field['type'] ?? '') === 'json' && array_key_exists($field['handle'], $data) && ! is_string($data[$field['handle']])) {
                add($findings, 'warning', 'kit', $file, "{$label}: {$handle}.{$field['handle']} is a json field — store it as a JSON STRING (core's view json_decode()s it), not as a raw array.");
            }
        }
    }
    foreach (is_array($block['children'] ?? null) ? $block['children'] : [] as $child) {
        checkBlockNode($child, $label, $file, $findings, $blocks, $note);
    }
}

// ── shared ────────────────────────────────────────────────────────────────

/** @return array<string, array{fields: list<array<string, mixed>>, rootClasses: list<string>, hooks: list<string>}> */
function loadBlockContract(): array
{
    $file = __DIR__.'/data/blocks.json';
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

    if (! is_array($data) || ! is_array($data['blocks'] ?? null)) {
        fwrite(STDERR, "warning: tools/data/blocks.json is missing — block checks are skipped.\n");

        return [];
    }

    $blocks = [];
    foreach ($data['blocks'] as $block) {
        $blocks[$block['handle']] = $block;
    }

    return $blocks;
}

function add(array &$findings, string $level, string $rule, string $file, string $message): void
{
    $findings[] = ['level' => $level, 'rule' => $rule, 'file' => $file, 'message' => $message];
}

function printReport(array $theme, bool $printTokens): void
{
    $tty = function_exists('posix_isatty') && posix_isatty(STDOUT);
    $paint = fn (string $code, string $text): string => $tty ? "\033[{$code}m{$text}\033[0m" : $text;

    fwrite(STDOUT, $paint('1', $theme['theme']).'  '.$paint('2', $theme['path']).PHP_EOL);

    if ($theme['errors'] === 0 && $theme['warnings'] === 0) {
        fwrite(STDOUT, '  '.$paint('32', '✔ clean').PHP_EOL);
    }

    foreach ($theme['findings'] as $f) {
        $tag = match ($f['level']) {
            'error' => $paint('31', '✖ error'),
            'warning' => $paint('33', '▲ warn '),
            default => $paint('36', '• note '),
        };
        fwrite(STDOUT, "  {$tag} ".$paint('2', "[{$f['rule']}]")." {$f['file']}: {$f['message']}".PHP_EOL);
    }

    if ($printTokens && $theme['variables'] !== []) {
        fwrite(STDOUT, PHP_EOL.'  CSS variables from tokens.json:'.PHP_EOL);
        $width = max(array_map('strlen', array_keys($theme['variables'])));
        foreach ($theme['variables'] as $name => $v) {
            $dark = $v['dark'] === null ? '' : $paint('2', '   dark: '.$v['dark']);
            fwrite(STDOUT, '    '.str_pad($name, $width).'  '.$v['light'].$dark.PHP_EOL);
        }
    }

    fwrite(STDOUT, PHP_EOL.'  '.$theme['errors'].' error(s), '.$theme['warnings'].' warning(s)'.PHP_EOL.PHP_EOL);
}
