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

        $layers = $maestro->kit(trim(File::get("{$build}/.git/maestro-kit")))['layers'];
        // Skeleton layers mirror craftcms/craft, so edits never land in them.
        $editable = array_values(array_filter($layers, fn (string $layer) => ! $maestro->isSkeleton($layer)));
        $newFileLayer = $this->option('layer') ?? end($editable);

        if (! in_array($newFileLayer, $editable, true)) {
            error("[{$newFileLayer}] is not an editable layer of this kit.");

            return self::FAILURE;
        }

        $owner = function (string $path) use ($maestro, $editable): ?string {
            foreach (array_reverse($editable) as $layer) {
                if (File::exists($maestro->kitsPath("{$layer}/{$path}"))) {
                    return $layer;
                }
            }

            return null;
        };

        // A new file joins the most specific layer that already has its directory,
        // so e.g. new project config YAML lands next to the existing project config.
        $directoryOwner = function (string $path) use ($owner, $newFileLayer): string {
            for ($dir = dirname($path); $dir !== '.'; $dir = dirname($dir)) {
                if ($layer = $owner($dir)) {
                    return $layer;
                }
            }

            return $newFileLayer;
        };

        $changes = Process::path($build)->run('git status --porcelain=v1 -z --untracked-files=all --no-renames')->throw()->output();

        foreach (array_filter(explode("\0", $changes)) as $entry) {
            $status = substr($entry, 0, 2);
            $path = substr($entry, 3);

            if (str_contains($status, 'D')) {
                if ($layer = $owner($path)) {
                    File::delete($maestro->kitsPath("{$layer}/{$path}"));
                    info("Deleted {$path} from {$layer}");

                    for ($dir = dirname($path); $dir !== '.' && File::isEmptyDirectory($maestro->kitsPath("{$layer}/{$dir}")); $dir = dirname($dir)) {
                        File::deleteDirectory($maestro->kitsPath("{$layer}/{$dir}"));
                    }
                }

                if ($fallback = collect($layers)->reverse()->first(fn ($l) => File::exists($maestro->kitsPath("{$l}/{$path}")))) {
                    warning("{$path} still comes from {$fallback} and will return on the next build.");
                }

                continue;
            }

            $layer = $owner($path) ?? ($this->option('layer') ?? $directoryOwner($path));
            $target = $maestro->kitsPath("{$layer}/{$path}");
            File::ensureDirectoryExists(dirname($target));
            Process::run(['cp', '-p', "{$build}/{$path}", $target])->throw();
            info("Synced {$path} to {$layer}");
        }

        BuildCommand::commitBuild($build, 'Sync');

        return self::SUCCESS;
    }
}
