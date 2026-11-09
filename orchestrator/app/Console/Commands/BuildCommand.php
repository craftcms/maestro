<?php

namespace App\Console\Commands;

use App\DdevConfig;
use App\EnvFile;
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

        foreach ($maestro->layers($handle) as $mount) {
            $target = rtrim("{$build}/{$mount['into']}", '/');
            info("Copying {$mount['layer']}".($mount['into'] !== '' ? " into {$mount['into']}/" : '').'...');
            File::ensureDirectoryExists($target);
            // cp -a keeps file modes, so executables like ./craft stay executable.
            Process::run(['cp', '-a', $maestro->kitsPath($mount['layer']).'/.', $target])->throw();
        }

        // .env files and DDEV config merge instead of overriding: see EnvFile and DdevConfig.
        foreach (self::mergedFiles($maestro, $handle) as $path => $sources) {
            if (count($sources) > 1) {
                File::put("{$build}/{$path}", self::merger($path)::merge(array_map(File::get(...), $sources)));
            }
        }

        // Snapshot the build in its own Git repository, so `sync` can find
        // what changed and the kit's .gitignore files decide what counts.
        Process::path($build)->run('git init -q')->throw();
        File::put("{$build}/.git/maestro-kit", $handle);
        // Kits don't ship npm lock files, so the one `npm install` writes stays out of `sync`,
        // and neither do built assets: CI runs the kit's build commands when it publishes.
        $exclude = ['package-lock.json', ...array_map(fn (string $path) => '/'.trim($path, '/'), $kit['build']['output'] ?? [])];
        File::append("{$build}/.git/info/exclude", implode("\n", $exclude)."\n");
        $this->commitBuild($build, 'Build '.$handle);

        info("{$kit['name']} built in {$build}");

        return self::SUCCESS;
    }

    /**
     * The class that merges a build path across layers, or null if later layers replace it.
     *
     * @return class-string<EnvFile>|null
     */
    public static function merger(string $path): ?string
    {
        return match (true) {
            DdevConfig::matches($path) => DdevConfig::class,
            EnvFile::matches($path) => EnvFile::class,
            default => null,
        };
    }

    /**
     * Every merged file in the kit's layers, by build path, with each layer's copy in build order.
     *
     * @return array<string, list<string>>
     */
    protected static function mergedFiles(Maestro $maestro, string $handle): array
    {
        $files = [];

        foreach ($maestro->layers($handle) as $mount) {
            foreach (File::allFiles($maestro->kitsPath($mount['layer']), true) as $file) {
                $path = ltrim("{$mount['into']}/{$file->getRelativePathname()}", '/');

                if (self::merger($path)) {
                    $files[$path][] = $file->getPathname();
                }
            }
        }

        return $files;
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
