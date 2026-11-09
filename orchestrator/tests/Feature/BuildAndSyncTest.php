<?php

use App\Maestro;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/maestro-test-'.uniqid();

    $files = [
        'Base/Blank/composer.json' => 'skeleton',
        'Base/Blank/craft' => 'skeleton',
        'Base/Blank/config/general.php' => 'skeleton',
        'Base/Blank/templates/index.twig' => 'skeleton',
        'Base/Starter/templates/index.twig' => 'starter',
        'Base/Starter/templates/_layout.twig' => 'starter',
        'Base/Starter/config/project/project.yaml' => 'starter',
        'Base/Kit/composer.json' => 'kit',
        'Base/Headless/config/general.php' => 'headless',
        'Base/Frontend/frontend/package.json' => 'frontend',
        'Base/Starter/.ddev/config.yaml' => <<<'YAML'
            name: craft
            php_version: "8.5"
            database:
                type: mysql
                version: "8.0"
            web_environment: []

            YAML,
        'Base/Kit/.ddev/config.yaml' => <<<'YAML'
            name: demo
            database:
                type: postgres
                version: "17"

            YAML,
        'Base/Blank/.env.example' => <<<'ENV'
            # Header from the skeleton

            # App name
            APP_NAME=Craft
            APP_DEBUG=true

            DB_HOST=127.0.0.1
            DB_PORT=5432

            ENV,
        'Base/Starter/.env.example' => <<<'ENV'
            # The starter's name
            APP_NAME=Starter

            # Mail
            MAIL_HOST=mail
            MAIL_PORT=25

            ENV,
        'Base/Kit/.env.example' => <<<'ENV'
            # Only for the demo kit

            APP_NAME=Demo
            DB_PORT=3306

            ENV,
    ];

    foreach ($files as $path => $contents) {
        File::ensureDirectoryExists(dirname("{$this->root}/kits/{$path}"));
        File::put("{$this->root}/kits/{$path}", $contents);
    }

    chmod("{$this->root}/kits/Base/Blank/craft", 0755);

    File::put("{$this->root}/kits.json", json_encode([
        'skeletons' => ['Base/Blank' => ['repository' => 'craftcms/craft', 'ref' => '5.x']],
        'kits' => ['demo' => [
            'name' => 'Demo', 'craft' => 5, 'repository' => 'craftcms/starter-demo', 'branch' => 'main',
            'layers' => ['Base/Blank', 'Base/Starter', 'Base/Kit'],
        ], 'headless' => [
            'name' => 'Headless', 'craft' => 5, 'repository' => 'craftcms/starter-headless', 'branch' => 'main',
            'layers' => [
                ['layer' => 'Base/Blank', 'into' => 'backend'],
                ['layer' => 'Base/Headless', 'into' => 'backend'],
                'Base/Frontend',
            ],
        ]],
    ]));

    $this->app->instance(Maestro::class, new Maestro($this->root));
});

afterEach(fn () => File::deleteDirectory($this->root));

test('build stacks layers so later layers win', function () {
    $this->artisan('build', ['kit' => 'demo'])->assertSuccessful();

    $build = "{$this->root}/build";
    expect(File::get("{$build}/composer.json"))->toBe('kit')
        ->and(File::get("{$build}/templates/index.twig"))->toBe('starter')
        ->and(File::get("{$build}/config/general.php"))->toBe('skeleton')
        ->and(is_executable("{$build}/craft"))->toBeTrue();
});

test('sync writes build changes to the owning layer and never to the skeleton', function () {
    $this->artisan('build', ['kit' => 'demo'])->assertSuccessful();

    $build = "{$this->root}/build";
    $kits = "{$this->root}/kits";
    File::put("{$build}/composer.json", 'edited');                  // owned by Kit
    File::put("{$build}/templates/index.twig", 'edited');           // owned by Starter, also in Blank
    File::put("{$build}/config/general.php", 'edited');             // only in the skeleton, Starter has config/
    File::put("{$build}/config/project/sections.yaml", 'new');      // new, next to Starter's project config
    File::put("{$build}/README.md", 'new');                         // new, at the root
    File::delete("{$build}/templates/_layout.twig");                // owned by Starter

    $this->artisan('sync')->assertSuccessful();

    expect(File::get("{$kits}/Base/Kit/composer.json"))->toBe('edited')
        ->and(File::get("{$kits}/Base/Starter/templates/index.twig"))->toBe('edited')
        ->and(File::get("{$kits}/Base/Blank/templates/index.twig"))->toBe('skeleton')
        ->and(File::get("{$kits}/Base/Blank/config/general.php"))->toBe('skeleton')
        ->and(File::get("{$kits}/Base/Starter/config/general.php"))->toBe('edited')
        ->and(File::get("{$kits}/Base/Starter/config/project/sections.yaml"))->toBe('new')
        ->and(File::get("{$kits}/Base/Kit/README.md"))->toBe('new')
        ->and(File::exists("{$kits}/Base/Starter/templates/_layout.twig"))->toBeFalse();

    // A second sync only sees changes made since the first one.
    File::put("{$kits}/Base/Kit/README.md", 'changed in kits/');
    $this->artisan('sync')->assertSuccessful();
    expect(File::get("{$kits}/Base/Kit/README.md"))->toBe('changed in kits/');
});

test('layers mounted into a subfolder build there and sync back to the same layer', function () {
    $this->artisan('build', ['kit' => 'headless'])->assertSuccessful();

    $build = "{$this->root}/build";
    $kits = "{$this->root}/kits";
    expect(File::get("{$build}/backend/config/general.php"))->toBe('headless')
        ->and(File::get("{$build}/backend/composer.json"))->toBe('skeleton')
        ->and(File::get("{$build}/frontend/package.json"))->toBe('frontend')
        ->and(File::exists("{$build}/composer.json"))->toBeFalse();

    File::put("{$build}/backend/config/general.php", 'edited');      // owned by Headless
    File::put("{$build}/backend/composer.json", 'edited');           // only in the skeleton
    File::ensureDirectoryExists("{$build}/backend/modules");
    File::put("{$build}/backend/modules/Module.php", 'new');         // new, inside backend/
    File::put("{$build}/frontend/app.vue", 'new');                   // new, next to the frontend
    File::put("{$build}/frontend/package-lock.json", '{}');          // written by npm install, never shipped

    $this->artisan('sync')->assertSuccessful();

    expect(File::get("{$kits}/Base/Headless/config/general.php"))->toBe('edited')
        ->and(File::get("{$kits}/Base/Blank/composer.json"))->toBe('skeleton')
        ->and(File::get("{$kits}/Base/Headless/composer.json"))->toBe('edited')
        ->and(File::get("{$kits}/Base/Headless/modules/Module.php"))->toBe('new')
        ->and(File::get("{$kits}/Base/Frontend/frontend/app.vue"))->toBe('new')
        ->and(File::exists("{$kits}/Base/Frontend/backend"))->toBeFalse()
        ->and(File::exists("{$kits}/Base/Frontend/frontend/package-lock.json"))->toBeFalse();
});

test('build merges .env files across layers', function () {
    $this->artisan('build', ['kit' => 'demo'])->assertSuccessful();

    expect(File::get("{$this->root}/build/.env.example"))->toBe(<<<'ENV'
        # Only for the demo kit

        # Header from the skeleton

        # The starter's name
        APP_NAME=Demo
        APP_DEBUG=true

        DB_HOST=127.0.0.1
        DB_PORT=3306

        # Mail
        MAIL_HOST=mail
        MAIL_PORT=25

        ENV);
});

test('sync writes .env changes key by key to the layers that set them', function () {
    $this->artisan('build', ['kit' => 'demo'])->assertSuccessful();

    $env = "{$this->root}/build/.env.example";
    $kits = "{$this->root}/kits";
    $edited = str_replace(
        ['APP_DEBUG=true', "MAIL_HOST=mail\n", 'MAIL_PORT=25'],     // skeleton key, Starter key, Starter key
        ['APP_DEBUG=false', '', 'MAIL_PORT=587'],
        File::get($env),
    )."\n# Queue\nQUEUE_CONNECTION=sync\n";                        // new key
    File::put($env, $edited);

    $this->artisan('sync')->assertSuccessful();

    expect(File::get("{$kits}/Base/Kit/.env.example"))->toBe(<<<'ENV'
        # Only for the demo kit

        APP_NAME=Demo
        DB_PORT=3306

        APP_DEBUG=false

        # Queue
        QUEUE_CONNECTION=sync

        ENV)
        ->and(File::get("{$kits}/Base/Starter/.env.example"))->toBe(<<<'ENV'
        # The starter's name
        APP_NAME=Starter

        # Mail
        MAIL_PORT=587

        ENV);

    // The layers now build the edited file.
    $this->artisan('build', ['kit' => 'demo'])->assertSuccessful();
    expect(File::get($env))->toBe($edited);

    // A key deleted from the skeleton's file can't be removed, so nothing changes.
    File::put($env, str_replace("DB_HOST=127.0.0.1\n", '', $edited));
    $this->artisan('sync')->assertSuccessful();
    expect(File::get("{$kits}/Base/Blank/.env.example"))->toContain('DB_HOST=127.0.0.1')
        ->and(File::get("{$kits}/Base/Kit/.env.example"))->not->toContain('DB_HOST');

    // A file only the skeleton has gets an override holding just the changed key.
    $this->artisan('build', ['kit' => 'headless'])->assertSuccessful();
    File::put("{$this->root}/build/backend/.env.example", str_replace('DB_PORT=5432', 'DB_PORT=3306', File::get("{$kits}/Base/Blank/.env.example")));
    $this->artisan('sync')->assertSuccessful();
    expect(File::get("{$kits}/Base/Headless/.env.example"))->toBe("DB_PORT=3306\n");
});

test('build merges DDEV config by top-level key, and sync writes each key back to its layer', function () {
    $this->artisan('build', ['kit' => 'demo'])->assertSuccessful();

    $config = "{$this->root}/build/.ddev/config.yaml";
    $kits = "{$this->root}/kits";
    // Nested maps replace the earlier value whole; keys keep the base layer's order.
    expect(File::get($config))->toBe(<<<'YAML'
        name: demo
        php_version: "8.5"
        database:
            type: postgres
            version: "17"
        web_environment: []

        YAML);

    File::put($config, str_replace(
        ['php_version: "8.5"', 'web_environment: []'],
        ['php_version: "8.4"', "web_environment:\n  - FOO=bar"],
        File::get($config),
    ));
    $this->artisan('sync')->assertSuccessful();

    // Both keys are set by Starter, so the Kit layer's override stays as it was.
    expect(File::get("{$kits}/Base/Starter/.ddev/config.yaml"))->toBe(<<<'YAML'
        name: craft
        php_version: "8.4"
        database:
            type: mysql
            version: "8.0"
        web_environment:
          - FOO=bar

        YAML)
        ->and(File::get("{$kits}/Base/Kit/.ddev/config.yaml"))->toBe(<<<'YAML'
        name: demo
        database:
            type: postgres
            version: "17"

        YAML);
});
