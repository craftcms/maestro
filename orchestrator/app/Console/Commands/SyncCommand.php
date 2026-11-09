<?php

namespace App\Console\Commands;

use App\Maestro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class SyncCommand extends Command
{
    protected $signature = 'sync {--layer= : Layer that receives new files (defaults to the layer that owns their directory)}';

    protected $description = 'Copy changes made in build/ back to the layers they came from';

    public function handle(Maestro $maestro): int
    {
        $build = $maestro->buildPath();

        if (! File::exists("{$build}/.git/maestro-kit")) {
            error("Nothing to sync. Run 'php artisan build' first.");

            return self::FAILURE;
        }

        $mounts = $maestro->layers(trim(File::get("{$build}/.git/maestro-kit")));
        // Skeleton layers mirror craftcms/craft, so edits never land in them.
        $editable = array_values(array_filter($mounts, fn (array $mount) => ! $maestro->isSkeleton($mount['layer'])));

        if (($layer = $this->option('layer')) && ! in_array($layer, array_column($editable, 'layer'), true)) {
            error("[{$layer}] is not an editable layer of this kit.");

            return self::FAILURE;
        }

        // The most specific editable layer that has this path, and the path inside it.
        $owner = function (string $path, array $mounts) use ($maestro): ?array {
            foreach (array_reverse($mounts) as $mount) {
                $source = $maestro->layerPath($mount, $path);

                if ($source !== null && File::exists($source)) {
                    return [$mount['layer'], $source];
                }
            }

            return null;
        };

        // A new file joins the most specific layer that already has its directory,
        // so e.g. new project config YAML lands next to the existing project config.
        $newFileOwner = function (string $path) use ($maestro, $editable): ?array {
            $candidates = $this->option('layer')
                ? array_filter($editable, fn (array $mount) => $mount['layer'] === $this->option('layer'))
                : $editable;

            for ($dir = dirname($path); ; $dir = dirname($dir)) {
                foreach (array_reverse($candidates) as $mount) {
                    $source = $maestro->layerPath($mount, $dir === '.' ? '' : $dir);

                    if ($source !== null && ($dir === '.' || File::isDirectory($source))) {
                        return [$mount['layer'], $maestro->layerPath($mount, $path)];
                    }
                }

                if ($dir === '.') {
                    return null;
                }
            }
        };

        $changes = Process::path($build)->run('git status --porcelain=v1 -z --untracked-files=all --no-renames')->throw()->output();

        foreach (array_filter(explode("\0", $changes)) as $entry) {
            $status = substr($entry, 0, 2);
            $path = substr($entry, 3);

            if (str_contains($status, 'D')) {
                if ([$layer, $source] = $owner($path, $editable)) {
                    File::delete($source);
                    info("Deleted {$path} from {$layer}");

                    for ($dir = dirname($source); $dir !== $maestro->kitsPath($layer) && File::isEmptyDirectory($dir); $dir = dirname($dir)) {
                        File::deleteDirectory($dir);
                    }
                }

                if ([$fallback] = $owner($path, $mounts)) {
                    warning("{$path} still comes from {$fallback} and will return on the next build.");
                }

                continue;
            }

            [$layer, $target] = $owner($path, $editable) ?? $newFileOwner($path) ?? [null, null];

            if ($layer === null) {
                warning("Skipped {$path}: no editable layer is mounted above it.");

                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            Process::run(['cp', '-p', "{$build}/{$path}", $target])->throw();
            info("Synced {$path} to {$layer}");
        }

        BuildCommand::commitBuild($build, 'Sync');

        return self::SUCCESS;
    }
}
