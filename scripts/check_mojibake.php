<?php

declare(strict_types=1);

$roots = ['app', 'resources', 'routes', 'config', 'database'];
$extensions = ['php', 'blade.php', 'js', 'ts', 'vue', 'css', 'scss', 'json', 'md', 'txt'];
$patterns = [
    '/Ã./u',
    '/Ä./u',
    '/Å./u',
    '/Â./u',
    '/�/u',
];

$errors = [];

foreach ($roots as $root) {
    if (! is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());
        $ok = false;
        foreach ($extensions as $ext) {
            if (str_ends_with($path, '.'.$ext) || str_ends_with($path, $ext)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            continue;
        }

        $content = @file_get_contents($file->getPathname());
        if (! is_string($content)) {
            continue;
        }

        $lines = preg_split('/\R/u', $content) ?: [];
        foreach ($lines as $lineNo => $line) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $errors[] = sprintf('%s:%d %s', $path, $lineNo + 1, trim($line));
                    break;
                }
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Mojibake/encoding issue detected:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
    exit(1);
}

echo "OK: No mojibake pattern found.\n";
