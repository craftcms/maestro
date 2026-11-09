<?php

namespace App\Console\Commands;

use App\Maestro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

class SkeletonSyncCommand extends Command
{
    protected $signature = 'skeleton:sync {layer?* : Skeleton layers to sync (defaults to all)}';

    protected $description = 'Mirror the upstream craftcms/craft branches into their skeleton layers';

    /**
     * Upstream files that describe the craftcms/craft repository itself, not a project.
     */
    protected const EXCLUDE = ['.git', '.github'];

    public function handle(Maestro $maestro): int
    {
        $layers = $this->argument('layer') ?: array_keys($maestro->skeletons());

        foreach ($layers as $layer) {
            $skeleton = $maestro->skeletons()[$layer];
            $checkout = storage_path('app/skeletons/'.str_replace('/', '-', $layer));

            File::deleteDirectory($checkout);
            Process::run([
                'git', 'clone', '-q', '--depth=1', '--branch', $skeleton['ref'],
                "https://github.com/{$skeleton['repository']}.git", $checkout,
            ])->throw();

            foreach (self::EXCLUDE as $path) {
                File::deleteDirectory("{$checkout}/{$path}");
            }

            $target = $maestro->kitsPath($layer);
            File::deleteDirectory($target);
            File::ensureDirectoryExists(dirname($target));
            File::moveDirectory($checkout, $target);

            info("Synced {$layer} from {$skeleton['repository']}@{$skeleton['ref']}");
        }

        return self::SUCCESS;
    }
}
