<?php
namespace App\Services;

//use SplFileObject;
use League\Csv\Reader;

/**
 * Allow to read from CSV file.
 *
 * Class CsvReader
 * @package App\Services
 */
class CsvReader implements ReaderInterface
{
    /**
     * Create an instance of CSV Parser
     * @param FileService $filer
     */
    public function __construct() {
    }

    /**
     * Take filename from Filer, then create and return file pointer to CSV iterator.
     *
     * @throws \Exception
     */
    public function getFilePointer(FileService $fileService) : \Iterator
    {
        if ($fileService->isFileEmpty()) {
            throw new \Exception('Empty file');
        }

        $skipFirstLine = true; // move to settings

        /*$csvPointer = new SplFileObject($fileService->getFullFileName());
        $csvPointer->setFlags(SplFileObject::READ_CSV);
        $csvPointer->setCsvControl("\t", '"', '\\'); // move to settings

        if ($skipFirstLine) {
            $csvPointer->current();
            $csvPointer->next();
        }*/

        $reader = Reader::createFromPath($fileService->getFullFileName(), 'r');
        $reader->skipEmptyRecords();
        $reader->setHeaderOffset(0);
        $reader->setDelimiter("\t");
        return $reader->getRecords();
    }
}
