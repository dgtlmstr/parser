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
     *
     * @var string
     */
    protected $signature = 'parser:update';

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
        try {
            $result = $this->parser->processFeed(); //+report info, ?report summary
            $this->line(date('[H:i:s] ') . "Update ok");
        } catch (Exception $exception) { // change exception
            $this->line(date('[H:i:s] ') . "Update error");
        }

        return 0;
    }
}
