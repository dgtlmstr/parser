<?php
namespace App\Services;

use App\Repositories\UserDataRepository;
use Validator;

/**
 * Feed manager allowing to handle and apply CSV update.
 *
 * @package App\Services
 */
class Parser {

    /**
     * Constant representing the count of records to be inserted to the temporary table at once.
     */
    public const PARSER_INSERT_MODE = 'bulkSql';

    /**
     * Constant representing the count of records to be inserted to the temporary table at once.
     */
    public const BULK_INSERT_LIMIT = 1000;

    /**
     * Constant representing the threshold.
     * @todo It requires special parameter {paramname} to be used to apply mass update.
     */
    public const UPDATE_THRESHOLD_LIMIT = 1000;

    /**
     * Desired mode to run parser in.
     * Either normal with DB updates or dry with reporting only.
     *
     * @var int
     */
    protected $runMode = RUN_MODE_NORMAL;

    /**
     * The instance of the Filer class.
     *
     * @var FileService
     */
    private $fileService;

    /**
     * The instance of the Userdata Repository.
     *
     * @var UserDataRepository
     */
    private $userDataRepository;

    /**
     * The Summary instance.
     *
     * @var Summary
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
     * @var ReportManager
     */
    private $reportManager;

    /**
     * Create a new Parser instance.
     *
     * @param FileService $fileService
     * @param ReaderInterface $csvParser
     * @param ReportManager $reportManager
     * @param Summary $summary
     * @param UserDataRepository $userDataRepository
     */
    public function __construct(
        FileService $fileService,
        ReaderInterface $csvParser,
        ReportManager $reportManager,
        Summary $summary,
        UserDataRepository $userDataRepository
    ){
        $this->fileService = $fileService;
        $this->fileService->setFolder(env("UPDATE_DIR_PATH"));
        $this->fileService->setFilename(env("UPDATE_FILENAME"));

        $this->csvReader = $csvParser;
        $this->reportManager = $reportManager;
        $this->summary = $summary;
        $this->userDataRepository = $userDataRepository;
    }

    /**
     * Synchronise user data from external source file with DB.
     *
     * 1. Create temporary table for entries.
     * 2. Parse CSV, validate raw data and add entries to the temporary table.
     * 3. Validate entries in DB (check duplicates, find rows that can't be deleted, etc) - mark invalid rows.
     * 4. Mark entries for operations.
     * 5. Calculate summary.
     * 6. Apply update if it's not a dry run.
     * 7. Report.
     *
     * Return true on successful processing and false in case of failure.
     *
     * @return bool
     * @throws \Exception
     */
    public function processFeed() : bool {
        //todo: custom exceptions
        $this->createTemporaryTable();

        if (!$this->validateAndParseCsvToDatabase()) {
            return false;
        }

        $this->markInvalidEntries();
        $this->reportInvalidEntries();

        $this->markEntriesForOperations();
        $this->calcSummary();

        if (!$this->checkIfUpdateCanBeApplied()) {
            $this->reportSummary();
            return false;
        }

        // in dry mode it will report only
        // should we implement separate method for dry run?
        $this->applyUpdateWithReporting();
        $this->calcSummary(); // recalc
        $this->reportSummary();

        return true;
    }

    /**
     * Return true if update file exists, otherwise false.
     * File path options should be defined in an environment constants UPDATE_DIR_PATH and UPDATE_FILENAME
     *
     * @return bool
     */
    public function CheckIfUpdateExists() {
        return $this->fileService->fileExists();
    }

    /**
     * Remove user data source file.
     */
    public function DeleteUpdate() {
        $this->fileService->fileRemove();
    }

    /**
     * Check if CSV okay.
     * Upload user data from a CSV file to the temporary DB table.
     * Validate each entry when parsing. Break and report if bad identifier found.
     *
     * Return true in case of success otherwise false.
     *
     * @return bool
     * @throws \Exception
     */
    public function validateAndParseCsvToDatabase() {
        $filePointer = $this->getCsvIterator();
        if (empty($filePointer)) {
            return false;
        }

        //todo: check if loop can be done in a shorter way (eg, foreach)
        while ($filePointer->valid()) {
            $row = $filePointer->current();
            if ($row[0] === null) continue;

            $entry = $this->mapEntry($row);

            if (!$this->validateEntry($entry)) {
                $entry['status_id'] = ENTRY_STATUS_PARSE_ERROR;
            }

            if (!$this->validateIdentifier($entry['identifier'])) {
                $this->reportManager->line(REPORT_STATUS_ERROR, "Bad identifier found! Update failed");
                return false;
            }

            $this->addEntryToDB($entry);

            $filePointer->next();
        }

        // commit the final few rows from buffer when insert mode is bulk
        if (self::PARSER_INSERT_MODE == 'bulk' ||
            self::PARSER_INSERT_MODE == 'bulkSql' ||
            self::PARSER_INSERT_MODE == 'bulkSqlRaw') {
            $this->commitEntries();
        }

        return true;
    }

    /**
     * Return CSV iterator or null if file not found.
     * Throws exception when CSV is in bad format (empty).
     *
     * @return \Iterator|null
     * @throws \Exception
     */
    public function getCsvIterator() {
        if (!$this->CheckIfUpdateExists()) {
            $this->reportManager->line(REPORT_STATUS_ERROR, "Can't find CSV. Nothing to update");
            return null;
        }

        if ($this->fileService->isFileEmpty()) {
            $this->reportManager->line(REPORT_STATUS_ERROR, "Bad CSV file! Update failed");
            return null;
        }

        return $this->csvReader->getFilePointer($this->fileService);
    }

    /**
     * Map raw CSV entry to user data DTO.
     *
     * @param $csvEntry
     * @return array
     */
    protected function mapEntry($csvEntry) {
        //todo: mapping based on config
        $userdata = [];

        $userdata['identifier'] = $csvEntry[0];
        $userdata['first_name'] = $csvEntry[1] ?? "";
        $userdata['last_name'] = $csvEntry[2] ?? "";
        $userdata['card_number'] = $csvEntry[3] ?? "";

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

        if (self::PARSER_INSERT_MODE == 'single' ||
            self::PARSER_INSERT_MODE == 'singleSql' ||
            self::PARSER_INSERT_MODE == 'singleSqlRaw' ||
            count($this->entriesBuffer) == self::BULK_INSERT_LIMIT) {
            $this->commitEntries();

            unset($this->entriesBuffer);
            $this->entriesBuffer = [];
        }
    }

    /**
     * Commit entries to DB
     */
    protected function commitEntries() {
        if (count($this->entriesBuffer) == 0) return;

        if (self::PARSER_INSERT_MODE == 'bulk') {
            $this->userDataRepository->bulkInsert($this->entriesBuffer);
        } else if (self::PARSER_INSERT_MODE == 'single') {
            $this->userDataRepository->singleInsert($this->entriesBuffer[0]);
        } else if (self::PARSER_INSERT_MODE == 'bulkSql') {
            $this->userDataRepository->bulkInsertSql($this->entriesBuffer);
        } else if (self::PARSER_INSERT_MODE == 'singleSql') {
            $this->userDataRepository->singleInsertSql($this->entriesBuffer[0]);
        } else if (self::PARSER_INSERT_MODE == 'bulkSqlRaw') {
            $this->userDataRepository->bulkInsertSqlRaw($this->entriesBuffer);
        } else if (self::PARSER_INSERT_MODE == 'singleSqlRaw') {
            $this->userDataRepository->singleInsertSqlRaw($this->entriesBuffer[0]);
        }
    }

    /**
     * Validate:
     * - identifier uniqueness
     * - card_number uniqueness
     * - field uniqueness against data existing in DB
     */
    protected function markInvalidEntries() {
        //todo: make calls based on Config
        $this->userDataRepository->markIdentifierDuplicates();
        $this->userDataRepository->markCardNumberDuplicates();
        $this->userDataRepository->markEntriesWithCardNumbersAlreadyTaken();
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
        $this->summary->setSummary($this->userDataRepository->getSummary());
        $this->summary->setCountToDelete($this->userDataRepository->countEntriesToDelete());
        $this->summary->setCountCantDelete($this->userDataRepository->countEntriesCantDelete());
        $this->summary->setTotalEntries($this->userDataRepository->countTotalEntries());
    }

    /**
     * Check if number of records to be updated within threshold.
     * Throws exception if above threshold.
     *
     * @returns bool
     */
    protected function checkIfUpdateCanBeApplied() : bool {
        if ($this->summary->getCountToDelete() >= self::UPDATE_THRESHOLD_LIMIT ||
            $this->summary->getCountToUpdate() >= self::UPDATE_THRESHOLD_LIMIT) {

            $this->reportManager->block(REPORT_STATUS_INFO, $this->summary);
            $this->reportManager->line(REPORT_STATUS_ERROR, 'Update row count is above threshold! Update failed');

            return false;
        }

        return true;
    }

    /**
     * Apply all inserts, updates, deletes and restores.
     * Trigger events.
     * If the mode is dry then report ony.
     */
    protected function applyUpdateWithReporting() { // errors can occur (UNIQUE CONTRAINT or smth)
        $this->userDataRepository->deleteRows();
        $this->userDataRepository->restoreRows();
        $this->userDataRepository->updateRows();
        $this->userDataRepository->addRows();
        $this->userDataRepository->reportNotChangedRows();
    }

    /**
     * Create temporary table to upload entries from CSV.
     */
    public function createTemporaryTable()
    {
        $this->userDataRepository->createTemporaryTable();
    }

    /**
     * Set run mode, either normal or dry.
     *
     * @param $runMode
     * @throws \Exception
     */
    public function setRunMode($runMode) {
        if (!in_array($runMode, [RUN_MODE_NORMAL, RUN_MODE_DRY])) {
            throw new \Exception('Unknown mode');
        }

        $this->runMode = $runMode;
    }

    /**
     * Validates identifier.
     * It must not be empty.
     *
     * @param $identifier
     * @return false
     */
    protected function validateIdentifier($identifier) {
        return !empty($identifier);
    }

    /**
     * Report summary totals.
     */
    protected function reportSummary() {
        $this->reportManager->block(REPORT_STATUS_INFO, $this->summary);
    }

    /**
     * Report each invalid entry information.
     */
    protected function reportInvalidEntries() {
        $this->userDataRepository->reportIdentifierDuplicates();
        $this->userDataRepository->reportCardNumberDuplicates();
        $this->userDataRepository->reportEntriesWithCardNumbersAlreadyTaken();
    }
}
