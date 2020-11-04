<?php

namespace App\Console\Commands;

use App\Services\CsvFakerGenerator;
use App\Services\FileService;
use Illuminate\Console\Command;

class CsvGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:csv {filename} {rowNumber}';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Command description';
    /**
     * @var FileService
     */

    private $filer;
    /**
     * @var CsvFakerGenerator
     */
    private $generator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FileService $filer, CsvFakerGenerator $generator)
    {
        $this->filer = $filer;
        $this->filer->setFolder(env("UPDATE_DIR_PATH"));

        $this->generator = $generator;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filename = $this->argument('filename');
        $rowNumber = $this->argument('rowNumber');

        $this->filer->setFilename($filename);
        $this->generator->generateCsv($this->filer, $rowNumber);

        return 0;
    }
}
