<?php
namespace App\Services;

use SplFileObject;

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
    public function getFilePointer(FileService $filer) : \Iterator {
        if ($filer->isFileEmpty()) {
            throw new \Exception('Empty file');
        }

        $csvPointer = new SplFileObject($filer->getFullFileName());
        $csvPointer->setFlags(SplFileObject::READ_CSV);
        $csvPointer->setCsvControl("\t", '"', '\\'); // move to settings

        $skipFirstLine = true; // move to settings
        if ($skipFirstLine) {
            $csvPointer->current();
            $csvPointer->next();
        }

        return $csvPointer;
    }
}
