<?php

/**
 * Audit: find columns that are used as a PRIMARY KEY or UNIQUE index while being
 * declared as a plain string() (which defaults to varchar(255)).
 *
 * On MySQL with utf8mb4, 255 characters is 1020 bytes, which exceeds the
 * 1000-byte index limit and fails the migration with
 * "Specified key was too long; max key length is 1000 bytes".
 *
 * Run with: php tools/audit-index-length.php
 */

$migrations = glob(__DIR__.'/../database/migrations/*.php');
$problems = [];

foreach ($migrations as $file) {
    $source = file_get_contents($file);
    $lines = explode("\n", $source);

    foreach ($lines as $number => $line) {
        // A string()/char() declaration followed on the same line by an index-ish
        // modifier, with no explicit length.
        if (! preg_match('/\$(table|this)->(string|char)\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $line, $m)) {
            continue;
        }

        $column = $m[3];
        $tail = substr($line, strpos($line, $m[0]) + strlen($m[0]));

        $isIndexed = preg_match('/->(primary|unique)\s*\(/', $tail)
            || preg_match('/->index\s*\(/', $tail);

        if (! $isIndexed) {
            continue;
        }

        // A composite unique index declared separately may include it; those are
        // checked by the second pass below.
        $problems[] = sprintf(
            '%s:%d  %s — declared string() with no length and indexed',
            basename($file),
            $number + 1,
            $column,
        );
    }

    // Second pass: composite ->unique([...]) / ->primary([...]) listing columns.
    if (preg_match_all('/->(unique|primary|index)\(\s*\[([^\]]+)\]/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $set) {
            // Only interesting if it spans multiple columns on one line.
            if (substr_count($set[2], ',') === 0) {
                continue;
            }

            $problems[] = sprintf(
                '%s  composite %s on [%s] — verify total key length fits 1000 bytes',
                basename($file),
                $set[1],
                trim(preg_replace('/\s+/', ' ', $set[2])),
            );
        }
    }
}

if ($problems === []) {
    echo "No unbounded indexed string columns found.\n";
    exit(0);
}

echo "Review these — each may exceed MySQL's 1000-byte index limit under utf8mb4:\n\n";
foreach ($problems as $problem) {
    echo '  '.$problem."\n";
}

echo "\nNote: a 255-character utf8mb4 column is 1020 bytes, so any single-column\n";
echo "PRIMARY KEY or UNIQUE index on one needs ->string('x', 191).\n";
