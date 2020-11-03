<?php
namespace App\Services;

use App\DTO\UpdateSummaryDTO;
use App\DTO\UserDataDTO;
use App\Repositories\UserDataRepository;
use Validator;

class Parser {

    /**
     * Constant representing the count of records to be inserted to the temporary table at once.
     */
    public const BULK_INSERT_LIMIT = 1000;

    /**
     * Constant representing the threshold.
     * It requires special parameter {paramname} to be used to apply mass update.
     */
    public const UPDATE_THRESHOLD_LIMIT = 1000;

    /**
     * The instance of the Filer class.
     *
     * @var Filer
     */
    private $filer;

    /**
     * The instance of the Userdata Repository.
     *
     * @var UserDataRepository
     */
    private $userDataRepository;

    /**
     * The summary stats DTO instance
     *
     * @var UpdateSummaryDTO
     */
    private $summary;

    /**
     * The Array to buffer entries for bulk insert to the work table.
     *
     * @var array
     */
    private $entriesBuffer;

    /**
     * The Csv Parser instance.
     *
     * @var CsvReader
     */
    private $csvReader;

    /**
     * Create a new Parser instance.
     *
     * @param Filer $filer
     * @param ReaderInterface $csvParser
     * @param UserDataRepository $userDataRepository
     */
    public function __construct(Filer $filer, ReaderInterface $csvParser, UserDataRepository $userDataRepository){
        $this->filer = $filer;
        $this->filer->setFolder(env("UPDATE_DIR_PATH"));
        $this->filer->setFilename(env("UPDATE_FILENAME"));

        $this->csvReader = $csvParser;

        $this->userDataRepository = $userDataRepository;
    }

    /**
     * Return true if update file exists, otherwise false.
     * File path options should be defined in an environment constants UPDATE_DIR_PATH and UPDATE_FILENAME
     *
     * @return bool
     */
    public function CheckIfUpdateExists() {
        return $this->filer->fileExists();
    }

    /**
     * Synchronise user data from external source file with DB.
     * // extend comment, algorithm
     *
     * @throws \Exception
     */
    public function processFeed() {
        //todo: custom exceptions
        $this->userDataRepository->createTemporaryTable();

        try {
            $this->validateAndParseCsv($this->filer); // name parsecsvtodb
        } catch (Exception $exception) { //change exception
            //todo: report with reporter
            throw new \Exception("Parse error");
        }

        try {
            $this->validateEntries();
            $this->markEntriesForOperations();
            $this->calcSummary();
        } catch (\Exception $exception) {
            //todo: report with reporter
            throw new \Exception("Validate error");
        }

        try {
            $this->validateUpdateBeforeApply();
        } catch (\Exception $exception) {
            //todo: report with reporter
            throw new \Exception("Above threshold");
        }

        // if 1 record with id not valid - break!
        // 6 columns but not 3 - is ok
        // based on fakecall param apply or not
        try {
            $this->applyUpdate();
        } catch (\Exception $exception) {
            //todo: report with reporter
            throw new \Exception("Update error");
        }
    }

    /**
     * Return parser update process statistics.
     *
     * @return UpdateSummaryDTO
     */
    public function getSummary() : UpdateSummaryDTO {
        return $this->summary;
    }

    /**
     * Remove user data source file.
     */
    public function DeleteUpdate() {
        $this->filer->fileRemove();
    }

    /**
     * Upload user data from a .csv file to userdata service table.
     * Validate each entry.
     * Add entry to DB.
     *
     * @throws \Exception
     */
    public function validateAndParseCsv() {

        // get raw iterator
        if (!$this->CheckIfUpdateExists()) {
            throw new \Exception("File not found");
        }

        if ($this->filer->isFileEmpty()) { //filer -> fileservice
            throw new \Exception('Empty file');
        }
        // get raw iterator

        $filePointer = $this->csvReader->getFilePointer($this->filer);

        while ($filePointer->valid()) {
            $row = $filePointer->current();

            $entry = $this->mapEntry($row);
            if ($this->validateEntry($entry)) { //save somewhere rejected rows
                $this->addEntryToDB($entry);
            }
            // id not found -> report on validate + add to db with status not valid
            // check performance (at once to db vs. csv + db + csv)

            $filePointer->next();
        }
    }

    /**
     * Method to be passed as a callback to CSV parser.
     * Adding new entry to userdata.
     * Expect array [integer (customer_id), string (first_name), string (last_name), string (card_number)] as an input parameter.
     *
     * @param array $csvEntry
     * @deprecated
     */
    public function handleEntryCallback(array $csvEntry) { //extract to class
        $entry = $this->mapEntry($csvEntry);
        if ($this->validateEntry($entry)) { //save somewhere rejected rows
            $this->addEntryToDB($entry);
        }
    }

    /**
     * Map raw CSV entry to userdata DTO.
     *
     * @param $csvEntry
     * @return array
     */
    protected function mapEntry($csvEntry) {
        //todo: mapping based on config
        $userdata = [];

        if (count($csvEntry) != 4) {
            return $userdata;
        }

        $userdata['identifier'] = $csvEntry[0];
        $userdata['first_name'] = $csvEntry[1];
        $userdata['last_name'] = $csvEntry[2];
        $userdata['card_number'] = $csvEntry[3];

        return $userdata;
    }

    /**
     * Check if entry from CSV passes type validation.
     *
     * @param $userdata
     * @return mixed
     */
    protected function validateEntry($userdata) { //performance
        $validator = Validator::make($userdata, [
            'identifier' => 'required|numeric', //find all empty and inform, stop on empty id
            'first_name' => 'required|string|max:30',
            'last_name' => 'required|string|max:30',
            'card_number' => 'required|string|max:16',
        ]);

        return !$validator->fails();
    }

    /**
     * Add entry to DB buffer and bulk insert to the DB when the buffer is full.
     *
     * @param $userdata
     */
    protected function addEntryToDB($userdata) {
        $this->entriesBuffer[] = $userdata;

        if (count($this->entriesBuffer) == self::BULK_INSERT_LIMIT) {
            $this->commitEntries();

            unset($this->entriesBuffer);
            $this->entriesBuffer = [];
        }
    }

    /**
     * Commit entries to DB
     */
    protected function commitEntries() {
        $this->userDataRepository->bulkInsert($this->entriesBuffer);
    }

    /**
     * Validate:
     * - identifier uniqueness
     * - card_number uniqueness
     * - field uniqueness against data existing in DB
     */
    protected function validateEntries() { // validate and set status
        //todo: make calls based on Config
        $this->userDataRepository->validateIdentifierDuplicates();
        $this->userDataRepository->validateCardNumberDuplicates();
        $this->userDataRepository->validateAgainstDb();  //change name
    }

    /**
     * Mark entries that are different between old and new table
     */
    protected function markEntriesForOperations() {
        //todo: define entry operations in Config
        $this->userDataRepository->markEntriesToAdd();
        $this->userDataRepository->markEntriesToUpdate();
        $this->userDataRepository->markEntriesToRestore();
        $this->userDataRepository->markEntriesNotChanged();
    }

    /**
     * Calculate summary on current update.
     */
    protected function calcSummary() {
        //todo: summary - to its own class
        $this->summary = $this->userDataRepository->getSummary();
    }

    /**
     * Check if number of records to be updated within threshold.
     * Throws exception if above threshold.
     *
     * @throws \Exception
     */
    protected function validateUpdateBeforeApply() { //check if update valid
        //todo: холостая обработка
        if ($this->summary->getToDelete() >= self::UPDATE_THRESHOLD_LIMIT ||
            $this->summary->getToUpdate() >= self::UPDATE_THRESHOLD_LIMIT)
            throw new \Exception("Above threshold");
        // + report
    }

    /**
     * Apply all inserts, updates, deletes and restores.
     * Trigger events.
     */
    protected function applyUpdate() { // errors can occur (UNIQUE CONTRAINT or smth)
        try {
            $this->userDataRepository->deleteRows(); // exceptions inside
            $this->userDataRepository->restoreRows();
            $this->userDataRepository->updateRows();
            $this->userDataRepository->addRows();
            $this->userDataRepository->reportNotChangedRows();
        } catch (\Exception $exception) {
            //todo: report(summary)
            $this->calcSummary();
            // break?
        }
    }
}
