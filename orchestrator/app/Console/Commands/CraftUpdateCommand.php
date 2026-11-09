<?php

namespace App\Console\Commands;

use App\EnvFile;
use App\Maestro;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

use function Laravel\Prompts\info;

class CraftUpdateCommand extends Command
{
    protected $signature = 'craft:update
        {craft : The Craft major version whose kits to update (5 or 6)}
        {--constraint= : New craftcms/cms constraint, such as ^5.13.0 (defaults to the current one)}
        {--db-driver=pgsql} {--db-host=127.0.0.1} {--db-port=5432}
        {--db-database=craft : A scratch database; its tables are dropped before each install}
        {--db-user=craft} {--db-password=craft}';

    protected $description = 'Update Craft in every kit of a Craft version: install on the locked version, run composer update and craft up, then sync';

    public function handle(Maestro $maestro): int
    {
        $craft = (int) $this->argument('craft');

        if (! in_array($craft, [5, 6], true)) {
            $this->error('Only Craft 5 and Craft 6 kits can be updated.');

            return self::FAILURE;
        }

        $projectConfig = $craft === 5 ? 'config/project/project.yaml' : 'config/craft/project/project.yaml';

        // Project config is tied to the Craft version, and each lock pins that version,
        // so one run per pair of lock and project config owner covers every kit.
        $runs = [];

        foreach ($maestro->kits() as $handle => $kit) {
            if ($kit['craft'] !== $craft) {
                continue;
            }

            $root = $maestro->layers($handle)[0]['into'];
            $lock = $this->owner($maestro, $handle, ltrim("{$root}/composer.lock", '/'))
                ?? throw new RuntimeException("{$handle} has no composer.lock.");
            $config = $this->owner($maestro, $handle, ltrim("{$root}/{$projectConfig}", '/'))
                ?? throw new RuntimeException("{$handle} has no project config.");

            $runs[$lock.'|'.$config] ??= ['kit' => $handle, 'root' => $root, 'lock' => dirname($lock)];
        }

        // Every run installs on the version the kits were locked to, even after an
        // earlier run updated a lock it shares.
        $originals = [];

        foreach ($runs as $run) {
            foreach (['composer.json', 'composer.lock'] as $file) {
                $originals[$run['lock']][$file] ??= File::get("{$run['lock']}/{$file}");
            }
        }

        $updated = [];

        foreach ($runs as $run) {
            $this->call('build', ['kit' => $run['kit']]);
            $project = rtrim("{$maestro->buildPath()}/{$run['root']}", '/');

            foreach ($originals[$run['lock']] as $file => $contents) {
                File::put("{$project}/{$file}", $contents);
            }

            info("Installing {$run['kit']} on its locked Craft version...");
            $this->shell($project, ['composer', 'install', '--no-interaction']);
            $this->install($craft, $project);

            if (isset($updated[$run['lock']])) {
                // The shared lock is already updated, so install it rather than resolving again.
                foreach (['composer.json', 'composer.lock'] as $file) {
                    File::copy("{$run['lock']}/{$file}", "{$project}/{$file}");
                }

                $this->shell($project, ['composer', 'install', '--no-interaction']);
            } else {
                if ($constraint = $this->option('constraint')) {
                    $this->shell($project, ['composer', 'require', '--no-update', '--no-interaction', "craftcms/cms:{$constraint}"]);
                }

                info("Updating {$run['kit']}...");
                $this->shell($project, ['composer', 'update', '--no-interaction']);
                $updated[$run['lock']] = true;
            }

            $this->shell($project, $craft === 5
                ? ['php', 'craft', 'up', '--interactive=0']
                : ['php', 'artisan', 'craft:up', '--no-interaction', '--noBackup']);
            // Installing also writes files the kits shouldn't ship, such as config/license.key,
            // so only the Composer files and project config sync back.
            $keep = [':!composer.json', ':!composer.lock', ':!'.dirname($projectConfig)];
            $this->shell($project, ['git', 'checkout', '--', '.', ...$keep]);
            $this->shell($project, ['git', 'clean', '-fdqx', '--', '.', ...$keep]);
            $this->call('sync');
        }

        foreach (array_keys($updated) as $lock) {
            $version = collect(json_decode(File::get("{$lock}/composer.lock"), true)['packages'])
                ->firstWhere('name', 'craftcms/cms')['version'];
            info(Str::after($lock, $maestro->kitsPath().'/')." is on craftcms/cms {$version}.");
        }

        info('Review the changes in kits/ before committing.');

        return self::SUCCESS;
    }

    /**
     * The most specific layer of the kit that has the build path.
     */
    protected function owner(Maestro $maestro, string $handle, string $path): ?string
    {
        foreach (array_reverse($maestro->layers($handle)) as $mount) {
            $source = $maestro->layerPath($mount, $path);

            if ($source !== null && File::exists($source)) {
                return $source;
            }
        }

        return null;
    }

    protected function install(int $craft, string $project): void
    {
        $env = $craft === 5 ? [
            'CRAFT_DB_DRIVER' => $this->option('db-driver'),
            'CRAFT_DB_SERVER' => $this->option('db-host'),
            'CRAFT_DB_PORT' => $this->option('db-port'),
            'CRAFT_DB_DATABASE' => $this->option('db-database'),
            'CRAFT_DB_USER' => $this->option('db-user'),
            'CRAFT_DB_PASSWORD' => $this->option('db-password'),
            'CRAFT_SECURITY_KEY' => Str::random(32),
            'PRIMARY_SITE_URL' => 'http://localhost',
            'CRAFT_BASE_CP_URL' => 'http://localhost',
        ] : [
            'DB_CONNECTION' => $this->option('db-driver'),
            'DB_HOST' => $this->option('db-host'),
            'DB_PORT' => $this->option('db-port'),
            'DB_DATABASE' => $this->option('db-database'),
            'DB_USERNAME' => $this->option('db-user'),
            'DB_PASSWORD' => $this->option('db-password'),
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_URL' => 'http://localhost',
        ];

        $example = $craft === 5 ? '.env.example.dev' : '.env.example';
        $env = collect($env)->map(fn (string $value, string $key) => "{$key}={$value}")->implode("\n");
        File::put("{$project}/.env", EnvFile::merge([File::get("{$project}/{$example}"), $env]));

        $credentials = ['--username=admin', '--email=admin@example.com', '--password='.Str::random(16)];

        if ($craft === 5) {
            $this->shell($project, ['php', 'craft', 'db/drop-all-tables', '--interactive=0']);
            $this->shell($project, ['php', 'craft', 'install', '--interactive=0', ...$credentials]);
        } else {
            $this->shell($project, ['php', 'artisan', 'db:wipe', '--force']);
            $this->shell($project, ['php', 'artisan', 'craft:install', '--no-interaction', ...$credentials]);
        }
    }

    /**
     * Run a command in the kit without the orchestrator's own .env variables,
     * which the kit would otherwise read instead of its .env.
     *
     * @param  list<string>  $command
     */
    protected function shell(string $project, array $command): void
    {
        $env = File::exists(base_path('.env'))
            ? array_fill_keys(array_keys(Dotenv::parse(File::get(base_path('.env')))), false)
            : [];

        Process::path($project)->env($env)->forever()->run($command)->throw();
    }
}
