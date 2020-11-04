<?php

namespace App\Console\Commands;

use App\Services\Parser;
use Illuminate\Console\Command;
use Mockery\Exception;
use Psy\Exception\FatalErrorException;

class ParseUpdate extends Command
{
    /**
     * The name and signature of the console command.
     * Can be used with parameter "--dry" for dry run.
     *
     * @var string
     */
    protected $signature = 'parser:update {--mode=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise user data from external .csv source with database.';

    /**
     * The instance of the Parser service.
     *
     * @var Parser
     */
    private $parser;

    /**
     * Create a new command instance.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;

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
            $this->parser->setRunMode(RUN_MODE_DRY);
        }

        $result = $this->parser->processFeed();
        $this->line(date('[H:i:s] ') . $result ? "Update okay" : "Update failed");

        return 0;
    }
}
