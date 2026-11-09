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
     * @return array<string, array{name: string, layers: string[], repository: string, branch: string}>
     */
    public function kits(): array
    {
        return $this->manifest()['kits'];
    }

    /**
     * @return array{name: string, layers: string[], repository: string, branch: string}
     */
    public function kit(string $handle): array
    {
        return $this->kits()[$handle]
            ?? throw new InvalidArgumentException("Unknown kit [{$handle}]. Available kits: ".implode(', ', array_keys($this->kits())));
    }

    /**
     * @return array<string, array{repository: string, ref: string}>
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
