<?php

namespace Dinithoshan\Racelab\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'racelab:install';

    protected $description = 'Run the Racelab database migrations.';

    public function handle(): int
    {
        $connection = config('racelab.database.connection') ?? 'racelab_timeline';

        $this->info("Running Racelab migrations using the '{$connection}' connection...");

        $this->call('migrate', [
            '--database' => $connection,
            '--path' => __DIR__ . '/../../database/migrations',
            '--realpath' => true,
        ]);

        $this->info('Racelab installation complete.');

        return self::SUCCESS;
    }
}
