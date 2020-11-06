<?php
namespace App\Services;

use App\DTO\EntryStatusDTO;
use App\Repositories\EntryRepository;
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
     * @var EntryRepository
     */
    protected $entryRepository;

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
     * Warning is being set on bad identifier found during parsing process.
     *
     * @var bool
     */
    protected $badIdFound = false;

    /**
     * Create a new Parser instance.
     *
     * @param FileService $fileService
     * @param ReaderInterface $csvReader
     * @param EntryRepository $entryRepository
     */
    public function __construct(
        FileService $fileService,
        ReaderInterface $csvReader,
        EntryRepository $entryRepository
    ){
        $this->fileService = $fileService;
        $this->fileService->setFolder(env("UPDATE_DIR_PATH"));
        $this->fileService->setFilename(env("UPDATE_FILENAME"));

        $this->csvReader = $csvReader;
        $this->entryRepository = $entryRepository;
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

        foreach ($filePointer as $row) {
        //for(;$filePointer->valid(); $filePointer->next()) {
            //$row = $filePointer->current();
            //if ($row[0] === null) { // try to avoid, maybe League CSV
            //    continue;
            //}

            $entry = $this->mapEntry($row);
            $entryStatusList = $this->validateEntry($entry);
            $entry = $this->prepareEntryStatus($entry, $entryStatusList);
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
     * @param $record
     * @return array
     */
    protected function mapEntry($record) {
        //todo: mapping based on config
        $entry = [];

        $csvEntry = array_values($record);
        $entry['identifier'] = !empty($csvEntry[0]) ? $csvEntry[0] : 0;
        $entry['first_name'] = $csvEntry[1] ?? "";
        $entry['last_name'] = $csvEntry[2] ?? "";
        $entry['card_number'] = $csvEntry[3] ?? "";

        $entry['raw_data'] = json_encode($record);

        return $entry;
    }

    /**
     * Check if entry from CSV passes type validation.
     * Return entry validation status array
     *
     * @param $entry
     * @return array
     */
    protected function validateEntry($entry) : array { //performance
        $entryStatusList = [];

        if (!$this->validateIdentifier($entry['identifier'])) {
            $entryStatusList[] = new EntryStatusDTO(ENTRY_STATUS_PARSE_BAD_ID, "Bad identifier");
            $this->badIdFound($entryStatusList);
        }

        $validator = Validator::make($entry, [
            'identifier' => 'required|numeric',
            'first_name' => 'required|string|max:30',
            'last_name' => 'required|string|max:30',
            'card_number' => 'required|string|max:16',
        ]);

        if ($validator->fails()) {
            $entryStatusList[] = new EntryStatusDTO(ENTRY_STATUS_PARSE_ERROR, "Entry parse validation fails");
        }

        if (empty($entryStatusList)) {
            $entryStatusList[] = new EntryStatusDTO(ENTRY_STATUS_UNKNOWN, "");
        }

        return $entryStatusList;
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
        if (self::PARSER_INSERT_MODE == 'bulk') {
            $this->entryRepository->bulkInsert($this->entriesBuffer);
        } else if (self::PARSER_INSERT_MODE == 'single') {
            $this->entryRepository->singleInsert($this->entriesBuffer[0]);
        } else if (self::PARSER_INSERT_MODE == 'bulkSql') {
            $this->entryRepository->bulkInsertSql($this->entriesBuffer);
        } else if (self::PARSER_INSERT_MODE == 'singleSql') {
            $this->entryRepository->singleInsertSql($this->entriesBuffer[0]);
        } else if (self::PARSER_INSERT_MODE == 'bulkSqlRaw') {
            $this->entryRepository->bulkInsertSqlRaw($this->entriesBuffer);
        } else if (self::PARSER_INSERT_MODE == 'singleSqlRaw') {
            $this->entryRepository->singleInsertSqlRaw($this->entriesBuffer[0]);
        }
    }

    /**
     * Add entry to DB buffer and bulk insert to the DB when the buffer is full.
     *
     * @param $entry
     */
    protected function saveEntry($entry) {
        $this->entriesBuffer[] = $entry;
        $this->entriesBufferCount++;

        if (self::PARSER_INSERT_MODE == 'single' ||
            self::PARSER_INSERT_MODE == 'singleSql' ||
            self::PARSER_INSERT_MODE == 'singleSqlRaw' ||
            $this->entriesBufferCount == self::BULK_INSERT_LIMIT) {
            $this->commitEntries();
            $this->entriesBufferCount = 0;

            unset($this->entriesBuffer); //maybe remove, test performance
            $this->entriesBuffer = [];
        }
    }

    /**
     * Add entry status id and details to the entry item.
     *
     * @param array $entry
     * @param array $entryStatusList
     * @return array
     */
    protected function prepareEntryStatus(array $entry, array $entryStatusList) {
        $statusId = ENTRY_STATUS_UNKNOWN;
        $statusDetails = "";

        foreach ($entryStatusList as $entryStatus) {
            if ($entryStatus->getStatusid() != ENTRY_STATUS_UNKNOWN) {
                $statusId = ENTRY_STATUS_REJECTED;
                $statusDetails .= (!empty($statusDetails) ? "; " : "") . "{$entryStatus->getStatusDetails()}";
            }
        }

        $entry['status_id'] = $statusId;
        $entry['status_details'] = $statusDetails;

        return $entry;
    }

    /**
     * Set Ba Id warning if found bad identifier.
     *
     * @param array $entryStatusList
     */
    protected function badIdFound(array $entryStatusList) {
        $this->badIdFound = true;
    }

    /**
     * Return true when bad identifiers where found during parsing process.
     *
     * @return bool
     */
    public function hasBadIds() {
        return $this->badIdFound;
    }
}
