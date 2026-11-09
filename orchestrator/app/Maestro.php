<?php

namespace App;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Reads kits.json and resolves the paths Maestro works with.
 *
 * A kit is an ordered list of layers (folders under kits/). Building a kit
 * copies each layer on top of the previous one, so later layers override
 * earlier ones. Layers listed under "skeletons" mirror an upstream repository
 * and are only ever written by `skeleton:sync`.
 */
class Maestro
{
    protected ?array $manifest = null;

    public function __construct(protected string $root) {}

    public function root(string $path = ''): string
    {
        return $this->root.($path !== '' ? '/'.$path : '');
    }

    public function kitsPath(string $layer = ''): string
    {
        return $this->root('kits'.($layer !== '' ? '/'.$layer : ''));
    }

    public function buildPath(): string
    {
        return $this->root('build');
    }

    /**
     * @return array<string, array{name: string, craft: int, layers: list<string|array{layer: string, into?: string}>, repository: string, branch: string, seed?: array{file: string, dump: string}}>
     */
    public function kits(): array
    {
        return $this->manifest()['kits'];
    }

    /**
     * @return array{name: string, craft: int, layers: list<string|array{layer: string, into?: string}>, repository: string, branch: string, seed?: array{file: string, dump: string}}
     */
    public function kit(string $handle): array
    {
        return $this->kits()[$handle]
            ?? throw new InvalidArgumentException("Unknown kit [{$handle}]. Available kits: ".implode(', ', array_keys($this->kits())));
    }

    /**
     * The kit's layers in build order. A layer is either a folder name, copied to
     * the root of the build, or {"layer": "...", "into": "backend"} to copy it
     * into a subfolder, as headless kits do with their Craft backend.
     *
     * @return list<array{layer: string, into: string}>
     */
    public function layers(string $handle): array
    {
        return array_map(
            fn (string|array $layer) => is_string($layer)
                ? ['layer' => $layer, 'into' => '']
                : ['layer' => $layer['layer'], 'into' => trim($layer['into'] ?? '', '/')],
            $this->kit($handle)['layers'],
        );
    }

    /**
     * Where a build path lives inside a layer, or null if the layer isn't mounted above it.
     *
     * @param  array{layer: string, into: string}  $mount
     */
    public function layerPath(array $mount, string $path): ?string
    {
        if ($mount['into'] === '') {
            return $this->kitsPath("{$mount['layer']}/{$path}");
        }

        if ($path === $mount['into']) {
            return $this->kitsPath($mount['layer']);
        }

        return str_starts_with($path, "{$mount['into']}/")
            ? $this->kitsPath($mount['layer'].'/'.substr($path, strlen($mount['into']) + 1))
            : null;
    }

    /**
     * @return array<string, array{repository: string, ref: string, exclude?: list<string>}>
     */
    public function skeletons(): array
    {
        return $this->manifest()['skeletons'] ?? [];
    }

    public function isSkeleton(string $layer): bool
    {
        return array_key_exists($layer, $this->skeletons());
    }

    protected function manifest(): array
    {
        return $this->manifest ??= json_decode(File::get($this->root('kits.json')), true, flags: JSON_THROW_ON_ERROR);
    }
}
