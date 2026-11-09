<?php

namespace App;

/**
 * Merges .ddev/config.yaml across layers by top-level key, the way EnvFile
 * merges .env files: a later layer's copy holds only the keys it changes or
 * adds, and each key replaces the earlier one whole, including nested maps
 * and lists.
 */
class DdevConfig extends EnvFile
{
    protected const KEY = '/^([A-Za-z_][A-Za-z0-9_-]*)\s*:/';

    public static function matches(string $path): bool
    {
        return $path === '.ddev/config.yaml' || str_ends_with($path, '/.ddev/config.yaml');
    }

    /**
     * Lines, with each top-level key joined to the indented lines and list items under it.
     *
     * @return list<string>
     */
    protected static function lines(string $contents): array
    {
        $lines = [];

        foreach (parent::lines($contents) as $line) {
            $nested = $line !== '' && (ctype_space($line[0]) || str_starts_with($line, '- '));

            if ($nested && $lines !== [] && preg_match(static::KEY, end($lines))) {
                $lines[array_key_last($lines)] .= "\n".$line;
            } else {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
