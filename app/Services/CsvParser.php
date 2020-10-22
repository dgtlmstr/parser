<?php
namespace App\Services;

use App\DTO\UserdataDTO;

class CsvParser {

    /**
     * Create an instance of CSV Parser
     */
    public function __construct() {
    }

    /**
     * Read data from CSV file and calls rowCallback function for each row found.
     *
     * @param Filer $filer
     * @param $rowCallback
     * @throws \Exception
     */
    public function parse(Filer $filer, $rowCallback) {
        if ($filer->isFileEmpty()) {
            trigger_error('Empty file');
        }

        if (!is_callable($rowCallback)) {
            throw new \Exception("Not callable callback $rowCallback");
        }

        $skipFirstLine = true;

        $handle = $filer->getFilePointerForReading();
        while (($row = fgetcsv($handle, 1000, "\t")) !== false) {
            if ($skipFirstLine) {
                $skipFirstLine = false;
                continue;
            }

            call_user_func($rowCallback, $row);
        }
    }
}
