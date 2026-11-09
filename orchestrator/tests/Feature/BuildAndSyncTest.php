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
