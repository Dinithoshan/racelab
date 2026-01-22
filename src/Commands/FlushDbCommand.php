<?php

namespace Dinithoshan\Racelab\Commands;

use Dinithoshan\Racelab\Config\TimelineConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlushDbCommand extends Command
{
    protected $signature = 'racelab:flush';

    protected $description = 'truncate the Racelab database';

    public function handle(): int
    {
        $this->info("Cleaning Racelab logs");

        try {
            DB::connection(TimelineConfig::connection())
                ->table(TimelineConfig::table())
                ->truncate();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Racelab logs cleaned!");
        return self::SUCCESS;
    }
}
