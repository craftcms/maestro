<?php

namespace App\Console\Commands;

use App\Maestro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class BuildCommand extends Command
{
    protected $signature = 'build {kit? : The kit handle from kits.json}';

    protected $description = 'Build a starter kit into the build/ folder by stacking its layers';

    public function handle(Maestro $maestro): int
    {
        $handle = $this->argument('kit') ?? select(
            label: 'Which starter kit would you like to build?',
            options: array_map(fn (array $kit) => $kit['name'], $maestro->kits()),
        );

        $kit = $maestro->kit($handle);
        $build = $maestro->buildPath();

        File::deleteDirectory($build);
        File::ensureDirectoryExists($build);

        foreach ($kit['layers'] as $layer) {
            info("Copying {$layer}...");
            // cp -a keeps file modes, so executables like ./craft stay executable.
            Process::run(['cp', '-a', $maestro->kitsPath($layer).'/.', $build])->throw();
        }

        // Snapshot the build in its own Git repository, so `sync` can find
        // what changed and the kit's .gitignore files decide what counts.
        Process::path($build)->run('git init -q')->throw();
        File::put("{$build}/.git/maestro-kit", $handle);
        $this->commitBuild($build, 'Build '.$handle);

        info("{$kit['name']} built in {$build}");

        return self::SUCCESS;
    }

    public static function commitBuild(string $build, string $message): void
    {
        Process::path($build)->run('git add -A')->throw();
        Process::path($build)->run([
            'git', '-c', 'user.name=Maestro', '-c', 'user.email=maestro@localhost', '-c', 'commit.gpgsign=false',
            'commit', '-q', '--allow-empty', '--no-verify', '-m', $message,
        ])->throw();
    }
}
