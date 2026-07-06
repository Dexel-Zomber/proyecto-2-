<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AcademicAlertService;

class RecalculateAcademicAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate academic alerts using moving averages and current thresholds.';

    public function handle()
    {
        $this->info('Starting academic alerts recalculation...');

        AcademicAlertService::recalculateAll();

        $this->info('Academic alerts recalculation completed.');

        return 0;
    }
}
