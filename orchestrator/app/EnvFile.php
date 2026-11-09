<?php

namespace App;

/**
 * Merges .env files across layers.
 *
 * The first layer that has an .env file provides it whole. A later layer's
 * copy holds only the keys it changes or adds:
 *
 * - A key the file already has is replaced where it stands. Comment lines
 *   directly above the key replace the existing ones; without any, the
 *   existing comments stay.
 * - A new key, with the comments above it, is appended at the end. Blank
 *   lines between new keys are kept, so groups stay together.
 * - A comment block at the top, followed by a blank line, goes at the top.
 */
class EnvFile
{
    protected const KEY = '/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=/';

    public static function matches(string $path): bool
    {
        $name = basename($path);

        return $name === '.env' || str_starts_with($name, '.env.');
    }

    /**
     * @param  list<string>  $files  Contents of each layer's copy, in build order
     */
    public static function merge(array $files): string
    {
        $lines = self::lines(array_shift($files));

        foreach ($files as $override) {
            [$header, $entries] = self::parse($override);
            $appending = false;

            foreach ($entries as $key => $entry) {
                $block = [...$entry['comments'], $entry['line']];

                if (($at = self::find($lines, $key)) === null) {
                    if (($entry['gap'] || ! $appending) && $lines !== [] && end($lines) !== '') {
                        $lines[] = '';
                    }

                    array_push($lines, ...$block);
                    $appending = true;

                    continue;
                }

                $start = $entry['comments'] === [] ? $at : self::commentStart($lines, $at);
                array_splice($lines, $start, $at - $start + 1, $block);
            }

            if ($header !== []) {
                $lines = [...$header, '', ...$lines];
            }
        }

        return $lines === [] ? '' : implode("\n", $lines)."\n";
    }

    /**
     * The file's leading comment block, and its keys with the comment lines
     * directly above them and whether a blank line separates them from the
     * key before.
     *
     * @return array{list<string>, array<string, array{comments: list<string>, line: string, gap: bool}>}
     */
    public static function parse(string $contents): array
    {
        [$header, $entries, $comments, $gap] = [[], [], [], false];

        foreach (self::lines($contents) as $line) {
            if (preg_match(self::KEY, $line, $match)) {
                $entries[$match[1]] = ['comments' => $comments, 'line' => $line, 'gap' => $gap];
                [$comments, $gap] = [[], false];
            } elseif (trim($line) === '') {
                if ($entries === [] && ! $gap) {
                    $header = $comments;
                }

                [$comments, $gap] = [[], true];
            } else {
                $comments[] = $line;
            }
        }

        return [$header, $entries];
    }

    /**
     * Render entries from parse() as the lines of a layer's override.
     *
     * @param  list<array{comments: list<string>, line: string, gap: bool}>  $entries
     */
    public static function render(array $entries): string
    {
        $lines = [];

        foreach ($entries as $i => $entry) {
            if ($i > 0 && $entry['gap']) {
                $lines[] = '';
            }

            array_push($lines, ...[...$entry['comments'], $entry['line']]);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Remove a key and the comment lines directly above it.
     */
    public static function remove(string $contents, string $key): string
    {
        $lines = self::lines($contents);

        if (($at = self::find($lines, $key)) === null) {
            return $contents;
        }

        $start = self::commentStart($lines, $at);
        array_splice($lines, $start, $at - $start + 1);

        // Don't leave two blank lines in a row.
        if (($lines[$start] ?? null) === '' && $start > 0 && $lines[$start - 1] === '') {
            array_splice($lines, $start, 1);
        }

        $contents = trim(implode("\n", $lines), "\n");

        return $contents === '' ? '' : $contents."\n";
    }

    /**
     * @return list<string>
     */
    protected static function lines(string $contents): array
    {
        $contents = rtrim($contents, "\n");

        return $contents === '' ? [] : explode("\n", $contents);
    }

    /**
     * @param  list<string>  $lines
     */
    protected static function find(array $lines, string $key): ?int
    {
        foreach ($lines as $i => $line) {
            if (preg_match(self::KEY, $line, $match) && $match[1] === $key) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    protected static function commentStart(array $lines, int $at): int
    {
        while ($at > 0 && str_starts_with(ltrim($lines[$at - 1]), '#')) {
            $at--;
        }

        return $at;
    }
}
