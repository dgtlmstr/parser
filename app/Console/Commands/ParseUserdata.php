<?php

namespace App\Console\Commands;

use App\Services\Parser;
use Illuminate\Console\Command;

class ParseUserdata extends Command
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
        $result = $this->parser->Update();

        switch ($result) {
            case Parser::PARSER_OK:
                $stats = $this->parser->getSummary();
                $this->line(date('[H:i:s] ') . "Update passed");
                $this->line(sprintf('New entries: %d', $stats->getNewEntries()));
                $this->line(sprintf('Deleted entries: %d', $stats->getDeletedEntries()));
                $this->line(sprintf('Restored entries: %d', $stats->getRestoredEntries()));
                $this->line(sprintf('Updated entries: %d', $stats->getUpdatedEntries()));
                $this->line(sprintf('Rejected entries: %d', $stats->getRejectedEntries()));

                $this->parser->DeleteUpdate();
                break;

            case Parser::PARSER_NO_FILE_UPDATE:
                $this->line(date('[H:i:s] ') . "Update failed. File not found");
                break;

            case Parser::PARSER_FAILED:
                $this->line(date('[H:i:s] ') . "Update failed. Parsing failed");
                break;

            case Parser::PARSER_ABOVE_THRESHOLD:
                $this->line(date('[H:i:s] ') . "Update stopped. Update entry count is above threshold");
                break;
        }
        return 0;
    }
}
