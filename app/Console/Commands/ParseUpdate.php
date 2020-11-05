<?php

namespace App\Console\Commands;

use App\Services\ParseManager;
use Illuminate\Console\Command;

/**
 * Command to run parser process.
 *
 * @package App\Console\Commands
 */
class ParseUpdate extends Command
{
    /**
     * The name and signature of the console command.
     * Use parameter "--mode=dry" for dry run.
     * Use parameter "--ignore-threshold-limit=true" to ignore threshold limit.
     *
     * @var string
     */
    protected $signature = 'parser:update {--mode=null} {--ignore-threshold-limit=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise user data from external .csv source with database.';

    /**
     * The instance of the Parser service.
     *
     * @var ParseManager
     */
    private $parseManager;

    /**
     * Create a new command instance.
     *
     * @param ParseManager $parseManager
     */
    public function __construct(ParseManager $parseManager)
    {
        $this->parseManager = $parseManager;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mode = $this->option('mode');
        if ($mode == 'dry') {
            $this->parseManager->setRunMode(RUN_MODE_DRY);
        }

        $ignoreThresholdLimit = $this->option('ignore-threshold-limit');
        if ($ignoreThresholdLimit == 'true') {
            $this->parseManager->setIgnoreThresholdLimit(true);
        }

        try {
            $this->parseManager->processFeed();
            $this->line(date('[H:i:s] ') . "Update okay");

            $reportManager = $this->parseManager->getReportManager();
            $reportManager->reportSummary();
            $this->line($reportManager->getSummary());
            $reportManager->reportInvalidEntries();

            return 0;
        } catch (\Exception $exception) {
            $this->line(date('[H:i:s] ') . "Update failed");
            return 1;
        }
    }
}
