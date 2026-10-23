<?php
declare(strict_types=1);

/**
 * CakePHP 3 -> 5 leftover scanner.
 *
 * Usage:
 *   php scan.php <project-path> [--format=md|json] [--patterns=<file>] [--phase=<skill>] [--max-hits=20] [--comments=1]
 *
 * Comment lines (//, #, *, /*) and trailing `// comments` are skipped unless --comments=1.
 * Patterns globbed `*.php` also match `.ctp` templates (before cake5-structure renames them).
 * Scans src/, templates/, config/, tests/, plugins/ and webroot/index.php (skips
 * vendor/, tmp/, logs/, node_modules/) for the patterns in references/patterns.json
 * and prints per-pattern counts with file:line hits. Exit code: 1 if any
 * blocker-severity pattern matched, else 0 — usable as a gate.
 *
 * Plain PHP 8, no dependencies.
 */

$args = $argv;
array_shift($args);
$opts = ['format' => 'md', 'patterns' => __DIR__ . '/../references/patterns.json', 'phase' => null, 'max-hits' => '20'];
$root = null;
foreach ($args as $arg) {
    if (preg_match('/^--([a-z-]+)=(.*)$/', $arg, $m)) {
        $opts[$m[1]] = $m[2];
    } elseif ($root === null) {
        $root = $arg;
    }
}
if ($root === null || !is_dir($root)) {
    fwrite(STDERR, "Usage: php scan.php <project-path> [--format=md|json] [--patterns=<file>] [--phase=<skill>] [--max-hits=20]\n");
    exit(2);
}
$root = rtrim(str_replace('\\', '/', (string)realpath($root)), '/');
$catalog = json_decode((string)file_get_contents($opts['patterns']), true);
if (!is_array($catalog) || empty($catalog['patterns'])) {
    fwrite(STDERR, "Cannot read patterns file: {$opts['patterns']}\n");
    exit(2);
}
$patterns = array_values(array_filter(
    $catalog['patterns'],
    fn($p) => $opts['phase'] === null || $p['phase'] === $opts['phase']
));
$maxHits = max(1, (int)$opts['max-hits']);

$scanDirs = ['src', 'templates', 'config', 'tests', 'plugins'];
$skipDirs = ['vendor', 'tmp', 'logs', 'node_modules', '.git'];
$files = [];
foreach ($scanDirs as $dir) {
    $path = "$root/$dir";
    if (!is_dir($path)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            fn(SplFileInfo $f) => !($f->isDir() && in_array($f->getFilename(), $skipDirs, true))
        )
    );
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'ctp'], true)) {
            $files[] = str_replace('\\', '/', $file->getPathname());
        }
    }
}
if (is_file("$root/webroot/index.php")) {
    $files[] = "$root/webroot/index.php";
}

/**
 * fnmatch-style glob against the path relative to the project root. A glob
 * without '/' matches the file name in any directory.
 */
$matchesGlob = function (string $rel, string $glob) use (&$matchesGlob): bool {
    // Templates are still .ctp before cake5-structure runs: a *.php glob covers them too.
    if (str_ends_with($rel, '.ctp') && str_ends_with($glob, '*.php')) {
        return $matchesGlob(substr($rel, 0, -4) . '.php', $glob);
    }
    if (strpos($glob, '/') === false) {
        return fnmatch($glob, basename($rel));
    }
    // 'tests/*.php' should match tests/TestCase/Foo/BarTest.php too.
    $prefix = strstr($glob, '*', true);
    return $prefix !== false
        ? str_starts_with($rel, $prefix) && fnmatch('*' . substr($glob, strlen($prefix) + 1), basename($rel))
        : $rel === $glob;
};

$results = [];
foreach ($patterns as $p) {
    $results[$p['id']] = ['pattern' => $p, 'count' => 0, 'hits' => []];
}
foreach ($files as $path) {
    $rel = substr($path, strlen($root) + 1);
    $lines = null;
    foreach ($patterns as $p) {
        if (!$matchesGlob($rel, $p['glob'] ?? '*.php')) {
            continue;
        }
        if (!empty($p['file_only'])) {
            $results[$p['id']]['count']++;
            $results[$p['id']]['hits'][] = $rel;
            continue;
        }
        $lines ??= file($path, FILE_IGNORE_NEW_LINES) ?: [];
        foreach ($lines as $i => $line) {
            // Commented-out code and docblocks aren't live leftovers (use --comments=1 to include them).
            if (empty($opts['comments'])) {
                if (preg_match('~^\s*(//|#|\*|/\*)~', $line)) {
                    continue;
                }
                // Drop a trailing `// comment` when it's outside string literals (even quote count before it).
                if (preg_match('~^(.*?)\s//\s.*$~', $line, $m) && substr_count($m[1], "'") % 2 === 0 && substr_count($m[1], '"') % 2 === 0) {
                    $line = $m[1];
                }
            }
            if (@preg_match('~' . str_replace('~', '\~', $p['regex']) . '~', $line)) {
                $results[$p['id']]['count']++;
                $results[$p['id']]['hits'][] = $rel . ':' . ($i + 1) . '  ' . trim(mb_substr($line, 0, 140));
            }
        }
    }
}

$blockers = 0;
foreach ($results as $r) {
    if ($r['pattern']['severity'] === 'blocker') {
        $blockers += $r['count'];
    }
}

if ($opts['format'] === 'json') {
    echo json_encode([
        'root' => $root,
        'files_scanned' => count($files),
        'blocker_hits' => $blockers,
        'results' => array_values(array_map(fn($r) => [
            'id' => $r['pattern']['id'],
            'severity' => $r['pattern']['severity'],
            'phase' => $r['pattern']['phase'],
            'count' => $r['count'],
            'fix' => $r['pattern']['fix'],
            'hits' => $r['hits'],
        ], array_filter($results, fn($r) => $r['count'] > 0))),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    $order = ['blocker' => 0, 'warning' => 1, 'info' => 2];
    uasort($results, fn($a, $b) => [$order[$a['pattern']['severity']] ?? 3, -$a['count']] <=> [$order[$b['pattern']['severity']] ?? 3, -$b['count']]);
    echo "# CakePHP 5 upgrade scan\n\n";
    echo "Root: `$root` — files scanned: " . count($files) . " — blocker hits: **$blockers**\n\n";
    echo "| Pattern | Severity | Phase | Hits |\n|---|---|---|---|\n";
    foreach ($results as $r) {
        echo "| {$r['pattern']['id']} | {$r['pattern']['severity']} | {$r['pattern']['phase']} | {$r['count']} |\n";
    }
    foreach ($results as $r) {
        if ($r['count'] === 0) {
            continue;
        }
        echo "\n## {$r['pattern']['id']} ({$r['count']})\n\nFix: {$r['pattern']['fix']}\n\n";
        foreach (array_slice($r['hits'], 0, $maxHits) as $hit) {
            echo "- `" . str_replace('`', "'", $hit) . "`\n";
        }
        if ($r['count'] > $maxHits) {
            echo '- … ' . ($r['count'] - $maxHits) . " more\n";
        }
    }
}

exit($blockers > 0 ? 1 : 0);
