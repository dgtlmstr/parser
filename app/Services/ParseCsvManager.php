<?php
namespace App\Services;

use App\Repositories\UserDataRepository;
use Illuminate\Support\Facades\Validator;

/**
 * Allow to process CSV.
 * Parse and and add entries to DB.
 *
 * @package App\Services
 */
class ParseCsvManager
{
    /**
     * Constant representing the count of records to be inserted to the temporary table at once.
     */
    public const PARSER_INSERT_MODE = 'bulkSql';

    /**
     * Constant representing the count of records to be inserted to the temporary table at once.
     */
    public const BULK_INSERT_LIMIT = 1000;

    /**
     * Constant representing the threshold limit.
     */
    public const UPDATE_THRESHOLD_LIMIT = 1000;

    /**
     * The instance of the Filer class.
     *
     * @var FileService
     */
    protected $fileService;

    /**
     * The Csv Parser instance.
     *
     * @var CsvReader
     */
    protected $csvReader;

    /**
     * The instance of the Userdata Repository.
     *
     * @var UserDataRepository
     */
    protected $userDataRepository;

    /**
     * The Array to buffer entries for bulk insert to the work table.
     *
     * @var array
     */
    protected $entriesBuffer;

    /**
     * Counter for Entries Buffer.
     *
     * @var int
     */
    protected $entriesBufferCount;

    /**
     * Create a new Parser instance.
     *
     * @param FileService $fileService
     * @param ReaderInterface $csvReader
     * @param UserDataRepository $userDataRepository
     */
    public function __construct(
        FileService $fileService,
        ReaderInterface $csvReader,
        UserDataRepository $userDataRepository
    ){
        $this->fileService = $fileService;
        $this->fileService->setFolder(env("UPDATE_DIR_PATH"));
        $this->fileService->setFilename(env("UPDATE_FILENAME"));

        $this->csvReader = $csvReader;
        $this->userDataRepository = $userDataRepository;  // replace with abstraction
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
    public function processCsv() {
        $filePointer = $this->getCsvIterator();
        if (empty($filePointer)) {
            return false;
        }

        for(;$filePointer->valid(); $filePointer->next()) {
            $row = $filePointer->current();
            if ($row[0] === null) { // try to avoid
                continue;
            }

            $entry = $this->mapEntry($row);
            $entry['status_id'] = $this->validateEntry($entry);
            $this->saveEntry($entry);
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
        if (!$this->fileService->fileExists()) {
            //$this->reportManager->line(REPORT_STATUS_ERROR, "Can't find CSV. Nothing to update");
            return null;
        }

        if ($this->fileService->isFileEmpty()) {
            //$this->reportManager->line(REPORT_STATUS_ERROR, "Bad CSV file! Update failed");
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
     * Return entry validation status.
     *
     * @param $userdata
     * @return int
     */
    protected function validateEntry($userdata) : int { //performance
        if ($this->validateIdentifier($userdata['identifier'])) {
            return ENTRY_STATUS_PARSE_BAD_ID;
        }

        $validator = Validator::make($userdata, [
            'identifier' => 'required|numeric', //find all empty and inform, stop on empty id
            'first_name' => 'required|string|max:30',
            'last_name' => 'required|string|max:30',
            'card_number' => 'required|string|max:16',
        ]);

        return $validator->fails() ? ENTRY_STATUS_PARSE_ERROR : ENTRY_STATUS_UNKNOWN;
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
     * Commit entries to DB
     */
    protected function commitEntries() {
        if ($this->entriesBufferCount == 0) return; //check just with the last commit

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
     * Add entry to DB buffer and bulk insert to the DB when the buffer is full.
     *
     * @param $userdata
     */
    protected function saveEntry($userdata) {
        $this->entriesBuffer[] = $userdata;
        $this->entriesBufferCount++;

        if (self::PARSER_INSERT_MODE == 'single' ||
            self::PARSER_INSERT_MODE == 'singleSql' ||
            self::PARSER_INSERT_MODE == 'singleSqlRaw' ||
            $this->entriesBufferCount == self::BULK_INSERT_LIMIT) { //make counter for better performance
            $this->commitEntries();
            $this->entriesBufferCount = 0;

            unset($this->entriesBuffer); //maybe remove, test performance
            $this->entriesBuffer = [];
        }
    }
}
