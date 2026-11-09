<?php

namespace App\Console\Commands;

use App\Maestro;
use Illuminate\Console\Command;

class MatrixCommand extends Command
{
    protected $signature = 'kits:matrix';

    protected $description = 'Print the kits as a GitHub Actions matrix';

    public function handle(Maestro $maestro): int
    {
        $this->line(json_encode(collect($maestro->kits())->map(fn (array $kit, string $handle) => [
            'kit' => $handle,
            'name' => $kit['name'],
            'repository' => $kit['repository'],
            'branch' => $kit['branch'],
            'craft' => $kit['craft'],
        ])->values()));

        return self::SUCCESS;
    }
}
