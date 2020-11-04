<?php

namespace App\Console\Commands;

use App\Services\Parser;
use App\Services\Profiler;
use Illuminate\Console\Command;

/**
 * Command to profile adding entries to database.
 *
 * @package App\Console\Commands
 */
class ProfileAddEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile:entriestodb {message = null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * Create a new command instance.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser, Profiler $profiler)
    {
        $this->parser = $parser;
        $this->profiler = $profiler;

        parent::__construct();
    }

    /**
     * Profile adding entries to DB
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $this->profiler->fixCurrentPeakMemory();
        $this->profiler->startTimer();

        $this->parser->createTemporaryTable();

        $this->parser->validateAndParseCsvToDatabase();
        $this->echoProfilerData();

        return 0;
    }

    private function echoProfilerData() {
        $microtime = $this->profiler->getCurrentMeasureTime();
        $peakMemory = $this->profiler->getCurrentMeasurePeakMemory();

        $this->line(sprintf('%.6f s; %s bytes', $microtime, number_format($peakMemory, 0, '.', ',')));
    }
}
