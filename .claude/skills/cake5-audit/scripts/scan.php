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
 * and prints per-pattern counts with file:line hits. Also checks element/layout/template
 * names against the git-tracked template files with exact case (template-name-case) and
 * disk-vs-git folder case (path-case-drift). Exit code: 1 if any
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

/*
 * Case checks. Windows/macOS find `Flash/default` when the file is `flash/default.php`;
 * Linux doesn't. Template names in code are compared with the paths git tracks (what a
 * Linux checkout gets), falling back to the disk when the project isn't a git repo.
 */
$caseChecks = [
    'template-name-case' => [
        'id' => 'template-name-case',
        'severity' => 'blocker',
        'phase' => 'cake5-structure',
        'fix' => 'Element/layout/template name differs in case from the file (works on Windows/macOS, "could not be found" on Linux). '
            . "Use the file's exact case: 5.x folders are lowercase (element/flash/default.php → 'flash/default'), except templates/Error/.",
    ],
    'path-case-drift' => [
        'id' => 'path-case-drift',
        'severity' => 'warning',
        'phase' => 'cake5-structure',
        'fix' => 'Disk and git disagree on case (core.ignorecase hides it), or git tracks one folder in two cases (two folders on Linux). '
            . 'Rename to the intended case with two `git mv` steps via a temp name; new files added under the wrong-case folder land in a second folder on Linux.',
    ],
];
foreach ($caseChecks as $id => $p) {
    if ($opts['phase'] === null || $p['phase'] === $opts['phase']) {
        $results[$id] = ['pattern' => $p, 'count' => 0, 'hits' => []];
    }
}

$tracked = [];
$gitOut = @shell_exec('git -C ' . escapeshellarg($root) . ' ls-files -z -- templates src plugins 2>' . (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'));
if (is_string($gitOut) && $gitOut !== '') {
    $tracked = array_values(array_filter(explode("\0", $gitOut)));
} else {
    foreach ($files as $path) {
        $tracked[] = substr($path, strlen($root) + 1);
    }
}
$templateFiles = array_values(array_filter($tracked, fn($t) => str_starts_with($t, 'templates/') && str_ends_with($t, '.php')));
$exact = array_flip($templateFiles);
$byLower = [];
foreach ($templateFiles as $t) {
    $byLower[strtolower($t)][] = $t;
}

/** Candidate template paths for a name used in code. */
$candidates = function (string $kind, string $name, string $rel): array {
    $name = ltrim($name, '/');
    return match ($kind) {
        // FlashComponent prefixes 'flash/'; in templates the name is already relative to element/.
        'element' => str_starts_with($rel, 'templates/') ? ["templates/element/$name.php"] : ["templates/element/flash/$name.php"],
        'layout' => ["templates/layout/$name.php", "templates/layout/email/html/$name.php", "templates/layout/email/text/$name.php"],
        default => [],
    };
};

if (isset($results['template-name-case'])) {
    $refs = [
        'element' => ['~->element\(\s*[\'"]([^\'"$.]+)[\'"]~', '~[\'"]element[\'"]\s*=>\s*[\'"]([^\'"$.]+)[\'"]~'],
        'layout' => ['~setLayout\(\s*[\'"]([^\'"$.]+)[\'"]~', '~->layout\s*=\s*[\'"]([^\'"$.]+)[\'"]~'],
        'template' => ['~setTemplate\(\s*[\'"]([^\'"$.]+)[\'"]~'],
    ];
    foreach ($files as $path) {
        $rel = substr($path, strlen($root) + 1);
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $i => $line) {
            if (empty($opts['comments']) && preg_match('~^\s*(//|#|\*|/\*)~', $line)) {
                continue;
            }
            foreach ($refs as $kind => $regexes) {
                foreach ($regexes as $re) {
                    if (!preg_match_all($re, $line, $m)) {
                        continue;
                    }
                    foreach ($m[1] as $name) {
                        if ($kind === 'template') {
                            // Relative to a controller/mailer folder: accept any file ending in /<name>.php.
                            $suffix = '/' . ltrim($name, '/') . '.php';
                            $ok = (bool)array_filter($templateFiles, fn($t) => str_ends_with($t, $suffix));
                            $ci = array_values(array_filter($templateFiles, fn($t) => str_ends_with(strtolower($t), strtolower($suffix))));
                        } else {
                            $paths = $candidates($kind, $name, $rel);
                            $ok = (bool)array_filter($paths, fn($c) => isset($exact[$c]));
                            $ci = array_merge(...array_map(fn($c) => $byLower[strtolower($c)] ?? [], $paths ?: ['']));
                        }
                        // Not found in any case: probably a plugin/core template, not a case problem.
                        if (!$ok && $ci) {
                            $results['template-name-case']['count']++;
                            $results['template-name-case']['hits'][] = "$rel:" . ($i + 1) . "  '$name' → file is {$ci[0]}";
                        }
                    }
                }
            }
        }
    }
}

if (isset($results['path-case-drift'])) {
    // One folder tracked under two spellings.
    $dirVariants = [];
    foreach ($tracked as $t) {
        $dir = dirname($t);
        $dirVariants[strtolower($dir)][$dir] = true;
    }
    foreach ($dirVariants as $variants) {
        if (count($variants) > 1) {
            $results['path-case-drift']['count']++;
            $results['path-case-drift']['hits'][] = 'git tracks: ' . implode(' + ', array_keys($variants));
        }
    }
    // Disk spelling differs from git's (each differing folder reported once).
    $listing = [];
    $seen = [];
    foreach ($tracked as $t) {
        $parts = explode('/', $t);
        $parent = $root;
        $so = '';
        foreach ($parts as $part) {
            $listing[$parent] ??= is_dir($parent) ? (scandir($parent) ?: []) : [];
            $so = $so === '' ? $part : "$so/$part";
            if (!in_array($part, $listing[$parent], true)) {
                $onDisk = current(array_filter($listing[$parent], fn($n) => strcasecmp($n, $part) === 0));
                if ($onDisk !== false && !isset($seen[$so])) {
                    $seen[$so] = true;
                    $results['path-case-drift']['count']++;
                    $results['path-case-drift']['hits'][] = "git: $so — disk: " . (dirname($so) === '.' ? '' : dirname($so) . '/') . $onDisk;
                }
                break;
            }
            $parent .= '/' . $part;
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
