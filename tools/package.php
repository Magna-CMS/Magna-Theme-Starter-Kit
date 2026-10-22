<?php

declare(strict_types=1);

/**
 * package — build a distributable zip of a theme.
 *
 *   php tools/package.php <theme-dir> [--out=dist]
 *
 * Runs theme-lint first and refuses to package a theme with errors. The zip
 * holds ONE top-level folder named after the theme's package name
 * (acme/aurora → aurora/), which is the layout Magna's installer unwraps,
 * and leaves out what a site does not need: VCS folders, editor and OS
 * clutter, art/ (README images), node_modules, tests and earlier zips.
 *
 * Symlinks are skipped: Magna's installer refuses an archive that contains
 * one.
 *
 * Needs PHP 8.1+ with the zip extension.
 */

const EXCLUDE = ['.git', '.github', '.idea', '.vscode', 'node_modules', 'art', 'tests', 'dist', '.DS_Store', 'Thumbs.db', '.editorconfig', '.gitattributes', '.gitignore'];

$args = array_slice($argv, 1);
$positional = array_values(array_filter($args, fn (string $a): bool => ! str_starts_with($a, '--')));
$out = 'dist';
foreach ($args as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $out = substr($arg, 6);
    }
}

if (count($positional) !== 1) {
    fwrite(STDERR, "Usage: php tools/package.php <theme-dir> [--out=dist]\n");
    exit(2);
}

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "PHP's zip extension is required.\n");
    exit(2);
}

$base = realpath($positional[0]);
if ($base === false || ! is_file($base.'/theme.json')) {
    fwrite(STDERR, "{$positional[0]} is not a theme folder (no theme.json).\n");
    exit(2);
}

passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/theme-lint.php').' '.escapeshellarg($base), $lint);
if ($lint !== 0) {
    fwrite(STDERR, "theme-lint reported errors — fix them before packaging.\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($base.'/theme.json'), true);
[$vendor, $package] = explode('/', (string) $manifest['name'], 2);
$version = (string) $manifest['version'];

if (! is_dir($out) && ! mkdir($out, 0755, true) && ! is_dir($out)) {
    fwrite(STDERR, "Could not create {$out}\n");
    exit(1);
}

$zipPath = rtrim($out, '/')."/{$vendor}-{$package}-{$version}.zip";
@unlink($zipPath);

$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Could not write {$zipPath}\n");
    exit(1);
}

$count = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        fn (SplFileInfo $file): bool => ! in_array($file->getFilename(), EXCLUDE, true)
            && ! str_ends_with($file->getFilename(), '.zip')
            && ! $file->isLink(),
    ),
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
    $zip->addFile($file->getPathname(), $package.'/'.$relative);
    $count++;
}

$zip->close();

fwrite(STDOUT, sprintf("Packaged %d files into %s (%s KB)\n", $count, $zipPath, number_format(filesize($zipPath) / 1024, 1)));
fwrite(STDOUT, "sha256 ".hash_file('sha256', $zipPath)."\n");
