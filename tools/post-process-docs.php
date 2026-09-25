<?php
declare(strict_types=1);

$output = realpath($argv[1] ?? '');
if ($output === false || !is_dir($output)) {
    fwrite(STDERR, "Documentation output directory not found.\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($output));
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
        continue;
    }

    $path = $file->getPathname();
    $html = file_get_contents($path);
    if ($html === false) {
        continue;
    }

    if (!str_contains(strtolower($html), '<meta charset=')) {
        $html = preg_replace_callback(
            '/(<head[^>]*>)/i',
            static fn (array $match): string => $match[1] . "\n    <meta charset=\"UTF-8\">",
            $html,
            1,
        ) ?? $html;
    }

    $relativePath = ltrim(str_replace($output, '', $path), DIRECTORY_SEPARATOR);
    $depth = substr_count(str_replace(DIRECTORY_SEPARATOR, '/', dirname($relativePath)), '/');
    $prefix = str_repeat('../', $depth);
    $html = preg_replace_callback(
        '/href="\/([^"#?]+)(#[^"]*)?"/i',
        static fn (array $match): string => 'href="' . $prefix . $match[1] . ($match[2] ?? '') . '"',
        $html,
    ) ?? $html;

    file_put_contents($path, $html);
}
